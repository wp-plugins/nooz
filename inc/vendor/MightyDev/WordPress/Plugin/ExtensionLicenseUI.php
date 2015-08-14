<?php

namespace MightyDev\WordPress\Plugin;

use MightyDev\License;

class ExtensionLicenseUI extends Core
{
    protected $license;
    protected $prefix;
    protected $extension_name;

    public function __construct( $prefix, $extension_name, NoozCore $core, License $license )
    {
        $this->prefix = $prefix;
        $this->extension_name = $extension_name;
        $this->core = $core;
        $this->license = $license;
    }

    public function register()
    {
        add_action( 'admin_init', array( $this, '_config_admin_menu' ) );
    }

    public function _config_admin_menu()
    {
        $settings = $this->core->get_settings();
        // license tab
        if ( 'license' == $this->get_active_tab() ) {
            register_setting( 'settings', sprintf( '%s_license_key', $this->prefix ), array( $this, '_sanitize_license_key') );
            // remove default submit button
            $data = $settings->update( 'settings', array( 'submit' => NULL ) );
        }
        $settings->register( 'license_tab', 'tabs', array(
            'id' => 'license',
            'title' => _x( 'License', 'software license', 'mightydev' ),
            // todo: make "software updates" and "priority support" text links
            'description' => __( 'An active license entitles you to software updates and priority support.', 'mightydev' ),
            'link' => $this->get_tab_url( 'license' ),
        ) );
        $settings->register( 'license_default_section', 'license_tab', array(
            'template' => 'fields.html',
        ) );

        $submit = _x( 'Activate', 'software license', 'mightydev' );
        $description = NULL;
        switch ( get_option( sprintf( '%s_license_status', $this->prefix ) ) ) {
            case 'invalid':
                $description = sprintf( _x( 'License key is <span %s>invalid</span>', 'software license status', 'mightydev' ), 'class="md-status bad"' );
                break;
            case 'valid':
                $submit = _x( 'Deactivate', 'software license', 'mightydev' );
                $description = sprintf( _x( 'License key is <span %s>active</span>', 'software license status', 'mightydev' ), 'class="md-status good"' );
                break;
            case 'expired':
                $submit = _x( 'Deactivate', 'software license', 'mightydev' );
                $description = sprintf( _x( 'License key has <span %s>expired</span>', 'software license status', 'mightydev' ), 'class="md-status bad"' );
                break;
            case 'site_inactive':
                // inactive with no activations left
                if ( 0 === $this->license->get_activations_left() ) {
                    $description = sprintf( _x( 'License key has <span %s>no activations available</span>', 'software license status', 'mightydev' ), 'class="md-status bad"' );
                }
                break;
        }
        $license_key = get_option( sprintf( '%s_license_key', $this->prefix ) );
        if ( empty( $license_key ) ) {
            $description = _x( 'Enter your license key for this extension.', 'software license', 'mightydev' );
        }
        $settings->register( sprintf( '%s_license_field', $this->prefix ), 'license_default_section', array(
            'template' => 'field-license-key.html',
            'name' => sprintf( '%s_license_key', $this->prefix ),
            'label' => sprintf( _x( '%s License Key', 'software license', 'mightydev' ), $this->extension_name ),
            'value' => $license_key,
            'description' => $description,
            'submit' => $submit,
            'submit_name' => sprintf( '%s_submit', $this->prefix ),
        ) );
    }

    public function _sanitize_license_key( $input )
    {
        // run per specific submit button
        if ( isset( $_POST[sprintf( '%s_submit', $this->prefix )] ) ) {
            $this->license->set_key( $input );
            $status_type = 'error';
            $status_message = NULL;
            if ( FALSE === stristr( $_POST[sprintf( '%s_submit', $this->prefix )], 'deactivate' ) ) {
                if ( $this->license->activate() ) {
                    update_option( sprintf( '%s_license_activated', $this->prefix ), date( 'Y-m-d' ) );
                }
            } else {
                if ( $this->license->deactivate() ) {
                    delete_option( sprintf( '%s_license_activated', $this->prefix ) );
                }
            }
            switch( $this->license->get_status() ) {
                case 'invalid':
                    $status_message = __( 'Invalid license.', 'mightydev' );
                    break;
                case 'valid':
                    $status_type = 'updated';
                    $status_message = __( 'License activated.', 'mightydev' );
                    break;
                case 'deactivated':
                    $status_type = 'updated';
                    $status_message = __( 'License deactivated.', 'mightydev' );
                    break;
                case 'no_activations_left':
                    $status_message = __( 'Unable to activate license, activation limit reached.', 'mightydev' );
                    break;
                case 'item_name_mismatch':
                    $status_message = __( 'Unable to activate license.', 'mightydev' );
                    break;
                default:
                    $status_message = __( 'Unable to deactivate license.', 'mightydev' );
                    break;
            }
            update_option( sprintf( '%s_license_status', $this->prefix ), $this->license->get_status() );
            add_settings_error( sprintf( '%s_license_key', $this->prefix ), 'license', $status_message, $status_type );
        }
        return $input;
    }
}
