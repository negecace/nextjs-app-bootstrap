<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Fetch company_id for this admin
$stmt = $pdo->prepare("SELECT company_id FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$company_id = $stmt->fetchColumn();

if (!$company_id) {
    die("Company not found.");
}

// Handle form submissions for add, edit, delete
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $agent_id = $_POST['agent_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $contact_info = trim($_POST['contact_info'] ?? '');

    if ($action === 'add') {
        if (empty($name)) {
            $errors[] = "Agent name is required.";
        }
        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO agents (company_id, name, contact_info) VALUES (?, ?, ?)");
            $stmt->execute([$company_id, $name, $contact_info]);
            header("Location: manage_agents.php");
            exit;
        }
    } elseif ($action === 'edit' && $agent_id) {
        if (empty($name)) {
            $errors[] = "Agent name is required.";
        }
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE agents SET name = ?, contact_info = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$name, $contact_info, $agent_id, $company_id]);
            header("Location: manage_agents.php");
            exit;
        }
    } elseif ($action === 'delete' && $agent_id) {
        $stmt = $pdo->prepare("DELETE FROM agents WHERE id = ? AND company_id = ?");
        $stmt->execute([$agent_id, $company_id]);
        header("Location: manage_agents.php");
        exit;
    }
}

// Fetch agents for this company
$stmt = $pdo->prepare("SELECT * FROM agents WHERE company_id = ?");
$stmt->execute([$company_id]);
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Agents - RealEstateMee</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        form { margin-bottom: 20px; }
        input[type="text"] { padding: 6px; width: 300px; margin-right: 10px; }
        button { padding: 6px 12px; }
        .error { color: red; }
        a { text-decoration: none; color: blue; }
    </style>
</head>
<body>
    <h2>Manage Agents</h2>
    <p><a href="dashboard.php">Back to Dashboard</a> | <a href="logout.php">Logout</a></p>

    <?php if ($errors): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?=htmlspecialchars($error)?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <h3>Add New Agent</h3>
    <form method="POST" action="manage_agents.php">
        <input type="hidden" name="action" value="add" />
        <input type="text" name="name" placeholder="Agent Name" required />
        <input type="text" name="contact_info" placeholder="Contact Info" />
        <button type="submit">Add Agent</button>
    </form>

    <h3>Existing Agents</h3>
    <?php if ($agents): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Contact Info</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agents as $agent): ?>
                    <tr>
                        <td><?=htmlspecialchars($agent['id'])?></td>
                        <td><?=htmlspecialchars($agent['name'])?></td>
                        <td><?=htmlspecialchars($agent['contact_info'])?></td>
                        <td>
                            <form method="POST" action="manage_agents.php" style="display:inline;">
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="agent_id" value="<?=htmlspecialchars($agent['id'])?>" />
                                <button type="submit" onclick="return confirm('Delete this agent?');">Delete</button>
                            </form>
                            <button onclick="showEditForm(<?=htmlspecialchars($agent['id'])?>, '<?=htmlspecialchars(addslashes($agent['name']))?>', '<?=htmlspecialchars(addslashes($agent['contact_info']))?>')">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No agents found.</p>
    <?php endif; ?>

    <div id="editFormContainer" style="display:none;">
        <h3>Edit Agent</h3>
        <form method="POST" action="manage_agents.php" id="editForm">
            <input type="hidden" name="action" value="edit" />
            <input type="hidden" name="agent_id" id="edit_agent_id" />
            <input type="text" name="name" id="edit_name" placeholder="Agent Name" required />
            <input type="text" name="contact_info" id="edit_contact_info" placeholder="Contact Info" />
            <button type="submit">Save Changes</button>
            <button type="button" onclick="hideEditForm()">Cancel</button>
        </form>
    </div>

    <script>
        function showEditForm(id, name, contact) {
            document.getElementById('edit_agent_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_contact_info').value = contact;
            document.getElementById('editFormContainer').style.display = 'block';
            window.scrollTo(0, document.body.scrollHeight);
        }
        function hideEditForm() {
            document.getElementById('editFormContainer').style.display = 'none';
        }
    </script>
</body>
</html>
