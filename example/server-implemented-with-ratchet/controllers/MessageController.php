<?php
namespace server\controllers;

use server\ChatServer;
use server\models\Message;

class MessageController
{
    public function actionSubmit()
    {
        $author = ChatServer::getSender()->name;
        $text = ChatServer::getParameter('message');
        $datetime = date(Message::DATETIME_FORMAT);

        Message::addToHistory($author, $text);
        ChatServer::broadcast('message', 'add', array('author' => $author, 'text' => $text, 'datetime' => $datetime));
    }
}