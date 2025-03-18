<?php
// Include the file with functions to work with the database
include('insert_data.php');

// Get the user name from the URL
$name = $_GET['name'];

// Check if the user name exists
if ($name) {
    try {
        // Delete the user
        deleteUser($conn, $name);

        // Redirect back to the form page after deletion
        header("Location: form.php"); // Redirect to form.php after deletion
        exit();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "No user name specified!";
}
?>