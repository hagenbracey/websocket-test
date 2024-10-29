<?php
session_start();
include 'header.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
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

    <body>
        <canvas id="game"></canvas>
        <div id="chat"></div>
        <input type="text" id="message" placeholder="Type a message..." />
        <button id="send">Send</button>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const canvas = document.getElementById("game");
                const ctx = canvas.getContext("2d");
                const squares = {};
                let ws;
                const username = '<?php echo $username ?>';
                const color = getColor(username);

                function getColor(username) {
                    const colors = [
                        '#FF5733', '#33FF57', '#3357FF', '#F1C40F',
                        '#9B59B6', '#E67E22', '#1ABC9C', '#E74C3C'
                    ];
                    const hashString = str => {
                        let hash = 0;
                        for (let i = 0; i < str.length; i++) {
                            hash = (hash << 5) - hash + str.charCodeAt(i);
                        }
                        return Math.abs(hash % colors.length);
                    };
                    return colors[hashString(username)];
                }

                function draw() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    for (const user in squares) {
                        ctx.fillStyle = squares[user].color;
                        ctx.fillRect(squares[user].x, squares[user].y, 50, 50);
                    }
                }

                function connect() {
                    ws = new WebSocket('ws://10.212.101.106:8082');

                    ws.onopen = function() {
                        console.log('WebSocket connection opened.');
                        initSquare();
                    };

                    ws.onmessage = function(event) {
                        const data = JSON.parse(event.data);
                        handleServerMessage(data);
                    };

                    ws.onerror = function(error) {
                        console.error('WebSocket Error:', error);
                    };

                    ws.onclose = function(event) {
                        console.log('WebSocket connection closed. Trying to reconnect...', event);
                        setTimeout(connect, 1000);
                    };
                }

                function initSquare() {
                    const x = Math.random() * (canvas.width - 50);
                    const y = Math.random() * (canvas.height - 50);
                    squares[username] = {
                        x,
                        y,
                        color
                    };

                    const messageData = JSON.stringify({
                        eventType: 'initSquare',
                        username,
                        x,
                        y,
                        color
                    });
                    ws.send(messageData);
                }

                function handleServerMessage(data) {
                    if (data.eventType === 'initSquare') {
                        squares[data.username] = {
                            x: data.x,
                            y: data.y,
                            color: data.color
                        };
                        draw();
                    } else if (data.eventType === 'moveSquare') {
                        if (squares[data.username]) {
                            squares[data.username].x = data.x;
                            squares[data.username].y = data.y;
                            draw();
                        }
                    } else if (data.eventType === 'chat') {
                        const chat = document.getElementById('chat');
                        const newMessage = document.createElement('div');
                        newMessage.innerHTML = `<span style="color:${data.color};">${data.username}:</span> ${data.message}`;
                        chat.appendChild(newMessage);
                        chat.scrollTop = chat.scrollHeight;
                    }
                }

                document.addEventListener('keydown', function(event) {
                    const step = 5;
                    if (squares[username]) {
                        switch (event.key) {
                            case 'w':
                                squares[username].y = Math.max(0, squares[username].y - step);
                                break;
                            case 'a':
                                squares[username].x = Math.max(0, squares[username].x - step);
                                break;
                            case 's':
                                squares[username].y = Math.min(canvas.height - 50, squares[username].y + step);
                                break;
                            case 'd':
                                squares[username].x = Math.min(canvas.width - 50, squares[username].x + step);
                                break;
                        }
                        draw();

                        const messageData = JSON.stringify({
                            eventType: 'moveSquare',
                            username,
                            x: squares[username].x,
                            y: squares[username].y
                        });
                        ws.send(messageData);
                    }
                });

                function sendMessage() {
                    const messageInput = document.getElementById('message');
                    const message = messageInput.value;
                    if (message.trim()) {
                        const messageData = JSON.stringify({
                            eventType: 'chat',
                            username,
                            message,
                            color
                        });
                        ws.send(messageData);
                        messageInput.value = '';
                    }
                }

                document.getElementById('send').onclick = sendMessage;
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