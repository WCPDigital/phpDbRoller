<?php
namespace DbRoller\Rollers
{
	use \Exception;
	
	/**
	* JSON DB Roller
	* A utility for converting a JSON Object into SQL.
	*
	* @package DB Roller
	* @author Patrick Purcell
	* @copyright Copyright (c) 2015 WCP Digital
	* @license http://opensource.org/licenses/MIT
	* @link http://www.wcpdigital.com.au
	* @version 1.0.0 <15/09/2015>
	*/
	class JsonRoller extends BaseRoller implements IDbRoller
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
			$dataString = file_get_contents( $filePath );
			if( empty( $dataString ) )	
				throw new Exception('DB Roller: File was empty or failed to load: '.$filePath );
			
			// Parse the JSON
			$tables = json_decode( utf8_encode( trim( $dataString ) ), true, 1024 );
			if( json_last_error() != JSON_ERROR_NONE )
				throw new Exception('DB Roller: JSON failed to decode: '.$filePath );
			
			// Parse the JSON into SQL Table data
			// Return the Build Script
			return $this->build( $tables, $execute, $rebuild );
		}
		
	} 
}