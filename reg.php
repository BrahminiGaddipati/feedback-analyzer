    <?php
    // Database connection settings
    $servername = "localhost";
    $username = "root";
    $password = ""; // your database password (leave empty for local setups)
    $dbname = "feedback"; // database name

    // Create a connection to MySQL
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check if the connection was successful
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Initialize error message and success message variables
    $errorMessage = "";
    $successMessage = "";

    // Check if the form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Sanitize the form input
        $registration_number = mysqli_real_escape_string($conn, $_POST['registration-number']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);

        // Check if fields are empty
        if (empty($registration_number) || empty($password)) {
            $errorMessage = "Please fill in all the fields.";
        } else {
            // Check if the registration number already exists
            $checkQuery = "SELECT * FROM student WHERE registration_number = ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param("s", $registration_number);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                // Registration number already exists
                $errorMessage = "This registration number is already registered.";
            } else {
                // Hash the password for security
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Prepare and bind SQL statement to insert the new student
                $insertQuery = "INSERT INTO student (registration_number, password) VALUES (?, ?)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("ss", $registration_number, $hashed_password);

                if ($stmt->execute()) {
                    $successMessage = "Registration successful.";
                    // Redirect after successful registration
                    echo "<script type='text/javascript'>alert('$successMessage'); window.location.href = 'student.php';</script>";
                } else {
                    $errorMessage = "Error: " . $stmt->error;
                }
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
        <title>Register New Student</title>
        <link rel="stylesheet" href="reg.css">
    </head>
    <body>
        <div class="register-container">
            <h1>Register New Student</h1>

            <!-- Display success or error message -->
            <?php if ($successMessage): ?>
                <div class="success"><?php echo $successMessage; ?></div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <div class="error"><?php echo $errorMessage; ?></div>
            <?php endif; ?>

            <form id="register-form" method="POST" action="reg.php">
                <label for="new-registration-number">Registration Number</label>
                <input type="text" id="new-registration-number" name="registration-number" placeholder="Enter Registration Number" required>

                <label for="new-password">Password</label>
                <input type="password" id="new-password" name="password" placeholder="Create a Password" required>

                <button type="submit">Register</button>
            </form>

            <a href="student.php">Already have an account? Sign In</a>
        </div>
    </body>
    </html>
