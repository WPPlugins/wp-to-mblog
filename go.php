<?php
include "../../../wp-config.php";
if (is_user_logged_in()) {
	include_once(dirname(__FILE__) . '/config.php');
	require_once(dirname(__FILE__) . '/OAuth/OAuth.php');
	session_start();
	if ($_GET['OAuth'] == "qq" || $_GET['OAuth'] == "QQ" || $_GET['callback'] == "QQ") {
		include_once("OAuth/qq_OAuth.php");
		$a = new qqOAuth(QQ_APP_KEY, QQ_APP_SECRET);
		$b = new qqOAuth(QQ_APP_KEY, QQ_APP_SECRET, $_SESSION['keys']['oauth_token'], $_SESSION['keys']['oauth_token_secret']);
		$access_token = "wptm_qq";
		$tid = "QQ";
	} elseif ($_GET['OAuth'] == "sina" || $_GET['OAuth'] == "SINA" || $_GET['callback'] == "SINA") {
		include_once("OAuth/sina_OAuth.php");
		$a = new sinaOAuth(SINA_APP_KEY, SINA_APP_SECRET);
		$b = new sinaOAuth(SINA_APP_KEY, SINA_APP_SECRET, $_SESSION['keys']['oauth_token'], $_SESSION['keys']['oauth_token_secret']);
		$access_token = "wptm_sina";
		$tid = "SINA";
	} elseif ($_GET['OAuth'] == "netease" || $_GET['OAuth'] == "NETEASE" || $_GET['callback'] == "NETEASE") {
		include_once("OAuth/netease_OAuth.php");
		$a = new neteaseOAuth(APP_KEY, APP_SECRET);
		$b = new neteaseOAuth(APP_KEY, APP_SECRET, $_SESSION['keys']['oauth_token'], $_SESSION['keys']['oauth_token_secret']);
		$access_token = "wptm_netease";
		$tid = "NETEASE";
	} elseif ($_GET['OAuth'] == "twitter" || $_GET['OAuth'] == "TWITTER" || $_GET['callback'] == "TWITTER") {
		include_once("OAuth/twitter_OAuth.php");
		$a = new twitterOAuth(T_APP_KEY, T_APP_SECRET);
		$b = new twitterOAuth(T_APP_KEY, T_APP_SECRET, $_SESSION['keys']['oauth_token'], $_SESSION['keys']['oauth_token_secret']);
		$access_token = "wptm_twitter_oauth";
		$tid = "TWITTER";
	} elseif ($_GET['OAuth'] == "douban" || $_GET['OAuth'] == "DOUBAN" || $_GET['callback'] == "DOUBAN") {
		include_once("OAuth/douban_OAuth.php");
		$a = new doubanOAuth(DOUBAN_APP_KEY, DOUBAN_APP_SECRET);
		$b = new doubanOAuth(DOUBAN_APP_KEY, DOUBAN_APP_SECRET, $_SESSION['keys']['oauth_token'], $_SESSION['keys']['oauth_token_secret']);
		$access_token = "wptm_douban";
		$tid = "DOUBAN";
	} else {
		return false;
	} 
	if ($_GET['OAuth']) {
		$callback = get_bloginfo('wpurl') . '/wp-content/plugins/wp-to-mblog/go.php?callback=' . $tid;

		$keys = $a -> getRequestToken($callback);

		$aurl = $a -> getAuthorizeURL($keys['oauth_token'], false, $callback);

		$_SESSION['keys'] = $keys;

		header('Location:' . $aurl);
	} elseif ($_GET['callback']) {
		$last_key = $b -> getAccessToken($_REQUEST['oauth_verifier']);

		$_SESSION['last_key'] = $last_key;

		$update = array ('oauth_token' => $_SESSION['last_key']['oauth_token'],
			'oauth_token_secret' => $_SESSION['last_key']['oauth_token_secret']
			);
		update_option($access_token, $update);

		header('Location:' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=wp-to-mblog');
	} 
} 

?>