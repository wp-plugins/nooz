<?php

namespace MightyDev\WordPress;

/**
 * @see http://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices
 */
class Notice
{
    const SUCCESS = 'updated';
    const ERROR = 'error';
    const NAG = 'update-nag';

    protected $message;
    protected $class;
    protected $capability;
    protected $page;

    /**
     * @see https://codex.wordpress.org/Roles_and_Capabilities#Capability_vs._Role_Table
     *
     * @param string $message
     * @param string $type Possible values are: updated (default), update-nag or error.
     * @param string $capability WordPress capability, admin: install_plugins, editor: edit_pages, author: publish_posts, contrib: edit_posts, subscriber: read
     * @param string $page WordPress admin php page
     */
    function __construct( $message, $type = 'updated', $capability = NULL, $page = NULL )
    {
        $this->capability = $capability;
        $this->set_type( $type );
        $this->message = $message;
        $this->page = $page;
    }

    /**
     * @param string $type
     */
    protected function set_type( $type )
    {
        $types = array( 'updated', 'update-nag', 'error' );
        if ( ! in_array( $type, $types ) ) {
            $type = $types[0];
        }
        $this->class = $type;
    }

    public function register()
    {
        add_action( 'admin_notices', array( $this, 'render' ) );
    }

    public function get_content()
    {
        if ( isset( $this->page ) ) {
            global $pagenow;
            if ( $pagenow != $this->page ) {
                return NULL;
            }
        }
        if ( isset( $this->capability ) ) {
            if ( ! current_user_can( $this->capability ) ) {
                return NULL;
            }
        }
        $format = '<div class="%s"><p>%s</p></div>';
        if (false !== strpos( $this->class, 'nag' ) ) {
            $format = '<div class="%s">%s</div>';
        }
        return sprintf( $format, $this->class, $this->message );
    }

    public function render()
    {
        echo $this->get_content();
    }
}
