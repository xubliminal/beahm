<?php
class OptimizePress_Sections_Modules {
	
	// Get the list of step 4 sections these can be overridden by the theme using the 'op_edit_sections_modules' filter
	static function sections(){
		static $sections;
		if(!isset($sections)){
			$sections = array(
				'sharing' => array(
					'title' => __('Sharing', OP_SN),
					'module' => 'sharing',
					'options' => op_theme_config('mod_options','sharing'),
					'no_content' => true
				),
				'related_posts' => array(
					'title' => __('Related Posts Box', OP_SN),
					'module' => 'related_posts',
					'options' => op_theme_config('mod_options','related_posts'),
					'no_content' => true
				),
				'comments' => array(
					'title' => __('Comments System', OP_SN),
					'module' => 'comments',
					'options' => op_theme_config('mod_options','comments'),
					'on_off' => false
				),
				/*'seo' => array(
					'title' => __('SEO Options', OP_SN),
					'module' => 'seo',
					'options' => op_theme_config('mod_options','seo'),
					'on_off' => false,
				),*/
				'scripts' => array(
					'title' => __('Other Scripts', OP_SN),
					'module' => 'scripts',
					'options' => op_theme_config('mod_options','scripts'),
					'on_off' => false,
				),
				'continue_reading' => array(
					'title' => __('Continue Reading Links', OP_SN),
					'module' => 'continue_reading',
					'options' => op_theme_config('mod_options','continue_reading'),
					'on_off' => false,
				)
			);
			$sections = apply_filters('op_edit_sections_modules',$sections);
		}
		return $sections;
	}
	
}