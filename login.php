<?php
include "../../../wp-config.php";
include_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/OAuth/OAuth.php');
session_start();
if ($_GET['go'] == "SINA") {
	if (!class_exists('sinaOAuth')) {
		include dirname(__FILE__) . '/OAuth/sina_OAuth.php';
	} 

	$to = new SinaOAuth(SINA_APP_KEY, SINA_APP_SECRET);
} elseif ($_GET['go'] == "QQ") {
	if (!class_exists('qqOAuth')) {
		include dirname(__FILE__) . '/OAuth/qq_OAuth.php';
	} 

	$to = new qqOAuth(QQ_APP_KEY, QQ_APP_SECRET);
} elseif ($_GET['go'] == "NETEASE") {
	if (!class_exists('neteaseOAuth')) {
		include dirname(__FILE__) . '/OAuth/netease_OAuth.php';
	} 

	$to = new neteaseOAuth(APP_KEY, APP_SECRET);
} elseif ($_GET['go'] == "DOUBAN") {
	if (!class_exists('doubanOAuth')) {
		include dirname(__FILE__) . '/OAuth/douban_OAuth.php';
	} 

	$to = new doubanOAuth(DOUBAN_APP_KEY, DOUBAN_APP_SECRET);
} else {
	if ($_SESSION['callback']) {
		$callback = $_SESSION['callback'];
	} else {
		$callback = get_bloginfo('wpurl');
	} 
	header('Location:' . $callback);
} 

if ($_GET['go']) {
	$callback = get_bloginfo('wpurl') . '/wp-content/plugins/wp-to-mblog/login.php';

	$tok = $to -> getRequestToken($callback);

	$_SESSION["oauth_token_secret"] = $tok['oauth_token_secret'];

	$request_link = $to -> getAuthorizeURL($tok['oauth_token'], false, $callback);

	$_SESSION['go'] = $_GET['go'];

	header('Location:' . $request_link);
} 

?>