<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id'];
    
    // Handle video upload
    $video = $_FILES['video'];
    $video_name = time() . '_' . $video['name'];
    $video_path = 'uploads/videos/' . $video_name;
    
    // Handle thumbnail upload
    $thumbnail = $_FILES['thumbnail'];
    $thumbnail_name = time() . '_' . $thumbnail['name'];
    $thumbnail_path = 'uploads/thumbnails/' . $thumbnail_name;
    
    // Create upload directories if they don't exist
    if (!file_exists('uploads/videos')) {
        mkdir('uploads/videos', 0777, true);
    }
    if (!file_exists('uploads/thumbnails')) {
        mkdir('uploads/thumbnails', 0777, true);
    }
    
    // Move uploaded files
    if (move_uploaded_file($video['tmp_name'], $video_path) && 
        move_uploaded_file($thumbnail['tmp_name'], $thumbnail_path)) {
        
        // Get video duration using FFmpeg (if available)
        $duration = '00:00'; // Default duration
        if (function_exists('exec')) {
            $ffmpeg = exec('which ffmpeg');
            if ($ffmpeg) {
                $command = "ffmpeg -i " . escapeshellarg($video_path) . " 2>&1";
                $output = shell_exec($command);
                if (preg_match('/Duration: (\d{2}):(\d{2}):(\d{2})/', $output, $matches)) {
                    $duration = $matches[1] . ':' . $matches[2];
                }
            }
        }
        
        // Insert video information into database
        $sql = "INSERT INTO videos (user_id, title, description, video_path, thumbnail_path, duration) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssss", $user_id, $title, $description, $video_path, $thumbnail_path, $duration);
        
        if ($stmt->execute()) {
            $message = "Video uploaded successfully!";
        } else {
            $message = "Error uploading video: " . $conn->error;
        }
    } else {
        $message = "Error moving uploaded files.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video - VideoShare</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .upload-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group label {
            font-weight: 500;
            color: var(--text-color);
        }
        
        .form-group input[type="text"],
        .form-group textarea {
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .file-input {
            position: relative;
            display: inline-block;
        }
        
        .file-input input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: var(--light-blue);
            color: var(--primary-color);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-input-label:hover {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .submit-btn {
            background: var(--primary-color);
            color: var(--white);
            padding: 1rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .submit-btn:hover {
            background: var(--dark-blue);
        }
        
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            text-align: center;
        }
        
        .success {
            background: #e6f4ea;
            color: #137333;
        }
        
        .error {
            background: #fce8e6;
            color: #c5221f;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="index.php">VideoShare</a>
            </div>
            <div class="nav-links">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="upload-container">
            <h1>Upload Video</h1>
            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form class="upload-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Video Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="video">Video File</label>
                    <div class="file-input">
                        <label class="file-input-label">
                            <i class="fas fa-video"></i> Choose Video
                            <input type="file" id="video" name="video" accept="video/*" required>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="thumbnail">Thumbnail Image</label>
                    <div class="file-input">
                        <label class="file-input-label">
                            <i class="fas fa-image"></i> Choose Thumbnail
                            <input type="file" id="thumbnail" name="thumbnail" accept="image/*" required>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-upload"></i> Upload Video
                </button>
            </form>
        </div>
    </main>

    <script>
        // Preview selected files
        document.getElementById('video').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                this.previousElementSibling.textContent = fileName;
            }
        });

        document.getElementById('thumbnail').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                this.previousElementSibling.textContent = fileName;
            }
        });
    </script>
</body>
</html> 