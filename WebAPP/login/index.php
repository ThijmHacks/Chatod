<?php
// Database connection
$servername = "localhost";
$username = "chatod";
$password = "";
$database = "chatod";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$register_error = $login_error = "";

// Handle Registration Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = isset($_POST['phone']) ? $_POST['phone'] : NULL;
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Check if the username or email already exists
    $check_user = "SELECT * FROM users WHERE username='$username' OR email='$email'";
    $result = $conn->query($check_user);

    if ($result->num_rows > 0) {
        $register_error = "Username or Email already exists.";
    } else {
        // Insert the new user into the database
        $sql = "INSERT INTO users (username, full_name, email, phone, password)
                VALUES ('$username', '$full_name', '$email', '$phone', '$password')";

        if ($conn->query($sql) === TRUE) {
            echo "Registration successful!";
        } else {
            $register_error = "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Handle Login Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = $_POST['login_username'];
    $password = $_POST['login_password'];

    // Retrieve the user from the database
    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phone'] = $user['phone'];
            echo "Login successful! Welcome, " . $_SESSION['full_name'];
        } else {
            $login_error = "Invalid password.";
        }
    } else {
        $login_error = "No user found with that username.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login & Register</title>
</head>
<body>

<h2>Register</h2>
<?php
if ($register_error) {
    echo "<p style='color:red;'>$register_error</p>";
}
?>
<form action="auth.php" method="POST">
    <input type="hidden" name="register">
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" required>
    <br>
    <label for="full_name">Full Name:</label>
    <input type="text" id="full_name" name="full_name" required>
    <br>
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
    <br>
    <label for="phone">Phone (Optional):</label>
    <input type="text" id="phone" name="phone">
    <br>
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>
    <br>
    <button type="submit">Register</button>
</form>

<h2>Login</h2>
<?php
if ($login_error) {
    echo "<p style='color:red;'>$login_error</p>";
}
?>
<form action="auth.php" method="POST">
    <input type="hidden" name="login">
    <label for="login_username">Username:</label>
    <input type="text" id="login_username" name="login_username" required>
    <br>
    <label for="login_password">Password:</label>
    <input type="password" id="login_password" name="login_password" required>
    <br>
    <button type="submit">Login</button>
</form>

</body>
</html>
