<?php
session_start();
include '../config/database.php';
require_once '../assets/includes/role_functions.php';
require_once '../assets/includes/notification_functions.php';

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

// Detect primary keys and column names
$emp_pk = getPrimaryKeyColumn($conn, 'employees');
$students_pk = getPrimaryKeyColumn($conn, 'students');
$sd_pk = getPrimaryKeyColumn($conn, 'student_details');

// Detect column names for students table
$students_columns = [];
$students_cols_result = $conn->query("SHOW COLUMNS FROM students");
if ($students_cols_result) {
    while ($col = $students_cols_result->fetch_assoc()) {
        $students_columns[] = $col['Field'];
    }
    $students_cols_result->close();
}
$students_first_col = in_array('firstname', $students_columns) ? 'firstname' : (in_array('first_name', $students_columns) ? 'first_name' : 'first_name');
$students_last_col = in_array('lastname', $students_columns) ? 'lastname' : (in_array('last_name', $students_columns) ? 'last_name' : 'last_name');

if (!isset($_SESSION['user_data'])) {
    // Refresh user data from database
    // PlagScanners are employees
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

if (!isset($_GET['project_id'])) {
    header("Location: home.php");
    exit();
}
$projectId = (int)$_GET['project_id'];
$scannerId = (int)$_SESSION['user_id'];

// Check if this project is assigned to this PlagScanner
$assignmentCheck = $conn->prepare("SELECT COUNT(*) as count FROM plagscan_reviews WHERE project_id = ? AND reviewed_by = ?");
$assignmentCheck->bind_param("ii", $projectId, $scannerId);
$assignmentCheck->execute();
$assignmentResult = $assignmentCheck->get_result();
$isAssigned = $assignmentResult->fetch_assoc()['count'] > 0;
$assignmentCheck->close();

if (!$isAssigned) {
    header("Location: home.php?error=not_assigned");
    exit();
}

// Get the latest version for this project (regardless of reviewed_by)
$q = $conn->prepare("SELECT pr.*, pw.project_title, pr.student_id FROM plagscan_reviews pr INNER JOIN project_working_titles pw ON pr.project_id = pw.id WHERE pr.project_id = ? ORDER BY pr.version DESC LIMIT 1");
$q->bind_param("i", $projectId);
$q->execute();
$res = $q->get_result();
$review = $res->fetch_assoc();
$q->close();

// Get all versions for this project to show history
// Note: pr.student_id references students.student_id, not student_details.id
// Join students first to get student names
$versionsQuery = $conn->prepare("SELECT pr.*, s.$students_first_col AS first_name, s.$students_last_col AS last_name 
    FROM plagscan_reviews pr 
    INNER JOIN students s ON pr.student_id = s.$students_pk 
    WHERE pr.project_id = ? 
    ORDER BY pr.version ASC");
$versionsQuery->bind_param("i", $projectId);
$versionsQuery->execute();
$versionsResult = $versionsQuery->get_result();
$allVersions = [];
while ($version = $versionsResult->fetch_assoc()) {
    $allVersions[] = $version;
}
$versionsQuery->close();

if (!$review) {
    header("Location: home.php?error=not_assigned");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = isset($_POST['status']) ? $_POST['status'] : 'under_review';
    $percent = isset($_POST['percent_similarity']) && $_POST['percent_similarity'] !== '' ? floatval($_POST['percent_similarity']) : null;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : null;
    $plagFile = $review['plagscan_result_file'];
    $aiFile = $review['ai_result_file'];
    $uploadDir = realpath(__DIR__ . '/../assets/uploads');
    $resultDir = __DIR__ . '/../assets/uploads/plagscan_results';
    $errors = [];
    $filesSaved = false;

    // Ensure directories exist
    if (!is_dir($resultDir)) {
        if (!mkdir($resultDir, 0755, true)) {
            $errors[] = 'Failed to create results directory.';
        }
    }

    // Handle result uploads (PDF)
    if (isset($_FILES['plagscan_result']) && $_FILES['plagscan_result']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['plagscan_result']['error'] === UPLOAD_ERR_OK) {
            $name = basename($_FILES['plagscan_result']['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $dest = $resultDir . '/' . time() . '_plagreport_' . $name;
                if (move_uploaded_file($_FILES['plagscan_result']['tmp_name'], $dest)) {
                    // store relative path like other pages do (../assets/uploads/...)
                    $plagFile = '../assets/uploads/plagscan_results/' . basename($dest);
                    $filesSaved = true;
                } else {
                    $errors[] = 'Failed to save Plagiarism Report file.';
                }
            } else {
                $errors[] = 'Plagiarism Report must be a PDF file.';
            }
        } else {
            $errors[] = 'Upload error for Plagiarism Report (code ' . (int)$_FILES['plagscan_result']['error'] . ').';
        }
    }

    // Handle AI result (PDF/TXT)
    if (isset($_FILES['ai_result']) && $_FILES['ai_result']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['ai_result']['error'] === UPLOAD_ERR_OK) {
            $name = basename($_FILES['ai_result']['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf','txt'])) {
                $dest = $resultDir . '/' . time() . '_aires_' . $name;
                if (move_uploaded_file($_FILES['ai_result']['tmp_name'], $dest)) {
                    $aiFile = '../assets/uploads/plagscan_results/' . basename($dest);
                    $filesSaved = true;
                } else {
                    $errors[] = 'Failed to save AI Result file.';
                }
            } else {
                $errors[] = 'AI Result must be a PDF or TXT file.';
            }
        } else {
            $errors[] = 'Upload error for AI Result (code ' . (int)$_FILES['ai_result']['error'] . ').';
        }
    }

    // If there were upload attempts but failures, surface error and do not claim success
    if (!empty($errors)) {
        $err = urlencode(implode(' ', $errors));
        header("Location: review_plagscan.php?project_id=$projectId&error=$err");
        exit();
    }

    $upd = $conn->prepare("UPDATE plagscan_reviews SET plagscan_result_file = ?, ai_result_file = ?, percent_similarity = ?, notes = ?, status = ?, date_reviewed = NOW(), reviewed_by = ? WHERE id = ?");
    $upd->bind_param("ssdssii", $plagFile, $aiFile, $percent, $notes, $status, $scannerId, $review['id']);
    if ($upd->execute()) {
        // Notify student of result
        $title = $status === 'approved' ? 'PlagScan Approved' : ($status === 'rejected' ? 'PlagScan Requires Action' : 'PlagScan Updated');
        $msg = 'Plagiarism checking result has been updated for your project. Status: ' . ucfirst(str_replace('_',' ',$status)) . '.';
        createNotification($conn, (int)$review['student_id'], $title, $msg, 'info', $projectId, 'plagscan_review');
        $flag = $filesSaved ? '1' : '0';
        header("Location: review_plagscan.php?project_id=$projectId&success=1&files=$flag");
        exit();
    }
    $upd->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review PlagScan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/captrack.png" type="image/png">
</head>
<body>

<?php include '../assets/includes/sidebar.php'; ?>

<div class="main-content">
    <?php include '../assets/includes/navbar.php'; ?>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0"><i class="bi bi-shield-check me-2"></i>PlagScan Review</h3>
            <a href="javascript:void(0)" class="btn btn-outline-secondary"
               onclick="if (document.referrer) { history.back(); } else { window.location.href='home.php'; }">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" id="successAlert">
                <i class="bi bi-check2-circle me-2"></i>
                Updated successfully<?php echo (isset($_GET['files']) && $_GET['files']==='1') ? ' (files saved)' : ''; ?>.
            </div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-2"><strong>Project:</strong> <?php echo htmlspecialchars($review['project_title']); ?></div>
                <div class="mb-2"><strong>Current Version:</strong> Version <?php echo $review['version']; ?> (Latest)</div>
                <div class="mb-3"><strong>Submitted:</strong> <?php echo date('M d, Y', strtotime($review['date_submitted'])); ?></div>
                <?php if (!empty($review['manuscript_file'])): ?>
                    <a class="btn btn-outline-primary btn-sm" target="_blank" href="<?php echo htmlspecialchars($review['manuscript_file']); ?>"><i class="bi bi-eye me-1"></i>View Current Manuscript</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (count($allVersions) > 1): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Version History</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($allVersions as $index => $version): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card <?php echo $version['id'] == $review['id'] ? 'border-primary' : 'border-secondary'; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0">Version <?php echo $version['version']; ?></h6>
                                        <?php if ($version['id'] == $review['id']): ?>
                                            <span class="badge bg-primary">Current</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-muted mb-2">
                                        <small>
                                            <i class="bi bi-calendar me-1"></i>
                                            <?php echo date('M d, Y H:i', strtotime($version['date_submitted'])); ?>
                                        </small>
                                    </p>
                                    <div class="mb-2">
                                        <span class="badge bg-<?php echo $version['status'] === 'approved' ? 'success' : ($version['status'] === 'rejected' ? 'danger' : ($version['status'] === 'under_review' ? 'info' : 'warning')); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $version['status'])); ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($version['manuscript_file'])): ?>
                                        <a href="<?php echo htmlspecialchars($version['manuscript_file']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($version['notes'])): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <strong>Notes:</strong> <?php echo htmlspecialchars($version['notes']); ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="card" id="plagscanForm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Plagiarism Report (PDF)</label>
                        <input type="file" class="form-control" name="plagscan_result" accept="application/pdf" disabled>
                        <?php if (!empty($review['plagscan_result_file'])): ?>
                            <a class="d-inline-block mt-2" target="_blank" href="<?php echo htmlspecialchars($review['plagscan_result_file']); ?>">Existing report</a>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">AI Result (PDF/TXT)</label>
                        <input type="file" class="form-control" name="ai_result" accept="application/pdf,text/plain" disabled>
                        <?php if (!empty($review['ai_result_file'])): ?>
                            <a class="d-inline-block mt-2" target="_blank" href="<?php echo htmlspecialchars($review['ai_result_file']); ?>">Existing AI file</a>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Percent Similarity</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control" name="percent_similarity" value="<?php echo htmlspecialchars($review['percent_similarity']); ?>" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" disabled>
                            <?php $opts = ['under_review','approved','rejected']; foreach ($opts as $opt): ?>
                                <option value="<?php echo $opt; ?>" <?php echo ($review['status']===$opt?'selected':''); ?>><?php echo ucfirst(str_replace('_',' ',$opt)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" rows="4" name="notes" disabled><?php echo htmlspecialchars($review['notes']); ?></textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="button" id="editBtn" class="btn btn-warning"><i class="bi bi-pencil-square me-2"></i>Edit</button>
                <button type="button" id="cancelBtn" class="btn btn-secondary d-none"><i class="bi bi-x-circle me-2"></i>Cancel</button>
                <button class="btn btn-primary d-none" id="saveBtn" type="submit"><i class="bi bi-save me-2"></i>Save</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
<script>
// Toggle edit mode: fields are read-only until Edit is pressed
(function(){
    const form = document.getElementById('plagscanForm');
    const editBtn = document.getElementById('editBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const saveBtn = document.getElementById('saveBtn');
    if (!form || !editBtn) return;

    function setDisabled(disabled){
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(el => {
            if (el.name === 'plagscan_result' || el.name === 'ai_result' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA' || el.type === 'number') {
                el.disabled = disabled;
            }
        });
    }

    editBtn.addEventListener('click', function(){
        setDisabled(false);
        editBtn.classList.add('d-none');
        cancelBtn.classList.remove('d-none');
        saveBtn.classList.remove('d-none');
    });

    cancelBtn.addEventListener('click', function(){
        // Revert by reloading; simplest consistent reset
        window.location.reload();
    });
})();

// Clean URL parameters and auto-hide success messages
document.addEventListener('DOMContentLoaded', function() {
    // Clean URL parameters after page load
    if (window.location.search.includes('success=') || window.location.search.includes('error=') || window.location.search.includes('files=')) {
        const url = new URL(window.location);
        url.searchParams.delete('success');
        url.searchParams.delete('error');
        url.searchParams.delete('files');
        window.history.replaceState({}, document.title, url.pathname + url.search);
    }
    
    // Auto-hide success messages after 3 seconds
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.transition = 'opacity 0.5s ease-out';
            successAlert.style.opacity = '0';
            setTimeout(function() {
                successAlert.remove();
            }, 500);
        }, 3000);
    }
});
</script>
</body>
</html>


