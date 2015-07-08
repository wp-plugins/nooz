<?php

namespace MightyDev\WordPress\Plugin;

use MightyDev\WordPress\Settings;
use \MightyDev\Templating\TemplatingInterface;

abstract class Core {

    protected $plugin_file;
    protected $plugin_title;
    protected $templating;
    protected $settings;
    private $core;
    private $version;

    public function set_options( $default_options )
    {
        foreach( $default_options as $name => $value ) {
            if ( FALSE === get_option( $name ) ) {
                update_option( $name, $value );
            }
        }
    }

    public function get_plugin_file()
    {
        return $this->plugin_file;
    }

    public function set_plugin_file( $plugin_file )
    {
        $this->plugin_file = $plugin_file;
    }

    public function get_templating()
    {
        return $this->templating;
    }

    public function set_templating( TemplatingInterface $templating )
    {
        $this->templating = $templating;
    }

    public function get_settings()
    {
        return $this->settings;
    }

    public function set_settings( Settings $settings )
    {
        $this->settings = $settings;
    }

    public function title( $plugin_title = NULL)
    {
        if ( NULL !== $plugin_title ) {
            $this->plugin_title = $plugin_title;
        }
        return $this->plugin_title;
    }

    public function core( $core = NULL )
    {
        if ( NULL !== $core ) {
            $this->core = $core;
        }
        return $this->core;
    }

    public function version( $version = NULL )
    {
        if ( NULL !== $version ) {
            $this->version = $version;
        }
        return $this->version;
    }

    public function plugin_dir_path()
    {
        return plugin_dir_path( $this->get_plugin_file() );
    }

    public function delete_option_with_prefix( $prefix )
    {
        $ret = FALSE;
        if ( strlen( $prefix ) >= 1 ) {
            global $wpdb;
            $ret = $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE `option_name` LIKE %s", $prefix . '%' ) );
        }
        return $ret;
    }

    public function get_active_tab( $default = 'general', $var = 'tab' )
    {
        global $pagenow;
        $active_tab = isset( $_GET[$var] ) ? $_GET[$var] : $default ;
        if ( 'options.php' == $pagenow && wp_get_referer() ) {
            $url = parse_url( wp_get_referer() );
            $query = wp_parse_args( $url['query'] );
            $active_tab = isset( $query[$var] ) ? $query[$var] : $active_tab ;
        }
        return $active_tab;
    }

    public function get_tab_url( $tab, $var = 'tab' )
    {
        $query = remove_query_arg( array( 'settings-updated', '_wpnonce' ) );
        return esc_url( add_query_arg( $var, $tab, $query ) );
    }

    public function is_tab( $tab, $var = 'tab' )
    {
        return isset( $_GET[$var] ) && $tab == $_GET[$var];
    }

    public function is_nonce( $nonce, $var = '_wpnonce' )
    {
        return isset( $_REQUEST[$var] ) && wp_verify_nonce( $_REQUEST[$var] , $nonce );
    }
}
