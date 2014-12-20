<?php
// Ajax module to build a file selection list

// Ron Patterson, WildDog Design
// PDO version

ini_set("display_errors", "on");

extract($_POST);
#echo "Date: $mtg_dt, type: $mtg_type";

$filelink = 1;
$fileedit = 1;

require("bugcommon.php");
require("dbdef.php");
require("BugTrackMongo.class.php");

$bug = new BugTrack($dbpath);

$id = $_REQUEST["id"];
# attachments are now in the db
$files="";
$rows = $bug->getBugAttachments($id);
if (count($rows) > 0)
{
	$i = 0;
	foreach ($rows as $row) {
		//list($aid, $fname, $size)=$row;
		$files.="<a href='get_file.php?id={$id}&idx={$i}' target='_blank'>{$row["file_name"]}</a> (<a href='#' onclick='return bt.remove_file(event,\"{$id}\",{$i});'>Remove</a>) ({$row["file_size"]})<br />";
	}
}
if ($files == "") $files = "None";
$files .= " <a href='#' onclick='return add_file();'>Upload file</a>";
echo $files;
?>
