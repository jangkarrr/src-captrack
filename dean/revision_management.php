<?php
session_start();
include '../config/database.php';
require_once '../assets/includes/role_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../users/login.php");
    exit();
}

$email = $_SESSION['email'];

// Fetch user role
// Check if user is employee or student
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'employee';
$table = ($user_type === 'student') ? 'student_details' : 'employees';
$userQuery = $conn->prepare("SELECT id, role FROM $table WHERE email = ?");
$userQuery->bind_param("s", $email);
$userQuery->execute();
$userResult = $userQuery->get_result();
$user = $userResult->fetch_assoc();
$userQuery->close();

// Authorize as dean using multi-role support
if (!isset($_SESSION['user_data']) || !hasRole($_SESSION['user_data'], 'dean')) {
    header("Location: ../users/login.php?error=unauthorized_access");
    exit();
}

$deanId = $user['id'];

// Note: Dean can only view revisions, not approve them
// Approval functionality is handled by the Capstone Professor (Adviser)

// Get all pending revisions grouped by group code
$revisionsQuery = "
    SELECT dr.*, u.first_name, u.last_name, u.email, pw.project_title, s.group_code,
           pw.date_created as project_created,
           td.date_submitted as title_defense_submitted,
           fd.date_submitted as final_defense_submitted,
           td.status as title_defense_status,
           fd.status as final_defense_status
    FROM defense_revisions dr
    INNER JOIN student_details sd ON dr.student_id = sd.id
    INNER JOIN project_working_titles pw ON dr.project_id = pw.id
    LEFT JOIN students s ON sd.email = s.email
    LEFT JOIN title_defense td ON (dr.defense_type = 'title' AND dr.defense_id = td.id)
    LEFT JOIN final_defense fd ON (dr.defense_type = 'final' AND dr.defense_id = fd.id)
    WHERE dr.status IN ('pending', 'under_review')
    ORDER BY s.group_code ASC, dr.date_submitted DESC
";
$revisionsStmt = $conn->prepare($revisionsQuery);
$revisionsStmt->execute();
$revisionsResult = $revisionsStmt->get_result();
$revisionsByGroup = [];
while ($revision = $revisionsResult->fetch_assoc()) {
    $groupCode = $revision['group_code'] ?? 'No Group';
    if (!isset($revisionsByGroup[$groupCode])) {
        $revisionsByGroup[$groupCode] = [];
    }
    $revisionsByGroup[$groupCode][] = $revision;
}
$revisionsStmt->close();

// Get recent approvals made by this dean
$recentApprovalsQuery = "
    SELECT dr.*, u.first_name, u.last_name, u.email, pw.project_title, s.group_code,
           pw.date_created as project_created,
           td.date_submitted as title_defense_submitted,
           fd.date_submitted as final_defense_submitted,
           td.status as title_defense_status,
           fd.status as final_defense_status
    FROM defense_revisions dr
    INNER JOIN student_details sd ON dr.student_id = sd.id
    INNER JOIN project_working_titles pw ON dr.project_id = pw.id
    LEFT JOIN students s ON sd.email = s.email
    LEFT JOIN title_defense td ON (dr.defense_type = 'title' AND dr.defense_id = td.id)
    LEFT JOIN final_defense fd ON (dr.defense_type = 'final' AND dr.defense_id = fd.id)
    WHERE dr.reviewed_by = ? AND dr.status IN ('approved', 'rejected')
    ORDER BY dr.date_reviewed DESC
    LIMIT 10
";
$recentApprovalsStmt = $conn->prepare($recentApprovalsQuery);
$recentApprovalsStmt->bind_param("i", $deanId);
$recentApprovalsStmt->execute();
$recentApprovalsResult = $recentApprovalsStmt->get_result();
$recentApprovals = [];
while ($approval = $recentApprovalsResult->fetch_assoc()) {
    $recentApprovals[] = $approval;
}
$recentApprovalsStmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Captrack Vault - Revision Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/captrack.png" type="image/png">
</head>
<body>

<?php include '../assets/includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <?php include '../assets/includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="bi bi-file-earmark-arrow-up"></i> Revision Management</h4>
            <a href="home.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4" id="revisionTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                    <i class="bi bi-clock-history me-2"></i>Pending Revisions
                    <?php if (!empty($revisionsByGroup)): ?>
                        <span class="badge bg-warning ms-2"><?php echo array_sum(array_map('count', $revisionsByGroup)); ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="recent-tab" data-bs-toggle="tab" data-bs-target="#recent" type="button" role="tab">
                    <i class="bi bi-check-circle-fill me-2"></i>Recent Approvals
                    <?php if (!empty($recentApprovals)): ?>
                        <span class="badge bg-success ms-2"><?php echo count($recentApprovals); ?></span>
                    <?php endif; ?>
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="revisionTabContent">
            <!-- Pending Revisions Tab -->
            <div class="tab-pane fade show active" id="pending" role="tabpanel">

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php endif; ?>

                <?php if (empty($revisionsByGroup)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No pending revisions at this time.
                    </div>
                <?php else: ?>
            <?php foreach ($revisionsByGroup as $groupCode => $revisions): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-people-fill me-2"></i>
                            Group: <?php echo htmlspecialchars($groupCode); ?>
                            <span class="badge bg-light text-dark ms-2"><?php echo count($revisions); ?> revision(s)</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($revisions as $revision): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card border">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>Revision #<?php echo $revision['id']; ?></strong>
                                                <span class="badge bg-<?php echo $revision['status'] === 'approved' ? 'success' : ($revision['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                                    <?php echo ucfirst($revision['status']); ?>
                                                </span>
                                            </div>
                                            <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($revision['date_submitted'])); ?></small>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Project:</strong> <?php echo htmlspecialchars($revision['project_title']); ?></p>
                                                    <p><strong>Student:</strong> <?php echo htmlspecialchars($revision['first_name'] . ' ' . $revision['last_name']); ?></p>
                                                    <p><strong>Defense Type:</strong> <?php echo ucfirst($revision['defense_type']); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>File:</strong> <?php echo htmlspecialchars($revision['original_filename']); ?></p>
                                                    <p><strong>Size:</strong> <?php echo number_format($revision['file_size'] / 1024, 2); ?> KB</p>
                                                    <p><strong>Submitted:</strong> <?php echo date('M d, Y H:i', strtotime($revision['date_submitted'])); ?></p>
                                                </div>
                                            </div>
                                            
                                            <!-- Timeline and Context Section -->
                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <div class="card bg-light">
                                                        <div class="card-body">
                                                            <h6 class="card-title"><i class="bi bi-clock-history me-2"></i>Revision Timeline & Context</h6>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="timeline-item mb-2">
                                                                        <small class="text-muted">Project Created:</small><br>
                                                                        <strong><?php echo date('M d, Y', strtotime($revision['project_created'])); ?></strong>
                                                                    </div>
                                                                    <div class="timeline-item mb-2">
                                                                        <small class="text-muted">Original Defense:</small><br>
                                                                        <strong>
                                                                            <?php 
                                                                            if ($revision['defense_type'] === 'title' && $revision['title_defense_submitted']) {
                                                                                echo date('M d, Y', strtotime($revision['title_defense_submitted']));
                                                                            } elseif ($revision['defense_type'] === 'final' && $revision['final_defense_submitted']) {
                                                                                echo date('M d, Y', strtotime($revision['final_defense_submitted']));
                                                                            } else {
                                                                                echo 'Not submitted';
                                                                            }
                                                                            ?>
                                                                        </strong>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="timeline-item mb-2">
                                                                        <small class="text-muted">Revision Submitted:</small><br>
                                                                        <strong><?php echo date('M d, Y H:i', strtotime($revision['date_submitted'])); ?></strong>
                                                                    </div>
                                                                    <div class="timeline-item mb-2">
                                                                        <small class="text-muted">Days Pending:</small><br>
                                                                        <strong class="text-warning">
                                                                            <?php 
                                                                            $daysPending = floor((time() - strtotime($revision['date_submitted'])) / (60 * 60 * 24));
                                                                            echo $daysPending . ' day' . ($daysPending != 1 ? 's' : '');
                                                                            ?>
                                                                        </strong>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Defense Status -->
                                                            <div class="row mt-2">
                                                                <div class="col-12">
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <small class="text-muted">Defense Status:</small>
                                                                        <span class="badge bg-<?php 
                                                                            $defenseStatus = $revision['defense_type'] === 'title' ? $revision['title_defense_status'] : $revision['final_defense_status'];
                                                                            echo $defenseStatus === 'approved' ? 'success' : ($defenseStatus === 'rejected' ? 'danger' : 'warning');
                                                                        ?>">
                                                                            <?php echo ucfirst($defenseStatus ?? 'pending'); ?>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex gap-2 mb-3">
                                                <a href="<?php echo htmlspecialchars($revision['revision_file']); ?>" target="_blank" class="btn btn-primary btn-sm">
                                                    <i class="bi bi-eye"></i> View Revision
                                                </a>
                                                <a href="<?php echo htmlspecialchars($revision['revision_file']); ?>" download class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            </div>

                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle me-2"></i>
                                                <strong>Note:</strong> Only the Capstone Professor (Adviser) can approve or reject revisions. You can view and download the revision files.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Recent Approvals Tab -->
            <div class="tab-pane fade" id="recent" role="tabpanel">
                <?php if (empty($recentApprovals)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No recent approvals found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Group</th>
                                    <th>Student</th>
                                    <th>Project</th>
                                    <th>Defense Type</th>
                                    <th>Decision</th>
                                    <th>Date Reviewed</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentApprovals as $approval): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($approval['group_code'] ?? 'N/A'); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($approval['first_name'] . ' ' . $approval['last_name']); ?></td>
                                        <td class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($approval['project_title']); ?>">
                                            <?php echo htmlspecialchars($approval['project_title']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo ucfirst($approval['defense_type']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $approval['status'] === 'approved' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($approval['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($approval['date_reviewed'])); ?></td>
                                        <td class="text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($approval['notes'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($approval['notes'] ?? 'No notes'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>

<script>
// Auto-dismiss success alerts
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.querySelector('.alert.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.transition = 'opacity 0.5s ease';
            successAlert.style.opacity = '0';
            setTimeout(() => successAlert.remove(), 500);
        }, 3000);
    }
});
</script>

</body>
</html>
