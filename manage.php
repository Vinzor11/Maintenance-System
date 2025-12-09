<?php

// manage.php (admin only)
session_start(); require 'db.php';
if ($_SESSION['role'] !== 'admin') exit("Access denied.");
$rid = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE id=?");
$stmt->execute([$rid]); $req = $stmt->fetch();
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $status = $_POST['status'];
    $comment = $_POST['comment'];
    $pdo->prepare("UPDATE maintenance_requests SET status=? WHERE id=?")->execute([$status, $rid]);
    if (!empty($comment)) {
        $pdo->prepare("INSERT INTO comments (request_id,user_id,comment) VALUES (?,?,?)")
            ->execute([$rid, $_SESSION['userid'], $comment]);
    }
    // Notify user
    $stmtU = $pdo->prepare("SELECT email FROM users WHERE id=?");
    $stmtU->execute([$req['user_id']]); $email = $stmtU->fetchColumn();
    sendMail($email, "Update on your request", "Status: $status. Comment: $comment");
    // Audit log
    $pdo->prepare("INSERT INTO audit_logs(action,user_id,request_id,details) VALUES ('Status Updated',?,?,?)")
        ->execute([$_SESSION['userid'], $rid, "Status: $status, Comment: $comment"]);
    header("Location: dashboard.php"); exit;
}
?>
<form method="POST">
Current Status: <?= $req['status'] ?><br>
<select name="status">
  <option value="Submitted">Submitted</option>
  <option value="In Progress">In Progress</option>
  <option value="Completed">Completed</option>
  <option value="Rejected">Rejected</option>
</select><br>
<textarea name="comment" placeholder="Admin comment"></textarea><br>
<button type="submit">Submit</button>
</form>
