<?php
// buglist1.php
// Ron Patterson, WildDog Design
// SQLite version
// return a standard <select> for a lookup table
#require("btsession.php");
// connect to the database 
require("bugcommon.php");
$max=50;
$start=isset($_POST["start"]) ? $_POST["start"] : "";
if ($start == "") $start=0;
$ttl="BugTrack Bugs List";
$dbh = $db->getHandle();
?>
<p>Click on the ID to see details. Click column title to sort.</p>
<div style="width: 650px;">
<table id="bt_tbl" class="display" border="1" cellspacing="0" cellpadding="3" width="100%">
<thead>
<tr>
<th>ID</th><th>Description</th><th>Date entered</th><th>Status</th>
</tr>
</thead>
<tbody>
<?php
$start=isset($_POST["start"]) ? intval($_POST["start"]) : 0;
$type=isset($_POST["type"]) ? $_POST["type"] : "open";
$bugtype=isset($_POST["bug_type"]) ? $_POST["bug_type"] : "";
if ($start == "") $start=0;
$ttl="BugTrack Bugs List";
$otype="open";
$nextlink="";
$crit = array();
if ($type == "closed") {
	$ttl="BugTrack Closed List";
	$otype = "closed";
}
if ($type == "bytype") {
	$arr=split("[|]",$_POST["sel_arg"]);
	$cd=$arr[0];
	if ($cd == "0" or $cd == " ") {
		echo "<b>No bug type selected</b>";
		exit;
	}
	$stype=$arr[1];
	//$crit .= " and bug_type='$cd'";
	$crit["bug_type"] = $cd;
	$ttl="BugTrack $type List";
	$otype = "open";
}
if ($type == "bystatus") {
	$status=$_POST["sel_arg"];
	if ($status == "0" or $status == " ") {
		echo "<b>No status type selected</b>";
		exit;
	}
	$stype=$sarr[$status];
	$ttl="BugTrack $type List";
	$otype = $status;
}
if ($type == "bypriority") {
	$priority=isset($_POST["priority"]) ? $_POST["priority"] : "";
	if ($status == "0" or $status == " ") {
		echo "<b>No priority type selected</b>";
		exit;
	}
	$stype=$parr[$priority];
	$ttl="BugTrack $type List";
	$otype = "open";
}
if ($type == "assignments") {
	//$crit .= " and assigned_to='".$_SESSION['user_id']."'";
	$crit["assigned_to"] = $_SESSION['user_id'];
	#$uname=$_SESSION['uname'];
	#$sql = "select id,lname,fname,nname,portal_role,emploc from metaman.d20_person where empnbr=$empnbr";
	#$sth = $dbh->prepare($sql);
	#$sth->execute();
	#$arr = $sth->fetchColumn();
	$ttl="BugTrack My Assignments";
	#$type = "open";
	$otype = "open";
}
if ($type == "unassigned") {
	//$crit .= " and ifnull(assigned_to,'')=''";
	$crit['$or'] = array(
		array("assigned_to" => array('$exists' => false))
		,array("assigned_to" => null)
		,array("assigned_to" => "")
	);
	$ttl="BugTrack Unassigned";
	#$type = "open";
	$otype = "open";
}
if ($type == "undefined" and substr($otype,0,1)=='o')
	//$crit .= " and status<>'c'";
	$crit["status"] = array('$ne' => "c");
elseif ($type == "assignments" or $type == "unassigned" or $type == "bytype")
	//$crit .= " and status<>'c'";
	$crit["status"] = array('$ne' => "c");
else
	//$crit .= " and status='".substr($otype,0,1)."'";
	$crit["status"] = substr($otype,0,1);
$cnt2 = 0; $out = "";
// execute query 
$arrColls = $db->getBugs($crit,array("bug_id",-1));
if (count($arrColls) > 0) {
	// loop thru all rows
	foreach ($arrColls as $arr) 
	{
//list($id,$descr,$prod,$entry_id,$bug_type,$status,$priority,$comments,$solution,$assigned_to,$bug_id,$entry_dtm,$update_dtm,$closed_dtm) = $arr;
		//extract($arr);
		$entry_dt = date("m/d/Y g:i a",strtotime($arr["entry_dtm"]));
		//$class = $count%2==0 ? "even" : "odd";
		$id = (string)$arr["_id"];
		$bid = $arr["bug_id"];
		$edt = date("m/d/Y g:i a",$arr["entry_dtm"]->sec);
		$out .= <<<END
<tr valign="top">
<td><a href="#" onclick="return bt.bugshow(null,'$id','$bid');"><b>{$arr["bug_id"]}</b></a></td>
<td>{$arr["descr"]}</td>
<td>{$edt}</td>
<td>{$sarr[$arr["status"]]}</td>
</tr>
END;
		$cnt2++;
	}
	echo $out;
}
$start+=$cnt2;
?>
</tbody>
</table>
</div>
<!-- <b><?php echo $cnt2; ?> Bugs shown.</b> -->
<!-- <a href=viewphp1.php><b>View PHP code modules</b></a> -->
<br>
</center>
