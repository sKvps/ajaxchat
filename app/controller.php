<?php
	require("const.php");
	require('classes/database_class.php');
	$db = new Database(array(
					'host'=>DBHOST,
					'username'=>DBUSER,
					'password'=>DBPASS,
					'dbname'=>DBNAME)
				   );	
?>
