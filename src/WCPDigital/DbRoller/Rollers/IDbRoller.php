<?php
namespace WCPDigital\DbRoller\Rollers
{
	interface IDbRoller
	{
		/**
		* Build From File.
		*
		* @param string $filePath.
		* @param bool $execute.
		*
		* @return string.
		*/
		public function BuildFromFile( $filePath, $execute = true, $rebuild = false  );		
		
		/**
		* Build from Array.
		* Use data to Create and Execute SQL.
		*
		* @param array $data.
		* @param bool $execute.
		*
		* @return string.
		*/		
		public function Build( Array $data, $execute = true, $rebuild = false  );
		
		/**
		* Seed Table (SQL Insert).
		*
		* @param string $tableName.
		* @param array $rows.
		* @param bool $execute.
		*
		* @return string.
		*/		
		public function SeedTable( $tableName, Array $rows, $execute = true );

	}
	
}