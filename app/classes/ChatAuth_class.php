<?php
     class ChatAuth {
		 public static function authenticate($username, $password) {
		      $auth_res = self::query("select `user_id`, `username` from `auth_users` 
									  where `username` = '".$username."' 
									  and `h_pw` = '".$password."'");
			  if($auth_res->num_rows <= 0) return false;
			  $auth_obj = $auth_res->fetch_object();
			  $_SESSION['auth_user'] = $auth_obj;
			  $up_li = self::query("update `auth_users` set `logged_in` = 1 where `user_id` = ".$auth_obj->user_id);
			  $auth_res->free_result();
			  $_SESSION['auth_user']->logged_in = true;
			  return true;
		 }
		 public static function logout($user_id) {
		 	$auth_logout = self::query("update `auth_users` set `logged_in` = 0 where `user_id` = ".$user_id);
			if($auth_logout->affected_rows >= 1) {
				session_unregister(session_name());
				$_SESSION = array();
				session_destroy();
				return true;
			}
			return false;
		 }
		 public static function checkLogin($user_id) {
		 	$cliQuery = self::query("select username from `auth_users` where logged_in = 1 and user_id = {$user_id} limit 1;");
			return ($cliQuery->num_rows == 1);
		 }
		 private static function query($str) {
		 	return self::$db->query($str);
		 }
		 public static function init() {
		      self::$db = Database::getResource();
		 }
		 private static $db;
	 }
?>