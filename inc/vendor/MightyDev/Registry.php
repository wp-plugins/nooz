<?php

namespace MightyDev;

class Registry
{
    protected static $items;

    private function __construct() {}
    private function __clone() {}

    public static function set( $key, $obj )
    {
        if ( isset( self::$items[$key] ) ) {
            throw new \Exception( sprintf( 'Can not register "%s" key again', $key ) );
        }
        self::$items[$key] = $obj;
    }
    public static function get( $key )
    {
        if ( ! isset( self::$items[$key] ) ) {
            throw new \Exception( sprintf( 'Key "%s" is not registered', $key ) );
        }
        return self::$items[$key];
    }
}
