<?php // 3be197cdb704a5fa667d2e7f508ecf53

namespace MightyDev;

class License
{
    protected $storage;
    protected $key;
    protected $api_url;
    protected $item_name;
    protected $response;

    public function __construct($storage, $item_name, $api_url, $key = null)
    {
        $this->storage = $storage;
        $this->item_name = $item_name;
        $this->api_url = $api_url;
        $this->setKey($key);
    }

    public function setKey($key)
    {
        $this->key = $key;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getStatus()
    {
        if (isset($this->response->error)) {
            // bad key
            if ('missing' == $this->response->error) {
                return 'missing';
            // expired
            } else if ('expired' == $this->response->error) {
                return 'expired';
            } else if ('no_activations_left' == $this->response->error) {
                return 'full';
            }
        }
        return 'unknown';
    }

    public function activate($key = null)
    {
        if ($this->storage->get('license_activated')) {
            var_dump($this->storage->get('activate_license_response', array()));exit;
            $this->response = $this->storage->get('activate_license_response', array());
            return true;
        }
        $this->response = $this->req('activate_license', $key);
        if (false !== $this->response) {
            if (isset($this->response->expires)) {
                $this->setExpirationDate($this->response->expires);
            }
            if ('valid' == $this->response->license) {
                $this->storage->set('activate_license_response', $this->response);
                $this->storage->set('license_activated', 1);
                return true;
            }
        }
        return false;
    }

    public function deactivate($key = null)
    {
        $this->response = $this->req('deactivate_license', $key);
        if (false !== $this->response && 'deactivated' == $this->response->license) {
            $this->storage->delete('activate_license_response');
            $this->storage->delete('license_activated');
            return true;
        }
        return false;
    }

    public function isValid()
    {
        $data = $this->req('check_license');
        if (false !== $data && 'valid' == $data->license) {
            return true;
        }
        return false;
    }

    protected function setExpirationDate($expire_date)
    {
        update_option($this->prefix . '_license_expiration_date', strtotime($expire_date));
    }

    public function getExpirationDate()
    {
        return get_option($this->id . '_license_expiration_date');
    }

    protected function req($action, $key = null)
    {
        $key = isset($key) ? $key : $this->getKey();
        if ($key) {
            $params = array(
                'edd_action' => $action,
                'license' => $key,
                'item_name' => urlencode($this->item_name),
            );
            $response = wp_remote_get(add_query_arg($params, $this->api_url), array('timeout' => 15, 'sslverify' => false));
            //var_dump($response);
            if (!is_wp_error($response)) {
                return json_decode(wp_remote_retrieve_body($response));
            }
        }
        return false;
    }
}
