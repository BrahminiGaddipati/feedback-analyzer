<?php
// Database Connection
$servername = "localhost";
$username = "root"; // Your DB username
$password = ""; // Your DB password
$dbname = "feedback"; // Your DB name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_title']) && isset($_POST['form_type'])) {
    $form_title = $_POST['form_title'];
    $form_type = $_POST['form_type'];

    $stmt = $conn->prepare("INSERT INTO feedback_forms (form_title, form_type, enabled, created_at) VALUES (?, ?, 1, NOW())");
    $stmt->bind_param("ss", $form_title, $form_type);

    if ($stmt->execute()) {
        $form_id = $stmt->insert_id;

        if (isset($_POST['questions']) && isset($_POST['question_types'])) {
            $questions = $_POST['questions'];
            $question_types = $_POST['question_types'];
        
            foreach ($questions as $index => $question) {
                $question_type = $question_types[$index];
        
                $questionStmt = $conn->prepare("INSERT INTO form_question (form_id, question_text, question_type) VALUES (?, ?, ?)");
                $questionStmt->bind_param("iss", $form_id, $question, $question_type);
                $questionStmt->execute();
                $questionStmt->close();
            }
        }
        // Redirect after successful form submission
        header("Location: admindash.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}



// Fetch all feedback forms (show both enabled and disabled forms for admin)
$sql = "SELECT * FROM feedback_forms ORDER BY created_at DESC";
$result = $conn->query($sql);

// Query to count active forms (enabled = 1)
$activeFormsQuery = "SELECT COUNT(*) as active_forms FROM feedback_forms WHERE enabled = 1";
$activeFormsResult = $conn->query($activeFormsQuery);
$activeFormsCount = 0;

if ($activeFormsResult->num_rows > 0) {
    $activeFormsData = $activeFormsResult->fetch_assoc();
    $activeFormsCount = $activeFormsData['active_forms'];
}

// Handle enabling/disabling forms
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_id']) && isset($_POST['action'])) {
    $form_id = $_POST['form_id'];
    $action = $_POST['action'];  // 'disable' or 'enable'
    
    // Set the enabled status based on the action
    $enabled = ($action === 'enable') ? 1 : 0;
    
    // Update the status in the database
    $stmt = $conn->prepare("UPDATE feedback_forms SET enabled = ? WHERE id = ?");
    $stmt->bind_param("ii", $enabled, $form_id);
    
    if ($stmt->execute()) {
        echo "Form status updated successfully.";
    } else {
        echo "Error updating form status: " . $stmt->error;
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_form_id'])) {
    $form_id = intval($_POST['delete_form_id']); // Sanitize input

    // Delete entries in feedback_answers
    $stmt = $conn->prepare("DELETE FROM feedback_answers WHERE form_id = ?");
    $stmt->bind_param("i", $form_id);
    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete answers']);
        exit();
    }
    $stmt->close();

    // Delete related questions from form_question
    $stmt = $conn->prepare("DELETE FROM form_question WHERE form_id = ?");
    $stmt->bind_param("i", $form_id);
    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete questions']);
        exit();
    }
    $stmt->close();

    // Delete the form itself
    $stmt = $conn->prepare("DELETE FROM feedback_forms WHERE id = ?");
    $stmt->bind_param("i", $form_id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'deleted']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete form']);
    }
    $stmt->close();
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_title']) && isset($_POST['form_type'])) {
    // Insert logic here
    
    echo "<script>document.getElementById('createFormSection').style.display = 'none';</script>";
}
// Count total responses
$responsesQuery = "SELECT COUNT(*) as total_responses FROM feedback_answers";
$responsesResult = $conn->query($responsesQuery);
$totalResponses = 0;

if ($responsesResult && $responsesResult->num_rows > 0) {
    $responsesData = $responsesResult->fetch_assoc();
    $totalResponses = $responsesData['total_responses'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Overview</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="adash.css">
</head>
<body>
    <header class="header">
        <div class="logo">
            <span>📄</span> FeedbackPro
        </div>
        <nav class="nav">
            <a href="#" class="icon"><span>🔔</span></a>
            <a href="#" class="icon"><span>⚙️</span></a>
            <a href="#" class="profile">
                <span>👤</span> Admin
            </a>
            <a href="admin.php" class="icon logout"><span>↩️</span></a>
        </nav>
    </header>

    <main class="dashboard">
        <section class="overview">
            <h1>Dashboard Overview</h1>
            <p>Manage feedback forms and view responses</p>
            <div class="stats">
                <div class="card">
                    <span class="icon">📄</span>
                    <h2>Total Forms</h2>
                    <p class="number"><?php echo $result->num_rows; ?></p>
                    <p class="desc">Active forms in system</p>
                </div>
                <div class="card">
          <span class="icon">📂</span>
          <h2>Active Forms</h2>
          <p class="number"><?php echo $activeFormsCount; ?></p>  <!-- Display active forms count here -->
          <p class="desc">Currently enabled forms</p>
        </div>
        <div class="card">
    <span class="icon">📊</span>
    <h2>Total Responses</h2>
    <p class="number"><?php echo $totalResponses; ?></p>
    <p class="desc">Feedback responses received</p>
</div>
            </div>
        </section>

        <section class="forms">
            <div class="forms-header">
                <h2>Feedback Forms</h2>
                <button class="create-button" onclick="toggleCreateForm()">+ Create Form</button>
            </div>

            <div class="form-list">
                <?php while ($form = $result->fetch_assoc()) { ?>
                    <div class="form-card" data-form-id="<?php echo $form['id']; ?>">
                        <h3><?php echo $form['form_title']; ?></h3>
                        <p>Type: <?php echo $form['form_type']; ?></p>
                        <p>Created: <?php echo $form['created_at']; ?></p>

                        <!-- Analytics and Toggle Section -->
                        <div class="actions">
                        <a href="analytics.php?form_id=<?php echo $form['id']; ?>" class="analytics">📊 Analytics</a>                            <label class="toggle">
                                <input type="checkbox" <?php echo $form['enabled'] == 1 ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <button class="delete-btn" data-form-id="<?php echo $form['id']; ?>">🗑 Delete</button>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </section>

        <!-- Hidden Form for Creating a New Feedback Form -->
        <section class="create-form-section" id="createFormSection" style="display: none; margin-top: 32px;">
            <h2>Create a New Feedback Form</h2>
            <form id="createForm" action="admindash.php" method="POST">
                <div class="form-group">
                    <label for="form-title">Form Title</label>
                    <input type="text" id="form-title" name="form_title" placeholder="Enter form title" required />
                </div>

                <div class="form-group">
                    <label for="form-type">Form Type</label>
                    <select id="form-type" name="form_type" required>
                        <option value="teacher-evaluation">Teacher Evaluation</option>
                        <option value="student-feedback">Student Feedback</option>
                    </select>
                </div>

                <div class="questions-section">
                    <h3>Questions</h3>
                    <div id="questions-container">
                        <!-- New questions will be appended here -->
                    </div>
                    <button type="button" id="add-question-btn" class="add-question-btn">+ Add Question</button>
                </div>

                <div class="form-actions">
                    <button type="button" class="cancel-btn" onclick="toggleCreateForm()">Cancel</button>
                    <button type="submit" class="create-form-btn">Create Form</button>
                </div>
            </form>
        </section>
    </main>

    <script>
       // Function to toggle the visibility of the form creation section
function toggleCreateForm() {
    const createFormSection = document.getElementById('createFormSection');
    if (createFormSection.style.display === 'none' || createFormSection.style.display === '') {
        createFormSection.style.display = 'block'; // Show the form section
    } else {
        createFormSection.style.display = 'none'; // Hide the form section
    }
}

        // Add event listener for the "Add Question" button
        document.getElementById('add-question-btn').addEventListener('click', function () {
    const questionsContainer = document.getElementById('questions-container');
    const questionDiv = document.createElement('div');
    questionDiv.classList.add('question-input');

    // Question text input
    const questionInput = document.createElement('input');
    questionInput.type = 'text';
    questionInput.name = 'questions[]';
    questionInput.placeholder = 'Enter question text';
    questionInput.required = true;

    // Question type dropdown
    const questionType = document.createElement('select');
    questionType.name = 'question_types[]';
    const ratingOption = new Option('Rating (1-5)', 'rating');
    const textResponseOption = new Option('Text Response', 'text');
    const multipleChoiceOption = new Option('Multiple Choice', 'multiple-choice');
    questionType.add(ratingOption);
    questionType.add(textResponseOption);
    questionType.add(multipleChoiceOption);

    // Remove button
    const removeButton = document.createElement('button');
    removeButton.textContent = '-';
    removeButton.classList.add('remove-btn');
    removeButton.type = 'button';

    removeButton.addEventListener('click', function () {
        questionsContainer.removeChild(questionDiv);
    });

    // Append inputs to question div
    questionDiv.appendChild(questionInput);
    questionDiv.appendChild(questionType);
    questionDiv.appendChild(removeButton);
    questionsContainer.appendChild(questionDiv);
});

        // Handle enabling/disabling feedback forms with AJAX
        document.querySelectorAll('.toggle input').forEach(toggle => {
            toggle.addEventListener('change', function () {
                const formId = this.closest('.form-card').getAttribute('data-form-id');
                const action = this.checked ? 'enable' : 'disable';
                
                fetch('admindash.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `form_id=${formId}&action=${action}`
                })
                .then(response => response.text())
                .then(data => {
                    console.log(data); // Optionally show success/error message
                })
                .catch(error => console.error('Error:', error));
            });
        });

        

        // Handle form deletion via AJAX
        document.querySelectorAll('.delete-btn').forEach(deleteBtn => {
            deleteBtn.addEventListener('click', function () {
                const formId = this.getAttribute('data-form-id');

                fetch('admindash.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `delete_form_id=${formId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'deleted') {
                        alert('Form deleted successfully');
                        location.reload();  // Reload the page to reflect the changes
                    } else {
                        alert('Error deleting form');
                    }
                });
            });
        });
    </script>
</body>
</html>
