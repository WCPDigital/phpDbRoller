<?php
namespace DbRoller\Translators
{
	use \Exception;
	
	class PGSQLTranslator extends BaseTranslator implements IDbTranslator
	{
		const PGSQL_ENGINE = 'MyISAM';
		const PGSQL_CHARSET = 'utf8';
		const PGSQL_COLLATION = 'utf8_general_ci';
		const PGSQL_AUTOINCREMENT = 1;

		const PGSQL_SERIAL = 'SERIAL';
		const PGSQL_BIGSERIAL = 'BIGSERIAL';
	
	
		/**
		* Safe Enclose.
		* Enclose (wrap) Table or Column names to differenciate from Reserved words.
		*
		* @param string $value.
		*
		* @return string.
		*/
		public function SafeEnclose( $value ){
			return '"'.$value.'"';

		}		
	
		/**
		* Table Exists.
		* Return a query for accessing the table's schema
		*
		* @param string $tableName.
		*
		* @return string.
		*/
		public function TableSchema( $tableName ){
			return " SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$tableName."'; ";
		}	
		
		/**
		* Normalise Column Names.
		* Normailse an array of column values into an array of column names.
		*
		* @param array $tableSchema.
		*
		* @return array.
		*/
		public function NormaliseColumnNames( Array $tableSchema ){
				
			$columnNames = array();
			foreach( $tableSchema as $row ){
				$columnNames[] = $row['COLUMN_NAME'];
			}
			return $columnNames;
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
		public function IsFunction( $dbKeyword, $dbVendor = self::PGSQL ){
			return parent::IsFunction( $dbKeyword, $dbVendor );
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
		public function Translate( $dbKeyword, $dbVendor = self::PGSQL ){
			return parent::Translate( $dbKeyword, $dbVendor );
		}

		
		/**
		* Write Column (SQL).
		*
		* @param array $args.
		*
		* @return null|string.
		*/		
		public function WriteColumn( Array $args ){

			// Error check
			if( empty( $args['Name'] ) || empty( $args['Type'] ) )
				throw new Exception('Name and Type are required fields.');

			// Add Name
			$sql = " ".$this->SafeEnclose( $args['Name'] )." ";
			
			
			// PostGres AutoIncrement is handled by the Serial Datatype
			// Convert INT and BIGINT to SERIAL and BIGSERIAL when flagged as AutoIncrement
			$args['Type'] = strtoupper($args['Type']);
			if( $args['AutoIncrement'] ){
				switch( $args['Type'] ){
					case 'INT':
						$args['Type'] = self::PGSQL_SERIAL;
						break;
						
					case 'BIGINT':
					default:
						$args['Type'] = self::PGSQL_BIGSERIAL;
						break;
				}
			}
			 
			// Ensure the autoincrement flag is set
			$args['AutoIncrement'] = ( $args['Type'] == self::PGSQL_SERIAL || $args['Type'] == self::PGSQL_BIGSERIAL );
			
			// If not AutoIncrement, Translate Type
			if( !$args['AutoIncrement'] ){
				$type = $this->Translate( $args['Type'] );
				if( !empty( $type ) ){
					$args['Type'] = $type;
				}
			}
			
			// Add Type
			$sql .= " ".$args['Type']." ";
			
			// Add Type Length/Values
			if( !empty( $args['LenVal'] ) ) 
				$sql .= " (".$args['LenVal'].") ";

			// Allow Nulls
			if( $args['AllowNull'] && !$args['AutoIncrement'] )
				$sql .= " NULL ";
			else
				$sql .= " NOT NULL ";
		
			// Add Default
			// Note: Disregard Defaults for Autoincrement
			if( !empty( $args['Default'] ) && !$args['AutoIncrement'] ){
				
				// Translate the Default
				$default = $this->Translate( $args['Default'] );
				if( !empty( $default ) ){
						$sql .= " DEFAULT ".$default;
				}
				
				// If not a Special Function, wrap in single quotes
				else{
						$sql .= " DEFAULT '".$args['Default']."' ";
				}
			}

			// Add Comment
			//if( !empty( $args['Comment'] ) ) 
				//$sql .= " COMMENT '".$args['Comment']."' ";

			return $sql;
		}
		
		/**
		* Write Insert (SQL).
		*
		* @param string $tableName.
		* @param array $cols.
		* @param array $params.
		*
		* @return string.
		*/		
		public function WriteInsert( $tableName, Array $cols, Array $params ){
			return " INSERT INTO " . $this->SafeEnclose( $tableName ) . " (" . implode( ",", array_map( array($this,'SafeEnclose'), $cols ) ) . ") VALUES (" . implode( ",", $params ) . "); ";
		}
		
		/**
		* Create Table.
		* Use data to Create Table SQL.
		*
		* @param string $tableName.
		* @param array $cols.
		* @param array $pkeys.
		* @param array $ukeys.
		* @param array $keys.
		* @param array $args Optional set of database specific parameters.
		*
		* @return null|string.
		*/		
		public function Create( $tableName, Array $cols, Array $pkeys, Array $ukeys, Array $keys, Array $args = null    ){

			// Add a Drop if Exists Query
			$sql = " DROP TABLE IF EXISTS ". $this->SafeEnclose( $tableName ) ."; ";
			
			// Start the Create Table Query
			$sql .= " CREATE TABLE ". $this->SafeEnclose( $tableName ) ." (";
			
			// Create Column SQL
			$numOfCols = count( $cols );
			for( $i=0; $i<$numOfCols; $i++ ){
				$col = $cols[$i];
				
				// Build the Column SQL
				$sql .= $this->WriteColumn( $col );
				
				// Append Col Spacer (comma)
				if( $i<($numOfCols-1) )
					$sql .= ",";
			}

			// Add Primary Keys
			// Only add Primary Keys on Table Creation
			$numOf = count($pkeys);
			if( $numOf > 0 ){
				
				$sql .= ", CONSTRAINT  ". $this->SafeEnclose( $this->NameConstraint($tableName, 'X', self::PK) ) ." PRIMARY KEY ( ";
				for( $i=0; $i<$numOf; $i++ ){
					$sql .= " ". $this->SafeEnclose( $pkeys[$i] ) ." ";
					
					// Append Col Spacer (comma)
					if( $i<($numOf-1) )
						$sql .= ",";			
				}
				$sql .= " ) ";
			}
			
			// Add Unique Keys
			$numOf = count($ukeys);
			if( $numOf > 0 ){
				foreach( $ukeys as $key ){
					$sql .= ", CONSTRAINT ". $this->SafeEnclose( $this->NameConstraint($tableName, $key,self::UQ) ) ." UNIQUE (".$this->SafeEnclose( $key ) .") ";			
				}
			}	
			
			// Complete the Table Create SQL
			$sql .= " ); ";

			// Finsihed SQL
			return $sql;
		}
		
		/**
		* Alter Table.
		* Use data to Alter Table SQL.
		*
		* @param string $tableName.
		* @param array $cols.
		* @param array $pkeys.
		* @param array $ukeys.
		* @param array $keys.
		*
		* @return null|string.
		*/		
		public function Alter( $tableName, Array $cols, Array $pkeys, Array $ukeys, Array $keys, Array $args = null   ){

			// No point executing a query if there are no changes
			// So we'll count them
			$changeCounter = 0;
			
			// Create the Alter Table SQL
			$sql = " ALTER TABLE ". $this->SafeEnclose( $tableName ) ." ";
			
			// Loop Columns and Add or Drop
			$numOfCols = count( $cols );
			for( $i=0; $i<$numOfCols; $i++ ){
				$col = $cols[$i];
				
				// Drop Column
				if( isset( $col['Drop'] ) && $col['Drop'] ){
					$sql .= " DROP " . $this->SafeEnclose( $col['Name'] ) . ', ';
					
					// Increment the Change Counter
					$changeCounter++;
				}
				
				// Add Column
				else if( !$col['Exists'] ){
				
					// Add or Modify
					$sql .=  " ADD ";
						
					// Build the Column SQL
					$sql .= $this->WriteColumn( $col ) . ', ';
					
					// Increment the Change Counter
					$changeCounter++;
				}
			}
			
			// Remove the trailing comma
			$sql = substr(trim($sql), 0, -1);

			// Add Unique Keys
			$numOf = count($ukeys);
			if( $numOf > 0 ){
				foreach( $ukeys as $key ){
					$sql .= ", ADD CONSTRAINT ".$this->SafeEnclose( $this->NameConstraint($tableName, $key,self::UQ) )." UNIQUE(".$this->SafeEnclose( $key ) .") ";			
				}
			}

			// Add Closure
			$sql .= ";";
			
			// Finsihed SQL
			if( $changeCounter > 0 )
				return $sql;
			
			// There are no changes, return null
			return null;
		}

	}
}