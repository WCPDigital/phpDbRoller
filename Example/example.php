<?php
namespace{

	//require_once('../DbRoller/bootstrap.php');
	require_once('../DbRoller/autoload.php');
	
	use \DbRoller\Translators\MySQLTranslator;
	use \DbRoller\Translators\MSSQLTranslator;
	use \DbRoller\Translators\SQLiteTranslator;
	use \DbRoller\Rollers\JsonRoller;
	use \DbRoller\Rollers\XmlRoller;
	
	try{
		$host = '';
		$database = '';
		$username = '';
		$password = '';
		
		// MySQL: Connect to DB and Execute
		//$db = new PDO('mysql:host='.$host.';dbname='.$database.';charset=utf8', $username, $password );
		
		// MS SQL; Connect to DB and Execute
		$db = new PDO('dblib:host='.$host.';dbname='.$database.';charset=utf8', $username, $password );

		// SQLite; Connect to DB and Execute	
		//$db = new PDO('sqlite:data/example.sqlite3');
		
		// Set error mode
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
	catch( PDOException $ex ) {
		
		// Rethrow to Higher level
		throw new Exception('DB Builder (Schema): '. $ex->getMessage() );
	}
	
	// Create a translator
	//$trans = new MySQLTranslator();
	$trans = new MSSQLTranslator();
	//$trans = new SQLiteTranslator();

	
	// JSON
	// Create a builder
	//$builder = new JsonRoller( $db, $trans );
	
	// Build the Schema and update the Database
	//$sql = $builder->BuildFromFile( __DIR__ . DIRECTORY_SEPARATOR . 'schema_example_create.json', true, true );
	//echo '<div style="margin:20px 0;">'.$sql.'</div>';
	
	// Build the Schema and update the Database
	//$sql = $builder->BuildFromFile( __DIR__ . DIRECTORY_SEPARATOR . 'schema_example_alter.json', true, false );
	//echo '<div style="margin:20px 0;">'.$sql.'</div>';
	
	
	// XML
	// Create a builder
	$builder = new XmlRoller( $db, $trans );
	
	// Build the Schema and update the Database
	$sql = $builder->BuildFromFile( __DIR__ . DIRECTORY_SEPARATOR . 'schema_example_create.xml', true, true );
	echo '<div style="margin:20px 0;">'.$sql.'</div>';
	
	// Build the Schema and update the Database
	//$sql = $builder->BuildFromFile( __DIR__ . DIRECTORY_SEPARATOR . 'schema_example_alter.xml', true, false );
	//echo '<div style="margin:20px 0;">'.$sql.'</div>';
	
	
	// Test to see if the Table (and Columns) exist in the Database
	$sql = "SELECT * FROM Accounts WHERE 1=1 ";
	
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