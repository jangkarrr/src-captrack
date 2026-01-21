<?php
// Start the session
session_start();

// Include the database connection
include '../config/database.php';

// Check if the form has been submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if the email and password are provided
    if (isset($_POST['email']) && isset($_POST['password'])) {
        // Get login data
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Query the database to find the user by email - Check both employees and student_details
        $user = null;
        $user_type = null;
        
        // First check employees table - handle different column names
        $sql = "SELECT *, 'employee' as user_type FROM employees WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_type = 'employee';
            
            // Normalize column names for employees table (handle firstname/lastname vs first_name/last_name)
            if (isset($user['employee_id']) && !isset($user['id'])) {
                $user['id'] = $user['employee_id'];
            }
            if (isset($user['firstname']) && !isset($user['first_name'])) {
                $user['first_name'] = $user['firstname'];
            }
            if (isset($user['lastname']) && !isset($user['last_name'])) {
                $user['last_name'] = $user['lastname'];
            }
            // Handle avatar/profile_pic
            if (isset($user['profile_pic']) && !isset($user['avatar'])) {
                $user['avatar'] = $user['profile_pic'];
            }
        } else {
            // Check student_details table - join with students to get name
            $stmt->close();
            
            // Detect column names for students table
            $students_columns = [];
            $students_cols_result = $conn->query("SHOW COLUMNS FROM students");
            if ($students_cols_result) {
                while ($col = $students_cols_result->fetch_assoc()) {
                    $students_columns[] = $col['Field'];
                }
                $students_cols_result->close();
            }
            $students_pk = 'student_id'; // Default
            $students_pk_result = $conn->query("SHOW COLUMNS FROM students");
            if ($students_pk_result) {
                while ($col = $students_pk_result->fetch_assoc()) {
                    if ($col['Key'] === 'PRI') {
                        $students_pk = $col['Field'];
                        break;
                    }
                }
                $students_pk_result->close();
            }
            
            $s_first_col = in_array('firstname', $students_columns) ? 'firstname' : (in_array('first_name', $students_columns) ? 'first_name' : 'first_name');
            $s_last_col = in_array('lastname', $students_columns) ? 'lastname' : (in_array('last_name', $students_columns) ? 'last_name' : 'last_name');
            
            // Detect primary key for student_details
            $sd_pk = 'id'; // Default
            $sd_pk_result = $conn->query("SHOW COLUMNS FROM student_details");
            if ($sd_pk_result) {
                while ($col = $sd_pk_result->fetch_assoc()) {
                    if ($col['Key'] === 'PRI') {
                        $sd_pk = $col['Field'];
                        break;
                    }
                }
                $sd_pk_result->close();
            }
            
            // Join student_details with students to get name
            $sql = "SELECT sd.*, s.$s_first_col AS first_name, s.$s_last_col AS last_name, 'student' as user_type 
                    FROM student_details sd 
                    LEFT JOIN students s ON sd.student_id = s.$students_pk 
                    WHERE sd.email = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $user_type = 'student';
                // Set role for students (student_details table doesn't have role column)
                $user['role'] = 'student';
            }
        }

        // Check if user exists
        if ($user && $user_type) {
            // Check if the user is verified (for employees) or status check for students
            $status_field = ($user_type === 'employee') ? 'status' : 'status';
            if (isset($user[$status_field]) && $user[$status_field] !== 'verified') {
                $error_message = "Your account is not verified yet. Please wait for the administrator to verify your details.";
            } else {
                // Verify the password
                if (password_verify($password, $user['password'])) {
                    // Set session variables for user - UPDATED FOR MULTI-ROLE
                    // Handle different ID column names
                    $user_id = isset($user['id']) ? $user['id'] : (isset($user['employee_id']) ? $user['employee_id'] : null);
                    $_SESSION['user_id'] = $user_id;
                    
                    // Handle different name column names
                    $first_name = isset($user['first_name']) ? $user['first_name'] : (isset($user['firstname']) ? $user['firstname'] : '');
                    $last_name = isset($user['last_name']) ? $user['last_name'] : (isset($user['lastname']) ? $user['lastname'] : '');
                    $_SESSION['name'] = trim($first_name . ' ' . $last_name);
                    
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = isset($user['role']) ? $user['role'] : '';
                    $_SESSION['roles'] = isset($user['roles']) ? $user['roles'] : (isset($user['role']) ? $user['role'] : ''); // Store all roles
                    
                    // Handle avatar/profile_pic
                    $avatar = isset($user['avatar']) ? $user['avatar'] : (isset($user['profile_pic']) ? $user['profile_pic'] : 'avatar.png');
                    $_SESSION['avatar'] = !empty($avatar) ? $avatar : 'avatar.png';
                    
                    // Normalize user_data for role functions (ensure consistent column names)
                    $normalized_user_data = $user;
                    if (!isset($normalized_user_data['id']) && isset($normalized_user_data['employee_id'])) {
                        $normalized_user_data['id'] = $normalized_user_data['employee_id'];
                    }
                    if (!isset($normalized_user_data['first_name']) && isset($normalized_user_data['firstname'])) {
                        $normalized_user_data['first_name'] = $normalized_user_data['firstname'];
                    }
                    if (!isset($normalized_user_data['last_name']) && isset($normalized_user_data['lastname'])) {
                        $normalized_user_data['last_name'] = $normalized_user_data['lastname'];
                    }
                    if (!isset($normalized_user_data['avatar']) && isset($normalized_user_data['profile_pic'])) {
                        $normalized_user_data['avatar'] = $normalized_user_data['profile_pic'];
                    }
                    // Ensure role is set (should already be there, but make sure)
                    if (!isset($normalized_user_data['role']) || empty($normalized_user_data['role'])) {
                        if (isset($user['role']) && !empty($user['role'])) {
                            $normalized_user_data['role'] = $user['role'];
                        } elseif (isset($user['roles']) && !empty($user['roles'])) {
                            // Extract first role from roles field
                            $roles_array = array_map('trim', explode(',', $user['roles']));
                            if (!empty($roles_array)) {
                                $normalized_user_data['role'] = $roles_array[0];
                            }
                        }
                    }
                    
                    // Ensure roles field is set
                    if (!isset($normalized_user_data['roles']) && isset($user['roles'])) {
                        $normalized_user_data['roles'] = $user['roles'];
                    }
                    
                    $_SESSION['user_data'] = $normalized_user_data; // Store normalized user data for role functions
                    $_SESSION['active_role'] = isset($normalized_user_data['role']) ? strtolower(trim($normalized_user_data['role'])) : ''; // Set initial active role
                    $_SESSION['user_type'] = $user_type; // Store whether user is employee or student

                    // If the user is a student, fetch and store year_section in session
                    if ($user_type === 'student' || (isset($user['role']) && $user['role'] === 'student')) {
                        // Get year_section from group_code (first 2 characters) or from student_details
                        $year_section = '';
                        if (isset($user['group_code']) && !empty($user['group_code'])) {
                            // Extract year_section from group_code (e.g., "3B-G1" -> "3B")
                            $year_section = substr($user['group_code'], 0, 2);
                        } elseif (isset($user['year_section'])) {
                            $year_section = $user['year_section'];
                        }
                        $_SESSION['year_section'] = $year_section;
                    }

                    // Check if the user is an admin - use normalized role
                    $user_role = isset($user['role']) ? strtolower(trim($user['role'])) : '';
                    
                    // Also check roles field for admin
                    $is_admin = ($user_role === 'admin');
                    if (!$is_admin && isset($user['roles']) && !empty($user['roles'])) {
                        $roles_array = array_map('trim', array_map('strtolower', explode(',', $user['roles'])));
                        $is_admin = in_array('admin', $roles_array);
                    }
                    
                    if ($is_admin) {
                        // Ensure output buffering is clean before redirect
                        while (ob_get_level()) {
                            ob_end_clean();
                        }
                        header("Location: ../admin/dashboard.php");
                        exit();
                    } else {
                        // Redirect based on the user role or user_type
                        $redirect_role = isset($user['role']) ? strtolower(trim($user['role'])) : ($user_type === 'student' ? 'student' : '');
                        
                        // Also check roles field if role is empty
                        if (empty($redirect_role) && isset($user['roles']) && !empty($user['roles'])) {
                            $roles_array = array_map('trim', array_map('strtolower', explode(',', $user['roles'])));
                            if (!empty($roles_array)) {
                                $redirect_role = $roles_array[0]; // Use first role
                            }
                        }
                        
                        // Ensure output buffering is clean before redirect
                        while (ob_get_level()) {
                            ob_end_clean();
                        }
                        
                        $redirect_url = null;
                        switch ($redirect_role) {
                            case 'faculty':
                                $redirect_url = "../faculty/home.php";
                                break;
                            case 'student':
                                $redirect_url = "../student/home.php";
                                break;
                            case 'adviser':
                                $redirect_url = "../adviser/home.php";
                                break;
                            case 'dean':
                                $redirect_url = "../dean/home.php";
                                break;
                            case 'panelist':
                                $redirect_url = "../panel/home.php";
                                break;
                            case 'grammarian':
                                $redirect_url = "../grammarian/home.php";
                                break;
                            case 'plagscanner':
                                $redirect_url = "../plagscanner/home.php";
                                break;
                            default:
                                error_log("Login error: Invalid or missing user role. Role: '$redirect_role', User type: '$user_type', User data: " . json_encode($user));
                                $error_message = "Invalid user role: " . htmlspecialchars($redirect_role ?: 'not set');
                                break;
                        }
                        
                        if ($redirect_url) {
                            header("Location: $redirect_url");
                            exit();
                        }
                    }
                } else {
                    // Incorrect password
                    $error_message = "Invalid password.";
                }
            }
        } else {
            // No user found with the provided email
            $error_message = "No user found with this email.";
        }

        // Close the prepared statement
        $stmt->close();
    } else {
        // If email or password is not set in the form
        $error_message = "Please enter both email and password.";
    }
}

// Close the database connection
$conn->close();

// Check if there's a success message to display
$show_success_popup = isset($_SESSION['success_message']) && !empty($_SESSION['success_message']);
$success_message = $show_success_popup ? htmlspecialchars($_SESSION['success_message']) : '';

// Clear the success message after displaying it
if ($show_success_popup) {
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="icon" href="../assets/img/captrack.png" type="image/png">
    <title>Login</title>
</head>
<body>  
    <!-- Error Popup -->
    <div id="errorPopup" class="error-popup">
        <p id="errorMessage"></p>
    </div>

    <div id="successPopup" class="success-popup">
        <p id="successMessage"></p>
    </div>

    <div class="login-wrapper">
        <!-- Left Section -->
        <div class="left-section">
            <div class="left-content">
                <img src="../assets/img/captrack.png" alt="SRC Logo">
                <h2>CapTrack Vault</h2>
            </div>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <div class="login-form">
                <h1>Login</h1>
                <form method="POST" action="login.php">
                    <div class="input-container">
                        <input type="email" name="email" id="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        <label for="email">Email</label>
                    </div>
                    
                    <div class="input-container">
                        <input type="password" name="password" id="password" required>
                        <label for="password">Password</label>
                    </div>
                    
                    <div class="fogotpassword-container">
                        <p class="forgot-password-link">
                            <a href="forgot_password.php">Forgot Password?</a>
                        </p>
                    </div>
                    
                    <button type="submit">Login</button>
                    
                    <p class="register-link">
                        <br>Don't have an account? <a href="register.php">Register here</a>.
                    </p>
                </form>
            </div>
        </div>
    </div>

<script>
    // Enhanced input handling for floating labels
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], select');
        
        inputs.forEach(input => {
            // Check if input has content on page load (including pre-filled values)
            checkInputContent(input);
            
            // Add event listeners for input changes
            input.addEventListener('input', function() {
                checkInputContent(this);
            });
            
            input.addEventListener('change', function() {
                checkInputContent(this);
            });
            
            input.addEventListener('blur', function() {
                checkInputContent(this);
            });
        });
    });

    function checkInputContent(input) {
        if (input.value.trim() !== '' && input.value !== '') {
            input.classList.add('has-content');
        } else {
            input.classList.remove('has-content');
        }
    }

    // Show the error popup if there's an error message from PHP
    function showError(message) {
        document.getElementById('errorMessage').innerText = message;
        document.getElementById('errorPopup').classList.add('show');
        setTimeout(function() {
            document.getElementById('errorPopup').classList.remove('show');
        }, 3000);
    }

    // Show the success popup
    function showSuccess(message) {
        document.getElementById('successMessage').innerText = message;
        document.getElementById('successPopup').classList.add('show');
        setTimeout(function() {
            document.getElementById('successPopup').classList.remove('show');
        }, 3000);
    }

    // Actually call the functions with PHP data
    <?php if (isset($error_message)) { ?>
        showError("<?php echo addslashes($error_message); ?>");
    <?php } ?>

    <?php if ($show_success_popup) { ?>
        showSuccess("<?php echo addslashes($success_message); ?>");
    <?php } ?>
</script>
</body>
</html>