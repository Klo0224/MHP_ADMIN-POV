<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "_Mindsoothe";

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

// Get doctor details
function getDoctorDetails($doctor_id, $conn) {
    $sql = "SELECT *, DATE_FORMAT(created_at, '%M %Y') as created_at FROM doctors WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get today's appointments count
function getTodayAppointmentsCount($doctor_id, $conn) {
    $today = date('Y-m-d');
    $sql = "SELECT COUNT(*) as today_count FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $doctor_id, $today);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['today_count'];
}

// Get total patients count
function getTotalPatientsCount($doctor_id, $conn) {
    $sql = "SELECT COUNT(DISTINCT patient_id) as total_patients FROM appointments WHERE doctor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total_patients'];
}

// Get upcoming appointments
function getUpcomingAppointments($doctor_id, $conn) {
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
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch doctor details and statistics
    $doctor = getDoctorDetails($doctor_id, $conn);
    $today_appointments = getTodayAppointmentsCount($doctor_id, $conn);
    $total_patients = getTotalPatientsCount($doctor_id, $conn);
    $upcoming_appointments = getUpcomingAppointments($doctor_id, $conn);

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

// Handle POST request for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['section']) && isset($_POST['content'])) {
    $allowed_sections = ['qualifications', 'education'];
    $section = filter_var($_POST['section'], FILTER_SANITIZE_STRING);
    $content = htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8');

    if (in_array($section, $allowed_sections)) {
        // Prepare update statement
        $stmt = $conn->prepare("UPDATE doctors SET {$section} = ? WHERE id = ?");
        $stmt->bind_param("si", $content, $doctor_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid section']);
    }
    exit();
}
// Handle incoming POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!isset($data['dates']) || !is_array($data['dates']) || 
        !isset($data['slots']) || !is_array($data['slots'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit();
    }

    $dates = $data['dates'];
    $slots = $data['slots'];

    $conn->begin_transaction();

    try {
        // Prepare statements outside the loops for efficiency
        $deleteStmt = $conn->prepare("DELETE FROM doctor_sched WHERE doctor_id = ? AND date = ?");
        $insertStmt = $conn->prepare("INSERT INTO doctor_sched (doctor_id, date, time_slot) VALUES (?, ?, ?)");

        // Loop through each selected date
        foreach ($dates as $date) {
            // Validate date format
            if (!strtotime($date)) {
                throw new Exception("Invalid date format");
            }

            // Delete existing schedule for the specific date
            $deleteStmt->bind_param("is", $doctor_id, $date);
            $deleteStmt->execute();

            // Insert new schedule slots for this date
            foreach ($slots as $slot) {
                $insertStmt->bind_param("iss", $doctor_id, $date, $slot);
                $insertStmt->execute();
            }
        }

        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Schedule saved for ' . count($dates) . ' date(s)'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Failed to save schedule: ' . $e->getMessage()
        ]);
    } finally {
        // Close prepared statements
        if (isset($deleteStmt)) $deleteStmt->close();
        if (isset($insertStmt)) $insertStmt->close();
    }

    $conn->close();
    exit();
}
