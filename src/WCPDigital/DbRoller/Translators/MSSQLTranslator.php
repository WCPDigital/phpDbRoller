<?php
namespace WCPDigital\DbRoller\Translators
{
	use \Exception;
	
	class MSSQLTranslator extends BaseTranslator implements IDbTranslator
	{
	
		/**
		* Safe Enclose.
		* Enclose (wrap) Table or Column names to differenciate from Reserved words.
		*
		* @param string $value.
		*
		* @return string.
		*/
		public function SafeEnclose( $value ){
			return '['.$value.']';

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
		public function IsFunction( $dbKeyword, $dbVendor = self::MSSQL ){
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
		public function Translate( $dbKeyword, $dbVendor = self::MSSQL ){
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
			
			// Translate Type
			$type = $this->Translate( $args['Type'] );
			if( !empty( $type ) ){
				$args['Type'] = $type;
			}

			// Add Type
			$sql .= " ".$args['Type']." ";
			
			// Add Type Length/Values
			if( !empty( $args['LenVal'] ) ) 
				$sql .= " (".$args['LenVal'].") ";
			
			// Add Auto Increment
			if( $args['AutoIncrement'] ) 
				$sql .= " IDENTITY(1,1) ";		
			
			// Allow Nulls
			// Note: Identity doesn't allow NULLs
			if( $args['AllowNull'] && !$args['AutoIncrement'] && !$args['IsKey'] )
				$sql .= " NULL ";
			else
				$sql .= " NOT NULL ";
		
			// Add Default
			// Note: Disregard Defaults for Autoincrement and Indices
			if( !empty( $args['Default'] ) && !$args['AutoIncrement'] && !$args['IsKey'] ){
				
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
		* @return string.
		*/		
		public function WriteInsert( $tableName, Array $cols, Array $params ){
			
			// Allow the table to have it's identities set
			//$sql = " SET IDENTITY_INSERT [dbo].[" . $tableName . "] ON; ";
			
			// Create the sql statement
			$sql .= " INSERT INTO [dbo]." . $this->SafeEnclose( $tableName ) . " (" . implode( ",", array_map( array($this,'SafeEnclose'), $cols ) ) . ") VALUES (" . implode( ",", $params ) . "); ";
		
			// Reactivate identies
			//$sql .= " SET IDENTITY_INSERT [dbo].[" . $tableName . "] OFF; ";
					
			return $sql;
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
			$sql = " IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '". $tableName ."') DROP TABLE ". $this->SafeEnclose( $tableName ) ."; ";

			// Start the Create Table Query
			$sql .= " IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '". $tableName ."') " .
					" CREATE TABLE [dbo].". $this->SafeEnclose( $tableName ) ." (";
			
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
				
				$sql .= ", CONSTRAINT ".$this->SafeEnclose( $this->NameConstraint($tableName, 'X', self::PK) )."  PRIMARY KEY ( ";
				for( $i=0; $i<$numOf; $i++ ){
					$sql .= " ".$this->SafeEnclose( $pkeys[$i] )." ";
					
					// Append Col Spacer (comma)
					if( $i<($numOf-1) )
						$sql .= ",";			
				}
				$sql .= " ) ";
			}
			
			// Complete the Table Create SQL
			$sql .= " ); ";
			
						
			// Add Unique Keys
			$numOf = count($ukeys);
			if( $numOf > 0 ){
				foreach( $ukeys as $key ){
					$sql .= " CREATE UNIQUE INDEX ".$this->SafeEnclose( $this->NameConstraint($tableName, $key,self::UQ) )." ON [dbo].".$this->SafeEnclose( $tableName)."(".$this->SafeEnclose( $key ) ."); ";			
				}
			}
						
			// Add Index/Key
			$numOf = count($keys);
			if( $numOf > 0 ){
				foreach( $keys as $key ){
					$sql .= " CREATE INDEX ".$this->SafeEnclose( $this->NameConstraint($tableName, $key,self::IDX) )." ON [dbo].".$this->SafeEnclose( $tableName ) ."(".$this->SafeEnclose( $key ) ."); ";			
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
				$sql .= " ALTER TABLE [dbo].". $this->SafeEnclose( $tableName ) ." ADD " . $this->WriteColumn( $col ) . "; ";
				
				// Increment the Change Counter
				$changeCounter++;
			}

			// Add Unique Keys
			$numOf = count($ukeys);
			if( $numOf > 0 ){
				foreach( $ukeys as $key ){
					$sql .= " CREATE UNIQUE INDEX ".$this->SafeEnclose( $this->NameConstraint($tableName, $key,self::UQ) )." ON [dbo].".$this->SafeEnclose( $tableName )."(".$this->SafeEnclose( $key ) ."); ";	
				}
			}
			
			// Add Keys
			$numOf = count($keys);
			if( $numOf > 0 ){
				foreach( $keys as $key ){
					$sql .= " CREATE INDEX ".$this->SafeEnclose( $this->NameConstraint($tableName, $key,self::IDX) ) ." ON [dbo].".$this->SafeEnclose( $tableName ) ."(".$this->SafeEnclose( $key ) ."); ";		
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