<?php

/*
Plugin Name: Nooz
Plugin URI: http://mightydev.com/nooz/
Description: Simplified press release and media coverage management for corporate websites.
Author: Mighty Digital
Author URI: http://mightydigital.com
Version: 0.1.0
*/

$nooz = new Nooz;

$nooz->init();

class Nooz
{
	private $options = array (
		'release_slug' => 'newsevents/press-releases',
		'shortcode_count' => 5,
		'shortcode_display' => 'list',
		'shortcode_target' => '_blank',
		'release_css_class' => '',
		'coverage_css_class' => '',
	);

	public $coverage_target = '_blank';

	private $post_type = array (
		'release' => 'nooz_release',
		'coverage' => 'nooz_coverage',
	);

	public function __construct()
	{
		$this->post_type = (object) $this->post_type;
	}

	public function init()
	{
		add_action( 'init', array( $this, 'registerCpt' ) );

        add_action( 'admin_menu', array ( $this, 'adminMenu' ), 999 );
	}

	public function adminMenu()
	{
		global $submenu;

		add_menu_page( 'Press', 'Press', 'edit_posts', 'nooz', null, 'dashicons-megaphone' );

		add_submenu_page( 'nooz', 'Add New', sprintf( '<span class="md-submenu-indent">%s</span>', 'Add New' ), 'edit_posts', 'post-new.php?post_type=' . $this->post_type->release );

		// reposition "Add New" submenu item after "All Releases" submenu item
		array_splice( $submenu['nooz'], 1, 0, array( array_pop( $submenu['nooz'] ) ) );

		add_submenu_page( 'nooz', 'Add New', sprintf( '<span class="md-submenu-indent">%s</span>', 'Add New' ), 'edit_posts', 'post-new.php?post_type=' . $this->post_type->coverage );

		$this->setMenuPosition( 'nooz', '99.0100' );
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
			'rewrite'            => array( 'slug' => $this->options['release_slug'], 'with_front' => false ),
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

		$this->setupShortcode();
	}

	// slug can be a partial string of the slug
	function setMenuPosition( $slug, $position = 99, $increment = 0.0001, $tries = 1000 )
	{
		global $menu;
		foreach ( $menu as $i => $item ) {
			// find one item and break
			if ( stristr( $item[2], $slug ) ) {
				unset( $menu[$i] );
				while( --$tries ) {
					// change menu only if position is available
					if ( ! isset( $menu[$position] )) {
						$menu[$position] = $item;
						ksort($menu);
						return;
					}
					$position = (string) ($position + $increment);
				}
				break;
			}
		}
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
	}

	public function setupShortcode()
	{
		add_shortcode( 'nooz', array( $this, 'shortcode' ) );
		add_shortcode( 'nooz-release', array( $this, 'shortcode' ) );
		add_shortcode( 'nooz-coverage', array( $this, 'shortcode' ) );
	}

	public function shortcode( $atts, $content = null, $tag = null )
	{
		$default_atts = array
		(
			'count' => $this->options['shortcode_count'],
			'type' => $tag, // release, coverage
			'display' => $this->options['shortcode_display'], // list, group
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
					$link_target = $this->coverage_target;

					$external_link = $meta['link'];
					$external_link_target = $this->coverage_target;
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
