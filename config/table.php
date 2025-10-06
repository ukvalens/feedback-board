<?php
include 'database.php';

// SQL statements to execute
$sql = "
CREATE DATABASE IF NOT EXISTS feedback_board;
USE feedback_board;

CREATE TABLE IF NOT EXISTS feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('feature', 'bug', 'improvement', 'ui', 'other') NOT NULL,
    upvotes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feedback_id INT,
    author VARCHAR(100) DEFAULT 'Anonymous',
    text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (feedback_id) REFERENCES feedbacks(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feedback_id INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vote (feedback_id, ip_address),
    FOREIGN KEY (feedback_id) REFERENCES feedbacks(id) ON DELETE CASCADE
);
";

// Execute multiple queries
if (mysqli_multi_query($conn, $sql)) {
    do {
        // Store first result set
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_next_result($conn));
    
    echo "Database and tables created successfully!";
    
    // Insert sample data
    $sample_data = "
    INSERT IGNORE INTO feedbacks (title, description, category, upvotes) VALUES
    ('Add dark mode', 'It would be great to have a dark mode option for the application to reduce eye strain in low-light conditions.', 'feature', 15),
    ('Fix login issue on mobile', 'When trying to log in on mobile devices, the login button doesn''t respond on the first tap.', 'bug', 8),
    ('Improve search functionality', 'The search could be more powerful with filters and advanced options.', 'improvement', 12),
    ('Redesign notification panel', 'The current notification panel is not very intuitive and could use a visual refresh.', 'ui', 6);
    
    INSERT IGNORE INTO comments (feedback_id, author, text) VALUES
    (1, 'User123', 'I would love this feature!'),
    (1, 'DesignerPro', 'This is already in our roadmap for Q3.'),
    (3, 'PowerUser', 'Yes! Filtering by date would be very helpful.');
    ";
    
    if (mysqli_multi_query($conn, $sample_data)) {
        do {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        } while (mysqli_next_result($conn));
        
        echo "Sample data inserted successfully!";
    }
    
} else {
    echo "Error creating database: " . mysqli_error($conn);
}

mysqli_close($conn);
?>