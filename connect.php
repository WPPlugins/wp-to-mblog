<?php
include_once(dirname(__FILE__) . '/config.php');
$plugin_url = get_bloginfo('wpurl').'/wp-content/plugins/wp-to-mblog';
$wptm_connect = get_option('wptm_connect');

add_action('init', 'wp_t_connect_init');

if ($wptm_connect['enable_connect']) { // 是否开启微博连接功能
	add_action('comment_form', 'wp_t_connect');
    add_action("login_form", "wp_t_connect");
    add_action("register_form", "wp_t_connect",12);
}

function wp_t_connect_init(){
	if (session_id() == "") {
		session_start();
	}
	if(!is_user_logged_in()) {		
        if(isset($_GET['oauth_token'])){
			require_once(dirname(__FILE__) . '/OAuth/OAuth.php');
			if($_SESSION['go'] == "SINA")    {wp_t_connect_sina();}
			if($_SESSION['go'] == "QQ")      {wp_t_connect_qq();}
			if($_SESSION['go'] == "NETEASE") {wp_t_connect_netease();}
			if($_SESSION['go'] == "DOUBAN")  {wp_t_connect_douban();}
        } 
    } 
}

function wp_t_connect($id=""){
    global $plugin_url, $wptm_connect;

if (is_user_logged_in()) {
	global $user_ID;
	$stid = get_user_meta($user_ID, 'stid', true);
	$qtid = get_user_meta($user_ID, 'qtid', true);
	$ntid = get_user_meta($user_ID, 'ntid', true);
	$dtid = get_user_meta($user_ID, 'dtid', true);
	$tdata = get_user_meta($user_ID, 'tdata', true);

	if ($stid && $tdata['tid'] == "stid") {
		echo '<p><input name="comment_to_sina" type="checkbox" id="comment_to_sina" value="1"  /><label for="comment_to_sina">同步评论到新浪微博</label></p>';
	} 
	if ($qtid && $tdata['tid'] == "qtid") {
		echo '<p><label for="comment_to_qq">同步评论到腾讯微博</label><input name="comment_to_qq" type="checkbox" id="comment_to_qq" value="1" style="width:30px;"  /></p>';
	} 
	if ($ntid && $tdata['tid'] == "ntid") {
		echo '<p><label for="comment_to_netease">同步评论到网易微博</label><input name="comment_to_netease" type="checkbox" id="comment_to_netease" value="1" style="width:30px;"  /></p>';
	} 
	if ($dtid && $tdata['tid'] == "dtid") {
		echo '<p><label for="comment_to_douban">同步评论到豆瓣</label><input name="comment_to_douban" type="checkbox" id="comment_to_douban" value="1" style="width:30px;"  /></p>';
	} 
	return;
}
	$_SESSION['callback'] = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
?>
	<style type="text/css"> 
	.t_login_button { padding-bottom: 5px;}
	.t_login_button img{ border:none;}
    </style>
<?php
	if(is_singular()) {
	echo '<p>您可以登录以下帐号发表评论：</p>';
	}
	echo '<p class="t_login_button">';
	if($wptm_connect['sina']) {
	echo '<a href="'.$plugin_url.'/login.php?go=SINA" rel="nofollow"><img src="'.$plugin_url.'/images/btn_sina.png" alt="使用新浪微博登录" /></a> ';
	}
	if($wptm_connect['qq']) {
	echo '<a href="'.$plugin_url.'/login.php?go=QQ" rel="nofollow"><img src="'.$plugin_url.'/images/btn_qq.png" alt="使用腾讯微博登录" /></a> ';
	}
	if($wptm_connect['douban']) {
	echo '<a href="'.$plugin_url.'/login.php?go=DOUBAN" rel="nofollow"><img src="'.$plugin_url.'/images/btn_douban.png" alt="使用豆瓣帐号登录" /></a> ';
	}
	if($wptm_connect['netease']) {
	echo '<a href="'.$plugin_url.'/login.php?go=NETEASE" rel="nofollow"><img src="'.$plugin_url.'/images/btn_netease.jpg" alt="使用网易微博登录" /></a> ';
	}
	echo '</p>';
}

//sina
function wp_t_connect_sina(){
	if (!class_exists('sinaOAuth')) {
		include dirname(__FILE__) . '/OAuth/sina_OAuth.php';
	}
	
	$to = new sinaOAuth(SINA_APP_KEY, SINA_APP_SECRET, $_GET['oauth_token'],$_SESSION['oauth_token_secret']);
	
	$tok = $to ->getAccessToken($_REQUEST['oauth_verifier']);

    //$to = new sinaClient(SINA_APP_KEY, SINA_APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
    //$sina = $to -> verify_credentials();
	$to = new sinaOAuth(SINA_APP_KEY, SINA_APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	$sina = $to->OAuthRequest('http://api.t.sina.com.cn/account/verify_credentials.json', 'GET',array());

	if($sina == "no auth"){
		echo '<script type="text/javascript">window.close();</script>';
		return;
	}

	//$sina = simplexml_load_string($sina);
	$sina = json_decode($sina);
	
	if((string)$sina->domain){
		$sina_username = $sina->domain;
	} else {
		$sina_username = $sina->id;
	}

	$user_email = $sina_username.'@t.sina.com.cn';
	$tid = "stid";
		
	wp_t_connect_login($sina->id.'|'.$sina_username.'|'.$sina->screen_name.'|'.$sina->url.'|'.$tok['oauth_token'] .'|'.$tok['oauth_token_secret'], $user_email, $tid); 
}
//qq
function wp_t_connect_qq(){
	if(!class_exists('qqOAuth')){
		include dirname(__FILE__).'/OAuth/qq_OAuth.php';
	}
	
	$to = new qqOAuth(QQ_APP_KEY, QQ_APP_SECRET, $_GET['oauth_token'],$_SESSION['oauth_token_secret']);
	
	$tok = $to->getAccessToken($_REQUEST['oauth_verifier']);

	$to = new qqOAuth(QQ_APP_KEY, QQ_APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);

	$qq = $to->OAuthRequest('http://open.t.qq.com/api/user/info?format=json', 'GET',array());

	if($qq == "no auth"){
		echo '<script type="text/javascript">window.close();</script>';
		return;
	}
	
	$qq = json_decode($qq);
	
	$qq = $qq ->data;

	$user_email = $qq->name.'@t.qq.com';
	$tid = "qtid";
		
	wp_t_connect_login($qq->head.'|'.$qq->name.'|'.$qq->nick.'||'.$tok['oauth_token'] .'|'.$tok['oauth_token_secret'], $user_email, $tid); 
}
//netease
function wp_t_connect_netease(){
	if (!class_exists('neteaseOAuth')) {
		include dirname(__FILE__) . '/OAuth/netease_OAuth.php';
	}
	
	$to = new neteaseOAuth(APP_KEY, APP_SECRET, $_GET['oauth_token'],$_SESSION['oauth_token_secret']);
	
	$tok = $to ->getAccessToken($_REQUEST['oauth_verifier']);

	$to = new neteaseOAuth(APP_KEY, APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	$netease = $to->OAuthRequest('http://api.t.163.com/account/verify_credentials.json', 'GET',array());

	if($netease == "no auth"){
		echo '<script type="text/javascript">window.close();</script>';
		return;
	}

	$netease = json_decode($netease);

	$user_email = $netease->screen_name.'@t.163.com';
	$tid = "ntid";
		
	wp_t_connect_login($netease->profile_image_url.'|'.$netease->screen_name.'|'.$netease->name.'|'.$netease->url.'|'.$tok['oauth_token'] .'|'.$tok['oauth_token_secret'], $user_email, $tid); 
}
//douban
function wp_t_connect_douban(){
	if (!class_exists('doubanOAuth')) {
		include dirname(__FILE__) . '/OAuth/douban_OAuth.php';
	}
	$to = new doubanOAuth(DOUBAN_APP_KEY, DOUBAN_APP_SECRET, $_GET['oauth_token'],$_SESSION["oauth_token_secret"]);
	
	$tok = $to->getAccessToken();

	$to = new doubanOAuth(DOUBAN_APP_KEY, DOUBAN_APP_SECRET, $tok['oauth_token'], $tok['oauth_token_secret']);
	
	$douban = $to->OAuthRequest('http://api.douban.com/people/%40me', array(), 'GET');
	if($douban == "no auth"){
		echo '<script type="text/javascript">window.close();</script>';
		return;
	}
	
	$douban = simplexml_load_string($douban);
	
	$douban_xmlns = $douban->children('http://www.douban.com/xmlns/');	

	$douban_id = str_replace("http://api.douban.com/people/","",$douban->id);
	$douban_url = "http://www.douban.com/people/".$douban_xmlns->uid;

	$user_email = $douban_xmlns->uid.'@douban.com';
	$tid = "dtid";
		
	wp_t_connect_login($douban_id.'|'.$douban_xmlns->uid.'|'.$douban->title.'|'.$douban->url.'|'.$tok['oauth_token'] .'|'.$tok['oauth_token_secret'], $user_email, $tid); 
}

function wp_t_connect_login($userinfo, $user_email, $tid) {
	global $wptm_connect;
	$userinfo = explode('|',$userinfo);
	if(count($userinfo) < 6) {
		wp_die("An error occurred while trying to contact Sina Connect.");
	}
	$callback = $_SESSION['callback'];
	if (preg_match("/\b$userinfo[1]\b/i", $wptm_connect['disable_username'])) {
		wp_die("很遗憾，”$userinfo[1]” 被系统保留，请更换微博帐号登录！返回 <a href='$callback'>$callback</a>");
	} 

	$wpuid = get_user_by_user_login('ID', $userinfo[1]);
	$wpmail = get_user_by_user_login('user_email', $userinfo[1]);
	$lock = get_user_meta($wpuid, 'lock',true);
	$tdata = get_user_meta($wpuid, 'tdata',true);
	$wpurl = get_bloginfo('wpurl');
	if ($lock) {
		if (($user_email != $wpmail) && ($userinfo[4] != $tdata['oauth_token'])) {
			wp_die("很遗憾，”$userinfo[1]” 已被 $wpmail 绑定，您可以使用该用户 <a href='$wpurl/wp-login.php'>登录</a> ，或者更换微博帐号，或者 <a href='$wpurl/wp-login.php?action=lostpassword'>找回密码</a>！<br />返回: <a href='$callback'>$callback</a>");
		}
	}

	if(!$wpuid) {
	    $wpuid = '';
	}

	$userdata = array(
		'ID' => $wpuid,
		'user_pass' => wp_generate_password(),
		'user_login' => $userinfo[1],
		'display_name' => $userinfo[2],
		'user_url' => $userinfo[3],
		'user_email' => $user_email
	);

	if(!function_exists('wp_insert_user')){
		include_once( ABSPATH . WPINC . '/registration.php' );
	} 

	if ($userinfo[0] && !$lock) {
		$wpuid = wp_insert_user($userdata);
	} 

	if ($wpuid) {
		update_usermeta($wpuid, $tid, $userinfo[0]);
		$t_array = array ("tid" => $tid,
			"oauth_token" => $userinfo[4],
			"oauth_token_secret" => $userinfo[5]
			);
		update_usermeta($wpuid, 'tdata', $t_array);
	} 

	if($wpuid) {
		wp_set_auth_cookie($wpuid, true, false);
		wp_set_current_user($wpuid);
	}
}

add_filter('user_contactmethods', 'wp_t_connect_author_page');
function wp_t_connect_author_page($input) {
	// add
	$input['lock'] = '锁定帐号 <span class="description">(请填任意值)<br />锁定后，其他同名的微博账号将无法注册，谨慎使用！</span>';
	// del
	unset($input['yim']);
	unset($input['aim']);
	unset($input['jabber']);
	return $input;
}

add_filter("get_avatar", "wp_t_connect_avatar",10,4);
function wp_t_connect_avatar($avatar, $email = '', $size = '32') {
	global $comment,$wptm_connect;
	if (is_object($comment)) {
		$email = $comment -> user_id;
		$comment_email = $comment -> comment_author_email;
	} 
	if (is_object($email)) {
		$email = $email -> user_id;
	} 
	if (preg_match("/@t.sina.com.cn/i", $comment_email)) {
		if ($stid = get_usermeta($email, 'stid')) {
			$out = 'http://tp3.sinaimg.cn/' . $stid . '/50/1.jpg';
			$avatar = "<img alt='' src='{$out}' class='avatar avatar-{$size}' height='{$size}' width='{$size}' />";
			return $avatar;
		} 
	} 
	if (preg_match("/@t.qq.com/i", $comment_email)) {
		if ($qtid = get_usermeta($email, 'qtid')) {
			$out = $qtid . '/40';
			$avatar = "<img alt='' src='{$out}' class='avatar avatar-{$size}' height='{$size}' width='{$size}' />";
			return $avatar;
		} 
	} 
	if (preg_match("/@t.163.com/i", $comment_email) && $wptm_connect['netease_avatar']) {
		if ($ntid = get_usermeta($email, 'ntid')) {
			$out = $ntid;
			$avatar = "<img alt='' src='{$out}' class='avatar avatar-{$size}' height='{$size}' width='{$size}' />";
			return $avatar;
		} 
	} 
	if (preg_match("/@douban.com/i", $comment_email)) {
		if ($dtid = get_usermeta($email, 'dtid')) {
			$out = 'http://t.douban.com/icon/u' . $dtid . '-1.jpg';
			$avatar = "<img alt='' src='{$out}' class='avatar avatar-{$size}' height='{$size}' width='{$size}' />";
			return $avatar;
		} 
	} else {
		return $avatar;
	} 
} 

add_action('comment_post', 'wp_t_connect_comment',1000);
function wp_t_connect_comment($id){
	global $wptm_connect;
	$comment_post_id = $_POST['comment_post_ID'];
	
	if(!$comment_post_id){
		return;
	}
	$comments = get_comment($id);
	$stid = get_user_meta($comments->user_id, 'stid',true);
	$qtid = get_user_meta($comments->user_id, 'qtid',true);
	$ntid = get_user_meta($comments->user_id, 'ntid',true);
	$dtid = get_user_meta($comments->user_id, 'dtid',true);
	$tdata = get_user_meta($comments->user_id, 'tdata',true);
	
	$content = strip_tags($comments->comment_content);
	$link = get_permalink($comment_post_id)."#comment-".$id;

    require_once(dirname(__FILE__) . '/OAuth/OAuth.php');
	if($stid){
		if($_POST['comment_to_sina']){
			if (!class_exists('sinaOAuth')) {
		        include dirname(__FILE__) . '/OAuth/sina_OAuth.php';
	        }
			$to = new sinaClient(SINA_APP_KEY, SINA_APP_SECRET,$tdata['oauth_token'], $tdata['oauth_token_secret']);
            if($wptm_connect['sina_username']) { $content = '@'.$wptm_connect['sina_username'].' '.$content; }
			$status = wp_status($content, $link, 140, 1);
			$result = $to -> update($status);
		}
	}
	if($qtid){
		if($_POST['comment_to_qq']){
			if(!class_exists('qqOAuth')){
				include dirname(__FILE__).'/OAuth/qq_OAuth.php';
			}
	        $to = new qqClient(QQ_APP_KEY, QQ_APP_SECRET,$tdata['oauth_token'], $tdata['oauth_token_secret']);
            if($wptm_connect['qq_username']) { $content = '@'.$wptm_connect['qq_username'].' '.$content; }
			$status = wp_status($content, $link, 140, 1);
	        $result = $to -> update($status);
		}
	}
	if($ntid){
		if($_POST['comment_to_netease']){
			if (!class_exists('neteaseOAuth')) {
		        include dirname(__FILE__) . '/OAuth/netease_OAuth.php';
	        }
			$to = new neteaseClient(APP_KEY, APP_SECRET,$tdata['oauth_token'], $tdata['oauth_token_secret']);
            if($wptm_connect['netease_username']) { $content = '@'.$wptm_connect['netease_username'].' '.$content; }
			$status = wp_status($content, $link, 163);
			$result = $to -> update($status);
		}
	}
	if($dtid){
		if($_POST['comment_to_douban']){
			if (!class_exists('doubanOAuth')) {
		        include dirname(__FILE__) . '/OAuth/douban_OAuth.php';
	        }
			$to = new doubanClient(DOUBAN_APP_KEY, DOUBAN_APP_SECRET,$tdata['oauth_token'], $tdata['oauth_token_secret']);
			$status = wp_status($content, $link, 140);
			$result = $to -> update($status);
		}
	}
}

function get_user_by_meta_value($meta_key, $meta_value) { // 获得user_id
	global $wpdb;
	$sql = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '%s' AND meta_value = '%s'";
	return $wpdb -> get_var($wpdb -> prepare($sql, $meta_key, $meta_value));
}

function get_user_by_user_login($table_name, $user_login) { // 获得user_value
	global $wpdb;
	$sql = "SELECT $table_name FROM $wpdb->users WHERE user_login = '%s'";
	return $wpdb -> get_var($wpdb -> prepare($sql, $user_login));
}

if(!function_exists('connect_login_form_login')){
	add_action("login_form_login", "connect_login_form_login");
	add_action("login_form_register", "connect_login_form_login");
	function connect_login_form_login(){
		if(is_user_logged_in()){
			$redirect_to = admin_url('profile.php');
			wp_safe_redirect($redirect_to);
		}
	}
}
?>