<?php

namespace WPAlchemy\Settings;

// slug can be a partial string of the slug
function setMenuPosition( $slug, $position = 99, $increment = 0.0001, $tries = 1000 )
{
    global $menu;
    foreach ( $menu as $i => $item ) {
        // find one item and break
        if ( stristr( $item[2], $slug ) ) {
            unset( $menu[$i] );
            while( --$tries ) {
                // change menu only if position is available
                if ( ! isset( $menu[$position] )) {
                    $menu[$position] = $item;
                    ksort($menu);
                    return;
                }
                $position = (string) ($position + $increment);
            }
            break;
        }
    }
}
