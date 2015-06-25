<?php

namespace MightyDev\Templating;

interface TemplatingInterface
{
    // todo: make $content and $data required args
    public function parse( $name, $content = null );
    public function render( $name, $data = null );
    public function template( $name );
}
