<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mini-project";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user_id from session (assuming it's set during login)
$borrower_username = $_SESSION['username'];
$borrower_query = "SELECT id FROM register WHERE username = ?";
$borrower_stmt = $conn->prepare($borrower_query);
$borrower_stmt->bind_param("s", $borrower_username);
$borrower_stmt->execute();
$borrower_result = $borrower_stmt->get_result();

if ($borrower_result->num_rows > 0) {
    $borrower = $borrower_result->fetch_assoc();
    $borrower_id = $borrower['id'];  // The borrower’s user_id
} else {
    die("Borrower not found.");
}

// Get tool_id from URL
if (isset($_GET['tool_id'])) {
    $tool_id = $_GET['tool_id'];
} else {
    die("Tool ID is required");
}

// Fetch tool details and owner location
$tool_query = "SELECT t.id, t.name, t.description, t.toolcondition, t.availabilitystatus, t.image, r.username, r.address 
               FROM tools t 
               JOIN register r ON t.id = r.id 
               WHERE t.tool_id = ?";
$stmt = $conn->prepare($tool_query);
$stmt->bind_param("i", $tool_id);
$stmt->execute();
$tool_result = $stmt->get_result();

if ($tool_result->num_rows > 0) {
    $tool = $tool_result->fetch_assoc();
} else {
    die("Tool not found");
}

// Handle form submission to lend the tool
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $req_date = date('Y-m-d'); // Request date is today
    $status = 'Pending'; // Initial status is Pending
    $borrow_start_date = $_POST['borrow_start_date']; // Borrow start date from form
    $days_needed = $_POST['days_needed']; // Number of days needed from form

    // Calculate return date based on start date and number of days
    $return_date = date('Y-m-d', strtotime("$borrow_start_date + $days_needed days"));

   // Insert into lend_details table
$lend_query = "INSERT INTO lend_details (tool_id, borrower_id, status, req_date, approved_date, return_date) 
               VALUES (?, ?, ?, ?, NULL, ?)";
$lend_stmt = $conn->prepare($lend_query);

// Bind parameters: 
// "i" for integer (tool_id and borrower_id), 
// "s" for string (status, req_date, and return_date)
$lend_stmt->bind_param("iisss", $tool_id, $borrower_id, $status, $req_date, $return_date);

if ($lend_stmt->execute()) {
    echo "<p>Tool lending request has been sent successfully!</p>";
} else {
    echo "<p>Error: " . $lend_stmt->error . "</p>";
}

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lend Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            margin-top: 50px;
        }
        .tool-image {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }
        .tool-details {
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .btn-submit {
            background-color: #28a745;
            color: white;
        }
        .btn-submit:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Lend Tool: <?php echo $tool['name']; ?></h2>

        <div class="row">
            <div class="col-md-6">
                <img src="<?php echo $tool['image']; ?>" alt="<?php echo $tool['name']; ?>" class="tool-image">
            </div>
            <div class="col-md-6 tool-details">
                <h4>Tool Description</h4>
                <p><?php echo $tool['description']; ?></p>

                <h4>Condition</h4>
                <p><?php echo $tool['toolcondition']; ?></p>

                <h4>Owner Location</h4>
                <p><?php echo $tool['address']; ?></p>

                <h4>Status</h4>
                <p><?php echo $tool['availabilitystatus'] == 'available' ? 'Available' : 'Lended'; ?></p>
            </div>
        </div>

        <!-- Form to send lending request -->
        <form method="POST" action="">
            <div class="form-group">
                <label>Borrower Name:</label>
                <input type="text" name="borrower_name" value="<?php echo $_SESSION['username']; ?>" class="form-control" readonly>
            </div>

            <div class="form-group">
                <label>Request Date:</label>
                <input type="text" name="req_date" value="<?php echo date('Y-m-d'); ?>" class="form-control" readonly>
            </div>

            <div class="form-group">
                <label>Number of Days Needed:</label>
                <input type="number" name="days_needed" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Borrow Start Date:</label>
                <input type="date" name="borrow_start_date" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-submit">Send Lend Request</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
