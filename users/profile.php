<?php
session_start();
include '../config/database.php';
include_once '../assets/includes/role_functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../users/login.php");
    exit();
}

$message = "";
$msg_type = "";

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

// Handle AJAX request for current password validation
if (isset($_POST['action']) && $_POST['action'] === 'validate_current_password') {
    $user_id = $_SESSION['user_id'];
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';

    // Check if user is employee or student
    $user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'employee';
    $table = ($user_type === 'student') ? 'student_details' : 'employees';
    $pk_column = getPrimaryKeyColumn($conn, $table);
    $stmt = mysqli_prepare($conn, "SELECT password FROM $table WHERE $pk_column = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && $row = mysqli_fetch_assoc($result)) {
        $is_valid = password_verify($current_password, $row['password']);
        echo json_encode(['valid' => $is_valid]);
    } else {
        echo json_encode(['valid' => false]);
    }
    exit();
}

// Handle change password form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['change_password'])) {
    $user_id = $_SESSION['user_id'];
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Check if user is employee or student
    $user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'employee';
    $table = ($user_type === 'student') ? 'student_details' : 'employees';
    $pk_column = getPrimaryKeyColumn($conn, $table);
    $stmt = mysqli_prepare($conn, "SELECT password FROM $table WHERE $pk_column = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && $row = mysqli_fetch_assoc($result)) {
        if (!password_verify($current_password, $row['password'])) {
            $message = "Current password is incorrect.";
            $msg_type = "danger";
        } elseif ($new_password !== $confirm_password) {
            $message = "New password and confirmation do not match.";
            $msg_type = "danger";
        } elseif (strlen($new_password) < 6 || strlen($new_password) > 12) {
            $message = "New password must be 6-12 characters long.";
            $msg_type = "danger";
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update = mysqli_prepare($conn, "UPDATE $table SET password = ? WHERE $pk_column = ?");
            mysqli_stmt_bind_param($update, "si", $hashed, $user_id);
            mysqli_stmt_execute($update);

            if (mysqli_stmt_affected_rows($update) > 0) {
                $message = "Password updated successfully.";
                $msg_type = "success";
            }
        }
    }
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'employee';

if ($user_type === 'student') {
    $pk_column = getPrimaryKeyColumn($conn, 'student_details');
    $students_pk = getPrimaryKeyColumn($conn, 'students');
    
    // Detect column names in students table
    $students_columns = [];
    $students_cols_result = $conn->query("SHOW COLUMNS FROM students");
    if ($students_cols_result) {
        while ($col = $students_cols_result->fetch_assoc()) {
            $students_columns[] = $col['Field'];
        }
        $students_cols_result->close();
    }
    
    $s_first_col = in_array('firstname', $students_columns) ? 's.firstname' : (in_array('first_name', $students_columns) ? 's.first_name' : 's.first_name');
    $s_middle_col = in_array('middlename', $students_columns) ? 's.middlename' : (in_array('middle_name', $students_columns) ? 's.middle_name' : 's.middle_name');
    $s_last_col = in_array('lastname', $students_columns) ? 's.lastname' : (in_array('last_name', $students_columns) ? 's.last_name' : 's.last_name');
    
    // Join student_details with students to get name and gender
    // Extract year_section from group_code (first 2 characters, e.g., "3B-G1" -> "3B")
    // Use LEFT JOIN to ensure we get student_details data even if students record is missing
    $user_query = mysqli_prepare($conn, "
        SELECT sd.*, 
               $s_first_col as first_name,
               $s_middle_col as middle_name,
               $s_last_col as last_name,
               s.gender,
               CASE 
                   WHEN sd.group_code IS NOT NULL AND LENGTH(sd.group_code) >= 2 
                   THEN SUBSTRING(sd.group_code, 1, 2)
                   ELSE NULL
               END as year_section,
               sd.group_code 
        FROM student_details sd
        LEFT JOIN students s ON sd.student_id COLLATE utf8mb4_unicode_ci = s.$students_pk COLLATE utf8mb4_unicode_ci
        WHERE sd.$pk_column = ?
    ");
    
    if (!$user_query) {
        error_log("Profile query preparation failed: " . mysqli_error($conn));
    }
} else {
    $pk_column = getPrimaryKeyColumn($conn, 'employees');
    $user_query = mysqli_prepare($conn, "
        SELECT *, NULL as year_section, NULL as group_code 
        FROM employees 
        WHERE $pk_column = ?
    ");
}
if ($user_query) {
    mysqli_stmt_bind_param($user_query, "i", $user_id);
    mysqli_stmt_execute($user_query);
    $user_result = mysqli_stmt_get_result($user_query);
    $user_data = mysqli_fetch_assoc($user_result);
    
    // Debug: Log if no data found or if year_section is missing
    if (!$user_data && $user_type === 'student') {
        error_log("Profile: No user data found for student user_id: $user_id");
    } elseif ($user_type === 'student') {
        if (!isset($user_data['group_code']) || empty($user_data['group_code'])) {
            error_log("Profile: group_code is missing for student user_id: $user_id");
        }
        if (!isset($user_data['year_section']) || $user_data['year_section'] === null) {
            error_log("Profile: year_section is NULL for student user_id: $user_id, group_code: " . (isset($user_data['group_code']) ? $user_data['group_code'] : 'NULL'));
        }
    }
} else {
    $user_data = null;
    error_log("Profile: Query preparation failed for user_id: $user_id");
}

// Normalize column names for consistent access (handle different formats)
if ($user_data) {
    // Handle different column name formats
    if (!isset($user_data['first_name']) && isset($user_data['firstname'])) {
        $user_data['first_name'] = $user_data['firstname'];
    }
    if (!isset($user_data['middle_name']) && isset($user_data['middlename'])) {
        $user_data['middle_name'] = $user_data['middlename'];
    }
    if (!isset($user_data['last_name']) && isset($user_data['lastname'])) {
        $user_data['last_name'] = $user_data['lastname'];
    }
}

// Handle upload messages
$upload_message = '';
$upload_msg_type = '';
if (isset($_SESSION['upload_message'])) {
    $upload_message = $_SESSION['upload_message'];
    $upload_msg_type = $_SESSION['upload_msg_type'];
    unset($_SESSION['upload_message'], $_SESSION['upload_msg_type']);
}

// Set default values if not found
$first_name = isset($user_data['first_name']) ? trim($user_data['first_name']) : '';
$middle_name = isset($user_data['middle_name']) ? trim($user_data['middle_name']) : '';
$last_name = isset($user_data['last_name']) ? trim($user_data['last_name']) : '';

$full_name = trim($first_name . ' ' . $middle_name . ' ' . $last_name);
if (empty($full_name)) {
    $full_name = 'Not specified';
}

$email = isset($user_data['email']) ? $user_data['email'] : '';

// Get group_code and year_section - with multiple fallbacks
$group_code = 'Not assigned';
$year_section = 'N/A';

if ($user_type === 'student') {
    // First try from query result
    if (isset($user_data['group_code']) && !empty($user_data['group_code'])) {
        $group_code = $user_data['group_code'];
    } else {
        // Fallback 1: Direct query by user_id
        $pk_column = getPrimaryKeyColumn($conn, 'student_details');
        $gcStmt = $conn->prepare("SELECT group_code FROM student_details WHERE $pk_column = ? LIMIT 1");
        if ($gcStmt) {
            $gcStmt->bind_param("i", $user_id);
            $gcStmt->execute();
            $gcResult = $gcStmt->get_result();
            if ($gcRow = $gcResult->fetch_assoc()) {
                if (isset($gcRow['group_code']) && !empty($gcRow['group_code'])) {
                    $group_code = $gcRow['group_code'];
                }
            }
            $gcStmt->close();
        }
        
        // Fallback 2: Query by email if still not found
        if (($group_code === 'Not assigned' || empty($group_code)) && !empty($email)) {
            $gcStmt2 = $conn->prepare("SELECT group_code FROM student_details WHERE email = ? LIMIT 1");
            if ($gcStmt2) {
                $gcStmt2->bind_param("s", $email);
                $gcStmt2->execute();
                $gcResult2 = $gcStmt2->get_result();
                if ($gcRow2 = $gcResult2->fetch_assoc()) {
                    if (isset($gcRow2['group_code']) && !empty($gcRow2['group_code'])) {
                        $group_code = $gcRow2['group_code'];
                    }
                }
                $gcStmt2->close();
            }
        }
    }
    
    // Extract year_section from group_code
    if (!empty($group_code) && $group_code !== 'Not assigned' && strlen($group_code) >= 2) {
        $year_section = substr($group_code, 0, 2);
    } elseif (isset($user_data['year_section']) && $user_data['year_section'] !== null && $user_data['year_section'] !== '') {
        $year_section = $user_data['year_section'];
    }
}
$roles_array = getUserRoles($user_data);
$roles_display = !empty($roles_array)
    ? implode(', ', array_map('getRoleDisplayName', $roles_array))
    : 'Student';
// Use user_type for display check - more reliable than hasRole
$is_student = ($user_type === 'student');
$gender = isset($user_data['gender']) && !empty($user_data['gender']) ? ucfirst($user_data['gender']) : 'Not specified';

// Set avatar path to match navbar structure
$avatar = isset($user_data['avatar']) && $user_data['avatar'] !== 'avatar.png'
    ? '../assets/img/avatar/' . $user_data['avatar']
    : '../assets/img/avatar.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile | Captrack Vault</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="icon" href="../assets/img/captrack.png" type="image/png">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 10px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            margin-bottom: 1rem;
        }
        
        .profile-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: #007bff;
        }
        
        .info-content h6 {
            margin: 0;
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .info-content p {
            margin: 0;
            color: #212529;
            font-weight: 500;
        }
        
        .settings-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .settings-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .settings-item:last-child {
            border-bottom: none;
        }
        
        .settings-info {
            display: flex;
            align-items: center;
        }
        
        .settings-info i {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: #007bff;
        }
        
        /* Modal Styles */
        .form-control { 
            padding-right: 40px;
            height: 38px;
        }
        
        .form-control.valid {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
        }
        
        .form-control.invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }
        
        .password-container { 
            position: relative;
            margin-bottom: 1rem;
        }
        
        .password-toggle { 
            position: absolute; 
            right: 10px; 
            top: 75%; 
            transform: translateY(-50%); 
            cursor: pointer; 
            color: #6c757d;
            font-size: 1.1rem;
            z-index: 10;
            line-height: 1;
        }
        
        .form-control:focus + .password-toggle { 
            color: #007bff;
        }
        
        .form-label {
            position: relative;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        
        .form-label.valid::after {
            content: '\2713';
            color: #28a745;
            font-size: 1rem;
            position: absolute;
            right: -20px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .validation-note {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
        }
        
        .validation-note.show {
            display: block;
        }
    </style>
</head>
<body>

<?php include '../assets/includes/sidebar.php'; ?>

<div class="main-content">
    <?php include '../assets/includes/navbar.php'; ?>

    <!-- Profile Header -->
    <div class="profile-header text-center">
        <img src="<?= htmlspecialchars($avatar) ?>" alt="Profile Picture" class="profile-avatar">
        <h3 class="mb-1"><?= htmlspecialchars($full_name) ?></h3>
        <p class="mb-0 opacity-75"><?= htmlspecialchars($roles_display) ?></p>
    </div>

    <div class="row">
        <?php if ($upload_message): ?>
            <div class="col-12 mb-3">
                <div class="alert alert-<?= $upload_msg_type === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($upload_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Profile Information -->
        <div class="col-md-8">
            <div class="profile-card">
                <h5 class="mb-4"><i class="bi bi-person-circle me-2"></i>Personal Information</h5>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="bi bi-person"></i>
                    </div>
                    <div class="info-content">
                        <h6>Full Name</h6>
                        <p><?= htmlspecialchars($full_name) ?></p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="bi bi-envelope"></i>
                    </div>
                    <div class="info-content">
                        <h6>Email Address</h6>
                        <p><?= htmlspecialchars($email) ?></p>
                    </div>
                </div>
                
                <?php if ($user_type === 'student'): ?>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="info-content">
                        <h6>Year & Section</h6>
                        <p><?= htmlspecialchars($year_section !== 'N/A' ? $year_section : 'Not assigned') ?></p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="info-content">
                        <h6>Group Code</h6>
                        <p><?= htmlspecialchars($group_code) ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="bi bi-gender-ambiguous"></i>
                    </div>
                    <div class="info-content">
                        <h6>Gender</h6>
                        <p><?= htmlspecialchars($gender) ?></p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="info-content">
                        <h6>Role</h6>
                        <p><?= htmlspecialchars($roles_display) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings -->
        <div class="col-md-4">
            <div class="settings-card">
                <h5 class="mb-4"><i class="bi bi-gear me-2"></i>Account Settings</h5>
                
                <div class="settings-item">
                    <div class="settings-info">
                        <i class="bi bi-key"></i>
                        <div>
                            <h6 class="mb-0">Change Password</h6>
                            <small class="text-muted">Update your account password</small>
                        </div>
                    </div>
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="bi bi-pencil"></i> Change
                    </button>
                </div>
                
                <div class="settings-item">
                    <div class="settings-info">
                        <i class="bi bi-image"></i>
                        <div>
                            <h6 class="mb-0">Profile Picture</h6>
                            <small class="text-muted">Update your profile image</small>
                        </div>
                    </div>
                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editAvatarModal">
                        <i class="bi bi-upload"></i> Upload
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">
                    <i class="bi bi-key me-2"></i>Change Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" id="passwordForm">
                <div class="modal-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="password-container">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                        <i class="bi bi-eye-slash password-toggle" id="toggleCurrentPassword"></i>
                    </div>

                    <div class="password-container">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <i class="bi bi-eye-slash password-toggle" id="toggleNewPassword"></i>
                        <div class="validation-note" id="new_password_note">Password must be 6-12 characters long</div>
                    </div>

                    <div class="password-container">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <i class="bi bi-eye-slash password-toggle" id="toggleConfirmPassword"></i>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
<script>
    // Show modal if there's a message (for password change feedback)
    <?php if ($message): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
            modal.show();
        });
    <?php endif; ?>

    // Avatar input change handler
    document.getElementById('avatarInput').addEventListener('change', function (event) {
        const file = event.target.files[0];
        const preview = document.getElementById('avatarPreview');
        if (file) {
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                this.value = '';
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Only JPG, JPEG, PNG & GIF files are allowed');
                this.value = '';
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Remove avatar handler
    document.getElementById('removeAvatarBtn')?.addEventListener('click', function() {
        if (confirm('Are you sure you want to delete your current avatar?')) {
            document.getElementById('removeAvatarInput').value = '1';
            this.closest('form').submit();
        }
    });

    // Toggle password visibility
    function togglePasswordVisibility(inputId, toggleId) {
        const input = document.getElementById(inputId);
        const toggle = document.getElementById(toggleId);
        
        toggle.addEventListener('click', () => {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            toggle.classList.toggle('bi-eye');
            toggle.classList.toggle('bi-eye-slash');
        });
    }

    // Apply toggle functionality to each password field
    togglePasswordVisibility('current_password', 'toggleCurrentPassword');
    togglePasswordVisibility('new_password', 'toggleNewPassword');
    togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');

    // Real-time validation
    const currentPasswordInput = document.getElementById('current_password');
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const currentPasswordLabel = document.querySelector('label[for="current_password"]');
    const newPasswordLabel = document.querySelector('label[for="new_password"]');
    const confirmPasswordLabel = document.querySelector('label[for="confirm_password"]');
    const newPasswordNote = document.getElementById('new_password_note');

    // Validate current password via AJAX
    currentPasswordInput.addEventListener('input', async () => {
        const password = currentPasswordInput.value;
        if (password.length > 0) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=validate_current_password&current_password=${encodeURIComponent(password)}`
                });
                const result = await response.json();
                if (result.valid) {
                    currentPasswordInput.classList.remove('invalid');
                    currentPasswordInput.classList.add('valid');
                    currentPasswordLabel.classList.add('valid');
                } else {
                    currentPasswordInput.classList.remove('valid');
                    currentPasswordInput.classList.add('invalid');
                    currentPasswordLabel.classList.remove('valid');
                }
            } catch (error) {
                console.error('Error validating password:', error);
            }
        } else {
            currentPasswordInput.classList.remove('valid', 'invalid');
            currentPasswordLabel.classList.remove('valid');
        }
    });

    // Validate new password (6-12 chars)
    newPasswordInput.addEventListener('input', () => {
        const password = newPasswordInput.value;
        const isValid = password.length >= 6 && password.length <= 12;
        if (password.length > 0) {
            if (isValid) {
                newPasswordInput.classList.remove('invalid');
                newPasswordInput.classList.add('valid');
                newPasswordLabel.classList.add('valid');
                newPasswordNote.classList.remove('show');
            } else {
                newPasswordInput.classList.remove('valid');
                newPasswordInput.classList.add('invalid');
                newPasswordLabel.classList.remove('valid');
                newPasswordNote.classList.add('show');
            }
        } else {
            newPasswordInput.classList.remove('valid', 'invalid');
            newPasswordLabel.classList.remove('valid');
            newPasswordNote.classList.remove('show');
        }
        // Trigger confirm password validation
        confirmPasswordInput.dispatchEvent(new Event('input'));
    });

    // Validate confirm password (matches new password)
    confirmPasswordInput.addEventListener('input', () => {
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        const isNewPasswordValid = newPassword.length >= 6 && newPassword.length <= 12;
        const isValid = confirmPassword.length > 0 && newPassword === confirmPassword && isNewPasswordValid;
        if (confirmPassword.length > 0) {
            if (isValid) {
                confirmPasswordInput.classList.remove('invalid');
                confirmPasswordInput.classList.add('valid');
                confirmPasswordLabel.classList.add('valid');
            } else {
                confirmPasswordInput.classList.remove('valid');
                confirmPasswordInput.classList.add('invalid');
                confirmPasswordLabel.classList.remove('valid');
            }
        } else {
            confirmPasswordInput.classList.remove('valid', 'invalid');
            confirmPasswordLabel.classList.remove('valid');
        }
    });

    // Clear form when modal is hidden
    document.getElementById('changePasswordModal').addEventListener('hidden.bs.modal', function () {
        // Clear form inputs
        document.getElementById('passwordForm').reset();
        
        // Clear validation classes
        const inputs = ['current_password', 'new_password', 'confirm_password'];
        const labels = ['label[for="current_password"]', 'label[for="new_password"]', 'label[for="confirm_password"]'];
        
        inputs.forEach(id => {
            const input = document.getElementById(id);
            input.classList.remove('valid', 'invalid');
        });
        
        labels.forEach(selector => {
            const label = document.querySelector(selector);
            label.classList.remove('valid');
        });
        
        // Hide validation note
        newPasswordNote.classList.remove('show');
    });
</script>
</body>
</html>