<?php
namespace DbRoller\Rollers
{
	use \PDO;
	use \Exception;
	use \DbRoller\Translators\IDbTranslator;
	
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
			$json = json_decode( utf8_encode( trim( $dataString ) ), true, 1024 );
			if( json_last_error() != JSON_ERROR_NONE )
				throw new Exception('DB Roller: JSON failed to decode: '.$filePath );
			
			// Parse the JSON into SQL Table data
			// Return the Build Script
			return $this->build( $json, $execute, $rebuild );
		}
		
		
		/**
		* Build From JSON.
		* Use data to Create and Execute SQL.
		*
		* @param object $data.
		* @param bool $execute.
		*
		* @return string.
		*/		
		public function Build( $data, $execute = true, $rebuild = false ){
			
			// Ensure the JSON Object is an Array
			if( !is_array( $data ) ) 
				throw new Exception('DB Roller: JSON must be an Array.' );
			
			// The build Script collects all generated SQL.
			$buildScript = '';
			
			// Loop Tables
			foreach( $data as $table ){
				
				// Table properties
				$tableName = '';
				$tableComment = '';
				$tableEngine = '';
				$tableCharSet = '';
				$tableCollation = '';
				$tableAutoIncrement = null;
				
				// Test for Table Name
				if( empty( $table['Name'] )  )
					throw new Exception('DB Roller (Schema): Table Name is Missing.');
				
				// Capture the Table Name
				else
					$tableName = $table['Name'];
				
				// Test to see if the Table (and COlumns) exist in the Database
				$sql = $this->trans->TableSchema( $tableName );
				
				// Query the Table
				$existingCols = null;
				try {
					$stmt = $this->conn->query( $sql );
					$schema = $stmt->fetchAll( PDO::FETCH_ASSOC );
					
					// Normalise the Column List
					// We need to do this because individual vendors
					// describe their schemas differently
					$existingCols = $this->trans->NormaliseColumnNames( $schema );
				}
				catch( PDOException $ex ) {
					
					// Rethrow to Higher level
					throw new Exception('DB Roller (Schema): '. $ex->getMessage() );
				}
				
				// Table Create or Modify flag
				$tableExists = count( $existingCols ) > 0;
				
				// Test for Table Engine
				if( !empty( $table['Engine'] ) )
					$tableEngine = $table['Engine'];
				
				// Test for Table Character Set
				if( !empty( $table['CharSet'] ) )
					$tableCharSet = $table['CharSet'];
				
				// Test for Table Collation
				if( !empty( $table['Collation'] ) )
					$tableCollation = $table['Collation'];
				
				// Test for Table Auto Increment
				if( !empty( $table['AutoIncrement'] ) )
					$tableAutoIncrement = intval( $table['AutoIncrement'] );
				
				// Test for Table Comment
				if( !empty( $table['Comment'] ) )
					$tableComment = $table['Comment'];

				// Loop Columns
				$Columns = array();
				$PKeys = array();
				$UKeys = array();
				$Keys = array();
				foreach( $table['Columns'] as $col ){
					
					// Column Properties
					$colName = '';
					$colType = '';
					$colLenVal = '';
					$colAllowNull = true;
					$colAutoIncrement = false;
					$colAttribute = '';
					$colDefault = null;
					$colComment = '';
					
					// Test for Column Name
					if( empty( $col['Name'] )  )
						throw new Exception('DB Roller (Schema): Column Name is Missing.');
					
					// Capture the Column Name
					else
						$colName = $col['Name'];
					
					// Add Column to Table flag
					$colExists = false;
					
					// See if Column Exists in Table
					// If we're rebuilding assume that it doesn't exist
					if( !$rebuild && in_array( $colName, $existingCols ) )
						$colExists = true;
					
					// Test for Column Type
					if( !empty( $col['Type'] ) )
						$colType = $col['Type'];
					
					// Test for Column Length/Values
					if( !empty( $col['LenVal'] ) )
						$colLenVal = $col['LenVal'];
					
					// Test for Column Allow NULLs
					if( !empty( $col['AllowNull'] ) )
						$colAllowNull = $col['AllowNull'];
					
					// Test for Column Default
					if( !empty( $col['Default'] ) )
						$colDefault = $col['Default'];
					
					// Attribute
					if( !empty( $col['Attribute'] ) ){
						$colAttribute = $col['Attribute'];
					}
					
					// Test for Column Auto Increment
					if( !empty( $col['AutoIncrement'] ) )
						$colAutoIncrement = $col['AutoIncrement'];
					
					// Test for Column Comment
					if( !empty( $col['Comment'] ) )
						$colComment = $col['Comment'];

					// Key/Index
					// Note: Only add Keys for new Columns
					$isKey = false;
					if( !$colExists && !empty( $col['Index'] ) ){
						
						switch( strtoupper( $col['Index'] ) ){
							case self::PRIMARY_KEY:
								$PKeys[] = $colName;
								break;
								
							case self::UNIQUE_KEY:
								$UKeys[] = $colName;
								break;
								
							case self::INDEX:
							case self::KEY:
								$Keys[] = $colName;
								break;
						}
						
						// Mark as an Index/Key
						$isKey = true;
					}
					
					// Add Final Values to Array
					$Columns[] = array(
						'Exists'=>$colExists,
						'IsKey'=>$isKey,
						'Name'=>$colName,
						'Type'=>$colType,
						'LenVal'=>$colLenVal,
						'AllowNull'=>$colAllowNull,
						'Default'=>$colDefault,
						'AutoIncrement'=>$colAutoIncrement,
						'Attribute'=>$colAttribute,
						'Comment'=>$colComment
					); 
				}
				
				// Table and Columns exist, make an Alter Table Query
				if( !$rebuild && $tableExists ){
					
					// Test for columns that exist but have been removed from the Schema
					foreach( $existingCols as $col ){
						
						$removeColumn = true;
						foreach( $Columns as $ncol ){
							if( strtoupper( $col ) == strtoupper( $ncol['Name'] ) )
								$removeColumn = false;
						}
						
						// Column is not in new Schema
						// Flag it to be removed
						if( $removeColumn )
							$Columns[] = array(
								'Exists'=>true,
								'Drop'=>true,
								'Name'=>$col
							); 
					}
					
					// Make the Alter Table SQL
					$sql = $this->trans->Alter( $tableName, $Columns, $PKeys, $UKeys, $Keys, array(
						'Comment'=>$tableComment,
						'Engine'=>$tableEngine,
						'CharSet'=>$tableCharSet,
						'Collation'=>$tableCollation,
						'AutoIncrement'=>$tableAutoIncrement
					) );
					
					// Alter the Table
					// Note: If the SQL is empty, then there are no changes
					if( $execute && !empty( $sql ) ){
						try {
							$this->conn->beginTransaction();
							$result = $this->conn->exec( $sql );
							$this->conn->commit();
						} 
						catch( PDOException $ex ) {
							$this->conn->rollBack();
							
							// Rethrow to Higher level
							throw new Exception('DB Roller (Schema): '. $ex->getMessage() );
						}
					}
					
					// Collect all SQL into a single String
					$buildScript .= $sql;
				}
				
				// Table doesn't exist or it's a revuild, make a Create Table Query
				else{
					
					// Make the Create Table SQL
					$sql = $this->trans->Create( $tableName, $Columns, $PKeys, $UKeys, $Keys, array(
						'Comment'=>$tableComment,
						'Engine'=>$tableEngine,
						'CharSet'=>$tableCharSet,
						'Collation'=>$tableCollation,
						'AutoIncrement'=>$tableAutoIncrement
					) );

					// Create the Table
					// Note: If the SQL is empty, then there are no changes
					if( $execute && !empty( $sql ) ){
						try {
							$this->conn->beginTransaction();
							$result = $this->conn->exec( $sql );
							$this->conn->commit();
						} 
						catch( PDOException $ex ) {
							$this->conn->rollBack();
							
							// Rethrow to Higher level
							throw new Exception('DB Roller (Schema): '. $ex->getMessage() );
						}
					}

					// Collect all SQL into a single String
					$buildScript .= $sql;
					
					// Seed the Table
					if( isset( $table['Insert'] ) && is_array( $table['Insert'] ) ){
						
						// Seed table and add capture the SQL
						$buildScript .= $this->SeedTable( $tableName, $table['Insert'], $execute );
					}
				}
			}
			
			// Return the complete Build Script
			return $buildScript;
		}
		

		
		/**
		* Seed Table (SQL Insert).
		*
		* @param string $tableName.
		* @param array $rows.
		* @param bool $execute.
		*
		* @return string.
		*/		
		public function SeedTable( $tableName, Array $rows, $execute = true ){
				
			// The build Script collects all generated SQL.
			$buildScript = '';
			
			try {
				$this->conn->beginTransaction();

				// Loop Rows and Cols
				foreach( $rows as $cols ){
			
					// Ensure the columns array is an array
					if( !is_array( $cols ) )
						continue;
			
					// Loop columns and create Name and Value arrays
					$colNames = array();
					$colParams = array();
					$colValues = array();
					foreach( $cols as $key => $value ){
						$colNames[] = $key;
						
						// Check for database function
						$func = $this->trans->IsFunction( $value );
						
						// Is not a function
						if( empty( $func ) ){
							$colParams[] = "?";
							$colValues[] = $value;
						}
						
						// Is a function
						else{
							$colParams[] = $func;
						}
					}
					
					// Create the sql statement
					$sql = $this->trans->WriteInsert( $tableName, $colNames, $colParams );
				
					// Prepare and Execute the Query
					// Note: If the SQL is empty, then there are no changes
					if( $execute && !empty( $sql ) ){
						$stmt = $this->conn->prepare( $sql );
						$stmt->execute( $colValues );
					}
					
					// Collect all SQL into a single String
					$buildScript .= $sql;
				} 
				
				// Commit all rows
				$this->conn->commit();
			}

			catch( PDOException $ex ) {
				$this->conn->rollBack();
				
				// Rethrow to Higher level
				throw new Exception('DB Roller (Schema): '. $ex->getMessage() );
			}
			
			// Return the complete Build Script
			return $buildScript;
		}			
	
	} // JsonBuilder
	
}