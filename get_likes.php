<?php
include 'config.php';

$stmt = $pdo->query("
    SELECT comment_id, COUNT(*) AS like_count 
    FROM comment_likes 
    GROUP BY comment_id
");
$likes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($likes, JSON_PRETTY_PRINT);
?>