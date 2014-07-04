<?php
/*
 * Function: empty_allow_zero
* Description: Mimics the empty() function but allows the value 0.
* 		By design, the empty() function will count 0 as being
* 		empty. There are cases where we do not want this so
* 		for those we use this function.
* Parameters:
* 	$value (multi): A string, boolean, array, etc that we want to test
*
*/
function empty_allow_zero($value = ''){
	return (empty($value) && '0' != $value ? true : false);
}

/**
 * Check if LE page is protected with DAP
 *
 * If page's post content is not empty and its ID is 0 then redirect the user to page specified in dashboard settings.
 * DAP hijacks global $post and adds to $post->post_content its "members only message" as well as login form.
 *
 * @author Luka Peharda <luka.peharda@gmail.com>
 * @since 2.0.8.
 * @param  string $template
 * @return mixed
 */
function dap_allowed_page($template)
{
	global $post;

	if (!empty($post->post_content) && $post->ID == 0) {
		if ('' !== $pageRedirectUrl = get_post_meta(get_queried_object_id(), 'dap_redirect_url', true)) {
			wp_redirect($pageRedirectUrl, 302);
		} else if (false === $redirectUrl = op_get_option('dap_redirect_url')) {
			wp_redirect(home_url(), 302);
		} else {
			wp_redirect($redirectUrl, 302);
		}
		exit();
	}

	return $template;
}

/**
 * Check if LE page is protected with Fast Member
 *
 * If page's post content is not empty and its ID is 0 then redirect the user to page specified in dashboard settings.
 * FM hijacks global $post and adds to $post->post_content its "members only message" as well as login form.
 *
 * @author Luka Peharda <luka.peharda@gmail.com>
 * @since 2.1.0.
 * @param  string $template
 * @return mixed
 */
function fast_member_allowed_page($template)
{
	global $post;
	$originalPost = get_queried_object();
	if (!empty($post->post_content) && $post->post_content !== $originalPost->post_content) {
		if ('' !== $pageRedirectUrl = get_post_meta($originalPost->ID, 'fast_member_redirect_url', true)) {
			wp_redirect($pageRedirectUrl, 302);
		} else if (false === $redirectUrl = op_get_option('fast_member_redirect_url')) {
			wp_redirect(home_url(), 302);
		} else {
			wp_redirect($redirectUrl, 302);
		}
		exit();
	}

	return $template;
}

/**
 * Check if LE page is protected with iMember360
 *
 * If page is created with Live Editor and is_404() func returns true (and ofcourse, iMember is active)
 * we redirect user to defined page.
 *
 * @author Luka Peharda <luka.peharda@gmail.com>
 * @since 2.1.0.
 * @param  string $template
 * @return mixed
 */
function imember_allowed_page($template)
{
	global $post;
	if (true === is_404()) {
		if ('' !== $pageRedirectUrl = get_post_meta(get_queried_object_id(), 'imember_redirect_url', true)) {
			wp_redirect($pageRedirectUrl, 302);
		} else if (false === $redirectUrl = op_get_option('imember_redirect_url')) {
			wp_redirect(home_url(), 302);
		} else {
			wp_redirect($redirectUrl, 302);
		}
		exit();
	}

	return $template;
}

function op_check_optin_form(){
	if(isset($_POST['op_optin_form']) && $_POST['op_optin_form'] == 'Y'){
		$message = '';
		$data = unserialize(base64_decode($_POST['op_optin_form_data']));
		foreach($data['fields'] as $field){
			$val = isset($_POST[$field['name']]) ? $_POST[$field['name']] : '';
			$message .= $field['text'].': '.$val."\n";
		}
		$message .= "\n";
		foreach($data['extra_fields'] as $name => $text){
			$val = isset($_POST[$name]) ? $_POST[$name] : '';
			$message .= $text.': '.$val."\n";
		}
		$email = op_post('email');
		$webinar = op_post('gotowebinar');
		/*
		 * Triggering GoToWebinar
		 */
		if (false !== $webinar) {
			processGoToWebinar($webinar, $email);
		}
		wp_mail($data['email_to'], sprintf(__('Optin Form Submission - %s', OP_SN), op_current_url()),$message);
		if($data['redirect_url'] != ''){
			wp_redirect($data['redirect_url']);
			exit;
		} else {
			$GLOBALS['op_optin_form_sent'] = true;
		}
	}
}

/**
 * Processes GoToWebinar interception request
 * @author Luka Peharda <luka.peharda@gmail.com>
 * @param  string $webinar
 * @param  email $email
 * @return void
 */
function processGoToWebinar($webinar, $email)
{
	$firstName = $lastName = '';

	foreach ($_POST as $key => $value) {
		$key = strtolower($key);
		if (in_array($key, array('firstname', 'first_name', 'first-name', 'fname', 'first', 'name', 'inf_field_firstname'))) {
			$firstName = $value;
			continue;
		}
		if (in_array($key, array('lastname', 'last_name', 'last-name', 'lname', 'last', 'name', 'inf_field_lastname'))) {
			$lastName = $value;
		}
	}

	require_once(OP_MOD . 'email/ProviderFactory.php');
	$provider = OptimizePress_Modules_Email_ProviderFactory::getFactory('gotowebinar', true);
	if ($provider->isEnabled()) {
		$data = $provider->subscribe(array(
			'list' => $webinar,
			'email' => $email,
			'firstName' => empty($firstName) ? 'Friend' : $firstName,
			'lastName' => empty($lastName) ? '.' :  $lastName
		));
	}
}

add_action('op_pre_template_include','op_check_optin_form');

function op_current_url(){
	return 'http'.(is_ssl()?'s':'').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
}
function op_optin_box($options,$values,$output,$tpl='',$wrap_elems=array()){
	static $global_imgs;
	if(!isset($global_imgs)){
		$global_imgs = op_page_img('',true,'global');
	}
	$disable_name = isset($options['disable_name']);
	$content_fields = op_get_var($options,'content_fields',array());
	$ignore = array();
	if(isset($options['ignore_fields'])){
		$ignore = is_array($options['ignore_fields'])?$options['ignore_fields']:array_filter(explode('|',$options['ignore_fields']));
	}
	if(!isset($values['content']) || count($values['content']) == 0){
		$content = op_get_var($options,'content_fields',array());
		$values['content'] = array();
		foreach($content as $name => $field){
			$values['content'][$name] = op_get_var($field,'default');
		}
	}
	if(!isset($values['form_open']) || !isset($values['form_close'])){
		$values['form_open'] = '<form action="#" class="op-optin-validation">';
		$values['form_close'] = '</form>';
		$values['hidden_elems'] = '';
		$values['email_input'] = '<input type="email" required="required" name="email" placeholder="'.$values['content']['email_default'].'" class="email" />';
		if(!$disable_name){
			$values['name_input'] = '<input type="text" name="name" required="required" placeholder="'.$values['content']['name_default'].'" class="name" />';
		}
	}
	$content = op_get_var($values,'content',array());
	$fields = array(
		'title' => '<h2>%s</h2>',
		'form_header' => '<p>%s</p>',
		'footer_note' => '<p class="secure-icon"><img src="'.$global_imgs.'secure.png" alt="secure" width="16" height="15"> %s</p>',
	);
	$btn_config = op_get_var($options,'submit_button_config',array());
	$vars = array(
		'form_open' => op_get_var($values,'form_open'),
		'form_close' => op_get_var($values,'form_close'),
		'hidden_elems' => op_get_var($values,'hidden_elems'),
		'name_input' => (!$disable_name ? op_get_var($values,'name_input') : ''),
		'email_input' => op_get_var($values,'email_input'),
		'submit_button' => op_mod('submit_button')->output(array('submit_button'),$btn_config,$values['submit_button'],true),
		'extra_fields' => ''
	);
	if(isset($values['extra_fields']) && is_array($values['extra_fields'])){
		$vars['extra_fields'] = implode('',$values['extra_fields']);
	}
	foreach($content_fields as $name => $settings){
		if(!isset($content[$name])){
			$value = op_get_var($settings,'default');
		} else {
			$value = $content[$name];
		}
		$wrap = '';
		if(isset($wrap_elems[$name])){
			$wrap = $wrap_elems[$name];
		} elseif(isset($fields[$name])){
			$wrap = $fields[$name];
		}
		$vars[$name] = $wrap == '' ? $value : sprintf($wrap,$value);
	}
	if($tpl != ''){
		$output = $tpl;
	} else {
		$output = '
	<div class="op_signup_form">
		{title}
		{form_header}
		{form_open}
		<div>
			{hidden_elems}
			{name_input}
			{email_input}
			{extra_fields}
			{submit_button}
		</div>
		{footer_note}
		{form_close}
	</div>';
	}
	$out = op_convert_template($output,$vars);

	return $out;
}
function op_convert_template($tpl,$output){
	$keys = array_map('op_wrap_tpl_key',array_keys($output));
	return str_replace($keys,$output,$tpl);
}
function op_wrap_tpl_key($el){
	return '{'.$el.'}';
}
function op_texturize($content){
	return shortcode_unautop(wpautop(wptexturize($content)));
}
function op_clean_shortcode_content($str){
	if(substr($str,0,4) == '</p>'){
		$str = substr($str,4);
	}
	if(substr($str,strlen($str)-3) == '<p>'){
		$str = substr($str,0,-3);
	}
	return $str;
}
function op_get_column_width($column){
	static $layout;
	static $has_cols = true;
	if($has_cols && !isset($layout)){
		if($layouts = op_theme_config('layouts')){
			$tmp_layout = $layouts['layouts'][op_get_current_item($layouts['layouts'],op_get_option('column_layout','option'))];
			if(isset($tmp_layout['widths'])){
				$layout = $tmp_layout['widths'];
			} else {
				$has_cols = false;
			}
		} else {
			$has_cols = false;
		}
	}
	if($has_cols){
		$col = op_get_var($layout,$column);
		if($conf = op_get_option('column_layout','widths',$column)){
			$conf = intval($conf);
			if((isset($col['min']) && $conf < $col['min']) || (isset($col['max']) && $conf > $col['max'])){
				return $col['width'];
			} else {
				return $conf;
			}
		}
		return $col['width'];
	}
}
function op_post(){
	$args = func_get_args();
	return _op_traverse_array($_POST,$args);
}
function op_get(){
	$args = func_get_args();
	return _op_traverse_array($_GET,$args);
}
function _op_traverse_array($array,$args){
	if(count($args) == 0){
		return $array;
	} else {
		$found = true;
		for($i=0,$al=count($args);$i<$al;$i++){
            /// fixing notice if $args[$i] is not set, I don't know what I am doing (Zvonko)
            // this was manifested in Dashboard
            if (!isset($args[$i])) continue;
			if(is_array($args[$i])){
				if(!$array = _op_traverse_array($array,$args[$i])){
					$found = false;
					break;
				}
			} else {
				if(isset($array[$args[$i]])){
					$array = $array[$args[$i]];
				} else {
					$found = false;
					break;
				}
			}
		}
		return $found ? $array : false;
	}
}
function op_truncate($title,$length=33,$more_text='&hellip;'){
	if(strlen($title) > $length){
		$parts = explode(' ',$title);
		$plength = count($parts);
		$title = '';
		$i = 0;
		while(strlen($title) < $length && $i < $plength){
			if(strlen($parts[$i]) + strlen($title) > $length){
				return $title.$more_text;
			} else {
				$title .= ' '.$parts[$i];
				$i++;
			}
		}
		return $title.$more_text;
	} else {
		return $title;
	}
}
function op_section_config($section){
	static $module_list;
	if(!isset($module_list)){
		if(defined('OP_PAGEBUILDER_ID')){
			require_once OP_LIB.'sections/page/functionality.php';
			$module_list = OptimizePress_Sections_Functionality::sections();
		} else {
			require_once OP_LIB.'sections/blog/modules.php';
			$module_list = OptimizePress_Sections_Modules::sections();
		}
	}
	if(isset($module_list[$section])){
		return $module_list[$section];
	}
	return false;
}
function op_get_current_item($array,$current_val){
	if(!is_array($array) || count($array) == 0){
		return false;
	}
	$cur = $current_val == '' ? key($array) : $current_val;
	return isset($array[$cur]) ? $cur : key($array);
}
function op_attr($str,$echo=false){
	if (strtolower(gettype($str))=='array'){
		foreach($str as $key=>$item){
			$str[$key] = htmlspecialchars($item, ENT_QUOTES);
			$str[$key] = str_replace(array("'",'"'),array('&#39;','&quot;'),$item);
		}
	} else {
		$str = htmlspecialchars($str, ENT_QUOTES);
		$str = str_replace(array("'",'"'),array('&#39;','&quot;'),$str);
		if($echo){
			echo $str;
		}
	}
	return $str;
}
function op_search_form(){
	if(op_check_include_tpl(array('searchform')) === false){
		get_search_form();
	}
}
function op_post_meta(){
	$cn = get_comments_number();
	$comments = sprintf(_n('1 Comment','%1$s Comments',$cn,OP_SN), number_format_i18n( $cn ));
	$args = array(__('<p class="post-meta"><a href="%1$s" title="%2$s" rel="author">%3$s</a><a href="%4$s">%5$s</a></p>', OP_SN ),
		esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
		sprintf( esc_attr__( 'View all posts by %s', OP_SN), get_the_author() ),
		esc_html( get_the_author() ),
		esc_url( get_comments_link() ),
		$comments
	);
	$args = apply_filters('op_post_meta',$args);
	call_user_func_array('printf',$args);
}
function op_get_enabled($array){
	$enabled = op_get_var($array,'enabled','N');
	return op_check_enabled($enabled);
}
function op_check_enabled($val){
	return ($val == 'N' || $val == 'Y' ? $val : 'N');
}
function op_img($file='',$return=false){
	$url = OP_IMG.$file;
	if($return){
		return $url;
	}
	echo $url;
}
function op_theme_img($file='',$return=false){
	$url = OP_THEME_URL.'images/'.$file;
	if($return){
		return $url;
	}
	echo $url;
}
function op_page_img($file='',$return=false,$folder=null){
	$path = is_null($folder) ? OP_PAGE_URL.'images/' : OP_PAGES_URL.$folder.'/images/';
	$url = $path.$file;
	if($return){
		return $url;
	}
	echo $url;
}
function op_get_var($array,$key,$default='',$wrap='',$force=false){
	$val = isset($array[$key]) ? $array[$key] : $default;

	$run = true;
	if(!$force && $val == ''){
		$run = false;
	}
	if($wrap != '' && $run){
		$val = sprintf($wrap,$val);
	}
	return $val;
}
function op_get_var_e($array,$key,$default='',$wrap='',$force=false){
	echo op_get_var($array,$key,$default,$wrap,$force);
}
function op_sidebar($name=null,$force=false){
	if(is_null($name) && (defined('OP_SIDEBAR') && OP_SIDEBAR === false) && $force !== true){
		return;
	}
	$templates = array();
	if ( isset($name) )
		$templates[] = "sidebar-{$name}";
	$templates[] = 'sidebar';
	op_check_include_tpl($templates);
}
function op_check_include_tpl($templates=array(),$return=false){
	$file = '';
	foreach($templates as $template){
		if(file_exists(OP_THEME_DIR.$template.'.php')){
			$file = $template;
			break;
		}
	}
	if(!empty($file)){
		if($return){
			return $file;
		} else {
			return op_theme_file($template);
		}
	}
	return false;
}
function op_init_theme($load_modules=true){
	op_theme_file('functions');
	$tpl_dir = op_get_option('theme','dir');
	if($tpl_dir){
		define('OP_THEME_DIR', OP_THEMES.$tpl_dir.'/');
		define('OP_THEME_URL', OP_URL.'themes/'.$tpl_dir.'/');
	}
	if($load_modules){
		$modules = op_theme_config('modules');
		$modules = is_array($modules) ? $modules : array();
		foreach($modules as $mod){
			op_mod($mod);
		}
	}
	do_action('op_init_theme');
}
function op_init_page($id){
	global $wp_query;
	define('OP_PAGEBUILDER',true);
	define('OP_PAGEBUILDER_ID',$id);
	do_action('op_pre_init_page');
	require_once OP_ASSETS.'live_editor.php';
	wp_enqueue_script('jquery', false, false, OP_VERSION);

	//If jQuery version is higher than 1.9 we require jQuery migrate plugin (which is by default registered in WP versions that come with jQuery 1.9 or higher)
	if (wp_script_is('jquery-migrate', 'registered')) {
		wp_enqueue_script(OP_SN.'-noconflict-js', OP_JS.'jquery/jquery.noconflict'.OP_SCRIPT_DEBUG.'.js', array('jquery', 'jquery-migrate'), OP_VERSION);
	} else {
		wp_enqueue_script(OP_SN.'-noconflict-js', OP_JS.'jquery/jquery.noconflict'.OP_SCRIPT_DEBUG.'.js', array('jquery'), OP_VERSION);
	}
	wp_enqueue_script(OP_SN.'-loadScript', OP_JS.'jquery/jquery.loadScript'.OP_SCRIPT_DEBUG.'.js', array(OP_SN.'-noconflict-js'), OP_VERSION);
	op_init_page_theme();
	if(have_posts()){
		the_post();
	}
	$GLOBALS['op_content_layout'] = op_page_layout('body',false,'content_area','');
	$GLOBALS['op_footer_layout'] = '';
	if(op_page_option('footer_area','enabled') == 'Y' && op_page_option('footer_area','large_footer','enabled') == 'Y'){
		$GLOBALS['op_footer_layout'] = op_page_layout('footer',false,'footer_area');
	}
	do_action('op_after_init_page');
}
function op_init_page_theme($load_modules=true){
	require_once OP_FUNC.'page.php';
	op_page_file('functions');
	op_page_file('functions',array(),OP_PAGES.'global/');
	$tpl_type = op_page_option('theme','type');
	$tpl_dir = op_page_option('theme','dir');
	if($tpl_dir){
		define('OP_PAGE_DIR', OP_PAGES.$tpl_type.'/'.$tpl_dir.'/');
		define('OP_PAGE_DIR_REL', '/pages/'.$tpl_type.'/'.$tpl_dir.'/');
		define('OP_PAGE_URL', OP_URL.'pages/'.$tpl_type.'/'.$tpl_dir.'/');
		require_once OP_FUNC.'feature_area.php';
		$class = 'OptimizePress_Page_Feature_Area';
		if(file_exists(OP_PAGE_DIR.'feature_area.php')){
			require_once OP_PAGE_DIR.'feature_area.php';
		} elseif(file_exists(OP_PAGES.'global/feature_areas/'.$tpl_type.'.php')){
			require_once OP_PAGES.'global/feature_areas/'.$tpl_type.'.php';
		} else {
			$class = 'OptimizePress_Page_Feature_Area_Base';
		}
		$GLOBALS['op_feature_area'] = new $class();
	}
	if($load_modules){
		if(!(op_page_config('disable','functionality') === true)){
			require_once OP_LIB.'sections/page/functionality.php';
			$object = new OptimizePress_Sections_Functionality();
			$GLOBALS['functionality_sections'] = $object->sections();
			foreach($GLOBALS['functionality_sections'] as $name => $section){
				if(isset($section['module'])){
					op_mod($section['module'],op_get_var($section,'module_type','blog'),array('section'=>$name));
				}
			}
		}
		do_action('op_page_module_init');
	}
}
function op_textdomain($var=OP_SN, $path=OP_DIR){
	static $loaded = array();
	if(!isset($loaded[$var])){
		$loaded[$var] = true;
		load_theme_textdomain($var, $path.'languages');
		$locale = get_locale();
		$locale_file = $path."languages/$locale.php";
		if ( is_readable($locale_file) )
			require_once($locale_file);
	}
}
function op_theme_url($path,$dir=null){
	if(is_null($dir)){
		$dir = op_get_option('theme','dir');
	}
	return OP_URL.'themes/'.$dir.'/'.ltrim($path,'/');
}
function op_page_url($path,$dir=null,$type=null){
	static $page_type;
	if(!isset($page_type)){
		$page_type = op_page_option('theme','type');
	}
	if(is_null($dir)){
		$dir = op_page_option('theme','dir');
	}
	return OP_URL.'pages/'.$page_type.'/'.$dir.'/'.ltrim($path,'/');
}
function op_pagination(array $args=array()){
	global $wp_query, $paged;
	$cur = $paged < 2 ? 1 : $paged;
    if ($paged == 0) {
        $cur = 1;
    }
	$defaults = array(
		'pages' => '',
		'range' => 4,
		'echo' => true
	);
	extract(wp_parse_args( $args, $defaults ));

	$showitems = ($range * 2)+1;

	if($pages == ''){
		if(!$pages = $wp_query->max_num_pages){
			$pages = 1;
		}
	}
	$out = array();
	if($pages != 1){
		if($paged > 2 && $paged > $range+1 && $showitems < $pages){
			$out[] = '<li class="first-link"><a href="'.get_pagenum_link(1).'">' . __('First', OP_SN) . '</a></li>';
		}
		if($paged > 1 && $showitems < $pages){
			$out[] = '<li class="previous-link"><a href="'.get_pagenum_link($paged - 1).'">' . __('Previous', OP_SN) . '</a></li>';
		}

		for($i=1; $i<= $pages; $i++){
			if($pages != 1 && ( !($i >= $paged+$range+1 || $i <= $paged-$range-1) || $pages <= $showitems)){
				$out[] = '<li class="numbered-link'.($cur == $i ? ' selected':'').'"><a href="'.get_pagenum_link($i).'">'.$i.'</a></li>';
			}
		}
		if($paged < $pages && $showitems < $pages){
			$out[] = '<li class="next-link"><a href="'.get_pagenum_link($cur + 1).'">' . __('Next', OP_SN) . '</a></li>';
		}
		if($paged < $pages-1 && $paged+$range-1 < $pages && $showitems < $pages){
			$out[] = '<li class="last-link"><a href="'.get_pagenum_link($pages).'">' . __('Last', OP_SN) . '</a></li>';
		}
	}
	if(count($out) > 0){
		$out = '
<div class="clear"></div>
<div class="pagination-details cf">
	<ul class="pagination">
	'.implode('',$out).'
	</ul>
	<p><em>'.sprintf(__('Page %1$s of %2$s', OP_SN), $cur, $pages).'</em></p>
</div>';
		if($echo){
			echo $out;
		}
		return $out;
	}
}
function &op_mod($name,$type='blog',$extra_args=array()){
	static $mods = array();
	static $default = null;
	if(!isset($mods[$type])){
		$mods[$type] = array();
	}
	$isset = false;
	if(!isset($mods[$type][$name]) && file_exists(OP_LIB.'modules/'.$type.'/'.$name.'/'.$name.'.php')){
		require_once OP_LIB.'modules/base.php';
		require_once OP_LIB.'modules/'.$type.'/'.$name.'/'.$name.'.php';
		$class = 'OptimizePress_'.ucfirst($type).'_'.op_classname($name).'_Module';
		if(class_exists($class)){
			$mods[$type][$name] = new $class(array('url'=>OP_LIB_URL.'modules/'.$type.'/'.$name.'/','path'=>OP_LIB.'modules/'.$type.'/'.$name.'/','shortname'=>$name));
			$isset = true;
		}
	} else {
		$isset = true;
	}
	/*if(count($extra_args) > 0 && $isset){
		call_user_func(array($mods[$type][$name],'set_config'),$extra_args);
	}*/
	return $mods[$type][$name];
}
function op_classname($name){
	return str_replace(' ','_',ucwords(str_replace('_',' ',$name)));
}
function op_safe_string($str){
	return str_replace(Array(',', '\'', '/', '"', '&', '?', '!', '*', '(', ')', '^', '%', '$', '#', '@', '{', '}', '[', ']', '|', ':', ';', '<', '>', '.', '~', '`', '+', '_', '='), '', str_replace(Array(' '), '-', $str));
}

/*
 * Function: op_generate_id
 * Description: Returns a unique string based on the current time,
 * 		a random number and applying an md5 hash to it
 * Parameters:
 *
 */
function op_generate_id(){
	return md5(strtotime('now').rand());
}

/**
 * Flattens multidimensional array (and sorts it by its keys if needed)
 *
 * @author Luka Peharda <luka.peharda@gmail.com>
 * @since 2.1.4
 * @param  array $array
 * @param  bool $sort
 * @return array
 */
function flatten_multidim_array($array, $sort = false)
{
	if ($sort) {
		ksort($array);
	}

	$data = array();
	foreach ($array as $item) {
		if (is_array($item)) {
			foreach ($item as $field) {
				$data[] = $field;
			}
		}
	}

	return $data;
}

/**
 * Displays OP screen with warning message
 *
 * It uses 'wp_die' method but later on we can implement our own style/design
 *
 * @author Luka Peharda <luka.peharda@gmail.com>
 * @since  2.1.5
 * @param  string $message
 * @param  string $title
 * @param  mized $action
 * @return void
 */
function op_warning_screen($message, $title, $action = null)
{
	$message = '<p class="op-warning-screen-message">' . $message . '</p>';
	if (null !== $action) {
		$message .= '<p class="op-warning-screen-action">' . $action . '</p>';
	}

	wp_die($message, $title, array('response' => 409));
}

/**
 * Get all revisions for postID in chronologically reversed order
 * @author Zvonko Biskup <zbiskup@gmail.com>
 * @since 2.1.9
 * @param int $postID
 * @return mixed
 */
function op_get_page_revisions($postID)
{
    global $wpdb;

    $table = $wpdb->prefix . 'optimizepress_post_layouts';

    $revisions = $wpdb->get_results($wpdb->prepare(
        "SELECT id, modified FROM `{$table}` WHERE `post_id` = %d AND `type` = %s AND status = 'revision' ORDER BY modified DESC",
        $postID,
        'body'
    ));

    return $revisions;
}

/**
 * Restore page revision
 * @author Zvonko Biskup <zbiskup@gmail.com>
 * @since 2.1.9
 * @return mixed
 */
function restore_page_revision()
{
    global $wpdb;
    $table = $wpdb->prefix . 'optimizepress_post_layouts';

    $postID = op_post('postID');
    $revisionID = op_post('revisionID');

    if (empty($postID) || empty($revisionID)) {
        return 0;
        exit;
    }

    $wpdb->update($table, array('status' => 'revision', 'modified' => date('Y-m-d H:i:s')), array('post_id' => $postID, 'status' => 'publish'));
    $wpdb->update($table, array('status' => 'publish'), array('id' => $revisionID));

    return 1;
    exit;
}

/**
 * Check if op-no-admin-bar is present in the URL and hides the admin bar if it is
 * used for revisions preview
 */
function hide_admin_bar()
{
    if (!empty($_GET['op-no-admin-bar'])) {
        add_filter('show_admin_bar', '__return_false');
    }
}

// adding action for AJAX call
add_action('wp_ajax_'.OP_SN.'-restore-page-revision', 'restore_page_revision');
// adding action for hiding admin bar
add_action('init', 'hide_admin_bar');