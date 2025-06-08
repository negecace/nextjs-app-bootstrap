<?php
session_start();
require_once '../config/db.php';

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'superadmin') {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errors[] = "Both username and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ? AND role = 'superadmin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = 'superadmin';
            header("Location: dashboard.php");
            exit;
        } else {
            $errors[] = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Super Admin Login - RealEstateMee</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 400px; 
            margin: 50px auto; 
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 { 
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        form { 
            display: flex; 
            flex-direction: column; 
        }
        input { 
            margin-bottom: 15px; 
            padding: 10px; 
            font-size: 1em;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .error { 
            color: #dc3545;
            background-color: #f8d7da;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        button { 
            padding: 12px;
            font-size: 1em;
            cursor: pointer;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Super Admin Login</h2>
        <?php if ($errors): ?>
            <div class="error">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?=htmlspecialchars($error)?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <input type="text" name="username" placeholder="Username" value="<?=htmlspecialchars($username ?? '')?>" required />
            <input type="password" name="password" placeholder="Password" required />
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
