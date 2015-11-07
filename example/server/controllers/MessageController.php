<?php

class MessageController
{
    public function actionSubmit()
    {
        $author = WebSocketRequest::$sender->get('name');
        $text = WebSocketRequest::getParameter('message');

        Message::addToHistory($author, $text);
        WebSocketRequest::broadcast('message', 'add', array('author' => $author, 'text' => $text));
    }
}