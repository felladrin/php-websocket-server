<?php
namespace server\controllers;

use server\ChatServer;

class CommandController
{
    public function actionDecode()
    {
        $message = ChatServer::getParameter('message');

        $message = substr($message, 1);

        if (!empty($message))
        {
            if (strpos($message, ' ') === false)
            {
                ChatServer::reply('user', 'alert-unknown-command', array('command' => $message));
            }
            else
            {
                list($command, $parameter) = explode(' ', $message, 2);

                switch ($command)
                {
                    case 'nick':
                        if (!empty($parameter))
                        {
                            $oldName = ChatServer::getSender()->name;
                            ChatServer::getSender()->name = $parameter;
                            ChatServer::broadcast('user', 'rename', array('from' => $oldName, 'to' => $parameter, 'id' => ChatServer::getSender()->resourceId));
                        }
                        break;
                    default:
                        ChatServer::reply('user', 'alert-unknown-command', array('command' => $command));
                }
            }
        }
    }
}