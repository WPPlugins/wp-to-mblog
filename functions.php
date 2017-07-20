<?php 
include_once( dirname(__FILE__) . '/config.php' );
// 同步列表
function wp_to_mblog_list($title, $postlink, $pic) {
	require_once( dirname(__FILE__) . '/OAuth/OAuth.php' );
	$status = wp_status($title, $postlink, 140);
	$status2 = wp_status($title, $postlink, 140, 1);
	$status3 = wp_status($title, $postlink, 163);
	//$status4 = wp_status($title, $postlink, 200);
	wp_to_mblog_t_qq($status2);
	wp_to_mblog_t_sina($status2, $pic);
	wp_to_mblog_t_163($status3, $pic);
	wp_to_mblog_twitter($status);
	wp_to_mblog_t_sohu($status2);
	wp_to_mblog_digu($status);
	wp_to_mblog_douban($status);
	wp_to_mblog_fanfou($status);
	wp_to_mblog_renjian($status2);
	wp_to_mblog_zuosa($status);
	//wp_to_mblog_9911($status);
	wp_to_mblog_follow5($status);
}
// 腾讯微博
function wp_to_mblog_t_qq($status) {
	if (!class_exists('qq_OAuth')) {
		include dirname(__FILE__) . '/OAuth/qq_OAuth.php';
	} 
	$qq = get_option('wptm_qq');
	$to = new qqClient(QQ_APP_KEY, QQ_APP_SECRET, $qq['oauth_token'], $qq['oauth_token_secret']);
	$result = $to -> update($status);
} 
// 新浪微博
function wp_to_mblog_t_sina($status, $pic) {
	if (!class_exists('sina_OAuth')) {
		include dirname(__FILE__) . '/OAuth/sina_OAuth.php';
	} 
	$sina = get_option('wptm_sina');
	$to = new sinaClient(SINA_APP_KEY, SINA_APP_SECRET, $sina['oauth_token'], $sina['oauth_token_secret']);
	if ($pic) {
		$result = $to -> upload($status , $pic);
	} else {
		$result = $to -> update($status);
	} 
} 
// 网易微博
function wp_to_mblog_t_163($status, $pic) {
	if (!class_exists('netease_OAuth')) {
		include dirname(__FILE__) . '/OAuth/netease_OAuth.php';
	} 
	$netease = get_option('wptm_netease');
	$to = new neteaseClient(APP_KEY, APP_SECRET, $netease['oauth_token'], $netease['oauth_token_secret']);
	if ($pic) {
		$result = $to -> upload($status , $pic);
	} else {
		$result = $to -> update($status);
	}
} 
// Twitter
function wp_to_mblog_twitter($status) {
	$wptm_options = get_option('wptm_options');
	if ($wptm_options['enable_proxy']) {
		$twitter = get_option('wptm_twitter');
		$api_url = 'http://smyxapi.appspot.com/api/statuses/update.xml';
		if ($wptm_options['custom_proxy']) {
			$api_url = $wptm_options['custom_proxy'];
		} 
		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, "$api_url");
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURLOPT_POST, 1);
		curl_setopt($curl_handle, CURLOPT_POSTFIELDS, "status=$status");
		curl_setopt($curl_handle, CURLOPT_USERPWD, "{$twitter['username']}:{$twitter['password']}");
		$buffer = curl_exec($curl_handle);
		curl_close($curl_handle);
	} else {
		if (!class_exists('twitter_OAuth')) {
			include dirname(__FILE__) . '/OAuth/twitter_OAuth.php';
		} 
		$twitter = get_option('wptm_twitter_oauth');
		$to = new twitterClient(T_APP_KEY, T_APP_SECRET, $twitter['oauth_token'], $twitter['oauth_token_secret']);
		$result = $to -> update($status);
	} 
} 
// 豆瓣
function wp_to_mblog_douban($status) {
	if (!class_exists('douban_OAuth')) {
		include dirname(__FILE__) . '/OAuth/douban_OAuth.php';
	} 
	$douban = get_option('wptm_douban');
	$to = new doubanClient(DOUBAN_APP_KEY, DOUBAN_APP_SECRET, $douban['oauth_token'], $douban['oauth_token_secret']);
	$result = $to -> update($status);
} 
// 嘀咕 [同步到 开心网、人人网、占座]
function wp_to_mblog_digu($status) {
	$digu = get_option('wptm_digu');
	$api_url = 'http://api.minicloud.com.cn/statuses/update.xml';
	$body = array('content' => $status);
	$headers = array('Authorization' => 'Basic ' . base64_encode("{$digu['username']}:{$digu['password']}"));
	$request = new WP_Http;
	$result = $request -> request($api_url , array('method' => 'POST', 'body' => $body, 'headers' => $headers));
} 
// 饭否
function wp_to_mblog_fanfou($status) {
	$fanfou = get_option('wptm_fanfou');
	$api_url = 'http://api.fanfou.com/statuses/update.xml';
	$body = array('status' => $status);
	$headers = array('Authorization' => 'Basic ' . base64_encode("{$fanfou['username']}:{$fanfou['password']}"));
	$request = new WP_Http;
	$result = $request -> request($api_url , array('method' => 'POST', 'body' => $body, 'headers' => $headers));
} 
// 搜狐微博
function wp_to_mblog_t_sohu($status) {
	$sohu = get_option('wptm_sohu');
	$api_url = 'http://api.t.sohu.com/statuses/update.xml';
	$body = array('status' => $status);
	$headers = array('Authorization' => 'Basic ' . base64_encode("{$sohu['username']}:{$sohu['password']}"));
	$request = new WP_Http;
	$result = $request -> request($api_url , array('method' => 'POST', 'body' => $body, 'headers' => $headers));
} 
// 人间网
function wp_to_mblog_renjian($status) {
	$renjian = get_option('wptm_renjian');
	$api_url = 'http://api.renjian.com/statuses/update.xml';
	$body = array('text' => $status);
	$headers = array('Authorization' => 'Basic ' . base64_encode("{$renjian['username']}:{$renjian['password']}"));
	$request = new WP_Http;
	$result = $request -> request($api_url , array('method' => 'POST', 'body' => $body, 'headers' => $headers));
} 
// 做啥网
function wp_to_mblog_zuosa($status) {
	$zuosa = get_option('wptm_zuosa');
	$api_url = 'http://api.zuosa.com/statuses/update.xml';
	$body = array('status' => $status);
	$headers = array('Authorization' => 'Basic ' . base64_encode("{$zuosa['username']}:{$zuosa['password']}"));
	$request = new WP_Http;
	$result = $request -> request($api_url , array('method' => 'POST', 'body' => $body, 'headers' => $headers));
} 
// 9911
function wp_to_mblog_9911($status) {
	$ms9911 = get_option('wptm_9911');
	$api_url = 'http://api.9911.com/statuses/update.xml';
	$body = array('status' => $status);
	$headers = array('Authorization' => 'Basic ' . base64_encode("{$ms9911['username']}:{$ms9911['password']}"));
	$request = new WP_Http;
	$result = $request -> request($api_url , array('method' => 'POST', 'body' => $body, 'headers' => $headers));
} 
// Follow5 [同步到 凤凰微博]
function wp_to_mblog_follow5($status) {
	$follow5 = get_option('wptm_follow5');
	$api_url = 'http://api.follow5.com/api/statuses/update.xml?api_key=C1D656C887DB993D6FB6CA4A30754ED8';
	$body = array('status' => $status, 'source' => 'qq_wp_follow5');
	$headers = array('Authorization' => 'Basic ' . base64_encode("{$follow5['username']}:{$follow5['password']}"));
	$request = new WP_Http;
	$result = $request -> request($api_url , array('method' => 'POST', 'body' => $body, 'headers' => $headers));
} 
?>