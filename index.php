<?php
session_start();
include 'config/database.php';

// Get user IP for vote tracking
$user_ip = $_SERVER['REMOTE_ADDR'];

// Handle filters and sorting
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'upvotes-desc';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT f.*, 
          (SELECT COUNT(*) FROM comments c WHERE c.feedback_id = f.id) as comment_count,
          (SELECT COUNT(*) FROM votes v WHERE v.feedback_id = f.id AND v.ip_address = ?) as user_voted
          FROM feedbacks f WHERE 1=1";

$params = [];
$types = '';

// Add IP parameter
$params[] = $user_ip;
$types .= 's';

if ($category != 'all') {
    $query .= " AND f.category = ?";
    $params[] = $category;
    $types .= 's';
}

if (!empty($search)) {
    $query .= " AND (f.title LIKE ? OR f.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

// Add sorting
switch ($sort) {
    case 'upvotes-desc':
        $query .= " ORDER BY f.upvotes DESC";
        break;
    case 'upvotes-asc':
        $query .= " ORDER BY f.upvotes ASC";
        break;
    case 'recent':
        $query .= " ORDER BY f.created_at DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY f.created_at ASC";
        break;
    default:
        $query .= " ORDER BY f.upvotes DESC";
}

// Prepare and execute query
$stmt = $conn->prepare($query);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$feedbacks = $result->fetch_all(MYSQLI_ASSOC);

// Get category counts
$category_query = "SELECT category, COUNT(*) as count FROM feedbacks GROUP BY category";
$category_result = $conn->query($category_query);
$category_counts = $category_result->fetch_all(MYSQLI_ASSOC);

$total_feedbacks = count($feedbacks);

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Board</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-comments"></i>
                    Feedback Board
                </div>
                <div class="header-stats">
                    <span id="feedback-count"><?php echo $total_feedbacks; ?></span> suggestions
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="main-content">
            <aside class="sidebar">
                <div class="filter-section">
                    <h2>Categories</h2>
                    <ul class="category-list" id="category-list">
                        <li class="category-item <?php echo $category == 'all' ? 'active' : ''; ?>" 
                            onclick="setCategory('all')">
                            All
                            <span class="category-count"><?php echo $total_feedbacks; ?></span>
                        </li>
                        <?php foreach ($category_counts as $cat): ?>
                            <li class="category-item <?php echo $category == $cat['category'] ? 'active' : ''; ?>" 
                                onclick="setCategory('<?php echo $cat['category']; ?>')">
                                <?php 
                                $category_names = [
                                    'feature' => 'Feature Request',
                                    'bug' => 'Bug Report',
                                    'improvement' => 'Improvement',
                                    'ui' => 'UI/UX',
                                    'other' => 'Other'
                                ];
                                echo $category_names[$cat['category']]; 
                                ?>
                                <span class="category-count"><?php echo $cat['count']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="sort-section">
                    <h2>Sort By</h2>
                    <div class="sort-options" id="sort-options">
                        <div class="sort-option <?php echo $sort == 'upvotes-desc' ? 'active' : ''; ?>" 
                             onclick="setSort('upvotes-desc')">Most Upvoted</div>
                        <div class="sort-option <?php echo $sort == 'upvotes-asc' ? 'active' : ''; ?>" 
                             onclick="setSort('upvotes-asc')">Least Upvoted</div>
                        <div class="sort-option <?php echo $sort == 'recent' ? 'active' : ''; ?>" 
                             onclick="setSort('recent')">Most Recent</div>
                        <div class="sort-option <?php echo $sort == 'oldest' ? 'active' : ''; ?>" 
                             onclick="setSort('oldest')">Oldest</div>
                    </div>
                </div>
            </aside>

            <main class="feedback-section">
                <form method="GET" action="" class="search-bar">
                    <input type="text" name="search" placeholder="Search feedback..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>

                <div class="feedback-form">
                    <h2>Submit Feedback</h2>
                    <form method="POST" action="submit_feedback.php">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" placeholder="Add a short, descriptive title" required>
                        </div>
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category" required>
                                <option value="">Select a category</option>
                                <option value="feature">Feature Request</option>
                                <option value="bug">Bug Report</option>
                                <option value="improvement">Improvement</option>
                                <option value="ui">UI/UX</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" placeholder="Please include any specific details" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-block">Submit Feedback</button>
                    </form>
                </div>

                <div class="feedback-list" id="feedback-list">
                    <?php if (count($feedbacks) === 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No feedback found</h3>
                            <p>Try changing your filters or search terms</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($feedbacks as $feedback): ?>
                            <div class="feedback-card">
                                <div class="feedback-header">
                                    <h3 class="feedback-title"><?php echo htmlspecialchars($feedback['title']); ?></h3>
                                    <span class="feedback-category">
                                        <?php 
                                        $category_names = [
                                            'feature' => 'Feature Request',
                                            'bug' => 'Bug Report',
                                            'improvement' => 'Improvement',
                                            'ui' => 'UI/UX',
                                            'other' => 'Other'
                                        ];
                                        echo $category_names[$feedback['category']]; 
                                        ?>
                                    </span>
                                </div>
                                <p class="feedback-description"><?php echo htmlspecialchars($feedback['description']); ?></p>
                                <div class="feedback-footer">
                                    <div class="upvote-section">
                                        <form method="POST" action="upvote_feedback.php" style="display: inline;">
                                            <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                            <button type="submit" class="upvote-btn <?php echo $feedback['user_voted'] ? 'voted' : ''; ?>">
                                                <i class="fas fa-chevron-up"></i>
                                            </button>
                                        </form>
                                        <span class="upvote-count"><?php echo $feedback['upvotes']; ?></span>
                                    </div>
                                    <div class="comment-count">
                                        <i class="fas fa-comment"></i> <?php echo $feedback['comment_count']; ?>
                                    </div>
                                </div>
                                <div class="comment-section">
                                    <form method="POST" action="add_comment.php" class="comment-form">
                                        <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                        <input type="text" name="comment_text" placeholder="Add a comment..." required>
                                        <button type="submit" class="add-comment">Comment</button>
                                    </form>
                                    <div class="comments-list">
                                        <?php
                                        // Get comments for this feedback using MySQLi
                                        $comment_query = "SELECT * FROM comments WHERE feedback_id = ? ORDER BY created_at ASC";
                                        $comment_stmt = $conn->prepare($comment_query);
                                        $comment_stmt->bind_param('i', $feedback['id']);
                                        $comment_stmt->execute();
                                        $comment_result = $comment_stmt->get_result();
                                        $comments = $comment_result->fetch_all(MYSQLI_ASSOC);
                                        $comment_stmt->close();
                                        
                                        foreach ($comments as $comment): 
                                        ?>
                                            <div class="comment">
                                                <div class="comment-author"><?php echo htmlspecialchars($comment['author']); ?></div>
                                                <div class="comment-text"><?php echo htmlspecialchars($comment['text']); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        function setCategory(category) {
            const url = new URL(window.location);
            url.searchParams.set('category', category);
            window.location.href = url.toString();
        }

        function setSort(sort) {
            const url = new URL(window.location);
            url.searchParams.set('sort', sort);
            window.location.href = url.toString();
        }

        // Remove the alert since we're using session messages
        // Auto-hide alert messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>