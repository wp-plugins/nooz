<?php

namespace WPAlchemy\Settings;

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

class Page
{
	public $title;

	private $group_name;

	private $option_name;

	private $page_slug;

	private $options;

	private $submenu;

	private $sections = array();

	private $fields = array();

	private $option = null;

	public function __construct($options)
	{
		$required_options = array( 'title', 'option_name', 'page_slug' );
		foreach( $required_options as $option ) {
			if ( isset( $options[$option] ) ) {
				$this->$option = $options[$option];
			}
		}
		// todo: auto generate this id
		$this->group_name = 'generated_id';
		add_action('admin_init', array($this, 'admin_init'));
	}

	public function addSubmenuPage($parent_slug, $page_title, $menu_title, $capability, $menu_slug)
	{
		$this->submenu = (object) array (
			'parent_slug' => $parent_slug,
			'page_title' => $page_title,
			'menu_title' => $menu_title,
			'capability' => $capability,
			'menu_slug' => $menu_slug,
		);

		add_action( 'admin_menu', array ( $this, 'admin_menu' ), 999 );
	}

	private function getFieldValue($field_id)
	{
		if ( is_null( $this->options ) ) {
			$this->options = get_option($this->option_name);
		}
		if ( isset( $this->options[$field_id] ) ) {
			return $this->options[$field_id];
		}
		return null;
	}

	private function getFieldName($field_id)
	{
		return sprintf('%s[%s]', $this->option_name, $field_id);
	}

	public function admin_init()
	{
		$this->options = get_option($this->option_name);

		// group name can be generated
		register_setting( $this->group_name, $this->option_name );

		foreach ( $this->sections as $section ){
			add_settings_section(
				$section->id, // ID
				$section->title, // Title
				function () use ( $section ) {
					echo $section->description;
				},
				$this->page_slug
			);

			$fields = $section->getFields();
			foreach ( $fields as $field ) {
				$data = array(
					'id' => $field->id,
					'name' => "$this->option_name[$field->id]",
					'value' => $this->getFieldValue($field->id),
					'description' => $field->description,
				);

				if (isset($field->args) && is_array($field->args)) {
					$data = array_merge($data, $field->args);
				}

				add_settings_field(
					$field->id,
					'<label for="'. $data['id'] .'">'.$field->title.'</label>',
					function () use ($data, $field) {
						if ($field->template instanceof InputField) {
							echo $this->template;
						} else if (is_callable($field->template)) {
							call_user_func($field->template, (object)$data, $this->options);
						} else {
							if (file_exists($field->template)) {
								extract($data);
								$name = $id;
								include $field->template;
							} else {
								echo $field->template;
							}
						}
					},
					$this->page_slug, // page
					$section->id
				);
			}
		}
	}

	public function admin_menu()
	{
		add_submenu_page( $this->submenu->parent_slug, $this->submenu->page_title, $this->submenu->menu_title, $this->submenu->capability, $this->submenu->menu_slug, array( $this, 'show' ) );
	}

	public function addSection($id, $title, $description = null, $callback = null)
	{
		$section = new Section($id, $title, $description, $callback);
		array_push( $this->sections, $section );
		return $section;
	}

	// how would a user create a custom section (accordianSection)
	public function addTabbedSection($id, $title, $description = null, $callback = null)
	{
		$section = new TabbedSection($id, $title, $description, $callback);
		array_push( $this->sections, $section );
		return $section;
	}

	public function show()
	{
		//var_dump($this->options);
		?><div class="wrap">
			<h2><?php echo $this->title; ?></h2>
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<?php settings_fields( $this->group_name ); ?>
				<?php do_settings_sections( $this->page_slug ); ?>
				<?php submit_button(); ?>
			</form>
			<?php if ( defined( 'WP_DEBUG' ) && true == WP_DEBUG ) : ?>
				<style> .xdebug-var-dump { overflow:auto; } </style>
				<div id="poststuff">
					<div class="postbox">
						<h3 class="hndle">Debug Information</h3>
						<div class="inside">
							<h4>Option: <span><?php echo $this->option_name; ?></span></h4>
							<?php var_dump( get_option( $this->option_name ) ); ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div><?php
	}
}

class TabbedSection extends Section
{

}

class Section
{
	// todo: make immutable
	public $id;
	public $title;
	public $description;

	protected $template;
	protected $fields = array();
	protected $default_options;

	public function __construct($id, $title, $description, $template)
	{
		$this->id = $id;
		$this->title = $title;
		$this->description = $description;
		$this->template = $template;
		$this->default_options = array (
			'before_field' => null,
			'after_field' => null,
			'default_value' => null,
			'placeholder' => null,
		);
	}

	protected function options( $options )
	{
		return array_merge( $this->default_options, $options );
	}

	public function addField($id, $title, $description, $template = null) {
		array_push( $this->fields, (object) array (
			'id' => $id,
			'title' => $title,
			'description' => $description,
			'template' => $template,
		) );
	}

	public function addNumberField($id, $title, $description, $options = null)
	{
		$options = $this->options( $options );
		array_push( $this->fields, (object) array (
			'id' => $id,
			'title' => $title,
			'description' => $description,
			'args' => $options,
			'template' => function ( $field ) use ( $options ) {
				if ( isset( $options['default_value'] ) && is_null( $field->value ) ) {
					$field->value = $options['default_value'];
				}
				$output = sprintf( '<p><span class="before-field">%s</span>', $options['before_field'] );
				$output .= sprintf( '<input type="number" id="%s" name="%s" value="%s">', $field->id, $field->name, $field->value );
		        $output .= sprintf( '<span class="after-field">%s</span></p>', $options['after_field'] );
				if ( isset( $field->description ) ) {
		            $output .= sprintf( '<p class="description">%s</p>', $field->description);
		        }
				echo $output;
			}
		) );
	}

	public function addTextField( $id, $title, $description, $options = array() )
	{
		$options = $this->options( $options );
		array_push( $this->fields, (object) array (
			'id' => $id,
			'title' => $title,
			'description' => $description,
			'args' => $options,
			'template' => function ( $field ) use ( $options ) {
				if ( isset( $options['default_value'] ) && is_null( $field->value ) ) {
					$field->value = $options['default_value'];
				}
				$output = sprintf( '<p><span class="before-field">%s</span>', $options['before_field'] );
				$output .= sprintf( '<input type="text" class="regular-text" id="%s" name="%s" value="%s" placeholder="%s">', $field->id, $field->name, $field->value, $options['placeholder'] );
		        $output .= sprintf( '<span class="after-field">%s</span></p>', $options['after_field'] );
				if ( isset( $field->description ) ) {
		            $output .= sprintf( '<p class="description">%s</p>', $field->description);
		        }
				echo $output;
			}
		) );
	}

	public function addTextAreaField($id, $title, $description)
	{
		array_push( $this->fields, (object) array (
			'id' => $id,
			'title' => $title,
			'description' => $description,
			'template' => function($field) {
				printf( '<p><textarea class="large-text" id="%s" name="%s" cols="50" rows="10">%s</textarea></p>', $field->id, $field->name, $field->value );
		        if (isset($field->description)) {
		            printf( '<p class="description">%s</p>', $field->description);
		        }
			}
		) );
	}

	public function addSelectField( $id, $title, $description, $values = array(), $options = array() )
	{
		$options = $this->options( $options );
		array_push( $this->fields, (object) array (
			'id' => $id,
			'title' => $title,
			'description' => $description,
			'args' => $options,
			'template' => function ( $field ) use ( $values, $options ) {
				if ( isset( $options['default_value'] ) && is_null( $field->value ) ) {
					$field->value = $options['default_value'];
				}
				$output = sprintf( '<p><span class="before-field">%s</span>', $options['before_field'] );
				$output .= sprintf( '<select class="regular-text" id="%s" name="%s">', $field->id, $field->name );
				foreach ( $values as $value ) {
					$value[1] = isset( $value[1] ) ? $value[1] : $value[0] ;
					$selected = ( $value[0] == $field->value ) ? ' selected' : '' ;
					$output .= sprintf( '<option value="%s"%s>%s</option>', $value[0], $selected, $value[1] );
				}
				$output .= sprintf( '</select><span class="after-field">%s</span></p>', $options['after_field'] );
				if ( isset( $field->description ) ) {
		            $output .= sprintf( '<p class="description">%s</p>', $field->description);
		        }
				echo $output;
			}
		) );
	}

	public function addOnOffField( $id, $title, $description, $args = null )
	{
		array_push( $this->fields, (object) array (
			'id' => $id,
			'title' => $title,
			'description' => $description,
			'template' => function( $field, $settings ) use( $args ) {
				if ( isset( $args['default_value'] ) && is_null( $field->value ) ) {
					$field->value = $args['default_value'];
				}
				$state = ( 'on' === $field->value ) ? ' checked="checked"' : '' ;
				printf( '<input type="hidden" name="%1$s" value="off"><input type="checkbox" id="%1$s" name="%1$s" value="on"%2$s> %3$s', $field->name, $state, $field->description );
			}
		) );
	}

	public function addCheckBoxField($id, $title, $description, $options, $args = null)
	{
		array_push( $this->fields, (object) array (
			'id' => $id,
			'title' => $title,
			'description' => $description,
			'template' => function($field, $settings) use($options, $args) {

				if (is_array($options)) {
					printf( '<input type="hidden" name="%s" value="off">', $field->name );
					foreach ($options as $option) {


						if ( false === $settings  && is_null( $field->value ) && ! empty( $option['default'] ) ) {
							$field->value = $option['value'];

						}
						$state = ( $option['value'] == $field->value ) ? ' checked="checked"' : '' ;
						printf( '<input type="checkbox" id="%s" name="%s" value="%s"%s> %s', $field->id, $field->name, $option['value'], $state, $option['description']);
					}
				}
				if (isset($field->description)) {
		            printf( '<p class="description">%s</p>', $field->description);
		        }
			}
		) );
	}

	public function getFields()
	{
		return $this->fields;
	}
}



class TextField extends InputField
{
	public $placeholder;

	public function get()
	{
		$output = sprintf('<p class="input-field"><input type="text" class="regular-text" id="%s" name="%s" value="%s"></p>', $this->id, $this->id, $this->value);
		if (isset($this->description)) {
			$output .= sprintf('<p class="description">%s</p>', $this->description);
		}
		return $output;

		?><p class="input-field"><input type="text" class="regular-text" id="<?php echo $this->id; ?>" name="<?php echo $this->id; ?>" value="<?php echo $this->value; ?>" /></p>
		<?php if (isset($this->description)) : ?>
			<p class="description"><?php echo $this->description; ?></p>
		<?php endif; ?><?php
	}
}

abstract class InputField
{
	public $id;
	public $name;
	public $value;
	public $label;
	public $description;

	abstract public function get();
}
