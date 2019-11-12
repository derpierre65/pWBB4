const axios = require('axios');

let isInstalled = false;
let options = {};
const actions = {
	wbbAddUser: 3,
	wbbCheckPassword: 4,
	wbbAddToGroups: 5,
	wbbRemoveFromGroups: 6,
	wbbBanUsername: 7,
	wbbBanUserID: 8,
	wbbUnbanUsername: 9,
	wbbUnbanUserID: 10,
	wbbEnableUsername: 11,
	wbbEnableUserID: 12,
	wbbDisableUsername: 13,
	wbbDisableUserID: 14,
	wbbAddPost: 15,
	wbbAddPostUserID: 16,
	wbbGetUserID: 17,
	wbbIsForbiddenUsername: 18,
	checkUpdate: 19,
	wbbIsBanned: 20,
	wbbAddThread: 21
};

function buildRequestData(data) {
	let finalData = {};
	let charCode = 97;
	for (let value of data) {
		if (typeof value !== 'function') {
			finalData[String.fromCharCode(charCode)] = value;
			charCode++;
		}
	}

	return finalData;
}

function sendRequest(action, data, callback) {
	// search callback in arguments list to prevent use Promise

	let requestUrl = options.url + '/samp.php';
	console.log(buildRequestData(data));
	let req = axios.get(requestUrl, {
		params: Object.assign({ action, key: options.key, responseType: 'json' }, buildRequestData(data))
	});

	if (typeof callback === 'function') {
		req.then((res) => {
			callback(null, res.data);
		}).catch((err) => {
			callback(err);
		});

		return;
	}

	return new Promise((resolve, reject) => {
		req.then((res) => resolve(res.data)).catch(reject);
	});
}

exports.actions = actions;

exports.install = (installOptions) => {
	if (typeof installOptions !== 'object' || installOptions === null || !installOptions.key || !installOptions.url) {
		throw new Error('option key and url are required.');
	}

	options = installOptions;

	// remove trailing slash
	while (options.url[options.url.length - 1] === '/') {
		options.url = options.url.substr(0, options.url.length - 1);
	}

	isInstalled = true;

	return true;
};

exports.native = sendRequest;
exports.addUser = (username, password, email, callback) => sendRequest(actions.wbbAddUser, [username, password, email], callback);
exports.checkPassword = (userID, password, callback) => sendRequest(actions.wbbCheckPassword, [userID, password], callback);
exports.addToGroups = (userID, groupIDs, callback) => sendRequest(actions.wbbAddToGroups, [userID, groupIDs], callback);
exports.removeFromGroups = (userID, groupIDs, callback) => sendRequest(actions.wbbRemoveFromGroups, [userID, groupIDs], callback);
exports.ban = (userIDOrUsername, reason = '', banTime = 0, callback) => {
	let type = typeof userIDOrUsername === 'number' ? 2 : 1;
	return sendRequest(actions.wbbBanUserID, [type, userIDOrUsername, reason, banTime], callback);
};
exports.unban = (userIDOrUsername, callback) => {
	let type = typeof userIDOrUsername === 'number' ? 2 : 1;
	return sendRequest(actions.wbbUnbanUserID, [type, userIDOrUsername], callback);
};
exports.enable = (userIDOrUsername, callback) => {
	let type = typeof userIDOrUsername === 'number' ? 2 : 1;
	return sendRequest(actions.wbbEnableUserID, [type, 1, userIDOrUsername], callback);
};
exports.disable = (userIDOrUsername, callback) => {
	let type = typeof userIDOrUsername === 'number' ? 2 : 1;
	return sendRequest(actions.wbbEnableUserID, [type, 2, userIDOrUsername], callback);
};
exports.addPost = ({ username, threadID, subject, message, enableBBCodes = 1, enableHtml = 0, enableSmilies = 1, showSignature = 1, isDisabled = 0, isGuest = 0 }, callback) => sendRequest(actions.wbbAddPost, [username, threadID, subject, message, enableBBCodes, enableHtml, enableSmilies, showSignature, 1, isDisabled, isGuest], callback);
exports.addPostUserID = ({ userID, threadID, subject, message, enableBBCodes = 1, enableHtml = 0, enableSmilies = 1, showSignature = 1, isDisabled = 0, isGuest = 0 }, callback) => sendRequest(actions.wbbAddPostUserID, [userID, threadID, subject, message, enableBBCodes, enableHtml, enableSmilies, showSignature, 2, isDisabled, isGuest], callback);
exports.getUserID = (username, callback) => sendRequest(actions.wbbGetUserID, [username], callback);
exports.isBanned = (userIDOrUsername, callback) => {
	let type = typeof userIDOrUsername === 'number' ? 0 : 1;
	return sendRequest(actions.wbbIsBanned, [userIDOrUsername, type], callback);
};