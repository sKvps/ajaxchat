<?php require("controller.php"); ?>
<?php
    session_start();
    header("Content-type: application/x-javascript");
	if(!isset($_GET['action'])) exit("{status:\"Parameter 'action' is required\"}");
    $action = $_GET['action'];
	switch($action) {
	    case "login":
		case "logout":
		case "init":
		   	require("classes/ChatAuth_class.php");
			ChatAuth::init();
			if($action == "logout") {
				if(ChatAuth::logout($_SESSION['auth_user']->user_id)) {
					die("{login_status: false}");
				}
				die("{login_status: true}");
			} elseif ($action == "init") {
			   if(isset($_SESSION['auth_user']) && ChatAuth::checkLogin($_SESSION['auth_user']->user_id)) {
				   die("{userData: ".json_encode($_SESSION['auth_user']).", status: 'Welcome, ".ucfirst($_SESSION['auth_user']->username)."', login_status: true}");
			   }										
			   else {
				  die("{status: 'Please login...', login_status: false}");
			   }
			}
			if(!empty($_POST['username']) && !empty($_POST['password'])) {
				$username=strtolower($_POST['username']); 
				$password=sha1($_POST['password']);
				if(ChatAuth::authenticate($username, $password)) {
				     die("{userData: ".json_encode($_SESSION['auth_user']).",status: 'Welcome, ".ucfirst($_SESSION['auth_user']->username)."', login_status: true}");
				} else {
				     die("{status: 'You have entered invalid credentials', login_status: false}");
				}
			} else {
			   die("{status: ''}");
			}
			break;
		case "set_user_status":
		case "get_user_info":
		case "new_conv":
			require("classes/conversation.php");
			if($action == "get_user_info" || $action == "set_user_status") {
				UserInfo::init();
			}
			switch($action) {
				case "get_user_info":
					echo UserInfo::get($_POST['user_id']);
					exit;
				case "set_user_status":
					echo UserInfo::setStatus($_POST['status']); 
					exit;
			}
			ConversationManager::init();
			echo ConversationManager::c_new($_POST['from'], $_POST['to']);
			break;
		case "poll":
			require("classes/buddies.php");
			BuddiesManager::init();
			$json = BuddiesManager::pollBuddies($_SESSION['auth_user']->user_id);
			if(isset($_POST['conv_id']) && isset($_POST['lasttime'])) {
				require("classes/conversation.php");
				$json .= MessagingManager::pollMessages($_POST['conv_id'], $_POST['lasttime'])."\n}";
			}
			echo $json.", my_status: {".BuddiesManager::getMyStatus()."}\n}";
			break;
		case "send_message":
		case "get_messages":
			require("classes/conversation.php");
			MessagingManager::init();
			if($action=="get_messages") {
				$json = MessagingManager::getMessages($_POST['conv'], (($_POST['first'] == 'true') ? true : false), $_POST['lastID']);
			} else {	
				$json = MessagingManager::sendMessage($_POST['conv_id'], $_POST['message']);
			}
			echo $json;
			break;
	}
?>