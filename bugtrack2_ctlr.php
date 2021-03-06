<?php
ini_set("display_errors", "on");
require_once("btsession.php");
// bugtrack_ctlr.php - BugTrack controller
// Ron Patterson, WildDog Design
// MongoDB version
// if ($_SESSION['user_id']=="") {
// 	die("<html><b>Not logged in!!<p><a href=login.php>Login</a></b></html>");
// }
date_default_timezone_set('America/Denver');
// connect to the database
require_once("dbdef.php");
//require("BugTrack.class.php");
require("BugTrackMongo.class.php");
$db = new BugTrack($dbpath);

$args = $_POST;
switch ($args["action"])
{
	case "bt_init":
		$results = $db->getBTlookups();
		echo json_encode($results);
		break;
	case "bt_check_session":
		echo $db->check_session();
		break;
	case "bt_login_handler":
		echo json_encode($db->login_session($args["uid"],$args["pw"]));
		//print_r($_SESSION);
		break;
	case "bt_logout_handler":
		$_SESSION["user_id"] = "";
		//print_r($_SESSION);
		break;
	case "list":
	case "list2":
		$results = $db->getBugs($args["type"],json_decode($args["sel_arg"]));
		//$results = $db->getBugs();
		echo json_encode($results);
		break;
	case "getUsersSearch":
		$results = $db->getUserEntries($args);
		echo json_encode($results);
		break;
	case "assign_user":
		$results = $db->assign_user($args);
		echo $results;
		break;
	case "show":
	case "add":
	case "edit":
		$results = $db->getBug($args["id"]);
		echo json_encode($results);
		break;
	case "add_update":
		if ($args["id"] == "") {
			$result = $db->addBug($args);
		} else {
			$result = $db->updateBug($args);
		}
		echo $result;
		break;
	case "delete":
		$results = $db->deleteBug($args["id"]);
		echo $results;
		break;
	case "get_worklog_entries":
		$results = $db->get_worklog_entries($args["id"]);
		echo $results;
		break;
	case "worklog_add":
		$results = $db->addWorkLog($args);
		echo $results;
		break;
	case "get_files":
		$results = $db->getBugAttachments($args["id"]);
		echo json_encode($results);
		break;
	case "get_module":
		echo file_get_contents($args["file"]);
		break;
	case "email_bug":
		$results = $db->do_bug_email($args);
		echo $results;
		break;
	case "admin_users":
		$recs = $db->getUserEntries();
		echo json_encode($recs);
		break;
	case "bt_user_show":
		$recs = $db->getUserRec($args["uid"]);
		echo json_encode($recs);
		break;
	case "user_add_update":
		if ($args["uid"] == "") {
			$result = $db->addUser($args);
		} else {
			$result = $db->updateUser($args);
		}
		echo $result;
		break;
	default:
		die("ERROR: Unknown arguments, ".print_r($args,1));
}
$db = null;
?>
