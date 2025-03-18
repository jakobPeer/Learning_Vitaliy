<?php
// Database connection
$conn = new PDO("pgsql:host=localhost;dbname=php_project", "postgres", "1234");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Function to insert or update data
function insertContact($conn, $name, $email, $ip, $message)
{
    try {
        // Check if a user with the given name exists
        $stmt = $conn->prepare("SELECT * FROM contact WHERE name = :name");
        $stmt->execute([':name' => $name]);
        $user = $stmt->fetch();

        if ($user) {
            // If the user exists, update their data (email, IP, message)
            $stmt = $conn->prepare("UPDATE contact SET email = :email, ip = :ip, last_message = :message, last_message_time = NOW() WHERE name = :name");
            $stmt->execute([':name' => $name, ':email' => $email, ':ip' => $ip, ':message' => $message]);
        } else {
            // If the user doesn't exist, insert a new user
            $stmt = $conn->prepare("INSERT INTO contact (name, email, ip, last_message, last_message_time) 
                                    VALUES (:name, :email, :ip, :message, NOW())");
            $stmt->execute([':name' => $name, ':email' => $email, ':ip' => $ip, ':message' => $message]);
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Function to check if an email already exists in the database
function checkEmailExists($conn, $newEmail, $currentName)
{
    try {
        $stmt = $conn->prepare("SELECT * FROM contact WHERE email = :email AND name != :name");
        $stmt->execute([':email' => $newEmail, ':name' => $currentName]);

        // If a user with the given email exists, return true
        $user = $stmt->fetch();
        return $user ? true : false;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Function to delete a user by name
function deleteUser($conn, $name)
{
    try {
        $stmt = $conn->prepare("DELETE FROM contact WHERE name = :name");
        $stmt->execute([':name' => $name]);

        if ($stmt->rowCount() > 0) {
            echo "User deleted successfully.";
        } else {
            echo "User not found or already deleted.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>