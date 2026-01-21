<?php
session_start();
include '../config/database.php';
include '../assets/includes/notification_functions.php';
require_once '../assets/includes/role_functions.php';

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check login and dean access using multi-role session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_data'])) {
    header("Location: ../users/login.php");
    exit();
}
if (!hasRole($_SESSION['user_data'], 'dean')) {
    header("Location: ../users/login.php?error=unauthorized_access");
    exit();
}

// Ensure active role is dean
if (!isset($_SESSION['active_role']) || $_SESSION['active_role'] !== 'dean') {
    $_SESSION['active_role'] = 'dean';
    $_SESSION['role'] = 'dean';
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

// Detect column names for employees table
$emp_columns = [];
$emp_cols_result = $conn->query("SHOW COLUMNS FROM employees");
if ($emp_cols_result) {
    while ($col = $emp_cols_result->fetch_assoc()) {
        $emp_columns[] = $col['Field'];
    }
    $emp_cols_result->close();
}

$emp_pk = getPrimaryKeyColumn($conn, 'employees');
$emp_first_col = in_array('firstname', $emp_columns) ? 'firstname' : (in_array('first_name', $emp_columns) ? 'first_name' : 'first_name');
$emp_last_col = in_array('lastname', $emp_columns) ? 'lastname' : (in_array('last_name', $emp_columns) ? 'last_name' : 'last_name');
$hasRolesColumn = in_array('roles', $emp_columns);
$hasRoleColumn = in_array('role', $emp_columns);

// Detect column names for students table
$students_columns = [];
$students_cols_result = $conn->query("SHOW COLUMNS FROM students");
if ($students_cols_result) {
    while ($col = $students_cols_result->fetch_assoc()) {
        $students_columns[] = $col['Field'];
    }
    $students_cols_result->close();
}
$students_pk = getPrimaryKeyColumn($conn, 'students');
$students_first_col = in_array('firstname', $students_columns) ? 'firstname' : (in_array('first_name', $students_columns) ? 'first_name' : 'first_name');
$students_last_col = in_array('lastname', $students_columns) ? 'lastname' : (in_array('last_name', $students_columns) ? 'last_name' : 'last_name');

// Detect column names for student_details table
$sd_columns = [];
$sd_cols_result = $conn->query("SHOW COLUMNS FROM student_details");
if ($sd_cols_result) {
    while ($col = $sd_cols_result->fetch_assoc()) {
        $sd_columns[] = $col['Field'];
    }
    $sd_cols_result->close();
}
$sd_pk = getPrimaryKeyColumn($conn, 'student_details');

// Fetch grammarian members (support multi-role users via roles column if present)
if ($hasRolesColumn) {
    $sql = "SELECT $emp_pk AS id, $emp_first_col AS first_name, $emp_last_col AS last_name FROM employees 
            WHERE LOWER(COALESCE(role,'')) = 'grammarian' 
               OR (roles IS NOT NULL AND roles <> '' AND FIND_IN_SET('grammarian', REPLACE(LOWER(roles), ' ', '')) > 0)
            ORDER BY $emp_first_col, $emp_last_col";
    $grammarianQuery = $conn->prepare($sql);
} elseif ($hasRoleColumn) {
    $grammarianQuery = $conn->prepare("SELECT $emp_pk AS id, $emp_first_col AS first_name, $emp_last_col AS last_name FROM employees WHERE role = 'grammarian' ORDER BY $emp_first_col, $emp_last_col");
} else {
    // No role column, return empty result
    $grammarianQuery = $conn->prepare("SELECT $emp_pk AS id, $emp_first_col AS first_name, $emp_last_col AS last_name FROM employees WHERE 1=0");
}

$grammarianQuery->execute();
$grammarianResult = $grammarianQuery->get_result();
$grammarianMembers = [];
while ($row = $grammarianResult->fetch_assoc()) {
    $grammarianMembers[] = $row;
}

// Handle assignment submission
$notification = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_grammarian'])) {
    $manuscriptId = $_POST['manuscript_id'];
    $grammarianId = $_POST['grammarian_id'];
    
    // Get manuscript details for notification
    // Note: mr.student_id references students.student_id, not student_details.id
    // Join students first, then join student_details to get email
    $manuscriptQuery = $conn->prepare("
        SELECT mr.id, mr.project_id, sd.$sd_pk AS student_details_id, pw.project_title, 
               s.$students_first_col AS first_name, s.$students_last_col AS last_name, sd.email
        FROM manuscript_reviews mr
        INNER JOIN project_working_titles pw ON mr.project_id = pw.id
        INNER JOIN students s ON mr.student_id COLLATE utf8mb4_unicode_ci = s.$students_pk COLLATE utf8mb4_unicode_ci
        LEFT JOIN student_details sd ON s.$students_pk COLLATE utf8mb4_unicode_ci = sd.student_id COLLATE utf8mb4_unicode_ci
        WHERE mr.id = ?
    ");
    $manuscriptQuery->bind_param("i", $manuscriptId);
    $manuscriptQuery->execute();
    $manuscriptResult = $manuscriptQuery->get_result();
    $manuscriptData = $manuscriptResult->fetch_assoc();
    
    // Get grammarian name for notification
    $grammarianNameQuery = $conn->prepare("SELECT $emp_first_col AS first_name, $emp_last_col AS last_name FROM employees WHERE $emp_pk = ?");
    $grammarianNameQuery->bind_param("i", $grammarianId);
    $grammarianNameQuery->execute();
    $grammarianNameResult = $grammarianNameQuery->get_result();
    $grammarianData = $grammarianNameResult->fetch_assoc();
    $grammarianName = $grammarianData['first_name'] . ' ' . $grammarianData['last_name'];
    
    // Update the manuscript with assigned grammarian
    $updateQuery = $conn->prepare("UPDATE manuscript_reviews SET reviewed_by = ?, status = 'under_review' WHERE id = ?");
    $updateQuery->bind_param("ii", $grammarianId, $manuscriptId);
    
    if ($updateQuery->execute()) {
        // Notify the student (use student_details_id for notifications)
        $studentDetailsId = isset($manuscriptData['student_details_id']) ? $manuscriptData['student_details_id'] : null;
        if ($studentDetailsId) {
            createNotification(
                $conn, 
                $studentDetailsId, 
                'Grammarian Assigned', 
                'A grammarian (' . htmlspecialchars($grammarianName) . ') has been assigned to review your manuscript for project "' . htmlspecialchars($manuscriptData['project_title']) . '".',
                'info',
                $manuscriptData['project_id'],
                'manuscript_review'
            );
        }
        
        // Also notify the grammarian about the assignment
        createNotification(
            $conn, 
            $grammarianId, 
            'New Manuscript Assignment', 
            'You have been assigned to review the manuscript for project "' . htmlspecialchars($manuscriptData['project_title']) . '" by ' . htmlspecialchars($manuscriptData['first_name'] . ' ' . $manuscriptData['last_name']) . '.',
            'info',
            $manuscriptData['project_id'],
            'manuscript_review'
        );
        
        $notification = 'Grammarian assigned successfully!';
    } else {
        $notification = 'Error assigning grammarian: ' . $conn->error;
    }
}

// Fetch manuscripts without assigned grammarians
// Note: mr.student_id references students.student_id, not student_details.id
// Join students first, then join student_details to get email and group_code
$unassignedQuery = $conn->prepare("
    SELECT 
        mr.id,
        mr.project_id,
        mr.student_id,
        mr.manuscript_file,
        mr.status,
        mr.date_submitted,
        pw.project_title,
        s.$students_first_col AS first_name,
        s.$students_last_col AS last_name,
        sd.email,
        sd.group_code
    FROM manuscript_reviews mr
    INNER JOIN project_working_titles pw ON mr.project_id = pw.id
    INNER JOIN students s ON mr.student_id COLLATE utf8mb4_unicode_ci = s.$students_pk COLLATE utf8mb4_unicode_ci
    LEFT JOIN student_details sd ON s.$students_pk COLLATE utf8mb4_unicode_ci = sd.student_id COLLATE utf8mb4_unicode_ci
    WHERE mr.reviewed_by IS NULL AND mr.manuscript_file IS NOT NULL
    ORDER BY mr.date_submitted DESC
");
$unassignedQuery->execute();
$unassignedResult = $unassignedQuery->get_result();

// Fetch recently assigned manuscripts for reference
// Note: mr.student_id references students.student_id, not student_details.id
// Join students first, then join student_details to get student name
$recentlyAssignedQuery = $conn->prepare("
    SELECT 
        mr.id,
        mr.project_id,
        mr.status,
        mr.date_submitted,
        pw.project_title,
        s.$students_first_col AS first_name,
        s.$students_last_col AS last_name,
        CONCAT(g.$emp_first_col, ' ', g.$emp_last_col) as grammarian_name
    FROM manuscript_reviews mr
    INNER JOIN project_working_titles pw ON mr.project_id = pw.id
    INNER JOIN students s ON mr.student_id COLLATE utf8mb4_unicode_ci = s.$students_pk COLLATE utf8mb4_unicode_ci
    LEFT JOIN student_details sd ON s.$students_pk COLLATE utf8mb4_unicode_ci = sd.student_id COLLATE utf8mb4_unicode_ci
    INNER JOIN employees g ON mr.reviewed_by = g.$emp_pk
    WHERE mr.reviewed_by IS NOT NULL
    ORDER BY mr.date_submitted DESC
    LIMIT 10
");
$recentlyAssignedQuery->execute();
$recentlyAssignedResult = $recentlyAssignedQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Grammarian - Captrack Vault</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/captrack.png" type="image/png">
    <style>
        .assignment-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .assignment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .assignment-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            position: relative;
        }
        
        .assignment-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .project-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        .project-meta {
            font-size: 0.9rem;
            opacity: 0.9;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .assignment-body {
            padding: 25px;
        }
        
        .student-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .assignment-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .grammarian-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            background: white;
        }
        
        .grammarian-select:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .assign-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .assign-btn:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        .recent-assignments {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .recent-header {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            padding: 20px;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .recent-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f8f9fa;
            transition: background-color 0.2s ease;
        }
        
        .recent-item:hover {
            background-color: #f8f9fa;
        }
        
        .recent-item:last-child {
            border-bottom: none;
        }
        
        .recent-project-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .recent-meta {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.4s ease, fadeOut 0.5s ease 3.5s forwards;
        }
        
        .notification.success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .notification.error {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeOut {
            to { opacity: 0; transform: translateY(-20px); }
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-under_review { background-color: #d1ecf1; color: #0c5460; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        
        @media (max-width: 768px) {
            .assignment-body {
                padding: 20px;
            }
            
            .project-meta {
                flex-direction: column;
                gap: 10px;
            }
            
            .page-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

<?php include '../assets/includes/sidebar.php'; ?>

<div class="main-content">
    <?php include '../assets/includes/navbar.php'; ?>
    
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">
                <i class="bi bi-pencil-square-fill me-3"></i>
                Grammarian Assignment
            </h1>
            <p class="page-subtitle">Assign grammarians to review student manuscripts</p>
        </div>
    </div>
    
    <?php if ($notification): ?>
        <div class="notification <?php echo strpos($notification, 'Error') !== false ? 'error' : 'success'; ?>">
            <i class="bi bi-<?php echo strpos($notification, 'Error') !== false ? 'exclamation-circle' : 'check-circle'; ?> me-2"></i>
            <?php echo htmlspecialchars($notification); ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <div class="row">
            <!-- Unassigned Manuscripts -->
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="bi bi-file-text-check me-2"></i>Manuscripts Awaiting Assignment</h3>
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                        <?php echo $unassignedResult->num_rows; ?> pending
                    </span>
                </div>
                
                <?php if ($unassignedResult->num_rows > 0): ?>
                    <?php while ($manuscript = $unassignedResult->fetch_assoc()): ?>
                        <div class="assignment-card">
                            <div class="assignment-header">
                                <div class="project-title">
                                    <?php echo htmlspecialchars($manuscript['project_title']); ?>
                                </div>
                                <div class="project-meta">
                                    <span><i class="bi bi-calendar me-1"></i><?php echo date('M j, Y', strtotime($manuscript['date_submitted'])); ?></span>
                                    <span><i class="bi bi-file-pdf me-1"></i>Manuscript Available</span>
                                    <span class="status-badge status-<?php echo $manuscript['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $manuscript['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="assignment-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="student-info">
                                            <h6><i class="bi bi-person me-2"></i>Student Information:</h6>
                                            <p class="mb-1"><strong><?php echo htmlspecialchars($manuscript['first_name'] . ' ' . $manuscript['last_name']); ?></strong></p>
                                            <p class="mb-1"><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($manuscript['email']); ?></p>
                                            <?php if ($manuscript['group_code']): ?>
                                                <p class="mb-0"><i class="bi bi-people me-1"></i>Group: <?php echo htmlspecialchars($manuscript['group_code']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <a href="<?php echo htmlspecialchars($manuscript['manuscript_file']); ?>" 
                                               target="_blank" class="btn btn-outline-primary">
                                                <i class="bi bi-eye me-1"></i>View Manuscript
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="assignment-form">
                                            <form method="POST">
                                                <input type="hidden" name="manuscript_id" value="<?php echo $manuscript['id']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">
                                                        <i class="bi bi-pencil-square me-2"></i>Assign Grammarian:
                                                    </label>
                                                    <select name="grammarian_id" class="grammarian-select" required>
                                                        <option value="">Select a grammarian...</option>
                                                        <?php foreach ($grammarianMembers as $grammarian): ?>
                                                            <option value="<?php echo $grammarian['id']; ?>">
                                                                <?php echo htmlspecialchars($grammarian['first_name'] . ' ' . $grammarian['last_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <button type="submit" name="assign_grammarian" class="assign-btn">
                                                    <i class="bi bi-check-circle me-2"></i>Assign Grammarian
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="assignment-card">
                        <div class="empty-state">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <h4>All Caught Up!</h4>
                            <p>All submitted manuscripts have been assigned grammarians.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Recently Assigned -->
            <div class="col-lg-4">
                <div class="recent-assignments">
                    <div class="recent-header">
                        <i class="bi bi-clock-history me-2"></i>Recent Assignments
                    </div>
                    <?php if ($recentlyAssignedResult->num_rows > 0): ?>
                        <?php while ($recent = $recentlyAssignedResult->fetch_assoc()): ?>
                            <div class="recent-item">
                                <div class="recent-project-title">
                                    <?php echo htmlspecialchars(strlen($recent['project_title']) > 50 ? substr($recent['project_title'], 0, 50) . '...' : $recent['project_title']); ?>
                                </div>
                                <div class="recent-meta">
                                    <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($recent['first_name'] . ' ' . $recent['last_name']); ?><br>
                                    <i class="bi bi-arrow-right me-1"></i><?php echo htmlspecialchars($recent['grammarian_name']); ?><br>
                                    <i class="bi bi-calendar me-1"></i><?php echo date('M j, Y', strtotime($recent['date_submitted'])); ?><br>
                                    <span class="status-badge status-<?php echo $recent['status']; ?> mt-1">
                                        <?php echo ucfirst(str_replace('_', ' ', $recent['status'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="recent-item text-center text-muted py-4">
                            <i class="bi bi-inbox display-4 mb-2"></i>
                            <p class="mb-0">No assignments yet</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Grammarian Summary -->
                <div class="recent-assignments mt-4">
                    <div class="recent-header">
                        <i class="bi bi-people me-2"></i>Available Grammarians
                    </div>
                    <?php if (count($grammarianMembers) > 0): ?>
                        <?php foreach ($grammarianMembers as $grammarian): ?>
                            <div class="recent-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($grammarian['first_name'] . ' ' . $grammarian['last_name']); ?></strong>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">Grammarian</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="recent-item text-center text-muted py-4">
                            <i class="bi bi-exclamation-triangle display-4 mb-2"></i>
                            <p class="mb-0">No grammarians available</p>
                            <small>Add grammarians through the admin panel</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-hide notifications
document.addEventListener('DOMContentLoaded', function() {
    const notification = document.querySelector('.notification');
    if (notification) {
        setTimeout(() => {
            notification.style.display = 'none';
        }, 4000);
    }
});

// Form validation
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const select = this.querySelector('select[name="grammarian_id"]');
        if (!select.value) {
            e.preventDefault();
            alert('Please select a grammarian to assign for manuscript review.');
            select.focus();
        }
    });
});
</script>
</body>
</html>
