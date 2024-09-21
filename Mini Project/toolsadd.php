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

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the logged-in user's ID
$user = $_SESSION['username'];
$stmt = $conn->prepare("SELECT id FROM register WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tool_name = $_POST['name'];
    $description = $_POST['description'];
    $tool_condition = $_POST['toolcondition'];
    $availability_status = $_POST['availabilitystatus'];
    $category = $_POST['category'];

    // Check if all required fields are filled
    if (!empty($tool_name) && !empty($tool_condition) && !empty($availability_status)) {

        // Check if an image was uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $image = $_FILES['image'];

            // Directory to store uploaded images
            $target_dir = "uploads/";
            
            // Make sure the uploads directory exists
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // Set the image file name with a timestamp to avoid duplicates
            $image_name = basename($image["name"]);
            $target_file = $target_dir . time() . "_" . $image_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Allowed file types
            $allowed_types = array("jpg", "jpeg", "png", "gif");

            if (in_array($imageFileType, $allowed_types)) {
                // Move the uploaded file to the target directory
                if (move_uploaded_file($image["tmp_name"], $target_file)) {
                    // Prepare SQL query to insert the tool data along with the image path
                    $stmt = $conn->prepare("INSERT INTO tools (id, name, description, toolcondition, availabilitystatus, category, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issssss", $user_id, $tool_name, $description, $tool_condition, $availability_status, $category, $target_file);

                    if ($stmt->execute()) {
                        // Redirect to dashboard after successful insertion
                        header("Location: dashbord.php");
                        exit();
                    } else {
                        echo "Error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    echo "Error uploading file.";
                }
            } else {
                echo "Only JPG, JPEG, PNG, and GIF files are allowed.";
            }
        } else {
            echo "Please upload a valid image.";
        }
    } else {
        echo "Please fill in all required fields.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Add a Tool to Lend</h2>
        <form action="toolsadd.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Tool Name</label>
                <input type="text" class="form-control" name="name" id="name" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" name="description" id="description"></textarea>
            </div>
            <div class="mb-3">
                <label for="toolcondition" class="form-label">Condition</label>
                <select class="form-control" name="toolcondition" id="toolcondition" required>
                    <option value="good">Good</option>
                    <option value="good">Fair</option>
                    <option value="good">excellent</option>
                    <option value="good">usable</option>
                    <option value="good">tampered</option>
                </select>
                
            </div>
            <div class="mb-3">
                <label for="availabilitystatus" class="form-label">Availability Status</label>
                <select class="form-control" name="availabilitystatus" id="availabilitystatus" required>
                    <option value="available">Available</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="category" class="form-label">Category</label>
                <input type="text" class="form-control" name="category" id="category">
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Upload Image</label>
                <input type="file" class="form-control" name="image" id="image" accept="image/*" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Tool</button>
        </form>
    </div>
</body>
</html>
