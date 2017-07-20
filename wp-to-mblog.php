<?php
/*
Plugin Name: WordPress微博同步
Author: 水脉烟香
Author URI: http://www.smyx.net/
Plugin URI: http://www.smyx.net/wp-to-mblog.html
Description: 支持同步日志的 标题和链接 到各大微博和社区，并且支持使用微博帐号登录 WordPress 博客。
Version: 1.3.0
*/

add_action('admin_menu', 'wp_to_mblog_add_page');
add_action('init', 'wp_to_mblog_header');
add_action('admin_head', 'wp_to_mblog_reauthorize');

$plugin_url = get_bloginfo('wpurl').'/wp-content/plugins/wp-to-mblog';
$wptm_options = get_option('wptm_options');

include_once( dirname(__FILE__) . '/functions.php' );
include_once( dirname(__FILE__) . '/page.php' );
include_once( dirname(__FILE__) . '/connect.php' );

function wp_strlen($text) { // 字符长度(一个汉字代表一个字符，两个字母代表一个字符)
	$a = mb_strlen($text, 'UTF-8');
	$b = strlen($text);
	$c = $b / 3 ;
	$d = ($a + $b) / 4;
	if ($a == $b) { // 纯英文、符号、数字
		return $b / 2; 
	} elseif ($a == $c) { // 纯中文
		return $a;
	} elseif ($a != $c) { // 混合
		return $d;
	} 
}

function wp_status($content, $url, $length, $num = '') {
	$temp_length = (mb_strlen($content)) + (mb_strlen($url));
	if ($num) {
		$temp_length = (wp_strlen($content)) + (wp_strlen($url));
	} 
	if ($temp_length > $length - 3) { // ...
		$chars = $length - 6 - mb_strlen($url);
		if ($num) {
			$chars = $length - 6 - wp_strlen($url);
		} 
		$content = mb_substr($content, 0, $chars, 'utf-8');
		$content = $content . "...";
	} 
	$status = $content . ' ' . $url;
	return trim($status);
}

function wp_in_array($a, $b) {
	$arrayA = explode(',', $a);
	$arrayB = explode(',', $b);
	foreach($arrayB as $val) {
		if (in_array($val, $arrayA))
			return true;
	} 
	return false;
}

if ($wptm_options['enable_wptm']) { // 是否开启微博同步功能
	add_action('publish_post', 'wp_to_mblog_publish', 0);
}

function wp_to_mblog_add_page() {
	add_options_page('WordPress微博同步', 'WordPress微博同步', 'manage_options', 'wp-to-mblog', 'wp_to_mblog_do_page');
} 

function wp_to_mblog_reauthorize() {
	if (!get_option('wptm_options')) {
		echo "<div class='update-nag'><center><p>您还没有对“WordPress微博同步”进行设置，<a href='options-general.php?page=wp-to-mblog'>现在去设置</a></p></center></div>";
	} 
}

function wp_to_mblog_publish($post_ID) {
	global $wptm_options;
	if (get_option('timezone_string')) {
		date_default_timezone_set(get_option('timezone_string'));
		$time = time();
	} elseif (get_option('gmt_offset')) {
		$time = time() + (get_option('gmt_offset') * 3600);
	} 
	$title = strip_tags(get_the_title($post_ID));
	$postlink = get_permalink($post_ID);
	$shortlink = get_bloginfo('url') . "/?p=" . $post_ID;
	$thePost = get_post($post_ID);
	$content = $thePost -> post_content;
	$excerpt = $thePost -> post_excerpt;
	$post_content = strip_tags($content);
	if ($wptm_options['cat_ids']) { // 不想同步的日志分类ID
		$cat_ids = $wptm_options['cat_ids'];
		$categories = get_the_category($post_ID);
		$sum = count($categories);
		for ($i = 0; $i < $sum ; $i++) {
			$cat_id .= $categories[$i] -> cat_ID . ',';
		} 
		if (wp_in_array($cat_ids, $cat_id)) {
			return;
		} 
	}
	if($excerpt) { // 判断是否有摘要
	   $post_content = strip_tags($excerpt);
	}
	$update_days = 60 * 60 * 24 * 1;
	$connect = " - ";
	preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches);
    $sum = count($matches[1]);
    if ($sum > 0) {
	    $pic = $matches[1][0];
    } 
	if ($wptm_options['update_days']) {
		$update_days = $wptm_options['update_days'] * 60 * 60 * 24;
	} 
	if (($thePost -> post_status == 'publish' || $_POST['publish'] == 'Publish') && ($_POST['prev_status'] == 'draft' || $_POST['original_post_status'] == 'draft' || $_POST['original_post_status'] == 'auto-draft' || $_POST['prev_status'] == 'pending' || $_POST['original_post_status'] == 'pending')) { // 判断是否为新发布
		$title = $wptm_options['new_prefix'] . $title;
	} else if ((($_POST['originalaction'] == "editpost") && (($_POST['prev_status'] == 'publish') || ($_POST['original_post_status'] == 'publish'))) && $thePost -> post_status == 'publish') { //判断是否已发布
		if (($time - strtotime($thePost -> post_date) < $update_days) || $wptm_options['update_days'] == 0 ) {
			return; //判断当前时间与日志发布时间差
		} 
		$title = $wptm_options['update_prefix'] . $title;
	} else if ( $_POST['_inline_edit'] ){ // 判断是否是快速编辑
	    $quicktime = $_POST['aa'] . '-' . $_POST['mm'] . '-' .$_POST['jj'] . ' ' .$_POST['hh'] . ':' .$_POST['mn'] . ':00';
	    $quicktime = strtotime($quicktime);
		if (($time - $quicktime < $update_days) || $wptm_options['update_days'] == 0) {
			return; //判断当前时间与日志发布时间差
		} 
		$title = $wptm_options['update_prefix'] . $title;
	} 
	if ($wptm_options['enable_shorten']) { // 是否使用博客默认短网址
		$postlink = $shortlink;
	} 
	if ($wptm_options['sync_option'] == 1) { // 同步日志内容
		$title = $post_content;
		$postlink = "";
		$connect = "";
	}
	if ($wptm_options['sync_option'] == 3) { // 同步日志内容 + 链接
		$title = $post_content;
	} 	
	if ($wptm_options['sync_option'] == 2) { // 同步 前缀+标题+摘要/内容+链接
		$title = $title . " - " . $post_content;
	} 

	wp_to_mblog_list($title, $postlink, $pic);
} 

function wp_to_mblog_header () {
	global $plugin_url;
	if (isset($_POST['add_twitter'])) {
		header('Location:' . $plugin_url. '/go.php?OAuth=TWITTER');
	}
	if (isset($_POST['add_qq'])) {
		header('Location:' . $plugin_url. '/go.php?OAuth=QQ');
	}
	if (isset($_POST['add_sina'])) {
		header('Location:' . $plugin_url. '/go.php?OAuth=SINA');
	}
	if (isset($_POST['add_netease'])) {
		header('Location:' . $plugin_url. '/go.php?OAuth=NETEASE');
	}
	if (isset($_POST['add_douban'])) {
		header('Location:' . $plugin_url. '/go.php?OAuth=DOUBAN');
	}
}

function wp_to_mblog_update() {
	$update_options = array(
		'enable_wptm' => trim($_POST['enable_wptm']),
		'enable_proxy' => trim($_POST['enable_proxy']),
		'custom_proxy' => trim($_POST['custom_proxy']),
		'sync_option' => trim($_POST['sync_option']),
		'new_prefix' => trim($_POST['new_prefix']),
		'update_prefix' => trim($_POST['update_prefix']),
		'update_days' => trim($_POST['update_days']),
		'cat_ids' => trim($_POST['cat_ids']),
		'page_password' => trim($_POST['page_password']),
		'enable_shorten' => trim($_POST['enable_shorten'])
		);
	$disable_username = (trim($_POST['disable_username'])) ? trim($_POST['disable_username']) : 'admin';
	$wptm_connect = array(
		'enable_connect' => trim($_POST['enable_connect']),
		'sina' => trim($_POST['sina']),
		'qq' => trim($_POST['qq']),
		'netease' => trim($_POST['netease']),
		'douban' => trim($_POST['douban']),
		'sina_username' => trim($_POST['sina_username']),
		'qq_username' => trim($_POST['qq_username']),
		'netease_username' => trim($_POST['netease_username']),
		'netease_avatar' => trim($_POST['netease_avatar']),
		'disable_username' => $disable_username
		);
	$update = array(
		'username' => trim($_POST['username']),
		'password' => trim($_POST['password'])
		);
	$updated = '<div class="updated"><p><strong>' . __('Settings saved.') . '</strong></p></div>';
	if (isset($_POST['update_options'])) {
		update_option("wptm_options", $update_options);
		echo $updated;
	} 
	if (isset($_POST['wptm_connect'])) {
		update_option("wptm_connect", $wptm_connect);
		echo $updated;
	} 
	if (isset($_POST['update_twitter'])) {
		update_option("wptm_twitter", $update);
		echo $updated;
	} 
	if (isset($_POST['update_sohu'])) {
		update_option("wptm_sohu", $update);
		echo $updated;
	}
	if (isset($_POST['update_digu'])) {
		update_option("wptm_digu", $update);
		echo $updated;
	} 
	if (isset($_POST['update_douban'])) {
		update_option("wptm_douban", $update);
		echo $updated;
	} 
	if (isset($_POST['update_renjian'])) {
		update_option("wptm_renjian", $update);
		echo $updated;
	} 
	if (isset($_POST['update_fanfou'])) {
		update_option("wptm_fanfou", $update);
		echo $updated;
	} 
	if (isset($_POST['update_zuosa'])) {
		update_option("wptm_zuosa", $update);
		echo $updated;
	} 
	//if (isset($_POST['update_ms9911'])) {
	//	update_option("wptm_9911", $update);
	//	echo $updated;
	//} 
	if (isset($_POST['update_follow5'])) {
		update_option("wptm_follow5", $update);
		echo $updated;
	}
	// delete
	if (isset($_POST['delete_twitter_oauth'])) {
		update_option("wptm_twitter_oauth", '');
	}
	if (isset($_POST['delete_qq_oauth'])) {
		update_option("wptm_qq", '');
	}
	if (isset($_POST['delete_sina_oauth'])) {
		update_option("wptm_sina", '');
	}
	if (isset($_POST['delete_netease_oauth'])) {
		update_option("wptm_netease", '');
	}
	if (isset($_POST['delete_douban_oauth'])) {
		update_option("wptm_douban", '');
	}
	if (isset($_POST['delete_twitter'])) {
		update_option("wptm_twitter", '');
	} 
	if (isset($_POST['delete_sohu'])) {
		update_option("wptm_sohu", '');
	}
	if (isset($_POST['delete_digu'])) {
		update_option("wptm_digu", '');
	} 
	if (isset($_POST['delete_douban'])) {
		update_option("wptm_douban", '');
	} 
	if (isset($_POST['delete_renjian'])) {
		update_option("wptm_renjian", '');
	} 
	if (isset($_POST['delete_fanfou'])) {
		update_option("wptm_fanfou", '');
	} 
	if (isset($_POST['delete_zuosa'])) {
		update_option("wptm_zuosa", '');
	}
	if (isset($_POST['delete_follow5'])) {
		update_option("wptm_follow5", '');
	}
}
function wp_to_mblog_do_page() {
	global $plugin_url;
	wp_to_mblog_update();
	$qq = get_option('wptm_qq');
	$sina = get_option('wptm_sina');
	$netease = get_option('wptm_netease');
	$twitter = get_option('wptm_twitter');
	$twitter_oauth = get_option('wptm_twitter_oauth');
	$sohu = get_option('wptm_sohu');
	$digu = get_option('wptm_digu');
	$douban = get_option('wptm_douban');
	$renjian = get_option('wptm_renjian');
	$fanfou = get_option('wptm_fanfou');
	$zuosa = get_option('wptm_zuosa');
	//$ms9911 = get_option('wptm_9911');
	$follow5 = get_option('wptm_follow5');
	$wptm_options = get_option('wptm_options');
	$wptm_connect = get_option('wptm_connect');
?>
<link rel="stylesheet" type="text/css" href="<?php echo $plugin_url;?>/style.css" />
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo $plugin_url;?>/floatdialog.js"></script>
<div class="wrap">
<h2>WordPress微博同步</h2>
<form method="post" action="<?php echo get_bloginfo('wpurl');?>/wp-admin/options-general.php?page=wp-to-mblog">
    <?php wp_nonce_field('update-options');?>
    <p><strong>基本设置</strong></p>
	<table class="form-table">
		<tr>
			<td width="25%" valign="top">是否开启“微博同步”功能</td>
			<td><input name="enable_wptm" type="checkbox" value="checkbox" <?php if($wptm_options['enable_wptm']) echo "checked='checked'"; ?>></td>
		</tr>
		<tr>
			<td width="25%" valign="top">Twitter是否使用代理？</td>
			<td><input name="enable_proxy" type="checkbox" value="checkbox" <?php if($wptm_options['enable_proxy']) echo "checked='checked'"; ?>> (国内主机用户必须勾选才能使用)</td>
		</tr>
		<tr>
			<td width="25%" valign="top">自定义代理API</td>
			<td><input name="custom_proxy" type="text" size="60" value="<?php echo $wptm_options['custom_proxy']; ?>" /><br />不填则默认为http://smyxapi.appspot.com/api/statuses/update.xml [ <a href="http://www.smyx.net/wp-to-mblog.html">使用说明</a> ]</td>
		</tr>
		<tr>
			<td width="25%" valign="top">同步内容设置</td>
			<td><input name="sync_option" type="radio" value="0" <?php if($wptm_options['sync_option'] == 0) echo "checked='checked'"; ?> />前缀+标题+链接 <input name="sync_option" type="radio" value="2" <?php if($wptm_options['sync_option'] == 2) echo "checked='checked'"; ?> />前缀+标题+摘要/内容+链接 <input name="sync_option" type="radio" value="1" <?php if($wptm_options['sync_option'] == 1) echo "checked='checked'"; ?> />日志摘要/内容 <input name="sync_option" type="radio" value="3" <?php if($wptm_options['sync_option'] == 3) echo "checked='checked'"; ?> />日志摘要/内容+链接</td>
		</tr>
		<tr>
			<td width="25%" valign="top">自定义消息</td>
			<td>发布新日志前缀：<input name="new_prefix" type="text" size="12" value="<?php echo $wptm_options['new_prefix']; ?>" /> 修改日志前缀：<input name="update_prefix" type="text" size="12" value="<?php echo $wptm_options['update_prefix']; ?>" /> 更新日志间隔：<input name="update_days" type="text" size="1" value="<?php echo $wptm_options['update_days']; ?>" onkeyup="value=value.replace(/[^\d]/g,'')" /> 天 [0=不更新]</td>
		</tr>
		<tr>
			<td width="25%" valign="top">禁止同步的日志分类ID</td>
			<td><input name="cat_ids" type="text" value="<?php echo $wptm_options['cat_ids']; ?>" /> 用半角逗号(,)隔开 (设置后该ID分类下的日志将不会同到微博)</td>
		</tr>
		<tr>
			<td width="25%" valign="top">自定义页面密码设置</td>
			<td><input name="page_password" type="password" value="<?php echo $wptm_options['page_password']; ?>" /></td>
		</tr>
		<tr>
			<td width="25%" valign="top">是否使用博客默认短网址</td>
			<td><input name="enable_shorten" type="checkbox"  value="checkbox" <?php if($wptm_options['enable_shorten']) echo "checked='checked'"; ?>> (形如：http://yourblog.com/?p=1)</td>
		</tr>
    </table>
<p class="submit">
<input type="submit" name="update_options" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>
</form>

<form method="post" action="<?php echo get_bloginfo('wpurl');?>/wp-admin/options-general.php?page=wp-to-mblog">
    <?php wp_nonce_field('wptm_connect');?>
    <p><strong>高级设置</strong></p>
	<table class="form-table">
		<tr>
			<td width="25%" valign="top">是否开启“微博登录”功能</td>
			<td><input name="enable_connect" type="checkbox" value="checkbox" <?php if($wptm_connect['enable_connect']) echo "checked='checked'"; ?>></td>
		</tr>
		<tr>
			<td width="25%" valign="top">添加按钮</td>
			<td><input name="sina" type="checkbox" value="checkbox" <?php if($wptm_connect['sina']) echo "checked='checked'"; ?> /><img src="<?php echo $plugin_url; ?>/images/btn_sina.png" />
			<input name="qq" type="checkbox" value="checkbox" <?php if($wptm_connect['qq']) echo "checked='checked'"; ?> /><img src="<?php echo $plugin_url; ?>/images/btn_qq.png" /> 
			<input name="netease" type="checkbox" value="checkbox" <?php if($wptm_connect['netease']) echo "checked='checked'"; ?> /><img src="<?php echo $plugin_url; ?>/images/btn_netease.jpg" /> 
			<input name="douban" type="checkbox" value="checkbox" <?php if($wptm_connect['douban']) echo "checked='checked'"; ?> /><img src="<?php echo $plugin_url; ?>/images/btn_douban.png" /></td>
		</tr>
		<tr>
			<td width="25%" valign="top">绑定微博帐号</td>
			<td>新浪微博昵称: <input name="sina_username" type="text" size="10" value='<?php echo $wptm_connect['sina_username'];?>' /> 腾讯微博帐号: <input name="qq_username" type="text" size="10" value='<?php echo $wptm_connect['qq_username'];?>' /> 网易微博昵称: <input name="netease_username" type="text" size="10" value='<?php echo $wptm_connect['netease_username'];?>' /> <br />(说明：有新的评论时将以 @微博帐号 的形式显示在您跟评论者相对应的微博上，仅对方勾选了同步评论到微博时才有效！注：腾讯微博帐号不是QQ号码)</td>
		</tr>
		<tr>
			<td width="25%" valign="top">网易微博评论者头像</td>
			<td><input name="netease_avatar" type="checkbox" value="checkbox" <?php if($wptm_connect['netease_avatar']) echo "checked='checked'"; ?>>已显示</td>
		</tr>
		<tr>
			<td width="25%" valign="top">禁止注册的用户名</td>
			<td><input name="disable_username" type="text" size="60" value='<?php echo $wptm_connect['disable_username'];?>' /> 用半角逗号(,)隔开</td>
		</tr>
    </table>
<p class="submit">
<input type="submit" name="wptm_connect" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>
</form>

<div id="tlist">
<p><strong>帐号绑定</strong></p>
<?php if($wptm_options['enable_proxy']) { ?>
<a href="javascript:;" id="twitter_porxy" class="twitter<?php echo ($twitter['password']) ? ' bind': '';?>" title="Twitter"><b></b></a>
<?php } else { ?>
<a href="javascript:;" id="<?php echo ($twitter_oauth['oauth_token']) ? 'bind_twitter' : 'twitter';?>" class="twitter" title="Twitter"><b></b></a>
<?php } ?>
<a href="javascript:;" id="<?php echo ($qq['oauth_token']) ? 'bind_qq' : 'qq';?>" class="qq" title="腾讯微博"><b></b></a>
<a href="javascript:;" id="<?php echo ($sina['oauth_token']) ? 'bind_sina' : 'sina';?>" class="sina" title="新浪微博"><b></b></a>
<a href="javascript:;" id="<?php echo ($netease['oauth_token']) ? 'bind_netease' : 'netease';?>" class="netease" title="网易微博"><b></b></a>
<a href="javascript:;" id="sohu" class="sohu<?php echo ($sohu['password']) ? ' bind': '';?>" title="搜狐微博"><b></b></a>
<a href="javascript:;" id="digu" class="digu<?php echo ($digu['password']) ? ' bind': '';?>" title="嘀咕"><b></b></a>
<a href="javascript:;" id="<?php echo ($douban['oauth_token']) ? 'bind_douban' : 'douban';?>" class="douban" title="豆瓣"><b></b></a>
<a href="javascript:;" id="fanfou" class="fanfou<?php echo ($fanfou['password']) ? ' bind': '';?>" title="饭否"><b></b></a>
<a href="javascript:;" id="renjian" class="renjian<?php echo ($renjian['password']) ? ' bind': '';?>" title="人间网"><b></b></a>
<a href="javascript:;" id="zuosa" class="zuosa<?php echo ($zuosa['password']) ? ' bind': '';?>" title="做啥"><b></b></a>
<a href="javascript:;" id="follow5" class="follow5<?php echo ($follow5['password']) ? ' bind': '';?>" title="Follow5"><b></b></a>
</div>

<div class="dialog" id="dialog"> <a href="javascript:void(0);" class="close">X</a>
<form method="post" action="<?php echo get_bloginfo('wpurl');?>/wp-admin/options-general.php?page=wp-to-mblog">
<?php wp_nonce_field('options');?>

<p align="center"><img src="<?php echo $plugin_url;?>/images/twitter.png" class="title_pic" /></p>
<table class="form-table">
<tr valign="top">
<th scope="row">用&nbsp;&nbsp;&nbsp;&nbsp;户 :</th>
<td><input type="text" class="username" id="username" name="username" value="" /></td>
</tr>
<tr valign="top">
<th scope="row">密&nbsp;&nbsp;&nbsp;&nbsp;码 :</th>
<td><input type="password" class="password" id="password" name="password" value="" /></td>
</tr>
</table>
<p class="submit">
<input type="submit" name="update" id="update" class="button-primary" value="<?php _e('Save Changes') ?>" />
<input type="submit" name="delete" id="delete" class="button-primary" value="解除绑定" style="display: none;" />
</p>
</form>
</div>

<div class="dialog_add" id="dialog_add"> <a href="javascript:void(0);" class="close">X</a>
<form method="post" action="<?php echo get_bloginfo('wpurl');?>/wp-admin/options-general.php?page=wp-to-mblog">
<?php wp_nonce_field('add');?>
  <h2>提示！</h2>
  <p> 您还没有绑定同步授权，是否<b>绑定</b>？ </p>
  <p>
    <input type="submit" class="button-primary add" name="add" value="是" /> 
	<input type="button" class="button-primary close" value="否" /> 
  </p>
</form>
</div>

<div class="dialog_delete" id="dialog_delete"> <a href="javascript:void(0);" class="close">X</a>
<form method="post" action="<?php echo get_bloginfo('wpurl');?>/wp-admin/options-general.php?page=wp-to-mblog">
<?php wp_nonce_field('delete');?>
  <h2>提示！</h2>
  <p> 您已经绑定了同步授权，是否<b>解除</b>？ </p>
  <p>
    <input type="submit" class="button-primary delete" name="delete" value="是" onclick="if(confirm('Are you sure？')) return true;else return false; " /> 
	<input type="button" class="button-primary close" value="否" /> 
  </p>
</form>
</div>

</div>
<script type="text/javascript">
$(".close").show();
$("#twitter_porxy, #sohu, #digu, #fanfou, #renjian, #zuosa, #ms9911, #follow5").click(function () {
  var id = $(this).attr("id").replace('_porxy', '');
  $(".title_pic").attr("src", "<?php echo $plugin_url;?>/images/" + id + ".png");
  $('input[name="username"]').attr("id", "username_" + id);
  $('input[name="password"]').attr("id", "password_" + id);
  $("#username_twitter").attr("value", "<?php echo $twitter['username'];?>");
  $("#username_sohu").attr("value", "<?php echo $sohu['username'];?>");
  $("#username_digu").attr("value", "<?php echo $digu['username'];?>");
  $("#username_fanfou").attr("value", "<?php echo $fanfou['username'];?>");
  $("#username_renjian").attr("value", "<?php echo $renjian['username'];?>");
  //$("#username_ms9911").attr("value", "<?php echo $ms9911['username'];?>");
  $("#username_zuosa").attr("value", "<?php echo $zuosa['username'];?>");
  $("#username_follow5").attr("value", "<?php echo $follow5['username'];?>");
  $('#update').attr("name", 'update_' + id);
  $('#delete').attr("name", 'delete_' + id);
  $(".dialog").attr("id", "dialog_" + id);
  $("#delete").hide();
});
$(".bind").click(function () {
  $("#delete").show();
});
$("#twitter, #qq, #sina, #netease, #douban").click(function () {
  var id = $(this).attr("id");
  $(".dialog_add").attr("id", "dialog_" + id);
  $(".add").attr("name", "add_" + id);
});
$("#bind_twitter, #bind_qq, #bind_sina, #bind_netease, #bind_douban").click(function () {
  var id = $(this).attr("id").replace('bind_', '');
  $(".dialog_delete").attr("id", "dialog_" + id);
  $(".delete").attr("name", "delete_" + id + "_oauth");
});
$("#demo").floatdialog("dialog");
$("#demo_add").floatdialog("dialog_add");
$("#demo_delete").floatdialog("dialog_delete");
$("#twitter, #bind_twitter, #twitter_porxy").floatdialog("dialog_twitter");
$("#qq, #bind_qq").floatdialog("dialog_qq");
$("#sina, #bind_sina").floatdialog("dialog_sina");
$("#netease, #bind_netease").floatdialog("dialog_netease");
$("#douban, #bind_douban").floatdialog("dialog_douban");
$("#sohu").floatdialog("dialog_sohu");
$("#digu").floatdialog("dialog_digu");
$("#fanfou").floatdialog("dialog_fanfou");
$("#renjian").floatdialog("dialog_renjian");
$("#zuosa").floatdialog("dialog_zuosa");
//$("#ms9911").floatdialog("dialog_ms9911");
$("#follow5").floatdialog("dialog_follow5");
$('#update').click(function () {
  if ($(".username").val() == '') {
    alert("请输入用户名!  ");
    return false;
  }
  if ($(".password").val() == '') {
    alert("请输入密码!  ");
    return false;
  }
});
$('.wrap').click(function () {
   $('.updated').slideUp("normal");
});
</script>
<?php
}