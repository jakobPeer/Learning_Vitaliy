<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $amount = intval($_POST['amount']);
    $action = $_POST['action'];

    // recieve user balans
    $stmt = $pdo->prepare("SELECT coins FROM users WHERE userid = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        die("Error: User not found.");
    }

    $current_balance = $user['coins'];

    // check if user balans is -
    if (($current_balance + $amount) < 0) {
        die("Error: Balance cannot be negative!");
    }

    // check type of transaction
    $transaction_type = ($amount > 0) ? "deposit" : "withdraw";

    try {
        // update balans of user
        $stmt = $pdo->prepare("UPDATE users SET coins = coins + :amount WHERE userid = :user_id");
        $stmt->execute(['amount' => $amount, 'user_id' => $user_id]);

        // add notice to user transaction history
        $stmt = $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, transaction_type, created_at) 
                               VALUES (:user_id, :amount, :type, NOW())");
        $stmt->execute(['user_id' => $user_id, 'amount' => $amount, 'type' => $transaction_type]);

        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}
?>