<?php

ini_set( 'display_errors', 1 );

require __DIR__ . '/../../../maintenance/Maintenance.php';
require __DIR__ . '/CharacterConverter.php';

class ConvertWholeDatabase extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->mDescription = 'Convert a wiki database from latin1 to utf8mb4 tables';
		$this->addOption( 'db-name', 'Which database to convert (default is the local wiki DB)' );
	}

	public function execute() {
		$dbName = $this->getOption( 'db-name' ) ?? $GLOBALS['wgDBname'];

		$characterConverter = CharacterConverter::newFromDatabase( $dbName );
		$characterConverter->registerPreConversionCallback( function ( $tableName, $textColumns ) {
			$this->output( "Converting $tableName... columns: [" . implode(', ', $textColumns) . "]\n" );
		} );

		$characterConverter->convert();
	}
}

$maintClass = ConvertWholeDatabase::class;
require RUN_MAINTENANCE_IF_MAIN;
