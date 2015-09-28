<?php
namespace{
	
	require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'Translators'.DIRECTORY_SEPARATOR.'IDbTranslator.php';
	require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'Translators'.DIRECTORY_SEPARATOR.'BaseTranslator.php';
	require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'Translators'.DIRECTORY_SEPARATOR.'SQLiteTranslator.php';
	require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'Translators'.DIRECTORY_SEPARATOR.'MSSQLTranslator.php';
	require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'Translators'.DIRECTORY_SEPARATOR.'MySQLTranslator.php';
	
	require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'Rollers'.DIRECTORY_SEPARATOR.'IDbRoller.php';
	require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'Rollers'.DIRECTORY_SEPARATOR.'BaseRoller.php';
	require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'Rollers'.DIRECTORY_SEPARATOR.'JsonRoller.php';
	require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'Rollers'.DIRECTORY_SEPARATOR.'XmlRoller.php';
	
}