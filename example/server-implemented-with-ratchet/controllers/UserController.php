<?php
namespace server\controllers;

use server\ChatServer;
use server\models\Message;
use server\models\User;

class UserController
{
    public function actionSetup()
    {
        ChatServer::reply('user', 'load-user-list', User::getUserList());
        ChatServer::reply('message', 'load-history', Message::getHistory());
    }
}