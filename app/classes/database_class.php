<?php
	class Database {
		private static $db_resource;
		
		public function __construct($conn) {
			if(self::$db_resource instanceof mysqli) return;
			$conn=(object)$conn;
			self::$db_resource = new mysqli($conn->host, $conn->username, $conn->password, $conn->dbname);
			if(! self::$db_resource instanceof mysqli)
				throw new Exception(mysqli_error(self::$db_resource));
		}
		public static function getResource() {
			return self::$db_resource;
		}
	}
?>