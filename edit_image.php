<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'database.php';

$errors = array();
$success = '';
$image = null; // Initialize $image variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $newFilename = trim($_POST['filename']);

    if (empty($newFilename)) {
        $errors[] = 'Filename cannot be empty.';
    } else {
        $stmt = $pdo->prepare("UPDATE images SET filename = ? WHERE id = ?");
        if ($stmt->execute([$newFilename, $id])) {
            $success = 'Image details updated successfully.';
        } else {
            $errors[] = 'Failed to update image details.';
        }
    }
}

// Check if ID is specified and fetch image details
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM images WHERE id = ?");
    $stmt->execute([$id]);
    $image = $stmt->fetch();
    if (!$image) {
        $errors[] = 'Image not found.';
    }
} 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Image</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Edit Image</h2>
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

        <?php if ($image): ?>
            <form action="edit_image.php" method="post">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($image['id']); ?>">
                <label for="filename">Filename:</label>
                <input type="text" id="filename" name="filename" value="<?php echo htmlspecialchars($image['filename']); ?>" required>
                <button type="submit">Update</button>
            </form>
        <?php else: ?>
            
        <?php endif; ?>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>
