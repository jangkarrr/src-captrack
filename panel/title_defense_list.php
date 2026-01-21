<?php
session_start();
include '../config/database.php';
require_once '../assets/includes/role_functions.php';

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../users/login.php");
    exit();
}

$email = $_SESSION['email'];

// Authorize as panelist using multi-role support
if (!isset($_SESSION['user_data']) || !hasRole($_SESSION['user_data'], 'panelist')) {
    header("Location: ../users/login.php?error=unauthorized_access");
    exit();
}

// Determine panelist user id from session
$user = $_SESSION['user_data'];

$panelistId = isset($user['id']) ? (int)$user['id'] : (int)$_SESSION['user_id'];

// Ensure active role reflects Panelist when visiting panel pages
if (!isset($_SESSION['active_role']) || $_SESSION['active_role'] !== 'panelist') {
    $_SESSION['active_role'] = 'panelist';
    $_SESSION['role'] = 'panelist';
}

// Helper function to detect primary key column
function getPrimaryKeyColumn($conn, $table) {
    $pk_column = 'id'; // Default
    $columns_result = $conn->query("SHOW COLUMNS FROM $table");
    if ($columns_result) {
        while ($col = $columns_result->fetch_assoc()) {
            if ($col['Key'] === 'PRI') {
                $pk_column = $col['Field'];
                break;
            }
        }
        $columns_result->close();
    }
    return $pk_column;
}

// Detect column names for students and student_details tables
$students_columns = [];
$students_cols_result = $conn->query("SHOW COLUMNS FROM students");
if ($students_cols_result) {
    while ($col = $students_cols_result->fetch_assoc()) {
        $students_columns[] = $col['Field'];
    }
    $students_cols_result->close();
}
$students_pk = getPrimaryKeyColumn($conn, 'students');
$hasGroupCode = in_array('group_code', $students_columns);
$hasGroupId = in_array('group_id', $students_columns);

$sd_columns = [];
$sd_cols_result = $conn->query("SHOW COLUMNS FROM student_details");
if ($sd_cols_result) {
    while ($col = $sd_cols_result->fetch_assoc()) {
        $sd_columns[] = $col['Field'];
    }
    $sd_cols_result->close();
}
$sd_pk = getPrimaryKeyColumn($conn, 'student_details');
$sd_hasGroupCode = in_array('group_code', $sd_columns);

// Build group_code field and condition
$groupCodeField = $sd_hasGroupCode ? 'sd.group_code' : ($hasGroupCode ? 's.group_code' : 'NULL AS group_code');
$groupCodeCondition = $sd_hasGroupCode ? 'sd.group_code = pa.group_code' : ($hasGroupCode ? 's.group_code = pa.group_code' : '1=0');

// Set default status filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Modified query to only show submissions from assigned groups with grading status and revision info
$query = "
    SELECT td.id, td.project_id, td.pdf_file, td.status, td.scheduled_date, 
           pw.project_title, $groupCodeField AS group_code,
           CASE 
               WHEN EXISTS (
                   SELECT 1 FROM panelist_grades pg 
                   WHERE pg.panelist_id = ? AND pg.defense_type = 'title' AND pg.defense_id = td.id
               ) THEN 'graded'
               ELSE 'pending_grading'
           END as grading_status,
           (SELECT COUNT(*) FROM defense_revisions dr 
            WHERE dr.project_id = td.project_id AND dr.defense_type = 'title' AND dr.defense_id = td.id 
            AND dr.status IN ('pending', 'under_review')) as pending_revisions
    FROM title_defense td
    INNER JOIN project_working_titles pw ON td.project_id = pw.id
    INNER JOIN student_details sd ON td.submitted_by = sd.email
    " . ($hasGroupCode || $hasGroupId ? "LEFT JOIN students s ON sd.student_id = s.$students_pk" : "") . "
    INNER JOIN panel_assignments pa ON ($groupCodeCondition)
    WHERE pa.panelist_id = ? AND pa.status = 'active'
";

// Modify query based on status filter
if ($statusFilter !== 'all') {
    if ($statusFilter === 'graded') {
        $query .= " AND EXISTS (
            SELECT 1 FROM panelist_grades pg 
            WHERE pg.panelist_id = ? AND pg.defense_type = 'title' AND pg.defense_id = td.id
        )";
    } elseif ($statusFilter === 'pending_grading') {
        $query .= " AND NOT EXISTS (
            SELECT 1 FROM panelist_grades pg 
            WHERE pg.panelist_id = ? AND pg.defense_type = 'title' AND pg.defense_id = td.id
        )";
    } else {
        $query .= " AND td.status = ?";
    }
}
$query .= " ORDER BY grading_status ASC, td.date_submitted DESC";

$submissionsQuery = $conn->prepare($query);
if ($statusFilter === 'graded' || $statusFilter === 'pending_grading') {
	$submissionsQuery->bind_param("iii", $panelistId, $panelistId, $panelistId);
} elseif ($statusFilter !== 'all') {
	$submissionsQuery->bind_param("iis", $panelistId, $panelistId, $statusFilter);
} else {
	$submissionsQuery->bind_param("ii", $panelistId, $panelistId);
}
$submissionsQuery->execute();
$submissions = $submissionsQuery->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Captrack Vault Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/project.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/captrack.png" type="image/png">
</head>
<body>

<?php include '../assets/includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <?php include '../assets/includes/navbar.php'; ?>

    <div class="container">
        <h4>Title Defense Submissions</h4>

        <!-- Filter Buttons -->
        <div class="filter-buttons d-none d-md-flex">
            <a href="title_defense_list.php?status=all" class="<?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All</a>
            <a href="title_defense_list.php?status=pending_grading" class="<?php echo $statusFilter === 'pending_grading' ? 'active' : ''; ?>">Pending Grading</a>
            <a href="title_defense_list.php?status=graded" class="<?php echo $statusFilter === 'graded' ? 'active' : ''; ?>">Graded</a>
        </div>

        <!-- Filter Dropdown for Mobile -->
        <div class="filter-dropdown d-md-none">
            <div class="dropdown">
                <button class="btn dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php 
                    $filterLabels = [
                        'all' => 'All',
                        'pending_grading' => 'Pending Grading',
                        'graded' => 'Graded'
                    ];
                    echo isset($filterLabels[$statusFilter]) ? $filterLabels[$statusFilter] : 'All';
                    ?>
                </button>
                <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                    <li><a class="dropdown-item <?php echo $statusFilter === 'all' ? 'active' : ''; ?>" href="title_defense_list.php?status=all">All</a></li>
                    <li><a class="dropdown-item <?php echo $statusFilter === 'pending_grading' ? 'active' : ''; ?>" href="title_defense_list.php?status=pending_grading">Pending Grading</a></li>
                    <li><a class="dropdown-item <?php echo $statusFilter === 'graded' ? 'active' : ''; ?>" href="title_defense_list.php?status=graded">Graded</a></li>
                </ul>
            </div>
        </div>

        <!-- Submissions Table -->
        <?php if ($submissions->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Project Title</th>
                            <th scope="col" class="text-center sortable" data-sort="name">Group Code <i class="bi bi-sort-numeric-down"></i></th>
                            <th class="text-center">Schedule Date</th>
                            <th scope="col" class="text-center sortable" data-sort="status">Grading Status <i class="bi bi-sort-alpha-down"></i></th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $submissions->fetch_assoc()): ?>
                            <tr>
                                <td class="truncate" title="<?php echo htmlspecialchars($row['project_title']); ?>">
                                    <?php echo htmlspecialchars($row['project_title']); ?>
                                    <?php if ($row['pending_revisions'] > 0): ?>
                                        <span class="badge bg-info ms-2" title="Pending Revisions">
                                            <i class="bi bi-file-earmark-arrow-up"></i> <?php echo $row['pending_revisions']; ?> Revision<?php echo $row['pending_revisions'] > 1 ? 's' : ''; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo htmlspecialchars(isset($row['group_code']) ? $row['group_code'] : 'N/A'); ?></td>
                                <td class="text-center"><?php echo $row['scheduled_date'] ? date("F d, Y h:i A", strtotime($row['scheduled_date'])) : "Not Scheduled"; ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo $row['grading_status'] === 'graded' ? 'success' : 'warning'; ?>">
                                        <?php echo $row['grading_status'] === 'graded' ? 'Graded' : 'Pending Grading'; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a class="btn" href="title_defense_file.php?id=<?php echo $row['id']; ?>">
                                        <?php echo $row['grading_status'] === 'graded' ? 'View Grades' : 'Grade'; ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No submissions found for the selected filter.</p>
        <?php endif; ?>

        <!-- Success Toast -->
        <div class="toast-container position-fixed top-0 end-0 p-3">
            <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        Grades submitted successfully!
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dropdownButton = document.getElementById('filterDropdown');
        const dropdownItems = document.querySelectorAll('.filter-dropdown .dropdown-item');

        dropdownItems.forEach(item => {
            item.addEventListener('click', function () {
                dropdownButton.textContent = this.textContent;
            });
        });

        // Check for success query parameter and show toast
        const urlParams = new URLSearchParams(window.location.search);
        const success = urlParams.get('success');
        if (success === 'grades_submitted' || success === 'grades_updated') {
            const toastElement = document.getElementById('successToast');
            const toastBody = toastElement.querySelector('.toast-body');
            
            // Update message based on action
            if (success === 'grades_updated') {
                toastBody.textContent = 'Grades updated successfully!';
            } else {
                toastBody.textContent = 'Grades submitted successfully!';
            }
            
            const toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 2000 // 2 seconds
            });
            toast.show();
            // Clean up the URL to remove the success parameter
            const status = urlParams.get('status') || 'all';
            window.history.replaceState({}, document.title, window.location.pathname + '?status=' + status);
        }
    });
</script>
</script>
<script src="../assets/js/sortable.js"></script>
</body>
</html>