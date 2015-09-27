<?php
namespace DbRoller\Rollers
{
	use \Exception;
	
	/**
	* XML DB Roller
	* A utility for converting a XML Object into SQL.
	*
	* @package DB Roller
	* @author Patrick Purcell
	* @copyright Copyright (c) 2015 WCP Digital
	* @license http://opensource.org/licenses/MIT
	* @link http://www.wcpdigital.com.au
	* @version 1.0.0 <15/09/2015>
	*/
	class XmlRoller extends BaseRoller implements IDbRoller
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
			
			// Get the File Contents into an XML Object
			$xmlData = simplexml_load_file( $filePath );
			if( empty( $xmlData ) )	
				throw new Exception('DB Roller: File was empty or failed to load: '.$filePath );

			
			// Normalise the XML Data ready for passing to the standard Builder
			$i = 0;
			$tables = array();
			foreach( $xmlData->Table as $tbl ){
				
				// Define the Table
				$tables[$i] = array(
					'Name'=>(string)$tbl->Name,
					'Engine'=>(string)$tbl->Engine,
					'CharSet'=>(string)$tbl->CharSet,
					'Collation'=>(string)$tbl->Collation,
					'AutoIncrement'=>$this->parseType( (string)$tbl->AutoIncrement ),
					'Comment'=>(string)$tbl->Comment,
					'Columns'=>array(),
					'Insert'=>array()
				);
				
				// Process the Columns
				foreach( $tbl->Columns->Col as $col ){
					
					$params = array();
					foreach( $col as $param ){
						$params[$param->getName()] = $this->parseType( trim((string)$param) );
					}
					$tables[$i]['Columns'][] = $params;
				}
				
				// Process the Row Inserts
				foreach( $tbl->Insert->Row as $row ){
					$cols = array();
					foreach( $row as $col ){
						$cols[$col->getName()] = $this->parseType( trim((string)$param) );
					}
					$tables[$i]['Insert'][] = $cols;
				}
				
				// Increment Table Counter
				$i++;
			}
			
			// Parse the JSON into SQL Table data
			// Return the Build Script
			return $this->build( $tables, $execute, $rebuild );
		}
		
		protected function parseType( $str ){
			
			// Is boolean
			if( strtolower($str) === 'true' )
				return true;
			
			// Is boolean
			if( strtolower($str) === 'false' )
				return false;
			
			// Is Number
			//if( is_numeric($str) )
				//return $str;
			
			// Is string (PHP will figure it out)
			return $str;
		}
	} 
}