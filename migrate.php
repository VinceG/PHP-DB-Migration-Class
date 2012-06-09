<?php
date_default_timezone_set("America/Los_Angeles");
require_once('config.php');

$settings = array(
				'dbName' => M_DB_NAME,
				'dbHost' => M_DB_HOST,
				'dbUser' => M_DB_USER,
				'dbPass' => M_DB_PASS,
				'dbExtra' => M_DB_EXTRA
			);

require_once('lib/Migration.php');
$migration = Migration::factory(M_DB_DRIVER, $settings, true);
$migration->start();