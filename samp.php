<?php
define('API_VERSION', '0.5.1');

/** @noinspection PhpIncludeInspection */
require('./samp.inc.php');

if ( !defined('_pWBB4_WCF_VERSION') ) {
	define('_pWBB4_WCF_VERSION', 2); // wcf 2/2.1 - wbb 4/4.1
}

if ( !defined('_pWBB4_WBB_DIR') ) {
	define('_pWBB4_WBB_DIR', dirname(__FILE__));
}

use wbb\data\board\BoardCache;
use wbb\data\post\PostAction;
use wcf\data\cronjob\Cronjob;
use wcf\data\trophy\Trophy;
use wcf\data\user\trophy\UserTrophyAction;
use wcf\data\user\trophy\UserTrophyList;
use wcf\data\user\User;
use wcf\data\user\UserAction;
use wcf\data\user\UserRegistrationAction;
use wcf\system\cronjob\UserBanCronjob;
use wcf\system\message\censorship\Censorship;
use wcf\system\WCF;

// wcf\data\user\infraction\warning\UserInfractionWarningAction  -> warn (user: user object, title, points, expires, reason)

/**
 * Class SAMPCore
 *
 * @author      Pierre Wüst
 * @property mixed postA
 * @property mixed postB
 * @property mixed postC
 * @property mixed postD
 * @property mixed postE
 * @property mixed postF
 * @property mixed postG
 * @property mixed postH
 * @property mixed postI
 * @property mixed postJ
 * @property mixed postK
 */
class SAMPCore {
	private $functions = [
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
		'wbbIsForbiddenUsername', // WBB_IsForbiddenUsername
		'checkUpdates',
		'wbbIsBanned', // WBB_IsBannedUserID
		'wbbIsBanned', // WBB_IsBannedUsername
		'wbbAddTrophy'
	];

	private $index = -1;

	private $action = -1;

	private $status = -1;

	private $playerid = -1;

	private $responseType = null;

	private $response = null;

	/**
	 * @var \wcf\data\user\User
	 */
	private $userObj = null;

	public function __construct() {
		if ( defined('_SECURITY_KEY') && (empty($_GET['key']) || $_GET['key'] != _SECURITY_KEY) ) {
			$this->setError(403);
		}
		if ( defined('_CHECK_REMOTEADDR') ) {
			$ips = explode(',', _CHECK_REMOTEADDR);
			$foundIp = false;
			foreach ( $ips as $key => $ip ) {
				if ( $ip == $_SERVER['REMOTE_ADDR'] ) {
					$foundIp = true;
					break;
				}
			}

			if ( !$foundIp ) {
				$this->setError(403);
			}
		}
		if ( empty($_REQUEST['action']) ) {
			$this->setError(404);
		}
		if ( isset($_GET['playerid']) ) {
			$this->playerid = intval($_GET['playerid']);
		}
		if ( isset($_GET['index']) ) {
			$this->index = intval($_GET['index']);
		}
		$this->action = intval($_GET['action']) - 1;
		if ( isset($_REQUEST['responseType']) ) {
			$this->responseType = $_REQUEST['responseType'];
		}

		$functionName = $this->functions[$this->action];
		if ( !isset($functionName) ) {
			$this->setError(406);
		}
		if ( !method_exists($this, $functionName) && !method_exists($this, $functionName._pWBB4_WCF_VERSION) ) {
			$this->setError(406);
		}
		if ( substr($functionName, 0, 3) == 'wbb' ) {
			$this->initWBB();
		}

		if ( method_exists($this, $functionName._pWBB4_WCF_VERSION) ) {
			$this->{$functionName._pWBB4_WCF_VERSION}();
		}
		else {
			$this->{$functionName}();
		}

		$this->generateOutput();
	}

	public function initWBB() {
		/** @noinspection PhpIncludeInspection */
		require_once(_pWBB4_WBB_DIR.'/global.php');
	}

	public function setResponse($res) {
		$this->response = $res;
	}

	public function setStatus($status) {
		$this->status = $status;
	}

	public function setError($errorCode, $res = null) {
		if ( $res !== null ) {
			$this->setResponse($res);
		}

		$this->status = $errorCode;
		$this->generateOutput();

		return true;
	}

	public function generateOutput() {
		if ( $this->responseType === 'json' ) {
			header('Content-Type: application/json');

			die(json_encode([
				'index'    => $this->index,
				'playerid' => $this->playerid,
				'status'   => $this->status,
				'response' => $this->response
			]));
		}

		if ( $this->response === null ) {
			$this->response = 'null';
		}
		echo join([
			$this->index,
			$this->playerid,
			$this->status,
			$this->response
		], ' ');
		exit;
	}

	public function getPost($keys) {
		foreach ( $keys as $key => $value ) {
			if ( is_string($key) && is_string($value) ) {
				if ( !isset($_REQUEST[$key]) ) {
					$this->setError(400);
				}

				$this->{$value} = $_REQUEST[$key];
				continue;
			}

			if ( !isset($_REQUEST[$value]) ) {
				$this->setError(400);
			}
			$varName = 'post'.ucfirst($value);
			$this->{$varName} = $_REQUEST[$value];
		}

		return false;
	}

	public function checkUpdates() {
		$version = @file_get_contents('https://raw.githubusercontent.com/derpierre65/pWBB4/master/VERSION', false, stream_context_create([
			"ssl" => [
				"verify_peer"      => false,
				"verify_peer_name" => false
			]
		]));

		if ( version_compare($version, API_VERSION, '>') ) {
			$this->setResponse($version);
			$this->setStatus(1);
		}
		else {
			$this->setStatus(0);
		}
	}

	public function isValidEmail($email) {
		return preg_match('/^[^\x00-\x20()<>@,;:\\".[\]\x7f-\xff]+(?:\.[^\x00-\x20()<>@,;:\\".[\]\x7f-\xff]+)*\@[^\x00-\x20()<>@,;:\\".[\]\x7f-\xff]+(?:\.[^\x00-\x20()<>@,;:\\".[\]\x7f-\xff]+)+$/i', $email);
	}

	private function getUserByUserID($userID) {
		$this->userObj = new User($userID);
		if ( $this->userObj->userID < 1 ) {
			return false;
		}

		return true;
	}

	private function getUserByUsername($username) {
		$this->userObj = new User(null);
		$this->userObj = $this->userObj->getUserByUsername($username);
		if ( $this->userObj->userID < 1 ) {
			return false;
		}

		return true;
	}

	/* php functions */
	public function phpEmail() {
		$this->getPost(['a']);
		$valid = $this->isValidEmail($this->postA);
		$this->setResponse($this->postA);
		$this->setStatus($valid ? 1 : 0);
	}

	public function phpHash() {
		$this->getPost(['a', 'b']);
		$this->response = @hash($this->postA, $this->postB);
		if ( $this->response ) {
			$this->setStatus(1);
		}
		else {
			$this->setStatus(0);
		}
	}

	/* wbb functions */

	/**
	 * @throws \wcf\system\exception\SystemException
	 */
	public function wbbRemoveFromGroups() {
		$this->getPost(['a', 'b']);
		if ( !$this->getUserByUserID($this->postA) ) {
			$this->setError(-1);
		}

		$groupIDs = explode(',', $this->postB);
		$removeGroupIDs = [];
		foreach ( $groupIDs as $groupID ) {
			if ( in_array($groupID, $this->userObj->getGroupIDs()) ) {
				$removeGroupIDs[] = $groupID;
			}
		}

		if ( empty($removeGroupIDs) ) {
			$this->setError(-2);
		}

		$action = new UserAction([$this->userObj], 'removeFromGroups', [
			'groups'           => $removeGroupIDs,
			'addDefaultGroups' => false,
			'deleteOldGroups'  => false
		]);
		if ( !$action->executeAction() ) {
			$this->setError(-3);
		}
		$this->setStatus(1);
	}

	/**
	 * @throws \wcf\system\exception\SystemException
	 */
	public function wbbAddToGroups() {
		$this->getPost(['a', 'b']);
		if ( !$this->getUserByUserID($this->postA) ) {
			$this->setError(-1);
		}

		$groupIDs = explode(',', $this->postB);
		$addGroupIDs = [];
		foreach ( $groupIDs as $groupID ) {
			if ( !in_array($groupID, $this->userObj->getGroupIDs()) ) {
				$addGroupIDs[] = $groupID;
			}
		}

		if ( empty($addGroupIDs) ) {
			$this->setError(-2);
		}

		$action = new UserAction([$this->userObj], 'addToGroups', [
			'groups'           => $addGroupIDs,
			'addDefaultGroups' => false,
			'deleteOldGroups'  => false
		]);
		if ( !$action->executeAction() ) {
			$this->setError(-3);
		}
		$this->setStatus(1);
	}

	public function wbbCheckPassword() {
		$this->getPost(['a', 'b']);
		if ( !$this->getUserByUserID($this->postA) ) {
			$this->setError(-1);
		}
		if ( !$this->userObj->checkPassword($this->postB) ) {
			$this->setError(-2);
		}
		$this->setStatus(1);
	}

	/**
	 * @throws \wcf\system\exception\SystemException
	 */
	public function wbbAddUser() {
		$this->getPost(['a', 'b', 'c']);

		$reg = (new UserRegistrationAction([], 'validateUsername', ['username' => $this->postA]))->executeAction()['returnValues'];
		if ( !$reg['isValid'] && $reg['error'] == 'notValid' ) {
			$this->setError(-1, $this->postA);
		}
		if ( !$reg['isValid'] ) {
			$this->setError(-2, $this->postA);
		}

		$reg = (new UserRegistrationAction([], 'validatePassword', ['password' => $this->postB]))->executeAction()['returnValues'];
		if ( !$reg['isValid'] ) {
			$this->setError(-3, $this->postB);
		}

		$reg = (new UserRegistrationAction([], 'validateEmailAddress', ['email' => $this->postC]))->executeAction()['returnValues'];
		if ( !$reg['isValid'] && $reg['error'] == 'notValid' ) {
			$this->setError(-4, $this->postC);
		}
		if ( !$reg['isValid'] ) {
			$this->setError(-5, $this->postC);
		}

		$action = new UserAction([], 'create', [
			'data' => [
				'username' => $this->postA,
				'password' => $this->postB,
				'email'    => $this->postC
			]
		]);
		/**
		 * @var $user \wcf\data\user\User
		 */
		$user = $action->executeAction()['returnValues'];
		$this->setStatus(1);
		$this->setResponse($user->getUserID());
	}

	/**
	 * @throws \wcf\system\exception\SystemException
	 */
	public function wbbBanUser() {
		$this->getPost(['a', 'b', 'c', 'd']);
		if ( $this->postA == 1 ) {
			$this->getUserByUsername($this->postB);
		}
		else {
			$this->getUserByUserID($this->postB);
		}
		if ( $this->userObj->userID < 1 ) {
			$this->setError(-1);
		}

		$this->postD = intval($this->postD);
		if ( $this->postD > 0 ) {
			$this->postD = date('d.m.Y H:i:s', time() + $this->postD);
		}

		$action = new UserAction([$this->userObj->userID], 'ban', [
			'banExpires' => $this->postD,
			'banReason'  => $this->postC
		]);
		if ( !$action->executeAction() ) {
			$this->setError(-2);
		}
		$this->setStatus(1);
	}

	/**
	 * @throws \wcf\system\exception\SystemException
	 */
	public function wbbUnbanUser() {
		$this->getPost(['a', 'b']);
		if ( $this->postA == 1 ) {
			$this->getUserByUsername($this->postB);
		}
		else {
			$this->getUserByUserID($this->postB);
		}
		if ( $this->userObj->userID < 1 ) {
			$this->setError(-1);
		}

		if ( !(new UserAction([$this->userObj->userID], 'unban', []))->executeAction() ) {
			$this->setError(-2);
		}
		$this->setStatus(1);
	}

	/**
	 * @throws \wcf\system\exception\SystemException
	 */
	public function wbbDisableEnableUser() {
		$this->getPost(['a', 'b', 'c']);
		$action = 'enable';
		if ( $this->postB == 2 ) {
			$action = 'disable';
		}

		if ( $this->postA == 1 ) {
			$this->getUserByUsername($this->postC);
		}
		else {
			$this->getUserByUserID($this->postC);
		}
		if ( $this->userObj->userID < 1 ) {
			$this->setError(-1);
		}

		if ( !(new UserAction([$this->userObj->userID], $action, []))->executeAction() ) {
			$this->setError(-2);
		}
		$this->setStatus(1);
	}

	/**
	 * wbbAddPost function for wcf 2.0/2.1
	 *
	 * @return void
	 * @throws \wcf\system\exception\SystemException
	 *
	 */
	public function wbbAddPost2() {
		$this->getPost(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k']);

		// set response to userID/username if user dont exist
		$this->setResponse($this->postA);

		// Get user by name or id?
		if ( $this->postI == 1 ) {
			$this->getUserByUsername($this->postA);
		}
		else {
			$this->getUserByUserID($this->postA);

			if ( $this->userObj->userID < 1 ) {
				$this->setError(-1);
			}
		}

		$userID = $username = null;
		if ( $this->userObj->userID < 1 ) {
			if ( $this->postK == 1 ) {
				$userID = null;
				$username = $this->postA;
			}
			else {
				$this->setError(1);
			}
		}
		else {
			$userID = $this->userObj->userID;
			$username = $this->userObj->username;
		}

		// exist thread?
		$thread = new \wbb\data\thread\Thread($this->postB);
		if ( $thread === null || !$thread->threadID ) {
			$this->setError(-2);
		}

		// is thread closed, deleted or disabled?
		if ( $thread->isDeleted || $thread->isClosed || $thread->isDisabled ) {
			if ( $thread->isClosed ) {
				$this->setResponse(1);
			}
			elseif ( $thread->isDeleted ) {
				$this->setResponse(2);
			}
			elseif ( $thread->isDisabled ) {
				$this->setResponse(3);
			}

			$this->setError(-3);
		}

		if ( mb_strlen($this->postC) > 255 ) {
			$this->postC = mb_substr($this->postC, 0, 255);
		}

		// text is empty
		if ( empty($this->postD) ) {
			$this->setError(-4);
		}

		// found censored words
		if ( ENABLE_CENSORSHIP ) {
			$result = Censorship::getInstance()->test($this->postD);
			if ( $result ) {
				$this->setError(-5);
			}
		}

		// login in as user
		WCF::getSession()->changeUser($this->userObj, true);

		// looking for max text length
		$maxTextLength = WCF::getSession()->getPermission('user.board.maxPostLength');

		// delete login session
		WCF::getSession()->delete();

		// text too long
		if ( $maxTextLength != 0 && mb_strlen($this->postD) > $maxTextLength ) {
			$this->setError(-6);
		}

		// text is too short
		if ( WBB_THREAD_MIN_CHAR_LENGTH && mb_strlen($this->postD) < WBB_THREAD_MIN_CHAR_LENGTH ) {
			$this->setError(-7);
		}

		// min. word count not reached
		if ( WBB_THREAD_MIN_WORD_COUNT && count(explode(' ', $this->postD)) < WBB_THREAD_MIN_WORD_COUNT ) {
			$this->setError(-8);
		}

		$action = new PostAction([], 'create', [
			'data' => [
				'threadID'      => $this->postB,
				'subject'       => $this->postC,
				'message'       => $this->postD,
				'time'          => TIME_NOW,
				'userID'        => $userID,
				'username'      => $username,
				'enableBBCodes' => $this->postE,
				'enableHtml'    => $this->postF,
				'enableSmilies' => $this->postG,
				'showSignature' => $this->postH,
				'enableTime'    => 0, // TODO: implement
				'isDisabled'    => $this->postJ == 1 ? 1 : 0
			]
		]);
		$post = $action->executeAction()['returnValues'];

		$this->setResponse($post->postID);
		$this->setStatus(1);
	}

	/**
	 * wbbAddPost function for wsc 3.0/3.1
	 *
	 * @return void
	 * @throws \wcf\system\exception\SystemException
	 *
	 */
	public function wbbAddPost3() {
		$this->getPost(['a', 'b', 'd', 'i', 'j', 'k']);
		// set response to userID/username if user dont exist
		$this->setResponse($this->postA);
		// Get user by name or id?
		if ( $this->postI == 1 ) {
			$this->getUserByUsername($this->postA);
		}
		else {
			$this->getUserByUserID($this->postA);
			if ( $this->userObj->userID < 1 ) {
				$this->setError(-1);
			}
		}
		$userID = $username = null;
		if ( $this->userObj->userID < 1 ) {
			if ( $this->postK == 1 ) {
				$userID = null;
				$username = $this->postA;
			}
			else {
				$this->setError(1);
			}
		}
		else {
			$userID = $this->userObj->userID;
			$username = $this->userObj->username;
		}
		// exist thread?
		$thread = new \wbb\data\thread\Thread($this->postB);
		if ( $thread === null || !$thread->threadID ) {
			$this->setError(-2);
		}
		// is thread closed, deleted or disabled?
		if ( $thread->isDeleted || $thread->isClosed || $thread->isDisabled ) {
			if ( $thread->isClosed ) {
				$this->setResponse(1);
			}
			elseif ( $thread->isDeleted ) {
				$this->setResponse(2);
			}
			elseif ( $thread->isDisabled ) {
				$this->setResponse(3);
			}
			$this->setError(-3);
		}
		// text is empty
		if ( empty($this->postD) ) {
			$this->setError(-4);
		}
		// found censored words
		if ( ENABLE_CENSORSHIP ) {
			$result = Censorship::getInstance()->test($this->postD);
			if ( $result ) {
				$this->setError(-5);
			}
		}
		// login in as user
		WCF::getSession()->changeUser($this->userObj, true);
		// looking for max text length
		$maxTextLength = WCF::getSession()->getPermission('user.board.maxPostLength');
		// delete login session
		WCF::getSession()->delete();
		// text too long
		if ( $maxTextLength != 0 && mb_strlen($this->postD) > $maxTextLength ) {
			$this->setError(-6);
		}
		// text is too short
		if ( WBB_THREAD_MIN_CHAR_LENGTH && mb_strlen($this->postD) < WBB_THREAD_MIN_CHAR_LENGTH ) {
			$this->setError(-7);
		}
		// min. word count not reached
		if ( WBB_THREAD_MIN_WORD_COUNT && count(explode(' ', $this->postD)) < WBB_THREAD_MIN_WORD_COUNT ) {
			$this->setError(-8);
		}

		$action = new PostAction([], 'create', [
			'data' => [
				'threadID'   => $this->postB,
				'message'    => $this->postD,
				'time'       => TIME_NOW,
				'userID'     => $userID,
				'username'   => $username,
				'enableTime' => 0, // TODO: implement
				'isDisabled' => $this->postJ == 1 ? 1 : 0
			]
		]);
		$post = $action->executeAction()['returnValues'];
		$this->setResponse($post->postID);
		$this->setStatus(1);
	}

	/**
	 * coming soon
	 * wbbAddThread
	 *
	 * @return bool
	 * @throws \wcf\system\exception\SystemException
	 *
	 */
	public function wbbAddThread2() {
		$this->getPost(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i']);

		// Get user by name or id?
		if ( $this->postI == 1 ) {
			$this->getUserByUsername($this->postA);
		}
		else {
			$this->getUserByUserID($this->postA);
		}

		// user dont exist (-1)
		if ( $this->userObj->userID < 1 ) {
			$this->setError(-1);
		}

		// Board is closed or dont exist (-2)
		$board = BoardCache::getInstance()->getBoard($this->postB);
		if ( $board === null || !$board->isBoard() || $board->isClosed ) {
			$this->setError(-2);
		}

		// subject invalid (-5)
		if ( empty($this->postC) ) {
			$this->setError(-5);
		}
		if ( mb_strlen($this->postC) > 255 ) {
			$this->postC = mb_substr($this->postC, 0, 255);
		}

		// found censored words (-6)
		if ( ENABLE_CENSORSHIP ) {
			$result = Censorship::getInstance()->test($this->postC);
			if ( $result ) {
				WCF::getTPL()->assign('censoredWords', $result);
				$this->setError(-6);
			}
		}

		// text is empty (-7)
		if ( empty($this->postD) ) {
			return $this->setError(-7);
		}
		$maxTextLength = WCF::getSession()->getPermission('user.board.maxPostLength');
		// #debug HIER WEITER MACHEN

		// text too long (-8)
		if ( $maxTextLength != 0 && mb_strlen($this->postD) > $maxTextLength ) {
			$this->setError(-8);
		}

		// text is to short (-3)
		if ( WBB_THREAD_MIN_CHAR_LENGTH && mb_strlen($this->postD) < WBB_THREAD_MIN_CHAR_LENGTH ) {
			$this->setError(-3);
		}

		// min. word count not reached (-4)
		if ( WBB_THREAD_MIN_WORD_COUNT && count(explode(' ', $this->postD)) < WBB_THREAD_MIN_WORD_COUNT ) {
			$this->setError(-4);
		}

		$data = [
			'boardID'    => $this->postB,
			'languageID' => $this->userObj->getLanguage(),
			//'topic' => $this->
		];
	}

	public function wbbGetUserID() {
		$this->getPost(['a']);
		if ( !$this->getUserByUsername($this->postA) ) {
			$this->setError(-1, $this->postA);
		}
		$this->setStatus(1);
		$this->setResponse($this->userObj->userID);
	}

	public function wbbIsForbiddenUsername() {
		$this->getPost(['a']);

		$forbiddenusernames = explode("\n", REGISTER_FORBIDDEN_USERNAMES);
		if ( in_array($this->postA, $forbiddenusernames) ) {
			$this->setStatus(1);
		}
		else {
			$this->setStatus(0);
		}
	}

	public function wbbIsBanned() {
		// b: 0 = userid | 1 = username
		$this->getPost(['a', 'b']);

		if ( class_exists(UserBanCronjob::class) ) {
			$cronjob = new UserBanCronjob();
			$cronjob->execute(new Cronjob(null));
		}

		if ( $this->postB == 1 ) {
			$this->getUserByUsername($this->postA);
		}
		else {
			$this->getUserByUserID($this->postA);
		}

		if ( $this->userObj->userID < 1 ) {
			$this->setResponse($this->postA);
			$this->setError(-1);
		}

		if ( $this->userObj->banned ) {
			$this->setResponse($this->userObj->banReason);
			$this->setStatus(1);
		}
		else {
			$this->setStatus(0);
		}
	}

	/**
	 * @param $variable
	 *
	 * @return mixed
	 */
	public function get($variable) {
		return $this->{$variable};
	}

	/**
	 * coming soon
	 *
	 * @throws \wcf\system\exception\SystemException
	 *
	 * -1: invalid userID or username
	 * -2: invalid trophyID
	 * -3: trophy can only added/deleted automatically
	 */
	public function wbbAddTrophy() {
		$_REQUEST['a'] = '1';
		$_REQUEST['b'] = 'hallo123';
		$_REQUEST['c'] = '1';
		$_REQUEST['d'] = '1';

		$this->getPost([
			'a' => 'isUsername',
			'b' => 'usernameUserID',
			'c' => 'isAddTrophy',
			'd' => 'trophyID'
		]);

		if ( !$this->get('isAddTrophy') ) {
			$this->getPost([
				'e' => 'deleteAll'
			]);
		}

		if ( $this->get('isUsername') ) {
			$this->getUserByUsername($this->get('usernameUserID'));
		}
		else {
			$this->getUserByUserID($this->get('usernameUserID'));
		}

		// -1: invalid userID/username
		if ( $this->userObj->userID < 1 ) {
			$this->setResponse($this->get('usernameUserID'));
			$this->setError(-1);
		}

		$trophy = new Trophy($this->get('trophyID'));
		if ( $trophy->trophyID < 1 ) {
			$this->setError(-2);
		}

		if ( $trophy->awardAutomatically ) {
			$this->setError(-3);
		}

		if ( $this->get('isAddTrophy') ) {
			(new UserTrophyAction([], 'create', [
				'data' => [
					'trophyID'             => $trophy->trophyID,
					'userID'               => $this->userObj->userID,
					'description'          => '',
					'time'                 => TIME_NOW,
					'useCustomDescription' => 0
				]
			]))->executeAction();

			$this->setResponse('ok');
			$this->setStatus(1);
		}
		else {
			$userTrophies = new UserTrophyList();
		}
	}
}

$core = new SAMPCore();