<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];

// Fetch company_id for this admin
$stmt = $pdo->prepare("SELECT company_id FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$company_id = $stmt->fetchColumn();

if (!$company_id) {
    die("Company not found.");
}

// Fetch counts and summaries
$agents_count = $pdo->prepare("SELECT COUNT(*) FROM agents WHERE company_id = ?");
$agents_count->execute([$company_id]);
$agents_count = $agents_count->fetchColumn();

$owners_count = $pdo->prepare("SELECT COUNT(*) FROM owners WHERE company_id = ?");
$owners_count->execute([$company_id]);
$owners_count = $owners_count->fetchColumn();

$properties_count = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE company_id = ?");
$properties_count->execute([$company_id]);
$properties_count = $properties_count->fetchColumn();

$active_properties_count = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE company_id = ? AND status = 'active'");
$active_properties_count->execute([$company_id]);
$active_properties_count = $active_properties_count->fetchColumn();

$sold_properties_count = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE company_id = ? AND status = 'sold'");
$sold_properties_count->execute([$company_id]);
$sold_properties_count = $sold_properties_count->fetchColumn();

$rented_properties_count = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE company_id = ? AND status = 'rented'");
$rented_properties_count->execute([$company_id]);
$rented_properties_count = $rented_properties_count->fetchColumn();

// Commission earned from previous sales
$commission_earned_stmt = $pdo->prepare("
    SELECT SUM(c.amount) FROM commissions c
    JOIN properties p ON c.property_id = p.id
    WHERE p.company_id = ? AND c.type = 'earned'
");
$commission_earned_stmt->execute([$company_id]);
$commission_earned = $commission_earned_stmt->fetchColumn() ?? 0;

// Potential commission based on active properties
$potential_commission_stmt = $pdo->prepare("
    SELECT SUM(p.price * p.commission_percentage / 100) FROM properties p
    WHERE p.company_id = ? AND p.status = 'active'
");
$potential_commission_stmt->execute([$company_id]);
$potential_commission = $potential_commission_stmt->fetchColumn() ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard - RealEstateMee</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; }
        h2, h3 { margin-bottom: 10px; }
        .summary { display: flex; gap: 20px; margin-bottom: 20px; }
        .card { border: 1px solid #ccc; padding: 15px; flex: 1; border-radius: 5px; background: #f9f9f9; }
        a { text-decoration: none; color: blue; }
        nav a { margin-right: 15px; }
    </style>
</head>
<body>
    <h2>Admin Dashboard</h2>
    <p>Welcome, <?=htmlspecialchars($admin_username)?> | <a href="logout.php">Logout</a></p>

    <nav>
        <a href="manage_agents.php">Manage Agents</a>
        <a href="manage_owners.php">Manage Owners</a>
        <a href="manage_properties.php">Manage Properties</a>
        <a href="approve_properties.php">Approve Properties</a>
    </nav>

    <div class="summary">
        <div class="card">
            <h3>Agents</h3>
            <p><?=htmlspecialchars($agents_count)?></p>
        </div>
        <div class="card">
            <h3>Owners</h3>
            <p><?=htmlspecialchars($owners_count)?></p>
        </div>
        <div class="card">
            <h3>Properties</h3>
            <p>Total: <?=htmlspecialchars($properties_count)?></p>
            <p>Active: <?=htmlspecialchars($active_properties_count)?></p>
            <p>Sold: <?=htmlspecialchars($sold_properties_count)?></p>
            <p>Rented: <?=htmlspecialchars($rented_properties_count)?></p>
        </div>
        <div class="card">
            <h3>Commissions</h3>
            <p>Earned: $<?=number_format($commission_earned, 2)?></p>
            <p>Potential: $<?=number_format($potential_commission, 2)?></p>
        </div>
    </div>
</body>
</html>
