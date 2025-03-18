<?php
// Include the file with functions to work with the database
include('insert_data.php');

// Get the user's name from the URL
$name = $_GET['name'];

// Retrieve the user's data for editing
$stmt = $conn->prepare("SELECT * FROM contact WHERE name = :name");
$stmt->execute([':name' => $name]);
$user = $stmt->fetch();

// If the user is not found, redirect back to the main page
if (!$user) {
    header("Location: index.php");
    exit();
}

// Handle the form submission if data is sent
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the data from the form
    $email = $_POST['email'];
    $ip = $_POST['ip'];
    $message = $_POST['message'];

    // Update the user's data in the database
    $stmt = $conn->prepare("UPDATE contact SET email = :email, ip = :ip, last_message = :message, last_message_time = NOW() WHERE name = :name");
    $stmt->execute([':name' => $name, ':email' => $email, ':ip' => $ip, ':message' => $message]);

    // Fetch updated user data
    $user['email'] = $email;
    $user['ip'] = $ip;
    $user['last_message'] = $message;

    // Display success message after the update (without redirect)
    $updatedMessage = "User updated successfully!";
}

// Retrieve updated list of users
$stmt = $conn->prepare("SELECT * FROM contact");
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }

        h2 {
            color: #333;
        }

        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .message {
            font-size: 16px;
            color: green;
            margin-top: 20px;
        }

        .error {
            font-size: 16px;
            color: red;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f4f4f4;
        }

        a {
            color: #4CAF50;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <h2>Edit User: <?php echo htmlspecialchars($user['name']); ?></h2>
    <form action="edit_user.php?name=<?php echo urlencode($user['name']); ?>" method="post">
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>"
            required><br><br>

        <label for="ip">IP Address:</label>
        <input type="text" name="ip" id="ip" value="<?php echo htmlspecialchars($user['ip']); ?>" required><br><br>

        <label for="message">Message:</label>
        <textarea name="message" id="message"
            required><?php echo htmlspecialchars($user['last_message']); ?></textarea><br><br>

        <input type="submit" value="Update User">
    </form>

    <?php
    // Display success message if the user is updated
    if (isset($updatedMessage)) {
        echo "<p class='message'>$updatedMessage</p>";
    }
    ?>

    <h3>All Users:</h3>
    <table border="1">
        <thead>
            <tr>
                <th>User Name</th>
                <th>Email</th>
                <th>IP Address</th>
                <th>Message</th>
                <th>Last Message Time</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Loop through the users and display them in the table
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . htmlspecialchars($user['ip']) . "</td>";
                echo "<td>" . htmlspecialchars($user['last_message']) . "</td>";
                echo "<td>" . htmlspecialchars($user['last_message_time']) . "</td>";
                echo "<td>";
                echo "<a href='edit_user.php?name=" . urlencode($user['name']) . "'>Edit</a> | ";
                echo "<a href='delete_user.php?name=" . urlencode($user['name']) . "' onclick='return confirm(\"Are you sure you want to delete this user?\")'>Delete</a>";
                echo "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

</body>

</html>