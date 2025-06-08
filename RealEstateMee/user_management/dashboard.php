<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch user role and company_id
$stmt = $pdo->prepare("SELECT role, company_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

$role = $user['role'];
$company_id = $user['company_id'];

// Fetch properties related to user (as agent or owner)
$properties_stmt = $pdo->prepare("
    SELECT p.*, a.name AS agent_name, o.name AS owner_name
    FROM properties p
    LEFT JOIN agents a ON p.agent_id = a.id
    LEFT JOIN owners o ON p.owner_id = o.id
    WHERE p.agent_id = ? OR p.owner_id = ?
");
$properties_stmt->execute([$user_id, $user_id]);
$properties = $properties_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch commissions earned (sold properties)
$earned_commission_stmt = $pdo->prepare("
    SELECT SUM(c.amount) AS total_earned
    FROM commissions c
    JOIN properties p ON c.property_id = p.id
    WHERE c.agent_id = ? AND c.type = 'earned'
");
$earned_commission_stmt->execute([$user_id]);
$earned_commission = $earned_commission_stmt->fetchColumn() ?? 0;

// Fetch potential commissions (active properties)
$potential_commission_stmt = $pdo->prepare("
    SELECT SUM(p.price * p.commission_percentage / 100) AS total_potential
    FROM properties p
    WHERE p.agent_id = ? AND p.status = 'active'
");
$potential_commission_stmt->execute([$user_id]);
$potential_commission = $potential_commission_stmt->fetchColumn() ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Dashboard - RealEstateMee</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        h2 { margin-bottom: 10px; }
        .commission { font-weight: bold; }
        a { text-decoration: none; color: blue; }
    </style>
</head>
<body>
    <h2>Welcome, <?=htmlspecialchars($username)?></h2>
    <p><a href="change_password.php">Change Password</a> | <a href="logout.php">Logout</a></p>

    <h3>Your Properties</h3>
    <?php if ($properties): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Price</th>
                    <th>Commission %</th>
                    <th>Agent</th>
                    <th>Owner</th>
                    <th>Approved</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($properties as $property): ?>
                    <tr>
                        <td><?=htmlspecialchars($property['id'])?></td>
                        <td><?=htmlspecialchars($property['status'])?></td>
                        <td>$<?=number_format($property['price'], 2)?></td>
                        <td><?=htmlspecialchars($property['commission_percentage'])?>%</td>
                        <td><?=htmlspecialchars($property['agent_name'] ?? 'N/A')?></td>
                        <td><?=htmlspecialchars($property['owner_name'] ?? 'N/A')?></td>
                        <td><?= $property['approved'] ? 'Yes' : 'No' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No properties found.</p>
    <?php endif; ?>

    <h3>Commission Summary</h3>
    <p class="commission">Commission Earned: $<?=number_format($earned_commission, 2)?></p>
    <p class="commission">Potential Commission: $<?=number_format($potential_commission, 2)?></p>
</body>
</html>
