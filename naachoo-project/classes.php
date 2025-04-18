<?php
// config.php - Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'password');
define('DB_NAME', 'naachoo_studio');

// Establish database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// auth.php - Authentication functions
function verifyAdminPassword($password) {
    // In production, use password_hash() and password_verify()
    $adminPasswordHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // hash of 'naachoo123'
    return password_verify($password, $adminPasswordHash);
}

// classes.php - Class management functions
function getAllClasses() {
    $conn = getDBConnection();
    $sql = "SELECT c.*, i.name as instructor_name 
            FROM classes c 
            JOIN instructors i ON c.instructor_id = i.id
            ORDER BY c.day, c.start_time";
    $result = $conn->query($sql);
    
    $classes = [];
    while($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
    
    $conn->close();
    return $classes;
}

function addClass($className, $instructorId, $day, $startTime, $duration) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO classes (name, instructor_id, day, start_time, duration) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisss", $className, $instructorId, $day, $startTime, $duration);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $success;
}

function updateClass($classId, $className, $instructorId, $day, $startTime, $duration) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE classes SET name=?, instructor_id=?, day=?, start_time=?, duration=? WHERE id=?");
    $stmt->bind_param("sisssi", $className, $instructorId, $day, $startTime, $duration, $classId);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $success;
}

function deleteClass($classId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM classes WHERE id=?");
    $stmt->bind_param("i", $classId);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $success;
}

// attendance.php - Attendance management functions
function getStudents() {
    $conn = getDBConnection();
    $sql = "SELECT id, name FROM students ORDER BY name";
    $result = $conn->query($sql);
    
    $students = [];
    while($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    $conn->close();
    return $students;
}

function getStudentAttendance($studentId, $month, $year) {
    $conn = getDBConnection();
    
    // Get all weeks in the month
    $firstDay = date("$year-$month-01");
    $lastDay = date("Y-m-t", strtotime($firstDay));
    
    $weeks = [];
    $current = strtotime($firstDay);
    $last = strtotime($lastDay);
    
    while($current <= $last) {
        $weekStart = date('Y-m-d', $current);
        $weekEnd = date('Y-m-d', strtotime('+6 days', $current));
        $weekNumber = date('W', $current);
        
        $weeks["Week $weekNumber"] = [
            'start' => $weekStart,
            'end' => $weekEnd
        ];
        
        $current = strtotime('+1 week', $current);
    }
    
    // Get attendance for each week day
    $attendanceData = [];
    foreach($weeks as $weekName => $weekDates) {
        $sql = "SELECT a.date, a.status 
                FROM attendance a 
                WHERE a.student_id = ? 
                AND a.date BETWEEN ? AND ?
                ORDER BY a.date";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $studentId, $weekDates['start'], $weekDates['end']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $weekAttendance = [];
        while($row = $result->fetch_assoc()) {
            $dayOfWeek = date('l', strtotime($row['date']));
            $weekAttendance[$dayOfWeek] = $row['status'];
        }
        
        // Fill in missing days
        $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        foreach($weekDays as $day) {
            if(!isset($weekAttendance[$day])) {
                $weekAttendance[$day] = 'absent'; // Default status
            }
        }
        
        // Order by week days
        $orderedAttendance = [];
        foreach($weekDays as $day) {
            $orderedAttendance[] = $weekAttendance[$day];
        }
        
        $attendanceData[$weekName] = $orderedAttendance;
    }
    
    $stmt->close();
    $conn->close();
    return $attendanceData;
}

function saveAttendance($studentId, $week, $attendance) {
    $conn = getDBConnection();
    
    // Get week dates
    $year = date('Y');
    $weekNumber = str_replace('Week ', '', $week);
    $dates = [];
    
    for($i = 0; $i < 5; $i++) { // Monday to Friday
        $date = date('Y-m-d', strtotime($year . 'W' . str_pad($weekNumber, 2, '0', STR_PAD_LEFT) . $i));
        $dates[] = $date;
    }
    
    // Save each day's attendance
    $success = true;
    for($i = 0; $i < 5; $i++) {
        $date = $dates[$i];
        $status = $attendance[$i];
        
        // Check if record exists
        $checkSql = "SELECT id FROM attendance WHERE student_id = ? AND date = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("is", $studentId, $date);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if($result->num_rows > 0) {
            // Update existing record
            $row = $result->fetch_assoc();
            $updateSql = "UPDATE attendance SET status = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("si", $status, $row['id']);
            $success = $success && $updateStmt->execute();
            $updateStmt->close();
        } else {
            // Insert new record
            $insertSql = "INSERT INTO attendance (student_id, date, status) VALUES (?, ?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("iss", $studentId, $date, $status);
            $success = $success && $insertStmt->execute();
            $insertStmt->close();
        }
        
        $checkStmt->close();
    }
    
    $conn->close();
    return $success;
}

// API endpoints
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    try {
        switch($action) {
            case 'verify_admin':
                $password = $_POST['password'] ?? '';
                $response = [
                    'success' => verifyAdminPassword($password),
                    'message' => verifyAdminPassword($password) ? 'Admin verified' : 'Invalid password'
                ];
                break;
                
            case 'get_classes':
                $response = [
                    'success' => true,
                    'classes' => getAllClasses()
                ];
                break;
                
            case 'add_class':
                $response = [
                    'success' => addClass(
                        $_POST['name'],
                        $_POST['instructor_id'],
                        $_POST['day'],
                        $_POST['start_time'],
                        $_POST['duration']
                    ),
                    'message' => 'Class added successfully'
                ];
                break;
                
            case 'update_class':
                $response = [
                    'success' => updateClass(
                        $_POST['id'],
                        $_POST['name'],
                        $_POST['instructor_id'],
                        $_POST['day'],
                        $_POST['start_time'],
                        $_POST['duration']
                    ),
                    'message' => 'Class updated successfully'
                ];
                break;
                
            case 'delete_class':
                $response = [
                    'success' => deleteClass($_POST['id']),
                    'message' => 'Class deleted successfully'
                ];
                break;
                
            case 'get_students':
                $response = [
                    'success' => true,
                    'students' => getStudents()
                ];
                break;
                
            case 'get_attendance':
                $response = [
                    'success' => true,
                    'attendance' => getStudentAttendance(
                        $_POST['student_id'],
                        $_POST['month'],
                        $_POST['year']
                    )
                ];
                break;
                
            case 'save_attendance':
                $response = [
                    'success' => saveAttendance(
                        $_POST['student_id'],
                        $_POST['week'],
                        $_POST['attendance']
                    ),
                    'message' => 'Attendance saved successfully'
                ];
                break;
        }
    } catch(Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
    
    echo json_encode($response);
    exit;
}
?>

