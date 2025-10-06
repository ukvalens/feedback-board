<?php
session_start();
include 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];

    // Validate input
    if (empty($title) || empty($description) || empty($category)) {
        $_SESSION['message'] = 'Please fill in all fields.';
        $_SESSION['message_type'] = 'error';
        header('Location: index.php');
        exit;
    }

    // Insert feedback
    $query = "INSERT INTO feedbacks (title, description, category) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sss', $title, $description, $category);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Feedback submitted successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error submitting feedback. Please try again.';
        $_SESSION['message_type'] = 'error';
    }

    $stmt->close();
    header('Location: index.php');
    exit;
}
?>