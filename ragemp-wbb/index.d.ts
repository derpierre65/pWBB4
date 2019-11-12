declare module 'ragemp-wbb' {
	type InstallOptions = {
		url: string
		key: string
		version?: 4 | 5
	};

	interface addPostUserIDObject {
		userID: number
		threadID: number
		subject: string
		message: string
		enableBBCodes?: number
		enableHtml?: number
		enableSmilies?: number
		showSignature?: number
		isDisabled?: number
		isGuest?: number
	}

	interface addPostObject {
		username: string
		threadID: number
		subject: string
		message: string
		enableBBCodes?: number
		enableHtml?: number
		enableSmilies?: number
		showSignature?: number
		isDisabled?: number
		isGuest?: number
	}

	const enum Actions {
		WBBADDUSER = 3,
		WBBCHECKPASSWORD = 4,
		WBBADDTOGROUPS = 5,
		WBBREMOVEFROMGROUPS = 6,
		WBBBANUSERNAME = 7,
		WBBBANUSERID = 8,
		WBBUNBANUSERNAME = 9,
		WBBUNBANUSERID = 10,
		WBBENABLEUSERNAME = 11,
		WBBENABLEUSERID = 12,
		WBBDISABLEUSERNAME = 13,
		WBBDISABLEUSERID = 14,
		WBBADDPOST = 15,
		WBBADDPOSTUSERID = 16,
		WBBGETUSERID = 17,
		WBBISFORBIDDENUSERNAME = 18,
		CHECKUPDATE = 19,
		WBBISBANNED = 20,
		WBBADDTHREAD = 21,
	}

	function install(options: InstallOptions): boolean

	function addUser(username: string, password: string, email: string, callback?: Function): any

	function checkPassword(userID: number, password: string, callback?: Function): any

	function addToGroups(userID: number, groupIDs: number[] | number, callback?: Function): any

	function removeFromGroups(userID: number, groupIDs: number[] | number, callback?: Function): any

	function ban(userIDOrUsername: string | number, reason: string, banTime: number, callback?: Function): any

	function unban(userIDOrUsername: string | number, callback?: Function): any

	function enable(userIDOrUsername: string | number, callback?: Function): any

	function addPost(object: addPostObject, callback?: Function): any

	function addPostUserID(object: addPostUserIDObject, callback?: Function): any

	function disableUsername(username: string, callback?: Function): any

	function disableUserID(userID: number, callback?: Function): any

	function getUserID(username: string, callback?: Function): any

	function isBannedUserID(userID: number, callback?: Function): any

	function isBannedUsername(username: string, callback?: Function): any
}
