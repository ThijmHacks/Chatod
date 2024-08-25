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

session_start();

// Get the logged-in user's ID and username
$user_id = null;
$username = $_SESSION['username'];
$sql = "SELECT id, profile_picture FROM users WHERE username='$username'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    $current_profile_picture = $user['profile_picture'];
}

// Handle profile picture upload
$upload_status = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['profile_picture'])) {
    $target_dir = "../uploads/";
    $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $uploadOk = 1;

    // Check if image file is an actual image or fake image
    $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
    if($check !== false) {
        $uploadOk = 1;
    } else {
        $upload_status = "File is not an image.";
        $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["profile_picture"]["size"] > 500000) {
        $upload_status = "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif" ) {
        $upload_status = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        $upload_status = "Sorry, your file was not uploaded.";
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            // Update the user's profile picture in the database
            $sql = "UPDATE users SET profile_picture='$target_file' WHERE id='$user_id'";
            if ($conn->query($sql) === TRUE) {
                $upload_status = "Profile picture updated successfully!";
                $current_profile_picture = $target_file;
            } else {
                $upload_status = "Error updating profile picture: " . $conn->error;
            }
        } else {
            $upload_status = "Sorry, there was an error uploading your file.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - Change Profile Picture</title>
</head>
<body>

<h2>Settings - Change Profile Picture</h2>

<!-- Display current profile picture -->
<h3>Your Current Profile Picture</h3>
<img src="<?php echo htmlspecialchars($current_profile_picture); ?>" alt="Profile Picture" style="max-width: 150px; max-height: 150px;">

<!-- Form to upload a new profile picture -->
<h3>Upload New Profile Picture</h3>
<form action="./" method="POST" enctype="multipart/form-data">
    <input type="file" name="profile_picture" required>
    <button type="submit">Upload</button>
</form>

<!-- Display status of the upload -->
<?php if (!empty($upload_status)): ?>
    <p><?php echo $upload_status; ?></p>
<?php endif; ?>

</body>
</html>
