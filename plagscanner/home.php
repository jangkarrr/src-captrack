<?php
session_start();
include '../config/database.php';
require_once '../assets/includes/role_functions.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../users/login.php");
    exit();
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

// Authorize as PlagScanner - Refresh user data if needed
if (!isset($_SESSION['user_data'])) {
    // Refresh user data from database
    // Grammarians/PlagScanners are employees
    $userQuery = $conn->prepare("SELECT * FROM employees WHERE $emp_pk = ?");
    $userQuery->bind_param("i", $_SESSION['user_id']);
    $userQuery->execute();
    $userResult = $userQuery->get_result();
    if ($userResult->num_rows > 0) {
        $_SESSION['user_data'] = $userResult->fetch_assoc();
    } else {
        header("Location: ../users/login.php?error=user_not_found");
        exit();
    }
    $userQuery->close();
}

if (!hasRole($_SESSION['user_data'], 'plagscanner')) {
    header("Location: ../users/login.php?error=unauthorized_access");
    exit();
}

// Ensure active role reflects PlagScanner
if (!isset($_SESSION['active_role']) || $_SESSION['active_role'] !== 'plagscanner') {
    $_SESSION['active_role'] = 'plagscanner';
    $_SESSION['role'] = 'plagscanner';
}

$scannerId = $_SESSION['user_id'];

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
$students_first_col = in_array('firstname', $students_columns) ? 'firstname' : (in_array('first_name', $students_columns) ? 'first_name' : 'first_name');
$students_last_col = in_array('lastname', $students_columns) ? 'lastname' : (in_array('last_name', $students_columns) ? 'last_name' : 'last_name');
$hasGroupCode = in_array('group_code', $students_columns);

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

// Build group_code field
$groupCodeField = $sd_hasGroupCode ? 'sd.group_code' : ($hasGroupCode ? 's.group_code' : 'NULL AS group_code');

// Check if students table has name columns
$hasStudentsNameCols = in_array($students_first_col, $students_columns) && in_array($students_last_col, $students_columns);
$studentNameSelect = $hasStudentsNameCols ? "s.$students_first_col AS first_name, s.$students_last_col AS last_name" : "NULL AS first_name, NULL AS last_name";

// Status filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query to get latest version of each project assigned to this PlagScanner
// Note: pr.student_id references students.student_id, not student_details.id
// Join students first, then join student_details to get email and group_code
// Always join student_details if we need group_code or if it might be needed
$query = "
    SELECT pr.*, pw.project_title, $studentNameSelect, $groupCodeField AS group_code,
           (SELECT COUNT(*) FROM plagscan_reviews pr2 WHERE pr2.project_id = pr.project_id) as total_versions
    FROM plagscan_reviews pr
    INNER JOIN project_working_titles pw ON pr.project_id = pw.id
    INNER JOIN students s ON pr.student_id = s.$students_pk
    LEFT JOIN student_details sd ON s.$students_pk = sd.student_id
    WHERE pr.project_id IN (
        SELECT DISTINCT project_id FROM plagscan_reviews WHERE reviewed_by = ?
    ) AND pr.version = (
        SELECT MAX(pr3.version) FROM plagscan_reviews pr3 WHERE pr3.project_id = pr.project_id
    )
";
if ($statusFilter !== 'all') {
    $query .= " AND pr.status = ?";
}
$query .= " ORDER BY pr.status ASC, pr.date_submitted DESC";

$stmt = $conn->prepare($query);
if ($statusFilter !== 'all') {
    $stmt->bind_param("is", $scannerId, $statusFilter);
} else {
    $stmt->bind_param("i", $scannerId);
}
$stmt->execute();
$assignments = $stmt->get_result();

// Stats
$statsStmt = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
FROM plagscan_reviews WHERE reviewed_by = ?");
$statsStmt->bind_param("i", $scannerId);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
$statsStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PlagScanner Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/captrack.png" type="image/png">
    <style>
        .stats-card {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            color: #fff;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .stats-card h3 { font-size: 2.2rem; font-weight: 700; margin: 0; }
        .stats-card p { margin: 0; opacity: 0.95; }

        .manuscript-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 1.25rem 1.25rem;
            margin-bottom: 1rem;
            transition: box-shadow .25s ease, transform .25s ease;
            background: #fff;
        }
        .manuscript-card:hover { box-shadow: 0 6px 18px rgba(0,0,0,0.08); transform: translateY(-2px); }

        .status-badge {
            padding: 0.45rem 0.9rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-under_review { background-color: #d1ecf1; color: #0c5460; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }

        .filter-buttons { margin-bottom: 1.25rem; }
        .filter-btn { margin-right: 0.5rem; margin-bottom: 0.5rem; }

        .btn-review {
            background: linear-gradient(45deg, #0d6efd, #0dcaf0);
            border: none;
            color: #fff;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            transition: all .25s ease;
        }
        .btn-review:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(13,110,253,0.35); color: #fff; }

        .empty-state { text-align: center; padding: 3rem; color: #6c757d; }
        .empty-state i { font-size: 3rem; margin-bottom: 0.75rem; opacity: 0.6; }
    </style>
</head>
<body>

<?php include '../assets/includes/sidebar.php'; ?>

<div class="main-content">
    <?php include '../assets/includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3 mb-0">
                    <i class="bi bi-shield-lock me-2"></i>
                    PlagScanner Dashboard
                </h1>
                <p class="text-muted">Run and upload plagiarism results for student manuscripts</p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%); color: #fff;">
                    <h3><?php echo (int)$stats['total']; ?></h3>
                    <p>Total Assignments</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);">
                    <h3><?php echo (int)$stats['pending']; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                    <h3><?php echo (int)$stats['under_review']; ?></h3>
                    <p>Under Review</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #d299c2 0%, #fef9d7 100%);">
                    <h3><?php echo (int)$stats['approved']; ?></h3>
                    <p>Approved</p>
                </div>
            </div>
        </div>

        <div class="filter-buttons">
            <a href="?status=all" class="btn btn-outline-primary filter-btn <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All</a>
            <a href="?status=pending" class="btn btn-outline-warning filter-btn <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?status=under_review" class="btn btn-outline-info filter-btn <?php echo $statusFilter === 'under_review' ? 'active' : ''; ?>">Under Review</a>
            <a href="?status=approved" class="btn btn-outline-success filter-btn <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">Approved</a>
            <a href="?status=rejected" class="btn btn-outline-danger filter-btn <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
        </div>

        <div class="row">
            <div class="col-12">
                <?php if ($assignments && $assignments->num_rows > 0): ?>
                    <?php while ($item = $assignments->fetch_assoc()): ?>
                        <div class="manuscript-card">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-1">
                                        <?php echo htmlspecialchars($item['project_title']); ?>
                                        <span class="badge bg-primary ms-2">Version <?php echo $item['version']; ?></span>
                                        <?php if ($item['total_versions'] > 1): ?>
                                            <span class="badge bg-info ms-1"><?php echo $item['total_versions']; ?> versions</span>
                                        <?php endif; ?>
                                    </h5>
                                    <p class="text-muted mb-1">
                                        <i class="bi bi-person me-1"></i>
                                        <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?>
                                        <?php if ($item['group_code']): ?>
                                            <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($item['group_code']); ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar me-1"></i>
                                        Submitted: <?php echo date('M d, Y', strtotime($item['date_submitted'])); ?>
                                    </small>
                                </div>
                                <div class="col-md-3 text-center">
                                    <span class="status-badge status-<?php echo $item['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?></span>
                                </div>
                                <div class="col-md-3 text-end">
                                    <?php if ($item['status'] === 'pending' || $item['status'] === 'under_review'): ?>
                                        <a href="review_plagscan.php?project_id=<?php echo (int)$item['project_id']; ?>" class="btn btn-review">
                                            <i class="bi bi-eye me-1"></i>Review
                                        </a>
                                    <?php else: ?>
                                        <a href="review_plagscan.php?project_id=<?php echo (int)$item['project_id']; ?>" class="btn btn-outline-primary">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-file-text"></i>
                        <h4>No assignments found</h4>
                        <p>There are no items matching your current filter.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
</body>
</html>


