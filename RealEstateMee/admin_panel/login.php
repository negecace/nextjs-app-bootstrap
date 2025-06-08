<?php
session_start();
require_once '../config/db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errors[] = "Username and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE username = ? AND role = 'admin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $username;
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard.php");
            exit;
        } else {
            $errors[] = "Invalid admin username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Login - RealEstateMee</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; }
        form { display: flex; flex-direction: column; }
        input { margin-bottom: 10px; padding: 8px; font-size: 1em; }
        .error { color: red; }
        button { padding: 10px; font-size: 1em; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Admin Login</h2>
    <?php if ($errors): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?=htmlspecialchars($error)?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="POST" action="login.php">
        <input type="text" name="username" placeholder="Admin Username" value="<?=htmlspecialchars($username ?? '')?>" required />
        <input type="password" name="password" placeholder="Password" required />
        <button type="submit">Login</button>
    </form>
    <p><a href="../user_management/login.php">User Login</a></p>
</body>
</html>
