<?php

class Message
{
    const DATETIME_FORMAT = 'M j, H:i';

    private static $history = array();

    public static function addToHistory($author, $text)
    {
        if (count(static::$history) >= 25)
        {
            array_shift(static::$history);
        }

        array_push(static::$history, array(
            'author' => $author,
            'text' => $text,
            'datetime' => date(static::DATETIME_FORMAT)
        ));
    }

    public static function getHistory()
    {
        return static::$history;
    }
}