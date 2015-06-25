<?php

namespace MightyDev\WordPress;

class Updater extends \EDD_SL_Plugin_Updater implements UpdaterInterface
{
    protected $plugin_basename;
    protected $options = array();
    protected $license;

    protected $upgrade_action_name;
    protected $upgrade_nonce_name;

    public function __construct(\MightyDev\License $license, array $options)
    {
        $this->license = $license;
        $this->options = $options;
        $this->plugin_basename = plugin_basename($this->options['plugin_file']);

        // todo: this needs to move up as an option
        $this->upgrade_action_name = 'mdnooz_upgrade';
        $this->upgrade_nonce_name = 'upgrade-plugin_' . $this->plugin_basename;
        // todo: move to watch()
        add_action('update-custom_' . $this->upgrade_action_name, array($this, '_render_upgrade_page'));
    }

    public function watch()
    {
        if (get_option($this->plugin_basename . '_force_upgrade')) {
            $this->options['version'] = '0';
            delete_option($this->plugin_basename . '_force_upgrade');
        }
        // todo: use $options obj to get license key
        $options = array_merge($this->options, array('license' => $this->license->getKey()));
        parent::__construct($this->options['store_url'], $this->options['plugin_file'], $options);
    }

    public function upgrade()
    {
        $nonce = wp_create_nonce($this->upgrade_nonce_name);
        delete_option('_site_transient_update_plugins');
        update_option($this->plugin_basename . '_force_upgrade', true);
        wp_redirect(admin_url('update.php?action=' . $this->upgrade_action_name . '&plugin=' . urlencode($this->plugin_basename) . '&_wpnonce=' . $nonce));
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
