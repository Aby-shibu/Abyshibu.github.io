<?php

$servername = "localhost"; 
$username = "root";        
$password = "";            
$dbname = "mini-project"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username_error = "";
$password_error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $user = $_POST['name'];
    $pass = $_POST['pword'];

    $stmt = $conn->prepare("SELECT password FROM register WHERE username = ?");
    $stmt->bind_param("s", $user);

   
    $stmt->execute();
    $stmt->store_result();

    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (password_verify($pass, $hashed_password)) {
           
            session_start();
            $_SESSION['username'] = $user;
            header("Location: dashbord.php");
            exit();
        } else {
            
            $password_error = "Invalid password";
        }
    } else {
       
        $username_error = "Invalid username";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN PAGE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="formstyle.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Login</h2>
                        <form action="login.php" method="post" onsubmit="return validate()">
                            <div class="mb-3">
                                <label for="unme" class="form-label">Username</label>
                                <input type="text" class="form-control <?php echo !empty($username_error) ? 'input-error' : ''; ?>" name="name" id="unme" placeholder="Enter your username" value="<?php echo htmlspecialchars($user ?? ''); ?>" required>
                                <div class="error"><?php echo $username_error; ?></div>
                            </div>
                            <div class="mb-3">
                                <label for="pass" class="form-label">Password</label>
                                <input type="password" class="form-control <?php echo !empty($password_error) ? 'input-error' : ''; ?>" name="pword" id="pass" placeholder="Enter your password" value="<?php echo htmlspecialchars($pass ?? ''); ?>" required>
                                <div class="error"><?php echo $password_error; ?></div>
                            </div>
                            <button type="submit" class="btn btn-dark w-100">Login</button>
                        </form>
                        <p class="text-center mt-3">Don't have an account? <a href="register.php">Register</a></p>
                        <p class="text-center"><a href="forgotpass.php">Forgot Password</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
