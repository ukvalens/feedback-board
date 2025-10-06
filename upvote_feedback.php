<?php
session_start();
include 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback_id = $_POST['feedback_id'];
    $user_ip = $_SERVER['REMOTE_ADDR'];

    // Check if user has already voted
    $check_query = "SELECT id FROM votes WHERE feedback_id = ? AND ip_address = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('is', $feedback_id, $user_ip);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $_SESSION['message'] = 'You have already upvoted this feedback!';
        $_SESSION['message_type'] = 'error';
        header('Location: index.php');
        exit;
    }
    $check_stmt->close();

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert vote
        $vote_query = "INSERT INTO votes (feedback_id, ip_address) VALUES (?, ?)";
        $vote_stmt = $conn->prepare($vote_query);
        $vote_stmt->bind_param('is', $feedback_id, $user_ip);
        $vote_stmt->execute();
        $vote_stmt->close();

        // Update upvote count
        $update_query = "UPDATE feedbacks SET upvotes = upvotes + 1 WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param('i', $feedback_id);
        $update_stmt->execute();
        $update_stmt->close();

        $conn->commit();
        
        $_SESSION['message'] = 'Upvote added successfully!';
        $_SESSION['message_type'] = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = 'Error adding upvote. Please try again.';
        $_SESSION['message_type'] = 'error';
    }

    header('Location: index.php');
    exit;
}
?>