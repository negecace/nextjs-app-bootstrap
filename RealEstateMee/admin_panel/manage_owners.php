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
    $owner_id = $_POST['owner_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $contact_info = trim($_POST['contact_info'] ?? '');

    if ($action === 'add') {
        if (empty($name)) {
            $errors[] = "Owner name is required.";
        }
        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO owners (company_id, name, contact_info) VALUES (?, ?, ?)");
            $stmt->execute([$company_id, $name, $contact_info]);
            header("Location: manage_owners.php");
            exit;
        }
    } elseif ($action === 'edit' && $owner_id) {
        if (empty($name)) {
            $errors[] = "Owner name is required.";
        }
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE owners SET name = ?, contact_info = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$name, $contact_info, $owner_id, $company_id]);
            header("Location: manage_owners.php");
            exit;
        }
    } elseif ($action === 'delete' && $owner_id) {
        $stmt = $pdo->prepare("DELETE FROM owners WHERE id = ? AND company_id = ?");
        $stmt->execute([$owner_id, $company_id]);
        header("Location: manage_owners.php");
        exit;
    }
}

// Fetch owners for this company
$stmt = $pdo->prepare("SELECT * FROM owners WHERE company_id = ?");
$stmt->execute([$company_id]);
$owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Owners - RealEstateMee</title>
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
    <h2>Manage Owners</h2>
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

    <h3>Add New Owner</h3>
    <form method="POST" action="manage_owners.php">
        <input type="hidden" name="action" value="add" />
        <input type="text" name="name" placeholder="Owner Name" required />
        <input type="text" name="contact_info" placeholder="Contact Info" />
        <button type="submit">Add Owner</button>
    </form>

    <h3>Existing Owners</h3>
    <?php if ($owners): ?>
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
                <?php foreach ($owners as $owner): ?>
                    <tr>
                        <td><?=htmlspecialchars($owner['id'])?></td>
                        <td><?=htmlspecialchars($owner['name'])?></td>
                        <td><?=htmlspecialchars($owner['contact_info'])?></td>
                        <td>
                            <form method="POST" action="manage_owners.php" style="display:inline;">
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="owner_id" value="<?=htmlspecialchars($owner['id'])?>" />
                                <button type="submit" onclick="return confirm('Delete this owner?');">Delete</button>
                            </form>
                            <button onclick="showEditForm(<?=htmlspecialchars($owner['id'])?>, '<?=htmlspecialchars(addslashes($owner['name']))?>', '<?=htmlspecialchars(addslashes($owner['contact_info']))?>')">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No owners found.</p>
    <?php endif; ?>

    <div id="editFormContainer" style="display:none;">
        <h3>Edit Owner</h3>
        <form method="POST" action="manage_owners.php" id="editForm">
            <input type="hidden" name="action" value="edit" />
            <input type="hidden" name="owner_id" id="edit_owner_id" />
            <input type="text" name="name" id="edit_name" placeholder="Owner Name" required />
            <input type="text" name="contact_info" id="edit_contact_info" placeholder="Contact Info" />
            <button type="submit">Save Changes</button>
            <button type="button" onclick="hideEditForm()">Cancel</button>
        </form>
    </div>

    <script>
        function showEditForm(id, name, contact) {
            document.getElementById('edit_owner_id').value = id;
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
