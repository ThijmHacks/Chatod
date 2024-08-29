function goToChat(friend_id) {
    window.location.href = "http://localhost/chatod/chats/?friend_id=" + friend_id;
} else {
        console.error('Friend ID is undefined');
    }
}
