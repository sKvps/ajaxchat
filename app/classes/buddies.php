<?php
	class BuddiesManager {
		private static $db;
		private static function checkInitiatorOnline() {
			$iqs = "select username from auth_users where logged_in =1 and user_id = ".$_SESSION['auth_user']->user_id;
			$iq = self::query($iqs);
			if($iq->num_rows >= 1) return true;
			return false;
		}
		public static function getMyStatus() {
			$mqs = "select status, stat_time from auth_users where logged_in = 1 and user_id = ".$_SESSION['auth_user']->user_id;
			$mq = self::query($mqs);
			$mqo = $mq->fetch_object();
			if($mq->num_rows == 1) return "me: { user_id: ".$_SESSION['auth_user']->user_id.", stat_time: '".date("g:ia", $mqo->stat_time)."', status: '".addslashes($mqo->status)."'}";
			return "me: {error:'".mysqli_error(self::$db)."'}";
		}
		public static function pollBuddies($user_id) {
			$online = self::checkInitiatorOnline();
			if(!$online) return "{status:'You were unexpectedly disconnected from the server, please log-in again!'";
			$qs = "select `buddies`,`user_id` from `auth_users` where `logged_in` = 1 AND `user_id` != ".$user_id;
			$buddiesQuery = self::query($qs);
			if(!$buddiesQuery) return "{status:'You have no buddies online', login_status:true}";
			$json = "{buddies: [";
			while($o = $buddiesQuery->fetch_object()) {
				$budArray = explode(",", $o->buddies);
				foreach($budArray as $b) {
					if((int) $b == $user_id) {
						$json .= self::getBuddyInfo($o->user_id);
						break;
					}
				}
			}
			$json .= "] ";
			return $json;
		}
		private static function getBuddyInfo($b_id) {
			$qs = "select `username`, `status` from `auth_users` where `user_id` = ".$b_id." and `logged_in` = 1 limit 1;";
			$bq = self::query($qs);
			if(!$bq) return '{}';
			$bi = $bq->fetch_object();
			return "{username:'".$bi->username."', user_id: ".$b_id.", status:'".$bi->status."'},";
		}
		public static function query($s) { return self::$db->query($s); }
		public static function init() {
			self::$db = Database::getResource();
		}
	}
?>