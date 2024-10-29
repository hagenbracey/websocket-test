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
    protected $squares; // Store user squares

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->squares = []; // Initialize squares array
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";

        // Send existing squares to the new client
        foreach ($this->squares as $username => $square) {
            $conn->send(json_encode([
                'eventType' => 'initSquare',
                'username' => $username,
                'x' => $square['x'],
                'y' => $square['y'],
                'color' => $square['color'],
            ]));
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($data['username'])) {
            switch ($data['eventType']) {
                case 'initSquare':
                    $this->squares[$data['username']] = [
                        'x' => $data['x'],
                        'y' => $data['y'],
                        'color' => $data['color']
                    ];
                    // Notify all clients of the new square
                    foreach ($this->clients as $client) {
                        $client->send(json_encode($data));
                    }
                    break;

                case 'moveSquare':
                    if (isset($this->squares[$data['username']])) {
                        $this->squares[$data['username']]['x'] = $data['x'];
                        $this->squares[$data['username']]['y'] = $data['y'];
                        // Notify all clients of the moved square
                        foreach ($this->clients as $client) {
                            $client->send(json_encode($data));
                        }
                    }
                    break;

                case 'chat':
                    foreach ($this->clients as $client) {
                        $client->send(json_encode($data));
                    }
                    break;
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
    '0.0.0.0'
);

echo "WebSocket server started at ws://10.212.101.106:8082\n";
$server->run();
