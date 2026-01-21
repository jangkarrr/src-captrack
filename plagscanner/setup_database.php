<?php
// Database setup script for PlagScan functionality
include '../config/database.php';

echo "<h2>PlagScan Database Setup</h2>";

// Create plagscan_reviews table
$sql = "CREATE TABLE IF NOT EXISTS plagscan_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    student_id INT NOT NULL,
    manuscript_file VARCHAR(500) DEFAULT NULL,
    plagscan_result_file VARCHAR(500) DEFAULT NULL,
    ai_result_file VARCHAR(500) DEFAULT NULL,
    percent_similarity DECIMAL(5,2) DEFAULT NULL,
    status ENUM('pending', 'under_review', 'approved', 'rejected') DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    date_submitted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_reviewed TIMESTAMP NULL DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES project_working_titles(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✅ Table 'plagscan_reviews' created successfully or already exists.</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating table: " . $conn->error . "</p>";
}

// Check if upload directories exist
$manuscriptsDir = "../assets/uploads/plagscan";
$resultsDir = "../assets/uploads/plagscan_results";

if (!is_dir($manuscriptsDir)) {
    if (mkdir($manuscriptsDir, 0755, true)) {
        echo "<p style='color: green;'>✅ Created plagscan upload directory.</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create plagscan directory.</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Plagscan upload directory exists.</p>";
}

if (!is_dir($resultsDir)) {
    if (mkdir($resultsDir, 0755, true)) {
        echo "<p style='color: green;'>✅ Created plagscan results directory.</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create plagscan results directory.</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Plagscan results directory exists.</p>";
}

echo "<br><h3>Setup Complete!</h3>";
echo "<p><a href='home.php' class='btn btn-primary'>Go to PlagScanner Dashboard</a></p>";
echo "<p><a href='../admin/users.php' class='btn btn-secondary'>Manage Users</a></p>";

$conn->close();
?>


