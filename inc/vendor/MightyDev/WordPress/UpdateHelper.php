<?php

namespace MightyDev\WordPress;

class UpdateHelper
{
    protected $plugin_file;

    public function __construct( $plugin_file )
    {
        $this->plugin_file = $plugin_file;
    }

    public function disable_wordpress_repository_updates()
    {
        add_filter( 'http_request_args', array( $this, '_disable_wordpress_repository_updates' ), 10, 2 );
    }

    public function _disable_wordpress_repository_updates( $request, $url )
    {
        if ( FALSE !== strpos( $url, '//api.wordpress.org/plugins/update-check/1.1/' ) ) {
            $plugins = json_decode( $request['body']['plugins'], TRUE );
            if ( array_key_exists( plugin_basename( $this->plugin_file ), $plugins['plugins'] ) ) {
                unset( $plugins['plugins'][plugin_basename( $this->plugin_file )] );
            }
            $request['body']['plugins'] = json_encode( $plugins );
        }
        return $request;
    }
}
