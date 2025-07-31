<?php
session_start();

// Check if the student is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: student.php'); // Redirect to login if not authenticated
    exit();
}

$student_id = $_SESSION['student_id']; // Get student ID from session

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "feedback";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch only enabled feedback forms
$sql = "SELECT * FROM feedback_forms WHERE enabled = 1 ORDER BY created_at DESC";
$result = $conn->query($sql);

if (!$result) {
    echo "Error executing query: " . $conn->error;
}

// Fetch the number of feedback forms submitted by the student
$sql_completed = "SELECT COUNT(*) AS completed_forms FROM feedback_answers WHERE student_id = ?";
$stmt = $conn->prepare($sql_completed);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($completed_forms);
$stmt->fetch();
$stmt->close();

// Calculate the completion rate
$total_forms = $result->num_rows;
$completion_rate = ($total_forms > 0) ? round(($completed_forms / $total_forms) * 100) : 0;

// Handle AJAX request to check form status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['form_id'])) {
    $form_id = $_POST['form_id'];

    // Check the status of the form
    $sql = "SELECT enabled FROM feedback_forms WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $form_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $form = $result->fetch_assoc();

    if ($form && $form['enabled'] == 0) {
        echo 'disabled'; // If form is disabled, return 'disabled'
    } else {
        echo 'enabled'; // If form is enabled, return 'enabled'
    }

    $stmt->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard</title>
  <link rel="stylesheet" href="sdash.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    // Function to periodically check and hide disabled forms
    $(document).ready(function() {
      setInterval(function() {
        $('.form-card').each(function() {
          var formId = $(this).data('form-id');
          var formCard = $(this); // Store reference

          $.ajax({
            url: '', // Posting to the same page
            type: 'POST',
            data: { form_id: formId },
            success: function(response) {
              if (response === 'disabled') {
                formCard.hide(); // Hide the disabled form
              }
            }
          });
        });
      }, 5000); // Checks every 5 seconds
    });
  </script>
</head>
<body>
  <div class="dashboard">
    <header class="header">
      <div class="logo">FeedbackPro</div>
      <div class="profile">
        <button class="notifications">🔔</button>
        <div class="user">👤 Student</div>
        <a href="student.php" class="icon logout"><span>↩️</span></a>
        </div>
    </header>
    <main>
      <h1>Student Dashboard</h1>
      <p>View and complete feedback forms</p>
      
      <!-- Stats Section -->
      <div class="stats">
        <div class="stat">
          <p>Available Forms</p>
          <h2><?php echo $total_forms; ?></h2>
          <span>Forms to complete</span>
        </div>
        <div class="stat">
          <p>Completed</p>
          <h2><?php echo $completed_forms; ?></h2>
          <span>Forms submitted</span>
        </div>
        <div class="stat">
          <p>Completion Rate</p>
          <h2><?php echo $completion_rate; ?>%</h2>
          <span>Overall progress</span>
        </div>
      </div>

      <!-- Available Feedback Forms -->
      <div class="forms">
        <h2>Available Feedback Forms</h2>
        <?php while ($form = $result->fetch_assoc()) { ?>
          <div class="form-card" data-form-id="<?php echo $form['id']; ?>">
            <h3><?php echo $form['form_title']; ?></h3>
            <p>Type: <?php echo $form['form_type']; ?></p>
            <p>Created: <?php echo $form['created_at']; ?></p>
            <a href="feedback_form.php?form_id=<?php echo $form['id']; ?>" class="start-btn">Start Feedback</a>
          </div>
        <?php } ?>
      </div>
    </main>
  </div>
</body>
</html>

<?php
$conn->close();
?>
