<?php
ini_set("display_errors", "on");
require_once("btsession.php");
// bugtrack_ctlr.php - BugTrack controller
// Ron Patterson
// SQLite version
// if ($_SESSION['user_id']=="") {
// 	die("<html><b>Not logged in!!<p><a href=login.php>Login</a></b></html>");
// }
// connect to the database 
require_once("dbdef.php");
//require("BugTrack.class.php");
require("BugTrackMongo.class.php");
$db = new BugTrack($dbpath);

$args = $_POST;
switch ($args["action"])
{
	case "bt_check_session":
		echo $db->check_session();
		break;
	case "bt_login_handler":
		echo $db->login_session($args["uid"],$args["pw"]);
		//print_r($_SESSION);
		break;
	case "bt_logout_handler":
		$_SESSION["user_id"] = "";
		//print_r($_SESSION);
		break;
	case "list":
		require_once("buglist.php");
		break;
	case "list2":
		require_once("buglist1.php");
		break;
	case "show":
		require_once("bugshow1.php");
		break;
	case "add":
	case "edit":
		require_once("bugedit.php");
		break;
	case "add_update":
		require_once("bugedit1.php");
		break;
	case "delete":
		$sql = "delete from bt_bugsx where id=?";
		$result = $db->execute($sql,array($args["id"]));
		if ($result) echo "SUCCESS";
		die("ERROR: Delete failed!");
		break;
	case "add_worklog":
		require_once("bugedit3.php");
		break;
	case "worklog_add":
		require_once("bugedit4.php");
		break;
	case "get_module":
		echo file_get_contents($args["file"]);
		break;
	case "email_bug":
	case "send_email":
		require_once("bugsend1.php");
		break;
	case "admin":
		require_once("bugadmin.php");
		break;
	case "admin_users":
		$recs = $db->getUserEntries();
		require_once("bugadmin1.php");
		break;
	case "bt_user_show":
		if ($args["uid"] != "")
		{
			$recs = $db->getUserRec($args["uid"]);
			$rec = $recs;
		}
		else
		{
			$rec = "";
		}
		require_once("bugadmin2.php");
		break;
	case "user_add_update":
		require_once("bugadmin3.php");
		break;
	case "help":
		require_once("bughelp.php");
		break;
	case "assign_search":
		require_once("bugassign.php");
		break;
	case "assign_list":
		require_once("bugassign1.php");
		break;
	case "assign_user":
		$recs = $db->assign_user($args["id"],$args["uid"]);
		echo $recs;
		break;
	default:
		die("ERROR: Unknown arguments, ".print_r($args,1));
}
$db = null;
?>
