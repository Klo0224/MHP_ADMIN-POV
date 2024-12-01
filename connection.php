<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "login_register";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get doctor's complete information
$doctor_id = $_SESSION['doctor_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
// Get doctor details
$sql = "SELECT *, DATE_FORMAT(created_at, '%M %Y') as created_at FROM doctors WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

// Get today's appointments count
$today = date('Y-m-d');
$sql = "SELECT COUNT(*) as today_count FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $doctor_id, $today);
$stmt->execute();
$today_appointments = $stmt->get_result()->fetch_assoc()['today_count'];

// Get total patients count
$sql = "SELECT COUNT(DISTINCT patient_id) as total_patients FROM appointments WHERE doctor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$total_patients = $stmt->get_result()->fetch_assoc()['total_patients'];

// Get upcoming appointments
$sql = "SELECT a.*, p.name as patient_name 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.id 
            WHERE a.doctor_id = ? 
            AND a.appointment_date >= CURRENT_DATE 
            ORDER BY a.appointment_date ASC 
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $upcoming_appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Prepare response
$response = [
    'doctor' => $doctor,
    'today_appointments' => $today_appointments,
    'total_patients' => $total_patients,
    'upcoming_appointments' => $upcoming_appointments
];

// Send JSON response
header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle GET request to fetch specific profile section
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if a specific section is requested
    if (isset($_GET['section'])) {
        $section = $_GET['section'];
        
        // Allowed sections to prevent SQL injection
        $allowed_sections = ['qualifications', 'education'];
        
        if (in_array($section, $allowed_sections)) {
            // Prepare statement to fetch specific section
            $stmt = $conn->prepare("SELECT `{$section}` FROM doctors WHERE id = ?");
            $stmt->bind_param("i", $doctor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $profile = $result->fetch_assoc();

            // Send JSON response
            header('Content-Type: application/json');
            echo json_encode($profile);
            exit();
        }
    }
}

// Handle POST request to update profile information
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read raw POST data
    $input_data = json_decode(file_get_contents('php://input'), true);

    // Allowed fields for update
    $allowed_fields = ['qualifications', 'education'];

    if (isset($input_data['section']) && isset($input_data['value'])) {
        if (in_array($input_data['section'], $allowed_fields)) {
            // Prepare update statement
            $stmt = $conn->prepare("UPDATE doctors SET `{$input_data['section']}` = ? WHERE id = ?");
            $stmt->bind_param("si", $input_data['value'], $doctor_id);

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => ucfirst($input_data['section']) . ' updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to update profile', 
                    'error' => $stmt->error
                ]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid section']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    }
}

$conn->close();
?>