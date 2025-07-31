<?php
// Check if form_id is passed in the URL
if (!isset($_GET['form_id'])) {
    die("Error: Form ID is missing.");
}

$form_id = $_GET['form_id'];

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "feedback";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the form details based on form_id
$sql = "SELECT * FROM feedback_forms WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $form_id);
$stmt->execute();
$form_result = $stmt->get_result();
$form = $form_result->fetch_assoc();

// Fetch the questions for the form
$sql_questions = "SELECT * FROM form_question WHERE form_id = ? ORDER BY id";
$stmt_questions = $conn->prepare($sql_questions);
$stmt_questions->bind_param("i", $form_id);
$stmt_questions->execute();
$questions_result = $stmt_questions->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $form['form_title']; ?> Feedback</title>
  <link rel="stylesheet" href="feed.css">
  <script>
    // Function to validate form before submission
    function validateForm() {
      var isValid = true;
      document.querySelectorAll('.form-group').forEach(function(group) {
        var requiredInput = group.querySelector('input:checked, select, textarea');
        if (!requiredInput) {
          group.classList.add('error');
          isValid = false;
        } else {
          group.classList.remove('error');
        }
      });

      return isValid;
    }
  </script>
</head>
<body>
  <div class="feedback-wrapper">
    <div class="feedback-container">
      <h2><?php echo $form['form_title']; ?></h2>
      <form class="feedback-form" action="submit_feedback.php" method="POST" onsubmit="return validateForm()">
        <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">

        <?php while ($question = $questions_result->fetch_assoc()) { ?>
          <div class="form-group">
            <label for="question_<?php echo $question['id']; ?>"><?php echo $question['question_text']; ?></label>
            <?php if ($question['question_type'] == 'rating') { ?>
              <div class="rating-options">
                <div class="rating-item">
                  <input type="radio" id="rating-1-<?php echo $question['id']; ?>" name="rating_<?php echo $question['id']; ?>" value="1" required>
                  <label for="rating-1-<?php echo $question['id']; ?>">1</label>
                  <span>Poor</span>
                </div>
                <div class="rating-item">
                  <input type="radio" id="rating-2-<?php echo $question['id']; ?>" name="rating_<?php echo $question['id']; ?>" value="2">
                  <label for="rating-2-<?php echo $question['id']; ?>">2</label>
                  <span>Satisfactory</span>
                </div>
                <div class="rating-item">
                  <input type="radio" id="rating-3-<?php echo $question['id']; ?>" name="rating_<?php echo $question['id']; ?>" value="3">
                  <label for="rating-3-<?php echo $question['id']; ?>">3</label>
                  <span>Good</span>
                </div>
                <div class="rating-item">
                  <input type="radio" id="rating-4-<?php echo $question['id']; ?>" name="rating_<?php echo $question['id']; ?>" value="4">
                  <label for="rating-4-<?php echo $question['id']; ?>">4</label>
                  <span>Very Good</span>
                </div>
                <div class="rating-item">
                  <input type="radio" id="rating-5-<?php echo $question['id']; ?>" name="rating_<?php echo $question['id']; ?>" value="5">
                  <label for="rating-5-<?php echo $question['id']; ?>">5</label>
                  <span>Excellent</span>
                </div>
              </div>
            <?php } elseif ($question['question_type'] == 'text') { ?>
              <textarea id="question_<?php echo $question['id']; ?>" name="text_<?php echo $question['id']; ?>" rows="4" placeholder="Your answer..."></textarea>
            <?php } elseif ($question['question_type'] == 'multiple-choice') { 
                // Fetch the options dynamically from the database
                $sql_options = "SELECT * FROM form_question_options WHERE question_id = ?";
                $stmt_options = $conn->prepare($sql_options);
                $stmt_options->bind_param("i", $question['id']);
                $stmt_options->execute();
                $options_result = $stmt_options->get_result();
              ?>
              <select id="question_<?php echo $question['id']; ?>" name="multiple_choice_<?php echo $question['id']; ?>" required>
                <option value="">Select an option</option>
                <?php while ($option = $options_result->fetch_assoc()) { ?>
                  <option value="<?php echo $option['option_value']; ?>"><?php echo $option['option_text']; ?></option>
                <?php } ?>
              </select>
            <?php } ?>
          </div>
        <?php } ?>

        <div class="form-actions">
          <button type="button" class="cancel-btn" onclick="window.location.href='studentdash.php'">Cancel</button>
          <button type="submit" class="submit-btn">Submit Feedback</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>

<?php
$conn->close();
?>
