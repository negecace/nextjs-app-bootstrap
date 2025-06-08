<?php
require_once 'config/db.php';

// Function to check if installation is already done
function isInstalled($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'superadmin'");
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to create default company
function createDefaultCompany($pdo) {
    $stmt = $pdo->prepare("INSERT INTO companies (name, pattern_color) VALUES (?, ?)");
    $stmt->execute(['Caribbean Housing Solutions - Cahosol', '#007bff']);
    return $pdo->lastInsertId();
}

// Function to create a user
function createUser($pdo, $username, $password, $role, $company_id = null) {
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, company_id) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$username, $password_hash, $role, $company_id]);
}

// Start installation
$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if already installed
        if (isInstalled($pdo)) {
            die("Installation has already been completed.");
        }

        // Begin transaction
        $pdo->beginTransaction();

        // Create default company
        $company_id = createDefaultCompany($pdo);
        $messages[] = "Default company created successfully.";

        // Create super admin account
        if (createUser($pdo, 'superadmin', 'password123', 'superadmin', null)) {
            $messages[] = "Super admin account created successfully.";
        } else {
            throw new Exception("Failed to create super admin account.");
        }

        // Create default user account
        if (createUser($pdo, 'user123', 'password123', 'user', $company_id)) {
            $messages[] = "Default user account created successfully.";
        } else {
            throw new Exception("Failed to create default user account.");
        }

        // Commit transaction
        $pdo->commit();
        $messages[] = "Installation completed successfully!";

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $errors[] = "Installation failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Install RealEstateMee</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
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
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .info {
            background-color: #e2e3e5;
            color: #383d41;
            margin-top: 30px;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background-color: #0056b3;
        }
        ul {
            margin: 0;
            padding-left: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>RealEstateMee Installation</h1>

        <?php if (!empty($errors)): ?>
            <div class="message error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?=htmlspecialchars($error)?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($messages)): ?>
            <div class="message success">
                <ul>
                    <?php foreach ($messages as $message): ?>
                        <li><?=htmlspecialchars($message)?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!isInstalled($pdo)): ?>
            <form method="POST">
                <button type="submit">Install RealEstateMee</button>
            </form>

            <div class="message info">
                <h3>Default Accounts:</h3>
                <p><strong>Super Admin:</strong></p>
                <ul>
                    <li>Username: superadmin</li>
                    <li>Password: password123</li>
                </ul>
                <p><strong>Default User:</strong></p>
                <ul>
                    <li>Username: user123</li>
                    <li>Password: password123</li>
                </ul>
                <p><em>Note: Please change these passwords after installation!</em></p>
            </div>
        <?php else: ?>
            <div class="message info">
                <p>RealEstateMee is already installed.</p>
                <p>You can:</p>
                <ul>
                    <li><a href="super_admin_panel/login.php">Login as Super Admin</a></li>
                    <li><a href="user_management/login.php">Login as User</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
