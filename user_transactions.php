<?php
include 'config.php';

$user_id = $_GET['user_id'] ?? null;
if (!$user_id) {
    die("User ID is required.");
}

$stmt = $pdo->prepare("SELECT * FROM coin_transactions WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute(['user_id' => $user_id]);
$transactions = $stmt->fetchAll();

if (empty($transactions)) {
    echo "<p>No transactions found for this user.</p>";
} else {
    echo "<table>
            <tr>
                <th>Transaction ID</th>
                <th>Amount</th>
                <th>Type</th>
                <th>Date</th>
            </tr>";
    foreach ($transactions as $tx) {
        echo "<tr>
                <td>{$tx['transaction_id']}</td>
                <td>{$tx['amount']}</td>
                <td>{$tx['transaction_type']}</td>
                <td>{$tx['created_at']}</td>
              </tr>";
    }
    echo "</table>";
}
?>