<?php

namespace MightyDev\Templating;

class TwigTemplating implements TemplatingInterface
{
    protected $engine;
    protected $array_loader;

    public function __construct( \Twig_Environment $engine, \Twig_Loader_Array $loader )
    {
        $this->engine = $engine;
        $this->array_loader = $loader;
    }

    public function parse( $name, $content = null )
    {
        if ( 1 == func_num_args() ) {
            $name = 'default';
            $content = func_get_arg(0);
        } else {
            $name = func_get_arg(0);
            $content = func_get_arg(1);
        }
        $this->array_loader->setTemplate( $name, $content );
    }

    public function render( $name, $data = null )
    {
        if ( 1 == func_num_args() ) {
            $name = 'default';
            $data = func_get_arg(0);
        } else {
            $name = func_get_arg(0);
            $data = func_get_arg(1);
        }
        return $this->engine->render( $name, $data );
    }

    public function template( $name )
    {
        if ( $this->engine->getLoader()->exists( $name ) ) {
            return $this->engine->getLoader()->getSource( $name );
        }
    }
}
