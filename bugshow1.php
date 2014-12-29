<?php
// bugshow1.php
// Ron Patterson, WildDog Design
// MongoDB version
ini_set("display_errors", "1");
require("bugcommon.php");

//$id = isset($_POST['id']) ? intval($_POST['id']) : "";
$id = $_POST['id'];
if ($id == "") {
	echo "<b>No ID provided</b>\n";
	exit;
}

$ttl="Show Record";
// execute query 
$arr = $db->getBug($id);
if (empty($arr)) die("ERROR: Bug not found ($id)");
		//list($id,$descr,$product,$btusernm,$bug_type,$status,$priority,$comments,$solution,$assigned_to,$bug_id,$entry_dtm,$update_dtm,$closed_dtm) = $arr;
$descr = stripslashes($arr["descr"]);
$product = stripslashes($arr["product"]);
$comments = stripslashes($arr["comments"]);
$solution = stripslashes($arr["solution"]);
$user_nm = $arr["user_nm"];
$assigned_to = !empty($arr["assigned_to"]) ? $arr["assigned_to"] : "";
$edtm = !empty($arr["entry_dtm"]) ? date("m/d/Y g:i a",$arr["entry_dtm"]->sec) : "";
$udtm = !empty($arr["update_dtm"]) ? date("m/d/Y g:i a",$arr["update_dtm"]->sec) : "";
$cdtm = !empty($arr["closed_dtm"]) ? date("m/d/Y g:i a",$arr["closed_dtm"]->sec) : "";
$bt = $db->getBugTypeDescr($arr["bug_type"]);
# attachments are now in the db
$files="";
$rows = $db->getBugAttachments($id);
if (count($rows) > 0) {
	foreach ($rows as $row) {
		//list($aid,$fname,$size)=$row;
		$files.="<a href='get_file.php?id=".$id."' target='_blank'>{$row["file_name"]}</a> ({$row["file_size"]})<br>";
	}
}
$dbh = $db->getHandle();
$ename = ""; $email = "";
if (isset($user_nm) and $user_nm != "") {
	$arr2 = get_user($dbh,$user_nm);
	$ename = "$arr2[1] $arr2[0]";
	$email = $arr2[2];
};
$aname = "None"; $aemail = "";
if (isset($assigned_to) and $assigned_to != "") {
	$arr2 = get_user($dbh,$assigned_to);
	$aname = "$arr2[1] $arr2[0]";
	$aemail = $arr2[2];
} else $aname="";
$alink = ""; $elink = "";
//if (ereg($_SESSION["uname"],AUSERS)) {
if ($_SESSION["roles"] == "admin") {
//	$alink = "<a href='#' onclick='return bt.assign_locate(\"bugassign.php?id=$id\")'>Assign</a>";
	$alink = "<a href='#' onclick='return bt.assign_locate(\"$id\",\"{$arr["bug_id"]}\");'>Assign</a>";
}
	$elink = <<<END
<a href="#" onclick="return bt.bugedit(event,'$id','{$arr["bug_id"]}');">Edit record</a>
-- <a href="#" onclick="return delete_entry('$id','{$arr["bug_id"]}');">Delete</a> --
END;
//}
/*
$flist=glob("attachments/$bug_id"."___*");
if ($flist) {
	foreach ($flist as $filename) {
		$fn=ereg_replace($bug_id."___","",basename($filename));
		$files.="$sep<a href='$filename'>$fn</a>";
		$sep=", ";
	}
}
*/
$nextlink="x";
$type=isset($_GET["type"]) ? $_GET["type"] : "";
if ($type == "closed") {
	$nextlink="type=closed";
}
if ($type == "bytype") {
	$nextlink="type=bytype&bug_type=".$_GET["bug_type"];
}
if ($type == "bystatus") {
	$status=$_GET["status"];
	$nextlink="type=bystatus&status=$status";
}
if ($type == "assignments") {
	$uname=$_SESSION['uname'];
	$nextlink="type=assignments";
}
if ($type == "unassigned") {
	$nextlink="type=unassigned";
}
#$dvd_title = ereg_replace("\"","\\&quot;",$dvd_title);
?>
<div class="bugform">
<form name="form1" method="post" action="#">
<input type="hidden" name="update_list" id="update_list" value="0">
<input type="hidden" name="update_log" id="update_log" value="0">
<input type="hidden" name="id" id="id" value="<?php echo $id ?>">
<input type="hidden" name="bug_id" id="bug_id" value="<?php echo $arr["bug_id"] ?>">
</form>
<fieldset>
	<legend>BugTrack Record</legend>
	<label>ID:</label>
	<div class="fields2"><?php echo $arr["bug_id"]; ?></div><br class="clear">
	<label>Description:</label>
	<div class="fields2"><?php echo $arr["descr"]; ?></div><br class="clear">
	<label>Product or Application:</label>
	<div class="fields2"><?php echo $arr["product"]; ?></div><br class="clear">
	<label>Bug Type:</label>
	<div class="fields2"><?php echo $bt; ?></div><br class="clear">
	<label>Status:</label>
	<div class="fields2"><?php echo $sarr[$arr["status"]]; ?></div><br class="clear">
	<label>Priority:</label>
	<div class="fields2"><?php echo $parr[$arr["priority"]]; ?></div><br class="clear">
	<label>Comments:</label>
	<div class="fields2"><?php echo nl2br(addlinks($arr["comments"])); ?></div><br class="clear">
	<label>Solution:</label>
	<div class="fields2"><?php echo nl2br(addlinks($arr["solution"])); ?></div><br class="clear">
	<label>Attachments:</label>
	<div class="fields2"><div id="filesDiv"><?php echo $files; ?></div></div><br class="clear">
	<label>Entry By:</label>
	<div class="fields2"><a href="mailto:<?php echo $email; ?>"><?php echo $ename; ?></a></div><br class="clear">
	<label>Assigned To:</label>
	<div class="fields2"><div id="assignedDiv"><a href="mailto:<?php echo $aemail; ?>"><?php echo $aname; ?></a></div> <?php echo $alink; ?></div><br class="clear">
	<label>Entry Date/Time:</label>
	<div class="fields2"><?php echo $edtm; ?></div><br class="clear">
	<label>Update Date/Time:</label>
	<div class="fields2"><?php echo $udtm; ?></div><br class="clear">
	<label>Closed Date/Time:</label>
	<div class="fields2"><?php echo $cdtm; ?></div><br class="clear">
</fieldset>
<p align="center">
<?php echo $elink ?>
<a href="#" onclick="return bt.cancelDialog();">Show list</a>
-- <a href="#" onclick="return bt.email_bug('<?php echo $id; ?>');">Email Bug</a>
</p>
<div id="worklogDiv">
<?php
$rows = !empty($arr["worklog"]) ? $arr["worklog"] : array();
$count = count($rows);
echo "<p align='center'>$count Worklog entries found -- <a href='#' onclick='return bt.add_worklog(event,\"$id\");'>Add</a><p>\n";
if ($count > 0):
?>
<table border="1" cellspacing="0" cellpadding="3" class="worklog">
<?php
	foreach ($rows as $row) {
		//list($wid,$bid,$btusernm,$comments,$entry_dtm)=$row;
        if (empty($row)) continue;
		if ($row["user_nm"] != "") {
			$arr2 = get_user($dbh,$row["user_nm"]);
			$ename = "$arr2[1] $arr2[0]";
			$email = $arr2[2];
		} else {$ename=""; $email="";}
?>
<tr><td><b>Date/Time: <?php echo date("m/d/Y g:i a",$row["entry_dtm"]->sec); ?>, By: <a href="mailto:<?php echo $email ?>"><?php echo $ename; ?></a></b></td></tr>
<tr><td cellspan="2"><?php echo nl2br(addlinks($row["comments"])); ?></td></tr>
<?php
	}
endif;
?>
</table>
</div>
<script type="text/javascript">get_files('<?php echo $id ?>');</script>
