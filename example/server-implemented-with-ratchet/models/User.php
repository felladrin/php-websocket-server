<?php
namespace server\models;

use server\ChatServer;

class User
{
    public static function getUserList()
    {
        $clients = ChatServer::getClients();
        $userList = array();

        foreach ($clients as $client)
        {
            $userList[] = array(
                'id' => $client->resourceId,
                'name' => $client->name,
            );
        }

        return $userList;
    }
}