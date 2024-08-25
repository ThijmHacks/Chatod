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

// Get the logged-in user's ID
$user_id = null;
$username = $_SESSION['username'];
$sql = "SELECT id FROM users WHERE username='$username'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
}

// Handle sending a friend request
$friend_request_status = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['friend_username'])) {
    $friend_username = $_POST['friend_username'];

    // Find the receiver's user ID
    $sql = "SELECT id FROM users WHERE username='$friend_username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $friend = $result->fetch_assoc();
        $receiver_id = $friend['id'];

        // Check if a friend request already exists
        $sql = "SELECT * FROM friend_requests WHERE sender_id='$user_id' AND receiver_id='$receiver_id'";
        $result = $conn->query($sql);

        if ($result->num_rows == 0) {
            // Insert the friend request
            $sql = "INSERT INTO friend_requests (sender_id, receiver_id) VALUES ('$user_id', '$receiver_id')";
            if ($conn->query($sql) === TRUE) {
                $friend_request_status = "Friend request sent to $friend_username!";
            } else {
                $friend_request_status = "Error sending friend request: " . $conn->error;
            }
        } else {
            $friend_request_status = "You have already sent a friend request to $friend_username.";
        }
    } else {
        $friend_request_status = "User $friend_username not found.";
    }
}

// Handle accepting a friend request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accept_friend'])) {
    $request_id = $_POST['request_id'];
    $sql = "UPDATE friend_requests SET status='accepted' WHERE id='$request_id'";
    $conn->query($sql);
}

// Handle declining a friend request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['decline_friend'])) {
    $request_id = $_POST['request_id'];
    $sql = "UPDATE friend_requests SET status='declined' WHERE id='$request_id'";
    $conn->query($sql);
}

// Retrieve pending friend requests for the logged-in user
$pending_requests = [];
$sql = "SELECT friend_requests.id, users.username
        FROM friend_requests
        JOIN users ON friend_requests.sender_id = users.id
        WHERE friend_requests.receiver_id = '$user_id'
        AND friend_requests.status = 'pending'";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pending_requests[] = $row;
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome, <?php echo $_SESSION['full_name']; ?></title>
</head>
<body>

<h2>Welcome, <?php echo $_SESSION['full_name']; ?>!</h2>

<!-- Friend Request Input Bar -->
<h3>Send a Friend Request</h3>
<form action="./" method="POST">
    <label for="friend_username">Friend's Username:</label>
    <input type="text" name="friend_username" id="friend_username" required>
    <button type="submit">Send Request</button>
</form>

<!-- Display status of friend request sending -->
<?php if (!empty($friend_request_status)): ?>
    <p><?php echo $friend_request_status; ?></p>
<?php endif; ?>

<!-- Display Pending Friend Requests -->
<h3>Pending Friend Requests</h3>
<?php if (count($pending_requests) > 0): ?>
    <?php foreach ($pending_requests as $request): ?>
        <div>
            <?php if (is_array($request) && isset($request['username'])): ?>
                <strong><?php echo htmlspecialchars($request['username']); ?></strong> wants to be your friend.
                <form action="./" method="POST" style="display:inline;">
                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                    <button type="submit" name="accept_friend">Accept</button>
                </form>
                <form action="./" method="POST" style="display:inline;">
                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                    <button type="submit" name="decline_friend">Decline</button>
                </form>
            <?php else: ?>
                <p>An error occurred or no valid friend request data found.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No pending friend requests.</p>
<?php endif; ?>

</body>
</html>
