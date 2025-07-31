<?php
// Check if form_id is passed in the URL
if (!isset($_POST['form_id'])) {
    die("Error: Form ID is missing.");
}

$form_id = $_POST['form_id'];
$student_id = 1; // You can replace this with the actual student ID from session or user data

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "feedback";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
// The answer provided by the student

        // Insert into feedback_answers table
        $sql = "INSERT INTO feedback_answers (form_id, question_id, student_id, answer, submitted_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiis", $form_id, $question_id, $student_id, $answer);
        

        $stmt->close();
    }


// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Feedback Submitted</title>
  <script type="text/javascript">
    // Display an alert and redirect to student dashboard after submission
    alert("Feedback submitted successfully!");
    window.location.href = "studentdash.php"; // Redirect to studentdash.php
  </script>
</head>
<body>
</body>
</html>
