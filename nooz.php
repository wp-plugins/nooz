<?php

/*
Plugin Name: Nooz
Plugin URI: http://mightydev.com/nooz/
Description: Simplified press release and media coverage management for corporate websites.
Author: Mighty Digital
Author URI: http://mightydigital.com
Version: 0.2.0
*/

include __DIR__ . '/inc/wpalchemy/Page.php';

$nooz = new Nooz;

$nooz->init();

class Nooz
{
	private $post_type = array (
		'release' => 'nooz_release',
		'coverage' => 'nooz_coverage',
	);

	private $option_name = 'nooz_options';

	public function __construct()
	{
		$this->post_type = (object) $this->post_type;

		$this->default_settings = array (
			'release_slug' => 'news/press-releases',
			'ending' => 'on',
			'date_format' => 'F j<\s\u\p>S</\s\u\p>, Y', // %F %j<sup>%S</sup> %Y
			'shortcode_count' => 5,
			'shortcode_display' => 'list',
			'target' => '_blank',
		);

		$this->settings = array_merge( $this->default_settings, get_option( 'nooz_options', array() ) );
	}

	public function init()
	{
		add_action( 'init', array( $this, 'registerCpt' ) );

		// on settings update flush the rewrite rules
		add_action( 'admin_init', array ( $this, 'flushRewriteRules' ) );
		add_action( 'updated_option', array( $this, 'optionUpdate') );

		// setup menus
		add_action( 'admin_menu', array ( $this, 'adminMenu' ), 999 );

		$this->setupContentFilter();

		$this->setupSettingsPage();

		$this->setupShortcode();

		//$this->setupDefaultPages();
	}

	public function flushRewriteRules()
	{
		if ( true == get_option( 'nooz_options_changed' ) ) {
			flush_rewrite_rules();
			update_option( 'nooz_options_changed', false );
		}
	}

	public function optionUpdate( $option )
	{
		if ( $this->option_name == $option ) {
			update_option( 'nooz_options_changed', true );
		}
	}

	public function setupDefaultPages()
	{
		// setup draft pages
	}

	public function setupContentFilter()
	{
		add_filter( 'the_content', array( $this, 'filterContent' ) );
	}

	public function filterContent($content)
	{
		global $post;
		if ( $this->post_type->release == $post->post_type ) {
			$meta = get_post_meta( $post->ID, '_nooz_release', true );
			$pre_content = null;
			if ( isset( $this->settings['location'] ) ) {
				$content = '<p>' . $this->settings['location'] . ' &mdash; ' . get_the_time( ! empty( $this->settings['date_format'] ) ? $this->settings['date_format'] : $this->default_settings['date_format'] ) . '</p>' . $content;
			}
			if ( isset( $meta['subheadline'] ) ) {
				$content = '<h2>' . $meta['subheadline'] . '</h2>' . $content;
			}
			$content .= wpautop( $this->settings['boilerplate'] );
			if ( 'off' != $this->settings['ending'] ) {
				$content .= "<p>###</p>";
			}
		}
		return $content;
	}

	public function setupSettingsPage()
	{
		$page = new WPAlchemy\Settings\Page(array(
			'title' => 'Settings',
			'option_name' => 'nooz_options',
			'page_slug' => 'nooz',

		));

		// todo: decouple menu creation from page display
		$page->addSubmenuPage('nooz', 'Settings', 'Settings', 'manage_options', 'nooz');

		$shortcode_section = $page->addSection( 'shortcode', 'Shortcode', 'Default shortcode settings, common between press releases as coverage.' );

		$shortcode_section->addNumberField( 'shortcode_count', 'Display Count', 'The number of press releases and coverage to display.', array( 'default_value' => $this->default_settings['shortcode_count'] ) );

		$shortcode_section->addSelectField( 'shortcode_display', 'Display Type', 'How to display press releases and coverage.', array ( array ( 'list', 'List' ), array ( 'group', 'Group' ) ) );

		$release_section = $page->addSection( 'release', 'Press Release', 'Settings for press releases' );

		$release_section->addTextField( 'release_slug', 'URL Rewrite', 'The URL structure for press releases. "{slug}" is the auto-generated part of the URL created when adding a <a href="post-new.php?post_type='. $this->post_type->release .'">new press release</a>.', array( 'before_field' => site_url() . '/', 'default_value' => $this->default_settings['release_slug'], 'after_field' => '/{slug}/' ) );

		$release_section->addTextField( 'location', 'Location', 'The location precedes the press release and helps to orient the reader.', array( 'placeholder' => 'San Francisco, California' ) );

		$release_section->addTextField( 'date_format', 'Date Format', 'The <a href="http://php.net/manual/en/function.date.php" target="_blank">date format</a> to use. The date will be automatically generated after the location.', array( 'default_value' => $this->default_settings['date_format'] ) );

		$release_section->addTextAreaField( 'boilerplate', 'Boilerplate', 'The boilerplate is a few sentences at the end of your press release that describes your organization. This should be used consistently on press materials and written strategically, to properly reflect your organization.' );

		$release_section->addOnOffField( 'ending', 'Ending', 'Add ending mark <strong>###</strong>, common on press releases.', array ( 'default_value' => $this->default_settings['ending'] ) );

		$coverage_section = $page->addSection( 'coverage', 'Press Coverage', 'Settings for press coverage');

		$coverage_section->addTextField( 'target', 'Link Target', 'Default link target for press coverage links.', array('default_value' => $this->default_settings['target'] ) );
	}

	public function adminMenu()
	{
		global $submenu;

		add_menu_page( 'Press', 'Press', 'edit_posts', 'nooz', null, 'dashicons-megaphone' );

		add_submenu_page( 'nooz', 'Add New', sprintf( '<span class="md-submenu-indent">%s</span>', 'Add New' ), 'edit_posts', 'post-new.php?post_type=' . $this->post_type->release );

		// reposition "Add New" submenu item after "All Releases" submenu item
		array_splice( $submenu['nooz'], 1, 0, array( array_pop( $submenu['nooz'] ) ) );

		add_submenu_page( 'nooz', 'Add New', sprintf( '<span class="md-submenu-indent">%s</span>', 'Add New' ), 'edit_posts', 'post-new.php?post_type=' . $this->post_type->coverage );

		\WPAlchemy\Settings\setMenuPosition( 'nooz', '99.0100' );
	}

	public function registerCpt()
	{
		$labels = array(
			'name'               => _x( 'Press Releases', 'post type general name', 'nooz' ),
			'singular_name'      => _x( 'Press Release', 'post type singular name', 'nooz' ),
			//'menu_name'          => _x( 'Wraps3', 'admin menu', 'nooz' ),
			//'name_admin_bar'     => _x( 'Wrap', 'add new on admin bar', 'nooz' ),
			'add_new'            => _x( 'Add New', 'wrap', 'nooz' ),
			'add_new_item'       => __( 'Add New Press Release', 'nooz' ),
			'new_item'           => __( 'New Page', 'nooz' ),
			'edit_item'          => __( 'Edit Release', 'nooz' ),
			'view_item'          => __( 'View Release', 'nooz' ),
			'all_items'          => __( 'All Releases', 'nooz' ),
			'search_items'       => __( 'Search Pages', 'nooz' ),
			'parent_item_colon'  => __( 'Parent Pages:', 'nooz' ),
			'not_found'          => __( 'No pages found.', 'nooz' ),
			'not_found_in_trash' => __( 'No pages found in Trash.', 'nooz' )
		);
		$args = array(
			'labels'             => $labels,
			'public'             => true,
			//'has_archive'        => false
			// show_ui=true because CPT are not editable if show_ui=false
			// https://core.trac.wordpress.org/browser/tags/4.0.1/src/wp-admin/post-new.php#L14
			// https://core.trac.wordpress.org/browser/trunk/src/wp-admin/post-new.php#L14
			//'show_ui'            => true,
			'show_in_menu'       => 'nooz',
			'show_in_admin_bar'  => true,
			//'rewrite'            => false,
			'rewrite'            => array( 'slug' => $this->settings['release_slug'], 'with_front' => false ),
			//'capability_type'    => 'post',
			//'menu_position'      => null,
			//'show_in_nav_menus'  => false, // show in Appearance > Menus
			//'menu_icon'          => 'dashicons-megaphone',
			'supports'           => array( 'title', 'editor', 'author', 'revisions' )
		);
		register_post_type( 'nooz_release', $args );

		$labels = array(
			'name'               => _x( 'Press Coverage', 'press coverage', 'nooz' ),
			'singular_name'      => _x( 'Press Coverage', 'press coverage', 'nooz' ),
			//'menu_name'          => _x( 'Wraps3', 'admin menu', 'nooz' ),
			//'name_admin_bar'     => _x( 'Wrap', 'add new on admin bar', 'nooz' ),
			'add_new'            => _x( 'Add New', 'press coverage', 'nooz' ),
			'add_new_item'       => __( 'Add New Press Coverage', 'nooz' ),
			'new_item'           => __( 'New Press Coverage', 'nooz' ),
			'edit_item'          => __( 'Edit Coverage', 'nooz' ),
			'view_item'          => __( 'View Coverage', 'nooz' ),
			'all_items'          => __( 'All Coverage', 'nooz' ),
			//'search_items'       => __( 'Search Pages', 'nooz' ),
			//'parent_item_colon'  => __( 'Parent Pages:', 'nooz' ),
			'not_found'          => __( 'No press coverage found.', 'nooz' ),
			'not_found_in_trash' => __( 'No press coverage found in Trash.', 'nooz' )
		);
		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'show_ui'            => true, // required because public == false
			'show_in_menu'       => 'nooz',
			'show_in_admin_bar'  => true,
			'rewrite'            => false,
			//'capability_type'    => 'post',
			//'menu_position'      => null,
			//'menu_icon'          => 'dashicons-megaphone',
			'supports'           => array( 'title', 'revisions' )
		);
		register_post_type( 'nooz_coverage', $args );

		$this->coverageMetabox();
	}

	public function coverageMetabox()
	{
		if ( ! class_exists( 'WPAlchemy_MetaBox' ) ) {
			require_once __DIR__ . '/inc/wpalchemy/MetaBox.php';
		}
		$mb = new WPAlchemy_MetaBox(array
		(
			'id' => '_nooz',
			'title' => 'Details',
			'template' => __DIR__ . '/inc/coverage-meta.php',
			'types' => array( 'nooz_coverage' )
		));
		$mb = new WPAlchemy_MetaBox(array
		(
			'id' => '_nooz_release',
			'title' => 'Subheadline',
			'template' => __DIR__ . '/inc/subheadline-meta.php',
			'types' => array( $this->post_type->release ),
			'lock' => 'after_post_title',
			'hide_title' => true,
		));
	}

	public function setupShortcode()
	{
		add_shortcode( 'nooz', array( $this, 'shortcode' ) );
		add_shortcode( 'nooz-release', array( $this, 'shortcode' ) );
		add_shortcode( 'nooz-coverage', array( $this, 'shortcode' ) );
	}

	public function shortcode( $atts, $content = null, $tag = null )
	{
		// todo: use "release_css_class" and "coverage_css_class" settings

		$default_atts = array
		(
			'count' => $this->settings['shortcode_count'],
			'type' => $tag, // release, coverage
			'display' => $this->settings['shortcode_display'], // list, group
			'target' => '',
			'class' => '',
		);

		extract( shortcode_atts( $default_atts, $atts ) );

		$type = stristr( $type, 'coverage' ) ? 'nooz_coverage' : 'nooz_release' ;

		if ('*' == $count) {
			$count = -1;
		}

		$my_posts = get_posts( array( 'post_type' => $type, 'posts_per_page' => $count ) );

		$html = '';

		if ( ! empty( $my_posts ) ) {
			$previous_year = $year = 0;
			$open = false;

			$html_ul = sprintf('<ul class="nooz-list %s %s">', str_replace('_', '-', $type), $class);

			if ( 'list' == $display ) {
				$html .= $html_ul;
			}

			foreach( $my_posts as $my_post ) {
				$year = mysql2date( 'Y', $my_post->post_date );

				$month = mysql2date( 'n', $my_post->post_date );

				$day = mysql2date( 'j', $my_post->post_date );



				if ( 'nooz_coverage' == $type ) {
					$meta = get_post_meta( $my_post->ID, '_nooz', TRUE );
					$link = $meta['link'];
					$link_target = $this->settings['target'];

					$external_link = $meta['link'];
					$external_link_target = $this->settings['target'];
				} else {
					$link = get_permalink( $my_post->ID );
					$link_target = '';
				}

				$external_link_class = '';

				if ( preg_match( '/^http/i', $link ) && ! stristr( $link, 'violin-memory.com') )
				{
					$external_link_class = ' class="redirect-link external"';
				}

				if( 'group' == $display && $year != $previous_year )
				{
					if ( true == $open) {
						$html .= '</ul>';
					}

					$html .= '<h3 class="nooz-group">' . $year . '</h3>' . $html_ul;
					$open = true;
				}

				$previous_year = $year;

				$html .= sprintf( '<li><time datetime="%s">%s</time>', get_the_time( 'Y-m-d', $my_post->ID ), get_the_time( 'M j, Y', $my_post->ID ) );

				$html .= '<a href="' . $link . '" target="' . ( $target ? $target : $link_target )  . '">' . get_the_title( $my_post->ID ) . '</a></li>';
			}

			$html .= '</ul>';
		}

		return $html;
	}
}
