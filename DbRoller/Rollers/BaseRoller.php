<?php
namespace DbRoller\Rollers
{
	use \PDO;
	use \Exception;
	use \DbRoller\Translators\IDbTranslator;
	
	/**
	* Baser DB Roller
	* A utility for converting a JSON Object into SQL.
	*
	* @package DB Roller
	* @author Patrick Purcell
	* @copyright Copyright (c) 2015 WCP Digital
	* @license http://opensource.org/licenses/MIT
	* @link http://www.wcpdigital.com.au
	* @version 1.0.0 <15/09/2015>
	*/
	class BaseRoller
	{
		const PRIMARY_KEY = 'PRIMARY';
		const UNIQUE_KEY = 'UNIQUE';
		const INDEX = 'INDEX';
		const KEY = 'KEY';
		
		/**
		* Database Connection.
		*
		* @var PDO
		*/	
		protected $conn = null;
		
		/**
		* Database Translator.
		*
		* @var IDbTranslator
		*/	
		protected $trans = null;
		
		/**
		* Constructor.
		*
		* @param PDO $conn             The PDO connection object. Ensure exceptions are enabled.
		* @param IDbTranslator $trans  The DB Vendor specific data translator and interpreter.
		*
		* @return void.
		*/			
		public function __construct( PDO $conn, IDbTranslator $trans ) {
			$this->conn = $conn; 
			$this->trans = $trans;
		}
	
	} // BaseRoller
	
}