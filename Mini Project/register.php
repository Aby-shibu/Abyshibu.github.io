<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mini-project";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email_error = "";  // Variable to store email error
$username_error = "";  // Variable to store username error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['uname'] ?? '';
    $email = $_POST['email'] ?? '';
    $pass = $_POST['pass'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';

    if (!empty($user) && !empty($email) && !empty($pass) && !empty($phone) && !empty($address)) {
        // Check if username or email already exists
        $check_user_stmt = $conn->prepare("SELECT id FROM register WHERE username = ?");
        $check_user_stmt->bind_param("s", $user);
        $check_user_stmt->execute();
        $check_user_stmt->store_result();

        $check_email_stmt = $conn->prepare("SELECT id FROM register WHERE email = ?");
        $check_email_stmt->bind_param("s", $email);
        $check_email_stmt->execute();
        $check_email_stmt->store_result();

        if ($check_user_stmt->num_rows > 0) {
            // Username already exists, set error message
            $username_error = "Username already exists";
        } elseif ($check_email_stmt->num_rows > 0) {
            // Email already exists, set error message
            $email_error = "Email already exists";
        } else {
            // Insert new user into the database
            $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO register (username, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $user, $email, $hashed_password, $phone, $address);

            if ($stmt->execute()) {
                $_SESSION['username'] = $user;
                header("Location: dashbord.php");
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close();
        }

        $check_user_stmt->close();
        $check_email_stmt->close();
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
    <title>Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="formstyle.css">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="text-center mb-4">Registration Form</h2>
                    <form action="" method="post" onsubmit="return validate()">
                        <div class="mb-3">
                            <label for="unme" class="form-label">Username</label>
                            <input type="text" class="form-control" name="uname" id="unme" placeholder="Enter your name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email" placeholder="Enter your email" required>
                        </div>
                        <div class="mb-3">
                            <label for="pass" class="form-label">Password</label>
                            <input type="password" class="form-control" name="pass" id="pass" placeholder="Enter your password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmpass" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmpass" placeholder="Re-enter your password" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" id="phone" placeholder="Mobile no" required>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="address" placeholder="Enter your present address" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-dark w-100">Submit</button>
                    </form>
                    <p class="text-center mt-3">Already have an account? <a href="login.php">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript">
    function validate() {
        var emailInput = document.getElementById("email");
        var usernameInput = document.getElementById("unme");
        
        emailInput.placeholder = "Enter your email";
        usernameInput.placeholder = "Enter your name";
        
        emailInput.classList.remove("input-error");
        usernameInput.classList.remove("input-error");

        return true;
    }

    // Error message handling after PHP processing
    <?php if (!empty($email_error)) { ?>
    document.getElementById("email").placeholder = "<?php echo $email_error; ?>";
    document.getElementById("email").value = "";
    document.getElementById("email").classList.add("input-error");
    <?php } ?>

    <?php if (!empty($username_error)) { ?>
    document.getElementById("unme").placeholder = "<?php echo $username_error; ?>";
    document.getElementById("unme").value = "";
    document.getElementById("unme").classList.add("input-error");
    <?php } ?>
</script>
</body>
</html>
