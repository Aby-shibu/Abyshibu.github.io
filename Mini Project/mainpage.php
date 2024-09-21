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

// Get categories
$category_query = "SELECT DISTINCT category FROM tools";
$category_result = $conn->query($category_query);

// Filter tools based on selected category and search query
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

$tools_query = "SELECT tool_id, name, description, toolcondition, availabilitystatus, category, image FROM tools WHERE 1=1";

if ($selected_category) {
    $tools_query .= " AND category = '$selected_category'";
}

if ($search_query) {
    // Modify query to search for tools by name or description
    $tools_query .= " AND (name LIKE '%$search_query%' OR description LIKE '%$search_query%')";
}

$tools_result = $conn->query($tools_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lend A Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        /* Redesigned Attractive Card */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
        }

        .card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }

        .card-body {
            padding: 20px;
            text-align: center;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .card-text {
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 15px;
        }

        .availability {
            display: inline-block;
            padding: 6px 12px;
            font-size: 0.85rem;
            border-radius: 30px;
            font-weight: bold;
        }

        .availability.available {
            background-color: #d4edda;
            color: #155724;
        }

        .availability.unavailable {
            background-color: #f8d7da;
            color: #721c24;
        }

        .btn-lend {
            background-color: #007bff;
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 0.9rem;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .btn-lend:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-danger fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand h1" href="index.php">Lend A Tool</a>
            <form class="d-flex mt-3" role="search" method="GET" action="">
                <input class="form-control me-2" name="search" type="search" placeholder="Search tools..." aria-label="Search">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDarkNavbar" aria-controls="offcanvasDarkNavbar" aria-label="Toggle navigation">
                <span><i class="fa-solid fa-bars" style="color: #000000;"></i></span>
            </button>

            <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="offcanvasDarkNavbar" aria-labelledby="offcanvasDarkNavbarLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="offcanvasDarkNavbarLabel">Menu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="homepage.html">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categories.php">Categories</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="dashbord.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Filter by Category -->
    <div class="container mt-5 pt-4">
        <form method="GET" action="" class="mb-3">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php
                        if ($category_result->num_rows > 0) {
                            while ($category = $category_result->fetch_assoc()) {
                                $selected = ($selected_category == $category['category']) ? 'selected' : '';
                                echo '<option value="'.$category['category'].'" '.$selected.'>'.$category['category'].'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
        </form>

        <!-- Tool Cards -->
        <div class="row">
            <?php
            if ($tools_result->num_rows > 0) {
                while ($tool = $tools_result->fetch_assoc()) {
                    // Check availability status
                    $availability_status = $tool['availabilitystatus'] == 'available' ? 'available' : 'unavailable';
                    $availability_text = $tool['availabilitystatus'] == 'available' ? 'Available' : 'Lended';

                    echo '<div class="col-6 col-sm-4 col-md-3 col-lg-3 mb-4">';
                    echo '<div class="card h-100">';
                    echo '<img src="'.$tool['image'].'" class="card-img-top" alt="'.$tool['name'].'">';
                    echo '<div class="card-body">';
                    echo '<h5 class="card-title">'.$tool['name'].'</h5>';
                    echo '<p class="card-text">'.$tool['description'].'</p>';
                    // Display availability status
                    echo '<p class="availability '.$availability_status.'">'.$availability_text.'</p>';
                   echo '<a href="lend.php?tool_id='.$tool['tool_id'].'" class="btn-lend">Lend</a>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p>No tools found matching your search criteria.</p>';
            }
            ?>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
