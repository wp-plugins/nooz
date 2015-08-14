<?php

namespace MightyDev\WordPress;

use MightyDev\WordPress\Notice;

class AdminHelper
{
    // todo: method depends on a external class outside of mightydev autoload
    function create_meta_box( $id, $title, $template, array $config = array() )
    {
        $options = array_merge( array( 'id' => $id, 'title' => $title, 'template' => $template ), $config );
        return new \WPAlchemy_MetaBox( $options );
    }

    function create_notice( $message, $class = 'updated', $capability = NULL, $page = NULL )
    {
        return new Notice( $message, $class, $capability, $page );
    }

    function set_menu_position( $slug, $position = 99, $increment = 0.0001, $tries = 200 )
    {
        global $menu;
        foreach ( $menu as $i => $item ) {
            // find one item and break
            if ( stristr( $item[2], $slug ) ) {
                unset( $menu[ $i ] );
                while( --$tries ) {
                    // change menu only if position is available
                    if ( ! isset( $menu[ $position ] ) ) {
                        $menu[ $position ] = $item;
                        ksort( $menu );
                        return;
                    }
                    $position = (string) ( $position + $increment );
                }
                break;
            }
        }
    }
}
