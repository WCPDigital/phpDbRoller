<?php
namespace{
	
	require_once 'Translators'.DIRECTORY_SEPARATOR.'IDbTranslator.php';
	require_once 'Translators'.DIRECTORY_SEPARATOR.'BaseTranslator.php';
	require_once 'Translators'.DIRECTORY_SEPARATOR.'SQLiteTranslator.php';
	require_once 'Translators'.DIRECTORY_SEPARATOR.'MSSQLTranslator.php';
	require_once 'Translators'.DIRECTORY_SEPARATOR.'MySQLTranslator.php';
	
	require_once 'Rollers'.DIRECTORY_SEPARATOR.'IDbRoller.php';
	require_once 'Rollers'.DIRECTORY_SEPARATOR.'BaseRoller.php';
	require_once 'Rollers'.DIRECTORY_SEPARATOR.'JsonRoller.php';
	
}