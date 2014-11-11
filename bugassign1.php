<?php
// bugassign1.php - Directory staff search results
// Ron Patterson, WildDog Design
// MongoDB version
require("bugcommon.php");
extract($_POST);
#print_r($_POST); exit;

if ($fname == " " && $lname == "") die("No search data entered!!");

$bid2=$id;
$lname1 = slashem($lname);
$fname1 = slashem($fname);

$dbh = $db->getHandle();
//reset($UsersArr);
$list = ""; $found = 0;
//$crit = array("active"=>"y");
//$coll = $dbh->bt_users->find($crit)->sort(array("lname"=>1,"fname"=>1));
$results = $db->getUserEntries();
foreach ($results as $arr)
{
	extract($arr);
	echo $lname1.$fname1;
	if ((trim($lname1) != "" and !preg_match("/".$lname1."/i",$lname)) or (trim($fname1) != "" and !preg_match("/".$fname1."/i",$fname))) continue;
	$list .= <<<END
  <tr>
    <td valign="TOP" height="16"><a href="#" onclick="return bt.do_assign('$bid2','$uid','$bid');">$lname, $fname</a></td> 
    <td valign="TOP" height="16">$uid</td> 
    <td valign="TOP" height="16">$email</td> 
  </tr>
END;
	++$found;
}
?>
<h5>Your search found <?php echo $found; ?> listing(s). Click
on the Last Name link to assign to BugTrack record.</h5>
<?php
if ($found > 0):
?>
<table width="520" border="1" cellspacing="0" cellpadding="3" class="worklog">
  <tr>
    <td width="40%" valign="TOP"><b>Name</b></td> 
    <td width="20%" valign="TOP"><b>Username</b></td> 
    <td width="40%" valign="TOP"><b>Email</b></td> 
  </tr>
  <?php echo $list ?>
</table>
<div id="results2"></div>
<?php

endif;
?>
