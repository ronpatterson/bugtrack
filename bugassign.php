<?php
// bugassign.php
// Ron Patterson, WildDog Design
// MongoDB version
# bugassign.php
# Ron Patterson
#print_r($_SESSION);
$ttl="BugTrack Assignment Search";
require("bugcommon.php");
$id=$_POST["id"];
$bid=$_POST["bid"];
# show the selection page
?>
<div class="bugform">
<form name="bt_form9" id="bt_form9" enctype="x-www-form-encoded" onsubmit="return bt.assign_list();">
<h5>You can search on any of the fields listed below. The more
information you fill in, the narrower the search becomes.</h5>
<input type="hidden" name="id" value="<?php echo $id; ?>">
<input type="hidden" name="bid" value="<?php echo $bid; ?>">

<fieldset>
	<legend>BugTrack Assignment Search</legend>
	<label for="lname">Last Name:</label>
	<div class="fields2"><input type="text" name="lname" id="lname" size="22"></div><br class="clear">
	<label for="fname">First Name:</label>
	<div class="fields2"><input type="text" name="fname" id="fname" size="22"></div><br class="clear">
	<label>&nbsp;</label>
	<div class="fields2"><input type="submit" name="find" value="Start Search"> <input
 type="reset"></div><br class="clear">
	<div id="assign_results"></div>
	</fieldset>
</form>
</div>
