<?php

require_once __DIR__ . '/../../WebSocketServer.php';

spl_autoload_register(function($class)
{
    $foldersToAutoload = array('models', 'controllers', 'helpers');

    foreach ($foldersToAutoload as $folder)
    {
        if (file_exists(__DIR__ . "/$folder/$class.php"))
        {
            /** @noinspection PhpIncludeInspection */
            require_once __DIR__ . "/$folder/$class.php";
        }
    }
});

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