<?php
function wp_to_microblog() {
$wptm_options = get_option('wptm_options');
$plugin_url = WP_PLUGIN_URL . '/wp-to-mblog';
$password = $_POST['password'];
if (isset($_POST['message'])) {
	if ((is_user_logged_in()) || ($wptm_options['page_password'] && $password == $wptm_options['page_password'])) {
		include_once( dirname(__FILE__) . '/config.php' );
		require_once( dirname(__FILE__) . '/OAuth/OAuth.php' );
		$status = mb_substr($_POST['message'], 0, 140, 'utf-8');
		if (isset($_POST['pic'])) {
			$pic = $_POST['pic'];
		}
		if (isset($_POST['twitter'])) {
			wp_to_mblog_twitter($status);
		} 
		if (isset($_POST['qq'])) {
			wp_to_mblog_t_qq($status);
		} 
		if (isset($_POST['sina'])) {
			wp_to_mblog_t_sina($status, $pic);
		} 
		if (isset($_POST['netease'])) {
			wp_to_mblog_t_163($status, $pic);
		} 
		if (isset($_POST['sohu'])) {
			wp_to_mblog_t_sohu($status);
		} 
		if (isset($_POST['digu'])) {
			wp_to_mblog_digu($status);
		}  
		if (isset($_POST['douban'])) {
			wp_to_mblog_douban($status);
		} 
		if (isset($_POST['fanfou'])) {
			wp_to_mblog_fanfou($status);
		} 
		if (isset($_POST['renjian'])) {
			wp_to_mblog_renjian($status);
		} 
		if (isset($_POST['zuosa'])) {
			wp_to_mblog_zuosa($status);
		} 
		if (isset($_POST['follow5'])) {
			wp_to_mblog_follow5($status);
		}
	} else {
		$error = '<span style="color:#690">密码错误！</span>';
		$message = $_POST['message'];
	} 
} 
$html = '
<link type="text/css" href="'.$plugin_url.'/page.css" rel="stylesheet" />
<script type="text/javascript">
function textCounter(field,maxlimit){if(field.value.length>maxlimit){field.value=field.value.substring(0,maxlimit)}else{document.getElementById("wordage").childNodes[1].innerHTML=maxlimit-field.value.length}}function isok(theform){if(tform.message.value==""){alert("发布的内容不能为空！");tform.message.focus();return(false)}if(tform.password.value==""){alert("请输入正确的密码！");tform.password.focus();return(false)}return(true)}
</script>
<form action="" method="post" name="tform" id="tform" onSubmit="return isok(this)">
  <fieldset>
    <div id="say">说说你的新鲜事
      <div id="wordage">你还可以输入 <span>140</span> 字</div>
    </div>
    <textarea cols="60" rows="5" name="message" id="message" onblur="textCounter(this.form.message,140);" onKeyDown="textCounter(this.form.message,140);" onKeyUp="textCounter(this.form.message,140);">'.$message.'</textarea>
    <p>图片地址：</p>
    <input name="pic" id="pic" size="50" type="text" />（仅支持新浪、网易微博）
    <p>发布到：</p>
    <input name="twitter" id="twitter" type="checkbox" value="checkbox" checked />
    <label for="twitter">Twitter</label>
    <input name="qq" id="qq" type="checkbox" value="checkbox" checked />
    <label for="qq">腾讯微博</label>
    <input name="sina" id="sina" type="checkbox" value="checkbox" checked />
    <label for="sina">新浪微博</label>
    <input name="netease" id="netease" type="checkbox" value="checkbox" checked />
    <label for="netease">网易微博</label>
    <input name="sohu" id="sohu" type="checkbox" value="checkbox" checked />
    <label for="sohu">搜狐微博</label>
    <input name="digu" id="digu" type="checkbox" value="checkbox" checked />
    <label for="digu">嘀咕</label>
    <input name="douban" id="douban" type="checkbox" value="checkbox" checked />
    <label for="douban">豆瓣</label>
    <input name="fanfou" id="fanfou" type="checkbox" value="checkbox" checked />
    <label for="fanfou">饭否</label>
    <input name="renjian" id="renjian" type="checkbox" value="checkbox" checked />
    <label for="renjian">人间网</label>
    <input name="zuosa" id="zuosa" type="checkbox" value="checkbox" checked />
    <label for="zuosa">做啥</label>
    <input name="follow5" id="follow5" type="checkbox" value="checkbox" checked />
    <label for="follow5">Follow5</label>';
if(!is_user_logged_in()) {
$html .='
    <p>
    <label for="password">密码：</label>
    <input name="password" id="password" type="password" value="'.$password.'" /> '.$error.'
	</p>';
}
$html .= '
    <p><input type="submit" id="submit" value="发表" /></p>
  </fieldset>
</form>';
echo $html;
}
add_shortcode('wp_to_microblog', 'wp_to_microblog'); //简码
?>