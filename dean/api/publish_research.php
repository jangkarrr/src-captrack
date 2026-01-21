<?php
session_start();

require_once '../../assets/includes/role_functions.php';

// Ensure user is authenticated and has dean role
if (!isset($_SESSION['user_data']) || !hasRole($_SESSION['user_data'], 'dean')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include '../../config/database.php';
require_once '../../assets/includes/notification_functions.php';

header('Content-Type: application/json');

$research_id = isset($_POST['id']) ? trim($_POST['id']) : '';

if ($research_id === '' || !is_numeric($research_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid research ID']);
    exit();
}

$research_id = (int)$research_id;

if ($research_id < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid research ID']);
    exit();
}

try {
    $conn->begin_transaction();

    // Ensure the research exists, is verified, and not yet published
    $get_query = "SELECT id, title, user_id, status, published_by_dean FROM books WHERE id = ? LIMIT 1";
    $get_stmt = $conn->prepare($get_query);
    $get_stmt->bind_param('i', $research_id);
    $get_stmt->execute();
    $result = $get_stmt->get_result();
    $book = $result->fetch_assoc();

    if (!$book) {
        throw new Exception('Research not found');
    }

    if (strtolower(trim($book['status'])) !== 'verified') {
        throw new Exception('Research must be verified by admin before publishing');
    }

    if (!empty($book['published_by_dean'])) {
        throw new Exception('Research is already published');
    }

    // Mark as published by dean
    $update_query = "UPDATE books SET published_by_dean = 1, dean_published_at = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('i', $research_id);
    $update_stmt->execute();

    if ($update_stmt->affected_rows === 0) {
        throw new Exception('Failed to publish research');
    }

    $user_id = $book['user_id'];
    $research_title = $book['title'];

    // Notify student that their research has been published (if user_id exists)
    if (!empty($user_id)) {
        createNotification(
            $conn,
            $user_id,
            'Research Published',
            'Your research "' . htmlspecialchars($research_title) . '" has been published in the CCS Research Repository.',
            'success',
            $research_id,
            'books'
        );
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Research published successfully',
        'research_title' => $research_title,
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($get_stmt)) {
        $get_stmt->close();
    }
    if (isset($update_stmt)) {
        $update_stmt->close();
    }
    $conn->close();
}
