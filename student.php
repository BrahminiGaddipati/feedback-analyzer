<?php
// Database connection settings
$servername = "localhost";
$username = "root";
$password = ""; // Leave empty for local setups
$dbname = "feedback";

// Create a connection to MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize error message variable
$errorMessage = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize the form input
    $registration_number = trim($_POST['registration-number']);
    $password = trim($_POST['password']);

    // Check if fields are empty
    if (empty($registration_number) || empty($password)) {
        $errorMessage = "Please fill in all the fields.";
    } else {
        // Query the database to check the registration number
        $stmt = $conn->prepare("SELECT id, password FROM student WHERE registration_number = ?");
        $stmt->bind_param("s", $registration_number);
        $stmt->execute();
        $stmt->store_result();

        // Check if the registration number exists
        if ($stmt->num_rows > 0) {
            // Bind the result to a variable
            $stmt->bind_result($student_id, $hashed_password);
            $stmt->fetch();

            // Verify the password using password_verify
            if (password_verify($password, $hashed_password)) {
                // Credentials are correct, set session variables
                session_start();
                $_SESSION['logged_in'] = true;
                $_SESSION['student_id'] = $student_id; // Store student ID for later use
                $_SESSION['registration_number'] = $registration_number;

                // Redirect to the student dashboard
                header("Location: studentdash.php");
                exit();
            } else {
                // Incorrect password
                $errorMessage = "Wrong registration number or password. Please try again.";
            }
        } else {
            // Registration number not found
            $errorMessage = "Wrong registration number or password. Please try again.";
        }

        // Close statement
        $stmt->close();
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link rel="stylesheet" href="student.css">
</head>
<body>
    <div class="login-container">
        <div class="icon">➡️</div>
        <h1>Student Login</h1>
        <div class="tabs">
            <div class="tab active" onclick="goToStudent()">Student</div>
            <div class="tab" onclick="goToAdmin()">Admin</div>
        </div>

     <style>
  form#login-form button[type="submit"] {
    margin-bottom: 8px;
  }
</style>

<form id="login-form" method="POST" action="">
  <label for="registration-number">Registration Number</label>
  <input type="text" id="registration-number" name="registration-number" placeholder="Enter Registration Number" required>

  <label for="password">Password</label>
  <input type="password" id="password" name="password" placeholder="Password" required>

  <button type="submit">Sign In</button>
</form>

<button onclick="window.location.href='reg.php'">New Student? Register Here</button>


    <script>
        // Function to go to the student page
        function goToStudent() {
            window.location.href = 'student.php';
        }

        // Function to go to the admin page
        function goToAdmin() {
            window.location.href = 'admin.php';
        }

        // Show an alert if the error message is set
        <?php if (!empty($errorMessage)) : ?>
            alert('<?php echo $errorMessage; ?>');
        <?php endif; ?>
    </script>
</body>
</html>
