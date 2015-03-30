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
	
	public function __construct ( $dbpath )
	{
		// MongoDB database version
		try
		{
			//$this->mdb = new MongoClient($dbpath);
			$this->mdb = new Mongo($dbpath);
			$this->db = $this->mdb->bugtrack;
			//var_dump($this->db);
		}
		catch (Exception $e)
		{
			die("SQL CONNECTION ERROR: ".$e->getMessage());
			//header("Location: /dberror.html");
			exit;
		}
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

	public function getBTlookups ()
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
		return json_encode($results);
	}

	public function getBug ($id)
	{
		global $sarr, $parr;
		// id, descr, product, user_nm, bug_type, status, priority, comments, solution, assigned_to, bug_id, entry_dtm, update_dtm, closed_dtm,worklog,attachments
		$results = $this->db->bt_bugs->findOne(array("_id"=>new MongoId($id)));
		if (empty($results)) return array(); // empty record!
		$results["_id"] = (string)$results["_id"];
		$results["status_descr"] = $sarr[$results["status"]];
		$results["priority_descr"] = $parr[$results["priority"]];
		$results["edtm"] = date("m/d/Y g:m a",$results["entry_dtm"]->sec);
		$results["udtm"] = isset($results["update_dtm"]) ? date("m/d/Y g:m a",$results["update_dtm"]->sec) : "";
		$results["cdtm"] = isset($results["closed_dtm"]) ? date("m/d/Y g:m a",$results["closed_dtm"]->sec) : "";
		if (!empty($results["worklog"]))
		{
			for ($x=0; $x<count($results["worklog"]); ++$x)
			{
				$results["worklog"][$x]["edtm"] = date("m/d/Y g:m a",$results["worklog"][$x]["entry_dtm"]->sec);
			}
		}
		if (!empty($results["attachments"]))
		{
			for ($x=0; $x<count($results["attachments"]); ++$x)
			{
				$results["attachments"][$x]["edtm"] = date("m/d/Y g:m a",$results["attachments"][$x]["entry_dtm"]->sec);
			}
		}
		return $results;
	}

	public function getBugs ($crit = array(), $order = array())
	{
		global $sarr;
		$olist = array("bug_id","descr","entry_dtm","status","_id");
		if (empty($crit)) $crit = array();
		$results = array();
		//var_dump($crit);
		$coll = $this->db->bt_bugs->find($crit,array("bug_id"=>1,"descr"=>1,"entry_dtm"=>1,"status"=>1,"_id"=>1))->sort($order);
		while ($coll->hasNext())
		{
			// reorg data for DataTables
			$row = (array)$coll->getNext();
			//var_dump($row);
			$arr = [];
			foreach ($olist as $i)
			{
				$v = $i != "" ? $row[$i] : "";
				if ($i == "_id") $v = (string)$v;
				if ($i == "entry_dtm") $v = date("m/d/Y g:i a",$v->sec);
				if ($i == "status") $v = $sarr[$v];
				$arr[] = $v;
			}
			$results[] = $arr;
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
		$arr = explode("|",$group);
		$group = $arr[0];
		$bug_id="$group$bid";
		$iid = new MongoId(); // generate a _id
		$arrTemp = array(
  "_id" => $iid
, "bug_id" => $bug_id
, "descr" => $descr
, "product" => $product
, "user_nm" => $user_nm
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
		return (string)$iid.",".$bug_id;
	}

	// idx = record index
	// rec = record array
	// closed = boolean
	public function updateBug ($idx, $rec, $closed)
	{
		extract($rec);
		$arrTemp = array(
  "descr" => $descr
, "product" => $product
, "bug_type" => $bug_type
, "status" => $status
, "priority" => $priority
, "comments" => $comments
, "solution" => $solution
//, "assigned_to" => $assigned_to
, "update_dtm" => new MongoDate()
);
		if ($closed)
			$arrTemp["closed_dtm"] = new MongoDate();
		$res = $this->db->bt_bugs->update(array("_id"=>new MongoId($idx)),array('$set'=>$arrTemp));
		//$count = $res["n"];
		if (!$res) die("ERROR: Record not updated!");
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
		$arrBug = $this->getBug($rec["bug_id"]);
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
		$rec = (object)$this->getBug($args["bug_id"]);
		if (empty($rec)) die("ERROR: Bug not found ({$args["id"]})");
		$bt = $this->getBugTypeDescr($rec->bug_type);
		if ($rec->user_nm != "") {
			$arr = $this->get_user($rec->user_nm);
			$ename = "$arr[2] $arr[1]";
			$email = $arr[3];
		} else $ename="";
		if ($rec->assigned_to != "") {
			$arr = $this->get_user($rec->assigned_to);
			$aname = "$arr[2] $arr[1]";
			$aemail = $arr[3];
		} else $aname="";
		$msg = "$msg2

Details of Bug ID {$rec->bug_id}.

Description: {$rec->descr}
Product or Application: {$rec->product}
Bug Type: $bt
Status: {$sarr[$rec->status]}
Priority: {$parr[$rec->priority]}
Comments: {$rec->comments}
Solution: {$rec->solution}
Entry By: $ename
Assigned To: $aname
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
					$arr = $this->get_user($o->user_nm);
					$ename = "$arr[2] $arr[1]";
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
		if (1) {
		$msg = nl2br($msg);
		return <<<END
To: $sendto<br>
Subject: $subject<br>
Headers: $ccx<br>
Content:<br>
$msg
END;
		}
		//mail($sendto,$subject,stripcslashes($msg),$ccx);
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
			array("_id" => $arrBug["_id"])
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

	public function getUserEntries ()
	{
		$found = $this->db->bt_users->count();
		if ($found == 0) return array(); // empty record!
		$coll = $this->db->bt_users->find()->sort(array("lname"=>1,"fname"=>1));
		while ($coll->hasNext())
		{
			$results[] = $coll->getNext();
		}
		return $results;
	}

	public function getUserRec ($uid)
	{
		$results = $this->db->bt_users->findOne(array("uid"=>$uid));
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
, "roles" => $roles
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
	public function updateUser ($oid, $rec)
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
, "roles" => $roles
, "pw" => $pw5
, "bt_group" => $bt_group
);
		$res = $this->db->bt_users->update(array("_id"=>new MongoId($oid)),array('$set'=>$arrTemp));
		//var_dump($res);
		//$count = $res["n"];
		if (!$res) die("ERROR: Record not updated!");
	}
	
	public function assign_user ($id, $uid)
	{
		$arrTemp = array(
  "assigned_to" => $uid
, "update_dtm" => new MongoDate()
);
		$this->db->bt_bugs->update(array("_id"=>new MongoId($id)),array('$set'=>$arrTemp));
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
		$_SESSION["roles"] = join(",",$row["roles"]);
		$_SESSION["group"] = $row["bt_group"];
		echo json_encode($row);
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
