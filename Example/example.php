<?php
namespace{
	
	require_once('../src/autoload.php');
	
	use \DbRoller\Translators\MySQLTranslator;
	use \DbRoller\Translators\PGSQLTranslator;
	use \DbRoller\Translators\MSSQLTranslator;
	use \DbRoller\Translators\SQLiteTranslator;
	use \DbRoller\Rollers\JsonRoller;
	use \DbRoller\Rollers\XmlRoller;
	use \DbRoller\Rollers\YamlRoller;
	
	try{
		$host = 'localhost';
		$database = '';
		$username = '';
		$password = '';
		
		// MySQL: Connect to DB and Execute
		$db = new PDO('mysql:host='.$host.';dbname='.$database.';charset=utf8', $username, $password );
		
		// PostGreSQL: Connect to DB and Execute
		//$db = new PDO('pgsql:host='.$host.';dbname='.$database.';', $username, $password );
		
		// MS SQL; Connect to DB and Execute
		//$db = new PDO('dblib:host='.$host.';dbname='.$database.';charset=utf8', $username, $password );

		// SQLite; Connect to DB and Execute	
		//$db = new PDO('sqlite:data/example.sqlite3');
		
		// Set error mode
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
	catch( PDOException $ex ) {
		
		// Rethrow to Higher level
		throw new Exception('DB Builder (Schema): '. $ex->getMessage() );
	}
	
	
	try{
	
	// Create a translator
	$trans = new MySQLTranslator();
	//$trans = new PGSQLTranslator();
	//$trans = new MSSQLTranslator();
	//$trans = new SQLiteTranslator();

	
	// JSON
	// Create a builder
	$builder = new JsonRoller( $db, $trans );
	
	// Build the Schema and update the Database
	$sql = $builder->BuildFromFile( __DIR__ . DIRECTORY_SEPARATOR . 'schema_example_create.json', true, true );
	echo '<h2>JSON Create</h2><div style="margin:20px 0;">'.$sql.'</div>';
	
	// Build the Schema and update the Database
	//$sql = $builder->BuildFromFile( __DIR__ . DIRECTORY_SEPARATOR . 'schema_example_alter.json', true, false );
	//echo '<h2>JSON Alter</h2><div style="margin:20px 0;">'.$sql.'</div>';
	
	
	// XML
	// Create a builder
	$builder = new XmlRoller( $db, $trans );
	
	// Build the Schema and update the Database
	$sql = $builder->BuildFromFile( __DIR__ . DIRECTORY_SEPARATOR . 'schema_example_create.xml', true, true );
	echo '<h2>XML Create</h2><div style="margin:20px 0;">'.$sql.'</div>';
	
	// Build the Schema and update the Database
	//$sql = $builder->BuildFromFile( __DIR__ . DIRECTORY_SEPARATOR . 'schema_example_alter.xml', true, false );
	//echo '<h2>XML Alter</h2><div style="margin:20px 0;">'.$sql.'</div>';
	
	
	
	
	// YAML
	// Create a builder
	$builder = new YamlRoller( $db, $trans );
	
	// Build the Schema and update the Database
	$sql = $builder->BuildFromFile( __DIR__ . DIRECTORY_SEPARATOR . 'schema_example_create.yaml', true, true );
	echo '<h2>YAML Create</h2><div style="margin:20px 0;">'.$sql.'</div>';
	
	// Build the Schema and update the Database
	//$sql = $builder->BuildFromFile( __DIR__ . DIRECTORY_SEPARATOR . 'schema_example_alter.yaml', true, false );
	//echo '<h2>YAML ALter</h2><div style="margin:20px 0;">'.$sql.'</div>';
	
	}
	catch( Exception $ex ){
		
		die( $ex->getMessage() );
	}
	
	// Test to see if the Table (and Columns) exist in the Database
	$sql = "SELECT * FROM ". $trans->SafeEnclose('Accounts') . " WHERE 1=1 ";
	
	// Query the Table
	$rows = null;
	try {
		$stmt = $db->query( $sql );
		$rows = $stmt->fetchAll( PDO::FETCH_ASSOC );
	}
	catch( PDOException $ex ) {
		
		// Rethrow to Higher level
		throw new Exception('DB Builder (Schema): '. $ex->getMessage() );
	}
	
	echo '<div style="margin:20px 0;">'.print_r( $rows ).'</div>';
}