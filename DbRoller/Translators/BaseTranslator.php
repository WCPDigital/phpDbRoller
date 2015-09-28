<?php
namespace DbRoller\Translators
{
	use \Exception;
	
	abstract class BaseTranslator
	{
		const MYSQL = 'MYSQL';
		const MSSQL = 'MSSQL';
		const SQLITE = 'SQLITE';
		const PGSQL = 'PGSQL';
		const DB2 = 'DB2';
		const ORACLE = 'ORACLE';
		
		const IDX = 'IDX';
		const UQ = 'UQ';
		const PK = 'PK';
		const FK = 'FK';
		
		/**
		* Matrix File Name.
		*
		* @var string
		*/
		const MARTIX_FILE_NAME = 'matrix.csv';
		
		/**
		* Matrix Multi-Dim Array
		*
		* @var array
		*/	
		protected $matrix = null;
		
		/**
		* Load Matrix.
		* Load csv file containing the Database translation information.
		*
		* @param string $filePath.
		*
		* @return array.
		*/
		public function LoadMatrix( $filePath = null ){
			
			// Default file path
			if( empty( $filePath ) ){
				$filePath = dirname( __FILE__ ).DIRECTORY_SEPARATOR.self::MARTIX_FILE_NAME;
			}
			
			// Ensure the path is readable
			if( !is_readable( $filePath ) )
				throw new Exception('DB Roller: File not readable: '.$filePath );
			
			// Get the File Contents
			$dataString = file_get_contents( $filePath );
			if( empty( $dataString ) )	
				throw new Exception('DB Roller: File was empty or failed to load: '.$filePath );
			
			// Normalise Linebreaks
			//$dataString = preg_replace('/\r\n|\r|\n/', PHP_EOL, $dataString);
			$dataString = str_replace(array("\r\n","\r","\n"), PHP_EOL, $dataString );
			
			// Create Matrix as a Multi-Dim Array
			$this->matrix = array_map( function($row){
					return array_map( function( $col ){
						return strtoupper( trim( $col ) );
					}, str_getcsv( $row ) );
				}, explode( PHP_EOL, $dataString )
			);
			
			// Return the Matrix 
			return $this->matrix;
		}

		/**
		* Translate.
		* Load csv file containing the Database translation information.
		*
		* @param string $dbKeyword.
		* @param string $dbVendor.
		*
		* @return string.
		*/
		public function Translate( $dbKeyword, $dbVendor = self::MYSQL ){
			
			// Convert strings to Uppercase
			$dbKeyword = strtoupper( $dbKeyword );
			$dbVendor = strtoupper( $dbVendor );
			
			// Lazy load the Translation Matrix
			if( !is_array( $this->matrix ) )
				$this->matrix = $this->LoadMatrix(); 

			// Find the Index of the Vendor
			// Horizontal Value
			$vIndex = -1;
			foreach( $this->matrix as $row ){
				foreach( $row as $idx => $val ){
					if( $val == $dbVendor ){
						$vIndex = $idx;
						break;
					}
				}
			}
			
			// Check the Vendor Column, see if it's a valid keyword
			// Vertical Value
			$fIndex = -1;
			foreach( $this->matrix as $idx => $row ){
				
				// Key/Value exists
				if( isset( $this->matrix[ $idx ][ $vIndex ] ) ){
					
					// Match found, return the keyword
					if( $this->matrix[ $idx ][ $vIndex ] == $dbKeyword )
						return $this->matrix[ $idx ][ $vIndex ]; 
				}
			}
			
			// Vendor match not found, look for the translation
			// Find the Index of the Function
			// Vertical Value
			$fIndex = -1;
			foreach( $this->matrix as $idx => $row ){
				foreach( $row as $val ){
					if( $val == $dbKeyword ){
						$fIndex = $idx;
						break;
					}
				}
			}
			
			// Translation found
			if( isset( $this->matrix[ $fIndex ][ $vIndex ] ) )
				return $this->matrix[ $fIndex ][ $vIndex ];
				
			// Translation not found
			return '';
		}
		

		/**
		* Is Function
		* Check to see if the string is a DB Function
		*
		* @param string $dbKeyword.
		* @param string $dbVendor.
		*
		* @return null|string.
		*/
		public function IsFunction( $dbKeyword, $dbVendor ){
			$dbKeyword = trim( $dbKeyword );
			
			// String is too short. Can't be a function
			if( strlen( $dbKeyword ) < 1 )
				return null;
			
			// Is Function
			if( substr($dbKeyword, 0, 1) === '{' && substr( $dbKeyword, -1, 1 ) === '}' ){
				$func = substr($dbKeyword, 1, -1);	
				
				// Lookup in the Translation Matrix
				return $this->Translate( $func, $dbVendor );
			}

			// Is not a Function
			return '';
		}
				

		/**
		* Name Constraint
		* Generate a Constraint Name for an Index or Key
		*
		* @param string $tableName.
		* @param string $columnName.
		* @param string $prefix.
		*
		* @return string.
		*/
		public function NameConstraint( $tableName, $columnName, $prefix = self::IDX ){
			return strtoupper( $prefix.'_'.$tableName.'_'.$columnName );
		}
	}
	
}