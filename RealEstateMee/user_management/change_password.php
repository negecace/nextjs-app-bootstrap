<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match.";
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($current_password, $user['password_hash'])) {
            // Update password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            if ($update_stmt->execute([$new_hash, $_SESSION['user_id']])) {
                $success = "Password changed successfully.";
            } else {
                $errors[] = "Failed to update password. Please try again.";
            }
        } else {
            $errors[] = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Change Password - RealEstateMee</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; }
        form { display: flex; flex-direction: column; }
        input { margin-bottom: 10px; padding: 8px; font-size: 1em; }
        .error { color: red; }
        .success { color: green; }
        button { padding: 10px; font-size: 1em; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Change Password</h2>
    <?php if ($errors): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?=htmlspecialchars($error)?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?=htmlspecialchars($success)?></div>
    <?php endif; ?>
    <form method="POST" action="change_password.php">
        <input type="password" name="current_password" placeholder="Current Password" required />
        <input type="password" name="new_password" placeholder="New Password" required />
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required />
        <button type="submit">Change Password</button>
    </form>
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>
