<?php

class UserController
{
    public function actionRequestHistory()
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