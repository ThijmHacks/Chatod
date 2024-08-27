<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login");
    exit();
}

// If logged in, continue
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

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

// Handle Sending Friend Requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_request'])) {
    $friend_username = $_POST['friend_username'];

    // Ensure the user is not trying to send a request to themselves
    if ($friend_username != $username) {
        // Get the friend ID from the username
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $friend_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $friend = $result->fetch_assoc();
            $friend_id = $friend['id'];

            // Check if a friend request already exists
            $sql = "SELECT id FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $user_id, $friend_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                // Insert the friend request
                $sql = "INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $user_id, $friend_id);
                if ($stmt->execute()) {
                    $message = "Friend request sent to " . htmlspecialchars($friend_username) . "!";
                } else {
                    $error = "Error sending friend request: " . $conn->error;
                }
            } else {
                $error = "Friend request already sent to this user.";
            }
        } else {
            $error = "User not found.";
        }
    } else {
        $error = "You cannot send a friend request to yourself.";
    }
}

// Handle Friend Request Actions (Accept/Decline)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];

    if (isset($_POST['accept'])) {
        // Update the friend request status to 'accepted'
        $sql = "UPDATE friend_requests SET status = 'accepted' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $request_id);
        if ($stmt->execute()) {
            // Retrieve sender_id and receiver_id
            $sql = "SELECT sender_id, receiver_id FROM friend_requests WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $request = $result->fetch_assoc();

            $sender_id = $request['sender_id'];
            $receiver_id = $request['receiver_id'];

            // Insert friendship in friends table (bi-directional)
            $sql = "INSERT INTO friends (user_id1, user_id2) VALUES (?, ?), (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiii", $receiver_id, $sender_id, $sender_id, $receiver_id);
            if ($stmt->execute()) {
                $message = "Friend request accepted and friendship established!";
            } else {
                $error = "Error inserting friendship: " . $conn->error;
            }
        } else {
            $error = "Error updating request status: " . $conn->error;
        }
    } elseif (isset($_POST['decline'])) {
        // Decline the friend request
        $sql = "DELETE FROM friend_requests WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $request_id);
        if ($stmt->execute()) {
            $message = "Friend request declined.";
        } else {
            $error = "Error declining the request: " . $conn->error;
        }
    }
}

// Fetch Friend Requests
$sql = "SELECT fr.id AS request_id, u.username FROM friend_requests fr
        JOIN users u ON fr.sender_id = u.id
        WHERE fr.receiver_id = ? AND fr.status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$friendRequests = $stmt->get_result();

// Fetch Friends List
$sql = "SELECT u.username FROM friends f
        JOIN users u ON (f.user_id1 = u.id OR f.user_id2 = u.id)
        WHERE (f.user_id1 = ? OR f.user_id2 = ?) AND u.id != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$friends = $stmt->get_result();

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friends</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- Left Sidebar -->
<div class="left-bar">
    <!-- Content for the left sidebar can be added here -->
</div>

<!-- Section Bar -->
<div class="section-bar">
    <button class="section-btn" onclick="showSection('section1')">Online</button>
    <button class="section-btn" onclick="showSection('section2')">All Friends</button>
    <button class="section-btn" onclick="showSection('section3')">AddFriend</button>
</div>

<!-- Sections Content -->
<div class="sections-container">
    <div id="section1" class="section-content active"><p>Online Friends</p></div>
    <div id="section2" class="section-content">
        <h3>Your Friends</h3>
           <?php if ($friends->num_rows > 0): ?>
               <?php while ($friend = $friends->fetch_assoc()): ?>
                   <p>
                       <a href="../chats?friend_id=<?php $friend['id']; ?>">
                           <?php echo $friend['username']; ?>
                       </a>
                   </p>
               <?php endwhile; ?>
           <?php else: ?>
               <p>You have no friends yet.</p>
           <?php endif; ?>
    </div>
    <div id="section3" class="section-content">
        <!-- Friend Request Input Form -->
        <h3>Send a Friend Request</h3>
        <form method="POST" action="./">
            <label for="friend_username">Enter friend's username:</label>
            <input type="text" id="friend_username" name="friend_username" required>
            <button type="submit" name="send_request">Send Friend Request</button>
        </form>

        <!--Friend Requests-->
        <h3>Friend Requests</h3>
        <?php if ($friendRequests->num_rows > 0): ?>
            <?php while ($request = $friendRequests->fetch_assoc()): ?>
                <strong><?php echo htmlspecialchars($request['username']); ?></strong> wants to be your friend.
                <form method="POST" action="./" style="display:inline; margin-left:10px;">
                    <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                    <button type="submit" name="accept">Accept</button>
                    <button type="submit" name="decline">Decline</button>
                </form><br>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No new friend requests.</p>
        <?php endif; ?>
        <?php if (isset($message)): ?>
            <p style="color: green;"><?php echo $message; ?></p>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>
