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
		//$this->mdb->close();
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

	public function getBug ($id)
	{
		// id, descr, product, user_nm, bug_type, status, priority, comments, solution, assigned_to, bug_id, entry_dtm, update_dtm, closed_dtm
		$results = $this->db->bt_bugs->findOne(array("_id"=>new MongoId($id)));
		if (empty($results)) return array(); // empty record!
		//$results["entry_dtm"] = date("m/d/Y g:m a",$results["entry_dtm"]->sec);
		return $results;
	}

	public function getBugs ($crit = array(), $order = array())
	{
		if (empty($crit)) $crit = array();
		$results = array();
		$coll = $this->db->bt_bugs->find($crit)->sort($order);
		while ($coll->hasNext())
		{
			$results[] = $coll->getNext();
		}
		return $results;
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
, "assigned_to" => $assigned_to
, "entry_dtm" => new MongoDate()
, "update_dtm" => null
, "closed_dtm" => null
);
		$res = $this->db->bt_bugs->insert($arrTemp);
		//var_dump($res);
		//$count = $res["n"];
		if (!$res) die("ERROR: Record not added! $sql");
		return $iid.",".$bug_id;
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
, "assigned_to" => $assigned_to
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
		$this->db->bt_worklog.remove(array("_id" => $id));
		$this->db->bt_attachments.remove(array("_id" => $id));
		$this->db->bt_bugs.remove(array("_id" => $id));
	}

	// rec = record array
	public function addWorkLog ($rec)
	{
		// id, bug_id, user_nm, comments, entry_dtm
		extract($rec);
		$arrTemp = array(
  "bug_id" => $bug_id
, "user_nm" => $usernm
, "comments" => $comments
, "wl_public" => $wl_public
, "entry_dtm" => new MongoDate()
);
		$res = $this->db->bt_worklog->insert($arrTemp);
		if (!$res) die("ERROR: Record not added! $sql");
		return 1;
	}

	// idx = record index
	// rec = record array
	public function updateWorkLog ($idx, $rec)
	{
		extract($rec);
		$arrTemp = array(
  "user_nm" => $usernm
, "comments" => $comments
, "wl_public" => $wl_public
);
		$res = $this->db->bt_bugs->update(array("_id"=>new MongoId($idx)),array('$set'=>$arrTemp));
		$count = $res["n"];
		if ($count == 0) die("ERROR: Record not updated! $sql");
	}

	public function getBugTypeDescr ($bug_type)
	{
		$descr = $this->db->bt_type->findOne(array("cd"=>$bug_type),array("descr"=>1));
		return $descr["descr"];
	}

	public function getWorkLogEntries ($id)
	{
		$results = array();
		$coll = $this->db->bt_worklog->find(array("bug_id"=>$id));
		while ($coll->hasNext())
		{
			$results[] = $coll->getNext();
		}
		return $results;
	}

	public function getBugAttachment ($id)
	{
		$result = $this->db->bt_attachments->findOne(array("_id"=>new MongoId($id)));
		return $result;
	}

	public function getBugAttachments ($id) {
		$results = array();
		$coll = $this->db->bt_attachments->find(array("bug_id"=>$id));
		while ($coll->hasNext())
		{
			$results[] = $coll->getNext();
		}
		return $results;
	}

	// rec = record array
	public function addAttachment ($id, $filename, $size, $raw_file)
	{
		// id, bug_id, file_name, file_size, file_hash, entry_dtm
		//extract($rec);
		//$hash = md5($id.$filename.date("YmdHis"));
		$hash = md5($raw_file);
		$arrTemp = array(
  "bug_id" => $bug_id
, "file_name" => $file_name
, "file_size" => $file_size
, "file_hash" => $file_hash
, "entry_dtm" => new MongoDate()
);
		$res = $this->db->bt_attachments->insert($arrTemp);
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

	public function deleteAttachment ($id)
	{
		$result = $this->db->bt_attachments->findOne(array("_id"=>new MongoId($id)),array("file_hash"=>1));
		if (!empty($result))
		{
			$hash = $result["file_hash"];
			$res = $this->db->bt_bugs.remove(array("_id" => $id));
			if ($res)
			{
				$pdir = substr($hash,0,2);
				unlink($this->adir.$pdir."/".$hash);
			}
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