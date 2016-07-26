<?php
define('_VERSION',			'0.1');

use wcf\data\user\User;
use wcf\data\user\UserAction;
use wcf\data\user\UserRegistrationAction;

class Core {
	private $functions = array(
		'phpHash',
		'phpEmail',
		'wbbAddUser',
		'wbbCheckPassword',
		'wbbAddToGroups',
		'wbbRemoveFromGroups',
		'wbbBanUser',
		'wbbBanUser',
		'wbbUnbanUser',
		'wbbUnbanUser',
		'disableEnableUser',
		'disableEnableUser',
		'disableEnableUser',
		'disableEnableUser',
		'',
	);
	private $index = -1;
	private $action = -1;
	private $status = -1;
	private $playerid = -1;
	private $response = null;
	private $userObj = null;
	
	public function __construct() {
		if ( defined('_SECURITY_KEY') && (empty($_GET['key']) || $_GET['key'] !=_SECURITY_KEY) ) $this->setError(403);
		if ( defined('_CHECK_REMOTEADDR') && $_SERVER['REMOTE_ADDR'] != _CHECK_REMOTEADDR ) $this->setError(403);
		if ( empty($_GET['action']) ) $this->setError(404);
		if ( isset($_GET['playerid']) ) $this->playerid = intval($_GET['playerid']);
		if ( isset($_GET['index']) ) $this->index = intval($_GET['index']);
		$this->action = intval($_GET['action']) - 1;
		
		if ( !isset($this->functions[$this->action]) ) $this->setError(406);
		if ( !method_exists($this, $this->functions[$this->action]) ) $this->setError(406);
		if ( substr($this->functions[$this->action], 0, 3) == 'wbb' ) $this->initWBB();
		$this->{$this->functions[$this->action]}();
		$this->generateOutput();
	}
	public function initWBB() {
		require_once(dirname(__FILE__).'/global.php');
	}
	public function setResponse($res) {
		$this->response = $res;
	}
	public function setStatus($status) {
		$this->status = $status;
	}
	public function setError($errorcode, $res = null) {
		if ( $res ) {
			$this->setResponse($res);
		}
		$this->status = $errorcode;
		$this->generateOutput();
	}
	public function generateOutput() {
		if ( $this->response === null ) $this->response = 'null';
		echo join(array(
			$this->index,
			$this->playerid,
			$this->status,
			$this->response
		), ' ');
		exit;
	}
	public function getPost($keys) {
		foreach($keys as $key => $value) {
			if ( !isset($_POST[$value]) ) $this->setError(400);
			$varname = 'post'.ucfirst($value);
			$this->{$varname} = $_POST[$value];
		}
		return false;
	}
	public function isValidEmail($email) {
		return preg_match('/^[^\x00-\x20()<>@,;:\\".[\]\x7f-\xff]+(?:\.[^\x00-\x20()<>@,;:\\".[\]\x7f-\xff]+)*\@[^\x00-\x20()<>@,;:\\".[\]\x7f-\xff]+(?:\.[^\x00-\x20()<>@,;:\\".[\]\x7f-\xff]+)+$/i', $email);
	}
	private function getUserByUserID($userID) {
		$this->userObj = new User($userID);
		if ( $this->userObj->userID < 1 ) return false;
		return true;
	}
	private function getUserByUsername($username) {
		$this->userObj = new User(null);
		$this->userObj = $this->userObj->getUserByUsername($username);
		if ( $this->userObj->userID < 1 ) return false;
		return true;
	}
	/* php functions */
	public function phpEmail() {
		$this->getPost(array('a'));
		$valid = $this->isValidEmail($this->postA);
		$this->setResponse($this->postA);
		$this->setStatus($valid ? 1 : 0);
	}
	public function phpHash() {
		$this->getPost(array('a', 'b'));
		$this->response = @hash($this->postA, $this->postB);
		if ( $this->response )
			$this->setStatus(1);
		else
			$this->setStatus(0);
	}
	/* wbb functions */
	public function wbbRemoveFromGroups() {
		$this->getPost(array('a', 'b'));
		$action = new UserAction(array($this->postA), 'removeFromGroups', array(
			'groups' => explode(',', $this->postB),
			'addDefaultGroups' => false,
			'deleteOldGroups' => false
		));
		if ( !$action->executeAction() ) $this->setError(-1);
		$this->setStatus(1);
	}
	public function wbbAddToGroups() {
		$this->getPost(array('a', 'b'));
		if ( !$this->getUserByUserID($this->postA) ) $this->setError(-1);

		$action = new UserAction(array($this->postA), 'addToGroups', array(
			'groups' => explode(',', $this->postB),
			'addDefaultGroups' => false,
			'deleteOldGroups' => false
		));
		if ( !$action->executeAction() ) $this->setError(-1);
		$this->setStatus(1);
	}
	public function wbbCheckPassword() {
		$this->getPost(array('a', 'b'));
		if ( !$this->getUserByUserID($this->postA) ) $this->setError(-1);
		if ( !$this->userObj->checkPassword($this->postB) ) $this->setError(-2);
		$this->setStatus(1);
	}
	public function wbbAddUser() {
		$this->getPost(array('a', 'b', 'c'));

		$reg = (new UserRegistrationAction(array(), 'validateUsername', array('username' => $this->postA)))->executeAction()['returnValues'];
		if ( !$reg['isValid'] && $reg['error'] == 'notValid' ) $this->setError(-1, $this->postA);
		if ( !$reg['isValid'] ) $this->setError(-2, $this->postA);
		
		$reg = (new UserRegistrationAction(array(), 'validatePassword', array('password' => $this->postB)))->executeAction()['returnValues'];
		if ( !$reg['isValid'] ) $this->setError(-3, $this->postB);
		
		$reg = (new UserRegistrationAction(array(), 'validateEmailAddress', array('email' => $this->postC)))->executeAction()['returnValues'];
		if ( !$reg['isValid'] && $reg['error'] == 'notValid' ) $this->setError(-4, $this->postC);
		if ( !$reg['isValid'] ) $this->setError(-5, $this->postC);
		
		$action = new UserAction(array(), 'create', array('data' => array(
			'username' => $this->postA,
			'password' => $this->postB,
			'email' => $this->postC
		)));
		$user = $action->executeAction()['returnValues'];
		$this->setStatus(1);
		$this->setResponse($user->getUserID());
	}
	public function wbbBanUser() {
		$this->getPost(array('a', 'b', 'c', 'd'));
		if ( $this->postA == 1 ) {
			$this->getUserByUsername($this->postB);
		} else {
			$this->getUserByUserID($this->postB);
		}
		if ( $this->userObj->userID < 1 ) $this->setError(-1);
		
		$this->postD = intval($this->postD);
		if ( $this->postD > 0 ) $this->postD = date('d.m.Y H:i:s', time() + $this->postD);
		
		$action = new UserAction(array($this->userObj->userID), 'ban', array(
			'banExpires' => $this->postD,
			'banReason' => $this->postC
		));
		if ( !$action->executeAction() ) $this->setError(-2);
		$this->setStatus(1);
	}
	public function wbbUnbanUser() {
		$this->getPost(array('a', 'b'));
		if ( $this->postA == 1 ) {
			$this->getUserByUsername($this->postB);
		} else {
			$this->getUserByUserID($this->postB);
		}
		if ( $this->userObj->userID < 1 ) $this->setError(-1);

		if ( !(new UserAction(array($this->userObj->userID), 'unban', array()))->executeAction() ) $this->setError(-2);
		$this->setStatus(1);
	}
	public function disableEnableUser() {
		$this->getPost(array('a', 'b', 'c'));
		$action = 'enable';
		if ( $this->postB == 2 ) $action = 'disable';
		
		if ( $this->postA == 1 ) {
			$this->getUserByUsername($this->postB);
		} else {
			$this->getUserByUserID($this->postB);
		}
		if ( $this->userObj->userID < 1 ) $this->setError(-1);
		
		if ( !(new UserAction(array($this->userObj->userID), $action, array()))->executeAction() ) $this->setError(-2);
		$this->setStatus(1);
	}
}

$Core = new Core();
