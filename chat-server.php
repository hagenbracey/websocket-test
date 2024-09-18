<?php
require 'vendor/autoload.php';

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;

class Chat implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($data['username']) && isset($data['message'])) {
            $message = htmlspecialchars($data['message']);
            $username = htmlspecialchars($data['username']);

            echo "Received message: " . $msg . PHP_EOL;

            foreach ($this->clients as $client) {
                $client->send(json_encode(['username' => $username, 'message' => $message]));
            }

        } else {
            echo "Invalid JSON or missing fields: $msg\n";
        }
    }


    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection closed! ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8082,
    '10.0.0.229'
);

echo "WebSocket server started at ws://10.0.0.229:8082\n";
$server->run();
