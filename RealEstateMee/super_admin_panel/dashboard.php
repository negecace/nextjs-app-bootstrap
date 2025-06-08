<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit;
}

// Fetch statistics
$companies_count = $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$admins_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$agents_count = $pdo->query("SELECT COUNT(*) FROM agents")->fetchColumn();
$properties_count = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();

// Fetch all companies with their admins
$companies = $pdo->query("
    SELECT c.*, 
           COUNT(DISTINCT a.id) as agents_count,
           COUNT(DISTINCT p.id) as properties_count,
           GROUP_CONCAT(DISTINCT u.username) as admins
    FROM companies c
    LEFT JOIN agents a ON c.id = a.company_id
    LEFT JOIN properties p ON c.id = p.company_id
    LEFT JOIN users u ON c.id = u.company_id AND u.role = 'admin'
    GROUP BY c.id
    ORDER BY c.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Handle company actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_company') {
        $name = trim($_POST['name'] ?? '');
        $pattern_color = trim($_POST['pattern_color'] ?? '#000000');
        
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO companies (name, pattern_color) VALUES (?, ?)");
            $stmt->execute([$name, $pattern_color]);
            header("Location: dashboard.php");
            exit;
        }
    }
    elseif ($action === 'delete_company') {
        $company_id = $_POST['company_id'] ?? '';
        if (!empty($company_id)) {
            $stmt = $pdo->prepare("DELETE FROM companies WHERE id = ?");
            $stmt->execute([$company_id]);
            header("Location: dashboard.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Super Admin Dashboard - RealEstateMee</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 1200px; 
            margin: 20px auto;
            padding: 0 20px;
            background-color: #f5f5f5;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .stat-card p {
            margin: 0;
            font-size: 24px;
            color: #007bff;
        }
        .companies {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 100px auto;
            padding: 20px;
            width: 400px;
            border-radius: 8px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Super Admin Dashboard</h2>
        <div>
            <button class="btn btn-primary" onclick="showAddCompanyModal()">Add New Company</button>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card">
            <h3>Companies</h3>
            <p><?=htmlspecialchars($companies_count)?></p>
        </div>
        <div class="stat-card">
            <h3>Admins</h3>
            <p><?=htmlspecialchars($admins_count)?></p>
        </div>
        <div class="stat-card">
            <h3>Agents</h3>
            <p><?=htmlspecialchars($agents_count)?></p>
        </div>
        <div class="stat-card">
            <h3>Properties</h3>
            <p><?=htmlspecialchars($properties_count)?></p>
        </div>
    </div>

    <div class="companies">
        <h3>Companies</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Admins</th>
                    <th>Agents</th>
                    <th>Properties</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($companies as $company): ?>
                <tr>
                    <td><?=htmlspecialchars($company['name'])?></td>
                    <td><?=htmlspecialchars($company['admins'] ?: 'None')?></td>
                    <td><?=htmlspecialchars($company['agents_count'])?></td>
                    <td><?=htmlspecialchars($company['properties_count'])?></td>
                    <td><?=htmlspecialchars(date('Y-m-d', strtotime($company['created_at'])))?></td>
                    <td>
                        <a href="manage_company.php?id=<?=htmlspecialchars($company['id'])?>" class="btn btn-primary">Manage</a>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_company">
                            <input type="hidden" name="company_id" value="<?=htmlspecialchars($company['id'])?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this company?')">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Company Modal -->
    <div id="addCompanyModal" class="modal">
        <div class="modal-content">
            <h3>Add New Company</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_company">
                <div class="form-group">
                    <label for="name">Company Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="pattern_color">Pattern Color</label>
                    <input type="color" id="pattern_color" name="pattern_color" value="#000000">
                </div>
                <button type="submit" class="btn btn-primary">Create Company</button>
                <button type="button" class="btn btn-danger" onclick="hideAddCompanyModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function showAddCompanyModal() {
            document.getElementById('addCompanyModal').style.display = 'block';
        }

        function hideAddCompanyModal() {
            document.getElementById('addCompanyModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('addCompanyModal')) {
                hideAddCompanyModal();
            }
        }
    </script>
</body>
</html>
