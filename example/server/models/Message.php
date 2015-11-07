<?php

class Message
{
    private static $history = array();

    public static function addToHistory($author, $text)
    {
        if (count(static::$history) > 50)
        {
            unset(static::$history[0]);
        }

        array_push(static::$history, array(
            $author,
            $text,
            date('Y-m-d H:s:i')
        ));
    }

    public static function getHistory()
    {
        return static::$history;
    }
}