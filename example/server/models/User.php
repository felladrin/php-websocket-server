<?php

class User
{
    public static function getUserList()
    {
        $clients = ChatServer::Instance()->getClients();
        $userList = array();

        foreach ($clients as $client)
        {
            $userList[] = array(
                'id' => $client->id,
                'name' => $client->get('name'),
            );
        }

        return $userList;
    }
}