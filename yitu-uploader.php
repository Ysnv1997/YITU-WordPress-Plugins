<?php
/*
Plugin Name: 益图图床外链小工具
Plugin URI: http://ishanran.com/yitu/
Description: 山然小站独自开发的图床插件，用于WordPress博客添加 图床上传小工具、评论处图片上传按钮、文章编辑处图片上传按钮。
Author: 山然小站
Author URI: http://ishanran.com/
Version: 1.0
*/
add_filter('use_block_editor_for_post', '__return_false');
define('YITU_URL', plugin_dir_url(__FILE__));
define('YITU_VERSION', "1.0");
define('VERSION_CHECK_URL', "http://cdn.ishanran.com/yitu-update.json");

include("YITU-UPLOADER-COMMENTS.php");
function qcgzxw_scripts_css()
{
	wp_deregister_script('jquery');
	wp_register_script('jquery', YITU_URL . 'js/jquery.min.js', YITU_VERSION);
	wp_enqueue_script('jquery');
	if (is_single() || is_page() || is_home()) wp_enqueue_style('bootstrap', YITU_URL . 'css/bootstrap.min.css', array(), YITU_VERSION);
}

$Uploader = get_option('YITU_DATA');
echo "<script> var infoData={'Token':'" . $Uploader['Token'] . "'}</script>";
function admin_scripts_css()
{
	wp_enqueue_script('admin-content-js', YITU_URL . 'js/content.min.js', array(), YITU_VERSION, true);
	wp_enqueue_style('admin-content-css', YITU_URL . 'css/input.min.css', array(), YITU_VERSION);
}
add_action('wp_enqueue_scripts', 'qcgzxw_scripts_css');
//功能启用
if ($Uploader['Content']) {
	add_action('admin_head', 'admin_scripts_css');
	add_action('media_buttons', 'admin_upload_img');
}

//END
//插件更新检测
function update()
{
	$response = wp_remote_get(VERSION_CHECK_URL);
	if (is_array($response) && !is_wp_error($response) && $response['response']['code'] == '200') {
		$body = json_decode($response['body']);
	}
	return $body;
}
//添加链接
function YITU_UPLOADER_LINKS($actions, $plugin_file)
{
	static $plugin;
	if (!isset($plugin))
		$plugin = plugin_basename(__FILE__);
	if ($plugin == $plugin_file) {
		$settings	= array('settings' => '<a href="options-general.php?page=YITU-UPLOADER-OPTIONS">插件设置</a>');
		$site_link	= array('support' => '<a href="http://ishanran.com/yitu/" target="_blank">使用说明</a>');
		$actions 	= array_merge($settings, $actions);
		$actions	= array_merge($site_link, $actions);
	}
	return $actions;
}
add_filter('plugin_action_links', 'YITU_UPLOADER_LINKS', 10, 2);
//默认数据
add_action('admin_init', 'YITU_options_default_options');
function YITU_options_default_options()
{
	$Uploader = get_option('YITU_DATA'); //获取选项
	if ($Uploader == '') {
		$Uploader = array( //设置默认数据
			'Content' => '',
			'Donate' => '',
			'Token' => '',
		);
		update_option('YITU_DATA', $Uploader); //更新选项   
	}
}

//设置菜单
function my_plugin_menu()
{
	add_options_page('益图设置页面', '益图设置', 'manage_options', 'YITU-UPLOADER-OPTIONS', 'my_plugin_options');
}
add_action('admin_menu', 'my_plugin_menu');
function my_plugin_options()
{
	if (isset($_POST['Update'])) {
		$date = update();
		$ver = $date->ver;
		if ($ver > YITU_VERSION) {
			$url = $date->url;
			$content = $date->content;

			echo '<div class="notice notice-warning"><p>益图 插件已经有新版本啦！ <a target="_blank" href="' . $url . '">立即下载</a></p><p><strong>更新内容：</strong>' . $content . '</p></div>';
		} else {
			echo '<div class="updated" id="message"><p>暂无更新</p></div>';
		}
	}
	if (isset($_POST['DataSubmit'])) {
		$Uploader = array(
			'Content' => trim(@$_POST['content']),
			'Token' => trim(@$_POST['Token']),
		);
		@update_option('YITU_DATA', $Uploader);
		add_action('widgets_init', 'qcgzxw');
		echo '<div class="updated" id="message"><p>提交成功</p></div>';
	} else {
		if (!isset($_POST['Update'])) {
			echo '<div class="updated subscribe-main" id="message"><p>创作不易，给GitHub点个Star吧。——<span class="text-ruo">[<a href="https://github.com/Ysnv1997/YITU-WordPress-Plugins" target="_blank">立即前往</a>]</span><i class="fr fb f20 qcgzxw-close">&#215;</i></p>';
			echo "</div><style>.fb{font-weight:bold;}.f12{font-size:12px;}..f16{font-size:16px;}.f18{font-size:18px;}..fl{float:left;}.fr{float:right;margin-top:-2px;}.oh{overflow:hidden;}i{font-style:normal;}.color-primary{color:#337ab7;}.color-success{color:#5cb85c;}.color-info{color:#5bc0de;}.color-warning{color:#f0ad4e;}.color-red{color:red;padding:0 3px;}</style><script>jQuery('.qcgzxw-close').click(function() {jQuery('.subscribe-main').fadeOut('slow',function(){jQuery('.subscribe-main').remove();});});</script>";
		}
	}
	$Uploader = get_option('YITU_DATA');
	$Content	= $Uploader['Content']	!== '' ? 'checked="checked"' : '';
	$Token	= $Uploader['Token'];

	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}
	echo '<div class="wrap">';
	echo '<h2>益图 插件设置</h2>';
	echo '<p>&nbsp;&nbsp;益图是一款为WordPress添加上传图片小工具以及评论处图片上传按钮的插件！</p>';
	echo '<form method = "post">';
	echo '<table class = "form-table">';
	echo '<tbody>';

	echo '<tr valign="top">';
	echo '<th scope="row">Token</th>';
	echo '<td><label><input value = "' . $Token . '" type = "input" name = "Token">  <a href="https://htm.fun/user" target="target">点击获取Token</a></label></td>';
	echo '</tr>';

	echo '<tr valign="top">';
	echo '<th scope="row">后台文章编辑启用图片上传</th>';
	echo '<td><label><input value = "true" type = "checkbox" name = "content" ' . $Content . '>  勾选后在后台文章编辑处自动添加图片上传按钮</label></td>';
	echo '</tr>';



	echo '</tbody>';
	echo '</table>';
	echo '<p class = "submit">';
	echo '<input class = "button button-primary" type = "submit" name = "DataSubmit" id = "submit" value = "保存更改" />&nbsp;&nbsp;&nbsp;&nbsp;';
	echo '<input class = "button" type = "submit" name = "Update" id = "update" value = "检测更新" />';
	echo '</p>';

	echo '</table>';
	echo '</div>';
}
