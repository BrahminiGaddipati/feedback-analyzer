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

$form_id = intval($_GET['form_id'] ?? 0);

if ($form_id <= 0) {
    die("Invalid form ID");
}

// Handle CSV download of all responses
if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    // Fetch questions
    $questions_result = $conn->query("SELECT id, question_text FROM form_question WHERE form_id = $form_id ORDER BY id ASC");
    $questions = [];
    while ($q = $questions_result->fetch_assoc()) {
        $questions[$q['id']] = $q['question_text'];
    }

    // Fetch all responses for form, grouped by respondent
    $response_result = $conn->query("SELECT respondent_id, question_id, answer FROM feedback_answers WHERE form_id = $form_id ORDER BY respondent_id, question_id");
    $responses_by_respondent = [];

    while ($row = $response_result->fetch_assoc()) {
        $responses_by_respondent[$row['respondent_id']][$row['question_id']] = $row['answer'];
    }

    // Prepare CSV header
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=all_responses_form_' . $form_id . '.csv');
    $output = fopen('php://output', 'w');

    // Header row: Respondent ID + Questions
    $header = ['Respondent ID'];
    foreach ($questions as $qid => $qtext) {
        $header[] = $qtext;
    }
    fputcsv($output, $header);

    // Data rows
    foreach ($responses_by_respondent as $respondent_id => $answers) {
        $row = [$respondent_id];
        foreach ($questions as $qid => $qtext) {
            $row[] = $answers[$qid] ?? '';
        }
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// Fetch form details
$form_query = "SELECT * FROM feedback_forms WHERE id = $form_id";
$form_result = $conn->query($form_query);
$form = $form_result->fetch_assoc();
if (!$form) {
    die("Form not found.");
}

// Fetch all questions for the form
$questions_result = $conn->query("SELECT id, question_text FROM form_question WHERE form_id = $form_id ORDER BY id ASC");
$questions = [];
while ($row = $questions_result->fetch_assoc()) {
    $questions[$row['id']] = $row['question_text'];
}

// Fetch all answers grouped by question
$answers = [];
$answers_result = $conn->query("SELECT question_id, answer FROM feedback_answers WHERE form_id = $form_id");
while ($row = $answers_result->fetch_assoc()) {
    $answers[$row['question_id']][] = $row['answer'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Verbal - Analytics</title>
    <link rel="stylesheet" href="analytics.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f9f9f9;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
        }
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .header-row h2 {
            flex: 1 1 auto;
            text-align: center;
            margin: 0;
        }
        .header-row button {
            flex: 0 0 auto;
            background: #009688;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            transition: background-color 0.3s ease;
        }
        .header-row button:hover {
            background: #00796b;
        }
        .question-section {
            margin-bottom: 50px;
        }
        .text-responses h4 {
            margin-top: 20px;
        }
        .responses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        .response-card {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 8px;
            border-left: 5px solid #009688;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 150px;
            overflow-y: auto;
        }
        canvas {
            max-width: 100%;
            height: 300px !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-row">
            <button onclick="window.location.href='admindash.php'">⬅ Back</button>
            <h2>Verbal - Analytics</h2>
            <button onclick="window.location.href='?form_id=<?php echo $form_id; ?>&download=csv'">📥 Download All Responses</button>
        </div>

        <?php foreach ($questions as $qid => $question_text): ?>
            <?php
                $numeric_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
                $text_responses = [];

                if (isset($answers[$qid])) {
                    foreach ($answers[$qid] as $ans) {
                        if (is_numeric($ans) && $ans >= 1 && $ans <= 5) {
                            $numeric_counts[intval($ans)]++;
                        } else {
                            $text_responses[] = $ans;
                        }
                    }
                }

                $hasNumeric = array_sum($numeric_counts) > 0;
                $chart_id = "chart_" . $qid;
            ?>
            <div class="question-section">
                <h3><?php echo htmlspecialchars($question_text); ?></h3>

                <?php if ($hasNumeric): ?>
                    <canvas id="<?php echo $chart_id; ?>"></canvas>
                    <script>
                        const ctx<?php echo $qid; ?> = document.getElementById('<?php echo $chart_id; ?>').getContext('2d');
                        new Chart(ctx<?php echo $qid; ?>, {
                            type: 'bar',
                            data: {
                                labels: ['1', '2', '3', '4', '5'],
                                datasets: [{
                                    label: 'Responses',
                                    data: <?php echo json_encode(array_values($numeric_counts)); ?>,
                                    backgroundColor: 'rgba(75, 192, 192, 0.3)',
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0,
                                            stepSize: 1,
                                            callback: function(value) {
                                                return Number.isInteger(value) ? value : null;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    </script>
                <?php endif; ?>

                <?php if (!empty($text_responses)): ?>
                    <div class="text-responses">
                        <h4>📝 Text Responses</h4>
                        <div class="responses-grid">
                            <?php foreach ($text_responses as $text): ?>
                                <div class="response-card"><?php echo nl2br(htmlspecialchars($text)); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
