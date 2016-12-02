<?php
define('_SECURITY_KEY', '4004fdca193cda44691cf6f98300673d64123f32df16453e3f8d6b7b8c24111a');
define('_CHECK_REMOTEADDR', '127.0.0.1');

define('_VERSION',			'0.1');

use wcf\data\user\User;
use wcf\data\user\UserAction;
use wcf\data\user\UserRegistrationAction;

// wcf\data\user\infraction\warning\UserInfractionWarningAction  -> warn (user: user object, title, points, expires, reason)

class Core {
	private $functions = array(
		'phpHash', // PHP_Hash
		'phpEmail', // PHP_CheckEmail
		'wbbAddUser', // WBB_AddUser
		'wbbCheckPassword', // WBB_CheckPassword
		'wbbAddToGroups', // WBB_AddToGroups
		'wbbRemoveFromGroups', // WBB_RemoveFromGroups
		'wbbBanUser', // WBB_BanUsername
		'wbbBanUser', // WBB_BanUserID
		'wbbUnbanUser', // WBB_UnbanUsername
		'wbbUnbanUser', // WBB_UnbanUserID
		'wbbDisableEnableUser', // WBB_EnableUsername
		'wbbDisableEnableUser', // WBB_EnableUserID
		'wbbDisableEnableUser', // WBB_DisableUsername
		'wbbDisableEnableUser', // WBB_DisableUserID
		'wbbAddPost', // WBB_AddPost
		'wbbAddPost', // WBB_AddPostUserID
		'wbbGetUserID', // WBB_GetUserID
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
	public function wbbDisableEnableUser() {
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
	// TODO
	public function wbbAddPost() {
		$this->getPost(array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i'));

		// Get user by name or id?
		if ( $this->postI == 1 ) {
			$this->getUserByUsername($this->postA);
		} else {
			$this->getUserByUserID($this->postA);
		}

		// user dont exist (-1)
		if ( $this->userObj->userID < 1 ) $this->setError(-1);

		// Board is closed or dont exist (-2)
		$this->board = BoardCache::getInstance()->getBoard($this->postB);
		if ($this->board === null || !$this->board->isBoard() || $this->board->isClosed) $this->setError(-2);

		// subject invalid (-5)
		if ( empty($this->postC) ) $this->setError(-5);
		if ( mb_strlen($this->postC) > 255 ) $this->postC = mb_substr($this->postC, 0, 255);

		// found censored words (-6)
		if (ENABLE_CENSORSHIP) {
			$result = Censorship::getInstance()->test($this->postC);
			if ($result) {
				WCF::getTPL()->assign('censoredWords', $result);
				$this->setError(-6);
			}
		}

		// text is empty (-7)
		if ( empty($this->postD) ) return $this->setError(-7);
		$this->maxTextLength = WCF::getSession()->getPermission('user.board.maxPostLength');
		// #debug HIER WEITER MACHEN

		// text too long (-8)
		if ($this->maxTextLength != 0 && mb_strlen($this->postD) > $this->maxTextLength) $this->setError(-8);

		// text is to short (-3)
		if (WBB_THREAD_MIN_CHAR_LENGTH && mb_strlen($this->postD) < WBB_THREAD_MIN_CHAR_LENGTH) $this->setError(-3);

		// min. word count not reached (-4)
		if ( WBB_THREAD_MIN_WORD_COUNT && count(explode(' ', $this->postD)) < WBB_THREAD_MIN_WORD_COUNT ) $this->setError(-4);

		$data = array(
			'boardID' => $this->postB,
			'languageID' => $this->userObj->getLanguage(),
			//'topic' => $this->
		);

	}
	public function wbbGetUserID() {
		$this->getPost(array('a'));
		if ( !$this->getUserByUsername($this->postA) ) $this->setError(-1, $this->postA);
		$this->setStatus(1);
		$this->setResponse($this->userObj->userID);
	}
}

$Core = new Core();
