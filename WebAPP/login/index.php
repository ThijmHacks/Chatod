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
            header("Location: ../chatod/friends");
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
    <link rel="stylesheet" href="style.css">

</head>
<body>
<div class="container" id="container">
	<div class="form-container sign-up-container">
        <?php
            if ($register_error) {
                echo "<p style='color:red;'>$register_error</p>";
            }
        ?>
        <form action="index.php" method="POST">
			<h1>Create Account</h1>
			<input type="hidden" name="register">
			<input type="text" id="username" name="username" placeholder="Username: " required>
            <input type="text" id="full_name" name="full_name" placeholder="Full name: "required>
			<input type="email" id="email" name="email" placeholder="Email: " required>
            <input type="text" id="phone" name="phone" placeholder="Phone: ">
            <input type="password" id="password" name="password" placeholder="Password: "required>
			<button type="submit">Register</button>
		</form>
	</div>
	<div class="form-container sign-in-container">
        <?php
         if ($login_error) {
            echo "<p style='color:red;'>$login_error</p>";
         }
        ?>
        <form action="index.php" method="POST">
			<h1>Sign in</h1>
			<input type="hidden" name="login">
			<input type="text" id="login_username" name="login_username" placeholder="Username: " required>
			<input type="password" id="login_password" name="login_password" placeholder="Password: "required>
			<a href="#">Forgot your password?</a>
			<button type="submit">Login</button>
		</form>
	</div>
	<div class="overlay-container">
		<div class="overlay">
			<div class="overlay-panel overlay-left">
				<h1>Welcome Back!</h1>
				<p>To keep connected with us please login with your personal info</p>
				<button class="ghost" id="signIn">Sign In</button>
			</div>
			<div class="overlay-panel overlay-right">
				<h1>Hello, Friend!</h1>
				<p>Enter your personal details and start journey with us</p>
				<button class="ghost" id="signUp">Sign Up</button>
			</div>
		</div>
	</div>
</div>

<script src="script.js"></script>

</body>
</html>
