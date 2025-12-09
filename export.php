<?php

session_start(); require 'db.php';
if ($_SESSION['role'] !== 'admin') exit("Access denied.");
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=requests.csv');
$stmt = $pdo->query("SELECT * FROM maintenance_requests");
echo "ID,Title,System,Status,User,Created\n";
while ($row = $stmt->fetch()) {
  echo "{$row['id']},\"{$row['title']}\",{$row['system_type']},{$row['status']},{$row['user_id']},{$row['created_at']}\n";
}
