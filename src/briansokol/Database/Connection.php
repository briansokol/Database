<?php

namespace Database;

require_once('Expression.php');

class Connection {

	protected $conn;
	protected $connInfo = array();
	protected $debug = FALSE;
	protected $lastInsert = null;
	protected $affectedRows = null;
	protected $readyToConnect = false;
	protected $lastUnpreparedQuery = "";


	public function __construct($host, $user, $pass, $db, $charset = 'utf8') {
		$this->setCredentials($host, $user, $pass, $db, $charset);
	}

	public function setCredentials($host, $user, $pass, $db, $charset = 'utf8') {
		if (!empty($host) && !empty($user) && !empty($pass) && !empty($db)) {
			$this->connInfo['hostname'] = $host;
			$this->connInfo['database'] = $db;
			$this->connInfo['username'] = $user;
			$this->connInfo['password'] = $pass;
			$this->connInfo['charset'] = $charset;
			$this->readyToConnect = true;
		} else {
			$this->readyToConnect = false;
		}
	}

	public function isReadyToConnect() {
		return $this->readyToConnect;
	}

	public function connect() {
		if ($this->isReadyToConnect()) {
			$this->conn = new \PDO('mysql:host='.$this->connInfo['hostname'].';dbname='.$this->connInfo['database'].';charset='.$this->connInfo['charset'], $this->connInfo['username'], $this->connInfo['password']);
			$this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			return true;
		} else {
			return false;
		}
	}

	public function query($sql, $params = null) {
		if ($this->debug == false || !strncasecmp("SELECT", trim($sql), 6)) {
			if (is_array($params)) {
				$stmt = $this->conn->prepare($sql);
				$stmt->execute($params);
				$this->lastUnpreparedQuery = $sql;
				return $stmt->fetchAll(\PDO::FETCH_ASSOC);
			} else {
				$this->lastUnpreparedQuery = $sql;
				$result = $this->conn->query($sql);
				return $result->fetchAll(\PDO::FETCH_ASSOC);
			}
		}
		return FALSE;
	}
	
	public function queryPrepared($sql, $params) {
		return $this->query($sql, $params);
	}

	public function insert($table, $data, $onDuplicateData = null) {
		if ($this->debug == TRUE) {
			return false;
		}
		
		if (empty($data)) {
			return false;
		}
		
		$binds = array();
		
		$sql = "INSERT INTO `$table` SET ";
		$values = array();
		foreach ($data as $key => $value) {
			if ($value instanceof Expression) {
				$values[] = "`$key` = ".$value->getExpression();
			} elseif (is_null($value)) {
				$values[] = "`$key` = NULL";
			} else {
				$values[] = "`$key` = :$key";
				$binds[':'.$key] = $value;
			}
		}
		$sql .= implode(",", $values);
		if (is_array($onDuplicateData) && !empty($onDuplicateData)) {
			$values = array();
			foreach ($onDuplicateData as $key => $value) {
				if ($value instanceof Expression) {
					$values[] = "`$key` = ".$value->getExpression();
				} elseif (is_null($value)) {
					$values[] = "`$key` = NULL";
				} else {
					$values[] = "`$key` = :$key";
					$binds[':'.$key] = $value;
				}
			}
			$sql .= ' ON DUPLICATE KEY UPDATE ' . implode(",", $values);
		}
		$stmt = $this->conn->prepare($sql);
		$this->lastUnpreparedQuery = $sql;
		$stmt->execute($binds);
		$this->affectedRows = $stmt->rowCount();
		$this->insertId = $this->conn->lastInsertId();
		return $this->insertId;
	}

	public function update($table, $data, $where = NULL) {
		if ($this->debug == TRUE) {
			return false;
		}
		
		if (empty($data)) {
			return false;
		}
		
		$binds = array();
		
		$sql = "UPDATE `$table` SET ";
		foreach ($data as $key => $value) {
			if ($value instanceof Expression) {
				$values[] = "`$key` = ".$value->getExpression();
			} elseif (is_null($value)) {
				$values[] = "`$key` = NULL";
			} else {
				$values[] = "`$key` = :$key";
				$binds[':'.$key] = $value;
			}
		}
		$sql .= implode(",", $values);
		if (!empty($where))
			$sql .= " WHERE $where";
		$stmt = $this->conn->prepare($sql);
		$this->lastUnpreparedQuery = $sql;
		$stmt->execute($binds);
		$this->affectedRows = $stmt->rowCount();
		$this->insertId = $this->conn->lastInsertId();
		return $this->affectedRows;
	}

	public function getAdapter() {
		return $this->conn;
	}

	public function getConnectionInfo() {
		return $this->connInfo;
	}

	public function debug($mode = NULL) {
		if (is_null($mode)) {
			return $this->debug;
		} elseif (is_bool($mode)) {
			$this->debug = $mode;
			return TRUE;
		} else {
			return FALSE;
		}

	}

	public function getLastInsertID() {
		return $this->lastInsert;
	}

	public function getAffectedRows() {
		return $this->lastInsert;
	}

	public function getLastError() {
		return $this->lastError;
	}

	public function getLastUnpreparedQuery() {
		return $this->lastUnpreparedQuery;
	}

	public function escape($string) {
		return $this->conn->quote($string);
	}
}

?>
