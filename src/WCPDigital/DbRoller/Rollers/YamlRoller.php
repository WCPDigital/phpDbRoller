<?php
namespace WCPDigital\DbRoller\Rollers
{
	use \Exception;
	
	/**
	* YAML DB Roller
	* A utility for converting a JSON Object into SQL.
	*
	* @package DB Roller
	* @author Patrick Purcell
	* @copyright Copyright (c) 2015 WCP Digital
	* @license http://opensource.org/licenses/MIT
	* @link http://www.wcpdigital.com.au
	* @version 1.0.0 <15/09/2015>
	*/
	class YamlRoller extends BaseRoller implements IDbRoller
	{
		/**
		* Build From File.
		*
		* @param string $filePath.
		* @param bool $execute.
		*
		* @return string.
		*/
		public function BuildFromFile( $filePath, $execute = true, $rebuild = false ){
			
			// Ensure the path is readable
			if( !is_readable( $filePath ) )
				throw new Exception('DB Roller: File not readable: '.$filePath );
			
			// Get the File Contents
			$tables = \yaml_parse_file( $filePath );
			if( empty( $tables ) )	
				throw new Exception('DB Roller: File was empty or failed to load: '.$filePath );
			
			
			// Parse the JSON into SQL Table data
			// Return the Build Script
			return $this->build( $tables, $execute, $rebuild );
		}
		
	} 
}