<?php
// Database configuration and processing
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'dance_school';
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';
$showForm = true;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);
    $class_id = $_POST['class_id'];

    // Check class capacity
    $capacity_check = $conn->prepare("SELECT COUNT(*) AS enrolled, capacity FROM enrollments 
                                    JOIN classes ON classes.class_id = enrollments.class_id 
                                    WHERE enrollments.class_id = ?");
    $capacity_check->bind_param("i", $class_id);
    $capacity_check->execute();
    $result = $capacity_check->get_result()->fetch_assoc();
    
    if ($result['enrolled'] >= $result['capacity']) {
        $error = "This class is full. Please choose another class.";
    } else {
        // Insert enrollment
        $stmt = $conn->prepare("INSERT INTO enrollments (student_name, email, phone, class_id) 
                               VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $email, $phone, $class_id);

        if ($stmt->execute()) {
            $success = "<h2>Enrollment Successful!</h2>
                        <p>Thank you for registering, $name! We'll contact you at $email to confirm your spot.</p>";
            $showForm = false;
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    $capacity_check->close();
}

// Get available classes for dropdown
$classes = [];
$class_result = $conn->query("SELECT * FROM classes");
if ($class_result->num_rows > 0) {
    while($row = $class_result->fetch_assoc()) {
        $classes[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dance Class Enrollment</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #FFF8F0; /* Cream background */
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        .container { 
            max-width: 600px; 
            margin: 30px auto; 
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border: 1px solid #FFA500; /* Orange border */
        }
        h1 {
            color: #E67E22; /* Dark orange */
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #FFA500; /* Orange underline */
            padding-bottom: 10px;
        }
        p {
            text-align: center;
            margin-bottom: 30px;
            color: #666;
        }
        .form-group { 
            margin-bottom: 20px; 
        }
        label { 
            display: block; 
            margin-bottom: 8px;
            font-weight: 600;
            color: #E67E22; /* Dark orange */
        }
        input, select { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #FFA500; /* Orange border */
            border-radius: 5px;
            background-color: #FFF8F0; /* Cream background */
            transition: all 0.3s;
            box-sizing: border-box;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #E67E22; /* Dark orange */
            box-shadow: 0 0 5px rgba(230, 126, 34, 0.5);
        }
        button { 
            background: #FF8C00; /* Orange */
            color: white; 
            padding: 12px 25px; 
            border: none; 
            border-radius: 5px;
            cursor: pointer; 
            font-size: 16px;
            font-weight: bold;
            width: 100%;
            transition: background 0.3s;
            margin-top: 10px;
        }
        button:hover {
            background: #E67E22; /* Darker orange on hover */
        }
        .success { 
            color: #2E7D32; 
            padding: 15px; 
            border: 1px solid #C8E6C9;
            background-color: #E8F5E9;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error { 
            color: #C62828; 
            padding: 15px; 
            border: 1px solid #FFCDD2;
            background-color: #FFEBEE;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        option {
            padding: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ready to Join a Class?</h1>
        <p>Find your perfect dance class and start your journey today.</p>

        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Phone:</label>
                    <input type="tel" name="phone" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Select Class:</label>
                    <select name="class_id" required>
                        <?php foreach ($classes as $class): 
                            $selected = (isset($_POST['class_id']) && $_POST['class_id'] == $class['class_id']) ? 'selected' : '';
                        ?>
                            <option value="<?= $class['class_id'] ?>" <?= $selected ?>>
                                <?= "{$class['class_name']} - {$class['schedule']} ({$class['instructor']})" ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" name="submit">Book Your First Class</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>