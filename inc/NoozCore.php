<?php

namespace MightyDev\WordPress\Plugin;

use MightyDev\WordPress\AdminHelper;

class NoozCore extends Core
{
    protected $release_post_type = 'nooz_release';
    protected $coverage_post_type = 'nooz_coverage';
    protected $admin_helper;

    public function __construct( $plugin_file )
    {
        $this->set_plugin_file( $plugin_file );
    }

    public function set_default_options()
    {
        $this->set_options( array(
            'mdnooz_coverage_target' => '_blank',
            'mdnooz_release_boilerplate' => '',
            'mdnooz_release_date_format' => 'F j, Y',
            'mdnooz_release_ending' => '###',
            'mdnooz_release_location' => '',
            'mdnooz_release_slug' => 'news/press-releases',
            'mdnooz_shortcode_count' => 5,
            // todo: provide a group by option, year or month ... list, group-year, group-month
            'mdnooz_shortcode_display' => 'list',
            'mdnooz_shortcode_date_format' => 'M j, Y',
            'mdnooz_shortcode_use_excerpt' => 'on',
        ) );
    }

    public function set_admin_helper( AdminHelper $admin_helper )
    {
        $this->admin_helper = $admin_helper;
    }

    public function current_admin_colors()
    {
        // see https://core.trac.wordpress.org/browser/tags/4.2.2/src/wp-admin/includes/misc.php#L597
        global $_wp_admin_css_colors;
        $current_color = get_user_option( 'admin_color' );
        if ( empty( $current_color ) || ! isset( $_wp_admin_css_colors[ $current_color ] ) ) {
            $current_color = 'fresh';
        }
        return $_wp_admin_css_colors[ $current_color ];
    }

    public function upgrade_options()
    {
        if ( ! get_option( 'mdnooz_upgrade_options' ) ) {
            $options = get_option( 'nooz_options', array() );
            $map = array(
                'target'             => 'mdnooz_coverage_target',
                'boilerplate'        => 'mdnooz_release_boilerplate',
                'date_format'        => 'mdnooz_release_date_format',
                'ending'             => 'mdnooz_release_ending',
                'location'           => 'mdnooz_release_location',
                'release_slug'       => 'mdnooz_release_slug',
                'shortcode_count'    => 'mdnooz_shortcode_count',
                'shortcode_display'  => 'mdnooz_shortcode_display',
            );
            foreach ( $options as $name => $value ) {
                if ( isset( $map[$name] ) ) {
                    if ( 'ending' == $name && 'on' == $value ) {
                        $value = '###';
                    }
                    update_option( $map[$name], $value );
                }
            }
            delete_option( 'nooz_options' );
            if ( false !== get_option( 'nooz_default_pages' ) ) {
                update_option( 'mdnooz_default_pages', get_option( 'nooz_default_pages' ) );
                delete_option( 'nooz_default_pages' );
            }
            update_option( 'mdnooz_upgrade_options', date( 'Ymd' ) );
        }
    }

    public function get_release_post_type()
    {
        return $this->release_post_type;
    }

    public function get_coverage_post_type()
    {
        return $this->coverage_post_type;
    }

    public function get_release_date_format()
    {
        if ( get_option( 'mdnooz_release_date_format' ) ) {
            return wp_kses_data( strip_tags( get_option( 'mdnooz_release_date_format' ) ) );
        } else {
            return $this->get_default_date_format();
        }
    }

    public function get_shortcode_date_format()
    {
        if ( get_option( 'mdnooz_shortcode_date_format' ) ) {
            return wp_kses_data( strip_tags( get_option( 'mdnooz_shortcode_date_format' ) ) );
        } else {
            return $this->get_default_date_format();
        }
    }

    protected function get_default_date_format()
    {
        return get_option( 'date_format' );
    }

    /**
     * @codeCoverageIgnore
     */
    public function register()
    {
        $this->set_default_options();
        $this->upgrade_options();
        $this->watch_release_slug_update();
        $this->init_cpt();
        $this->create_release_metabox();
        $this->create_coverage_metabox();
        $this->init_admin_menus();
        $this->init_default_pages();
        $this->init_content_filter();
        $this->init_shortcodes();
        add_action( 'admin_enqueue_scripts', array( $this, '_admin_styles_and_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, '_front_styles_and_scripts' ) );
        // runs once on clean install or after an uninstall
        add_action( 'init', array ( $this, '_installed' ) );
    }

    public function _installed()
    {
        if ( FALSE == get_option( 'mdnooz_installed' ) ) {
            flush_rewrite_rules();
            update_option( 'mdnooz_installed', TRUE );
        }
    }

    public function _admin_styles_and_scripts()
    {
        wp_enqueue_style( 'mdnooz-admin', plugins_url( 'inc/assets/admin.css', $this->plugin_file ), array(), $this->version() );
    }

    public function _front_styles_and_scripts()
    {
        wp_enqueue_style( 'mdnooz-front', plugins_url( 'inc/assets/front.css', $this->get_plugin_file() ), array(), $this->version() );
    }

    public function init_cpt()
    {
        add_action( 'init', array( $this, 'create_cpt' ) );
    }

    public function init_admin_menus()
    {
        add_action( 'admin_init', array ( $this, '_config_admin_menus' ) );
        add_action( 'admin_menu', array ( $this, '_create_admin_menus' ) );
    }

    public function init_content_filter()
    {
        add_filter( 'the_content', array( $this, '_filter_release_content' ) );
    }

    public function init_default_pages()
    {
        $option = 'mdnooz_default_pages';
        $option_val = get_option( $option );
        if ( false === $option_val ) {
            if ( isset( $_GET[$option] ) ) {
                update_option( $option, $_GET[$option] );
                if ( 'publish' == $_GET[$option] ) {
                    add_action( 'admin_init', array ( $this, '_create_default_pages' ) );
                }
            } else {
                $url = admin_url( 'edit.php?post_status=publish&post_type=page&' . $option . '=' );
                $message = sprintf( __( 'Create default press pages? Yes, <a href="%s">create pages</a>. No, <a href="%s">dismiss</a>.', 'mdnooz' ), $url . 'publish', $url . 'dismiss' );
                $notice = $this->admin_helper->create_notice( $message, 'update-nag', 'edit_pages' );
                $notice->register();
            }
        }
        return $option_val;
    }

    public function _create_default_pages()
    {
        $format = "<h2>%s</h2>\n[nooz-release]\n<p class=\"nooz-more-link\"><a href=\"/news/press-releases/\">%s</a></p>\n<h2>%s</h2>\n[nooz-coverage]\n<p class=\"nooz-more-link\"><a href=\"/news/press-coverage/\">%s</a></p>";
        $args = array ( __( 'Press Releases', 'mdnooz' ), __( 'More press releases ...', 'mdnooz' ), __( 'Press Coverage', 'mdnooz' ), __( 'More press coverage ...', 'mdnooz' ) );
        $post_id = wp_insert_post( array (
            'post_content' => vsprintf( $format, $args ),
            'post_title' => __( 'News', 'mdnooz' ),
            'post_name' => 'news',
            'post_type' => 'page',
            'post_status' => 'publish',
        ) );
        wp_insert_post( array (
            'post_content' => '[nooz-release count="*"]',
            'post_title' => __( 'Press Releases', 'mdnooz' ),
            'post_name' => 'press-releases',
            'post_type' => 'page',
            'post_parent' => $post_id,
            'post_status' => 'publish',
        ) );
        wp_insert_post( array (
            'post_content' => '[nooz-coverage count="*"]',
            'post_title' => __( 'Press Coverage', 'mdnooz' ),
            'post_name' => 'press-coverage',
            'post_type' => 'page',
            'post_parent' => $post_id,
            'post_status' => 'publish',
        ) );
    }

    public function watch_release_slug_update()
    {
        // on release_slug update, flush the rewrite rules
        add_action( 'updated_option', array( $this, '_option_update') );
        add_action( 'admin_init', array ( $this, '_flush_rewrite_rules' ) );
    }

    public function _flush_rewrite_rules()
    {
        if ( true == get_option( 'mdnooz_release_slug_changed' ) ) {
            flush_rewrite_rules();
            update_option( 'mdnooz_release_slug_changed', false );
        }
    }

    public function _option_update( $option )
    {
        if ( 'mdnooz_release_slug' == $option ) {
            update_option( 'mdnooz_release_slug_changed', true );
        }
    }

    public function _filter_release_content( $content )
    {
        global $post;
        if ( $this->get_release_post_type() == $post->post_type ) {
            $meta = get_post_meta( $post->ID, '_' . $this->get_release_post_type(), true );
            $content = $this->get_templating()->render( 'release-default.html', array(
                'subheadline' => isset( $meta['subheadline'] ) ? $meta['subheadline'] : null,
                'location' => get_option( 'mdnooz_release_location' ),
                'date' => get_the_date( $this->get_release_date_format() ),
                'boilerplate' => trim( wpautop( get_option( 'mdnooz_release_boilerplate' ) ) ),
                'ending' => get_option( 'mdnooz_release_ending' ),
                'content' => $content,
            ) );
        }
        return $content;
    }

    public function init_shortcodes()
    {
        add_shortcode( 'nooz', array( $this, '_list_shortcode' ) );
        add_shortcode( 'nooz-release', array( $this, '_list_shortcode' ) );
        add_shortcode( 'nooz-coverage', array( $this, '_list_shortcode' ) );
    }

    public function _config_admin_menus()
    {
        $active_tab = $this->get_active_tab();
        switch( $active_tab ) {
            case 'coverage':
                register_setting( 'settings', 'mdnooz_coverage_target' );
                break;
            case 'release':
                register_setting( 'settings', 'mdnooz_release_slug' );
                register_setting( 'settings', 'mdnooz_release_location' );
                register_setting( 'settings', 'mdnooz_release_date_format' );
                register_setting( 'settings', 'mdnooz_release_boilerplate' );
                register_setting( 'settings', 'mdnooz_release_ending' );
                break;
            case 'general':
                register_setting( 'settings', 'mdnooz_shortcode_count' );
                register_setting( 'settings', 'mdnooz_shortcode_date_format' );
                register_setting( 'settings', 'mdnooz_shortcode_display' );
                register_setting( 'settings', 'mdnooz_shortcode_use_excerpt' );
                break;
        }
        $this->settings->register( 'settings', null, array(
            'template' => 'settings.html',
            'title' => __( 'Settings', 'mdnooz' ),
            'settings_errors' => $this->settings->get_settings_errors(),
            'settings_fields' => $this->settings->get_settings_fields( 'settings' ),
            'submit' => $this->settings->get_submit_button(),
        ) );
        $this->settings->register( 'tabs', 'settings', array(
            'template' => 'tabs.html',
            'active' => $active_tab,
        ) );
        $this->settings->register( 'general_tab', 'tabs', array(
            'id' => 'general',
            'title' => _x( 'List', 'list of items', 'mdnooz' ),
            'description' => __( 'Default settings for the main press release and coverage list page.', 'mdnooz' ),
            'link' => $this->get_tab_url( 'general' ),
        ) );
        $this->settings->register( 'general_default_section', 'general_tab', array(
            'template' => 'fields.html',
        ) );
        $this->settings->register( 'shortcode_count_field', 'general_default_section', array(
            'template' => 'field-number.html',
            'label' => __( 'Display Count', 'mdnooz' ),
            'description' => __( 'The number of press releases and coverage to display.', 'mdnooz' ),
            'name' => 'mdnooz_shortcode_count',
            'value' => get_option( 'mdnooz_shortcode_count' ),
            'min' => 1,
        ) );
        $this->settings->register( 'shortcode_display_field', 'general_default_section', array(
            'template' => 'field-select.html',
            'label' => __( 'Display Type', 'mdnooz' ),
            'description' => __( 'How to display press releases and coverage.', 'mdnooz' ),
            'name' => 'mdnooz_shortcode_display',
            'value' => get_option( 'mdnooz_shortcode_display' ),
            'options' => array (
                array (
                    'label' => 'List',
                    'value' => 'list',
                ),
                array (
                    'label' => 'Group',
                    'value' => 'group',
                ),
            ),
        ) );
        $this->settings->register( 'shortcode_date_format_field', 'general_default_section', array(
            'template' => 'field-text.html',
            'class' => 'md-tiny-field',
            'name' => 'mdnooz_shortcode_date_format',
            'label' => __( 'Date Format', 'mdnooz' ),
            'description' => __( sprintf( 'The date appearing for each press release and coverage. Leave this blank to use the <a href="%s">default date format</a> as set in WordPress. Learn more about <a href="%s" target="_blank">formatting dates</a>.', '/wp-admin/options-general.php', 'https://codex.wordpress.org/Formatting_Date_and_Time' ), 'mdnooz' ),
            'value' => get_option( 'mdnooz_shortcode_date_format' ),
            'placeholder' => $this->get_default_date_format(),
        ) );
        $this->settings->register( 'shortcode_use_excerpt_field', 'general_default_section', array(
            'template' => 'field-checkbox.html',
            'name' => 'mdnooz_shortcode_use_excerpt',
            'label' => __( 'Display Excerpts', 'mdnooz' ),
            'after_field' => __( 'Enable press release and coverage excerpts.', 'mdnooz' ),
            'description' => __( 'An excerpt will only be used if available for the specific press release or coverage.', 'mdnooz' ),
            'checked' => 'on' == get_option( 'mdnooz_shortcode_use_excerpt' ),
        ) );
        $this->settings->register( 'release_tab', 'tabs', array(
            'id' => 'release',
            'title' => __( 'Press Release', 'mdnooz' ),
            'link' => $this->get_tab_url( 'release' ),
        ) );
        $this->settings->register( 'release_default_section', 'release_tab', array(
            'template' => 'fields.html',
        ) );
        $this->settings->register( 'release_slug_field', 'release_default_section', array(
            'template' => 'field-text.html',
            'id' => 'md-release-slug',
            'label' => __( 'URL Rewrite', 'mdnooz' ),
            'name' => 'mdnooz_release_slug',
            'description' => __( sprintf( 'The URL structure for a press release. Your <a href="%s" target="_blank">cache</a> may need to be cleared if changed.', 'https://codex.wordpress.org/WordPress_Optimization#Caching' ), 'mdnooz' ),
            'value' => get_option( 'mdnooz_release_slug' ),
            'before_field' => site_url() . '/',
            'after_field' => '/{{slug}}/',
        ) );
        $this->settings->register( 'release_location_field', 'release_default_section', array(
            'template' => 'field-text.html',
            'name' => 'mdnooz_release_location',
            'label' => _x( 'Location', 'city/state', 'mdnooz' ),
            'description' => __( 'The location precedes the press release and helps to orient the reader (e.g. San Francisco, CA)', 'mdnooz' ),
            'value' => get_option( 'mdnooz_release_location' ),
        ) );
        $this->settings->register( 'release_date_format_field', 'release_default_section', array(
            'template' => 'field-text.html',
            'class' => 'md-tiny-field',
            'name' => 'mdnooz_release_date_format',
            'label' => __( 'Date Format', 'mdnooz' ),
            'description' => __( sprintf( 'The date follows the location. Leave this blank to use the <a href="%s">default date format</a> as set in WordPress. Learn more about <a href="%s" target="_blank">formatting dates</a>.', '/wp-admin/options-general.php', 'https://codex.wordpress.org/Formatting_Date_and_Time' ), 'mdnooz' ),
            'value' => get_option( 'mdnooz_release_date_format' ),
            'placeholder' => $this->get_default_date_format(),
        ) );
        $this->settings->register( 'release_boilerplate_field', 'release_default_section', array(
            'template' => 'field-textarea.html',
            'name' => 'mdnooz_release_boilerplate',
            'label' => _x( 'Boilerplate', 'boilerplate text/content', 'mdnooz' ),
            'description' => __( 'The boilerplate is a few sentences at the end of your press release that describes your organization. This should be used consistently on press materials and written strategically, to properly reflect your organization. Using HTML in this field is allowed.', 'mdnooz' ),
            'value' => get_option( 'mdnooz_release_boilerplate' ),
        ) );
        $this->settings->register( 'release_ending_field', 'release_default_section', array(
            'template' => 'field-text.html',
            'class' => 'md-tiny-field',
            'name' => 'mdnooz_release_ending',
            'label' => _x( 'Ending', 'an ending mark/the end', 'mdnooz' ),
            'value' => get_option( 'mdnooz_release_ending' ),
            'description' => __( 'The ending mark signifies the absolute end of the press release (e.g. ###, END, XXX, -30-).', 'mdnooz' ),
        ) );
        $this->settings->register( 'coverage_tab', 'tabs', array(
            'id' => 'coverage',
            'title' => __( 'Press Coverage', 'mdnooz' ),
            'link' => $this->get_tab_url( 'coverage' ),
        ) );
        $this->settings->register( 'coverage_default_section', 'coverage_tab', array(
            'template' => 'fields.html',
        ) );
        $this->settings->register( 'coverage_target_field', 'coverage_default_section', array(
            'template' => 'field-text.html',
            'id' => 'md-coverage-target',
            'name' => 'mdnooz_coverage_target',
            'label' => _x( 'Link Target', 'Internet Link URL target', 'mdnooz' ),
            'value' => get_option( 'mdnooz_coverage_target' ),
            'description' => __( 'Default link target for press coverage links.', 'mdnooz' ),
        ) );
    }

    public function _create_admin_menus()
    {
        global $submenu;
        $menu_slug = 'nooz';
        $parent_menu_slug = $menu_slug;
        add_menu_page( $this->title(), $this->title(), 'edit_posts', $menu_slug, null, 'dashicons-megaphone' );
        $add_new_release_text = __( 'Add New Release', 'mdnooz' );
        add_submenu_page( $parent_menu_slug, $add_new_release_text, $add_new_release_text, 'edit_posts', 'post-new.php?post_type=' . $this->release_post_type );
        // reposition "Add New" submenu item after "All Releases" submenu item
        array_splice( $submenu[$menu_slug], 1, 0, array( array_pop( $submenu[$menu_slug] ) ) );
        $add_new_coverage_text = __( 'Add New Coverage', 'mdnooz' );
        add_submenu_page( $parent_menu_slug, $add_new_coverage_text, $add_new_coverage_text, 'edit_posts', 'post-new.php?post_type=' . $this->coverage_post_type );
        $this->admin_helper->set_menu_position( 'nooz', '99.0100' );
        $title = _x( 'Settings', 'Admin settings page', 'mdnooz' );
        add_submenu_page( $parent_menu_slug, $title, $title, 'manage_options', $menu_slug, array( $this, '_render_settings_page' ) );
    }

    /**
     * @codeCoverageIgnore
     */
    public function _render_settings_page()
    {
        $config = $this->settings->build();
        echo $this->get_templating()->render( $config['template'], $config );
    }

    public function create_cpt()
    {
        $menu_slug = 'nooz';
        $labels = array(
            'name'               => _x( 'Press Releases', 'post type general name', 'mdnooz' ),
            'singular_name'      => _x( 'Press Release', 'post type singular name', 'mdnooz' ),
            'add_new'            => _x( 'Add New', 'press release', 'mdnooz' ),
            'add_new_item'       => __( 'Add New Press Release', 'mdnooz' ),
            'new_item'           => __( 'New Page', 'mdnooz' ),
            'edit_item'          => __( 'Edit Press Release', 'mdnooz' ),
            'view_item'          => __( 'View Press Release', 'mdnooz' ),
            'all_items'          => __( 'All Releases', 'mdnooz' ),
            'not_found'          => __( 'No press releases found.', 'mdnooz' ),
            'not_found_in_trash' => __( 'No press releases found in Trash.', 'mdnooz' )
        );
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            // show_ui=true (default) because CPT are not editable if show_ui=false
            // https://core.trac.wordpress.org/browser/tags/4.0.1/src/wp-admin/post-new.php#L14
            // https://core.trac.wordpress.org/browser/trunk/src/wp-admin/post-new.php#L14
            'show_in_menu'       => $menu_slug,
            'show_in_admin_bar'  => true,
            'rewrite'            => array( 'slug' => get_option( 'mdnooz_release_slug' ), 'with_front' => false ),
            'supports'           => array( 'title', 'editor', 'excerpt', 'author', 'revisions' )
        );
        register_post_type( $this->release_post_type, $args );

        $labels = array(
            'name'               => _x( 'Press Coverage', 'press coverage', 'mdnooz' ),
            'singular_name'      => _x( 'Press Coverage', 'press coverage', 'mdnooz' ),
            'add_new'            => _x( 'Add New', 'press coverage', 'mdnooz' ),
            'add_new_item'       => __( 'Add New Press Coverage', 'mdnooz' ),
            'new_item'           => __( 'New Press Coverage', 'mdnooz' ),
            'edit_item'          => __( 'Edit Coverage', 'mdnooz' ),
            'view_item'          => __( 'View Coverage', 'mdnooz' ),
            'all_items'          => __( 'All Coverage', 'mdnooz' ),
            'not_found'          => __( 'No press coverage found.', 'mdnooz' ),
            'not_found_in_trash' => __( 'No press coverage found in Trash.', 'mdnooz' )
        );
        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true, // required because public == false
            'show_in_menu'       => $menu_slug,
            'show_in_admin_bar'  => true,
            'rewrite'            => false,
            'supports'           => array( 'title', 'excerpt', 'revisions' )
        );
        register_post_type( $this->coverage_post_type, $args );
    }

    public function create_release_metabox()
    {
        $options = array(
            'types' => array( $this->release_post_type ),
            'lock' => 'after_post_title',
            'hide_title' => true
        );
        $this->admin_helper->create_meta_box( '_' . $this->release_post_type, 'Subheadline', dirname( __FILE__ ) . '/templates/subheadline-meta.php', $options );
    }

    public function create_coverage_metabox()
    {
        $options = array(
            'types' => array( $this->coverage_post_type )
        );
        // todo: consider renaming _nooz to _nooz_coverage .. needs backward-compatibility consideration
        $this->admin_helper->create_meta_box( '_nooz', 'Details', dirname( __FILE__ ) . '/templates/coverage-meta.php', $options );
    }

    public function _list_shortcode( $atts, $content = null, $tag = null )
    {
        return $this->get_templating()->render( 'list-default.html', $this->get_list_shortcode_data( $atts, $content, $tag ) );
    }

    public function get_list_shortcode_data( $atts, $content, $tag )
    {
        $default_atts = array(
            'class' => '',
            'count' => get_option( 'mdnooz_shortcode_count' ),
            'date_format' => $this->get_shortcode_date_format(),
            'display' => get_option( 'mdnooz_shortcode_display' ), // list, group
            'target' => '',
            'type' => $tag, // release, coverage
            'use_excerpt' => get_option( 'mdnooz_shortcode_use_excerpt' ),
        );
        $atts = shortcode_atts( $default_atts, $atts );
        $type = stristr( $atts['type'], 'coverage' ) ? $this->coverage_post_type : $this->release_post_type ;
        $data = array(
            'type' => str_replace( '_', '-', $type ),
            'css_classes' => $atts['class'],
            'items' => array(),
            'groups' => array(),
        );
        // todo: use a pagination strategy to query posts
        $posts = get_posts( array(
            'post_type' => $type,
            'posts_per_page' => ( '*' == $atts['count'] ) ? -1 : $atts['count']
        ) );
        if ( ! empty( $posts ) ) {
            foreach( $posts as $post ) {
                $link = '';
                $link_target = '';
                $source = NULL;
                if ( $this->coverage_post_type == $type ) {
                    $meta = get_post_meta( $post->ID, '_nooz', TRUE );
                    if( isset( $meta['link'] ) ) {
                        $link = $meta['link'];
                        $link_target = get_option( 'mdnooz_coverage_target' );
                    }
                    if ( isset( $meta['source'] ) ) {
                        $source = $meta['source'];
                    }
                } else { // if release post type
                    $link = get_permalink( $post->ID );
                }
                $item = array(
                    'title' => get_the_title( $post->ID ),
                    'link' => $link,
                    'source' => $source,
                    'target' => $atts['target'] ? $atts['target'] : $link_target,
                    'datetime' => get_the_time( 'Y-m-d', $post->ID ),
                    'datetime_formatted' => get_the_date( $atts['date_format'], $post->ID ),
                );
                if ( $this->is_truthy( $atts['use_excerpt'] ) ) {
                    $item['excerpt'] = $post->post_excerpt;
                }
                if ( 'group' == $atts['display'] ) {
                    $year = mysql2date( 'Y', $post->post_date );
                    $month = mysql2date( 'n', $post->post_date );
                    if ( ! isset( $data['groups'][$year] ) ) {
                        $data['groups'][$year] = array(
                            'title' => $year,
                            'items' => array(),
                        );
                    }
                    $data['groups'][$year]['items'][] = $item;
                } else {
                    $data['items'][] = $item;
                }
            }
        }
        return $data;
    }

    protected function is_truthy( $val )
     {
        return TRUE === $val || in_array( $val, array( 'on', 'yes', '1', 1, 'true', 'enable', 'enabled', 'ok' ) );
    }

    public function uninstall()
    {
        $this->delete_option_with_prefix( 'nooz' );
        $this->delete_option_with_prefix( 'mdnooz' );
    }
}
