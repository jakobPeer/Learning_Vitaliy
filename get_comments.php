<?php
include 'config.php';

if (!isset($_GET['user_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'User ID not provided']));
}

$user_id = $_GET['user_id'];

$stmt = $pdo->prepare("SELECT * FROM user_comments WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute(['user_id' => $user_id]);

$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($comments)) {
    echo "<p>No comments yet.</p>";
} else {
    echo "<table><tr><th>Comment</th><th>Date</th></tr>";
    foreach ($comments as $comment) {
        echo "<tr><td>" . htmlspecialchars($comment['comment_text']) . "</td><td>" . $comment['created_at'] . "</td></tr>";
    }
    echo "</table>";
}
?>