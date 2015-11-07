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

foreach(glob(__DIR__ . '/helpers/*.php') as $helper)
{
    /** @noinspection PhpIncludeInspection */
    require_once $helper;
}

class ChatServer extends WebSocketServer
{
    protected $debugMode = true;

    protected function onMessageRecieved(WebSocketClient $sender, $message)
    {
        WebSocketRequest::decode($sender, $message);
    }

    protected function onClientConnected(WebSocketClient $newClient)
    {
        WebSocketRequest::$sender = $newClient;
        WebSocketRequest::broadcastExcludingSender('user', 'connected', array('id' => $newClient->id));
    }

    protected function onClientDisconnected(WebSocketClient $leftClient)
    {
        WebSocketRequest::$sender = $leftClient;
        WebSocketRequest::broadcastExcludingSender('user', 'disconnected', array('id' => $leftClient->id));
    }
}

try
{
    ChatServer::Instance()->start('127.0.0.1', 8080);
}
catch (Exception $e)
{
    echo 'Fatal exception occured: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine() . "\n";
}