<?php
// Start session to store login data
session_start();

// Define correct credentials
$correct_employee_id = 'emp1234';
$correct_password = 'Sujatha1234';

// Initialize error message variable
$error_message = '';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the input values from the form
    $employee_id = trim($_POST['employee-id']); // Trim removes extra spaces
    $password = trim($_POST['password']);

    // Validate credentials
    if ($employee_id === $correct_employee_id && $password === $correct_password) {
        // Store a session variable to mark the user as logged in
        $_SESSION['logged_in'] = true;
        $_SESSION['employee_id'] = $employee_id;

        // Redirect to admin dashboard
        header('Location: admindash.php');
        exit(); // Stop further execution after redirect
    } else {
        // Set error message if credentials are incorrect
        $error_message = 'Wrong username or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="login-container">
        <div class="icon">➡️</div>
        <h1>Admin Login</h1>
        <div class="tabs">
            <div class="tab" onclick="goToStudent()">Student</div>
            <div class="tab active">Admin</div>
        </div>

        <!-- Display error message if credentials are wrong -->
        <?php if (!empty($error_message)) : ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Login form -->
        <form method="POST" action="">
            <label for="employee-id">Employee ID</label>
            <input type="text" id="employee-id" name="employee-id" placeholder="Enter Employee ID" required>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>

    <script>
        function goToStudent() {
            window.location.href = 'student.php';
        }
    </script>
</body>
</html>
