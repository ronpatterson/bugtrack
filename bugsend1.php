<?php
// bugsend1.php
// Ron Patterson, WildDog Design
// MongoDB version
// connect to the database 
require("bugcommon.php");
$ttl="Send Record";
extract($_POST);
if (!isset($id)) $id = isset($_GET['id']) ? $_GET['id'] : "";
if ($id == "") die("ERROR: No ID provided!");

if (!isset($action) or $action == "") $action="form";
//$id = intval($id);
$uname = $_SESSION["user_id"];

// execute query 
$arr = $db->getBug($id);
if (count($arr) == 0) die("ERROR: Bug not found ($id)");
		//list($id,$descr,$product,$btusernm,$bug_type,$status,$priority,$comments,$solution,$assigned_to,$bug_id,$entry_dtm,$update_dtm,$closed_dtm) = $arr;
extract($arr);
$bt = $db->getBugTypeDescr($bug_type);
$dbh = $db->getHandle();
if ($user_nm != "") {
	$arr = get_user($dbh,$user_nm);
	$ename = "$arr[1] $arr[0]";
	$email = $arr[2];
} else {$ename=""; $email="";}
if ($assigned_to != "") {
	$arr = get_user($dbh,$assigned_to);
	$aname = "$arr[1] $arr[0]";
	$aemail = $arr[2];
} else {$aname=""; $aemail="";}
#$dvd_title = ereg_replace("\"","\\&quot;",$dvd_title);
if ($action == "send_email") {
// 	$descr = stripcslashes($descr);
// 	$comments = stripcslashes($comments);
// 	$solution = stripcslashes($solution);
	$edtm = $entry_dtm != "" ? date("m/d/Y g:i a",$entry_dtm->sec) : "";
	$udtm = $update_dtm != "" ? date("m/d/Y g:i a",$update_dtm->sec) : "";
	$cdtm = $closed_dtm != "" ? date("m/d/Y g:i a",$closed_dtm->sec) : "";
	$msg = "$msg2
	
Details of Bug ID $bug_id.

Description: $descr
Product or Application: $product
Bug Type: $bt
Status: $sarr[$status]
Priority: $parr[$priority]
Comments: $comments
Solution: $solution
Entry By: $ename
Assigned To: $aname
Entry Date/Time: $edtm
Update Date/Time: $udtm
Closed Date/Time: $cdtm

";
	$rows = !empty($arr["worklog"]) ? $arr["worklog"] : array();
	$msg .= count($rows)." Worklog entries found

";
	if (count($rows) > 0) {
		foreach ($rows as $row) {
			//list($wid,$bid,$usernm,$comments,$entry_dtm,$edtm)=$row;
			if ($row["user_nm"] != "")
			{
				$arr = get_user($dbh,$row["usernm"]);
				$ename = "$arr[2] $arr[1]";
			} else $ename="";
			$comments = stripcslashes($row["comments"]);
			$edtm = date("m/d/Y g:i a",$row["entry_dtm"]->sec);
			$msg .= "Date/Time: $edtm, By: $ename
Comments: {$row["comments"]}
";
		}
	}
	if (!preg_match("/@/",$sendto)) $sendto.="@wilddogdesign.com";
	if ($cc != "" and !preg_match("/@/",$cc)) $cc.="@wilddogdesign.com";
	if ($cc != "") $ccx="CC: $cc"; else $ccx="";
	//mail($sendto,$subject,stripcslashes($msg),$ccx);
}
?>
<div class="bugform">
<?php
if ($action == "email_bug"):
?>
<form name="form1" id="bt_email_form" method="post" onsubmit="javascript:return bt.send_email();"><br>
  <input type="hidden" name="action2" value="send_email">
  <input type="hidden" name="id" value="<?php echo $id; ?>">
  <input type="hidden" name="bug_id" value="<?php echo $bug_id; ?>">
  <input type="hidden" name="uname" value="<?php echo $uname; ?>">
	<fieldset>
		<legend>BugTrack Record</legend>
		<label>ID:</label>
		<div class="fields2"><?php echo $bug_id; ?></div><br class="clear">
		<label>Description:</label>
		<div class="fields2"><?php echo $descr; ?></div><br class="clear">
		<label for="sendto">Send to:</label>
		<div class="fields2"><input type="text" name="sendto" id="sendto" size="40"></div><br class="clear">
		<label for="cc">Send copy to (CC):</label>
		<div class="fields2"><input type="text" name="cc" id="cc" size="40"></div><br class="clear">
		<label for="subject">Subject:</label>
		<div class="fields2"><input type="text" name="subject" id="subject" size="40" value="<?php echo "$bug_id - $descr"; ?>"></div><br class="clear">
		<label for="msg2">Message to add:</label>
		<div class="fields2"><textarea name="msg2" id="msg2" rows="3" cols="40" wrap="virtual"></textarea></div><br class="clear">
 		<label>&nbsp;</label>
        <div class="fields2"><input type="submit" value="Send Bug Message"> <input
 type="reset"></div><br class="clear">
 		<div id="email_errors"></div>
	</fieldset>
  <br>
</form>
<?php
else:
	exit;
endif;
?>
