<?php
session_start();
include 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback_id = $_POST['feedback_id'];
    $comment_text = trim($_POST['comment_text']);

    // Validate input
    if (empty($comment_text)) {
        $_SESSION['message'] = 'Please enter a comment.';
        $_SESSION['message_type'] = 'error';
        header('Location: index.php');
        exit;
    }

    // Insert comment
    $query = "INSERT INTO comments (feedback_id, text) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('is', $feedback_id, $comment_text);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Comment added successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error adding comment. Please try again.';
        $_SESSION['message_type'] = 'error';
    }

    $stmt->close();
    header('Location: index.php');
    exit;
}
?>