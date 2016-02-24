<?php
/**
 * Implementation of the user object in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent a role in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2016 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_Role { /* {{{ */
	/**
	 * @var integer id of role
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var string name of role
	 *
	 * @access protected
	 */
	var $_login;

	/**
	 * @var string role of user. Can be one of SeedDMS_Core_User::role_user,
	 *      SeedDMS_Core_User::role_admin, SeedDMS_Core_User::role_guest
	 *
	 * @access protected
	 */
	var $_role;

	/**
	 * @var object reference to the dms instance this user belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	const role_user = '0';
	const role_admin = '1';
	const role_guest = '2';

	function SeedDMS_Core_Role($id, $name, $role) { /* {{{ */
		$this->_id = $id;
		$this->_name = $name;
		$this->_role = $role;
		$this->_dms = $role;
	} /* }}} */

	/**
	 * Create an instance of a role object
	 *
	 * @param string|integer $id Id, login name, or email of user, depending
	 * on the 3rd parameter.
	 * @param object $dms instance of dms
	 * @param string $by search by [name|email]. If 'name' is passed, the method
	 * will check for the 4th paramater and also filter by email. If this
	 * parameter is left empty, the user will be search by its Id.
	 * @param string $email optional email address if searching for name
	 * @return object instance of class SeedDMS_Core_User
	 */
	public static function getInstance($id, $dms, $by='') { /* {{{ */
		$db = $dms->getDB();

		switch($by) {
		case 'name':
			$queryStr = "SELECT * FROM `tblRoles` WHERE `name` = ".$db->qstr($id);
			break;
		default:
			$queryStr = "SELECT * FROM `tblRoles` WHERE id = " . (int) $id;
		}

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$role = new self($resArr["id"], $resArr["name"], $resArr["role"]);
		$role->setDMS($dms);
		return $role;
	} /* }}} */

	public static function getAllInstances($orderby, $dms) { /* {{{ */
		$db = $dms->getDB();

		if($orderby == 'ame')
			$queryStr = "SELECT * FROM tblRoles ORDER BY name";
		else
			$queryStr = "SELECT * FROM tblRoles ORDER BY id";
		$resArr = $db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		$roles = array();

		for ($i = 0; $i < count($resArr); $i++) {
			$role = new self($resArr[$i]["id"], $resArr[$i]["name"], $resArr[$i]["role"]);
			$role->setDMS($dms);
			$roles[$i] = $role;
		}

		return $roles;
} /* }}} */

	function setDMS($dms) {
		$this->_dms = $dms;
	}

	function getID() { return $this->_id; }

	function getName() { return $this->_name; }

	function setName($newName) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblRoles SET name =".$db->qstr($newName)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_name = $newName;
		return true;
	} /* }}} */

	function isAdmin() { return ($this->_role == SeedDMS_Core_Role::role_admin); }

	function isGuest() { return ($this->_role == SeedDMS_Core_Role::role_guest); }

	function getRole() { return $this->_role; }

	function setRole($newrole) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblRoles SET role = " . $newrole . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_role = $newrole;
		return true;
	} /* }}} */

	/**
	 * Delete role
	 *
	 * @return boolean true on success or false in case of an error
	 */
	function remove($user) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM tblRoles WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			return false;
		}

		return true;
	} /* }}} */

	function isUsed() { /* {{{ */
		$db = $this->_dms->getDB();
		
		$queryStr = "SELECT * FROM tblUsers WHERE role=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_array($resArr) && count($resArr) == 0)
			return false;
		return true;
	} /* }}} */

	function getUsers() { /* {{{ */
		$db = $this->_dms->getDB();
		
		if (!isset($this->_users)) {
			$queryStr = "SELECT * FROM tblUsers WHERE role=".$this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_users = array();

			$classnamerole = $this->_dms->getClassname('role');

			$classname = $this->_dms->getClassname('user');
			foreach ($resArr as $row) {
				$role = $classnamerole::getInstance($row['role'], $this->_dms);
				$user = new $classname($row["id"], $row["login"], $row["pwd"], $row["fullName"], $row["email"], $row["language"], $row["theme"], $row["comment"], $role, $row['hidden']);
				$user->setDMS($this->_dms);
				array_push($this->_users, $user);
			}
		}
		return $this->_users;
	} /* }}} */

} /* }}} */

/**
 * Class to represent a user in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_User { /* {{{ */
	/**
	 * @var integer id of user
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var string login name of user
	 *
	 * @access protected
	 */
	var $_login;

	/**
	 * @var string password of user as saved in database (md5)
	 *
	 * @access protected
	 */
	var $_pwd;

	/**
	 * @var string date when password expires
	 *
	 * @access protected
	 */
	var $_pwdExpiration;

	/**
	 * @var string full human readable name of user
	 *
	 * @access protected
	 */
	var $_fullName;

	/**
	 * @var string email address of user
	 *
	 * @access protected
	 */
	var $_email;

	/**
	 * @var string prefered language of user
	 *      possible values are subdirectories within the language directory
	 *
	 * @access protected
	 */
	var $_language;

	/**
	 * @var string preselected theme of user
	 *
	 * @access protected
	 */
	var $_theme;

	/**
	 * @var string comment of user
	 *
	 * @access protected
	 */
	var $_comment;

	/**
	 * @var string role of user. Can be one of SeedDMS_Core_User::role_user,
	 *      SeedDMS_Core_User::role_admin, SeedDMS_Core_User::role_guest
	 *
	 * @access protected
	 */
	var $_role;

	/**
	 * @var boolean true if user shall be hidden
	 *
	 * @access protected
	 */
	var $_isHidden;

	/**
	 * @var boolean true if user is disabled
	 *
	 * @access protected
	 */
	var $_isDisabled;

	/**
	 * @var int number of login failures
	 *
	 * @access protected
	 */
	var $_loginFailures;

	/**
	 * @var object home folder
	 *
	 * @access protected
	 */
	var $_homeFolder;

	/**
	 * @var array list of users this user can substitute
	 *
	 * @access protected
	 */
	var $_substitutes;

	/**
	 * @var array reverse list of users this user can substitute
	 *
	 * @access protected
	 */
	var $_rev_substitutes;

	/**
	 * @var object reference to the dms instance this user belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	const role_user = '0';
	const role_admin = '1';
	const role_guest = '2';

	function SeedDMS_Core_User($id, $login, $pwd, $fullName, $email, $language, $theme, $comment, $role, $isHidden=0, $isDisabled=0, $pwdExpiration='0000-00-00 00:00:00', $loginFailures=0, $quota=0, $homeFolder=null) {
		$this->_id = $id;
		$this->_login = $login;
		$this->_pwd = $pwd;
		$this->_fullName = $fullName;
		$this->_email = $email;
		$this->_language = $language;
		$this->_theme = $theme;
		$this->_comment = $comment;
		$this->_role = $role;
		$this->_isHidden = $isHidden;
		$this->_isDisabled = $isDisabled;
		$this->_pwdExpiration = $pwdExpiration;
		$this->_loginFailures = $loginFailures;
		$this->_quota = $quota;
		$this->_homeFolder = $homeFolder;
		$this->_substitutes = null;
		$this->_rev_substitutes = null;
		$this->_dms = null;
	}

	/**
	 * Create an instance of a user object
	 *
	 * @param string|integer $id Id, login name, or email of user, depending
	 * on the 3rd parameter.
	 * @param object $dms instance of dms
	 * @param string $by search by [name|email]. If 'name' is passed, the method
	 * will check for the 4th paramater and also filter by email. If this
	 * parameter is left empty, the user will be search by its Id.
	 * @param string $email optional email address if searching for name
	 * @return object instance of class SeedDMS_Core_User
	 */
	public static function getInstance($id, $dms, $by='', $email='') { /* {{{ */
		$db = $dms->getDB();

		switch($by) {
		case 'name':
			$queryStr = "SELECT * FROM tblUsers WHERE login = ".$db->qstr($id);
			if($email)
				$queryStr .= " AND email=".$db->qstr($email);
			break;
		case 'email':
			$queryStr = "SELECT * FROM tblUsers WHERE email = ".$db->qstr($id);
			break;
		default:
			$queryStr = "SELECT * FROM tblUsers WHERE id = " . (int) $id;
		}
		$resArr = $db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$classname = $dms->getClassname('role');
		$role = $classname::getInstance($resArr['role'], $dms);

		$user = new self($resArr["id"], $resArr["login"], $resArr["pwd"], $resArr["fullName"], $resArr["email"], $resArr["language"], $resArr["theme"], $resArr["comment"], $role, $resArr["hidden"], $resArr["disabled"], $resArr["pwdExpiration"], $resArr["loginfailures"], $resArr["quota"], $resArr["homefolder"]);
		$user->setDMS($dms);
		return $user;
	} /* }}} */

	public static function getAllInstances($orderby, $dms) { /* {{{ */
		$db = $dms->getDB();

		if($orderby == 'fullname')
			$queryStr = "SELECT * FROM tblUsers ORDER BY fullname";
		else
			$queryStr = "SELECT * FROM tblUsers ORDER BY login";
		$resArr = $db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		$users = array();

		for ($i = 0; $i < count($resArr); $i++) {
			$user = new self($resArr[$i]["id"], $resArr[$i]["login"], $resArr[$i]["pwd"], $resArr[$i]["fullName"], $resArr[$i]["email"], (isset($resArr[$i]["language"])?$resArr[$i]["language"]:NULL), (isset($resArr[$i]["theme"])?$resArr[$i]["theme"]:NULL), $resArr[$i]["comment"], $resArr[$i]["role"], $resArr[$i]["hidden"], $resArr[$i]["disabled"], $resArr[$i]["pwdExpiration"], $resArr[$i]["loginfailures"], $resArr[$i]["quota"], $resArr[$i]["homefolder"]);
			$user->setDMS($dms);
			$users[$i] = $user;
		}

		return $users;
} /* }}} */

	function setDMS($dms) {
		$this->_dms = $dms;
	}

	function getID() { return $this->_id; }

	function getLogin() { return $this->_login; }

	function setLogin($newLogin) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET login =".$db->qstr($newLogin)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_login = $newLogin;
		return true;
	} /* }}} */

	function getFullName() { return $this->_fullName; }

	function setFullName($newFullName) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET fullname = ".$db->qstr($newFullName)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_fullName = $newFullName;
		return true;
	} /* }}} */

	function getPwd() { return $this->_pwd; }

	function setPwd($newPwd) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET pwd =".$db->qstr($newPwd)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_pwd = $newPwd;
		return true;
	} /* }}} */

	function getPwdExpiration() { return $this->_pwdExpiration; }

	function setPwdExpiration($newPwdExpiration) { /* {{{ */
		$db = $this->_dms->getDB();

		if(trim($newPwdExpiration) == '')
			$newPwdExpiration = '0000-00-00 00:00:00';
		$queryStr = "UPDATE tblUsers SET pwdExpiration =".$db->qstr($newPwdExpiration)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_pwdExpiration = $newPwdExpiration;
		return true;
	} /* }}} */

	function getEmail() { return $this->_email; }

	function setEmail($newEmail) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET email =".$db->qstr($newEmail)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_email = $newEmail;
		return true;
	} /* }}} */

	function getLanguage() { return $this->_language; }

	function setLanguage($newLanguage) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET language =".$db->qstr($newLanguage)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_language = $newLanguage;
		return true;
	} /* }}} */

	function getTheme() { return $this->_theme; }

	function setTheme($newTheme) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET theme =".$db->qstr($newTheme)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_theme = $newTheme;
		return true;
	} /* }}} */

	function getComment() { return $this->_comment; }

	function setComment($newComment) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET comment =".$db->qstr($newComment)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_comment = $newComment;
		return true;
	} /* }}} */

	function getRole() { return $this->_role; }

	function setRole($newrole) { /* {{{ */
		$db = $this->_dms->getDB();

		if(is_object($newrole))
			$queryStr = "UPDATE tblUsers SET role = " . $newrole->getID() . " WHERE id = " . $this->_id;
		else
			$queryStr = "UPDATE tblUsers SET role = " . $newrole . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_role = $newrole;
		return true;
	} /* }}} */

	function isAdmin() { return (is_object($this->_role) ? $this->_role->isAdmin() : $this->_role == SeedDMS_Core_User::role_admin); }

	/**
	 * Was never used and is now deprecated
	 */
	function _setAdmin($isAdmin) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET role = " . SeedDMS_Core_User::role_admin . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_role = SeedDMS_Core_User::role_admin;
		return true;
	} /* }}} */

	function isGuest() { return (is_object($this->_role) ? $this->_role->isGuest() : $this->_role == SeedDMS_Core_User::role_guest); }

	/**
	 * Was never used and is now deprecated
	 */
	function _setGuest($isGuest) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET role = " . SeedDMS_Core_User::role_guest . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_role = SeedDMS_Core_User::role_guest;
		return true;
	} /* }}} */

	function isHidden() { return $this->_isHidden; }

	function setHidden($isHidden) { /* {{{ */
		$db = $this->_dms->getDB();

		$isHidden = ($isHidden) ? "1" : "0";
		$queryStr = "UPDATE tblUsers SET hidden = " . $isHidden . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_isHidden = $isHidden;
		return true;
	}	 /* }}} */

	function isDisabled() { return $this->_isDisabled; }

	function setDisabled($isDisabled) { /* {{{ */
		$db = $this->_dms->getDB();

		$isDisabled = ($isDisabled) ? "1" : "0";
		$queryStr = "UPDATE tblUsers SET disabled = " . $isDisabled . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_isDisabled = $isDisabled;
		return true;
	}	 /* }}} */

	function addLoginFailure() { /* {{{ */
		$db = $this->_dms->getDB();

		$this->_loginFailures++;
		$queryStr = "UPDATE tblUsers SET loginfailures = " . $this->_loginFailures . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		return $this->_loginFailures;
	} /* }}} */

	function clearLoginFailures() { /* {{{ */
		$db = $this->_dms->getDB();

		$this->_loginFailures = 0;
		$queryStr = "UPDATE tblUsers SET loginfailures = " . $this->_loginFailures . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		return true;
	} /* }}} */

	/**
	 * Calculate the disk space for all documents owned by the user
	 * 
	 * This is done by using the internal database field storing the
	 * filesize of a document version.
	 *
	 * @return integer total disk space in Bytes
	 */
	function getUsedDiskSpace() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT SUM(filesize) sum FROM tblDocumentContent a LEFT JOIN tblDocuments b ON a.document=b.id WHERE b.owner = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		return $resArr[0]['sum'];
	} /* }}} */

	function getQuota() { return $this->_quota; }

	function setQuota($quota) { /* {{{ */
		$db = $this->_dms->getDB();

		$quota = intval($quota);
		$queryStr = "UPDATE tblUsers SET quota = " . $quota . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_quota = $quota;
		return true;
	}	 /* }}} */

	function getHomeFolder() { return $this->_homeFolder; }

	function setHomeFolder($homefolder) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblUsers SET homefolder = " . ($homefolder ? (int) $homefolder : NULL) . " WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_homeFolder = $homefolder;
		return true;
	}	 /* }}} */

	/**
	 * Remove the user and also remove all its keywords, notifies, etc.
	 * Do not remove folders and documents of the user, but assign them
	 * to a different user.
	 *
	 * @param object $user the user doing the removal (needed for entry in
	 *        review and approve log).
	 * @param object $assignToUser the user who is new owner of folders and
	 *        documents which previously were owned by the delete user.
	 * @return boolean true on success or false in case of an error
	 */
	function remove($user, $assignToUser=null) { /* {{{ */
		$db = $this->_dms->getDB();

		/* Records like folders and documents that formely have belonged to
		 * the user will assign to another user. If no such user is set,
		 * the function now returns false and will not use the admin user
		 * anymore.
		 */
		if(!$assignToUser)
			return;
		$assignTo = $assignToUser->getID();

		$db->startTransaction();

		// delete private keyword lists
		$queryStr = "SELECT tblKeywords.id FROM tblKeywords, tblKeywordCategories WHERE tblKeywords.category = tblKeywordCategories.id AND tblKeywordCategories.owner = " . $this->_id;
		$resultArr = $db->getResultArray($queryStr);
		if (count($resultArr) > 0) {
			$queryStr = "DELETE FROM tblKeywords WHERE ";
			for ($i = 0; $i < count($resultArr); $i++) {
				$queryStr .= "id = " . $resultArr[$i]["id"];
				if ($i + 1 < count($resultArr))
					$queryStr .= " OR ";
			}
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$queryStr = "DELETE FROM tblKeywordCategories WHERE owner = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		//Benachrichtigungen entfernen
		$queryStr = "DELETE FROM tblNotify WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		/* Assign documents of the removed user to the given user */
		$queryStr = "UPDATE tblFolders SET owner = " . $assignTo . " WHERE owner = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "UPDATE tblDocuments SET owner = " . $assignTo . " WHERE owner = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "UPDATE tblDocumentContent SET createdBy = " . $assignTo . " WHERE createdBy = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Remove private links on documents ...
		$queryStr = "DELETE FROM tblDocumentLinks WHERE userID = " . $this->_id . " AND public = 0";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// ... but keep public links
		$queryStr = "UPDATE tblDocumentLinks SET userID = " . $assignTo . " WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// set administrator for deleted user's attachments
		$queryStr = "UPDATE tblDocumentFiles SET userID = " . $assignTo . " WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// unlock documents locked by the user
		$queryStr = "DELETE FROM tblDocumentLocks WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Delete user from all groups
		$queryStr = "DELETE FROM tblGroupMembers WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// User aus allen ACLs streichen
		$queryStr = "DELETE FROM tblACLs WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Delete image of user
		$queryStr = "DELETE FROM tblUserImages WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Delete entries in password history
		$queryStr = "DELETE FROM tblUserPasswordHistory WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Delete entries in password request
		$queryStr = "DELETE FROM tblUserPasswordRequest WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// mandatory review/approve
		$queryStr = "DELETE FROM tblMandatoryReviewers WHERE reviewerUserID = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM tblMandatoryApprovers WHERE approverUserID = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM tblMandatoryReviewers WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM tblMandatoryApprovers WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM tblWorkflowMandatoryWorkflow WHERE userid = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM tblWorkflowTransitionUsers WHERE userid = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// set administrator for deleted user's events
		$queryStr = "UPDATE tblEvents SET userID = " . $assignTo . " WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Delete user itself
		$queryStr = "DELETE FROM tblUsers WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// TODO : update document status if reviewer/approver has been deleted
		// "DELETE FROM tblDocumentApproveLog WHERE userID = " . $this->_id;
		// "DELETE FROM tblDocumentReviewLog WHERE userID = " . $this->_id;


		$reviewStatus = $this->getReviewStatus();
		foreach ($reviewStatus["indstatus"] as $ri) {
			$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
				"VALUES ('". $ri["reviewID"] ."', '-2', 'Reviewer removed from process', ".$db->getCurrentDatetime().", '". $user->getID() ."')";
			$res=$db->getResult($queryStr);
			if(!$res) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$approvalStatus = $this->getApprovalStatus();
		foreach ($approvalStatus["indstatus"] as $ai) {
			$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
				"VALUES ('". $ai["approveID"] ."', '-2', 'Approver removed from process', ".$db->getCurrentDatetime().", '". $user->getID() ."')";
			$res=$db->getResult($queryStr);
			if(!$res) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$receiptStatus = $this->getReceiptStatus();
		foreach ($receiptStatus["indstatus"] as $ri) {
			$queryStr = "INSERT INTO `tblDocumentReceiptLog` (`receiptID`, `status`, `comment`, `date`, `userID`) ".
				"VALUES ('". $ri["receiptID"] ."', '-2', 'Recipient removed from process', ".$db->getCurrentDatetime().", '". $user->getID() ."')";
			$res=$db->getResult($queryStr);
			if(!$res) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$revisionStatus = $this->getRevisionStatus();
		foreach ($revisionStatus["indstatus"] as $ri) {
			$queryStr = "INSERT INTO `tblDocumentRevisionLog` (`revisionID`, `status`, `comment`, `date`, `userID`) ".
				"VALUES ('". $ri["revisionID"] ."', '-2', 'Revisor removed from process', ".$db->getCurrentDatetime().", '". $user->getID() ."')";
			$res=$db->getResult($queryStr);
			if(!$res) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$db->commitTransaction();
		return true;
	} /* }}} */

	/**
	 * Make the user a member of a group
	 * This function uses {@link SeedDMS_Group::addUser} but checks before if
	 * the user is already a member of the group.
	 *
	 * @param object $group group to be the member of
	 * @return boolean true on success or false in case of an error or the user
	 *        is already a member of the group
	 */
	function joinGroup($group) { /* {{{ */
		if ($group->isMember($this))
			return false;

		if (!$group->addUser($this))
			return false;

		unset($this->_groups);
		return true;
	} /* }}} */

	/**
	 * Removes the user from a group
	 * This function uses {@link SeedDMS_Group::removeUser} but checks before if
	 * the user is a member of the group at all.
	 *
	 * @param object $group group to leave
	 * @return boolean true on success or false in case of an error or the user
	 *        is not a member of the group
	 */
	function leaveGroup($group) { /* {{{ */
		if (!$group->isMember($this))
			return false;

		if (!$group->removeUser($this))
			return false;

		unset($this->_groups);
		return true;
	} /* }}} */

	/**
	 * Get all groups the user is a member of
	 *
	 * @return array list of groups
	 */
	function getGroups() { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_groups))
		{
			$queryStr = "SELECT `tblGroups`.*, `tblGroupMembers`.`userID` FROM `tblGroups` ".
				"LEFT JOIN `tblGroupMembers` ON `tblGroups`.`id` = `tblGroupMembers`.`groupID` ".
				"WHERE `tblGroupMembers`.`userID`='". $this->_id ."'";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_groups = array();
			$classname = $this->_dms->getClassname('group');
			foreach ($resArr as $row) {
				$group = new $classname($row["id"], $row["name"], $row["comment"]);
				$group->setDMS($this->_dms);
				array_push($this->_groups, $group);
			}
		}
		return $this->_groups;
	} /* }}} */

	/**
	 * Checks if user is member of a given group
	 *
	 * @param object $group
	 * @return boolean true if user is member of the given group otherwise false
	 */
	function isMemberOfGroup($group) { /* {{{ */
		return $group->isMember($this);
	} /* }}} */

	/**
	 * Check if user has an image in its profile
	 *
	 * @return boolean true if user has a picture of itself
	 */
	function hasImage() { /* {{{ */
		if (!isset($this->_hasImage)) {
			$db = $this->_dms->getDB();

			$queryStr = "SELECT COUNT(*) AS num FROM tblUserImages WHERE userID = " . $this->_id;
			$resArr = $db->getResultArray($queryStr);
			if ($resArr === false)
				return false;

			if ($resArr[0]["num"] == 0)	$this->_hasImage = false;
			else $this->_hasImage = true;
		}

		return $this->_hasImage;
	} /* }}} */

	/**
	 * Get the image from the users profile
	 *
	 * @return array image data
	 */
	function getImage() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblUserImages WHERE userID = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if ($resArr === false)
			return false;

		if($resArr)
			$resArr = $resArr[0];
		return $resArr;
	} /* }}} */

	function setImage($tmpfile, $mimeType) { /* {{{ */
		$db = $this->_dms->getDB();

		$fp = fopen($tmpfile, "rb");
		if (!$fp) return false;
		$content = fread($fp, filesize($tmpfile));
		fclose($fp);

		if ($this->hasImage())
			$queryStr = "UPDATE tblUserImages SET image = '".base64_encode($content)."', mimeType = ".$db->qstr($mimeType)." WHERE userID = " . $this->_id;
		else
			$queryStr = "INSERT INTO tblUserImages (userID, image, mimeType) VALUES (" . $this->_id . ", '".base64_encode($content)."', ".$db->qstr($mimeType).")";
		if (!$db->getResult($queryStr))
			return false;

		$this->_hasImage = true;
		return true;
	} /* }}} */

	/**
	 * Returns all documents of a given user
	 *
	 * @param object $user
	 * @return array list of documents
	 */
	function getDocuments() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser` ".
			"FROM `tblDocuments` ".
			"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
			"WHERE `tblDocuments`.`owner` = " . $this->_id . " ORDER BY `sequence`";

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$documents = array();
		$classname = $this->_dms->getClassname('document');
		foreach ($resArr as $row) {
			$document = new $classname($row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], $row["lockUser"], $row["keywords"], $row["sequence"]);
			$document->setDMS($this->_dms);
			$documents[] = $document;
		}
		return $documents;
	} /* }}} */

	/**
	 * Returns all documents locked by a given user
	 *
	 * @param object $user
	 * @return array list of documents
	 */
	function getDocumentsLocked() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser` ".
			"FROM `tblDocumentLocks` LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `tblDocumentLocks`.`document` ".
			"WHERE `tblDocumentLocks`.`userID` = '".$this->_id."' ".
			"ORDER BY `id` DESC";

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$documents = array();
		$classname = $this->_dms->getClassname('document');
		foreach ($resArr as $row) {
			$document = new $classname($row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], $row["lockUser"], $row["keywords"], $row["sequence"]);
			$document->setDMS($this->_dms);
			$documents[] = $document;
		}
		return $documents;
	} /* }}} */

	/**
	 * Returns all documents check out by a given user
	 *
	 * @param object $user
	 * @return array list of documents
	 */
	function getDocumentsCheckOut() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT `tblDocuments`.* ".
			"FROM `tblDocumentCheckOuts` LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `tblDocumentCheckOuts`.`document` ".
			"WHERE `tblDocumentCheckOuts`.`userID` = '".$this->_id."' ".
			"ORDER BY `id` ASC";

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$documents = array();
		$classname = $this->_dms->getClassname('document');
		foreach ($resArr as $row) {
			$document = new $classname($row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], $row["lockUser"], $row["keywords"], $row["sequence"]);
			$document->setDMS($this->_dms);
			$documents[] = $document;
		}
		return $documents;
	} /* }}} */

	/**
	 * Get a list of reviews
	 * This function returns a list of all reviews seperated by individual
	 * and group reviews. If the document id
	 * is passed, then only this document will be checked for reviews. The
	 * same is true for the version of a document which limits the list
	 * further.
	 *
	 * For a detailed description of the result array see
	 * {link SeedDMS_Core_User::getApprovalStatus} which does the same for
	 * approvals.
	 *
	 * @param int $documentID optional document id for which to retrieve the
	 *        reviews
	 * @param int $version optional version of the document
	 * @return array list of all reviews
	 */
	function getReviewStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		$status = array("indstatus"=>array(), "grpstatus"=>array());

		// See if the user is assigned as an individual reviewer.
		$queryStr = "SELECT `tblDocumentReviewers`.*, `tblDocumentReviewLog`.`status`, ".
			"`tblDocumentReviewLog`.`comment`, `tblDocumentReviewLog`.`date`, ".
			"`tblDocumentReviewLog`.`userID` ".
			"FROM `tblDocumentReviewers` ".
			"LEFT JOIN `tblDocumentReviewLog` USING (`reviewID`) ".
			"WHERE `tblDocumentReviewers`.`type`='0' ".
			($documentID==null ? "" : "AND `tblDocumentReviewers`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentReviewers`.`version` = '". (int) $version ."' ").
			"AND `tblDocumentReviewers`.`required`='". $this->_id ."' ".
			"ORDER BY `tblDocumentReviewLog`.`reviewLogID` DESC";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				if(isset($status["indstatus"][$res['documentID']])) {
					if($status["indstatus"][$res['documentID']]['date'] < $res['date']) {
						$status["indstatus"][$res['documentID']] = $res;
					}
				} else {
					$status["indstatus"][$res['documentID']] = $res;
				}
			}
		}

		// See if the user is the member of a group that has been assigned to
		// review the document version.
		$queryStr = "SELECT `tblDocumentReviewers`.*, `tblDocumentReviewLog`.`status`, ".
			"`tblDocumentReviewLog`.`comment`, `tblDocumentReviewLog`.`date`, ".
			"`tblDocumentReviewLog`.`userID` ".
			"FROM `tblDocumentReviewers` ".
			"LEFT JOIN `tblDocumentReviewLog` USING (`reviewID`) ".
			"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`groupID` = `tblDocumentReviewers`.`required` ".
			"WHERE `tblDocumentReviewers`.`type`='1' ".
			($documentID==null ? "" : "AND `tblDocumentReviewers`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentReviewers`.`version` = '". (int) $version ."' ").
			"AND `tblGroupMembers`.`userID`='". $this->_id ."' ".
			"ORDER BY `tblDocumentReviewLog`.`reviewLogID` DESC";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				if(isset($status["grpstatus"][$res['documentID']])) {
					if($status["grpstatus"][$res['documentID']]['date'] < $res['date']) {
						$status["grpstatus"][$res['documentID']] = $res;
					}
				} else {
					$status["grpstatus"][$res['documentID']] = $res;
				}
			}
		}
		return $status;
	} /* }}} */

	/**
	 * Get a list of approvals
	 * This function returns a list of all approvals seperated by individual
	 * and group approvals. If the document id
	 * is passed, then only this document will be checked for approvals. The
	 * same is true for the version of a document which limits the list
	 * further.
	 *
	 * The result array has two elements:
	 * - indstatus: which contains the approvals by individuals (users)
	 * - grpstatus: which contains the approvals by groups
	 *
	 * Each element is itself an array of approvals with the following elements:
	 * - approveID: unique id of approval
	 * - documentID: id of document, that needs to be approved
	 * - version: version of document, that needs to be approved
	 * - type: 0 for individual approval, 1 for group approval
	 * - required: id of user who is required to do the approval
	 * - status: 0 not approved, ....
	 * - comment: comment given during approval
	 * - date: date of approval
	 * - userID: id of user who has done the approval
	 *
	 * @param int $documentID optional document id for which to retrieve the
	 *        approvals
	 * @param int $version optional version of the document
	 * @return array list of all approvals
	 */
	function getApprovalStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		$status = array("indstatus"=>array(), "grpstatus"=>array());
		$queryStr =
   "SELECT `tblDocumentApprovers`.*, `tblDocumentApproveLog`.`status`, ".
			"`tblDocumentApproveLog`.`comment`, `tblDocumentApproveLog`.`date`, ".
			"`tblDocumentApproveLog`.`userID` ".
			"FROM `tblDocumentApprovers` ".
			"LEFT JOIN `tblDocumentApproveLog` USING (`approveID`) ".
			"WHERE `tblDocumentApprovers`.`type`='0' ".
			($documentID==null ? "" : "AND `tblDocumentApprovers`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentApprovers`.`version` = '". (int) $version ."' ").
			"AND `tblDocumentApprovers`.`required`='". $this->_id ."' ".
			"ORDER BY `tblDocumentApproveLog`.`approveLogID` DESC";

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				if(isset($status["indstatus"][$res['documentID']])) {
					if($status["indstatus"][$res['documentID']]['date'] < $res['date']) {
						$status["indstatus"][$res['documentID']] = $res;
					}
				} else {
					$status["indstatus"][$res['documentID']] = $res;
				}
			}
		}

		// See if the user is the member of a group that has been assigned to
		// approve the document version.
		$queryStr =
			"SELECT `tblDocumentApprovers`.*, `tblDocumentApproveLog`.`status`, ".
			"`tblDocumentApproveLog`.`comment`, `tblDocumentApproveLog`.`date`, ".
			"`tblDocumentApproveLog`.`userID` ".
			"FROM `tblDocumentApprovers` ".
			"LEFT JOIN `tblDocumentApproveLog` USING (`approveID`) ".
			"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`groupID` = `tblDocumentApprovers`.`required` ".
			"WHERE `tblDocumentApprovers`.`type`='1' ".
			($documentID==null ? "" : "AND `tblDocumentApprovers`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentApprovers`.`version` = '". (int) $version ."' ").
			"AND `tblGroupMembers`.`userID`='". $this->_id ."' ".
			"ORDER BY `tblDocumentApproveLog`.`approveLogID` DESC";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				if(isset($status["grpstatus"][$res['documentID']])) {
					if($status["grpstatus"][$res['documentID']]['date'] < $res['date']) {
						$status["grpstatus"][$res['documentID']] = $res;
					}
				} else {
					$status["grpstatus"][$res['documentID']] = $res;
				}
			}
		}
		return $status;
	} /* }}} */

	/**
	 * Get a list of receipts
	 * This function returns a list of all receipts seperated by individual
	 * and group receipts. If the document id
	 * is passed, then only this document will be checked for receipts. The
	 * same is true for the version of a document which limits the list
	 * further.
	 *
	 * For a detaile description of the result array see
	 * {link SeedDMS_Core_User::getApprovalStatus} which does the same for
	 * approvals.
	 *
	 * @param int $documentID optional document id for which to retrieve the
	 *        receipt
	 * @param int $version optional version of the document
	 * @return array list of all receipts
	 */
	function getReceiptStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		$status = array("indstatus"=>array(), "grpstatus"=>array());

		// See if the user is assigned as an individual recipient.
		$queryStr = "SELECT `tblDocumentRecipients`.*, `tblDocumentReceiptLog`.`status`, ".
			"`tblDocumentReceiptLog`.`comment`, `tblDocumentReceiptLog`.`date`, ".
			"`tblDocumentReceiptLog`.`userID` ".
			"FROM `tblDocumentRecipients` ".
			"LEFT JOIN `tblDocumentReceiptLog` USING (`receiptID`) ".
			"WHERE `tblDocumentRecipients`.`type`='0' ".
			($documentID==null ? "" : "AND `tblDocumentRecipients`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentRecipients`.`version` = '". (int) $version ."' ").
			"AND `tblDocumentRecipients`.`required`='". $this->_id ."' ".
			"ORDER BY `tblDocumentReceiptLog`.`receiptLogID` DESC";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				if(isset($status["indstatus"][$res['documentID']])) {
					if($status["indstatus"][$res['documentID']]['date'] < $res['date']) {
						$status["indstatus"][$res['documentID']] = $res;
					}
				} else {
					$status["indstatus"][$res['documentID']] = $res;
				}
			}
		}

		// See if the user is the member of a group that has been assigned to
		// receipt the document version.
		$queryStr = "SELECT `tblDocumentRecipients`.*, `tblDocumentReceiptLog`.`status`, ".
			"`tblDocumentReceiptLog`.`comment`, `tblDocumentReceiptLog`.`date`, ".
			"`tblDocumentReceiptLog`.`userID` ".
			"FROM `tblDocumentRecipients` ".
			"LEFT JOIN `tblDocumentReceiptLog` USING (`receiptID`) ".
			"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`groupID` = `tblDocumentRecipients`.`required` ".
			"WHERE `tblDocumentRecipients`.`type`='1' ".
			($documentID==null ? "" : "AND `tblDocumentRecipients`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentRecipients`.`version` = '". (int) $version ."' ").
			"AND `tblGroupMembers`.`userID`='". $this->_id ."' ".
			"ORDER BY `tblDocumentReceiptLog`.`receiptLogID` DESC";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				if(isset($status["grpstatus"][$res['documentID']])) {
					if($status["grpstatus"][$res['documentID']]['date'] < $res['date']) {
						$status["grpstatus"][$res['documentID']] = $res;
					}
				} else {
					$status["grpstatus"][$res['documentID']] = $res;
				}
			}
		}
		return $status;
	} /* }}} */

	/**
	 * Get a list of revisions
	 * This function returns a list of all revisions seperated by individual
	 * and group revisions. If the document id
	 * is passed, then only this document will be checked for revisions. The
	 * same is true for the version of a document which limits the list
	 * further.
	 *
	 * For a detaile description of the result array see
	 * {link SeedDMS_Core_User::getApprovalStatus} which does the same for
	 * approvals.
	 *
	 * @param int $documentID optional document id for which to retrieve the
	 *        revisions
	 * @param int $version optional version of the document
	 * @return array list of all revisions. If the result array has no elements,
	 * then the user was not a revisor. If there elements for 'indstatus' or
	 * 'grpstatus' then the revision hasn't been started.
	 */
	function getRevisionStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		$status = array("indstatus"=>array(), "grpstatus"=>array());

		// See if the user is assigned as an individual revisor.
		$queryStr = "SELECT `tblDocumentRevisors`.*, `tblDocumentRevisionLog`.`status`, ".
			"`tblDocumentRevisionLog`.`comment`, `tblDocumentRevisionLog`.`date`, ".
			"`tblDocumentRevisionLog`.`userID` ".
			"FROM `tblDocumentRevisors` ".
			"LEFT JOIN `tblDocumentRevisionLog` USING (`revisionID`) ".
			"WHERE `tblDocumentRevisors`.`type`='0' ".
			($documentID==null ? "" : "AND `tblDocumentRevisors`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentRevisors`.`version` = '". (int) $version ."' ").
			"AND `tblDocumentRevisors`.`required`='". $this->_id ."' ".
			"ORDER BY `tblDocumentRevisionLog`.`revisionLogID` DESC";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				if($res['date']) {
					if(isset($status["indstatus"][$res['documentID']])) {
						if($status["indstatus"][$res['documentID']]['date'] < $res['date']) {
							$status["indstatus"][$res['documentID']] = $res;
						}
					} else {
						$status["indstatus"][$res['documentID']] = $res;
					}
				}
			}
		}

		// See if the user is the member of a group that has been assigned to
		// revision the document version.
		$queryStr = "SELECT `tblDocumentRevisors`.*, `tblDocumentRevisionLog`.`status`, ".
			"`tblDocumentRevisionLog`.`comment`, `tblDocumentRevisionLog`.`date`, ".
			"`tblDocumentRevisionLog`.`userID` ".
			"FROM `tblDocumentRevisors` ".
			"LEFT JOIN `tblDocumentRevisionLog` USING (`revisionID`) ".
			"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`groupID` = `tblDocumentRevisors`.`required` ".
			"WHERE `tblDocumentRevisors`.`type`='1' ".
			($documentID==null ? "" : "AND `tblDocumentRevisors`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentRevisors`.`version` = '". (int) $version ."' ").
			"AND `tblGroupMembers`.`userID`='". $this->_id ."' ".
			"ORDER BY `tblDocumentRevisionLog`.`revisionLogID` DESC";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				if(isset($status["grpstatus"][$res['documentID']])) {
					if($status["grpstatus"][$res['documentID']]['date'] < $res['date']) {
						$status["grpstatus"][$res['documentID']] = $res;
					}
				} else {
					$status["grpstatus"][$res['documentID']] = $res;
				}
			}
		}
		return $status;
	} /* }}} */

	/**
	 * Get a list of documents with a workflow
	 *
	 * @param int $documentID optional document id for which to retrieve the
	 *        reviews
	 * @param int $version optional version of the document
	 * @return array list of all workflows
	 */
	function getWorkflowStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = 'SELECT DISTINCT d.*, c.userid FROM tblWorkflowTransitions a LEFT JOIN tblWorkflows b ON a.workflow=b.id LEFT JOIN tblWorkflowTransitionUsers c ON a.id=c.transition LEFT JOIN tblWorkflowDocumentContent d ON b.id=d.workflow WHERE d.document IS NOT NULL AND a.state=d.state AND c.userid='.$this->_id;
		if($documentID) {
			$queryStr .= ' AND d.document='.(int) $documentID;
			if($version)
				$queryStr .= ' AND d.version='.(int) $version;
		}
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		$result['u'] = array();
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				$result['u'][] = $res;
			}
		}

		$queryStr = 'select distinct d.*, c.groupid from tblWorkflowTransitions a left join tblWorkflows b on a.workflow=b.id left join tblWorkflowTransitionGroups c on a.id=c.transition left join tblWorkflowDocumentContent d on b.id=d.workflow left join tblGroupMembers e on c.groupid = e.groupID where d.document is not null and a.state=d.state and e.userID='.$this->_id;
		if($documentID) {
			$queryStr .= ' AND d.document='.(int) $documentID;
			if($version)
				$queryStr .= ' AND d.version='.(int) $version;
		}
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		$result['g'] = array();
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				$result['g'][] = $res;
			}
		}
		return $result;
	} /* }}} */

	/**
	 * Get a list of mandatory reviewers
	 * A user which isn't trusted completely may have assigned mandatory
	 * reviewers (both users and groups).
	 * Whenever the user inserts a new document the mandatory reviewers are
	 * filled in as reviewers.
	 *
	 * @return array list of arrays with two elements containing the user id
	 *         (reviewerUserID) and group id (reviewerGroupID) of the reviewer.
	 */
	function getMandatoryReviewers() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblMandatoryReviewers WHERE userID = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);

		return $resArr;
	} /* }}} */

	/**
	 * Get a list of mandatory approvers
	 * See {link SeedDMS_Core_User::getMandatoryReviewers}
	 *
	 * @return array list of arrays with two elements containing the user id
	 *         (approverUserID) and group id (approverGroupID) of the approver.
	 */
	function getMandatoryApprovers() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblMandatoryApprovers WHERE userID = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);

		return $resArr;
	} /* }}} */

	/**
	 * Get the mandatory workflow
	 * A user which isn't trusted completely may have assigned mandatory
	 * workflow
	 * Whenever the user inserts a new document the mandatory workflow is
	 * filled in as the workflow.
	 *
	 * @return object workflow
	 */
	function getMandatoryWorkflow() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblWorkflowMandatoryWorkflow WHERE userid = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr) return false;

		if(!$resArr)
			return null;

		$workflow = $this->_dms->getWorkflow($resArr[0]['workflow']);
		return $workflow;
	} /* }}} */

	/**
	 * Get the mandatory workflows
	 * A user which isn't trusted completely may have assigned mandatory
	 * workflow
	 * Whenever the user inserts a new document the mandatory workflow is
	 * filled in as the workflow.
	 *
	 * @return object workflow
	 */
	function getMandatoryWorkflows() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblWorkflowMandatoryWorkflow WHERE userid = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr) return false;

		if(!$resArr)
			return null;

		$workflows = array();
		foreach($resArr as $res) {
			$workflows[] = $this->_dms->getWorkflow($res['workflow']);
		}
		return $workflows;
	} /* }}} */

	/**
	 * Set a mandatory reviewer
	 * This function sets a mandatory reviewer if it isn't already set.
	 *
	 * @param integer $id id of reviewer
	 * @param boolean $isgroup true if $id is a group
	 * @return boolean true on success, otherwise false
	 */
	function setMandatoryReviewer($id, $isgroup=false) { /* {{{ */
		$db = $this->_dms->getDB();

		if ($isgroup){

			$queryStr = "SELECT * FROM tblMandatoryReviewers WHERE userID = " . $this->_id . " AND reviewerGroupID = " . $id;
			$resArr = $db->getResultArray($queryStr);
			if (count($resArr)!=0) return true;

			$queryStr = "INSERT INTO tblMandatoryReviewers (userID, reviewerGroupID) VALUES (" . $this->_id . ", " . $id .")";
			$resArr = $db->getResult($queryStr);
			if (is_bool($resArr) && !$resArr) return false;

		}else{

			$queryStr = "SELECT * FROM tblMandatoryReviewers WHERE userID = " . $this->_id . " AND reviewerUserID = " . $id;
			$resArr = $db->getResultArray($queryStr);
			if (count($resArr)!=0) return true;

			$queryStr = "INSERT INTO tblMandatoryReviewers (userID, reviewerUserID) VALUES (" . $this->_id . ", " . $id .")";
			$resArr = $db->getResult($queryStr);
			if (is_bool($resArr) && !$resArr) return false;
		}

	} /* }}} */

	/**
	 * Set a mandatory approver
	 * This function sets a mandatory approver if it isn't already set.
	 *
	 * @param integer $id id of approver
	 * @param boolean $isgroup true if $id is a group
	 * @return boolean true on success, otherwise false
	 */
	function setMandatoryApprover($id, $isgroup=false) { /* {{{ */
		$db = $this->_dms->getDB();

		if ($isgroup){

			$queryStr = "SELECT * FROM tblMandatoryApprovers WHERE userID = " . $this->_id . " AND approverGroupID = " . (int) $id;
			$resArr = $db->getResultArray($queryStr);
			if (count($resArr)!=0) return;

			$queryStr = "INSERT INTO tblMandatoryApprovers (userID, approverGroupID) VALUES (" . $this->_id . ", " . $id .")";
			$resArr = $db->getResult($queryStr);
			if (is_bool($resArr) && !$resArr) return false;

		}else{

			$queryStr = "SELECT * FROM tblMandatoryApprovers WHERE userID = " . $this->_id . " AND approverUserID = " . (int) $id;
			$resArr = $db->getResultArray($queryStr);
			if (count($resArr)!=0) return;

			$queryStr = "INSERT INTO tblMandatoryApprovers (userID, approverUserID) VALUES (" . $this->_id . ", " . $id .")";
			$resArr = $db->getResult($queryStr);
			if (is_bool($resArr) && !$resArr) return false;
		}
	} /* }}} */

	/**
	 * Set a mandatory workflow
	 * This function sets a mandatory workflow if it isn't already set.
	 *
	 * @param object $workflow workflow
	 * @return boolean true on success, otherwise false
	 */
	function setMandatoryWorkflow($workflow) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblWorkflowMandatoryWorkflow WHERE userid = " . $this->_id . " AND workflow = " . (int) $workflow->getID();
		$resArr = $db->getResultArray($queryStr);
		if (count($resArr)!=0) return;

		$queryStr = "INSERT INTO tblWorkflowMandatoryWorkflow (userid, workflow) VALUES (" . $this->_id . ", " . $workflow->getID() .")";
		$resArr = $db->getResult($queryStr);
		if (is_bool($resArr) && !$resArr) return false;
	} /* }}} */

	/**
	 * Set a mandatory workflows
	 * This function sets a list of mandatory workflows.
	 *
	 * @param array $workflows list of workflow objects
	 * @return boolean true on success, otherwise false
	 */
	function setMandatoryWorkflows($workflows) { /* {{{ */
		$db = $this->_dms->getDB();

		$db->startTransaction();
		$queryStr = "DELETE FROM tblWorkflowMandatoryWorkflow WHERE userid = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		foreach($workflows as $workflow) {
			$queryStr = "INSERT INTO tblWorkflowMandatoryWorkflow (userid, workflow) VALUES (" . $this->_id . ", " . $workflow->getID() .")";
			$resArr = $db->getResult($queryStr);
			if (is_bool($resArr) && !$resArr) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$db->commitTransaction();
		return true;
	} /* }}} */

	/**
	 * Deletes all mandatory reviewers
	 *
	 * @return boolean true on success, otherwise false
	 */
	function delMandatoryReviewers() { /* {{{ */
		$db = $this->_dms->getDB();
		$queryStr = "DELETE FROM tblMandatoryReviewers WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;
		return true;
	} /* }}} */

	/**
	 * Deletes all mandatory approvers
	 *
	 * @return boolean true on success, otherwise false
	 */
	function delMandatoryApprovers() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM tblMandatoryApprovers WHERE userID = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;
		return true;
	} /* }}} */

	/**
	 * Deletes the  mandatory workflow
	 *
	 * @return boolean true on success, otherwise false
	 */
	function delMandatoryWorkflow() { /* {{{ */
		$db = $this->_dms->getDB();
		$queryStr = "DELETE FROM tblWorkflowMandatoryWorkflow WHERE userid = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;
		return true;
	} /* }}} */

	/**
	 * Get all substitutes of the user
	 *
	 * These users are substitutes of the current user
	 *
	 * @return array list of users
	 */
	function getSubstitutes() { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_substitutes)) {
			$queryStr = "SELECT `tblUsers`.* FROM `tblUserSubstitutes` ".
				"LEFT JOIN `tblUsers` ON `tblUserSubstitutes`.`substitute` = `tblUsers`.`id` ".
				"WHERE `tblUserSubstitutes`.`user`='". $this->_id ."'";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_substitutes = array();
			$classname = $this->_dms->getClassname('user');
			foreach ($resArr as $row) {
				$user = new $classname($row["id"], $row["login"], $row["pwd"], $row["fullName"], $row["email"], $row["language"], $row["theme"], $row["comment"], $row["role"], $row["hidden"], $row["disabled"], $row["pwdExpiration"], $row["loginfailures"], $row["quota"], $row["homefolder"]);
				$user->setDMS($this->_dms);
				array_push($this->_substitutes, $user);
			}
		}
		return $this->_substitutes;
	} /* }}} */

	/**
	 * Get all users this user is a substitute of
	 *
	 * @return array list of users
	 */
	function getReverseSubstitutes() { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_rev_substitutes)) {
			$queryStr = "SELECT `tblUsers`.* FROM `tblUserSubstitutes` ".
				"LEFT JOIN `tblUsers` ON `tblUserSubstitutes`.`user` = `tblUsers`.`id` ".
				"WHERE `tblUserSubstitutes`.`substitute`='". $this->_id ."'";
			/* None admins can only be substitutes for regular users, otherwise
			 * regular users can become admin
			 */
			if(!$this->isAdmin())
				$queryStr .= " AND `tblUsers`.`role` = ".SeedDMS_Core_User::role_user;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_rev_substitutes = array();
			$classname = $this->_dms->getClassname('user');
			foreach ($resArr as $row) {
				$user = new $classname($row["id"], $row["login"], $row["pwd"], $row["fullName"], $row["email"], $row["language"], $row["theme"], $row["comment"], $row["role"], $row["hidden"], $row["disabled"], $row["pwdExpiration"], $row["loginfailures"], $row["quota"], $row["homefolder"]);
				$user->setDMS($this->_dms);
				array_push($this->_rev_substitutes, $user);
			}
		}
		return $this->_rev_substitutes;
	} /* }}} */

	/**
	 * Add a substitute to the user
	 *
	 * @return boolean true if successful otherwise false
	 */
	function addSubstitute($substitute) { /* {{{ */
		$db = $this->_dms->getDB();

		if(get_class($substitute) != $this->_dms->getClassname('user'))
			return false;

		$queryStr = "SELECT * FROM tblUserSubstitutes WHERE user=" . $this->_id . " AND substitute=".$substitute->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) == 1) return true;

		$queryStr = "INSERT INTO tblUserSubstitutes (user, substitute) VALUES (" . $this->_id . ", ".$substitute->getID().")";
		if (!$db->getResult($queryStr))
			return false;

		$this->_substitutes = null;
		return true;
	} /* }}} */

	/**
	 * Remove a substitute from the user
	 *
	 * @return boolean true if successful otherwise false
	 */
	function removeSubstitute($substitute) { /* {{{ */
		$db = $this->_dms->getDB();

		if(get_class($substitute) != $this->_dms->getClassname('user'))
			return false;

		$queryStr = "SELECT * FROM tblUserSubstitutes WHERE user=" . $this->_id . " AND substitute=".$substitute->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) == 0) return true;

		$queryStr = "DELETE FROM tblUserSubstitutes WHERE user=" . $this->_id . " AND substitute=".$substitute->getID();
		if (!$db->getResult($queryStr))
			return false;

		$this->_substitutes = null;
		return true;
	} /* }}} */

	/**
	 * Check if user is a substitute of the current user
	 *
	 * @return boolean true if successful otherwise false
	 */
	function isSubstitute($substitute) { /* {{{ */
		$db = $this->_dms->getDB();

		if(get_class($substitute) != $this->_dms->getClassname('user'))
			return false;

		$queryStr = "SELECT * FROM tblUserSubstitutes WHERE user=" . $this->_id . " AND substitute=".$substitute->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) == 1) return true;

		return false;
	} /* }}} */

	/**
	 * Check if user may switch to the given user
	 *
	 * Switching to the given user is only allowed if the given user
	 * is a substitute for the current user.
	 *
	 * @return boolean true if successful otherwise false
	 */
	function maySwitchToUser($touser) { /* {{{ */
		$db = $this->_dms->getDB();

		if(get_class($touser) != $this->_dms->getClassname('user'))
			return false;

		/* switching to an admin account is always forbitten, unless the
		 * current user is admin itself
		 */
		if(!$this->isAdmin() && $touser->isAdmin())
			return false;

		$queryStr = "SELECT * FROM tblUserSubstitutes WHERE substitute=" . $this->_id . " AND user=".$touser->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) == 1) return true;

		return false;
	} /* }}} */

	/**
	 * Get all notifications of user
	 *
	 * @param integer $type type of item (T_DOCUMENT or T_FOLDER)
	 * @return array array of notifications
	 */
	function getNotifications($type=0) { /* {{{ */
		$db = $this->_dms->getDB();
		$queryStr = "SELECT `tblNotify`.* FROM `tblNotify` ".
		 "WHERE `tblNotify`.`userID` = ". $this->_id;
		if($type) {
			$queryStr .= " AND `tblNotify`.`targetType` = ". (int) $type;
		}

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$notifications = array();
		foreach ($resArr as $row) {
			$not = new SeedDMS_Core_Notification($row["target"], $row["targetType"], $row["userID"], $row["groupID"]);
			$not->setDMS($this);
			array_push($notifications, $not);
		}

		return $notifications;
	} /* }}} */

} /* }}} */
?>
