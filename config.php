<?php
$host = "localhost";
$dbname = "sandbox_db";
$user = "postgres";
$password = "1234";

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("No connection with DataBase: " . $e->getMessage());
}
?>