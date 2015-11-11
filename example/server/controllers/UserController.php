<?php

class UserController
{
    public function actionSetup()
    {
        WebSocketRequest::reply('user', 'load-user-list', User::getUserList());
        WebSocketRequest::reply('message', 'load-history', Message::getHistory());
    }
}