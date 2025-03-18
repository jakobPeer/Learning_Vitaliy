<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $ip = $_POST['ip'];
    $coins = intval($_POST['coins']);

    // do uniq usertag user_tag
    function generateUniqueTag($pdo)
    {
        do {
            $tag = 'user_' . bin2hex(random_bytes(4)); // do unperdictable user_tag
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_tag = :tag");
            $stmt->execute(['tag' => $tag]);
            $count = $stmt->fetchColumn();
        } while ($count > 0); // if tag is exist, do new
        return $tag;
    }

    $user_tag = generateUniqueTag($pdo);

    try {
        // check, if user already has this email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE useremail = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            die("Error: User with this email already exists.");
        }

        // create a new user
        $stmt = $pdo->prepare("INSERT INTO users (username, useremail, createdat, coins, user_tag) 
                               VALUES (:username, :email, NOW(), :coins, :user_tag)");
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'coins' => $coins,
            'user_tag' => $user_tag
        ]);

        // recieve ID new user
        $user_id = $pdo->lastInsertId();

        // if money not 0, create a transaction
        if ($coins != 0) {
            $stmt = $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, transaction_type, created_at) 
                                   VALUES (:user_id, :amount, 'deposit', NOW())");
            $stmt->execute(['user_id' => $user_id, 'amount' => $coins]);
        }

        echo "User successfully created!";
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}
?>