<?php

namespace MightyDev\WordPress;

class Settings
{
    protected $registered;
    protected $relationships;

    public function __construct()
    {
        $this->registered = array(''=> array());
        $this->relationships = array();
    }

    // todo: type hint array ???
    // register( $id, $obj );
    // register( $id, $parent_id, $obj );
    public function register($id, $parent_id_or_arr, array $arr = null)
    {
        if (empty($parent_id_or_arr) || is_string($parent_id_or_arr)) {
            $this->bind($parent_id_or_arr, $id);
        } else {
            $arr = $parent_id_or_arr;
        }
        $this->registered[$id] = $arr;
    }

    public function unregister($id)
    {
        unset($this->registered[$id]);
        // remove any existing relationships
        foreach($this->relationships as $key => $relationship) {
            if ($id == $relationship['parent'] || $id == $relationship['child']) {
                unset($this->relationships[$key]);
            }
        }
    }

    public function build()
    {
        $config = array();
        foreach($this->relationships as $rel) {
            $id = $rel['child'];
            $parent_id = $rel['parent'];
            if ( empty( $parent_id ) ) {
                $config['items'][$id] = $this->registered[$id];
            } else {
                // todo: what happens if parent doesnt exist
                $node = &$this->find( $parent_id, $config );
                $node['items'][$id] = $this->registered[$id];
            }
        }
        if (1 === count($config['items'])) {
            return array_shift($config['items']);
        }
        return $config['items'];
    }

    public function get( $id )
    {
        if ( isset( $this->registered[$id] ) ) {
            return $this->registered[$id];
        }
        return NULL;
    }

    public function set( $id, array $vars )
    {
        if ( isset( $this->registered[$id] ) ) {
            $this->registered[$id] = $vars;
            return $this->registered[$id];
        }
        return NULL;
    }

    public function update( $id, array $vars )
    {
        if ( isset( $this->registered[$id] ) ) {
            foreach( $vars as $key => $value ) {
                $this->registered[$id][$key] = $value;
            }
            return $this->registered[$id];
        }
        return NULL;
    }

    public function get_submit_button( $text = null )
    {
        ob_start();
        submit_button( $text );
        return ob_get_clean();
    }

    public function get_settings_fields( $option_group )
    {
        ob_start();
        settings_fields( $option_group );
        return ob_get_clean();
    }

    public function get_settings_errors()
    {
        ob_start();
        settings_errors();
        return ob_get_clean();
    }

    protected function bind($parent_id, $child_id)
    {
        // check relationship
        if (!isset($this->registered[$parent_id])) {
            throw new \Exception(sprintf('Parent "%s" is not defined', $parent_id));
        }
        $this->relationships[$parent_id . '.' . $child_id] = array('parent' => $parent_id, 'child' => $child_id);
    }

    protected function unbind($parent_id, $child_id)
    {
        unset($this->relationships[$parent_id . '.' . $child_id]);
    }

    protected function &find( $search, &$arr )
    {
        $found = null;
        foreach( $arr as $id => &$node ) {
            if ( ! is_null( $found ) ) {
                break;
            }
            if ( $id === $search ) {
                $found = &$node;
                break;
            }
            if ( is_array( $node ) ) {
                $found = &$this->find( $search, $node );
            }
        }
        return $found;
    }
}
