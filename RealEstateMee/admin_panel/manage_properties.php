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

// Fetch agents and owners for dropdowns
$agents_stmt = $pdo->prepare("SELECT id, name FROM agents WHERE company_id = ?");
$agents_stmt->execute([$company_id]);
$agents = $agents_stmt->fetchAll(PDO::FETCH_ASSOC);

$owners_stmt = $pdo->prepare("SELECT id, name FROM owners WHERE company_id = ?");
$owners_stmt->execute([$company_id]);
$owners = $owners_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions for add, edit, delete
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $property_id = $_POST['property_id'] ?? null;
    $agent_id = $_POST['agent_id'] ?? null;
    $owner_id = $_POST['owner_id'] ?? null;
    $status = $_POST['status'] ?? 'active';
    $commission_percentage = $_POST['commission_percentage'] ?? 0;
    $price = $_POST['price'] ?? 0;

    if ($action === 'add') {
        if (empty($price) || !is_numeric($price)) {
            $errors[] = "Valid price is required.";
        }
        if (!is_numeric($commission_percentage) || $commission_percentage < 0) {
            $errors[] = "Valid commission percentage is required.";
        }
        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO properties (company_id, agent_id, owner_id, status, commission_percentage, price, approved) VALUES (?, ?, ?, ?, ?, ?, 0)");
            $stmt->execute([$company_id, $agent_id ?: null, $owner_id ?: null, $status, $commission_percentage, $price]);
            header("Location: manage_properties.php");
            exit;
        }
    } elseif ($action === 'edit' && $property_id) {
        if (empty($price) || !is_numeric($price)) {
            $errors[] = "Valid price is required.";
        }
        if (!is_numeric($commission_percentage) || $commission_percentage < 0) {
            $errors[] = "Valid commission percentage is required.";
        }
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE properties SET agent_id = ?, owner_id = ?, status = ?, commission_percentage = ?, price = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$agent_id ?: null, $owner_id ?: null, $status, $commission_percentage, $price, $property_id, $company_id]);
            header("Location: manage_properties.php");
            exit;
        }
    } elseif ($action === 'delete' && $property_id) {
        $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ? AND company_id = ?");
        $stmt->execute([$property_id, $company_id]);
        header("Location: manage_properties.php");
        exit;
    }
}

// Fetch properties for this company
$stmt = $pdo->prepare("
    SELECT p.*, a.name AS agent_name, o.name AS owner_name
    FROM properties p
    LEFT JOIN agents a ON p.agent_id = a.id
    LEFT JOIN owners o ON p.owner_id = o.id
    WHERE p.company_id = ?
");
$stmt->execute([$company_id]);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Properties - RealEstateMee</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        form { margin-bottom: 20px; }
        input[type="text"], select { padding: 6px; width: 200px; margin-right: 10px; }
        button { padding: 6px 12px; }
        .error { color: red; }
        a { text-decoration: none; color: blue; }
    </style>
</head>
<body>
    <h2>Manage Properties</h2>
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

    <h3>Add New Property</h3>
    <form method="POST" action="manage_properties.php">
        <input type="hidden" name="action" value="add" />
        <select name="agent_id">
            <option value="">Select Agent</option>
            <?php foreach ($agents as $agent): ?>
                <option value="<?=htmlspecialchars($agent['id'])?>"><?=htmlspecialchars($agent['name'])?></option>
            <?php endforeach; ?>
        </select>
        <select name="owner_id">
            <option value="">Select Owner</option>
            <?php foreach ($owners as $owner): ?>
                <option value="<?=htmlspecialchars($owner['id'])?>"><?=htmlspecialchars($owner['name'])?></option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="active">Active</option>
            <option value="sold">Sold</option>
            <option value="rented">Rented</option>
        </select>
        <input type="text" name="commission_percentage" placeholder="Commission %" />
        <input type="text" name="price" placeholder="Price" required />
        <button type="submit">Add Property</button>
    </form>

    <h3>Existing Properties</h3>
    <?php if ($properties): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Agent</th>
                    <th>Owner</th>
                    <th>Status</th>
                    <th>Commission %</th>
                    <th>Price</th>
                    <th>Approved</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($properties as $property): ?>
                    <tr>
                        <td><?=htmlspecialchars($property['id'])?></td>
                        <td><?=htmlspecialchars($property['agent_name'] ?? 'N/A')?></td>
                        <td><?=htmlspecialchars($property['owner_name'] ?? 'N/A')?></td>
                        <td><?=htmlspecialchars($property['status'])?></td>
                        <td><?=htmlspecialchars($property['commission_percentage'])?>%</td>
                        <td>$<?=number_format($property['price'], 2)?></td>
                        <td><?= $property['approved'] ? 'Yes' : 'No' ?></td>
                        <td>
                            <form method="POST" action="manage_properties.php" style="display:inline;">
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="property_id" value="<?=htmlspecialchars($property['id'])?>" />
                                <button type="submit" onclick="return confirm('Delete this property?');">Delete</button>
                            </form>
                            <button onclick="showEditForm(
                                <?=htmlspecialchars($property['id'])?>,
                                '<?=htmlspecialchars(addslashes($property['agent_id'] ?? ''))?>',
                                '<?=htmlspecialchars(addslashes($property['owner_id'] ?? ''))?>',
                                '<?=htmlspecialchars(addslashes($property['status']))?>',
                                '<?=htmlspecialchars(addslashes($property['commission_percentage']))?>',
                                '<?=htmlspecialchars(addslashes($property['price']))?>'
                            )">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No properties found.</p>
    <?php endif; ?>

    <div id="editFormContainer" style="display:none;">
        <h3>Edit Property</h3>
        <form method="POST" action="manage_properties.php" id="editForm">
            <input type="hidden" name="action" value="edit" />
            <input type="hidden" name="property_id" id="edit_property_id" />
            <select name="agent_id" id="edit_agent_id">
                <option value="">Select Agent</option>
                <?php foreach ($agents as $agent): ?>
                    <option value="<?=htmlspecialchars($agent['id'])?>"><?=htmlspecialchars($agent['name'])?></option>
                <?php endforeach; ?>
            </select>
            <select name="owner_id" id="edit_owner_id">
                <option value="">Select Owner</option>
                <?php foreach ($owners as $owner): ?>
                    <option value="<?=htmlspecialchars($owner['id'])?>"><?=htmlspecialchars($owner['name'])?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" id="edit_status">
                <option value="active">Active</option>
                <option value="sold">Sold</option>
                <option value="rented">Rented</option>
            </select>
            <input type="text" name="commission_percentage" id="edit_commission_percentage" placeholder="Commission %" />
            <input type="text" name="price" id="edit_price" placeholder="Price" required />
            <button type="submit">Save Changes</button>
            <button type="button" onclick="hideEditForm()">Cancel</button>
        </form>
    </div>

    <script>
        function showEditForm(id, agentId, ownerId, status, commission, price) {
            document.getElementById('edit_property_id').value = id;
            document.getElementById('edit_agent_id').value = agentId;
            document.getElementById('edit_owner_id').value = ownerId;
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_commission_percentage').value = commission;
            document.getElementById('edit_price').value = price;
            document.getElementById('editFormContainer').style.display = 'block';
            window.scrollTo(0, document.body.scrollHeight);
        }
        function hideEditForm() {
            document.getElementById('editFormContainer').style.display = 'none';
        }
    </script>
</body>
</html>
