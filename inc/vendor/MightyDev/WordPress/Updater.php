<?php

namespace MightyDev\WordPress;

use MightyDev\License;

class Updater extends EDD_SL_Plugin_Updater
{
    protected $plugin_basename;
    protected $options = array();
    protected $license;

    protected $upgrade_action_name;
    protected $upgrade_nonce_name;

    public function __construct( License $license, array $options )
    {
        $this->license = $license;
        $this->options = $options;
        //echo $this->options['plugin_file'];
        $this->plugin_basename = plugin_basename( $this->options['plugin_file'] );

        // todo: this needs to move up as an option
        $this->upgrade_action_name = 'mdnooz_upgrade';
        $this->upgrade_nonce_name = 'upgrade-plugin_' . $this->plugin_basename;
        // todo: move to watch()
        add_action('update-custom_' . $this->upgrade_action_name, array($this, '_render_upgrade_page'));
    }

    public function watch()
    {
        /*
        if (get_option($this->plugin_basename . '_force_upgrade')) {
            $this->options['version'] = '0';
            delete_option($this->plugin_basename . '_force_upgrade');
        }*/
        $this->disable_wordpress_repository_updates();
        // todo: use $options obj to get license key
        $options = array_merge( $this->options, array( 'license' => $this->license->get_key() ) );
        parent::__construct( $this->options['store_url'], $this->options['plugin_file'], $options );
    }

    public function disable_wordpress_repository_updates()
    {
        add_filter( 'http_request_args', array( $this, '_disable_wordpress_repository_updates' ), 10, 2 );
    }

    public function _disable_wordpress_repository_updates( $request, $url )
    {
        if ( FALSE !== strpos( $url, '//api.wordpress.org/plugins/update-check/1.1/' ) ) {
            $plugins = json_decode( $request['body']['plugins'], TRUE );
            if ( array_key_exists( $this->plugin_basename, $plugins['plugins'] ) ) {
                unset( $plugins['plugins'][$this->plugin_basename] );
            }
            $request['body']['plugins'] = json_encode( $plugins );
        }
        return $request;
    }

    public function upgrade()
    {
        $nonce = wp_create_nonce($this->upgrade_nonce_name);
        delete_option('_site_transient_update_plugins');
        update_option($this->plugin_basename . '_force_upgrade', true);
        $url = admin_url('update.php?action=' . $this->upgrade_action_name . '&plugin=' . urlencode($this->plugin_basename) . '&_wpnonce=' . $nonce);
        wp_redirect($url);
        exit;
    }

    public function _render_upgrade_page()
    {
        //$plugin = isset($_REQUEST['plugin']) ? trim($_REQUEST['plugin']) : '';

        if ( ! current_user_can('update_plugins') )
            wp_die(__('You do not have sufficient permissions to update plugins for this site.'));

        //check_admin_referer($this->upgrade_nonce_name);

        wp_enqueue_script('updates');
        require_once(ABSPATH . 'wp-admin/admin-header.php');

        $args = array(
            'title' => __('Upgrading Plugin'),
            'nonce' => $this->upgrade_nonce_name,
            'url' => 'update.php?action=' . $this->upgrade_action_name . '&plugin=' . urlencode($this->plugin_basename),
            'plugin' => $this->plugin_basename,
        );

        $upgrader = new \Plugin_Upgrader(new \Plugin_Upgrader_Skin($args));
        $upgrader->upgrade($this->plugin_basename);

        include(ABSPATH . 'wp-admin/admin-footer.php');
    }
}
