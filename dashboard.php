<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'database.php';

$errors = array();
$success = '';

// Handle image upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['image'])) {
    if ($_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileSize = $_FILES['image']['size'];
        $fileType = $_FILES['image']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif');
        $maxFileSize = 5 * 1024 * 1024; // 5MB

        if (in_array($fileExtension, $allowedExtensions) && $fileSize <= $maxFileSize) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $newFileName = uniqid() . '.' . $fileExtension;
            $uploadFile = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $uploadFile)) {
                $stmt = $pdo->prepare("INSERT INTO images (filename, filepath) VALUES (?, ?)");
                if ($stmt->execute([$fileName, $uploadFile])) {
                    $success = 'Image uploaded successfully.';
                } else {
                    $errors[] = 'Failed to save image info to the database.';
                }
            } else {
                $errors[] = 'Failed to move uploaded file.';
            }
        } else {
            $errors[] = 'Invalid file type or file size too large.';
        }
    } else {
        $errors[] = 'No file uploaded or upload error.';
    }
}

// Handle image deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("SELECT filepath FROM images WHERE id = ?");
    $stmt->execute([$id]);
    $image = $stmt->fetch();
    
    if ($image) {
        unlink($image['filepath']);
        $stmt = $pdo->prepare("DELETE FROM images WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = 'Image deleted successfully.';
        } else {
            $errors[] = 'Failed to delete image from database.';
        }
    } else {
        $errors[] = 'Image not found.';
    }
}

// Fetch images from the database
$images = $pdo->query("SELECT * FROM images ORDER BY uploaded_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    
</head>
<body>
    <div class="header">
        <form action="index.php">
        <button type="submit">Logout</button>
</form>
    </div>
    <div class="container">
        <h2>Sports Car Image Library</h2>
        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h3>Upload New Image</h3>
        <form action="dashboard.php" method="post" enctype="multipart/form-data">
            <label for="image">Choose image:</label>
            <input type="file" id="image" name="image" accept="image/*" required>
            <button type="submit">Upload</button>
        </form>

        <h3>Manage Images</h3>
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Filename</th>
                    <th>Uploaded At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($images as $image): ?>
                    <tr>
                        <td><a href="#" class="image-link" data-filepath="<?php echo htmlspecialchars($image['filepath']); ?>">
                            <img src="<?php echo htmlspecialchars($image['filepath']); ?>" alt="<?php echo htmlspecialchars($image['filename']); ?>" style="max-width: 100px;">
                        </a></td>
                        <td><?php echo htmlspecialchars($image['filename']); ?></td>
                        <td><?php echo htmlspecialchars($image['uploaded_at']); ?></td>
                        <td>
                            <a href="edit_image.php?id=<?php echo htmlspecialchars($image['id']); ?>">Edit</a> |
                            <a href="?delete=<?php echo htmlspecialchars($image['id']); ?>" onclick="return confirm('Are you sure you want to delete this image?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <div id="imageModal" class="modal">
        <span class="close">&times;</span>
        <div class="modal-content">
            <img id="modalImage" style="width: 100%;">
        </div>
    </div>

    <script>
        var modal = document.getElementById("imageModal");
        var modalImg = document.getElementById("modalImage");
        var closeBtn = document.getElementsByClassName("close")[0];

        document.querySelectorAll(".image-link").forEach(function(link) {
            link.addEventListener("click", function(event) {
                event.preventDefault();
                var filepath = this.getAttribute("data-filepath");
                modal.style.display = "block";
                modalImg.src = filepath;
            });
        });

        closeBtn.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
