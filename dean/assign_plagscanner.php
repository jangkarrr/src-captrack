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

// Fetch PlagScanner members (support multi-role users)
if ($hasRolesColumn) {
    $sql = "SELECT $emp_pk AS id, $emp_first_col AS first_name, $emp_last_col AS last_name FROM employees 
            WHERE LOWER(COALESCE(role,'')) = 'plagscanner' 
               OR (roles IS NOT NULL AND roles <> '' AND FIND_IN_SET('plagscanner', REPLACE(LOWER(roles), ' ', '')) > 0)
            ORDER BY $emp_first_col, $emp_last_col";
    $plagQuery = $conn->prepare($sql);
} elseif ($hasRoleColumn) {
    $plagQuery = $conn->prepare("SELECT $emp_pk AS id, $emp_first_col AS first_name, $emp_last_col AS last_name FROM employees WHERE role = 'plagscanner' ORDER BY $emp_first_col, $emp_last_col");
} else {
    // No role column, return empty result
    $plagQuery = $conn->prepare("SELECT $emp_pk AS id, $emp_first_col AS first_name, $emp_last_col AS last_name FROM employees WHERE 1=0");
}

$plagQuery->execute();
$plagResult = $plagQuery->get_result();
$plagMembers = [];
while ($row = $plagResult->fetch_assoc()) {
    $plagMembers[] = $row;
}

// Handle assignment submission
$notification = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_plagscanner'])) {
    $plagId = (int)$_POST['plag_id'];
    $scannerId = (int)$_POST['scanner_id'];

    // Get submission details for notification
    // Note: pr.student_id references students.student_id, not student_details.id
    $dataQ = $conn->prepare("SELECT pr.id, pr.project_id, pr.student_id, pw.project_title, 
        s.$students_first_col AS first_name, s.$students_last_col AS last_name, sd.email, sd.$sd_pk as student_details_id
        FROM plagscan_reviews pr
        INNER JOIN project_working_titles pw ON pr.project_id = pw.id
        INNER JOIN students s ON pr.student_id = s.$students_pk
        LEFT JOIN student_details sd ON s.$students_pk = sd.student_id
        WHERE pr.id = ?");
    $dataQ->bind_param("i", $plagId);
    $dataQ->execute();
    $dataR = $dataQ->get_result();
    $data = $dataR->fetch_assoc();

    // Get scanner name
    $nameQ = $conn->prepare("SELECT $emp_first_col AS first_name, $emp_last_col AS last_name FROM employees WHERE $emp_pk = ?");
    $nameQ->bind_param("i", $scannerId);
    $nameQ->execute();
    $nameR = $nameQ->get_result();
    $scanner = $nameR->fetch_assoc();
    $scannerName = $scanner['first_name'] . ' ' . $scanner['last_name'];

    // Update the plagscan record
    $updateQ = $conn->prepare("UPDATE plagscan_reviews SET reviewed_by = ?, status = 'under_review' WHERE id = ?");
    $updateQ->bind_param("ii", $scannerId, $plagId);

    if ($updateQ->execute()) {
        // Notify the student
        // Use student_details_id for notification (notifications use student_details primary key)
        $notificationUserId = isset($data['student_details_id']) ? $data['student_details_id'] : $data['student_id'];
        createNotification($conn, $notificationUserId, 'PlagScanner Assigned', 'A PlagScanner (' . htmlspecialchars($scannerName) . ') has been assigned to check your manuscript for project \"' . htmlspecialchars($data['project_title']) . '\".', 'info', $data['project_id'], 'plagscan_review');

        // Notify the plagscanner
        createNotification($conn, $scannerId, 'New PlagScan Assignment', 'You have been assigned to run plagiarism check for project \"' . htmlspecialchars($data['project_title']) . '\" by ' . htmlspecialchars($data['first_name'] . ' ' . $data['last_name']) . '.', 'info', $data['project_id'], 'plagscan_review');

        $notification = 'PlagScanner assigned successfully!';
    } else {
        $notification = 'Error assigning PlagScanner: ' . $conn->error;
    }
}

// Fetch unassigned plagscan submissions
// Note: pr.student_id references students.student_id, not student_details.id
// Join students first, then join student_details to get email and group_code
$unassignedQuery = $conn->prepare("SELECT pr.id, pr.project_id, pr.student_id, pr.manuscript_file, pr.status, pr.date_submitted, pw.project_title, 
    s.$students_first_col AS first_name, s.$students_last_col AS last_name, sd.email, sd.group_code
    FROM plagscan_reviews pr
    INNER JOIN project_working_titles pw ON pr.project_id = pw.id
    INNER JOIN students s ON pr.student_id = s.$students_pk
    LEFT JOIN student_details sd ON s.$students_pk = sd.student_id
    WHERE pr.reviewed_by IS NULL AND pr.manuscript_file IS NOT NULL
    ORDER BY pr.date_submitted DESC");
$unassignedQuery->execute();
$unassignedResult = $unassignedQuery->get_result();

// Recently assigned
// Note: pr.student_id references students.student_id, not student_details.id
$recentlyAssignedQuery = $conn->prepare("SELECT pr.id, pr.project_id, pr.status, pr.date_submitted, pw.project_title, 
    s.$students_first_col AS first_name, s.$students_last_col AS last_name, 
    CONCAT(ps.$emp_first_col, ' ', ps.$emp_last_col) as scanner_name
    FROM plagscan_reviews pr
    INNER JOIN project_working_titles pw ON pr.project_id = pw.id
    INNER JOIN students s ON pr.student_id = s.$students_pk
    LEFT JOIN student_details sd ON s.$students_pk = sd.student_id
    INNER JOIN employees ps ON pr.reviewed_by = ps.$emp_pk
    WHERE pr.reviewed_by IS NOT NULL
    ORDER BY pr.date_submitted DESC
    LIMIT 10");
$recentlyAssignedQuery->execute();
$recentlyAssignedResult = $recentlyAssignedQuery->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign PlagScanner - Captrack Vault</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/captrack.png" type="image/png">
</head>
<body>

<?php include '../assets/includes/sidebar.php'; ?>

<div class="main-content">
    <?php include '../assets/includes/navbar.php'; ?>

    <div class="page-header" style="background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%); color: white; padding: 2rem 0; margin-bottom: 2rem; border-radius: 0 0 20px 20px;">
        <div class="container">
            <h1 class="page-title"><i class="bi bi-shield-lock-fill me-3"></i>PlagScan Assignment</h1>
            <p class="page-subtitle">Assign PlagScanners to run plagiarism checks</p>
        </div>
    </div>

    <?php if ($notification): ?>
        <div class="alert <?php echo strpos($notification, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?> mx-3"><?php echo htmlspecialchars($notification); ?></div>
    <?php endif; ?>

    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="bi bi-file-text-check me-2"></i>Submissions Awaiting Assignment</h3>
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2"><?php echo $unassignedResult->num_rows; ?> pending</span>
                </div>

                <?php if ($unassignedResult->num_rows > 0): ?>
                    <?php while ($item = $unassignedResult->fetch_assoc()): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($item['project_title']); ?></div>
                                        <small class="opacity-75">Submitted: <?php echo date('M j, Y', strtotime($item['date_submitted'])); ?></small>
                                    </div>
                                    <span class="badge bg-light text-dark"><?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?></span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-2"><strong>Student:</strong> <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></div>
                                        <div class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($item['email']); ?></div>
                                        <?php if ($item['group_code']): ?><div class="mb-2"><strong>Group:</strong> <?php echo htmlspecialchars($item['group_code']); ?></div><?php endif; ?>
                                        <div class="mb-2"><a href="<?php echo htmlspecialchars($item['manuscript_file']); ?>" target="_blank" class="btn btn-outline-light btn-sm"><i class="bi bi-eye me-1"></i>View Manuscript</a></div>
                                    </div>
                                    <div class="col-md-6">
                                        <form method="POST" class="p-3 bg-light rounded">
                                            <input type="hidden" name="plag_id" value="<?php echo (int)$item['id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold"><i class="bi bi-person-plus me-2"></i>Assign PlagScanner:</label>
                                                <select name="scanner_id" class="form-select" required>
                                                    <option value="">Select a PlagScanner...</option>
                                                    <?php foreach ($plagMembers as $scanner): ?>
                                                        <option value="<?php echo $scanner['id']; ?>"><?php echo htmlspecialchars($scanner['first_name'] . ' ' . $scanner['last_name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="submit" name="assign_plagscanner" class="btn btn-success w-100"><i class="bi bi-check-circle me-2"></i>Assign</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center text-muted py-5">
                            <i class="bi bi-check-circle display-5 d-block mb-3"></i>
                            All submitted PlagScan requests have been assigned.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-secondary text-white"><i class="bi bi-clock-history me-2"></i>Recent Assignments</div>
                    <div class="list-group list-group-flush">
                        <?php if ($recentlyAssignedResult->num_rows > 0): ?>
                            <?php while ($recent = $recentlyAssignedResult->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="fw-semibold"><?php echo htmlspecialchars(strlen($recent['project_title']) > 50 ? substr($recent['project_title'], 0, 50) . '...' : $recent['project_title']); ?></div>
                                    <small class="text-muted">Assigned to <?php echo htmlspecialchars($recent['scanner_name']); ?></small>
                                    <div><small>Status: <?php echo ucfirst(str_replace('_', ' ', $recent['status'])); ?></small></div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="list-group-item text-muted">No recent assignments</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
</body>
</html>


