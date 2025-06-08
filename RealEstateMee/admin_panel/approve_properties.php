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

// Handle approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $property_id = $_POST['property_id'] ?? null;
    $action = $_POST['action'] ?? '';

    if ($property_id && in_array($action, ['approve', 'reject'])) {
        $approved = ($action === 'approve') ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE properties SET approved = ? WHERE id = ? AND company_id = ?");
        $stmt->execute([$approved, $property_id, $company_id]);
        header("Location: approve_properties.php");
        exit;
    }
}

// Fetch properties pending approval
$stmt = $pdo->prepare("
    SELECT p.*, a.name AS agent_name, o.name AS owner_name
    FROM properties p
    LEFT JOIN agents a ON p.agent_id = a.id
    LEFT JOIN owners o ON p.owner_id = o.id
    WHERE p.company_id = ? AND p.approved = 0
");
$stmt->execute([$company_id]);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Approve Properties - RealEstateMee</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        button { padding: 6px 12px; margin-right: 5px; }
        a { text-decoration: none; color: blue; }
    </style>
</head>
<body>
    <h2>Approve Properties</h2>
    <p><a href="dashboard.php">Back to Dashboard</a> | <a href="logout.php">Logout</a></p>

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
                        <td>
                            <form method="POST" action="approve_properties.php" style="display:inline;">
                                <input type="hidden" name="property_id" value="<?=htmlspecialchars($property['id'])?>" />
                                <button type="submit" name="action" value="approve">Approve</button>
                                <button type="submit" name="action" value="reject">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No properties pending approval.</p>
    <?php endif; ?>
</body>
</html>
