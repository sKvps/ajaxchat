<?php 
//	set_error_handler();
	function escape($str) {
		if (!get_magic_quotes_gpc())
			return mysql_escape_string($str);
		return addslashes($str);
	}
    class ConversationManager {
	    private static $db;
		public static function checkConversationExists($t, $f) {	
			$chTq = self::query("show tables");
			while($o = $chTq->fetch_array()) {
				 if($o[0] != 'auth_users') {
					  $table = $o[0];
					  $cr = explode("with", str_replace("conv_", "", $table));
					  if(($cr[0] == $t && $cr[1] == $f) || ($cr[0] == $f && $cr[1] == $t)) {
						  $c_n = new Conversation($cr[0], $cr[1]);
	 					  return self::createConversation($c_n);
					  }
				 }
			}
			return false;
		}
		public static function c_new($from, $to) {
			$ce = self::checkConversationExists($from, $to);
			if($ce) { 
				return $ce;
			}
			$c = new Conversation($from, $to);
			$cqs = " CREATE TABLE `".escape($c->conv_id)."` (
					`message` TEXT NOT NULL,
					`msg_id` INT( 250 ) NOT NULL AUTO_INCREMENT,
					`tme` BIGINT(255) NOT NULL,
					`from` INT( 200 ) NOT NULL,
					`to` INT( 200 ) NOT NULL,
					 PRIMARY KEY ( `msg_id` )
					) ENGINE = InnoDB;";
			if(!$create = self::query($cqs)) {
				die("{error:'".addslashes(mysqli_error(self::$db))."', login_status: true}");
			}
			return self::createConversation($c);
		}
		private static function createConversation(Conversation $c) {
			$ins_conv = self::query("select * from `".$c->conv_id."`;");
			$json = "{\nConversation: {\nconv_id: '".$c->conv_id. "', from: ".json_encode($c->from).", to: ".json_encode($c->to).", messages: [\n";
			if(mysqli_errno(self::$db)) {
				$n_c = explode("with", str_replace("conv_", "", $c->conv_id));
				$ins_conv = self::query("select * from `conv_".$n_c[1]."with".$n_c[0]."`;");
				if(mysqli_errno(self::$db)) 
					return "{error: 'Table was deleted!'}";
			}
			while($o = $ins_conv->fetch_object()) {
			    $json .= "{from: '".self::getChatterName($o->from)."', msg_id:".$o->msg_id.", message: '".escape($o->message)."', tme: '".date("g:i", $o->tme)."'},";
			}
			$json = substr($json, 0, strlen($json)-1);
			$json .= "]\n}\n}";
			return $json;
		}
		private static function getChatterName($u_id) {
			$gcn_q = self::query("select username from auth_users where user_id = {$u_id} limit 1;");
			return $gcn_q->fetch_object()->username;
		}
		public static function query($s) {
			return self::$db->query($s);
		}
		public static function init() {
			self::$db = Database::getResource();
		}
	}
    class Conversation {
		public $conv_id, $from, $to;
		public function __construct($from, $to) {
			$udQS = "select username, user_id from auth_users where user_id = ";
			$fudQ = ConversationManager::query($udQS.$from." limit 1;");
			if($fudQ) 
				$this->from = $fudQ->fetch_object();
			else 
				$this->from = $from;
			$tudQ = ConversationManager::query($udQS.$to." limit 1;");
			if($tudQ)
				$this->to = $tudQ->fetch_object();
			else 
				$this->to = $to;
			$this->conv_id = "conv_".$from."with".$to;
		}
	}
	class UserInfo {
		private static $db;
		public static function get($u_id) {
			$q = self::$db->query("select * from auth_users where user_id = ".$u_id." and logged_in = 1 limit 1;");
			$o = $q->fetch_object();
			$json = "{\nUserInfo: {user_id: ".$o->user_id.", status:'".stripslashes($o->status)."', username: '".ucfirst($o->username)."'}\n}";
			return $json;
		}
		public static function setStatus($status) {
			$s = htmlspecialchars(addslashes($status));
			$qs = "update `auth_users` set `status` = '".$s."', stat_time = UNIX_TIMESTAMP( ) where user_id = ".$_SESSION['auth_user']->user_id;
			$q = self::$db->query($qs);
			if(mysqli_affected_rows(self::$db) >= 1) return "{result:true}";
			return "{result:'".mysqli_error(self::$db)."'}";
		}
		public static function init() {
			self::$db = Database::getResource();
		}
	}
	class MessagingManager {
		private static $db;
		public static function init() {
			self::$db = Database::getResource(); 
		}
		private static function query($str) {
			return self::$db->query($str);
		}
		public static function sendMessage($conv, $message) {
			$message=escape($message);
			$od = explode("with", str_replace("conv_", "", $conv));
			$f = $_SESSION['auth_user']->user_id;
			$t = ($f==$od[0]) ? $od[1] : $od[0];
			$qs = "INSERT INTO `".$conv."` (
					`message` ,
					`msg_id` ,
					`tme` ,
					`from` ,
					`to`
					)
					VALUES (
					'".$message."', '', UNIX_TIMESTAMP( ) , '".$f."', '".$t."'
					);";
			$ins_q = self::query($qs);
			return "{msg_status: ".((self::$db->affected_rows >= 1) ? "'sent'" : "'failed'")."}";
		}
		private static function getChatterName($u_id) {
			$gcn_q = self::query("select username from auth_users where user_id = {$u_id} limit 1;");
			return $gcn_q->fetch_object()->username;
		}
		public static function getMessages($conv, $first, $lastID = 0)  {
			$gqs = ($first) ? "select * from `".$conv."`;" : "select * from `".$conv."` where `msg_id` > ".$lastID.";";
			$gq = self::query($gqs);
			if(mysqli_errno(self::$db)) return "{error:'Table was deleted!', error_msg:'".mysqli_error(self::$db)."'}";
			if($gq->num_rows < 1) return "{error:'No rows returned!'}";
			$json = "{ messages: [";
			while($o = $gq->fetch_object()) {
				$json .= "{message: '".escape($o->message)."', from: '".self::getChatterName($o->from)."', to: '".self::getChatterName($o->to)."', msg_id:".$o->msg_id.", tme: '".date("g:ia", $o->tme)."'},";
			}
			$j = substr($json, 0, strlen($json)-1);
			$j .= "]\n}";
			return $j;
		}
	}
?>