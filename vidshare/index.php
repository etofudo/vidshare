<?php
                session_start();
                
                ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VideoShare - Share Your Videos</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <?php
                if(isset($_SESSION['user_id'])) {
                    echo '<a href="upload.php" class="upload-btn"><i class="fas fa-upload"></i> Upload</a>';
                }
                else {
                    echo '<a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>';
                    echo '<a href="register.php"><i class="fas fa-user-plus"></i> Register</a>';
                }
                ?>
                
                
            </div>
        </nav>
    </header>

    <main>
        <section class="featured-videos">
            <h2>Featured Videos</h2>
            <div class="video-grid">
                <?php
                require_once 'config/database.php';
                $sql = "SELECT * FROM videos ORDER BY upload_date DESC LIMIT 8";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo '<div class="video-card">';
                        echo '<a href="watch.php?v=' . $row['video_id'] . '">';
                        echo '<div class="thumbnail">';
                        echo '<img src="' . $row['thumbnail_path'] . '" alt="' . $row['title'] . '">';
                        echo '<span class="duration">' . $row['duration'] . '</span>';
                        echo '</div>';
                        echo '<div class="video-info">';
                        echo '<h3>' . $row['title'] . '</h3>';
                        echo '<p class="uploader">' . $row['username'] . '</p>';
                        echo '<p class="views">' . $row['views'] . ' views • ' . $row['upload_date'] . '</p>';
                        echo '</div>';
                        echo '</a>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No videos available yet.</p>';
                }
                ?>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>About VideoShare</h3>
                <p>Share your videos with the world</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="terms.php">Terms of Service</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 VideoShare. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html> 