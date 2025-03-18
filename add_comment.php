<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['user_id']) || !isset($_POST['comment_text'])) {
        die(json_encode(['status' => 'error', 'message' => 'Invalid data']));
    }

    $user_id = $_POST['user_id'];
    $comment_text = trim($_POST['comment_text']);

    if (empty($comment_text)) {
        die(json_encode(['status' => 'error', 'message' => 'Comment cannot be empty']));
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO user_comments (user_id, comment_text) VALUES (:user_id, :comment_text)");
        $stmt->execute(['user_id' => $user_id, 'comment_text' => $comment_text]);

        header("Location: index.php?user_id=" . $user_id);
        exit;
    } catch (Exception $e) {
        die(json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]));
    }
}
?>