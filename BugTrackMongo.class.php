<?php
// BugTrackMongo.class.php
//
// Ron Patterson, WildDog Design/BPWC
//
// MongoDB version

define("AUSERS","ron,janie");
$sarr=array("o"=>"Open", "h"=>"Hold", "w"=>"Working", "y"=>"Awaiting Customer", "t"=>"Testing", "c"=>"Closed");
$parr=array("1"=>"High","2"=>"Normal","3"=>"Low");
$rarr=array("admin","ro","user");
$grparr=array("DOC"=>"Dept of Corrections","WDD"=>"WildDog Design");

function q ($val) {
	if (empty($val)) return "NULL";
	return "'".str_replace("'","''",$val)."'";
}

class BugTrack {
	protected $mdb;
	protected $db;
	protected $adir = "/usr/local/data/";
	protected $collsArray = array();
	protected $lookups = array();

	public function __construct ( $dbpath )
	{
		// MongoDB database version
		try
		{
			//$this->mdb = new MongoClient($dbpath);
			$this->mdb = new Mongo($dbpath);
			//$this->mdb->authenticate('admin','usI6F_-zy7yX');
			//$this->db = $this->mdb->php;
			$this->db = $this->mdb->bugtrack;
			//var_dump($this->db);
		}
		catch (Exception $e)
		{
			die("SQL CONNECTION ERROR: ".$e->getMessage());
			//header("Location: /dberror.html");
			exit;
		}
		$this->lookups = $this->getBTlookups();
	}

	public function __destruct ()
	{
		@$this->mdb->close();
		$this->mdb = null;
	}

	public function getCollections ()
	{
		$this->collsArray = $this->mdb->getCollectionNames();
		return $this->collsArray;
	}

	public function buildCollectionsList ()
	{
		if (count($this->collsArray) == 0) $this->getCollections();
		$out = "";
		foreach ($this->collsArray as $collName)
		{
			$coll = $db->selectCollection($collName);
			$sz = $coll->count();
			$out .= <<<END
	<li>{$collName} ({$sz}) <input type="checkbox" name="collections[]" value="{$collName}"></li>\n
END;
		}
		if ($out != "") return "<ul>\n".$out."</ul>";
		return "";
	}

	public function buildCollectionsTable ()
	{
		if (count($this->collsArray) == 0) $this->getCollections();
		$out = "";
		foreach ($this->collsArray as $collName)
		{
			$coll = $db->selectCollection($collName);
			$sz = $coll->count();
			$out .= <<<END
	<tr><td align="left">{$collName}</td><td align="center">{$sz}</td><td><input type="checkbox" name="collections[]" value="{$collName}"></td></tr>\n
END;
		}
		if ($out != "") return "<table><tr><th>Collection</th><th>Docs</th><th></th></tr>\n".$out."</table>";
		return "";
	}

	public function getBTlookupsOLD ()
	{
		global $sarr,$parr;
		$results = array();
		$coll = $this->db->bt_groups->find(array(),array("cd"=>1,"descr"=>1))->sort(array("descr"=>1));
		while ($coll->hasNext())
		{
			$results[] = $coll->getNext();
		}
		$results = array("bt_groups"=>$results);

		$results2 = array();
		$coll = $this->db->bt_type->find(array(),array("cd"=>1,"descr"=>1))->sort(array("descr"=>1));
		while ($coll->hasNext())
		{
			$results2[] = $coll->getNext();
		}
		$results["bt_types"] = $results2;
		$results["bt_status"] = $sarr;
		$results["bt_priority"] = $parr;
		$results["roles"] = isset($_SESSION["roles"]) ? $_SESSION["roles"] : "";
		return $results;
	}

	public function getBTlookups ()
	{
		$results = array();
		$coll = $this->db->bt_lookups->find(array(),array("_id"=>0));
		while ($coll->hasNext())
		{
			$lu = $coll->getNext();
			foreach (array("bt_type","bt_group","bt_status","bt_priority") as $type)
			{
				$arr = array();
				foreach ($lu[$type] as $item)
				{
					if ($item["active"] != "y") continue;
					$arr[] = array("cd"=>$item["cd"],"descr"=>$item["descr"]);
				}
				//sort($arr);
				$results[$type] = $arr;
			}
		}
		$results["roles"] = isset($_SESSION["roles"]) ? $_SESSION["roles"] : "";
		return $results;
	}

	public function getBTlookup ( $type, $cd )
	{
		$arr = $this->lookups[$type];
		for ($x=0; $x<count($arr); ++$x)
		{
			if ($arr[$x]["cd"] === $cd) return $arr[$x]["descr"];
		}
		return null;
	}

	public function getBug ($id)
	{
		// id, descr, product, user_nm, bug_type, status, priority, comments, solution, assigned_to, bug_id, entry_dtm, update_dtm, closed_dtm,worklog,attachments
		$results = $this->db->bt_bugs->findOne(array("_id"=>new MongoId($id)));
		if (empty($results)) return array(); // empty record!
		$results["_id"] = (string)$results["_id"];
		$results["status_descr"] = $this->getBTlookup("bt_status",$results["status"]);
		$results["priority_descr"] = $this->getBTlookup("bt_priority",$results["priority"]);
		$results["edtm"] = date("m/d/Y g:i a",$results["entry_dtm"]->sec);
		$results["udtm"] = isset($results["update_dtm"]) ? date("m/d/Y g:i a",$results["update_dtm"]->sec) : "";
		$results["cdtm"] = isset($results["closed_dtm"]) ? date("m/d/Y g:i a",$results["closed_dtm"]->sec) : "";
		$results["ename"] = "";
		if (!empty($results["user_nm"]))
		{
			$urec = $this->getUserRec($results["user_nm"]);
			if (!empty($urec)) $results["ename"] = $urec["lname"].", ".$urec["fname"];
		}
		$results["aname"] = "";
		if (!empty($results["assigned_to"]))
		{
			$urec = $this->getUserRec($results["assigned_to"]);
			if (!empty($urec)) $results["aname"] = $urec["lname"].", ".$urec["fname"];
		}
		if (!empty($results["worklog"]))
		{
			for ($x=0; $x<count($results["worklog"]); ++$x)
			{
				$results["worklog"][$x]["edtm"] = date("m/d/Y g:i a",$results["worklog"][$x]["entry_dtm"]->sec);
			}
		}
		if (!empty($results["attachments"]))
		{
			for ($x=0; $x<count($results["attachments"]); ++$x)
			{
				$results["attachments"][$x]["edtm"] = date("m/d/Y g:i a",$results["attachments"][$x]["entry_dtm"]->sec);
			}
		}
		return $results;
	}

	public function getBugs ($type = "", $crit = array())
	{
		$results = array();
		$aCrit = array(); $aTemp = array();
		if (!empty($crit))
		{
			if (count($crit) > 1)
				$aCrit = array('$and'=>$crit);
		}
		//var_dump($crit);
		$coll = $this->db->bt_bugs->find($crit);
		while ($coll->hasNext())
		{
			// reorg data for DataTables
			$row = (array)$coll->getNext();
			//var_dump($row);
			$row["_id"] = (string)$row["_id"];
			$row["entry_dtm"] = date("m/d/Y g:i a",$row["entry_dtm"]->sec);
			$row["status"] = $this->getBTlookup("bt_status",$row["status"]);
			$results[] = $row;
		}
		return array("data"=>$results);
	}

	private function getNextSequence ($name) {
		$ret = $this->db->counters->findAndModify (
			array( "_id" => $name ),
			array( '$inc' => array( 'seq' => 1 ) ),
			null,
			array(
				"new" => true,
				"upsert" => true
			)
		);

		return $ret["seq"];
	}

	// rec = record array
	public function addBug ($rec)
	{
		extract($rec);
		//error_log("rec=".print_r($rec,1));
		$bid = $this->getNextSequence("bug_id");
		//$arr = explode("|",$group);
		//$group = $arr[0];
		$bug_id="$bt_group$bid";
		$iid = new MongoId(); // generate a _id
		$arrTemp = array(
  "_id" => $iid
, "bug_id" => $bug_id
, "descr" => $descr
, "product" => $product
, "user_nm" => $_SESSION["user_id"]
, "bug_type" => substr($bug_type,0,1)
, "status" => $status
, "priority" => $priority
, "comments" => $comments
, "solution" => $solution
//, "assigned_to" => $assigned_to
, "entry_dtm" => new MongoDate()
);
		$res = $this->db->bt_bugs->insert($arrTemp);
		//var_dump($res);
		//$count = $res["n"];
		if (!$res) die("ERROR: Record not added! $sql");
		return "SUCCESS ".(string)$iid.",".$bug_id;
	}

	// rec = record array
	public function updateBug ($rec)
	{
		extract($rec);
		$bid = preg_replace("/.*(\\d+)$/",'$1',$bug_id);
		$bug_id = "$bt_group$bid";
		$arrTemp = array(
  "bug_id" => $bug_id
, "descr" => $descr
, "product" => $product
, "bug_type" => $bug_type
, "status" => $status
, "priority" => $priority
, "comments" => $comments
, "solution" => $solution
//, "assigned_to" => $assigned_to
, "update_dtm" => new MongoDate()
);
		if (isset($closed))
			$arrTemp["closed_dtm"] = new MongoDate();
		$res = $this->db->bt_bugs->update(array("_id"=>new MongoId($id)),array('$set'=>$arrTemp));
		//$count = $res["n"];
		if (!$res) die("ERROR: Record not updated!");
		return "SUCCESS";
	}

	public function deleteBug ($id)
	{
		$id = new MongoId($id);
		//$this->db->bt_worklog->remove(array("_id" => $id));
		//$this->db->bt_attachments->remove(array("_id" => $id));
		$this->db->bt_bugs->remove(array("_id" => $id));
		return "SUCCESS";
	}

	// rec = record array
	public function addWorkLog ($rec)
	{
		$arrBug = $this->getBug($rec["id"]);
		//var_dump($arrBug);
		$arrWorklogs = !empty($arrBug["worklog"]) ? $arrBug["worklog"] : array();
		// id, bug_id, user_nm, comments, entry_dtm
		$arrTemp = array(
  "user_nm" => $_SESSION["user_id"]
, "comments" => $rec["wl_comments"]
, "wl_public" => $rec["wl_public"]
, "entry_dtm" => new MongoDate()
);
		$arrWorklogs[] = $arrTemp;
		$res = $this->db->bt_bugs->update(
			array("_id" => new MongoId($arrBug["_id"]))
			,array(
				'$set' => array(
					"worklog" => $arrWorklogs
				)
			)
		);
		if (!$res) die("ERROR: Record not added! $sql");
		return "SUCCESS";
	}

	// idx = record index
	// rec = record array
	public function updateWorkLog ($idx, $rec)
	{
		$arrBug = $this->getBug($id);
		$arrWorklogs = $arrBug["worklog"];
		$arrTemp = array(
  "user_nm" => $_SESSION["user_id"]
, "comments" => $rec["wl_comments"]
, "wl_public" => $rec["wl_public"]
);
		$arrWorklogs[$idx] = $arrTemp;
		$res = $this->db->bt_bugs->update(
			array("_id" => new MongoId($arrBug["_id"]))
			,array(
				'$set' => array(
					"worklog" => $arrWorklogs
				)
			)
		);
		if ($count == 0) die("ERROR: Record not updated! $sql");
		return "SUCCESS";
	}

	public function do_bug_email ( $args )
	{
		global $sarr, $parr;
		$rec = (object)$this->getBug($args["id"]);
		if (empty($rec)) die("ERROR: Bug not found ({$args["id"]})");
		$bt = $this->getBugTypeDescr($rec->bug_type);
		if ($rec->user_nm != "") {
			$arr = $this->getUserRec($rec->user_nm);
			$ename = "{$arr["lname"]}, {$arr["fname"]}";
			$email = $arr["email"];
		} else $ename="";
		if (!empty($rec->assigned_to)) {
			$arr = $this->getUserRec($rec->assigned_to);
			$aname = "{$arr["lname"]}, {$arr["fname"]}";
			$aemail = $arr["email"];
		} else $aname="";
		$status = $this->getBTlookup("bt_status",$rec->status);
		$priority = $this->getBTlookup("bt_priority",$rec->priority);
		$msg = "{$args["msg2"]}

Details of Bug ID {$rec->bug_id}.

Description: {$rec->descr}
Product or Application: {$rec->product}
Bug Type: $bt
Status: {$status}
Priority: {$priority}
Comments: {$rec->comments}
Solution: {$rec->solution}
Assigned To: $aname
Entry By: $ename
Entry Date/Time: {$rec->edtm}
Update Date/Time: {$rec->udtm}
Closed Date/Time: {$rec->cdtm}

";
		$rows = !empty($rec->worklog) ? $rec->worklog : array();
		$msg .= count($rows)." Worklog entries found

";
		if (count($rows) > 0) {
			foreach ($rows as $row) {
				//list($wid,$bid,$usernm,$comments,$entry_dtm,$edtm)=$row;
				$o = (object)$row;
				if ($o->user_nm != "") {
					$arr = $this->getUserRec($o->user_nm);
					$ename = "{$arr["lname"]}, {$arr["fname"]}";
				} else $ename="";
				$comments = stripcslashes($o->comments);
				$msg .= "Date/Time: {$o->edtm}, By: $ename
Comments: $comments
";
			}
		}
		$sendto = $args["sendto"];
		$cc = $args["cc"];
		$subject = $args["subject"];
		if (!preg_match("/@/",$sendto)) $sendto.="@wilddogdesign.com";
		if ($cc != "" and !preg_match("/@/",$cc)) $cc.="@wilddogdesign.com";
		if ($cc != "") $ccx="CC: $cc"; else $ccx="";
		if (0) {
		$msg = nl2br($msg);
		return <<<END
To: $sendto<br>
Subject: $subject<br>
Headers: $ccx<br>
Content:<br>
$msg
END;
		}
		mail($sendto,$subject,stripcslashes($msg),$ccx);
		return "Message sent";
	}

	public function getBugTypeDescr ($bug_type)
	{
		$descr = $this->db->bt_type->findOne(array("cd"=>$bug_type),array("descr"=>1));
		return $descr["descr"];
	}

	public function getBugAttachment ($id, $idx)
	{
		$arrBug = $this->getBug($id);
		$arrAttachments = !empty($arrBug["attachments"]) ? $arrBug["attachments"] : array();
		return $arrAttachments[$idx];
	}

	public function getBugAttachments ($id) {
		$arrBug = $this->getBug($id);
		//var_dump($arrBug);
		$arrAttachments = !empty($arrBug["attachments"]) ? $arrBug["attachments"] : array();
		return $arrAttachments;
	}

	// rec = record array
	public function addAttachment ($id, $filename, $size, $raw_file)
	{
		$arrBug = $this->getBug($id);
		$arrAttachments = !empty($arrBug["attachments"]) ? $arrBug["attachments"] : array();
		// id, bug_id, file_name, file_size, file_hash, entry_dtm
		//extract($rec);
		//$hash = md5($id.$filename.date("YmdHis"));
		$hash = md5($raw_file);
		$arrTemp = array(
  "file_name" => $filename
, "file_size" => $size
, "file_hash" => $hash
, "entry_dtm" => new MongoDate()
);
		$arrAttachments[] = $arrTemp;
		$res = $this->db->bt_bugs->update(
			array("_id" => new MongoId($id))
			,array(
				'$set' => array(
					"attachments" => $arrAttachments
				)
			)
		);
		$pdir = substr($hash,0,2);
		if (!file_exists($this->adir.$pdir))
		{
			mkdir($this->adir.$pdir);
		}
		$fp = fopen($this->adir.$pdir."/".$hash,"wb");
		fwrite($fp,$raw_file);
		fclose($fp);

		return 1;
	}

	public function deleteAttachment ($id, $idx)
	{
		$arrBug = $this->getBug($id);
		$arrAttachments = $arrBug["attachments"];
		// remove the attachment item
		$arrAttachments2 = array_splice($arrAttachments,$idx,1);
		$res = $this->db->bt_bugs->update(
			array("_id" => $arrBug["_id"])
			,array(
				'$set' => array(
					"attachments" => $arrAttachments2
				)
			)
		);
		if ($res)
		{
			$hash = $arrBug["file_hash"];
			$pdir = substr($hash,0,2);
			unlink($this->adir.$pdir."/".$hash);
		}
	}

	public function getUserEntries ( $args = null )
	{
		$results = array();
		$aCrit = array(); $aTemp = array();
		if (!empty($args))
		{
			if (!empty($args["lname"]))
				$aTemp[] = array("lname"=>array('$regex'=>new MongoRegex("/^".$args["lname"]."/i")));
			if (!empty($args["fname"]))
				$aTemp[] = array("fname"=>array('$regex'=>new MongoRegex("/^".$args["fname"]."/i")));
			if (count($aTemp) == 2)
				$aCrit = array('$and'=>$aTemp);
			else
				if (!empty($aTemp)) $aCrit = $aTemp[0];
		}
		//var_dump($aCrit);
		$coll = $this->db->bt_users->find($aCrit)->sort(array("lname"=>1,"fname"=>1));
		while ($coll->hasNext())
		{
			$rec = $coll->getNext();
			$rec["name"] = $rec["lname"].", ".$rec["fname"];
			$results[] = $rec;
		}
		return array("data"=>$results);
	}

	public function getUserRec ($uid)
	{
		$results = $this->db->bt_users->findOne(array("uid"=>$uid));
		$results["id"] = (string)$results["_id"];
		return $results;
	}

	// rec = record array
	public function addUser ($rec)
	{
		// uid, lname, fname, email, active, roles, pw, bt_group
		extract($rec);
		$pw5 = md5($pw);
		//$roles = join(" ",$roles);
		$arrTemp = array(
  "uid" => $uid2
, "lname" => $lname
, "fname" => $fname
, "email" => $email
, "active" => $active
, "roles" => array($roles)
, "pw" => $pw5
, "bt_group" => $bt_group
);
		$res = $this->db->bt_users->insert($arrTemp);
		//var_dump($res);
		//$count = $res["n"];
		if (!$res) die("ERROR: Record not added!");
		return 1;
	}

	// uid = record key
	// rec = record array
	public function updateUser ($rec)
	{
		extract($rec);
		if ($pw == $pw2) $pw5 = $pw;
		else $pw5 = md5($pw);
		//$roles = join(" ",$roles);
		$arrTemp = array(
  "lname" => $lname
, "fname" => $fname
, "email" => $email
, "active" => $active
, "roles" => array($roles)
, "pw" => $pw5
, "bt_group" => $bt_group
);
		$res = $this->db->bt_users->update(array("_id"=>new MongoId($id)),array('$set'=>$arrTemp));
		//var_dump($res);
		//$count = $res["n"];
		if (!$res) die("ERROR: Record not updated!");
	}

	public function assign_user ($args)
	{
		$arrTemp = array(
  "assigned_to" => $args["uid"]
, "update_dtm" => new MongoDate(time())
);
		$this->db->bt_bugs->update(array("_id" => new MongoId($args["id"])),array('$set' => $arrTemp));
		return "Assignment done";
	}

	public function get_admin_emails ()
	{
		$results = array();
		try
		{
			$coll = $this->db->bt_users->find(array("roles"=>"admin"),array("email"=>1));
			while ($coll->hasNext())
			{
				$results[] = $coll->getNext()["email"];
			}
		}
		catch (Exception $e)
		{
			die("SQL ERROR: $sql, ".$e->getMessage());
		}
		return join(",",$results);
	}

	public function check_session ()
	{
		return (isset($_SESSION["user_id"]) and $_SESSION["user_id"] != "") ? 1 : 0;
	}

	public function login_session ( $uid, $pw )
	{
		$results = array();
		$row = $this->db->bt_users->findOne(array('uid'=>$uid,'pw'=>md5($pw)));
		if (empty($row)) die("FAIL");
		$_SESSION["user_id"] = $uid;
		$_SESSION["user_nm"] = $row["fname"]." ".$row["lname"];
		$_SESSION["email"] = $row["email"];
		//$_SESSION["roles"] = $row["roles"];
		$_SESSION["roles"] = join(",",$row["roles"]);
		$_SESSION["group"] = $row["bt_group"];
		return $row;
	}

	public function getHandle ()
	{
		return $this->db;
	}

	public function getAdir ()
	{
		return $this->adir;
	}

} // end class BugTrack
?>
