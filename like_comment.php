<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $comment_id = $_POST['comment_id'];
    $user_id = $_POST['user_id'];

    // Check if users put the like before
    $stmt = $pdo->prepare("SELECT * FROM comment_likes WHERE comment_id = :comment_id AND user_id = :user_id");
    $stmt->execute(['comment_id' => $comment_id, 'user_id' => $user_id]);
    $like = $stmt->fetch();

    if ($like) {
        // If like already exist - delete him and (analog dislike)
        $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = :comment_id AND user_id = :user_id");
        $stmt->execute(['comment_id' => $comment_id, 'user_id' => $user_id]);
        echo json_encode(["status" => "unliked"]);
    } else {
        // if no like - we will add him
        $stmt = $pdo->prepare("INSERT INTO comment_likes (id, comment_id, user_id, created_at) VALUES (gen_random_uuid(), :comment_id, :user_id, NOW())");
        $stmt->execute(['comment_id' => $comment_id, 'user_id' => $user_id]);
        echo json_encode(["status" => "liked"]);
    }
}
?>