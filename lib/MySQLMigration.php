<?php
/**
 * Mysql Migration Class
 *
 */
class MySQLMigration extends Migration {
	
	/**
	 * Return a list of tables within the selected database
	 * @return array
	 */
	public function getTables() {
		$rows = $this->query("SHOW TABLES;")->fetchAll(PDO::FETCH_COLUMN, 0);
		$tables = array();
		foreach($rows as $table) {
			$tables[$table] = $table;
		}

		return $tables;
	}
	/**
	 * Check if table exists in the database
	 * @param string $name
	 * @return boolean
	 */
	public function getTable($name) {
		$tables = $this->getTables();
		return isset($tables[$name]) ? true : false;
	}
	/**
	 * Add a column to a table in the database
	 * @param string $table
	 * @param string $column
	 * @param string $defenition
	 * @return boolean
	 */
	public function addColumn($table, $column, $definition) {
		return $this->exec("ALTER TABLE `".$table."` ADD `".$column."` ".$definition);
	}
	/**
	 * Drop column from table in database
	 * @param string $table
	 * @param string $column
	 * @return boolean
	 */
	public function dropColumn($table, $column) {
		return $this->exec("ALTER TABLE `".$table."` DROP `".$column."`;");
	}
	/**
	 * Empty table in the database
	 * @param string $table
	 * @return int
	 */
	public function emptyTable($table) {
		return $this->delete($table);
	}
	/**
	 * Drop table in the database
	 * @param string $table
	 * @return boolean
	 */
	public function dropTable($table) {
		return $this->exec("DROP TABLE `".$table."`");
	}
	/**
	 * Delete records from a table
	 * @param string $table
	 * @param string $condition
	 * @param array $params
	 * @return int
	 */
	public function delete($table, $condition=null, $params=array()) {
		// Do we have a condition
		$where = '';
		if($condition) {
			// Add to where
			$where = ' WHERE ' . $condition;
		}

		$sql = "DELETE FROM `".$table."`{$where}";

		$query = $this->prepare($sql);
		
		// Add in the params if we have any
		if(count($params)) {
			foreach($params as $k => $v) {
				$query->bindValue($k, $v);
			}
		}

		return $query->execute();
	}
	/**
	 * Insert records into a table
	 * @param string $table
	 * @param array $values
	 * @return int
	 */
	public function insert($table, $data) {
		$columns      = array();
		$placeholders = array();

		foreach ($data as $key => $val) {
			$columns[]      = "`".$key."`";
			$placeholders[] = ":$key";
		}

		$columns      = implode(', ', $columns);
		$placeholders = implode(', ', $placeholders);

		$sql   = "INSERT INTO `".$table."` ($columns) VALUES ($placeholders);";
		$query = $this->prepare($sql);

		foreach ($data as $key => $val) {
			$query->bindValue(":$key", $val);
		}

		return $query->execute();
	}
	/**
	 * Create new table in the database
	 * @param string $table
	 * @param array $values
	 * @return int
	 */
	public function createTable($name, $columns, $props=null) {
		$dbColumns = array();
		foreach($columns as $key => $value) {
			$dbColumns[] = "\t`".$key."` $value";
		}
		
		$sql  = "CREATE TABLE `".$name."` (\n";
		$sql .= implode(",\n", $dbColumns);
		$sql .= "\n\t)";
		
		// Do we have properties
		if($props) {
			$sql .= ' '.$props;
		}
		
		// Finish
		$sql .= ';';
		
		$query = $this->prepare($sql);
		return $query->execute();
	}
	/**
	 * Update records in a table
	 * @param string $table
	 * @param array $data
	 * @param string $condition
	 * @param array $params
	 * @return int
	 */
	public function update($table, $data, $condition=null, $params=array()) {		
		// Prepare values
		foreach($data as $a => $b) {
			$values[] = "`$a`=:$a";
		}
		
		// Implode values
		$values = implode(', ', $values);

		// Do we have a condition
		$where = '';
		if($condition) {
			// Add to where
			$where = ' WHERE ' . $condition;
		}

		$sql = "UPDATE `".$table."` SET $values{$where}";

		$query = $this->prepare($sql);

		foreach ($data as $key => $val) {
			$query->bindValue(":$key", $val);
		}
		
		// Add in the params if we have any
		if(count($params)) {
			foreach($params as $k => $v) {
				$query->bindValue($k, $v);
			}
		}

		return $query->execute();
	}
}