<?php

namespace WPAlchemy;

class Factory
{
	function createMetaBox( $id, $title, $template, $config = array() )
	{
		$options = array_merge( array( 'id' => $id, 'title' => $title, 'template' => $template ), $config );

		if ( ! class_exists( 'WPAlchemy_MetaBox' ) ) {
			require_once __DIR__ . '/MetaBox.php';
		}

		return new \WPAlchemy_MetaBox( $options );
	}

	function createNotice( $message, $class = 'updated', $capability = null, $view = null )
	{
		return new Notice( $message, $class, $capability, $view );
	}
}
