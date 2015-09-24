<?php
spl_autoload_register(  function( $className ) {

	// If we're not loading a DbRoller class, exit this function
	if( strpos( $className, 'DbRoller\\') !== 0 )
		return;
	
	// Autoloader likes to use Namespaces to search directories
	// We're going to remove the root namespace to ensure no 
	// double-up when adding the file-system location
	$className = str_replace( 'DbRoller\\', '', $className );

	// Convert Namespacing to File Location
	$file = dirname(__FILE__).DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $className ).'.php';
	
	// File exists, require it.
	if( is_readable( $file ) && is_file( $file ) )
		require_once $file;
} );
