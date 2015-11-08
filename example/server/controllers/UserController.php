<?php

class UserController
{
    public function actionRename()
    {
        $newName = WebSocketRequest::getParameter('name');
        WebSocketRequest::$sender->set('name', $newName);
        WebSocketRequest::broadcast('user', 'rename', array('name' => $newName));
    }

    public function actionSetup()
    {
        WebSocketRequest::reply('user', 'load-user-list', User::getUserList());
        WebSocketRequest::reply('message', 'load-history', Message::getHistory());
    }
}