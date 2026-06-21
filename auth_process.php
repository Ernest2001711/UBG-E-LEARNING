<?php
session_start();

// Database configuration setup
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "ubg_portal_db";

// Connect to the MySQL database
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

// Check if connection works
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Check if the form submitted data via POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    // --- 1. REGISTRATION LOGIC ---
    if ($action == 'register') {
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name  = mysqli_real_escape_string($conn, $_POST['last_name']);
        $reg_number = mysqli_real_escape_string($conn, $_POST['reg_number']);
        $email      = mysqli_real_escape_string($conn, $_POST['email']);
        $password   = $_POST['password'];

        // Securely encrypt the password before saving
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Save student as 'unauthorized' (is_authorized = 0) until admin approves
        $query = "INSERT INTO users (reg_number, first_name, last_name, email, password_hash, role, is_authorized) 
                  VALUES ('$reg_number', '$first_name', '$last_name', '$email', '$password_hash', 'student', 0)";

        if ($conn->query($query) === TRUE) {
            echo "<script>
                    alert('Registration successful! Your account is pending admin authorization.');
                    window.location.href='LOGIN.HTML';
                  </script>";
        } else {
            echo "Error saving data to database: " . $conn->error;
        }
    }

    // --- 2. LOGIN LOGIC ---
    if ($action == 'login') {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password'];

        $query = "SELECT * FROM users WHERE email = '$email'";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify if password matches encrypted hash
            if (password_verify($password, $user['password_hash'])) {
                
                // BLOCK entry if admin hasn't approved yet
                if ($user['is_authorized'] == 0) {
                    die("<script>
                            alert('Access Denied: Your account registration has not been authorized by admin yet.');
                            window.location.href='LOGIN.HTML';
                          </script>");
                }

                // If authorized, save details to active session memory
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['first_name'] . " " . $user['last_name'];

                // Send user to their specific dashboard screen
                if ($user['role'] == 'student') {
                    header("Location: student_dashboard.php");
                } elseif ($user['role'] == 'lecturer') {
                    header("Location: lecturer_dashboard.php");
                } elseif ($user['role'] == 'admin') {
                    header("Location: admin_dashboard.php");
                }
                exit();

            } else {
                echo "<script>alert('Invalid password.'); window.location.href='LOGIN.HTML';</script>";
            }
        } else {
            echo "<script>alert('No user account found with that email.'); window.location.href='LOGIN.HTML';</script>";
        }
    }
}
?>