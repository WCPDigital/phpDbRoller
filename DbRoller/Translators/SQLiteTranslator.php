<?php
namespace DbRoller\Translators
{
	use \Exception;
	
	class SQLiteTranslator extends BaseTranslator implements IDbTranslator
	{
		/**
		* Table Schema.
		* Return a query for accessing the table's schema
		*
		* @param string $tableName.
		*
		* @return string.
		*/
		public function TableSchema( $tableName ){
			return " PRAGMA table_info('".$tableName."'); ";
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
				$columnNames[] = $row['name'];
			}
			return $columnNames;
		}
		

		/**
		* Is Function
		* Check to see if the string is a DB Function
		*
		* @param string $dbFunction.
		* @param string $dbVendor.
		*
		* @return null|string.
		*/
		public function IsFunction( $dbFunction, $dbVendor = self::SQLITE ){
			return parent::IsFunction( $dbFunction, $dbVendor );
		}		
		
		/**
		* Translate.
		* Load csv file containing the Database translation information.
		*
		* @param string $dbFunction.
		* @param string $dbVendor.
		*
		* @return string.
		*/
		public function Translate( $dbFunction, $dbVendor = self::SQLITE ){
			return parent::Translate( $dbFunction, $dbVendor );
		}

		
		/**
		* Write Column (SQL).
		*
		* @param array $args.
		*
		* @return string.
		*/		
		public function WriteColumn( Array $args ){

			// Error check
			if( empty( $args['Name'] ) || empty( $args['Type'] ) )
				throw new Exception('Name and Type are required fields.');

			// Add Name
			$sql = " `".$args['Name']."` ";
			
			// Add Type
			$type = $this->Translate( $args['Type'] );
			if( !empty( $type ) ){
				$sql .= " ".$type." ";
			}
			else{
				$sql .= " ".$args['Type']." ";
			}
			
			// Add Type Length/Values
			if( !empty( $args['Index'] ) ) {
				switch( strtoupper( $args['Index'] ) ){
					
					case self::SQLITE_UNIQUE_KEY;
						$sql .= " UNIQUE ";
						break;
				}
			}
			
			// Allow Nulls
			if( $args['AllowNull'] && !$args['AutoIncrement'] && !$args['IsKey'] )
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

			return $sql;
		}
		
		/**
		* Write Insert (SQL).
		*
		* @param string $tableName.
		* @param array $cols.
		* @param array $params.
		*
		* @return null|string.
		*/		
		public function WriteInsert( $tableName, Array $cols, Array $params ){
			return " INSERT INTO `" . $tableName . "` (" . implode( ",", $cols ) . ") VALUES (" . implode( ",", $params ) . "); ";
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
			$sql = " DROP TABLE IF EXISTS `". $tableName ."`; ";
			
			// Start the Create Table Query
			$sql .= " CREATE TABLE IF NOT EXISTS `". $tableName ."` (";
			
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
				
				$sql .= ", CONSTRAINT `".$this->NameConstraint($tableName, 'X', self::PK)."` PRIMARY KEY  ( ";
				for( $i=0; $i<$numOf; $i++ ){
					$sql .= "`".$pkeys[$i]."`";
					
					// Append Col Spacer (comma)
					if( $i<($numOf-1) )
						$sql .= ",";			
				}
				$sql .= " ) ";
			}
			
			// Complete the Table
			$sql .= " ); ";
			
			// Add Index/Key
			$numOf = count($keys);
			if( $numOf > 0 ){
				foreach( $keys as $key ){
					$sql .= " CREATE INDEX `".$this->NameConstraint($tableName, $key,self::IDX)."` ON `".$tableName."`(`".$key."`); ";			
				}
			}
			
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
			$sql = '';
			
			// Loop Columns and Add or Drop
			$numOfCols = count( $cols );
			for( $i=0; $i<$numOfCols; $i++ ){
				$col = $cols[$i];
				
				// Drop and Alter/Modify is not supported
				if( $col['Exists'] || (isset( $col['Drop'] ) && $col['Drop']) )
					continue;
				
				// Add or Modify
				$sql .= " ALTER TABLE `". $tableName ."` ADD " . $this->WriteColumn( $col ) . "; ";

				// Increment the Change Counter
				$changeCounter++;
			}
			
			// Remove the trailing comma
			$sql = substr(trim($sql), 0, -1);

			// Add Closure
			$sql .= ";";
					
			// Add Index/Key
			$numOf = count($keys);
			if( $numOf > 0 ){
				foreach( $keys as $key ){
					$sql .= " CREATE INDEX `".$this->NameConstraint($tableName, $key,self::IDX)."` ON `".$tableName."`(`".$key."`); ";			
				}
			}

			// Finsihed SQL
			if( $changeCounter > 0 )
				return $sql;
			
			// There are no changes, return null
			return null;
		}

	}
}