<?php
namespace DbRoller\Translators
{
	interface IDbTranslator
	{
		/**
		* Safe Enclose.
		* Enclose (wrap) Table or Column names to differenciate from Reserved words.
		*
		* @param string $value.
		*
		* @return string.
		*/
		public function SafeEnclose( $value );	
		
		/**
		* Table Schema.
		* Return a query for accessing the table's schema
		*
		* @param string $tableName.
		*
		* @return string.
		*/
		public function TableSchema( $tableName );	
		
		/**
		* Normalise Column Names.
		* Normailse an array of column values into an array of column names.
		*
		* @param array $tableSchema.
		*
		* @return array.
		*/
		public function NormaliseColumnNames( Array  $tableSchema );
		
		/**
		* Is Function
		* Check to see if the string is a DB Function
		*
		* @param string $dbKeyword.
		* @param string $dbVendor.
		*
		* @return null|string.
		*/
		public function IsFunction( $dbKeyword, $dbVendor );
		
		
		/**
		* Translate.
		* Load csv file containing the Database translation information.
		*
		* @param string $dbKeyword.
		* @param string $dbVendor.
		*
		* @return string.
		*/
		public function Translate( $dbKeyword, $dbVendor );

		/**
		* Write Column (SQL).
		*
		* @param array $args.
		*
		* @return string.
		*/		
		public function WriteColumn( Array $args );
		
		/**
		* Write Insert (SQL).
		*
		* @param array $args.
		*
		* @return null|string.
		*/		
		public function WriteInsert( $tableName, Array $cols, Array $params );
		
		/**
		* Create Table.
		* Use data to Create Table SQL.
		*
		* @param string $tableName.
		* @param array $cols.
		* @param array $pkeys.
		* @param array $ukeys.
		* @param array $keys.
		*
		* @return null|string.
		*/		
		public function Create( $tableName, Array $cols, Array $pkeys, Array $ukeys, Array $keys, Array $args = null );
		
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
		public function Alter( $tableName, Array $cols, Array $pkeys, Array $ukeys, Array $keys, Array $args = null );

	}
	
}