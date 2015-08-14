<?php // 3be197cdb704a5fa667d2e7f508ecf53

namespace MightyDev;

class License
{
    protected $key;
    protected $api_url;
    protected $item_name;
    protected $response;

    public function __construct( $item_name, $api_url, $key = NULL )
    {
        $this->set_name( $item_name );
        $this->set_api_url( $api_url );
        $this->set_key( $key );
    }

    public function set_name( $name )
    {
        $this->item_name = $name;
    }

    public function get_name()
    {
        return $this->item_name;
    }

    public function set_api_url( $api_url )
    {
        $this->api_url = $api_url;
    }

    public function get_api_url()
    {
        return $this->api_url;
    }

    public function set_key( $key )
    {
        $this->key = $key;
    }

    public function get_key()
    {
        return $this->key;
    }

    public function get_status()
    {
        $status = 'unknown';
        if ( isset( $this->response->error ) ) {
            // missing, expired, no_activations_left, failed, item_name_mismatch
            $status = $this->response->error;
        } else if ( isset( $this->response->license ) ) {
            // valid, deactivated, site_inactive
            $status = $this->response->license;
        }
        return $status;
    }

    public function get_expiration_date()
    {
        if ( isset( $this->response->expires ) ) {
            return strtotime( $this->response->expires );
        }
    }

    public function get_activations_left()
    {
        if ( isset( $this->response->activations_left ) ) {
            return $this->response->activations_left;
        }
    }

    public function activate( $key = NULL )
    {
        $this->response = $this->req( 'activate_license', $key );
        return FALSE !== $this->response && 'valid' == $this->response->license;
    }

    public function deactivate( $key = NULL )
    {
        $this->response = $this->req( 'deactivate_license', $key );
        return FALSE !== $this->response && 'deactivated' == $this->response->license;
    }

    public function is_valid( $key = NULL )
    {
        $response = $this->req( 'check_license', $key );
        return FALSE !== $response && 'valid' == $response->license;
    }

    public function check( $key = NULL )
    {
        $this->response = $this->req( 'check_license', $key );
        return $this->get_status();
    }

    protected function req( $action, $key = NULL )
    {
        $key = isset( $key ) ? $key : $this->get_key();
        if ( $key ) {
            $params = array(
                'edd_action' => $action,
                'license' => $key,
                'item_name' => urlencode( $this->get_name() ),
            );
            $response = wp_remote_get( add_query_arg( $params, $this->get_api_url() ), array( 'timeout' => 15, 'sslverify' => FALSE ) );
            if ( ! is_wp_error( $response ) ) {
                return json_decode( wp_remote_retrieve_body( $response ) );
            }
        }
        return FALSE;
    }
}
