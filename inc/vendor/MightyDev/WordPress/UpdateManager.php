<?php

namespace MightyDev\WordPress;

class UpdateManager
{
    protected $plugin_basename;
    protected $options;
    protected $license;
    protected $updater;
    protected $is_pro_version;

    public function __construct($plugin_file, \MightyDev\WordPress\Options $options, \MightyDev\License $license, \MightyDev\WordPress\Updater $updater, $is_pro_version = false)
    {
        $this->plugin_basename = plugin_basename($plugin_file);
        $this->options = $options;
        $this->license = $license;
        $this->updater = $updater;
        $this->is_pro_version = $is_pro_version;
    }

    public function run()
    {
        add_action('admin_init', array($this,'_pre_update_options'));
        if ($this->is_pro_version) {
            add_filter('http_request_args', array($this, '_disable_wordpress_repository_updates'), 10, 2);
            $this->updater->watch();
        }
    }

    public function _disable_wordpress_repository_updates($request, $url)
    {
        if (false !== strpos($url, '//api.wordpress.org/plugins/update-check/1.1/')) {
            $plugins = json_decode($request['body']['plugins'], true);
            if (array_key_exists($this->plugin_basename, $plugins['plugins'])) {
                unset($plugins['plugins'][$this->plugin_basename]);
            }
            $request['body']['plugins'] = json_encode($plugins);
        }
        return $request;
    }

    public function _pre_update_options()
    {
        // options.php
        if (isset($_POST['submit-al'])||isset($_POST['submit-dl'])){
            // todo: remove option_name dependency
            $license_key = isset($_POST['nooz_options']['license']) ? $_POST['nooz_options']['license'] : null;
            if (isset($_POST['submit-al'])) {
                if (!$license_key) {
                    add_settings_error('nooz', 'activate-license', __('Invalid license key', 'nooz'));
                    return;
                }
                $this->license->setKey($license_key);
                if (!$this->license->activate()) {
                    $status = $this->license->getStatus();
                    if ('missing' == $status) {
                        $message = __('Invalid license key', 'nooz');
                    } else if ('expired' == $status) {
                        $message = __('Expired license key, <a href="%s">renew license</a>', 'nooz');
                    } else if ('full' == $status) {
                        $message = __('Unable to activate license, activation limit reached', 'nooz');
                    } else {
                        $message = __('Unable to activate license', 'nooz');
                    }
                    add_settings_error('nooz', 'activate-license', $message);
                    return;
                }
                if (!$this->is_pro_version) {
                    $this->updater->upgrade(); // redirect
                }
                add_settings_error('nooz', 'deactivate-license', __('License activated', 'nooz'), 'updated');
            } else if (isset($_POST['submit-dl'])) {
                $ret = false;
                if ($license_key) {
                    $this->license->setKey($license_key);
                    $ret = $this->license->deactivate();
                }
                if ($ret) {
                    add_settings_error('nooz', 'deactivate-license', __('License deactivated', 'nooz'), 'updated');
                } else {
                    add_settings_error('nooz', 'deactivate-license', __('Unable to deactivate license', 'nooz'));
                }
            }
        }
    }
}
