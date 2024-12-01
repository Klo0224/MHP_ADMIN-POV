<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "login_register";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';

// Admin login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $admin_username = $_POST['admin_username'];
    $admin_password = $_POST['admin_password'];

    // In a real-world scenario, you'd check these credentials against a database
    if ($admin_username === 'admin' && $admin_password === 'adminpass') {
        $_SESSION['admin'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $message = "Invalid admin credentials.";
    }
}

// Admin approval/rejection process
if (isset($_SESSION['admin']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $doctor_id = $_POST['doctor_id'];
        $sql = "UPDATE doctors SET status = 'approved' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $message = "Doctor approved successfully.";
    } elseif (isset($_POST['declined'])) {
        $doctor_id = $_POST['doctor_id'];
        $sql = "UPDATE doctors SET status = 'declined' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $message = "Doctor declined successfully.";
    }
}

function isImage($path) {
    if (!file_exists($path)) return false;
    $imageInfo = @getimagesize($path);
    return $imageInfo !== false;
}

function displayDoctorsTable($conn, $status) {
    $sql = "SELECT * FROM doctors WHERE status = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0):
    ?>
    <table>
        <tr>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>Specialization</th>
            <th>Experience</th>
            <th>License Front</th>
            <th>License Back</th>
            <?php if ($status === 'pending'): ?>
                <th>Action</th>
            <?php endif; ?>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['fname']); ?></td>
            <td><?php echo htmlspecialchars($row['lname']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['specialization']); ?></td>
            <td><?php echo htmlspecialchars($row['experience']); ?></td>
            <td>
                <?php
                $license_front = $row['license_front'];
                if (isImage($license_front)):
                ?>
                    <a href="#" onclick="showImage('<?php echo htmlspecialchars($license_front); ?>')" class="license-link">View Front</a>
                <?php else: ?>
                    <span class="license-error">Image not found</span>
                <?php endif; ?>
            </td>
            <td>
                <?php
                $license_back = $row['license_back'];
                if (isImage($license_back)):
                ?>
                    <a href="#" onclick="showImage('<?php echo htmlspecialchars($license_back); ?>')" class="license-link">View Back</a>
                <?php else: ?>
                    <span class="license-error">Image not found</span>
                <?php endif; ?>
            </td>
            <?php if ($status === 'pending'): ?>
            <td>
                <form method="post">
                    <input type="hidden" name="doctor_id" value="<?php echo $row['id']; ?>">
                    <button type="submit" name="approve">Approve</button>
                    <button type="submit" name="declined">Reject</button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <p>No <?php echo $status; ?> doctors found.</p>
    <?php endif;
}

// Logout process
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(180deg, rgb(28, 171, 227) 0%, rgb(28, 171, 227) 25%, rgb(159, 194.5, 244.5) 50%, rgb(200, 185, 250) 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }
        .login-form input {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .dashboard-section {
            background-color: white;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        h2, h3 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
            background-color: white;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        button {
            background-color: #1cabe3;
            border: none;
            color: white;
            padding: 8px 16px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 2px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
        button[name="declined"] {
            background-color: #f44336;
        }
        .license-link {
            color: #337088;
            text-decoration: none;
        }
        .license-link:hover {
            text-decoration: underline;
        }
        .license-error {
            color: #ff0000;
        }
        .logout {
            text-align: right;
            margin-top: 20px;
        }
        .logout a {
            color: #f44336;
            text-decoration: none;
            padding: 8px 16px;
            border: 1px solid #f44336;
            border-radius: 4px;
        }
        .logout a:hover {
            background-color: #f44336;
            color: white;
        }
        .message {
            background-color: #f2f2f2;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 20px;
            text-align: center;
            border-radius: 4px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.9);
        }
        .modal-content {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 700px;
        }
        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!isset($_SESSION['admin'])): ?>
            <h2>Admin Login</h2>
            <?php if ($message): ?>
                <p class="message"><?php echo $message; ?></p>
            <?php endif; ?>
            <form method="post" class="login-form">
                <input type="text" name="admin_username" placeholder="Admin Username" required>
                <input type="password" name="admin_password" placeholder="Admin Password" required>
                <button type="submit" name="admin_login">Login</button>
            </form>
        <?php else: ?>
            <h2>Admin Dashboard</h2>
            <?php if ($message): ?>
                <p class="message"><?php echo $message; ?></p>
            <?php endif; ?>
            
            <h3>Pending Doctors</h3>
            <?php displayDoctorsTable($conn, 'pending'); ?>

            <h3>Approved Doctors</h3>
            <?php displayDoctorsTable($conn, 'approved'); ?>

            <h3>Declined Doctors</h3>
            <?php displayDoctorsTable($conn, 'declined'); ?>

            <div class="logout">
                <a href="?logout=1">Logout</a>
            </div>
        <?php endif; ?>
    </div>

    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <script>
        function showImage(imagePath) {
            var modal = document.getElementById("imageModal");
            var modalImg = document.getElementById("modalImage");
            modal.style.display = "block";
            modalImg.src = imagePath;
        }

        function closeModal() {
            var modal = document.getElementById("imageModal");
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            var modal = document.getElementById("imageModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>