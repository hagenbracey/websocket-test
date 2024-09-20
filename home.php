<?php
session_start();
include 'header.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    session_destroy();
    header("Location: login.php");
    exit();
}

$username = htmlspecialchars($_SESSION["username"]);
?>
<div class="column has-text-centered">
    <p class="content has-text-centered"><?php echo "greetings, {$_SESSION["username"]}!" ?></p>

    <form action="<?php htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="post">
        <input class="button is-primary is-rounded" type="submit" name="logout" value="log out">
    </form>

    <head>
        <style>
            #chat {
                margin-top: 12px;
                height: 300px;
                border: 1px solid #ccc;
                overflow-y: scroll;
                margin-bottom: 10px;
                word-wrap: break-word;
                white-space: pre-wrap;
            }

            #chat div {
                margin-bottom: 5px;
            }

            #message {
                width: calc(100% - 80px);
                margin-right: 10px;
            }

            #send {
                width: 70px;
            }
        </style>
    </head>

    <body>
        <div id="chat"></div>
        <input type="text" id="message" placeholder="Type a message..." />
        <button id="send">Send</button>

        <script>
            var timeouts = 0;

            // Function to get a color based on the username
            function getColor(username) {
                // Define a color palette
                var colors = [
                    '#FF5733', // Red-Orange
                    '#33FF57', // Green
                    '#3357FF', // Blue
                    '#F1C40F', // Yellow
                    '#9B59B6', // Purple
                    '#E67E22', // Orange
                    '#1ABC9C', // Teal
                    '#E74C3C' // Red
                ];

                // Simple hash function to get a color index based on the username
                function hashString(str) {
                    var hash = 0;
                    for (var i = 0; i < str.length; i++) {
                        var char = str.charCodeAt(i);
                        hash = (hash << 5) - hash + char;
                        hash = hash & hash; // Convert to 32bit integer
                    }
                    return Math.abs(hash % colors.length);
                }

                // Return a color from the palette based on the hashed username
                return colors[hashString(username)];
            }

            document.addEventListener('DOMContentLoaded', function() {
                var ws;
                var username = '<?php echo $username ?>';

                function connect() {
                    ws = new WebSocket('ws://10.212.100.162:8082');

                    ws.onopen = function() {
                        console.log('WebSocket connection opened.');
                        timeouts = 0;
                    };

                    ws.onmessage = function(event) {
                        try {
                            var data = JSON.parse(event.data);
                            var chat = document.getElementById('chat');
                            if (chat) {
                                var newMessage = document.createElement('div');
                                var color = getColor(data.username);
                                newMessage.innerHTML = `<span style="color:${color};">${data.username}:</span> ${data.message}`;
                                chat.appendChild(newMessage);
                                chat.scrollTop = chat.scrollHeight;
                            } else {
                                console.error('Chat element not found.');
                            }
                        } catch (e) {
                            console.error('Failed to process message:', e);
                        }
                    };

                    ws.onerror = function(error) {
                        console.error('WebSocket Error:', error);
                    };

                    ws.onclose = function(event) {
                        console.log('WebSocket connection closed. Trying to reconnect...', event);
                        setTimeout(function() {
                            connect();
                        }, 1000 * timeouts)
                    };
                    timeouts++;
                }

                function sendMessage() {
                    var messageInput = document.getElementById('message');
                    var message = messageInput.value;
                    if (message.trim()) {
                        message = message.substr(0, 250);
                        if (ws.readyState === WebSocket.OPEN) {
                            try {
                                var messageData = JSON.stringify({
                                    username: username,
                                    message: message
                                });
                                ws.send(messageData);
                                messageInput.value = '';
                            } catch (e) {
                                console.error('Failed to send message:', e);
                            }
                        } else {
                            console.error('WebSocket is not open.');
                        }
                    }
                }

                document.getElementById('send').onclick = function() {
                    sendMessage();
                };

                document.getElementById('message').addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        sendMessage();
                    }
                });

                connect();
            });
        </script>

    </body>

    </html>