<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login");
    exit();
}

// Database connection
$servername = "localhost";
$dbUsername = "chatod";
$dbPassword = "";
$database = "chatod";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];
$friend_id = $_GET['friend_id'] ?? null;

if (!$friend_id) {
    echo "No friend selected.";
    exit();
}

// Fetch friend's username
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $friend_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "User not found.";
    exit();
}

$friend = $result->fetch_assoc();
$friend_username = $friend['username'];

// Handle sending a message
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['message'])) {
    $message = $_POST['message'];

    // Insert the message into the database
    $sql = "INSERT INTO chats (sender_id, receiver_id, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $user_id, $friend_id, $message);
    if ($stmt->execute()) {
        // Message sent successfully
    } else {
        echo "Error sending message: " . $conn->error;
    }
}

// Fetch chat history
$sql = "SELECT * FROM chats WHERE
        (sender_id = ? AND receiver_id = ?)
        OR (sender_id = ? AND receiver_id = ?)
        ORDER BY sent_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
$stmt->execute();
$chat_history = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?php echo htmlspecialchars($friend_username); ?></title>
</head>
<body>

<h1>Chat with <?php echo htmlspecialchars($friend_username); ?></h1>

<!-- Display Chat History -->
<div>
    <?php while ($chat = $chat_history->fetch_assoc()): ?>
        <p>
            <strong><?php echo ($chat['sender_id'] == $user_id) ? 'You' : htmlspecialchars($friend_username); ?>:</strong>
            <?php echo htmlspecialchars($chat['message']); ?>
            <br><small><?php echo $chat['sent_at']; ?></small>
        </p>
    <?php endwhile; ?>
</div>

<!-- Message Input Form -->
<form method="POST" action="index.php?friend_id=<?php echo $friend_id; ?>">
    <input type="text" name="message" placeholder="Type your message here" required>
    <button type="submit">Send</button>
</form>

</body>
</html>
