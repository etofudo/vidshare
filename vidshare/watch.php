<?php
session_start();
require_once 'config/database.php';

if (!isset($_GET['v'])) {
    header('Location: index.php');
    exit();
}

$video_id = $_GET['v'];
$sql = "SELECT v.*, u.username 
        FROM videos v 
        JOIN users u ON v.user_id = u.user_id 
        WHERE v.video_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $video_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$video = $result->fetch_assoc();

// Increment view count
$update_views = "UPDATE videos SET views = views + 1 WHERE video_id = ?";
$stmt = $conn->prepare($update_views);
$stmt->bind_param("i", $video_id);
$stmt->execute();

// Get comments
$comments_sql = "SELECT c.*, u.username 
                FROM comments c 
                JOIN users u ON c.user_id = u.user_id 
                WHERE c.video_id = ? 
                ORDER BY c.created_at DESC";
$stmt = $conn->prepare($comments_sql);
$stmt->bind_param("i", $video_id);
$stmt->execute();
$comments = $stmt->get_result();

// Handle comment submission
$comment_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        $insert_comment = "INSERT INTO comments (video_id, user_id, comment) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_comment);
        $stmt->bind_param("iis", $video_id, $_SESSION['user_id'], $comment);
        if ($stmt->execute()) {
            header("Location: watch.php?v=" . $video_id);
            exit();
        } else {
            $comment_message = "Error posting comment.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($video['title']); ?> - VideoShare</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .video-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .video-player {
            width: 100%;
            background: #000;
            aspect-ratio: 16/9;
        }
        
        .video-info {
            margin-top: 1rem;
        }
        
        .video-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .video-stats {
            display: flex;
            gap: 1rem;
            color: var(--gray-color);
            margin-bottom: 1rem;
        }
        
        .video-description {
            background: var(--white);
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .comments-section {
            background: var(--white);
            padding: 1rem;
            border-radius: 8px;
        }
        
        .comment-form {
            margin-bottom: 2rem;
        }
        
        .comment-form textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 1rem;
            resize: vertical;
        }
        
        .comment {
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }
        
        .comment:last-child {
            border-bottom: none;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .comment-author {
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .comment-date {
            color: var(--gray-color);
            font-size: 0.9rem;
        }
        
        .related-videos {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .related-video {
            display: flex;
            gap: 1rem;
            text-decoration: none;
            color: inherit;
        }
        
        .related-video-thumbnail {
            width: 168px;
            height: 94px;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .related-video-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .related-video-info {
            flex: 1;
        }
        
        .related-video-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .related-video-author {
            color: var(--gray-color);
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .video-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="index.php">VideoShare</a>
            </div>
            <div class="search-bar">
                <form action="search.php" method="GET">
                    <input type="text" name="query" placeholder="Search videos...">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="nav-links">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="upload.php" class="upload-btn"><i class="fas fa-upload"></i> Upload</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <div class="video-container">
            <div class="main-content">
                <video class="video-player" controls>
                    <source src="<?php echo htmlspecialchars($video['video_path']); ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                
                <div class="video-info">
                    <h1 class="video-title"><?php echo htmlspecialchars($video['title']); ?></h1>
                    <div class="video-stats">
                        <span><?php echo number_format($video['views']); ?> views</span>
                        <span>•</span>
                        <span><?php echo date('F j, Y', strtotime($video['upload_date'])); ?></span>
                    </div>
                    
                    <div class="video-description">
                        <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                    </div>
                </div>
                
                <div class="comments-section">
                    <h2>Comments</h2>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <form class="comment-form" method="POST">
                            <textarea name="comment" placeholder="Add a comment..." required></textarea>
                            <button type="submit" class="submit-btn">Comment</button>
                        </form>
                    <?php else: ?>
                        <p>Please <a href="login.php">login</a> to comment.</p>
                    <?php endif; ?>
                    
                    <div class="comments">
                        <?php while($comment = $comments->fetch_assoc()): ?>
                            <div class="comment">
                                <div class="comment-header">
                                    <span class="comment-author"><?php echo htmlspecialchars($comment['username']); ?></span>
                                    <span class="comment-date"><?php echo date('F j, Y', strtotime($comment['created_at'])); ?></span>
                                </div>
                                <p><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            
            <div class="sidebar">
                <h3>Related Videos</h3>
                <div class="related-videos">
                    <?php
                    $related_sql = "SELECT v.*, u.username 
                                  FROM videos v 
                                  JOIN users u ON v.user_id = u.user_id 
                                  WHERE v.video_id != ? 
                                  ORDER BY RAND() 
                                  LIMIT 5";
                    $stmt = $conn->prepare($related_sql);
                    $stmt->bind_param("i", $video_id);
                    $stmt->execute();
                    $related = $stmt->get_result();
                    
                    while($related_video = $related->fetch_assoc()):
                    ?>
                        <a href="watch.php?v=<?php echo $related_video['video_id']; ?>" class="related-video">
                            <div class="related-video-thumbnail">
                                <img src="<?php echo htmlspecialchars($related_video['thumbnail_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($related_video['title']); ?>">
                            </div>
                            <div class="related-video-info">
                                <h4 class="related-video-title"><?php echo htmlspecialchars($related_video['title']); ?></h4>
                                <p class="related-video-author"><?php echo htmlspecialchars($related_video['username']); ?></p>
                                <p class="related-video-stats">
                                    <?php echo number_format($related_video['views']); ?> views
                                </p>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Add video player controls
        const video = document.querySelector('.video-player');
        
        // Save video progress
        video.addEventListener('timeupdate', function() {
            localStorage.setItem('videoProgress_' + <?php echo $video_id; ?>, video.currentTime);
        });
        
        // Load video progress
        const savedTime = localStorage.getItem('videoProgress_' + <?php echo $video_id; ?>);
        if (savedTime) {
            video.currentTime = savedTime;
        }
    </script>
</body>
</html> 