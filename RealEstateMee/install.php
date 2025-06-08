<?php
require_once 'config/db.php';

class Installer {
    private $database;
    private $errors = [];
    private $success = [];

    public function __construct() {
        $this->database = new Database();
    }

    public function install() {
        try {
            // 1. Check PHP version
            if (version_compare(PHP_VERSION, '7.4.0', '<')) {
                throw new Exception('PHP version must be 7.4 or higher. Current version: ' . PHP_VERSION);
            }

            // 2. Check required PHP extensions
            $required_extensions = ['pdo', 'pdo_mysql', 'json', 'gd'];
            foreach ($required_extensions as $ext) {
                if (!extension_loaded($ext)) {
                    throw new Exception("Required PHP extension not loaded: {$ext}");
                }
            }

            // 3. Check database connection
            $db = $this->database->getConnection();
            $this->success[] = "Database connection successful";

            // 4. Create database schema
            $schema_file = __DIR__ . '/database/schema.sql';
            if (!file_exists($schema_file)) {
                throw new Exception('Database schema file not found');
            }

            $sql = file_get_contents($schema_file);
            $statements = array_filter(array_map('trim', explode(';', $sql)));

            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $db->exec($statement);
                }
            }
            $this->success[] = "Database schema created successfully";

            // 5. Create required directories
            $directories = [
                'public/uploads',
                'public/uploads/properties',
                'public/uploads/profiles',
                'logs'
            ];

            foreach ($directories as $dir) {
                if (!file_exists(__DIR__ . '/' . $dir)) {
                    mkdir(__DIR__ . '/' . $dir, 0755, true);
                }
            }
            $this->success[] = "Required directories created";

            // 6. Create .htaccess file for security
            $htaccess_content = "
Options -Indexes
<FilesMatch \"^\.ht\">
    Order allow,deny
    Deny from all
</FilesMatch>
";
            file_put_contents(__DIR__ . '/.htaccess', $htaccess_content);
            $this->success[] = "Security configurations applied";

            // 7. Create config file
            $config_sample = [
                'DEBUG_MODE' => false,
                'SITE_URL' => 'http://localhost/RealEstateMee',
                'UPLOAD_PATH' => __DIR__ . '/public/uploads',
                'MAX_FILE_SIZE' => 5242880, // 5MB
                'ALLOWED_FILE_TYPES' => ['jpg', 'jpeg', 'png', 'pdf']
            ];
            
            file_put_contents(
                __DIR__ . '/config/config.php',
                '<?php return ' . var_export($config_sample, true) . ';'
            );
            $this->success[] = "Configuration file created";

            return true;

        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getSuccess() {
        return $this->success;
    }
}

// Run installation if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $installer = new Installer();
    $success = $installer->install();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RealEstateMee Installation</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        .success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #0056b3;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            margin-bottom: 10px;
        }
        .requirements {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>RealEstateMee Installation</h1>
        
        <?php if (isset($installer)): ?>
            <?php if ($success): ?>
                <div class="success">
                    <h3>Installation Successful!</h3>
                    <ul>
                        <?php foreach ($installer->getSuccess() as $message): ?>
                            <li><?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p>Default superadmin credentials:</p>
                    <ul>
                        <li>Username: superadmin</li>
                        <li>Password: DefaultPass123</li>
                    </ul>
                    <p><strong>Please change these credentials immediately after logging in!</strong></p>
                </div>
            <?php else: ?>
                <div class="error">
                    <h3>Installation Failed</h3>
                    <ul>
                        <?php foreach ($installer->getErrors() as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="requirements">
            <h3>System Requirements</h3>
            <ul>
                <li>PHP version 7.4 or higher</li>
                <li>MySQL 5.7 or higher</li>
                <li>PDO PHP Extension</li>
                <li>JSON PHP Extension</li>
                <li>GD PHP Extension</li>
                <li>Write permissions for uploads and logs directories</li>
            </ul>
        </div>

        <?php if (!isset($success) || !$success): ?>
            <form method="POST" action="">
                <button type="submit" class="btn">Start Installation</button>
            </form>
        <?php else: ?>
            <a href="user_management/login.php" class="btn">Go to Login</a>
        <?php endif; ?>
    </div>
</body>
</html>
