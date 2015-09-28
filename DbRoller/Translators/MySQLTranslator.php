<?php
namespace DbRoller\Translators
{
	use \Exception;
	
	class MySQLTranslator extends BaseTranslator implements IDbTranslator
	{
		const MYSQL_ENGINE = 'MyISAM';
		const MYSQL_CHARSET = 'utf8';
		const MYSQL_COLLATION = 'utf8_general_ci';
		const MYSQL_AUTOINCREMENT = 1;

		const MYSQL_BINARY = 'BINARY';
		const MYSQL_UNSIGNED = 'UNSIGNED';
		const MYSQL_UNSIGNED_ZEROFILL = 'UNSIGNED ZEROFILL';
	
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
		* @param string $dbFunction.
		* @param string $dbVendor.
		*
		* @return null|string.
		*/
		public function IsFunction( $dbFunction, $dbVendor = self::MYSQL ){
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
		public function Translate( $dbFunction, $dbVendor = self::MYSQL ){
			return parent::Translate( $dbFunction, $dbVendor );
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
			if( !empty( $args['LenVal'] ) ) 
				$sql .= " (".$args['LenVal'].") ";

			// Add Attribute
			if( !empty( $args['Attribute'] ) ){
				
				// If not a Special Function, wrap in single quotes
				$args['Attribute'] = strtoupper($args['Attribute']);
				if( in_array( $args['Attribute'], array( self::MYSQL_BINARY, self::MYSQL_UNSIGNED, self::MYSQL_UNSIGNED_ZEROFILL ) ) )
					$sql .= " ".$args['Attribute']." ";
			}
			
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
			
			// Add Auto Increment
			if( $args['AutoIncrement'] ) 
				$sql .= " AUTO_INCREMENT ";		

			// Add Comment
			if( !empty( $args['Comment'] ) ) 
				$sql .= " COMMENT '".$args['Comment']."' ";

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
				
				$sql .= ", PRIMARY KEY `".$this->NameConstraint($tableName, 'X', self::PK)."` ( ";
				for( $i=0; $i<$numOf; $i++ ){
					$sql .= " `".$pkeys[$i]."` ";
					
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
					$sql .= ", UNIQUE KEY `".$this->NameConstraint($tableName, $key,self::UQ)."` (`".$key."`) ";			
				}
			}	
			
			// Add Keys/Indices
			$numOf = count($keys);
			if( $numOf > 0 ){
				foreach( $keys as $key ){
					$sql .= ", KEY `".$this->NameConstraint($tableName, $key,self::IDX)."` (`".$key."`) ";			
				}
			}
			
			// Complete the Table Create SQL
			$sql .= " ) ";
			
			// Add engine
			if( !empty( $args['Engine'] ) )
				$sql .= " ENGINE='".$args['Engine']."' ";
			else
				$sql .= " ENGINE='".self::MYSQL_ENGINE."' ";
			
			
			// Add CharSet
			if( !empty($args['CharSet']) )
				$sql .= " DEFAULT CHARSET='".$args['CharSet']."' ";
			else
				$sql .= " DEFAULT CHARSET='".self::MYSQL_CHARSET."' ";
			
			
			// Add Collation
			if( !empty($args['Collation']) )
				$sql .= " COLLATE='".$args['Collation']."' ";
			else
				$sql .= " COLLATE='".self::MYSQL_COLLATION."' ";
			
			
			// Add Auto Increment
			if( !empty( $args['AutoIncrement'] ) ){
				$sql .= " AUTO_INCREMENT=".intval( $args['AutoIncrement'] );
			}

			// Add Closure
			$sql .= ";";
			
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
			$sql = " ALTER TABLE `". $tableName ."` ";
			
			// Loop Columns and Add or Drop
			$numOfCols = count( $cols );
			for( $i=0; $i<$numOfCols; $i++ ){
				$col = $cols[$i];
				
				// Drop Column
				if( isset( $col['Drop'] ) && $col['Drop'] ){
					$sql .= " DROP `" .$col['Name'] . '`, ';
					
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
					$sql .= ", ADD UNIQUE KEY `".$this->NameConstraint($tableName, $key,self::UQ)."` (`".$key."`) ";			
				}
			}
			
			// Add Keys
			$numOf = count($keys);
			if( $numOf > 0 ){
				foreach( $keys as $key ){
					$sql .= ", ADD KEY `".$this->NameConstraint($tableName, $key,self::UQ)."` (`".$key."`) ";			
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