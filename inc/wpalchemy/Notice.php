<?php

namespace WPAlchemy;

// todo:
// http://wptheming.com/2011/08/admin-notices-in-wordpress/
// limit notices to certain admin pages .. global $pagenow; if ( $pagenow == 'plugins.php' )
// check user capabilities .. current_user_can( 'install_plugins' ) .. admin = install_plugins, Editor = edit_pages, Author = publish_posts, Contrib = edit_posts, Subscriber = read

class Notice
{
	protected $message;
	protected $class;
	protected $capability;
	protected $view;

	// http://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices
	// updated, error, update-nag
	function __construct( $message, $class = 'updated', $capability = null, $view = null )
	{
		$this->message( $message );
		$this->class = $class;
		$this->capability = $capability;
		$this->view = $view;
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	public function message( $message = null )
	{
		if ( is_null( $message ) ) {
			return $this->message;
		} else {
			$this->message = $message;
		}
	}

	public function output()
	{
		$format = '<div class="%s"><p>%s</p></div>';
		if (false !== strpos( $this->class, 'nag' ) ) {
			$format = '<div class="%s">%s</div>';
		}
		return sprintf( $format, $this->class, $this->message() );
	}

	public function admin_notices()
	{
		if ( isset( $this->view ) ) {
			global $pagenow;
			if ( $pagenow != $this->view ) {
				return;
			}
		}

		if ( isset( $this->capability ) ) {
			if ( ! current_user_can( $this->capability ) ) {
				return;
			}
		}

		echo $this->output();
	}
}
