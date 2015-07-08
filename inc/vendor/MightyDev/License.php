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
        $this->set_key($key);
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

    public function getStatus()
    {
        if (isset($this->response->error)) {
            // bad key
            if ( 'missing' == $this->response->error) {
                return 'missing';
            // expired
            } else if ( 'expired' == $this->response->error) {
                return 'expired';
            } else if ( 'no_activations_left' == $this->response->error) {
                return 'full';
            }
        }
        return 'unknown';
    }

    public function activate( $key = NULL )
    {
        $this->response = $this->req( 'activate_license', $key );
        if ( FALSE !== $this->response ) {
            if ( isset( $this->response->expires ) ) {
                $this->set_expiration_date( $this->response->expires );
            }
            if ( 'valid' == $this->response->license ) {
                update_option( 'mdnooz_license_response', $this->response );
                update_option( 'mdnooz_license_activated', date( 'Y-m-d' ) );
                return TRUE;
            }
        }
        return FALSE;
    }

    public function deactivate( $key = NULL )
    {
        $this->response = $this->req( 'deactivate_license', $key );
        if ( FALSE !== $this->response && 'deactivated' == $this->response->license ) {
            delete_option( 'mdnooz_license_response' );
            delete_option( 'mdnooz_license_activated' );
            return TRUE;
        }
        return FALSE;
    }

    public function is_valid( $key = NULL )
    {
        $response = $this->req( 'check_license', $key );
        return FALSE !== $response && 'valid' == $response->license;
    }

    protected function set_expiration_date( $expire_date )
    {
        update_option( 'mdnooz_license_expiration', strtotime( $expire_date ) );
    }

    public function get_expiration_date()
    {
        return get_option( 'mdnooz_license_expiration' );
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
                return json_decode(wp_remote_retrieve_body($response));
            }
        }
        return FALSE;
    }
}
