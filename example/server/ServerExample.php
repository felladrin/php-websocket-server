<?php

require_once __DIR__ . '/../../WebSocketServer.php';

foreach(glob(__DIR__ . '/controllers/*Controller.php') as $controller)
{
    /** @noinspection PhpIncludeInspection */
    require_once $controller;
}

foreach(glob(__DIR__ . '/models/*.php') as $model)
{
    /** @noinspection PhpIncludeInspection */
    require_once $model;
}

class ServerExample extends WebSocketServer
{
    protected $debugMode = true;

    public function onMessageRecieved(WebSocketClient $sender, $message)
    {
        WebSocketRequest::decode($sender, $message);
    }

    public function onClientConnected(WebSocketClient $newClient)
    {
        $newClient->broadcast(WebSocketRequest::encode('user', 'connected', array('id' => $newClient->id)));
    }

    public function onClientDisconnected(WebSocketClient $leftClient)
    {
        $leftClient->broadcast(WebSocketRequest::encode('user', 'disconnected', array('id' => $leftClient->id)));
    }
}

try
{
    ServerExample::Instance()->start('127.0.0.1', 8080);
}
catch (Exception $e)
{
    echo 'Fatal exception occured: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine() . "\n";
}