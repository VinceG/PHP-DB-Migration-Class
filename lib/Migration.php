<?php
/**
 * PHP DB Migration Class
 * Migration code based off Yii Framework
 * Description: Allows one to use migrations within any project without requiring to use a full blown framework
 * Currently supports MYSQL but class uses PDO so can support all PDO supported drivers
 * @author Vincent Gabriel <http://vadimg.com>
 * @since 06-09-2012
 */
class Migration {
	/**
	 * Migration table name
	 */
	public $migrationTable = 'migrations';
	/**
	 * Migration directory
	 */
	public $migrationDirectory = 'migrations';
	/**
	 * Migration path
	 */
	public $migrationPath = null;
	/**
	 * PDO object
	 * @var object
	 */
	protected $handle = null;
	/**
	 * Migration internal path variable
	 */
	protected $_migrationPath = null;
	/**
	 * Base migration name
	 */
	protected $baseMigration = 'm000000_000000_base';
	/**
	 * Singleton object
	 * @var object
	 */
	protected static $instance = null;
	/**
	 * Specific driver object
	 * @var object
	 */
	protected $obj = null;
	/**
	 * PDO Prepare statement
	 */
	protected $prepare;
	/**
	 * DB Name
	 */
	protected $dbName;
	/**
	 * DB User
	 */
	protected $dbUser;
	/**
	 * DB Password
	 */
	protected $dbPass;
	/**
	 * DB Extra
	 * used for socket definition for example
	 */
	protected $dbExtra = '';
	/**
	 * DB Driver
	 */
	protected $dbDriver = 'mysql';
	/**
	 * DB Host
	 */
	protected $dbHost = 'localhost';
	/**
	 * Auto connect DB
	 */
	protected $autoConnect = false;
	/**
	 * Allowed settings to set
	 */
	protected $allowedToSet = array('dbName', 'dbUser', 'dbPass', 'dbExtra', 'dbHost');
	/**
	 * Class factory method
	 * @param string $driver
	 */
	public static function factory($driver='mysql', $dbSettings=array(), $autoConnect=false) {
		// Load the appropriate class driver
		switch(strtolower($driver)) {
			case 'mysql':
				self::getInstance()->dbDriver = 'mysql';
				// Load the corresponding sub class
				require_once('MySQLMigration.php');
				self::getInstance()->obj = new MySQLMigration;
				self::getInstance()->setObj(self::getInstance()->obj);
			break;
			
			default:
				throw new MigrationException('Sorry, That DB Driver is not supported.');
			break;
		}
		
		// Set db settings
		if(count($dbSettings)) {
			self::getInstance()->setDbSettings($dbSettings);
		}
		
		// Did we want to connect
		if($autoConnect) {
			self::getInstance()->autoConnect = true;
			// Connect
			self::getInstance()->connect();
		}
		
		// Set default fetch mode
		self::getInstance()->fetchMode = PDO::FETCH_OBJ;
		
		// Set the migration path
		self::getInstance()->setMigrationPath(self::getInstance()->migrationPath);
		
		// Return created object
		return self::getInstance()->getObj();
	}
	
	/**
	 * Set migration path
	 * @param string $path
	 */
	public function setMigrationPath($path) {
		self::getInstance()->migrationPath = $path;
		self::getInstance()->_migrationPath = self::getInstance()->migrationPath . self::getInstance()->migrationDirectory;
	}
	
	/**
	 * Migration initializer 
	 *
	 */
	public function start() {
		echo "\nPHP DB Migration v1.0\n\n";
		
		// Make sure path exists
		if(!is_dir(self::getInstance()->_migrationPath)) {
			self::getInstance()->error('The migration path ' . self::getInstance()->_migrationPath . ' does not exist.');
		}

		// Make sure wirteable
		if(!is_writeable(self::getInstance()->_migrationPath)) {
			self::getInstance()->error('The migration path ' . self::getInstance()->_migrationPath . ' is not writeable.');
		}
		
		$allowedActions = array('create', 'help', 'up', 'down', 'history', 'new', 'to', 'mark', 'redo');

		if(isset($_SERVER['argv'][1]) && $_SERVER['argv'][1]) {
			// Make sure it's in the allowed actions
			if(!in_array($_SERVER['argv'][1], $allowedActions)) {
				self::getInstance()->error("That action is not allowed. Run the command with help at the end");
			}
		}

		// Set action
		$action = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : 'up';
		$args = $_SERVER['argv'];
		if(isset($args[0])) {
			unset($args[0]);
		}
		
		if(isset($args[1])) {
			unset($args[1]);
		}
		
		$args = array_merge($args, array());
		
		// Figure out what we want to view
		switch($action) {
			case 'up':
			default:
				self::getInstance()->migrationUp($args);
			break;
			
			case 'down':
				self::getInstance()->migrationDown($args);
			break;
			
			case 'history':
				self::getInstance()->migrationHistory($args);
			break;
			
			case 'new':
				self::getInstance()->newMigrations($args);
			break;
			
			case 'to':
				self::getInstance()->migrationTo($args);
			break;
			
			case 'mark':
				self::getInstance()->migrationMark($args);
			break;
			
			case 'redo':
				self::getInstance()->migrationRedo($args);
			break;

			case 'create':
				self::getInstance()->createMigration($args);
			break;

			case 'help':
				self::getInstance()->showHelp();
			break;
		}

		exit;
	}
	
	/**
	 * Connect to database
	 *
	 */
	public function connect() {
		try {
			$dsn = self::getInstance()->dbDriver . ':host=' . self::getInstance()->dbHost . ';dbname=' . self::getInstance()->dbName . self::getInstance()->dbExtra;
			self::getInstance()->handle = new PDO($dsn, self::getInstance()->dbUser, self::getInstance()->dbPass);
			/* turn on exception throwing */
			self::getInstance()->handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (Exception $e) {
			throw new MigrationException('Could not connect to the database: ' . $e->getMessage());
		}
	}
	/**
	 * Set db settings
	 * @param array $settings
	 */
	public function setDbSettings(array $settings) {
		// Set db settings
		foreach($settings as $key => $value) {
			if(in_array($key, self::getInstance()->allowedToSet)) {
				self::getInstance()->$key = $value;
			}
		}
	}
	/**
	 * Singletone instance for this class
	 * @return object
	 */
	public static function getInstance() {
		if(self::$instance !== null) {
			return self::$instance;
		}
		self::$instance = new self;
		return self::$instance;
	}
	/**
	 * Return DB Specific object
	 *
	 */
	public function getObj() {
		return self::getInstance()->obj;
	}
	/**
	 * Return PDO Handler
	 *
	 */
	public function getDb() {
		return self::getInstance()->handle;
	}
	/**
	 * Set DB Specific object
	 *
	 */
	public function setObj($obj) {
		self::getInstance()->obj = $obj;
	}
	/**
	 * Disconnect from database kill PDO Handler
	 *
	 */
	public function disconnect() {
		self::getInstance()->handle = null;
	}
	/**
	 * Set the PDO Fetch Mode
	 *
	 */
	public function setFetchMode($mode) {
		self::getInstance()->fetchMode = $mode;
		return $this;
	}
	/**
	 * Return the current PDO fetch mode
	 *
	 */
	public function getFetchMode() {
		return self::getInstance()->fetchMode;
	}
	/**
	 * Prepare statement
	 *
	 */
	public function prepare($sql) {
		return self::getInstance()->handle->prepare($sql);
	}
	/**
	 * Query sql statement
	 *
	 */
	public function query($sql, $values=array()) {
		self::getInstance()->prepare = self::getInstance()->prepare($sql);
		self::getInstance()->prepare->execute();
		return self::getInstance()->prepare;
	}
	/**
	 * Execute sql statement
	 *
	 */
	public function exec($sql, $values=array()) {
		if(count($values)) {
			self::getInstance()->prepare = self::getInstance()->prepare($sql);
			return self::getInstance()->prepare->execute($values);
		} else {
			return self::getInstance()->handle->exec($sql);
		}
	}
	/**
	 * Quote value
	 *
	 */
	public function quote($v) {
		return self::getInstance()->handle->quote($v);
	}
	/**
	 * Fetch values from PDO statement
	 *
	 */
	public function fetch() {
		return self::getInstance()->prepare->fetch(self::getInstance()->getFetchMode());
	}
	/**
	 * Fetch all values from PDO Statement
	 *
	 */
	public function fetchAll() {
		return self::getInstance()->prepare->fetchAll(self::getInstance()->getFetchMode());
	}
	/**
	 * Bind value to a pdo statement
	 *
	 */
	public function bind($key, $value) {
		return self::getInstance()->prepare->bindValue($key, $value);
	}
	/**
	 * Get last insert ID
	 *
	 */
	public function lastInsertId($name=null) {
		return self::getInstance()->handle->lastInsertId($name);
	}
	/**
	 * being transaction
	 *
	 */
	public function transaction() {
		return self::getInstance()->handle->beginTransaction();
	}
	/**
	 * commit transaction
	 *
	 */
	public function commit() {
		return self::getInstance()->handle->commit();
	}
	/**
	 * rollback transaction
	 *
	 */	
	public function rollback() {
		return self::getInstance()->handle->rollBack();
	}
	/**
	 * class destructor
	 * @see disconnect
	 *
	 */
	public function __destruct() {
		self::getInstance()->disconnect();
	}
	/**
	 * Run migrations up
	 *
	 */
	protected function migrationUp($args) {
		if(($migrations=self::getInstance()->getNewMigrations())===array()) {
			self::getInstance()->error("No new migration found. Your system is up-to-date.");
		}

		$total=count($migrations);
		$step=isset($args[0]) ? (int)$args[0] : 0;
		if($step>0) {
			$migrations=array_slice($migrations,0,$step);
		}

		$n=count($migrations);
		if($n===$total) {
			echo "Total $n new ".($n===1 ? 'migration':'migrations')." to be applied:\n";
		} else {
			echo "Total $n out of $total new ".($total===1 ? 'migration':'migrations')." to be applied:\n";
		}

		foreach($migrations as $migration) {
			echo "    $migration\n";
		}

		echo "\n";

		if(self::getInstance()->confirm('Apply the above '.($n===1 ? 'migration':'migrations')."?")) {
			foreach($migrations as $migration) {
				if(self::getInstance()->migrateUp($migration)===false) {
					echo "\nMigration failed. All later migrations are canceled.\n";
					return;
				}
			}
			echo "\nMigrated up successfully.\n";
		}
	}
	/**
	 * Migrate up single migration
	 *
	 */
	protected function migrateUp($class) {
		if($class===self::getInstance()->baseMigration) {
			return;
		}

		echo "*** applying $class\n";
		$start=microtime(true);
		$migration=self::getInstance()->instantiateMigration($class);
		if($migration->up()!==false) {
			self::getInstance()->getObj()->insert(self::getInstance()->migrationTable, array(
				'version'=>$class,
				'apply_time'=>time(),
			));
			$time=microtime(true)-$start;
			echo "*** applied $class (time: ".sprintf("%.3f",$time)."s)\n\n";
		} else {
			$time=microtime(true)-$start;
			echo "*** failed to apply $class (time: ".sprintf("%.3f",$time)."s)\n\n";
			return false;
		}
	}
	/**
	 * Migrate down single migration
	 *
	 */
	protected function migrateDown($class) {
		if($class===self::getInstance()->baseMigration) {
			return;
		}

		echo "*** reverting $class\n";
		$start=microtime(true);
		$migration=self::getInstance()->instantiateMigration($class);
		if($migration->down()!==false) {
			self::getInstance()->getObj()->delete(self::getInstance()->migrationTable, '`version`=:version', array(':version'=>$class));
			$time=microtime(true)-$start;
			echo "*** reverted $class (time: ".sprintf("%.3f",$time)."s)\n\n";
		} else {
			$time=microtime(true)-$start;
			echo "*** failed to revert $class (time: ".sprintf("%.3f",$time)."s)\n\n";
			return false;
		}
	}
	/**
	 * Run migrations down
	 *
	 */
	protected function migrationDown($args) {
		$step=isset($args[0]) ? (int)$args[0] : 1;
		if($step<1) {
			self::getInstance()->error("Error: The step parameter must be greater than 0.");
		}

		if(($migrations=self::getInstance()->getMigrationHistory($step))===array()) {
			self::getInstance()->error("No migration has been done before.");
		}
		$migrations=array_keys($migrations);

		$n=count($migrations);
		echo "Total $n ".($n===1 ? 'migration':'migrations')." to be reverted:\n";
		foreach($migrations as $migration) {
			echo "    $migration\n";
		}
		echo "\n";

		if(self::getInstance()->confirm('Revert the above '.($n===1 ? 'migration':'migrations')."?")) {
			foreach($migrations as $migration) {
				if(self::getInstance()->migrateDown($migration)===false) {
					echo "\nMigration failed. All later migrations are canceled.\n";
					return;
				}
			}
			echo "\nMigrated down successfully.\n";
		}
	}
	/**
	 * Get migration history
	 *
	 */
	protected function migrationHistory($args) {
		$limit=isset($args[0]) ? (int)$args[0] : -1;
		$migrations=self::getInstance()->getMigrationHistory($limit);
		if($migrations===array()) {
			self::getInstance()->error("No migration has been done before.");
		} else {
			$n=count($migrations);
			if($limit>0) {
				echo "Showing the last $n applied ".($n===1 ? 'migration' : 'migrations').":\n";
			} else {
				echo "Total $n ".($n===1 ? 'migration has' : 'migrations have')." been applied before:\n";
			}

			foreach($migrations as $version=>$time) {
				echo "    (".date('Y-m-d H:i:s',$time).') '.$version."\n";
			}
		}
	}
	/**
	 * Get new migrations
	 *
	 */
	protected function newMigrations($args) {
		$limit=isset($args[0]) ? (int)$args[0] : -1;
		$migrations=self::getInstance()->getNewMigrations();
		if($migrations===array()) {
			self::getInstance()->error("No new migrations found. Your system is up-to-date.");
		} else {
			$n=count($migrations);
			if($limit>0 && $n>$limit) {
				$migrations=array_slice($migrations,0,$limit);
				echo "Showing $limit out of $n new ".($n===1 ? 'migration' : 'migrations').":\n";
			} else {
				echo "Found $n new ".($n===1 ? 'migration' : 'migrations').":\n";
			}
			foreach($migrations as $migration) {
				echo "    ".$migration."\n";
			}
		}
	}
	/**
	 * Create new migration
	 *
	 */
	protected function createMigration($args) {
		// Make sure we have set a name
		if(!isset($args[0])) {
			self::getInstance()->error('Please specify a name');
		}

		// Migration name
		$name = $args[0];

		// Make sure name is valud
		if(!preg_match('/^\w+$/',$name)) {
			err("The name of the migration must contain letters, digits and/or underscore characters only");
		}

		$name='m'.gmdate('ymd_His').'_'.$name;
		$content=strtr(self::getInstance()->getTemplate(), array('{className}'=>$name, '{parentClass}' => get_class(self::getInstance()->getObj())));
		$file=self::getInstance()->_migrationPath.DIRECTORY_SEPARATOR.$name.'.php';

		if(self::getInstance()->confirm("Create new migration '$file'?")) {
			file_put_contents($file, $content);
			echo "New migration created successfully.\n";
		}
	}
	/** 
	 * Show help text usage examples
	 *
	 */
	protected function showHelp() {
		echo <<<EOF
	USAGE
	  php migrate.php [action] [parameter]

	DESCRIPTION
	  This command provides support for database migrations. The optional
	  'action' parameter specifies which specific migration task to perform.
	  It can take these values: up, down, to, create, history, new, mark.
	  If the 'action' parameter is not given, it defaults to 'up'.
	  Each action takes different parameters. Their usage can be found in
	  the following examples.

	EXAMPLES
	 * php migrate.php
	   Applies ALL new migrations. This is equivalent to 'php migrate.php up'.

	 * php migrate.php create create_user_table
	   Creates a new migration named 'create_user_table'.

	 * php migrate.php up 3
	   Applies the next 3 new migrations.

	 * php migrate.php down
	   Reverts the last applied migration.

	 * php migrate.php down 3
	   Reverts the last 3 applied migrations.

	 * php migrate.php to 101129_185401
	   Migrates up or down to version 101129_185401.

	 * php migrate.php mark 101129_185401
	   Modifies the migration history up or down to version 101129_185401.
	   No actual migration will be performed.

	 * php migrate.php history
	   Shows all previously applied migration information.

	 * php migrate.php history 10
	   Shows the last 10 applied migrations.

	 * php migrate.php new
	   Shows all new migrations.

	 * php migrate.php new 10
	   Shows the next 10 migrations that have not been applied.


EOF;
	}

	/**
	 * Reads input via the readline PHP extension if that's available, or fgets() if readline is not installed.
	 */
	protected function prompt($message) {
		if(extension_loaded('readline')) {
			$input = readline($message.' ');
			readline_add_history($input);
			return $input;
		} else {
			echo $message.' ';
			return trim(fgets(STDIN));
		}
	}
	/**
	 * Return template used to create migrations
	 *
	 */
	protected function getTemplate() {
$t = <<<EOF
<?php
class {className} extends {parentClass}
{
	public function up()
	{
	}

	public function down()
	{
		echo "{ClassName} does not support migration down.\\n";
		return false;
	}
}

EOF;

		return $t;
	}

	/**
	 * Asks user to confirm by typing y or n.
	 */
	protected function confirm($message) {
		echo $message.' [yes|no] ';
		return !strncasecmp(trim(fgets(STDIN)),'y',1);
	}
	/**
	 * Get migration history
	 *
	 */
	protected function getMigrationHistory($limit) {
		if(self::getInstance()->getObj()->getTable(self::getInstance()->migrationTable)===false) {
			self::getInstance()->createMigrationHistoryTable();
		}

		$limit = $limit > 0 ? " Limit $limit" : ''; 
		$rows = self::getInstance()->query("SELECT * FROM ".self::getInstance()->migrationTable." ORDER BY version DESC".$limit)->fetchAll();
		$records = array();
		foreach($rows as $row) {
			$records[ $row['version'] ] = $row['apply_time'];
		}

		return $records;
	}
	/**
	 * Create new migration
	 *
	 */
	protected function createMigrationHistoryTable() {
		echo 'Creating migration history table "'.self::getInstance()->migrationTable.'"...';
		$sql = "CREATE TABLE IF NOT EXISTS `".self::getInstance()->migrationTable."` (
			          `version` varchar(250) NOT NULL DEFAULT '',
			          `apply_time` varchar(250) NOT NULL DEFAULT '',
			          PRIMARY KEY (`version`)
			        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
		self::getInstance()->exec($sql);
		self::getInstance()->getObj()->insert(self::getInstance()->migrationTable,array(
			'version'=>self::getInstance()->baseMigration,
			'apply_time'=>time(),
		));
		echo "done.\n";
	}
	/**
	 * Initiate migration and return object
	 *
	 */
	protected function instantiateMigration($name) {
		$file=self::getInstance()->_migrationPath.DIRECTORY_SEPARATOR.$name.'.php';
		require_once($file);
		$migration=new $name;
		return $migration;
	}
	/**
	 * Return a list of new migrations to run
	 *
	 */
	protected function getNewMigrations() {
		$applied=array();
		foreach(self::getInstance()->getMigrationHistory(-1) as $version=>$time) {
			$applied[substr($version,1,13)]=true;
		}

		$migrations=array();
		$handle=opendir(self::getInstance()->_migrationPath);
		while(($file=readdir($handle))!==false) {
			if($file==='.' || $file==='..') {
				continue;
			}
			$path=self::getInstance()->_migrationPath.DIRECTORY_SEPARATOR.$file;
			if(preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/',$file,$matches) && is_file($path) && !isset($applied[$matches[2]]))  {
				$migrations[]=$matches[1];
			}
		}
		closedir($handle);
		sort($migrations);
		return $migrations;
	}
	/**
	 * Redo migrations
	 *
	 */
	protected function migrationRedo($args) {
		$step=isset($args[0]) ? (int)$args[0] : 1;
		if($step<1) {
			self::getInstance()->error("Error: The step parameter must be greater than 0.\n");
		}

		if(($migrations=self::getInstance()->getMigrationHistory($step))===array()) {
			self::getInstance()->error("No migration has been done before.");
		}
		$migrations=array_keys($migrations);

		$n=count($migrations);
		echo "Total $n ".($n===1 ? 'migration':'migrations')." to be redone:\n";
		foreach($migrations as $migration) {
			echo "    $migration\n";
		}
		echo "\n";

		if(self::getInstance()->confirm('Redo the above '.($n===1 ? 'migration':'migrations')."?")) {
			foreach($migrations as $migration) {
				if(self::getInstance()->migrateDown($migration)===false) {
					echo "\nMigration failed. All later migrations are canceled.\n";
					return;
				}
			}
			foreach(array_reverse($migrations) as $migration) {
				if(self::getInstance()->migrateUp($migration)===false) {
					echo "\nMigration failed. All later migrations are canceled.\n";
					return;
				}
			}
			echo "\nMigration redone successfully.\n";
		}
	}
	/**
	 * Run migration to a specific one
	 *
	 */
	protected function migrationTo($args) {
		if(isset($args[0])) {
			$version=$args[0];
		} else {
			self::getInstance()->error('Please specify which version to migrate to.');
		}

		$originalVersion=$version;
		if(preg_match('/^m?(\d{6}_\d{6})(_.*?)?$/',$version,$matches)) {
			$version='m'.$matches[1];
		} else {
			self::getInstance()->error("Error: The version option must be either a timestamp (e.g. 101129_185401)\nor the full name of a migration (e.g. m101129_185401_create_user_table).");
		}
		// try migrate up
		$migrations=self::getInstance()->getNewMigrations();
		foreach($migrations as $i=>$migration) {
			if(strpos($migration,$version.'_')===0) {
				self::getInstance()->migrationUp(array($i+1));
				return;
			}
		}

		// try migrate down
		$migrations=array_keys(self::getInstance()->getMigrationHistory(-1));
		foreach($migrations as $i=>$migration) {
			if(strpos($migration,$version.'_')===0) {
				if($i===0) {
					echo "Already at '$originalVersion'. Nothing needs to be done.\n";
				} else {
					self::getInstance()->actionDown(array($i));
				}
				return;
			}
		}

		self::getInstance()->error("Error: Unable to find the version '$originalVersion'.\n");
	}
	/**
	 * Mark migration to a specific one without actually running them
	 *
	 */
	protected function migrationMark($args) {
		if(isset($args[0])) {
			$version=$args[0];
		} else {
			self::getInstance()->error('Please specify which version to mark to.');
		}
		$originalVersion=$version;
		if(preg_match('/^m?(\d{6}_\d{6})(_.*?)?$/',$version,$matches)) {
			$version='m'.$matches[1];
		} else {
			self::getInstance()->error("Error: The version option must be either a timestamp (e.g. 101129_185401)\nor the full name of a migration (e.g. m101129_185401_create_user_table).");
		}

		// try mark up
		$migrations=self::getInstance()->getNewMigrations();
		foreach($migrations as $i=>$migration) {
			if(strpos($migration,$version.'_')===0) {
				if(self::getInstance()->confirm("Set migration history at $originalVersion?")) {
					for($j=0;$j<=$i;++$j) {
						self::getInstance()->getObj()->insert(self::getInstance()->migrationTable, array(
							'version'=>$migrations[$j],
							'apply_time'=>time(),
						));
					}
					echo "The migration history is set at $originalVersion.\nNo actual migration was performed.\n";
				}
				return;
			}
		}

		// try mark down
		$migrations=array_keys(self::getInstance()->getMigrationHistory(-1));
		foreach($migrations as $i=>$migration) {
			if(strpos($migration,$version.'_')===0){
				if($i===0) {
					echo "Already at '$originalVersion'. Nothing needs to be done.\n";
				} else {
					if($this->confirm("Set migration history at $originalVersion?"))
					{
						for($j=0;$j<$i;++$j) {
							self::getInstance()->getObj()->delete(self::getInstance()->migrationTable, '`version`=:version', array(':version'=>$migrations[$j]));
						}
						echo "The migration history is set at $originalVersion.\nNo actual migration was performed.\n";
					}
				}
				return;
			}
		}

		self::getInstance()->error("Error: Unable to find the version '$originalVersion'.\n");
	}
	/**
	 * Show error
	 *
	 */
	protected function error($error) {
		echo $error . "\n";
		exit;
	}
}


class MigrationException extends Exception {
	
}