<?php
include 'config.php';

function toggleUserBlock($pdo, string $blockerid, string $blockedid): array
{
    try {
        if (empty($blockerid) || empty($blockedid)) {
            return ['status' => 'error', 'ResponseCode' => 'Invalid user IDs'];
        }

        $pdo->beginTransaction();

        // Проверяем, существует ли уже блокировка
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_block_user WHERE blockerid = :blockerid AND blockedid = :blockedid");
        $stmt->execute(['blockerid' => $blockerid, 'blockedid' => $blockedid]);

        if ($stmt->fetchColumn() > 0) {
            // Удаляем блокировку
            $stmt = $pdo->prepare("DELETE FROM user_block_user WHERE blockerid = :blockerid AND blockedid = :blockedid");
            $stmt->execute(['blockerid' => $blockerid, 'blockedid' => $blockedid]);
            $response = 'User unblocked successfully.';
            $action = false;
        } else {
            // Добавляем блокировку
            $stmt = $pdo->prepare("INSERT INTO user_block_user (blockerid, blockedid) VALUES (:blockerid, :blockedid)");
            $stmt->execute(['blockerid' => $blockerid, 'blockedid' => $blockedid]);
            $response = 'User blocked successfully.';
            $action = true;
        }

        $pdo->commit();
        return ['status' => 'success', 'ResponseCode' => $response, 'isBlocked' => $action];

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Block user error: " . $e->getMessage()); // Логируем ошибки
        return ['status' => 'error', 'ResponseCode' => 'Failed to toggle user block'];
    }
}

// Получение параметров из POST-запроса
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $blockerid = $_POST['blockerid'] ?? null;
    $blockedid = $_POST['blockedid'] ?? null;

    if (!$blockerid || !$blockedid) {
        echo json_encode(['status' => 'error', 'ResponseCode' => 'Invalid user IDs']);
        exit;
    }

    $result = toggleUserBlock($pdo, $blockerid, $blockedid);
    echo json_encode($result);
}
?>