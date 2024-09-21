<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost"; 
$username = "root"; 
$password = "";
$dbname = "mini-project"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user = $_SESSION['username'];

// Fetch user details
$stmt = $conn->prepare("SELECT id, email, phone, address FROM register WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$stmt->bind_result($user_id, $email, $phone, $address);
$stmt->fetch();
$stmt->close();

// Fetch user's tools count
$stmt = $conn->prepare("SELECT COUNT(*) FROM tools WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($tool_count);
$stmt->fetch();
$stmt->close();

// Fetch tools added by the user
$stmt = $conn->prepare("SELECT tool_id, name, image FROM tools WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$tools = [];
while ($row = $result->fetch_assoc()) {
    $tools[] = $row;
}
$stmt->close();

// Handle tool deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_tool_id'])) {
    $tool_id = $_POST['delete_tool_id'];

    if (!empty($tool_id)) {
        // Fetch the image path
        $stmt = $conn->prepare("SELECT image FROM tools WHERE tool_id = ? AND id = ?");
        $stmt->bind_param("ii", $tool_id, $user_id);
        $stmt->execute();
        $stmt->bind_result($image_path);
        $stmt->fetch();
        $stmt->close();

        // Delete the image file if it exists
        if (!empty($image_path) && file_exists($image_path)) {
            unlink($image_path);
        }

        // Delete the tool record from the database
        $stmt = $conn->prepare("DELETE FROM tools WHERE tool_id = ? AND id = ?");
        $stmt->bind_param("ii", $tool_id, $user_id);
        
        if ($stmt->execute()) {
            echo '<div class="alert alert-success" role="alert">Tool deleted successfully!</div>';
        } else {
            echo '<div class="alert alert-danger" role="alert">Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();

        // Refresh tools list after deletion
        $stmt = $conn->prepare("SELECT tool_id, name, image FROM tools WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tools = [];
        while ($row = $result->fetch_assoc()) {
            $tools[] = $row;
        }
        $stmt->close();
    } else {
        echo '<div class="alert alert-warning" role="alert">Please provide a tool ID.</div>';
    }
}

// Handle address change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_address'])) {
    $new_address = $_POST['new_address'];

    if (!empty($new_address)) {
        // Update the address in the database
        $stmt = $conn->prepare("UPDATE register SET address = ? WHERE id = ?");
        $stmt->bind_param("si", $new_address, $user_id);

        if ($stmt->execute()) {
            $address = $new_address; // Update the current address displayed
            echo '<div class="alert alert-success" role="alert">Address updated successfully!</div>';
        } else {
            echo '<div class="alert alert-danger" role="alert">Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } else {
        echo '<div class="alert alert-warning" role="alert">Please enter a valid address.</div>';
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
        .card-body {
            text-align: center;
        }
        .btn {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="mb-4">
            <h1 class="text-center">Welcome, <?php echo htmlspecialchars($user); ?>!</h1>
            <div class="text-center mb-3">
                <a href="mainpage.php" class="btn btn-primary">Explore Lending</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>User Details</h5>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($address); ?></p>
                        <!-- Change Address Button triggers the modal -->
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changeAddressModal">Change Address</button>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Tools</h5>
                        <p>Lent: <?php echo $tool_count; ?></p>
                        <p>Provided: <?php echo $tool_count; ?></p>
                        <a href="toolsadd.php" class="btn btn-primary">Add Tool</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Messages & Notifications</h5>
                        <p>Messages</p>
                        <p>Notifications</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Display tools with delete functionality -->
        <div class="row mt-4">
            <?php foreach ($tools as $tool): ?>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($tool['name']); ?></h5>
                            <!-- Display tool image if available -->
                            <?php if (!empty($tool['image']) && file_exists($tool['image'])): ?>
                                <img src="<?php echo htmlspecialchars($tool['image']); ?>" alt="Tool Image" class="img-fluid">
                            <?php endif; ?>
                            <!-- Delete tool form -->
                            <form action="" method="post">
                                <input type="hidden" name="delete_tool_id" value="<?php echo $tool['tool_id']; ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade" id="changeAddressModal" tabindex="-1" aria-labelledby="changeAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeAddressModalLabel">Change Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="new_address" class="form-label">New Address</label>
                            <textarea class="form-control" id="new_address" name="new_address" rows="3" required><?php echo htmlspecialchars($address); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="update_address">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
