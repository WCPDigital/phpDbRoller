<?php
namespace DbRoller\Rollers
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
		* Build from Object.
		* Use data to Create and Execute SQL.
		*
		* @param object $data.
		* @param bool $execute.
		*
		* @return string.
		*/		
		public function Build( $data, $execute = true, $rebuild = false  );
		
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