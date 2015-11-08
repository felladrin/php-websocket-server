<?php

class CommandController
{
    public function actionDecode()
    {
        $message = WebSocketRequest::getParameter('message');

        $message = substr($message, 1);

        if (!empty($message))
        {
            if (strpos($message, ' ') === false)
            {
                WebSocketRequest::reply('user', 'alert-unknown-command', array('command' => $message));
            }
            else
            {
                list($command, $parameter) = explode(' ', $message, 2);

                switch ($command)
                {
                    case 'nick':
                        if (!empty($parameter))
                        {
                            $oldName = WebSocketRequest::$sender->get('name');
                            WebSocketRequest::$sender->set('name', $parameter);
                            WebSocketRequest::broadcast('user', 'rename', array('from' => $oldName, 'to' => $parameter, 'id' => WebSocketRequest::$sender->id));
                        }
                        break;
                    default:
                        WebSocketRequest::reply('user', 'alert-unknown-command', array('command' => $command));
                }
            }
        }
    }
}