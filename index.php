<?php
include 'config.php';

// === Delete user ===
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    // Deleting user transactions (if you have cascading deletion, you can skip this step)
    $stmt = $pdo->prepare("DELETE FROM coin_transactions WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);

    // Deleting the user's tokens
    $stmt = $pdo->prepare("DELETE FROM user_tokens WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);

    // Deleting the user himself
    $stmt = $pdo->prepare("DELETE FROM users WHERE userid = :user_id");
    $stmt->execute(['user_id' => $user_id]);

    header("Location: index.php");
    exit();
}

// ===Token Management (Activation/Deactivation) ===
if (isset($_POST['toggle_token'])) {
    $user_id = $_POST['user_id'];

    // Checking the current status of the token
    $stmt = $pdo->prepare("SELECT access_token FROM user_tokens WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $userToken = $stmt->fetch();

    if ($userToken && $userToken['access_token']) {

        // Deactivating the token, but putting empty lines or default values in NOT NULL rows
        $stmt = $pdo->prepare("UPDATE user_tokens SET 
access_token = NULL, 
refresh_token = '', 
access_expires_at = NULL, 
refresh_expires_at = '1970-01-01 00:00:00' 
WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);


    } else {
        // Creating a new token
        $new_access_token = bin2hex(random_bytes(32));
        $new_refresh_token = bin2hex(random_bytes(32));
        $access_expires = gmdate('Y-m-d H:i:s', strtotime('+45 minutes'));
        $refresh_expires = gmdate('Y-m-d H:i:s', strtotime('+7 days'));

        $stmt = $pdo->prepare("INSERT INTO user_tokens (user_id, access_token, refresh_token, access_expires_at, refresh_expires_at)
                               VALUES (:user_id, :token, :refresh, :expires, :refresh_expires)
                               ON CONFLICT (user_id) DO UPDATE 
                               SET access_token = EXCLUDED.access_token, 
                                   refresh_token = EXCLUDED.refresh_token, 
                                   access_expires_at = EXCLUDED.access_expires_at, 
                                   refresh_expires_at = EXCLUDED.refresh_expires_at");
        $stmt->execute([
            'user_id' => $user_id,
            'token' => $new_access_token,
            'refresh' => $new_refresh_token,
            'expires' => $access_expires,
            'refresh_expires' => $refresh_expires
        ]);
    }

    // Updating MD5 Hash


    header("Location: index.php");
    exit();
}

// === Filtering users ===
$searchQuery = "";
$params = [];

if (!empty($_GET['search_id'])) {
    $searchQuery .= " AND users.userid = :search_id";
    $params['search_id'] = $_GET['search_id'];
}

if (!empty($_GET['search_email'])) {
    $searchQuery .= " AND users.useremail LIKE :search_email";
    $params['search_email'] = "%" . $_GET['search_email'] . "%";
}

// === Getting a list of users ===
$query = "SELECT 
                users.userid, 
                users.username, 
                users.useremail, 
                users.user_hash, 
                users.coins,
                COALESCE(SUM(coin_transactions.amount), 0) AS balance,
                user_tokens.access_token
          FROM users
          LEFT JOIN user_tokens ON users.userid = user_tokens.user_id
          LEFT JOIN coin_transactions ON users.userid = coin_transactions.user_id
          WHERE 1=1 $searchQuery
          GROUP BY users.userid, users.username, users.useremail, users.user_hash, users.coins, user_tokens.access_token 
          ORDER BY users.userid";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        tr:hover {
            background-color: #f1f1f1;
            cursor: pointer;
        }

        .actions {
            display: flex;
            gap: 5px;
        }
    </style>
    <script>
        function loadTransactions(userId) {
            fetch("user_transactions.php?user_id=" + userId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById("transactionHistory").innerHTML = data;
                });

            fetch("get_comments.php?user_id=" + userId) // ✅ Исправлено!
                .then(response => response.text())
                .then(data => {
                    document.getElementById("userComments").innerHTML = data;
                });
        }


    </script>
</head>

<body>


    <h2>Create New User</h2>
    <form action="add_user.php" method="POST">
        <label for="username">Username:</label>
        <input type="text" name="username" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required
            value="<?= isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '' ?>"
            style="<?= isset($_GET['error']) && $_GET['error'] == 'email_exists' ? 'border: 2px solid red;' : '' ?>">

        <!-- Сообщение об ошибке -->
        <?php if (isset($_GET['error']) && $_GET['error'] == 'email_exists'): ?>
            <p style="color: red;">User with this email already exists.</p>
        <?php endif; ?>

        <label for="ip">IP Address:</label>
        <input type="text" name="ip" required>

        <label for="coins">Initial Coins:</label>
        <input type="number" name="coins" value="0">

        <button type="submit">Add User</button>
    </form>

    <h2>Search User</h2>
    <form method="GET">
        <label for="search_id">User ID:</label>
        <input type="number" name="search_id"
            value="<?= isset($_GET['search_id']) ? htmlspecialchars($_GET['search_id']) : '' ?>">

        <label for="search_email">Email:</label>
        <input type="email" name="search_email"
            value="<?= isset($_GET['search_email']) ? htmlspecialchars($_GET['search_email']) : '' ?>">

        <button type="submit">Search</button>
        <a href="index.php"><button type="button">Reset</button></a>
    </form>

    <h2>User Management</h2>

    <?php
    // Функция для проверки, заблокирован ли пользователь
    function isUserBlocked($user_id)
    {
        global $pdo;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_block_user WHERE blockedid = :user_id");
        $stmt->execute(['user_id' => $user_id]);

        return $stmt->fetchColumn() > 0;
    }
    ?>

    <?php if (empty($users)): ?>
        <p>No users found.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>MD5 Hash</th>
                <th>Balance</th>
                <th>Access Token</th>
                <th>Blocked Status</th> <!-- Новый столбец -->
                <th>Actions</th>
            </tr>

            <?php foreach ($users as $user): ?>
                <tr onclick="loadTransactions(<?= $user['userid'] ?>); loadComments(<?= $user['userid'] ?>);">
                    <td><?= $user['userid'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['useremail']) ?></td>
                    <td><?= htmlspecialchars($user['user_hash']) ?></td>
                    <td><?= $user['balance'] ?> coins</td>
                    <td><?= $user['access_token'] ? 'Active' : 'Inactive' ?></td>

                    <!-- Статус блокировки -->
                    <td>
                        <?= isUserBlocked($user['userid'])
                            ? '<span style="color:red;">🔴 Blocked</span>'
                            : '<span style="color:green;">🟢 Not Blocked</span>' ?>
                    </td>

                    <td class="actions">
                        <form method="POST" action="index.php">
                            <input type="hidden" name="user_id" value="<?= $user['userid'] ?>">
                            <button type="submit" name="toggle_token">
                                <?= $user['access_token'] ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>

                        <form method="POST" action="index.php"
                            onsubmit="return confirm('Вы уверены, что хотите удалить этого пользователя?');">
                            <input type="hidden" name="user_id" value="<?= $user['userid'] ?>">
                            <button type="submit" name="delete_user"
                                style="background-color: red; color: white; border: none; padding: 5px 10px; cursor: pointer;">
                                ❌ Delete
                            </button>
                        </form>

                        <form method="POST" action="update_balance.php" onsubmit="return adjustBalance(this);">
                            <input type="hidden" name="user_id" value="<?= $user['userid'] ?>">
                            <input type="number" name="amount" id="amount_<?= $user['userid'] ?>" placeholder="Coins" required>
                            <button type="submit" name="action" value="add" onclick="setBalanceAction(this, '+')">➕</button>
                            <button type="submit" name="action" value="subtract"
                                onclick="setBalanceAction(this, '-')">➖</button>
                        </form>


                        <!-- Кнопки "Block" и "Unblock" -->
                        <button onclick="toggleBlock(<?= $user['userid'] ?>)">🔒 Block</button>
                        <button onclick="toggleUnblock(<?= $user['userid'] ?>)">🔓 Unblock</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <script>
        function toggleBlock(blockedid) {
            let blockerid = 5; // ID администратора (можно получать из сессии)

            fetch('block_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `blockerid=${blockerid}&blockedid=${blockedid}`
            })
                .then(response => response.json())
                .then(data => {
                    alert(data.ResponseCode);
                    if (data.status === 'success') location.reload();
                })
                .catch(error => console.error('Error:', error));
        }

        function toggleUnblock(blockedid) {
            let blockerid = 5; // ID администратора (можно получать из сессии)

            fetch('block_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `blockerid=${blockerid}&blockedid=${blockedid}`
            })
                .then(response => response.json())
                .then(data => {
                    alert(data.ResponseCode);
                    if (data.status === 'success') location.reload();
                })
                .catch(error => console.error('Error:', error));
        }
    </script>






    <h2>Transaction History</h2>
    <div id="transactionHistory">
        <p>Select a user to view transactions.</p>
    </div>
    <?php

    // We receive comments for the selected user
    $user_comments = [];
    if (!empty($_GET['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM user_comments WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $_GET['user_id']]);
        $user_comments = $stmt->fetchAll();
    }


    // Displaying comments
    if (!empty($user_comments)) {
        echo "<h3>User Comments</h3>";
        echo "<ul>";
        foreach ($user_comments as $comment) {
            echo "<li><strong>{$comment['created_at']}:</strong> " . htmlspecialchars($comment['content']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No comments found for this user.</p>";
    }
    ?>



    <h2>User Comments</h2>
    <?php if (!empty($user_comments)): ?>
        <table>
            <tr>
                <th>Comment</th>
                <th>Date</th>
            </tr>
            <?php foreach ($user_comments as $comment): ?>
                <tr>
                    <td><?= htmlspecialchars($comment['content']) ?></td>
                    <td><?= $comment['created_at'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No comments yet.</p>
    <?php endif; ?>

    <!-- Form for adding comments -->
    <form action="add_comment.php" method="POST">
        <input type="hidden" name="user_id"
            value="<?= isset($_GET['user_id']) ? htmlspecialchars($_GET['user_id']) : '' ?>">
        <textarea name="content" placeholder="Add a comment..." required></textarea>
        <button type="submit">➕ Add Comment</button>
    </form>

    <script>
        function toggleBlock(blockedid) {
            const blockerid = prompt("Enter your user ID to block this user:");
            if (!blockerid) return;

            fetch('block_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `blockerid=${blockerid}&blockedid=${blockedid}`
            })
                .then(response => response.json())
                .then(data => {
                    alert(data.ResponseCode);
                    location.reload(); // Обновление страницы
                })
                .catch(error => console.error('Error:', error));
        }

        function toggleUnblock(blockedid) {
            const blockerid = prompt("Enter your user ID to unblock this user:");
            if (!blockerid) return;

            fetch('block_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `blockerid=${blockerid}&blockedid=${blockedid}`
            })
                .then(response => response.json())
                .then(data => {
                    alert(data.ResponseCode);
                    location.reload(); // Обновление страницы
                })
                .catch(error => console.error('Error:', error));
        }
    </script>

    <script>
        function setBalanceAction(button, action) {
            let form = button.closest("form");
            let input = form.querySelector("input[name='amount']");
            let amount = parseFloat(input.value);

            if (isNaN(amount) || amount <= 0) {
                alert("Введите корректную сумму!");
                return false;
            }

            if (action === "-") {
                input.value = -Math.abs(amount); // Принудительно делаем число отрицательным
            } else {
                input.value = Math.abs(amount); // Принудительно делаем число положительным
            }
        }
    </script>



</body>

</html>