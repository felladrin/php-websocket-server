<?php

class ChatController
{
    public function actionSubmitMessage()
    {
        WebSocketRequest::broadcast('message', 'add', array('message' => WebSocketRequest::getParameter('message')));
    }

    public function actionHandshake()
    {
        $sender = WebSocketRequest::$sender;

        $sender->set('name', RandomName::Full());

        $clients = ChatServer::Instance()->getClients();
        $existingUsers = array();

        foreach ($clients as $client)
        {
            if ($client != $sender)
            {
                $existingUsers[] = array(
                    'id' => $client->id,
                    'name' => $client->get('name'),
                );
            }
        }

        WebSocketRequest::reply('user', 'welcome', array(
            'id' => $sender->id,
            'name' => $sender->get('name'),
            'users' => $existingUsers,
        ));
    }
}