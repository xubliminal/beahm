<?php
class OptimizePress_Default_Assets {

    private static $used_items = array();
    private static $temp_val = array();
    private static $temp_tag = '';
    private static $lang_keys = array();
    private static $css_output = array();
    private static $element_count = array();
    private static $delayed_timers = array();
    private static $add_pretty_photo = false;
    private static $font = '';

    static function init(){
        add_filter('op_assets_before_addons',array('OptimizePress_Default_Assets','asset_list'));
        add_filter('op_assets_parse_list',array('OptimizePress_Default_Assets','parse_list'),1);
        add_action('op_asset_footer_js',array('OptimizePress_Default_Assets','_asset_js'));
        self::set_lang_keys();
        self::init_shortcodes();
        op_mod('comments');
    }

    function _set_font($font)
    {
        self::$font = $font;
    }

    function _get_font()
    {
        return self::$font;
    }

    function _add_element_field($atts,$content,$tag='',$prepend='',$append=''){

        global $shortcode_tags;

        //If the Live Editor is disabled, return the content with no changes, exiting the function
        if($GLOBALS['OP_LIVEEDITOR_DISABLE_NEW']) return $content;

        $GLOBALS['OP_PARSED_SHORTCODE'] = $add = '';

        if(defined('OP_PAGEBUILDER')){

            $popup_present = false;

            if (strpos($content, '[op_popup_button]') !== false) {

                $popup_present = true;
                $popup_button_class = '';
                $popup_content_class = '';
                $popup_content = $content;
                $new_popup_elements = '';

                preg_match_all('/\[op_popup_content_element[ d|\]].*?\[\/op_popup_content_element\]/is', $content, $popup_elements);

                foreach ($popup_elements[0] as $popup_element) {
                    $popup_element = str_replace('[op_popup_content_element]', '[op_liveeditor_element]', $popup_element);
                    $popup_element = str_replace('[/op_popup_content_element]', '[/op_liveeditor_element]', $popup_element);
                    $new_popup_elements .= $popup_element;
                }
                $new_popup_elements = '[op_popup_content][op_liveeditor_elements]' . $new_popup_elements . '[/op_liveeditor_elements][/op_popup_content]';

                $content = preg_replace('/\[op_popup_content[ d|\]].*?\[\/op_popup_content\]/is', $new_popup_elements, $content);
                $content = str_replace('[op_popup_button]', '<div class="op-popup-button ' . $popup_button_class . '">', $content);
                $content = str_replace('[/op_popup_button]', '</div>', $content);

                if(defined('OP_LIVEEDITOR')){
                $add_new_element_popup = '<a href="#add_element" class="add-new-element"><img src="'.OP_IMG.'/live_editor/add_new.png" alt="'.__('Add Element',OP_SN).'" /><span>'.__('Add Element',OP_SN).'</span></a>';
                } else {
                    $add_new_element_popup = '';
                }

                $content = str_replace('[op_popup_content_element]', '', $content);
                $content = str_replace('[/op_popup_content_element]', '', $content);

                $content = str_replace('[op_popup_content]', '<div class="op-popup-content">', $content);
                $content = str_replace('[/op_popup_content]', '</div>' . $add_new_element_popup, $content);

            }

            if($GLOBALS['OP_LIVEEDITOR_DEPTH'] == 0){
                $tag = $tag == '' ? self::$temp_tag : $tag;
                $new_content = $content;
                if(!preg_match('/'.op_shortcode_regex('op_liveeditor_elements').'/s',$content,$matches) && $popup_present === false){
                    $content = trim($content);
                    if($content == '<br />'){
                        $content = '';
                    }
                    $content = empty($content) ?' ' : ' [op_liveeditor_element][text_block]'.$content.'[/text_block][/op_liveeditor_element] ';
                    $new_content = '[op_liveeditor_elements]'.$content.'[/op_liveeditor_elements] ';
                    $content = $new_content;
                }

                if ($popup_present) {
                    $new_content = $popup_content;
                }

                if(defined('OP_LIVEEDITOR')){
                    $new_tag = '['.$tag;
                    if(is_array($atts)){
                        foreach($atts as $name => $val){
                            $new_tag .= ' '.$name.'="'.$val.'"';
                        }
                    }
                    $new_tag .= ']'.$prepend.'#OP_CHILD_ELEMENTS#'.$append.'[/'.$tag.']';
                    $GLOBALS['OP_PARSED_SHORTCODE'] = $new_tag;

                    $add = '<div class="op-hidden"><textarea name="shortcode[]" class="op-le-child-shortcode">'.op_attr($new_content).'</textarea></div>';
                }
            }
        }

        return apply_filters('the_content',$content).$add;

    }

    /*
    * Function: init_shortcodes
    * Description: Simply initializes the shortcodes
    * Parameters:
    *
    */
    static function init_shortcodes(){
        //Add the functions for printing scripts and styles in the header and footer
        add_action('wp_head',array('OptimizePress_Default_Assets','print_css'),7);
        add_action('wp_footer',array('OptimizePress_Default_Assets','_print_front_scripts'));

        //Get a list of assets
        $assets_array = self::_asset_list();

        //Add the following assets to said list
        $assets_array = array_merge($assets_array,array(
            'button_0' => '',
            'button_1' => '',
            'button_2' => '',
            'button_3' => '',
            'button_4' => '',
            'button_5' => '',
            'button_6' => '',
            'button_7' => '',
            'button_cart' => '',
            'question' => '',
            'feature' => '',
            'download' => '',
            'guarantee_content' => '',
            'pricing_table' => '',
            'step_graphics' => '',
            'tab' => ''
        ));

        //Remove the button asset as we have added individual button assets above
        unset($assets_array['button']);

        //Loop through the assets and...
        foreach($assets_array as $tag => $title){
            //... add the shortcode to the system
            add_shortcode($tag,array('OptimizePress_Default_Assets',$tag));
        }
    }

    /*
    * Function: _asset_list
    * Description: Used by function asset_list() to generate and return and array of assets
    * Parameters:
    *
    */
    static function _asset_list(){
        $assets_array = array(
            'affiliate_page' => __('Affiliate Page Snippets', OP_SN),
            'arrows' => __('Arrows', OP_SN),
            'audio_player' => __('Audio Player', OP_SN),
            'bullet_block' => __('Bullet Block', OP_SN),
            'button' => __('Button', OP_SN),
            'calendar_date' => __('Calendar Date & Time', OP_SN),
            'content_toggle' => __('Content Toggle', OP_SN),
            'countdown_timer' => __('Countdown Timer', OP_SN),
            // 'countdown_cookie_timer' => __('Countdown Timer with Cookie', OP_SN),
            'course_description' => __('Course Description Box', OP_SN),
            // 'delayed_content' => __('Delayed Content', OP_SN),
            'divider' => __('Divider', OP_SN),
            'dynamic_date' => __('Dynamic Date', OP_SN),
            'feature_block' => __('Feature Block', OP_SN),
            'feature_box' => __('Feature Box', OP_SN),
            'feature_box_creator' => __('Feature Box Creator', OP_SN),
            'file_download' => __('Files Download', OP_SN),
            'guarantee_box' => __('Guarantee Box', OP_SN),
            'headline' => __('Headline', OP_SN),
            'hyperlink' => __('Hyperlink', OP_SN),
            'images' => __('Images', OP_SN),
            'img_alert' => __('Image with Javascript Alert', OP_SN),
            'img_text_aside' => __('Image with Text Aside', OP_SN),
            'javascript' => __('JavaScript', OP_SN),
            'live_search' => __('Live Search', OP_SN),
            'navigation' => __('Navigation', OP_SN),
            'news_bar' => __('News Bar', OP_SN),
            // 'one_time_offer' => __('One Time Offer', OP_SN),
            'optin_box' => __('Optin Box', OP_SN),
            // 'optin_modal' => __('Optin Modal', OP_SN),
            'order_box' => __('Order Box', OP_SN),
            'order_step_graphics' => __('Order Step Graphics', OP_SN),
            'pricing_element' => __('Pricing Graphics', OP_SN),
            'pricing_table' => __('Pricing Table', OP_SN),
            'op_popup' => __('OverlayOptimizer', OP_SN),
            'progress_bar' => __('Progress Bar', OP_SN),
            'qna_elements' => __('Q&A Elements', OP_SN),
            'recent_posts' => __('Recent Posts', OP_SN),
            'social_sharing' => __('Social Sharing', OP_SN),
            'step_graphics' => __('Step Graphics', OP_SN),
            'tabs' => __('Tabs', OP_SN),
            'terms_conditions' => __('Terms & Conditions Box', OP_SN),
            'testimonials' => __('Testimonials', OP_SN),
            'tour' => __('Tour Elements', OP_SN),
            'two_column_block' => __('2 Column Text', OP_SN),
            'vertical_spacing' => __('Vertical Spacing', OP_SN),
            'video_lightbox' => __('Video Thumbnail & Lightbox', OP_SN),
            'video_player' => __('Video Player', OP_SN),
        );
        // insert the element only if OPM plugin is activated!
        if (defined("WS_PLUGIN__OPTIMIZEMEMBER_VERSION")) {
            $assets_array['membership_order_button'] = __('Membership Order Button', OP_SN);
            $assets_array['membership_download'] = __('Membership Files Download', OP_SN);
            $assets_array['membership_login_form'] = __('Membership Login Form', OP_SN);
        }
        return $assets_array;
    }

    /*
    * Function: asset_list
    * Description: Sets up the information such as title, etc for each asset
    * Parameters:
    *   $assets (array): Contains system assets, which this information is added to and then returned
    */
    static function asset_list($assets){
        //Get list of assets
        $assets_array = self::_asset_list();

        //Init the new assets array
        $new_assets = array();

        //Loop through each asset
        foreach($assets_array as $tag => $title){
            //Set up the asset's information
            $new_assets[$tag] = array(
                'title' => __($title,OP_SN),
                'description' => self::lang_key($tag.'_description'),
                'settings' => file_exists(OP_JS_PATH.'assets/core/'.$tag.'.js') ?'Y':'N',
                'image' => file_exists(OP_ASSETS.'thumbs/'.$tag.'.png') ? OP_ASSETS_URL.'thumbs/'.$tag.'.png' : ''
            );
        }

        //Add new assets to original core assets list and return
        $assets['core'] = array_merge($assets['core'],$new_assets);
        return $assets;
    }

    /*
    * Function: parse_list
    * Description: Processes the assets and child assets
    * Parameters:
    *   $assets (array): Array of the currently listed assets
    */
    function parse_list($assets){
        //Create assets array
        $assets_array = self::_asset_list();

        //Init new assets array
        $new_assets = array();

        //Create the initial tags array
        $tags = array(
            'button' => array('button_0', 'button_1', 'button_2', 'button_3', 'button_4', 'button_5', 'button_6', 'button_7', 'button_cart'),
            'guarantee_box' => array('guarantee_box','guarantee_content')
        );

        //Create the children tags array (for instance, the child tag for the tab asset is tabs)
        $children = array(
            'feature_block' => array('feature'),
            'optin_box' => array('optin_box_hidden','optin_box_field','optin_box_code','optin_box_button'),
            'pricing_table' => array('tab'),
            'qna_elements' => array('question'),
            'step_graphics' => array('step'),
            'tabs' => array('tab'),
            'terms_conditions' => array('terms'),
            'testimonials' => array('testimonial'),
            'two_column_block' => array('content1','content2'),
            'membership_order_button' => array('button_0','button_1','button_2','button_3','button_4','button_5','button_6','button_cart'),
            'membership_download' => array('download'),
            'file_download' => array('download'),
        );

        //Loop through all the assets
        foreach($assets_array as $tag => $title){
            //Init the child tags array
            $child_tags = array();

            //If this tag has a child tag, set it
            if(isset($children[$tag])) $child_tags = $children[$tag];

            //If the tag has a child tag, then loop through and add all child tags
            if(isset($tags[$tag])){
                foreach($tags[$tag] as $type_tag){
                    $new_assets[$type_tag] = array('asset'=>'core/'.$tag,'child_tags'=>$child_tags);
                }
            } else { //If not, just set up this asset
                $new_assets[$tag] = array('asset'=>'core/'.$tag,'child_tags'=>$child_tags);
            }
        }

        //Merge the assets array and the new assets array and return it
        $assets = array_merge($assets,$new_assets);
        return $assets;
    }

    /*
    * Function: affiliate_page
    * Description: Display function for this asset
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    *   $content: Contains what is inside the shortcode tags
    */
    static function affiliate_page($atts,$content=''){
        //Get the attributes from the shortcode
        $data = shortcode_atts(array(
            'style' => 1,
            'image' => '',
            'description' => '',
            'affiliate_link' => '',
            'embed_code' => '',
            'id' => 'affiliate_page_'.op_generate_id()
        ), $atts);

        //Process embed code if one exists
        $data['embed_code'] = (!empty($data['embed_code']) ? str_replace(array('<p>', '</p>'), '', base64_decode($data['embed_code'])) : '');

        //Clear the template
        _op_tpl('clear');

        //Process the new template and load it
        return _op_tpl('_load_file',OP_ASSETS.'tpls/affiliate_page/'.$data['style'].'.php',$data,true);
    }

    /*
    * Function: arrows
    * Description: Display function for this asset
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    */
    static function arrows($atts)
    {
        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(false);
        }

        $uid = 'arr_' . md5(serialize($atts));

        if (false === $content = get_transient('el_' . $uid)) {
            $atts = shortcode_atts(array(
                'style' => 'arrow-blue-1.png',
                'align' => 'center',
            ), $atts);

            $atts['op_assets_url'] = OP_ASSETS_URL;

            $content = op_sl_parse('arrows', $atts);

            if (is_string($content) && 0 === strpos($content, '##')) {
                $content = substr($content, 2);
            } elseif (!empty($content)) {
                set_transient('el_' . $uid, $content, OP_SL_ELEMENT_CACHE_LIFETIME);
            }
        }

        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(true);
        }

        return $content;
    }

    /*
    * Function: audio_player
    * Description: Display function for this asset
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    */
    static function audio_player($atts){
        //Extract attributes from shortcode and set defaults
        extract(shortcode_atts(array(
            'auto_play' => 'N',
            'url' => '',
            'type' => 'audio',
            'height' => '30',
            'url1' => '',
            'url2' => '',
            'margin_top' => 0,
            'margin_bottom' => 20,
            'border_size' => 0,
            'border_color' => '#fff'
        ), $atts));
        //Preparing the vars variable to be sent to video module
        $vars = array(
            'auto_play' => $auto_play,
            'url' => $url,
            'type' => $type,
            'height' => $height,
            'url1' => $url1,
            'url2' => $url2,
            'margin_top' => 0,
            'margin_bottom' => 20,
            'border_size' => 0,
            'border_color' => '#fff'
        );

        /**
         * Loading audio player script (audio & video is handled by the same script)
         */
        op_video_player_script();

        //Use the video module to generate the output html
        $output = op_mod('video')->output(array(),array(),$vars,true,true);

        //If we are not in the admin section (meaning the user facing side)
        //then we just return the output html without using a template
        if(!is_admin()) return $output['output'];

        //Return asset HTML with data inputted
        return '<div class="audio-plugin" style="height:'.$height.'px"><a href="#"></a></div>';
    }

    /*
    * Function: bullet_block
    * Description: Display function for this asset
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    *   $content: Contains what is inside the shortcode tags
    */
    static function bullet_block($atts,$content='')
    {
        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(false);
        }

        $uid = 'bbl_' . md5(serialize($atts) . $content);

        if (false === $output = get_transient('el_' . $uid)) {
            $params = shortcode_atts(array(
                'style' => 1,
                'width' => '',
                'alignment' => 'center'
            ), $atts);

            $content = op_clean_shortcode_content($content);
            $font = op_asset_font_style($atts);
            $path = OP_ASSETS_URL.'images/bullet_block/';

            $params['content'] = $content;
            $params['font'] = $font;
            $params['path'] = $path;
            $params['atts'] = $atts;

            $output = op_sl_parse('bullet_block', $params);

            if (is_string($output) && 0 === strpos($output, '##')) {
                $output = substr($output, 2);
            } elseif (!empty($output)) {
                set_transient('el_' . $uid, $output, OP_SL_ELEMENT_CACHE_LIFETIME);
            }
        }

        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(true);
        }

        return $output;
    }

    /*
    * Function: calendar_date
    * Description: Display function for the Calendar Date & Time asset
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    */
    static function calendar_date($atts){
        //Get the attributes from the shortcode
        $data = shortcode_atts(array(
            'style' => 1,
            'id' => 'calendar_date_'.op_generate_id(),
            'calendar_bar_color' => '',
            'month' => '',
            'day' => '',
            'full_date' => '',
            'time_1' => '',
            'time_2' => '',
            'time_3' => '',
            'timezone_1' => '',
            'timezone_2' => '',
            'timezone_3' => ''
        ), $atts);

        //Clear the template
        _op_tpl('clear');

        //Process the new template and load it
        return _op_tpl('_load_file',OP_ASSETS.'tpls/calendar_date/'.$data['style'].'.php',$data,true);
    }

    /*
    * Function: credit_card_icons
    * Description: Used by the button asset to add credit card icons to the asset
    * Parameters:
    *   $str: Text on button
    *   $cc: Pipe (|) separated string containing the credit card icons
    *   $align: Determines alignment for the button text
    */
    static function credit_card_icons($str,$cc,$align = 'center'){
        //Blow up the credit card icons into an array
        $cc = array_filter(explode('|',$cc));

        //Make sure we have icons selected
        if(count($cc) > 0){
            //Init the path for the icon images
            $path = OP_ASSETS_URL.'images/button/cc_icons/';

            //Init the icons HTML string
            $cc_str = '';

            //Loop through each icon
            foreach($cc as $c){
                //Add icon to the HTML string
                $cc_str .= '<img src="'.$path.$c.'" alt="" width="48" height="31" />';
            }

            //Add the container to the HTML string
            $str = '
                <div class="button-with-cc">
                    '.$str.'
                    <div>'.$cc_str.'</div>
                </div>
            ';
        }

        //Set the text alignment for the button
        $str = '<div style="text-align:'.$align.'">'.$str.'</div>';

        //Return the HTML
        return $str;
    }

    /*
    * Function: button_2
    * Description: Used by button asset to allow the user to choose this specific button
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    *   $content: Contains what is inside the shortcode tags
    */
    static function button_2($atts,$content='')
    {
        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(false);
        }

        //Generate an ID for this button
        $uid = 'btn_2_' .md5(serialize($atts) . $content);

        //Check if we should generate this button
        if (false === $markup = get_transient('el_' . $uid)) {
            //Extract the attributes from the shortcode into an array
            $atts = shortcode_atts(array(
                'href' => '',
                'color' => 'blue',
                'cc' => '',
                'align' => 'center',
                'element_type' => 'a',
                'new_window' => 'N',
            ), $atts);

            //Parse the markup for API
            $markup = op_sl_parse('button_2', $atts);

            //Assuming the markup contains something...
            if (is_string($markup) && 0 === strpos($markup, '##')) {
                $markup = substr($markup, 2);
            } elseif (!empty($markup)) {
                //Add the credit card icons to the button
                $markup = self::credit_card_icons(str_replace('%s', do_shortcode(op_clean_shortcode_content($content)), $markup), $atts['cc'], $atts['align']);
                set_transient('el_' . $uid, $markup, OP_SL_ELEMENT_CACHE_LIFETIME);
            }
        }

        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(true);
        }

        //Return the HTML
        return $markup;
    }

    /*
    * Function: button_3
    * Description: Used by button asset to allow the user to choose this specific button
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    *   $content: Contains what is inside the shortcode tags
    */
    static function button_3($atts,$content='')
    {
        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(false);
        }

        //Generate an ID for this button
        $uid = 'btn_3_' .md5(serialize($atts) . $content);

        //Check if we should generate this button
        if (false === $markup = get_transient('el_' . $uid)) {
            //Extract the attributes from the shortcode into an array
            $atts = shortcode_atts(array(
                'href' => '',
                'bg' => '',
                'text' => 'yes-let-me-in.png',
                'cc' => '',
                'align' => 'center',
                'element_type' => 'a',
                'new_window' => 'N',
            ), $atts);

            //Get the assets URL for this button
            $atts['op_assets_url'] = OP_ASSETS_URL;

            //Parse the markup for API
            $markup = op_sl_parse('button_3', $atts);

            //Assuming the markup contains something...
            if (is_string($markup) && 0 === strpos($markup, '##')) {
                $markup = substr($markup, 2);
            } elseif (!empty($markup)) {
                //Add the credit card icons to the button
                $markup = self::credit_card_icons($markup, $atts['cc'], $atts['align']);
                set_transient('el_' . $uid, $markup, OP_SL_ELEMENT_CACHE_LIFETIME);
            }
        }

        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(true);
        }

        //Return the HTML
        return $markup;
    }

    /*
    * Function: button_4
    * Description: Used by button asset to allow the user to choose this specific button
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    *   $content: Contains what is inside the shortcode tags
    */
    static function button_4($atts,$content='')
    {
        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(false);
        }

        //Generate an ID for this button
        $uid = 'btn_4_' .md5(serialize($atts) . $content);

        //Check if we should generate this button
        if (false === $markup = get_transient('el_' . $uid)) {
            //Extract the attributes from the shortcode into an array
            $atts = shortcode_atts(array(
                'href' => '',
                'border' => '',
                'size' => 'medium',
                'color' => 'black',
                'cc' => '',
                'align' => 'center',
                'element_type' => 'a',
                'new_window' => 'N',
            ), $atts);

            //Parse the markup for API
            $markup = op_sl_parse('button_4', $atts);

            //Assuming the markup contains something...
            if (is_string($markup) && 0 === strpos($markup, '##')) {
                $markup = substr($markup, 2);
            } elseif (!empty($markup)) {
                //Add the credit card icons to the button
                $markup = self::credit_card_icons(str_replace('%s', do_shortcode(op_clean_shortcode_content($content)), $markup), $atts['cc'], $atts['align']);
                set_transient('el_' . $uid, $markup, OP_SL_ELEMENT_CACHE_LIFETIME);
            }
        }

        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(true);
        }

        //Return the HTML
        return $markup;
    }

    /*
    * Function: button_5
    * Description: Used by button asset to allow the user to choose this specific button
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    */
    static function button_5($atts)
    {
        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(false);
        }

        //Generate an ID for this button
        $uid = 'btn_5_' .md5(serialize($atts));

        //Check if we should generate this button
        if (false === $markup = get_transient('el_' . $uid)) {
            //Extract the attributes from the shortcode into an array
            $atts = shortcode_atts(array(
                'href' => '',
                'bg' => 'blue',
                'text_color' => 'dark',
                'text' => 'add-to-cart.png',
                'cc' => '',
                'align' => 'center',
                'element_type' => 'a',
                'new_window' => 'N',
            ), $atts);

            //Get the assets URL for this button
            $atts['op_assets_url'] = OP_ASSETS_URL;

            //Parse the markup for API
            $markup = op_sl_parse('button_5', $atts);

            //Assuming the markup contains something...
            if (is_string($markup) && 0 === strpos($markup, '##')) {
                $markup = substr($markup, 2);
            } elseif (!empty($markup)) {
                //Add the credit card icons to the button
                $markup = self::credit_card_icons($markup, $atts['cc'], $atts['align']);
                set_transient('el_' . $uid, $markup, OP_SL_ELEMENT_CACHE_LIFETIME);
            }
        }

        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(true);
        }

        //Return the HTML
        return $markup;
    }

    /*
    * Function: button_6
    * Description: Used by button asset to allow the user to choose this specific button
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    */
    static function button_6($atts)
    {
        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(false);
        }

        //Generate an ID for this button
        $uid = 'btn_6_' .md5(serialize($atts));

        //Check if we should generate this button
        if (false === $markup = get_transient('el_' . $uid)) {
            //Extract the attributes from the shortcode into an array
            $atts = shortcode_atts(array(
                'href' => '',
                'bg' => 'green',
                'text' => 'style5_addtocart.png',
                'cc' => '',
                'align' => 'center',
                'element_type' => 'a',
                'new_window' => 'N',
            ), $atts);

            //Get the assets URL for this button
            $atts['op_assets_url'] = OP_ASSETS_URL;

            //Parse the markup for API
            $markup = op_sl_parse('button_6', $atts);

            //Assuming the markup contains something...
            if (is_string($markup) && 0 === strpos($markup, '##')) {
                $markup = substr($markup, 2);
            } elseif (!empty($markup)) {
                //Add the credit card icons to the button
                $markup = self::credit_card_icons($markup, $atts['cc'], $atts['align']);
                set_transient('el_' . $uid, $markup, OP_SL_ELEMENT_CACHE_LIFETIME);
            }
        }

        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(true);
        }

        //Return the HTML
        return $markup;
    }

    /**
     * Image upload button replacement shortcode
     * @param  array $atts
     * @return string
     */
    static function button_7($atts)
    {
        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(false);
        }

        $uid = 'btn_7_' .md5(serialize($atts));
        if (false === $markup = get_transient('el_' . $uid)) {

            $markup = op_sl_parse('button_7', $atts);

            if (is_string($markup) && 0 === strpos($markup, '##')) {
                $markup = substr($markup, 2);
            } elseif (!empty($markup)) {
                $markup = self::credit_card_icons($markup, $atts['cc'], $atts['align']);
                set_transient('el_' . $uid, $markup, OP_SL_ELEMENT_CACHE_LIFETIME);
            }
        }

        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(true);
        }

        return $markup;
    }

    /**
     * Button creator shortcode
     * @param  array $atts
     * @return string
     */
    static function button_1($atts)
    {
        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(false);
        }

        /*
         * Generating unique ID for this button
         */
        $uid = 'btn_1_' . md5(serialize($atts));

        if (false === $data = get_transient('el_' . $uid)) {

            $atts = shortcode_atts(array(
                'href'                          => '',
                'new_window'                    => '',
                'cc'                            => '',
                'align'                         => 'center',
                'element_type'                  => 'a',
                'location'                      => null,

                'text'                          => '',
                'text_size'                     => 20,
                'text_color'                    => null,
                'text_font'                     => null,
                'text_bold'                     => 'N',
                'text_underline'                => 'N',
                'text_italic'                   => 'N',
                'text_letter_spacing'           => null,

                'subtext_panel'                 => 'N',
                'subtext'                       => '',
                'subtext_size'                  => 15,
                'subtext_color'                 => null,
                'subtext_font'                  => null,
                'subtext_bold'                  => 'N',
                'subtext_underline'             => 'N',
                'subtext_italic'                => 'N',
                'subtext_letter_spacing'        => null,

                'text_shadow_panel'             => 'N',
                'text_shadow_vertical'          => 0,
                'text_shadow_horizontal'        => 0,
                'text_shadow_color'             => null,
                'text_shadow_blur'              => 0,

                'styling_width'                 => 63,
                'styling_height'                => 23,
                'styling_border_color'          => null,
                'styling_border_size'           => 0,
                'styling_border_radius'         => 0,
                'styling_border_opacity'        => 100,
                'styling_gradient'              => 'N',
                'styling_shine'                 => 'N',
                'styling_gradient_start_color'  => null,
                'styling_gradient_end_color'    => null,

                'drop_shadow_panel'             => 'N',
                'drop_shadow_vertical'          => 0,
                'drop_shadow_horizontal'        => 0,
                'drop_shadow_blur'              => 0,
                'drop_shadow_spread'            => 0,
                'drop_shadow_color'             => null,
                'drop_shadow_opacity'           => 100,

                'inset_shadow_panel'            => 'N',
                'inset_shadow_vertical'         => 0,
                'inset_shadow_horizontal'       => 0,
                'inset_shadow_blur'             => 0,
                'inset_shadow_spread'           => 0,
                'inset_shadow_color'            => null,
                'inset_shadow_opacity'          => 100
            ), $atts);

            /*
             * Only text/subtext box options are extracted here
             */
            extract(shortcode_atts(array(
                'text'                          => '',
                'text_size'                     => 20,
                'text_color'                    => null,
                'text_font'                     => null,
                'text_bold'                     => 'N',
                'text_underline'                => 'N',
                'text_italic'                   => 'N',
                'text_letter_spacing'           => null,

                'subtext_panel'                 => 'N',
                'subtext'                       => '',
                'subtext_size'                  => 15,
                'subtext_color'                 => null,
                'subtext_font'                  => null,
                'subtext_bold'                  => 'N',
                'subtext_underline'             => 'N',
                'subtext_italic'                => 'N',
                'subtext_letter_spacing'        => null,

                'cc'                            => '',
                'align'                         => 'center',
            ), $atts));

            /*
             * Initializing CSS styles
             */
            $styles = array('text' => array(), 'subtext' => array(), 'container' => array(), 'gradient' => array(), 'shine' => array(), 'hover' => array(), 'active' => array());

            /*
             * Text box
             */
            if (null !== $text_size) {
                $styles['text']['font-size'] = $text_size . 'px';
            }
            if (null !== $text_color) {
                $styles['text']['color'] = $text_color;
            }
            if (null !== $text_font) {
                $text_font = explode(';', $text_font);
                if (isset($text_font[1]) && $text_font[1] !== 'google') {
                    $styles['text']['font-family'] = op_default_fonts($text_font[0]);
                } else {
                    $styles['text']['font-family'] = $text_font[0];
                    $data['fonts'][] = $text_font[0];
                }
            }
            if ('Y' === $text_bold || 1 == $text_bold) {
                $styles['text']['font-weight'] = 'bold';
            } else {
                $styles['text']['font-weight'] = 'normal';
            }
            if ('Y' === $text_underline || 1 == $text_underline) {
                $styles['text']['text-decoration'] = 'underline';
            }
            if ('Y' === $text_italic || 1 == $text_italic) {
                $styles['text']['font-style'] = 'italic';
            }
            if (null !== $text_letter_spacing && 0 !== (int)$text_letter_spacing) {
                $styles['text']['letter-spacing'] = $text_letter_spacing . 'px';
            }

            /*
             * Subtext box
             */
            if ($subtext_panel === 'Y') {
                if (null !== $subtext_size) {
                    $styles['subtext']['font-size'] = $subtext_size . 'px';
                }
                if (null !== $subtext_color) {
                    $styles['subtext']['color'] = $subtext_color;
                }
                if (null !== $subtext_font) {
                    $subtext_font = explode(';', $subtext_font);
                    if (isset($subtext_font[1]) && $subtext_font[1] !== 'google') {
                        $styles['subtext']['font-family'] = op_default_fonts($subtext_font[0]);
                    } else {
                        $styles['subtext']['font-family'] = $subtext_font[0];
                        $data['fonts'][] = $subtext_font[0];
                    }
                }
                if ('Y' === $subtext_bold) {
                    $styles['subtext']['font-weight'] = 'bold';
                } else {
                    $styles['subtext']['font-weight'] = 'normal';
                }
                if ('Y' === $subtext_underline) {
                    $styles['subtext']['text-decoration'] = 'underline';
                }
                if ('Y' === $subtext_italic) {
                    $styles['subtext']['font-style'] = 'italic';
                }
                if (null !== $subtext_letter_spacing && 0 !== (int)$subtext_letter_spacing) {
                    $styles['subtext']['letter-spacing'] = $subtext_letter_spacing . 'px';
                }
            }

            $atts['styles'] = $styles;
            $atts['uid'] = $uid;

            $data['content'] = op_sl_parse('button_1', $atts);

            /*
             * We are saving transient only when there is some content returned
             */
            if (is_string($data['content']) && 0 === strpos($data['content'], '##')) {
                $data['content'] = substr($data['content'], 2);
            } elseif (!empty($data['content'])) {
                $data['content'] = self::credit_card_icons($data['content'], $cc, $align);
                set_transient('el_' . $uid, $data, OP_SL_ELEMENT_CACHE_LIFETIME);
            }
        }

        /*
         * We need to load external fonts everytime
         */
        if (isset($data['fonts'])) {
            foreach (array_unique($data['fonts']) as $font) {
                op_add_fonts($font);
            }
        }

        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(true);
        }

        return $data['content'];
    }

    /*
    * Function: button_0
    * Description: Used by button asset to allow the user to choose this specific button
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    *   $content: Contains what is inside the shortcode tags
    */
    function button_0($atts,$content='')
    {

        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(false);
        }

        //Generate an ID for this button
        $uid = 'btn_0_' .md5(serialize($atts) . $content);

        //Check if we should generate this button
        if (false === $markup = get_transient('el_' . $uid)) {

            //Extract the attributes from the shortcode into an array
            $atts = shortcode_atts(array(
                // 'href' => '',
                // 'color' => 'blue',
                'cc' => '',
                'align' => 'center',
                // 'element_type' => 'a',
                // 'new_window' => 'N',
                'button_text' => 'Button Text'
            ), $atts);

            //Parse the markup for API
            $markup = op_sl_parse('button_0', $atts['button_text']);

            //Assuming the markup contains something...
            if (is_string($markup) && 0 === strpos($markup, '##')) {
                $markup = substr($markup, 2);
            } elseif (!empty($markup)) {
                //Add the credit card icons to the button
                $markup = self::credit_card_icons(str_replace('%s', do_shortcode(op_clean_shortcode_content($content)), $markup), $atts['cc'], $atts['align']);
                set_transient('el_' . $uid, $markup, OP_SL_ELEMENT_CACHE_LIFETIME);
            }

        }

        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(true);
        }

        //Return the HTML
        return $markup;
    }

    /*
    * Function: button_cart
    * Description: Used by button asset to allow the user to choose from a set of shopping cart buttons
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    */
    static function button_cart($atts)
    {
        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(false);
        }

        //Generate an ID for this button
        $uid = 'btn_cart_' .md5(serialize($atts));

        //Check if we should generate this button
        if (false === $markup = get_transient('el_' . $uid)) {
            //Extract the attributes from the shortcode into an array
            $atts = shortcode_atts(array(
                'href' => '',
                'bg' => 'atc-1.png',
                'cc' => '',
                'align' => 'center',
                'element_type' => 'a',
                'new_window' => 'N',
            ), $atts);

            $atts['op_assets_url'] = OP_ASSETS_URL;

            //Parse the markup for API
            $markup = op_sl_parse('button_cart', $atts);

            //Assuming the markup contains something...
            if (is_string($markup) && 0 === strpos($markup, '##')) {
                $markup = substr($markup, 2);
            } elseif (!empty($markup)) {
                //Add the credit card icons to the button
                $markup = self::credit_card_icons($markup, $atts['cc'], $atts['align']);
                set_transient('el_' . $uid, $markup, OP_SL_ELEMENT_CACHE_LIFETIME);
            }
        }

        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(true);
        }

        //Return the HTML
        return $markup;
    }

    /*
    * Function: content_toggle
    * Description: Display function for this asset
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    *   $content: Contains what is inside the shortcode tags
    */
    static function content_toggle($atts,$content=''){
        //Init variables
        self::$used_items['content_toggle'] = true;
        self::$temp_tag = 'content_toggle';

        //Get font settings from attributes
        $font = op_asset_font_style($atts);
        $original_font_str = $GLOBALS['OP_LIVEEDITOR_FONT_STR'];
        $style_str = (!empty($font) ? ' style="'.$font.'"' : '');
        $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = (!empty($font) ? array('elements' => array('p','a'), 'style_str' => $font) : array());

        //Get additional arguments from function
        $args = func_get_args();

        //Process content
        $content = call_user_func_array(array('OptimizePress_Default_Assets','_add_element_field'),$args);
        $content = op_process_asset_content($content);

        //Reset to the original font string
        $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = ($GLOBALS['OP_LIVEEDITOR_DEPTH']==1 ? $original_font_str : '');

        //Return the HTML
        return '
            <div class="toggle-panel cf">
                <a href="#" class="toggle-panel-toggle-text toggle-show">'.op_get_var($atts,'label').'</a>
                <a href="#" class="toggle-panel-toggle-text toggle-hide" style="display:none">'.op_get_var($atts,'hide_label').'</a>
                <a href="#" class="toggle-panel-toggle"><span>+</span></a>
                <div class="toggle-panel-content"'.$font.'>
                    '.$content.'
                </div>
            </div>
        ';
    }

    /*
    * Function: countdown_timer
    * Description: Display function for this asset
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    */
    static function countdown_timer($atts){
        //Get the attributes from the shortcode
        $data = shortcode_atts(array(
            'style' => 1,
            'end_date' => '',
            'id' => 'countdown_timer_'.op_generate_id(),
            'years_text_singular' => __('Year', OP_SN),
            'years_text' => __('Years', OP_SN),
            'months_text_singular' => __('Month', OP_SN),
            'months_text' => __('Months', OP_SN),
            'weeks_text_singular' => __('Week', OP_SN),
            'weeks_text' => __('Weeks', OP_SN),
            'days_text_singular' => __('Day', OP_SN),
            'days_text' => __('Days', OP_SN),
            'hours_text_singular' => __('Hour', OP_SN),
            'hours_text' => __('Hours', OP_SN),
            'minutes_text_singular' => __('Minute', OP_SN),
            'minutes_text' => __('Minutes', OP_SN),
            'seconds_text_singular' => __('Second', OP_SN),
            'seconds_text' => __('Seconds', OP_SN),
        ), $atts);

        //Clear the template
        _op_tpl('clear');

        //Process the new template and load it
        return _op_tpl('_load_file',OP_ASSETS.'tpls/countdown_timer/'.$data['style'].'.php',$data,true);
    }

    /*
    * Function: countdown_cookie_timer
    * Description: Display function for the countdown timer with cookie asset
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    */
    static function countdown_cookie_timer($atts){
        //Get the attributes from the shortcode
        $data = shortcode_atts(array(
            'style' => 1,
            'length_of_time' => '',
            'length_of_time_suffix' => __('hours', OP_SN),
            'redirect_url' => '',
            'id' => 'countdown_cookie_timer_'.op_generate_id(),
        ), $atts);

        //Check if cookie is set and, if so, set the values based on the cookie
        if ($_COOKIE['op_countdown_cookie_timer_length_of_time']==$data['length_of_time'] &&
            $_COOKIE['op_countdown_cookie_timer_length_of_time_suffix']==$data['length_of_time_suffix']){
            //Get the data from the cookie
            $data['end_date'] = $_COOKIE['op_countdown_cookie_timer_end_date'];
            $data['redirect_url'] = $_COOKIE['op_countdown_cookie_timer_redirect_url'];

            //Check if the date is past today's date and, if so, redirect
            if ((strtotime(date(OP_DATE_TIME_PICKER_GMT))>strtotime($data['end_date'])) && !empty($data['redirect_url'])) header('Location: '.$data['redirect_url']);
        } else { //Otherwise we generate the date and set the cookies
            //Generate the end date
            $data['end_date'] = date(OP_DATE_TIME_PICKER_GMT, strtotime('+'.$data['length_of_time'].' '.$data['length_of_time_suffix']));

            //Set the cookie
            setcookie('op_countdown_cookie_timer_end_date', $data['end_date']);
            setcookie('op_countdown_cookie_timer_redirect_url', $data['redirect_url']);
            setcookie('op_countdown_cookie_timer_length_of_time', $data['length_of_time']);
            setcookie('op_countdown_cookie_timer_length_of_time_suffix', $data['length_of_time_suffix']);
        }

        //Clear the template
        _op_tpl('clear');

        //Process the new template and load it
        return _op_tpl('_load_file',OP_ASSETS.'tpls/countdown_cookie_timer/tpl.php', $data, true);
    }

    /*
    * Function: course_description
    * Description: Display function for this asset
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    */
    static function course_description($atts, $cont){
        //Get the attributes from the shortcode
        extract($data = shortcode_atts(array(
            'style' => 1,
            'icon' => '',
            'icon_folder' => 'course_description',
            'image' => '',
            'title' => '',
            'content' => '',
            'id' => 'course_description_'.op_generate_id(),
            'width' => '',
            'height' => '',
            'font' => '',
            'content_font' => ''
        ), $atts));

        //Decide whether we use the icon or the image
        $data['img'] = (!empty($image) ? $image : OP_ASSETS_URL.'images/' . $icon_folder . '/icons/'.$icon);

        //Get image dimensions
        $realImagePath = str_replace(site_url(), $_SERVER['DOCUMENT_ROOT'], $data['img']);
        list($data['width'], $data['height']) = @getimagesize($realImagePath);

        if (!empty($cont)) {
            $data['content'] = $cont;
        } else {
            //Decode the content from base64
            $data['content'] = base64_decode($data['content']);
        }

        //Generate font styling for title
        $font = op_asset_font_style($atts, 'font_');
        $data['font'] = (!empty($font) ? ' style=\''.$font.'\'' : '');

        //Generate font styling for content
        $content_font = op_asset_font_style($atts, 'content_font_');
        $data['content_font'] = (!empty($content_font) ? ' style=\''.$content_font.'\'' : '');

        //Clear the template
        _op_tpl('clear');

        //Process the new template and load it
        return _op_tpl('_load_file',OP_ASSETS.'tpls/course_description/'.$data['style'].'.php', $data, true);
    }

    /*
    * Function: delayed_content
    * Description: Display function for this asset
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    *   $content: Contains what is inside the shortcode tags
    */
    static function delayed_content($atts,$content=''){
        //Extract attributes from shortcode attributes
        extract(shortcode_atts(array(
            'timer' => '1'
        ),$atts));

        //Provide tag (parent asset) name
        self::$temp_tag = 'delayed_content';

        //Get additional arguments from function
        $args = func_get_args();

        //Gett he content for this asset
        $content = call_user_func_array(array('OptimizePress_Default_Assets','_add_element_field'),$args);

        //Init id and then generate new id for asset
        $id = '';
        if(!defined('OP_LIVEEDITOR')){
            $id = self::_get_element_number('delayed_content');
            self::$delayed_timers[$id] = intval($timer)*1000;
            $id = ' id="'.$id.'" style="visibility:hidden"';
        }

        //Return processed content
        return '<div'.$id.' class="delayed_content">'.$content.'</div>';
    }

    /*
    * Function: divider
    * Description: Display function for this asset
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    */
    static function divider($atts){
        //Extract attributes from shortcode and place them in the $data array
        $data = shortcode_atts(array(
            'path' => OP_ASSETS_URL.'images/divider/',
            'style' => 1,
            'label' => ''
        ),$atts);

        //Clear the template
        _op_tpl('clear');

        //Process the new template and load it
        return _op_tpl('_load_file',OP_ASSETS.'tpls/divider/style_'.$data['style'].'.php',$data,true);
    }

    /*
    * Function: dynamic_date
    * Description: Asset which isplays the date dynamically
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    */
    static function dynamic_date($atts){
        //Extract the attributes into variables
        extract(shortcode_atts(array(
            'before' => '',
            'after' => '',
            'date_format' => '',
        ),$atts));

        //Return the properly formatted time HTML
        return $before.date($date_format,current_time('timestamp')).$after;
    }

    /*
    * Function: feature_block
    * Description: Display function for this asset
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    *   $content: Contains what is inside the shortcode tags
    */
    static function feature_block($atts,$content=''){
        //Extract the attributes into variables
        extract(shortcode_atts(array(
            'style' => 'icon',
            'columns' => '2',
            'icon_style' => '',
            'overall_style' => 'icon'
        ), $atts));

        //Check if icon style is set. If so, we use this as the style
        $style = (!empty($icon_style) ? $icon_style : $style);

        //Originally the asset was set up to use only 'icon' or 'image'
        //When we added support for numbered styles as well, we need to
        //now check if it is one of the two original styles. If not, we
        //set the style  with the style number
        $style = (($style!='icon' && $style!='image') ? str_replace('style_', '', $style) : $style);

        //Fix for a bug that caused neither image nor icon to display.
        $content = str_replace('image="" upload_icon=""', 'icon="1.png" upload_icon=""', $content);

        //Get the number of the class we should use
        $style_number = intval($style);

        //Create the class name for this feature block
        //$className = ($style=='icon' || $style=='image' ? 'feature-block-with-'.$overall_style : 'feature-block-style-'.$style_number);

        if ($style=='icon' || $style=='image') {
            $className = 'feature-block-style-'.$style . ' feature-block-with-'.$style;
        } else {
            $className = 'feature-block-style-'.$style_number;
        }

        //Add style number attribute to shortcode prior to processing
        $content = str_replace('[feature', '[feature style_number="'.$style_number.'"', $content);

        //Set temp values for the features function below
        //This function converts the shortcode to html
        $title_font = op_asset_font_style($atts);
        self::$temp_val['feature_block_title'] = $title_font;
        self::$temp_val['feature_block'] = $style;

        //Set up the fonts
        $font = op_asset_font_style($atts,'content_font_');
        $original_font_str = $GLOBALS['OP_LIVEEDITOR_FONT_STR'];
        if($font != ''){
            $style_str = ' style=\''.$font.'\'';
            $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = array('elements' => array('p','a'), 'style_str' => $font);
        }

        //Convert shortcoded content into HTML
        $content = do_shortcode(op_clean_shortcode_content($content));
        $content = op_process_asset_content($content);
        $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = $original_font_str;

        //Set the correct number of columns
        switch($columns){
            case 4:
                $columns = 'four';
                break;
            case 3:
                $columns = 'three';
                break;
            case 1:
                $columns = 'one';
                break;
            default:
                $columns = 'two';
        }

        //Change content based on style
        switch($style){
            case 'icon':
                break;
            case 'image':
                break;
            case 1:
                $content = str_replace(array('<h2>', '</h2>'), array('<h2><span>', '</span></h2>'), $content);
                break;
        }
        //Return the HTML
        return '<ul class="feature-block '.$className.' feature-block-'.$columns.'-col cf">'.$content.'</ul>';
    }

    /*
    * Function: feature
    * Description: Display function for the feature block icons and images (not numbered styles)
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    *   $content: Contains what is inside the shortcode tags
    */
    static function feature($atts,$content=''){
        //Set the style of the feature block
        $style = isset(self::$temp_val['feature_block']) ? self::$temp_val['feature_block'] : 'icon';

        //Extract the attributes into variables
        extract(shortcode_atts(array(
            'title' => '',
            $style => '',
            'style_number' => 0,
            'bg_color' => '',
            'icon' => '',
            'upload_icon' => '',
            'href' => '',
        ), $atts));

        //Get the feature block title font
        $font = self::$temp_val['feature_block_title'];

        //Init the font style string
        $title_str = (!empty($font) ? ' style=\''.$font.'\'' : '');

        //If an image style, we set up the img class
        //$feature_block_img_class = ($style=='image' ? ' class="feature-block-'.$style.'"' : '');

        //Generate image tag
        $img_style_attr = ((!empty($bg_color)) ? ' style="' : '');
        $img_style_attr .= (!empty($bg_color) ? 'background-color: '.$bg_color.';' : '');
        $img_style_attr .= ((!empty($bg_color)) ? '"' : '');
        $img_html = (($style>=1 && $style<=4) ? '<span'.$img_style_attr.'>' : '').'<img'.(($style!='image' && $style!='icon') ? ' class="feature-block-'.$style_number.'"' : ' class="feature-block-'.$style.'"').' src="'.OP_ASSETS_URL.'images/feature_block/icons/'.$icon.'" />'.(($style>=1 && $style<=4) ? '</span>' : '');

        //Generate H2 tag
        if (!empty($title)) {
            $h2_html = ($style==1 ? '<h2'.$title_str.'><span>'.$title.'</span></h2>' : '<h2'.$title_str.'>'.$title.'</h2>');
        } else {
            $h2_html = '';
        }

        if (!empty($upload_icon)) {
            $image_source = $upload_icon;
        } else {
            $image_source = OP_ASSETS_URL.'images/feature_block/icons/'.$icon;
        }

        //For feature block with image
        if ($style === 'image' || $style === 'icon') {
            $additionalImgMarkupStart = '<span class="feature-block-icon-container">';
            $additionalImgMarkupEnd = '</span>';
        } else {
            $additionalImgMarkupStart = '';
            $additionalImgMarkupEnd = '';
        }

        // For feature blocks that have href set
        if (trim($href) !== '') {
            $href_start = '<a class="feature-block-link" href="' . $href . '">';
            $href_end = '</a>';
            $content = strip_tags($content);
        } else {
            $href_start = '';
            $href_end = '';
        }

        //Determine which style has been selected and generate HTML accordingly
        switch($style){
            case 1:
                $html = '
                    <li>'
                        . $href_start
                            . '<div>
                                <img class="feature-block-1" src="'.$image_source.'" />'
                                . $h2_html
                                . op_texturize(do_shortcode(op_clean_shortcode_content($content))).'
                            </div>'
                        . $href_end
                    . '</li>
                ';
                break;
            case 2:
                $html = '
                    <li>'
                        . $href_start
                            . '<div>
                                <span><img class="feature-block-2" src="'.$image_source.'" /></span>'
                                . $h2_html
                                . op_texturize(do_shortcode(op_clean_shortcode_content($content))).'
                            </div>'
                        . $href_end
                    . '</li>
                ';
                break;
            case 3:
                $html = '
                    <li>'
                        . $href_start
                            . '<div>
                                <span style="background-color: '.$bg_color.';"><img class="feature-block-3" src="'.$image_source.'" /></span>'
                                . $h2_html
                                . op_texturize(do_shortcode(op_clean_shortcode_content($content))).'
                            </div>'
                        . $href_end
                    . '</li>
                ';
                break;
            case 4:
                $html = '
                    <li>'
                        . $href_start
                            . '<div>
                                <span style="background-color: '.$bg_color.';"><img class="feature-block-4" src="'.$image_source.'" /></span>'
                                . $h2_html
                                . op_texturize(do_shortcode(op_clean_shortcode_content($content))).'
                            </div>'
                        . $href_end
                    . '</li>
                ';
                break;
            default:
                if (!empty($upload_icon)) {
                    $html = '
                        <li>'
                            . $href_start
                                . '<div>'
                                    . $additionalImgMarkupStart . '<img class="feature-block-icon" src="'.$upload_icon.'" />' . $additionalImgMarkupEnd
                                    . $h2_html
                                    . op_texturize(do_shortcode(op_clean_shortcode_content($content))).'
                                </div>'
                            . $href_end
                        . '</li>
                    ';
                } else {
                    $html = '
                    <li>'
                        . $href_start
                            . '<div>'
                                . $additionalImgMarkupStart . '<img class="feature-block-icon" src="'.OP_ASSETS_URL.'images/feature_block/icons/'.$icon.'" />' . $additionalImgMarkupEnd
                                . $h2_html
                                . op_texturize(do_shortcode(op_clean_shortcode_content($content))).'
                            </div>'
                        . $href_end
                    . '</li>
                    ';
                }
                break;
        }

        //Return the HTML
        return $html;
    }



    /*
    * Function: op_popup
    * Description: Display function for the OverlayOptimizer element (popup element)
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    *   $content: Contains what is inside the shortcode tags
    */
    function op_popup($atts,$content=''){

        //Extract the attributes into variables
        extract(shortcode_atts(array(
            'style' => '1',
            'title' => '',
            'letter_spacing' => '',
            'width' => '',
            'top_margin' => '',
            'bottom_margin' => '',
            'top_padding' => '',
            'bottom_padding' => '',
            'left_padding' => '',
            'right_padding' => '',
            'alignment' => '',

            'popup_width' => '700',
            'popup_open_effect' => 'fade',
            'popup_close_effect' => 'fade',
            // 'popup_open_method' => 'zoomIn',
            // 'popup_close_method' => 'zoomOut',
            'popup_open_speed' => 'normal',
            'popup_close_speed' => 'normal',
            'popup_border_size' => '0',
            'popup_border_color' => '#ffffff',

            'popup_padding_top' => '20',
            'popup_padding_bottom' => '20',
            'popup_padding_left' => '30',
            'popup_padding_right' => '30',
        ), $atts));

        //Init the styles array
        $styles = array(
            0 => 'feature-box feature-box-0',
            1 => 'feature-box feature-box-1',
            2 => 'feature-box feature-box-2',
            3 => 'feature-box feature-box-2 feature-box-2-blue',
            4 => 'feature-box feature-box-3',
            5 => 'feature-box feature-box-3 feature-box-3-blue',
            6 => 'feature-box feature-box-4',
            7 => 'feature-box feature-box-4 feature-box-4-brown',
            8 => 'feature-box feature-box-5',
            9 => 'feature-box feature-box-5 feature-box-5-round',
            10 => 'feature-box feature-box-6',
            11 => 'feature-box feature-box-6 feature-box-6-round',
            12 => 'feature-box feature-box-7',
            13 => 'feature-box feature-box-8',
            14 => 'feature-box feature-box-9',
            15 => 'feature-box feature-box-9 feature-box-9-brown',
            16 => 'feature-box feature-box-10',
            17 => 'feature-box feature-box-11',
            18 => 'feature-box feature-box-12',
            19 => 'feature-box feature-box-13',
            20 => 'feature-box feature-box-14',
            21 => 'feature-box feature-box-14 feature-box-14-round',
            22 => 'feature-box feature-box-15',
            23 => 'feature-box feature-box-16',
            24 => 'feature-box feature-box-17',
            25 => 'feature-box feature-box-18',
            26 => 'feature-box feature-box-19',
            27 => 'feature-box feature-box-20',
            28 => 'feature-box feature-box-21',
            29 => 'feature-box feature-box-22',
            30 => 'feature-box feature-box-22 feature-box-22-round',
            31 => 'feature-box feature-box-23',
            32 => 'feature-box feature-box-24',
            33 => 'feature-box feature-box-25'
        );

        //Init the styles that have a title
        $title_styles = array('13','16','17','18','19','31','32','33');

        //Init switch to determine whether or not this box has a title
        $has_title = false;

        //Get current style
        $style = op_get_current_item($styles,$style);

        //Init styling strings
        $style_str = '';
        $title_str = '';

        //Determine whether or not we need a title
        if (in_array($style, $title_styles)){
            //Set the title switch to true
            $has_title = true;

            //Get the font styles
            $title_str = op_asset_font_style($atts,'font_');

            //Generate the style attribute string
            $title_str = $title_str == '' ? '': " style='".$title_str."'";
        }

        //Set up font
        $font = op_asset_font_style($atts,'content_font_');
        $original_font_str = $GLOBALS['OP_LIVEEDITOR_FONT_STR'];
        $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = (!empty($font) ? array('elements' => array('p','a'), 'style_str' => $font) : $GLOBALS['OP_LIVEEDITOR_FONT_STR']);

        //Set the temporary asset tag to the feature box
        self::$temp_tag = 'op_popup';

        //Get content
        $args = func_get_args();

        //Get the content for adding an element field
        $content = call_user_func_array(array('OptimizePress_Default_Assets', '_add_element_field'), $args);

        //Process content from above
        $content = op_process_asset_content($content);

        //Set the font back to the original string
        $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = ($GLOBALS['OP_LIVEEDITOR_DEPTH'] == 1 ? $original_font_str : $GLOBALS['OP_LIVEEDITOR_FONT_STR']);

        //Init the block style variable
        $block_style = '';

        //Set up attributes for the asset
        $chks = array(
            'width' => 'width',
            'top_margin' => 'margin-top',
            'bottom_margin' => 'margin-bottom'
        );

        //Loop through each attribute
        foreach($chks as $chk => $prob){
            //Add attribute to the block style string
            $block_style .= (!empty_allow_zero($$chk) ? $prob.': '.$$chk.'px;' : '');
        }

        //Add the style HTML attribute if we have styles in the block style string
        $block_style = (!empty($block_style) ? ' style=\''.$block_style.'\'' : $block_style);

        //Init the content style string
        $content_style = '';

        //Init the attributes for the content styling
        $chks = array(
            'top_padding' => 'padding-top',
            'bottom_padding' => 'padding-bottom',
            'left_padding' => 'padding-left',
            'right_padding' =>' padding-right'
        );

        //Loop through each attribute
        foreach($chks as $chk => $prob){
            //Add attribute to the content style string
            $content_style .= (!empty_allow_zero($$chk) ? $prob.': '.$$chk.'px;' : '');
        }

        //Add font to content style string
        $content_style .= $font;

        //Add the style HTML attribute if we have styles in the content style string
        $content_style = (!empty($content_style) ? ' style=\''.$content_style.'\'' : $content_style);

        //Return the generated HTML
        // return '
        //  <div class="'.$styles[$style].' feature-box-align-'.$alignment.'"'.$block_style.'>
        //      '.($has_title?'<h2 class="box-title"'.$title_str.'>'.$title.'</h2>':'').'
        //      <div class="feature-box-content cf"'.$content_style.'>'.$content.'</div>
        //  </div>
        // ';

        if (!defined('OP_LIVEEDITOR')) {
            wp_enqueue_script(OP_SN.'-popup', OP_JS.'popup'.OP_SCRIPT_DEBUG.'.js', array(OP_SN.'-noconflict-js'), OP_VERSION, true);
        }

        // fancybox options

        $popup_data = ' data-width="' . $popup_width . '" ';

        $popup_data .= ' data-open-effect="' . $popup_open_effect . '" ';
        $popup_data .= ' data-close-effect="' . $popup_close_effect . '" ';
        // $popup_data .= ' data-open-method="' . $popup_open_method . '" ';
        // $popup_data .= ' data-close-method="' . $popup_close_method . '" ';
        $popup_data .= ' data-open-speed="' . $popup_open_speed . '" ';
        $popup_data .= ' data-close-speed="' . $popup_close_speed . '" ';
        $popup_data .= ' data-border-color="' . $popup_border_color . '" ';
        $popup_data .= ' data-border-size="' . $popup_border_size . '" ';

        $popup_data .= ' data-padding-top="' . $popup_padding_top . '" ';
        $popup_data .= ' data-padding-bottom="' . $popup_padding_bottom . '" ';
        $popup_data .= ' data-padding-left="' . $popup_padding_left . '" ';
        $popup_data .= ' data-padding-right="' . $popup_padding_right . '" ';

        return '<div class="op-popup cf"' . $content_style . $popup_data . '>' . $content . '</div>';
    }




    /*
    * Function: feature_box
    * Description: Display function for the feature box
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    *   $content: Contains what is inside the shortcode tags
    */
    static function feature_box($atts,$content=''){
        //Extract the attributes into variables
        extract(shortcode_atts(array(
            'style' => '1',
            'title' => '',
            'letter_spacing' => '',
            'width' => '',
            'top_margin' => '',
            'bottom_margin' => '',
            'top_padding' => '',
            'bottom_padding' => '',
            'left_padding' => '',
            'right_padding' => '',
            'alignment' => ''
        ), $atts));

        //Init the styles array
        $styles = array(
            1 => 'feature-box feature-box-1',
            2 => 'feature-box feature-box-2',
            3 => 'feature-box feature-box-2 feature-box-2-blue',
            4 => 'feature-box feature-box-3',
            5 => 'feature-box feature-box-3 feature-box-3-blue',
            6 => 'feature-box feature-box-4',
            7 => 'feature-box feature-box-4 feature-box-4-brown',
            8 => 'feature-box feature-box-5',
            9 => 'feature-box feature-box-5 feature-box-5-round',
            10 => 'feature-box feature-box-6',
            11 => 'feature-box feature-box-6 feature-box-6-round',
            12 => 'feature-box feature-box-7',
            13 => 'feature-box feature-box-8',
            14 => 'feature-box feature-box-9',
            15 => 'feature-box feature-box-9 feature-box-9-brown',
            16 => 'feature-box feature-box-10',
            17 => 'feature-box feature-box-11',
            18 => 'feature-box feature-box-12',
            19 => 'feature-box feature-box-13',
            20 => 'feature-box feature-box-14',
            21 => 'feature-box feature-box-14 feature-box-14-round',
            22 => 'feature-box feature-box-15',
            23 => 'feature-box feature-box-16',
            24 => 'feature-box feature-box-17',
            25 => 'feature-box feature-box-18',
            26 => 'feature-box feature-box-19',
            27 => 'feature-box feature-box-20',
            28 => 'feature-box feature-box-21',
            29 => 'feature-box feature-box-22',
            30 => 'feature-box feature-box-22 feature-box-22-round',
            31 => 'feature-box feature-box-23',
            32 => 'feature-box feature-box-24',
            33 => 'feature-box feature-box-25'
        );

        //Init the styles that have a title
        $title_styles = array('13','16','17','18','19','31','32','33');

        //Init switch to determine whether or not this box has a title
        $has_title = false;

        //Get current style
        $style = op_get_current_item($styles,$style);

        //Init styling strings
        $style_str = '';
        $title_str = '';

        //Determine whether or not we need a title
        if (in_array($style, $title_styles)){
            //Set the title switch to true
            $has_title = true;

            //Get the font styles
            $title_str = op_asset_font_style($atts,'font_');

            //Generate the style attribute string
            $title_str = $title_str == '' ? '': " style='".$title_str."'";
        }

        //Set up font
        $font = op_asset_font_style($atts,'content_font_');
        $original_font_str = $GLOBALS['OP_LIVEEDITOR_FONT_STR'];
        $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = (!empty($font) ? array('elements' => array('p','a'), 'style_str' => $font) : $GLOBALS['OP_LIVEEDITOR_FONT_STR']);

        //Set the temporary asset tag to the feature box
        self::$temp_tag = 'feature_box';

        //Get content
        $args = func_get_args();

        //Get the content for adding an element field
        $content = call_user_func_array(array('OptimizePress_Default_Assets', '_add_element_field'), $args);

        //Process content from above
        $content = op_process_asset_content($content);

        //Set the font back to the original string
        $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = ($GLOBALS['OP_LIVEEDITOR_DEPTH'] == 1 ? $original_font_str : $GLOBALS['OP_LIVEEDITOR_FONT_STR']);

        //Init the block style variable
        $block_style = '';

        //Set up attributes for the asset
        $chks = array(
            'width' => 'width',
            'top_margin' => 'margin-top',
            'bottom_margin' => 'margin-bottom'
        );

        //Loop through each attribute
        foreach($chks as $chk => $prob){
            //Add attribute to the block style string
            $block_style .= (!empty_allow_zero($$chk) ? $prob.': '.$$chk.'px;' : '');
        }

        //Add the style HTML attribute if we have styles in the block style string
        $block_style = (!empty($block_style) ? ' style=\''.$block_style.'\'' : $block_style);

        //Init the content style string
        $content_style = '';

        //Init the attributes for the content styling
        $chks = array(
            'top_padding' => 'padding-top',
            'bottom_padding' => 'padding-bottom',
            'left_padding' => 'padding-left',
            'right_padding' =>' padding-right'
        );

        //Loop through each attribute
        foreach($chks as $chk => $prob){
            //Add attribute to the content style string
            $content_style .= (!empty_allow_zero($$chk) ? $prob.': '.$$chk.'px;' : '');
        }

        //Add font to content style string
        $content_style .= $font;

        //Add the style HTML attribute if we have styles in the content style string
        $content_style = (!empty($content_style) ? ' style=\''.$content_style.'\'' : $content_style);

        //Return the generated HTML
        return '
            <div class="'.$styles[$style].' feature-box-align-'.$alignment.'"'.$block_style.'>
                '.($has_title?'<h2 class="box-title"'.$title_str.'>'.$title.'</h2>':'').'
                <div class="feature-box-content cf"'.$content_style.'>'.$content.'</div>
            </div>
        ';
    }

    /*
    * Function: feature_box_creator
    * Description: Display function for the feature box creator asset
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    *   $content: Contains what is inside the shortcode tags
    */
    static function feature_box_creator($atts, $content=''){
        //Extract the attributes into variables
        $data = shortcode_atts(array(
            'style' => '1',
            'letter_spacing' => '',
            'width' => '',
            'top_margin' => '',
            'bottom_margin' => '',
            'top_padding' => '',
            'bottom_padding' => '',
            'left_padding' => '',
            'right_padding' => '',
            'alignment' => '',
            'bg_color' => '',
            'bg_color_end' => '',
            'border_color' => '',
            'border_weight' => '',
            'border_radius' => '',
            'border_style' => '',
            'content' => '',
            'font' => '',
            'id' => op_generate_id()
        ), $atts);

        //Set the temporary asset tag to the feature box creator
        self::$temp_tag = 'feature_box_creator';

        //Instantiate the OP Fonts class
        $op_fonts = new OptimizePress_Fonts;

        //Add font style includes to page
        $op_fonts->add_font($atts['font_font']);

        //Get font style string for content
        $data['font'] = str_replace('"', '\'', op_asset_font_style($atts, 'font_'));

        //Set up font
        $original_font_str = $GLOBALS['OP_LIVEEDITOR_FONT_STR'];
        $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = (!empty($data['font']) ? array('elements' => array('p', 'a'), 'style_str' => $data['font']) : $GLOBALS['OP_LIVEEDITOR_FONT_STR']);

        //Get content
        $args = func_get_args();

        //Get the content for adding an element field
        $content = call_user_func_array(array('OptimizePress_Default_Assets', '_add_element_field'), $args);

        //Process content from above
        $data['content'] = op_process_asset_content($content);

        //Set the font back to the original string
        $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = ($GLOBALS['OP_LIVEEDITOR_DEPTH'] == 1 ? $original_font_str : $GLOBALS['OP_LIVEEDITOR_FONT_STR']);

        //Clear out any current templates
        _op_tpl('clear');

        //Return the HTML from the template
        return _op_tpl('_load_file', OP_ASSETS.'tpls/feature_box_creator/style_'.$data['style'].'.php', $data, true);
    }

    /*
    * Function: guarantee_content
    * Description: Wrapper for guarantee box asset
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    *   $content: Contains what is inside the shortcode tags
    */
    static function guarantee_content($atts,$content){
        return self::guarantee_box($atts,$content);
    }

    /*
    * Function: guarantee_box
    * Description: Display function for the guarantee box
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    *   $content: Contains what is inside the shortcode tags
    */
    static function guarantee_box($atts,$content=''){
        //Extract the attributes into variables
        extract(shortcode_atts(array(
            'style' => '1',
            'title' => '',
        ), $atts));

        //Set the asset tag to be the guarantee box
        self::$temp_tag = 'guarantee_box';

        //Get the extra arguments passed into the function
        $args = func_get_args();

        //Process attributes and settings to generate the HTML
        switch($style){
            case 10:
            case 11:
            case 12:
            case 13:
                //Set up the data array for use with a template
                $data = array(
                    'title' => $title,
                    'title_style' => '',
                );

                //Clear out any current templates
                _op_tpl('clear');

                //Get the font styles
                $title_font = op_asset_font_style($atts);

                //Add HTML attribute for style if styles exist
                $data['title_style'] = (!empty($title_font) ? ' style=\''.$title_font.'\'' : '');

                //Process font settings
                $font = op_asset_font_style($atts,'content_font_');
                $original_font_str = $GLOBALS['OP_LIVEEDITOR_FONT_STR'];

                //Add style attribute for font, if font exists
                if (!empty($font)){
                    $style_str = ' style=\''.$font.'\'';
                    $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = array('elements' => array('p','a'), 'style_str' => $font);
                }

                //Process content
                $data['content'] = op_process_asset_content(op_texturize(do_shortcode(op_clean_shortcode_content($content))));

                //Return to the original string
                $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = $original_font_str;

                //Return the HTML from the template
                return _op_tpl('_load_file',OP_ASSETS.'tpls/guarantee_box/style_'.$style.'.php',$data,true);
                break;
            default:
                //If the style needs no processing, simply return the HTML
                return '<img src="'.OP_ASSETS_URL.'images/guarantee_box/previews/guarantee_'.$style.'.png" alt="" class="guarantee-box-'.$style.'" />';
        }
    }

    /*
    * Function: headline
    * Description: Display function for the headline asset
    * Parameters:
    *   $atts: Contains all the attributes for this asset
    *   $content: Contains what is inside the shortcode tags
    */
    static function headline($atts,$content=''){
        //Extract the attributes into variables
        extract(shortcode_atts(array(
            'style' => '1',
            'align' => 'left',
            'letter_spacing' => '',
            'line_height' => '',
            'highlight' => '',
            'top_margin' => '',
            'bottom_margin' => '',
        ), $atts));

        //Init the styles array
        $styles = array(
            1 => '',
            2 => 'headline-style-1',
            3 => 'headline-style-2',
            4 => 'headline-style-3',
            5 => 'headline-style-4',
            6 => 'headline-style-5',
            7 => 'headline-style-6',
            8 => 'headline-style-7',
            9 => 'headline-style-8',
            10 => 'headline-style-9',
            11 => 'headline-style-10',
            12 => 'headline-style-11',
            13 => 'headline-style-12',
            14 => 'headline-style-13',
            15 => 'headline-style-14',
            16 => 'headline-style-15',
            17 => 'headline-style-16',
            18 => 'headline-style-17'
        );

        //Process content with regexes
        $content = preg_replace('/<\/p>\s*<p>/i','<br /><br />',$content);

        //Get the current style
        $style = op_get_current_item($styles,$style);

        //Ensure we have a style set
        if(isset($styles[$style])){
            //Set the path for the images
            $path = OP_ASSETS_URL.'images/headline/';

            //Init flag for surrounding headline with span tag
            $span = false;

            //Init the before and after HTML variables
            $before = $after = '';

            //Set the fade images
            $fadeimgs = '<img src="'.$path.'fade-left.png" alt="fade-left" width="120" height="10" class="fade-left" /><img src="'.$path.'fade-right.png" alt="fade-right" width="120" height="10" class="fade-right" />';

            //Init the templates array that determines whether we use spans and before and after tags
            $tpls = array(
                8 => array('span' => true),
                10 => array('span' => true),
                14 => array('span' => true, 'before' => $fadeimgs),
                15 => array('span' => true, 'before' => $fadeimgs)
            );

            //If this style is set, extract it's variables
            if (isset($tpls[$style])) extract($tpls[$style]);

            //Get and process content
            $str = do_shortcode(op_clean_shortcode_content($content));

            //Surround headline with span if style has one and any before and after tags
            $str = $before.($span ? '<span>'.$str.'</span>' : $str).$after;

            //Init the style string
            $style_str = (!empty($styles[$style]) ? ' class="'.$styles[$style].'"' : '');

            //Get font settings
            $font = op_asset_font_style($atts);

            //Init styling properties
            $chks = array(
                'align' => 'text-align',
                'line_height' => array('line-height','px'),
                'highlight' => 'background-color',
                'top_margin' => array('margin-top','px'),
                'bottom_margin' => array('margin-bottom','px')
            );

            //Loop through each property
            foreach($chks as $var => $chk){
                if (!empty($$var)) $font .= (is_array($chk) ? $chk[0].':'.$$var.$chk[1].';' : $chk.':'.$$var.';');
            }

            //Surroung styling with style HTML attribute, if styles exist
            $style_str .= (!empty($font) ? ' style=\''.$font.'\'' : '');

            //Init the return HTML
            $return_html = '';

            //Generate return HTML based on style
            switch($style){
                case 17:
                case 18:
                    $return_html = '
                        <table'.$style_str.'>
                            <tr>
                                <td class="stroke">
                                    <div></div>
                                </td>
                                <td class="headline">
                                    <h2>'.$str.'<h2>
                                </td>
                                <td class="stroke">
                                    <div></div>
                                </td>
                            </tr>
                        </table>
                    ';
                    break;
                default:
                    $return_html = '<h2'.$style_str.'>'.$str.'</h2>';
            }

            //Return the HTML
            return $return_html;
        }
    }

    static function hyperlink($atts,$content=''){
        extract(shortcode_atts(array(
            'href' => '',
            'align' => 'left',
            'new_window' => 'N',
        ), $atts));
        $content = op_clean_shortcode_content($content);
        $font = op_asset_font_style($atts);
        $style_str = '';
        if($font != ''){
            $style_str .= ' style=\''.$font.'\'';
        }
        return '<p style="text-align:'.$align.'"><a href="'.$href.'"'.$style_str.($new_window=='Y'?' target="_blank"':'').'>'.$content.'</a></p>';
    }

    static function images($atts)
    {
        $atts = shortcode_atts(array(
            'style' => '',
            'image' => '',
            'large_image' => '',
            'link_url' => '',
            'new_window' => 'N',
            'caption' => '',
            'align' => 'center',
            'custom_width' => 'N',
            'width' => '',
            'custom_width_val' => '',
            'top_margin' => '',
            'bottom_margin' => '',
            'left_margin' => '',
            'right_margin' => '',
            'alt_text' => '',
            'full_width' => ''
        ), $atts);

        extract($atts);

        //Set the custom or fixed width and convert to integer
        $width = intval(($custom_width=='Y' ? $custom_width_val : $width));

        //Set up style specific widths
        switch($style){
            case 3:
            case 5:
                $width += 18;
                break;
            case 4:
                $width += 42;
                break;
        }

        //Make sure the image is not empty before we continue
        if(!empty($image)){
            //Set up the image tag
            $img = '<img alt="'.$alt_text.'" src="'.$image.'" border="0"'.($full_width=='Y' ? ' class="full-width"' : '').' />';

            //Set the link if it exists
            if(!empty($link_url)){
                if ($large_image != '') {
                    $caption = '<a href="'.$link_url.'"'.($new_window=='Y'?' target="_blank"':'').'>'.$caption.'</a>';
                } else {
                    $img = '<a href="'.$link_url.'"'.($new_window=='Y'?' target="_blank"':'').'>'.$img.'</a>';
                }
            }

            $addPrettyPhoto = false;
            //Set the large image if it exists
            if(!empty($large_image)){
                if (false === self::$add_pretty_photo) {
                    self::$add_pretty_photo = true;
                }
                $img = '<a href="'.$large_image.'" rel="prettyPhoto">'.$img.'</a>';
            }

            //Set the inner frame div based on specific styles
            $img = (intval($style) > 2 ? '<div class="frame-style-inner">'.$img.'</div>' : $img);

            //Init the style string
            $style_str = '';

            //Check for styles and add them if they are set properly
            $chks = array('width' => 'width','top_margin' => 'margin-top', 'bottom_margin' => 'margin-bottom', 'right_margin' => 'margin-right', 'left_margin' => 'margin-left');
            foreach($chks as $chk => $prob){
                if($chk != '') $style_str .= (($chk == 'right_margin' || $chk == 'left_margin') && $align == 'center' ? $prob.':auto;' : $prob.':'.$$chk.'px;');
            }

            //Set alignment option
            $style_str .= ($align!='center' ? 'float: '.$align.';' : '');

            //Add the style attribute to the style string if it is not empty
            $style_str = (!empty($style_str) ? ' style=\''.$style_str.'\'' : '');

            //Add the frame style if the style exists
            $frame_style = (!empty($style) ? ' frame-style-'.$style : '');


            //Finally return the processed HTML
            $output = '
                <div class="image-caption'.$frame_style.'"'.$style_str.'>
                    '.$img.(empty($caption) ? '' : '<p class="wp-caption-text">'.$caption.'</p>').'
                </div>
            ';
        }

        return $output;
    }

    static function img_alert($atts,$content=''){
        extract(shortcode_atts(array(
            'image' => '',
            'text' => '',
        ), $atts));

        if($image != ''){
            $realImagePath = str_replace(site_url(), $_SERVER['DOCUMENT_ROOT'], $image);
            list($width, $height) = @getimagesize($realImagePath);
            $id = 'op_assets_core_img_alert_'.op_generate_id();

            return '
                <div class="img-alert-container" style="width: '.$width.'px;">
                    <img id="'.$id.'" alt="" src="'.$image.'" border="0"'.(!empty($width) && !empty($height) ? ' width="'.$width.'" height="'.$height.'"' : '').' />
                </div>
                <script type="text/javascript">
                    (function ($) {
                        //$("#'.$id.'").live("click", function(){
                        $("body").on("click", "#'.$id.'", function(){
                            alert("'.$text.'");
                        });
                    })(opjq);
                </script>
            ';
        }
    }

    static function img_text_aside($atts,$content=''){
        $data = shortcode_atts(array(
            'style' => 1,
            'image' => '',
            'image_alignment' => 'right',
            'headline' => '',
            'text' => '',
            'alignment' => 'left'
        ), $atts);

        if($data['image'] != ''){
            $realImagePath = str_replace(site_url(), $_SERVER['DOCUMENT_ROOT'], $data['image']);
            list($data['img_width'], $data['img_height']) = @getimagesize($realImagePath);
            $data['id'] = 'op_assets_core_img_text_aside_'.op_generate_id();

            //Clear the template
            _op_tpl('clear');

            //Process the new template and load it
            return _op_tpl('_load_file',OP_ASSETS.'tpls/img_text_aside/style_'.$data['style'].'.php',$data,true);
        }
    }

    static function live_search($atts)
    {
        extract(shortcode_atts(array(
            'style' => '1',
            'all_pages' => '',
            'product' => '',
            'category' => '',
            'subcategory' => '',
            'placeholder' => __('Search...', OP_SN),
        ),$atts));

        $html = '
        <form class="op-live-search-form" action="'.get_bloginfo('url').'" method="get">
            <div class="op-live-search-container">
                <input type="hidden" name="op_live_search_style" class="op_live_search_style" value="'.$style.'" />
                <input type="hidden" name="op_live_search_all_pages" class="op_live_search_all_pages" value="'.$all_pages.'" />
                <input type="hidden" name="op_live_search_product" class="op_live_search_product" value="'.$product.'" />
                <input type="hidden" name="op_live_search_category" class="op_live_search_category" value="'.$category.'" />
                <input type="hidden" name="op_live_search_subcategory" class="op_live_search_subcategory" value="'.$subcategory.'" />
                <input class="op-live-search-input" name="s" autocomplete="off" placeholder="'.$placeholder.'" />
            </div>
        </form>
        <script>
        var ajaxUrl = "'.admin_url('admin-ajax.php').'";
        </script>
        ';
        wp_enqueue_script('live_search', OP_JS.'live_search'.OP_SCRIPT_DEBUG.'.js', array(OP_SN.'-noconflict-js'), OP_VERSION, true);
        return $html;
    }

    static function navigation($atts){
        extract(shortcode_atts(array(
            'style' => '1',
            'nav_id' => '0',
            'title' => '',
            'left_margin' => '0',
            'right_margin' => '0',
        ),$atts));
        $title_styles = array(4, 6, 7, 8, 9, 10);
        $title_span_styles = array(9, 10);
        $js_styles = array(6, 7, 8, 9, 10);
        $left = intval($left_margin);
        $right = intval($right_margin);
        $style_str = '';
        if($left > 0) $style_str .= 'margin-left:'.$left.'px;';
        if($right > 0) $style_str .= 'margin-right:'.$right.'px;';
        $font = op_asset_font_style($atts);
        $element_id = 'asset-navigation-'.(!empty($title) ? op_safe_string($title) : op_generate_id());
        $title_html = (!empty($title) && in_array($style, $title_styles) ? '<li class="title">'.(in_array($style, $title_span_styles) ? '<span>'.$title.'</span>' : '<h2>'.$title.'</h2>').'</li>' : '');
        $nav_html = '
            <ul>
                '.$title_html.wp_nav_menu( array( 'menu'=>$nav_id, 'items_wrap' => '%3$s', 'container' => false, 'echo' => false, 'depth'=>2 ) ).'
            </ul>
        ';

        $inner_html = ($style == 5 ? '<div class="navigation-sidebar-inner">'.$nav_html.'</div>' : $nav_html);

        $js = (in_array($style, $js_styles) ? "
            <script type=\"text/javascript\">
            (function ($) {
                $( document ).ready(function() {
                    $('.navigation-sidebar-".$style." li a').unbind('click').click(function(e) {
                        if ($(this).closest('li').has('ul').length) {
                            e.preventDefault();

                            var li = $(this).closest('li');
                            li.find(' > ul').slideToggle('fast');
                            $(this).toggleClass('active');
                        }
                    });
                });
            }(opjq));
            </script>
        " : '');

        return '
            <style>#'.$element_id.'.navigation-sidebar-'.$style.' > ul > li > a{ '.$font.' }</style>
            <div id="'.$element_id.'" class="navigation-sidebar navigation-sidebar-'.$style.'"'.($style_str == ''?'':' style=\''.$style_str.'\'').'>
                '.$inner_html.'
            </div>
        '.$js;
    }

    static function news_bar($atts,$content=''){
        //Initialize variables
        $data = shortcode_atts(array(
            'style' => 1,
            'color' => '',
            'feature_text' => '',
            'main_text' => ''
        ), $atts);

        $data['id'] = 'news-bar-'.op_generate_id();

        //Clear the template
        _op_tpl('clear');

        //Process the new template and load it
        return _op_tpl('_load_file',OP_ASSETS.'tpls/news_bar/style_'.$data['style'].'.php',$data,true);
    }

    static function one_time_offer($atts){
        extract(shortcode_atts(array(
            'style' => '1',
        ), $atts));
        return '<div class="onetime-offer"><img alt="" src="'.OP_ASSETS_URL.'images/one_time_offer/'.$style.'" /></div>';
    }

    static function optin_box($atts,$content='')
    {
        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(false);
        }

        $uidOpBox = 'optbox_' . md5(serialize($atts) . $content . (int)defined('OP_LIVEEDITOR'));

        if (false === $cached = get_transient('el_' . $uidOpBox)) {
            $oldAtts = $atts;
            $atts = shortcode_atts(array(
                'integration_type' => 'custom',
                'email_address' => '',
                'redirect_url' => '',
                'style' => '1',
                'action' => '',
                'new_window' => 'N',
                'disable_name' => 'N',
                'method' => 'post',
                'submit' => '',
                'email_field' => '',
                'email_default' => '',
                'email_order' => 0,
                'name_field' => '',
                'name_default' => '',
                'name_order' => 0,
                'top_color' => '',
                'thank_you_page' => '',
                'action_page' => '',
                'list' => '',
                'width' => '',
                'margin_top' => '',
                'margin_right' => '',
                'margin_bottom' => '',
                'margin_left' => '',
                'alignment' => 'center',
                'button_below' => 'N',
                'gotowebinar' => null,
                'double_optin' => 'N',
            ), $atts);
            extract($atts);

            $data = array(
                'content' => array(
                    'headline' => '',
                    'paragraph' => '',
                    'privacy' => '',
                ),
                'fields' => array(),
                'submit_button' => '',
                'hidden_str' => '',
                'extra_fields' => array(),
                'top_color' => $top_color,
                'width' => $width,
                'margin_top' => $margin_top,
                'margin_right' => $margin_right,
                'margin_bottom' => $margin_bottom,
                'margin_left' => $margin_left,
                'box_alignment' => $alignment
            );

            $mc = preg_match_all('/'.op_shortcode_regex('optin_box_hidden|optin_box_field|optin_box_button').'/s',$content,$matches);
            $buttons = 0;
            if($mc > 0){
                for($i=0;$i<$mc;$i++){
                    switch($matches[2][$i]){
                        case 'optin_box_hidden':
                            $data['hidden_str'] .= op_clean_shortcode_content($matches[5][$i]);
                            break;
                        case 'optin_box_field':
                            $field = shortcode_atts(array(
                                'name' => '',
                            ), shortcode_parse_atts($matches[3][$i]));
                            if($field['name'] != ''){
                                $data['content'][$field['name']] = op_clean_shortcode_content($matches[5][$i]);
                                if($field['name'] == 'paragraph'){
                                    $data['content'][$field['name']] = wpautop(op_texturize(base64_decode($data['content'][$field['name']])));
                                }
                            }
                            break;
                        case 'optin_box_button':
                            $button_atts = shortcode_parse_atts($matches[3][$i]);
                            $button_atts['element_type'] = 'button';
                            $button_content = $matches[5][$i];
                            $type = op_get_var($button_atts,'type',1);
                            if($type == '0'){
                                $uid = md5('btn_0_' . $button_content);
                                if (false === $buttonMarkup = get_transient('el_' . $uid)) {
                                    $buttonMarkup = op_sl_parse('button_0', $button_content);
                                    /*
                                     * Save only when there is some content returned
                                     */
                                    if (is_string($buttonMarkup) && 0 === strpos($buttonMarkup, '##')) {
                                        $buttonMarkup = substr($buttonMarkup, 2);
                                    } elseif (!empty($buttonMarkup)) {
                                        set_transient('el_' . $uid, $buttonMarkup, OP_SL_ELEMENT_CACHE_LIFETIME);
                                    }
                                }
                                $data['submit_button'] = $buttonMarkup;
                            } else {
                                $func = 'button_'.$type;
                                $data['submit_button'] = call_user_func_array(array('OptimizePress_Default_Assets',$func),array($button_atts,$button_content));
                            }
                            /*
                             * Used on some templates as "textual link"
                             */
                            if (op_get_var($button_atts, 'button_below') === 'Y') {
                                $data['link_button'] = '<button type="submit" class="default-link">'.$button_content.'</button>';
                            } else {
                                $data['link_button'] = '';
                            }
                            break;
                    }
                }
            }

            $has_name = true;
            if($integration_type == 'email'){
                $has_name = false;
            }

            if (!$has_name) {
                $atts['action'] = '%%redirect_url%%';
                $atts['method'] = 'post';
            }
            $atts['op_current_url'] = '%%redirect_url%%';
            $atts['data'] = $data;
            $atts['no_name_styles'] = array('1','4','5','6','9','10');
            $atts['site_url_process_optin_form'] = get_bloginfo('url') . '/process-optin-form/';
            $atts['atts'] = $oldAtts;
            $atts['has_name'] = $has_name;

            $returnedData = op_sl_parse('optin_box', $atts);
            if (false === is_array($returnedData) && empty($returnedData)) {
                return;
            }
            extract($returnedData);

            if (!isset($required_fields)) {
                $required_fields = array();
            }

            $required = ' required="required"';
            /*
             * We need this check to remove required attribute (it was firing in feature box element)
             */
            if (defined('OP_LIVEEDITOR')) {
                $required = '';
            }

            $replacement_fields = array();
            foreach($extra_fields as $name => $value){
                $requiredField = in_array($name, $required_fields) ? $required : '';
                $data['extra_fields'][$name] = '<div class="text-box"><input type="text"' . $requiredField . ' name="'.$name.'" placeholder="'.$value.'" value="%%' . $name . '%%" /></div>';
                $replacement_fields[] = $name;
            }

            if(!$has_name){
                $fields['email']['name'] = 'email';
                $has_name_field && $fields['name']['name'] = 'name';
                $hidden = array(
                    'email_to' => $email_address,
                    'redirect_url' => $redirect_url,
                    'extra_fields' => $extra_fields,
                    'fields' => $fields
                );
                $data['hidden_str'] .= '<input type="hidden" name="op_optin_form_data" value="'.op_attr(base64_encode(serialize($hidden))).'" /><input type="hidden" name="op_optin_form" value="Y" />';
            }

            $data['hidden_str'] = empty($data['hidden_str']) ? '' : '<div style="display:none">'.$data['hidden_str'].'</div>';

            foreach($fields as $name => $info){
                $input_container_start_html = ($style!=12 && $style!=15 && $style!=19 && $style!=20 && $style!=21 && $style!=22 && $style!=23 && $style!=24 ? '<div class="text-box '.$name.'">' : '');
                $input_container_end_html = ($style!=12 && $style!=15 && $style!=19 && $style!=20 && $style!=21 && $style!=22 && $style!=23 && $style!=24 ? '</div>' : '');
                $requiredField = in_array($info['name'] . '_field', $required_fields) ? $required : '';
                if ($name === 'email') {
                    $data['fields'][$name.'_field'] = $input_container_start_html.'<input type="email"' . $requiredField . ' name="'.$info['name'].'"'.($info['text']==''?'':' placeholder="'.$info['text'].'"').' value="%%' . $info['name'] . '%%" />'.$input_container_end_html;
                } else {
                    $data['fields'][$name.'_field'] = $input_container_start_html.'<input type="text"' . $requiredField . ' name="'.$info['name'].'"'.($info['text']==''?'':' placeholder="'.$info['text'].'"').' value="%%' . $info['name'] . '%%" />'.$input_container_end_html;
                }
                $replacement_fields[] = $info['name'];
            }

            if (!isset($data['extra_fields']) || !is_array($data['extra_fields'])) {
                $data['extra_fields'] = array();
            }

            if (isset($order)) {
                $data['order'] = $order;
            }

            _op_tpl('clear');
            $template = _op_tpl('_load_file',OP_ASSETS.'tpls/optin_box/style_'.$style.'.php',$data,true);

            $cached = array(
                'template' => $template,
                'replacement_fields' => $replacement_fields,
            );
            set_transient('el_' . $uidOpBox, $cached, OP_SL_ELEMENT_CACHE_LIFETIME);
        }

        /*
         * Loading validation script
         */
        op_validation_script();

        if (is_array($cached)) {
            $html = str_replace('%%redirect_url%%', op_current_url(), $cached['template']);
            foreach ($cached['replacement_fields'] as $field) {
                $html = str_replace('%%' . $field . '%%', getOptinUrlValue($field), $html);
            }
        } else {
            $html = str_replace('%%redirect_url%%', op_current_url(), $cached);
        }

        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(true);
        }

        return $html;
    }

    /*function optin_modal($atts,$content=''){
        $no_name_styles = array();
        $no_textbox_styles = array('1', '2');
        extract(shortcode_atts(array(
            'integration_type' => 'custom',
            'email_address' => '',
            'redirect_url' => '',
            'style' => '1',
            'action' => '',
            'new_window' => 'N',
            'disable_name' => 'N',
            'method' => 'post',
            'submit' => '',
            'email_field' => '',
            'email_default' => '',
            'name_field' => '',
            'name_default' => '',
            'top_color' => '',
            'thank_you_page' => '',
            'action_page' => '',
            'list' => '',
            'width' => '',
            'margin_top' => '',
            'margin_right' => '',
            'margin_bottom' => '',
            'margin_left' => '',
            'alignment' => 'center',
            'button_below' => 'N',
            'link_title' => '',
            'headline' => '',
            'paragraph' => '',
            'privacy' => ''
        ), $atts));
        $hidden_str = '';
        $data = array(
            'content' => array(
                'headline' => '',
                'paragraph' => '',
                'privacy' => '',
            ),
            'fields' => array(),
            'submit_button' => '',
            'hidden_str' => '',
            'extra_fields' => array(),
            'top_color' => $top_color,
            'width' => $width,
            'margin_top' => $margin_top,
            'margin_right' => $margin_right,
            'margin_bottom' => $margin_bottom,
            'margin_left' => $margin_left,
            'box_alignment' => $alignment,
            'link_title' => $link_title,
            'headline' => $headline,
            'paragraph' => $paragraph,
            'privacy' => $privacy,
            'close_class' => OP_CSS_CLASS_CLOSE_MODAL
        );

        $mc = preg_match_all('/'.op_shortcode_regex('optin_modal_hidden|optin_modal_field|optin_modal_button').'/s',$content,$matches);
        $buttons = 0;
        if($mc > 0){
            for($i=0;$i<$mc;$i++){
                switch($matches[2][$i]){
                    case 'optin_modal_hidden':
                        $data['hidden_str'] .= op_clean_shortcode_content($matches[5][$i]);
                        break;
                    case 'optin_modal_field':
                        $field = shortcode_atts(array(
                            'name' => '',
                        ), shortcode_parse_atts($matches[3][$i]));
                        if($field['name'] != ''){
                            $data['content'][$field['name']] = op_clean_shortcode_content($matches[5][$i]);
                            if($field['name'] == 'paragraph'){
                                $data['content'][$field['name']] = wpautop(op_texturize($data['content'][$field['name']]));
                            }
                        }
                        break;
                    case 'optin_modal_button':
                        $button_atts = shortcode_parse_atts($matches[3][$i]);
                        $button_atts['element_type'] = 'button';
                        $button_content = $matches[5][$i];
                        $type = op_get_var($button_atts,'type',1);
                        if($type == '0'){
                            //$data['submit_button'] = '<button type="submit" class="default-button"><span>'.$button_content.'</span></button>';
                            $data['submit_button'] = '
                                <a href="#" class="css-button-block css-button style-2">
                                    <span class="text">'.$button_content.'</span>
                                    <div class="shine"></div>
                                    <div class="hover"></div>
                                    <div class="active"></div>
                                </a>
                            ';
                        } else {
                            $func = 'button_'.$type;
                            $data['submit_button'] = str_replace(array('<button', '</button>'), array('<a href="#"', '</a>'), call_user_func_array(array('OptimizePress_Default_Assets',$func),array($button_atts,$button_content)));
                        }
                        /*
                         * Used on some templates as "textual link"
                         *//*
                        if (op_get_var($button_atts, 'button_below') === 'Y') {
                            $data['link_button'] = '<button type="submit" class="default-link">'.$button_content.'</button>';
                        } else {
                            $data['link_button'] = '';
                        }
                        break;
                }
            }
        }
        $has_name_field = false;
        $fields = array('email'=>array('name'=>$email_field,'text'=>$email_default));
        if (!in_array($style,$no_name_styles) && $disable_name != 'Y'){
            $has_name_field = true;
            $fields['name'] = array('name'=>$name_field,'text'=>$name_default);
        }

        $has_name = true;
        if($integration_type == 'email'){
            $has_name = false;
        }

        if (empty($thank_you_page)) {
            $thank_you_page = op_current_url();
        }

        $extra_fields = array();
        $counter = 1;
        while(true){
            $found = false;
            if($has_name){
                if(isset($atts['extra_field_'.$counter.'_name']) && isset($atts['extra_field_'.$counter.'_title'])){
                    $extra_fields[$atts['extra_field_'.$counter.'_name']] = $atts['extra_field_'.$counter.'_title'];
                    $counter++;
                    $found = true;
                }
            } else {
                if(isset($atts['extra_field_'.$counter])){
                    $extra_fields['op_extra_'.$counter] = $atts['extra_field_'.$counter];
                    $counter++;
                    $found = true;
                }
            }
            if(!$found){
                break;
            }
        }
        foreach($extra_fields as $name => $value){
            $data['extra_fields'][] = '<div class="text-box"><input type="text" name="'.$name.'" placeholder="'.$value.'" value="' . getOptinUrlValue($name) . '" /></div>';
        }

        if(!$has_name){
            $fields['email']['name'] = 'email';
            $has_name_field && $fields['name']['name'] = 'name';
            $hidden = array(
                'email_to' => $email_address,
                'redirect_url' => $redirect_url,
                'extra_fields' => $extra_fields,
                'fields' => $fields
            );
            $data['hidden_str'] .= '<input type="hidden" name="op_optin_form_data" value="'.op_attr(base64_encode(serialize($hidden))).'" /><input type="hidden" name="op_optin_form" value="Y" />';
            $action = op_current_url();
            $method = 'post';
        }

        switch ($integration_type) {
            case 'infusionsoft':
                $action = $action_page;
                $data['hidden_str'] .= '<input type="hidden" name="provider" value="' . $integration_type . '" />';
                $data['hidden_str'] .= '<input type="hidden" name="redirect_url" value="' . $thank_you_page . '" />';
                $data['hidden_str'] .= '<input type="hidden" name="list" value="' . $list . '" />';
                break;
            case 'aweber':
            case 'mailchimp':
            case 'icontact':
            case 'getresponse':
                $action = get_bloginfo('url') . '/process-optin-form/';
                $data['hidden_str'] .= '<input type="hidden" name="provider" value="' . $integration_type . '" />';
                $data['hidden_str'] .= '<input type="hidden" name="redirect_url" value="' . $thank_you_page . '" />';
                $data['hidden_str'] .= '<input type="hidden" name="list" value="' . $list . '" />';
                break;
        }

        foreach($fields as $name => $info){
            $input_container_start_html = (!in_array($style, $no_textbox_styles) ? '<div class="text-box '.$name.'">' : '');
            $input_container_end_html = (!in_array($style, $no_textbox_styles) ? '</div>' : '');
            $data['fields'][$name.'_field'] = $input_container_start_html.'<input type="text" name="'.$info['name'].'"'.($info['text']==''?'':' placeholder="'.$info['text'].'"').' value="' . getOptinUrlValue($info['name']) . '" />'.$input_container_end_html;
        }

        $data['hidden_str'] = empty($data['hidden_str']) ? '' : '<div style="display:none">'.$data['hidden_str'].'</div>';
        $data['form_attrs'] = ' action="'.$action.'" method="'.$method.'" class="cf"'.($new_window=='Y'?' target="_blank"':'');
        $data['id'] = 'optin-modal-'.op_generate_id();
        _op_tpl('clear');
        return _op_tpl('_load_file',OP_ASSETS.'tpls/optin_modal/style_'.$style.'.php',$data,true);
    }*/

    static function order_box($atts,$content=''){
        $data = shortcode_atts(array(
            'style' => 1,
            'title' => '',
            'header' => '',
            'width' => '',
            'alignment' => 'center'
        ), $atts);
        $args = func_get_args();
        self::$temp_tag = 'order_box';
        $data['content'] = call_user_func_array(array('OptimizePress_Default_Assets','_add_element_field'),$args);


        $font = op_asset_font_style($atts);
        $original_font_str = $GLOBALS['OP_LIVEEDITOR_FONT_STR'];
        if($font != ''){
            $style_str = ' style=\''.$font.'\'';
            $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = array('elements' => array('p','a'), 'style_str' => $font);
        }
        /*
         * Fix for centering order box
         */
        $data['box_style'] = '';
        if (!empty($data['width'])) {
                $data['box_style'] = ' style="width:' . $data['width'] . 'px;';
            if ($data['alignment'] === 'center') {
                $data['box_style'] .= 'margin-left:auto;margin-right:auto;';
            } else {
                $data['box_style'] .= 'float:' . $data['alignment'] . ';';
            }
            $data['box_style'] .= '"';
        }

        $data['content'] = op_process_asset_content($data['content']);
        $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = $original_font_str;

        _op_tpl('clear');
        return _op_tpl('_load_file',OP_ASSETS.'tpls/order_box/style_'.$data['style'].'.php',$data,true);
    }

    static function order_step_graphics($atts)
    {
        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(false);
        }

        $uid = 'osg_' .md5(serialize($atts));

        if (false === $markup = get_transient('el_' . $uid)) {
            $atts = shortcode_atts(array(
                'style' => 1,
                'step1_text' => '',
                'step1_href' => '',
                'step2_text' => '',
                'step2_href' => '',
                'step3_text' => '',
                'selected' => '1',
            ), $atts);

            $markup = op_sl_parse('order_step_graphics', $atts);

            if (is_string($markup) && 0 === strpos($markup, '##')) {
                $markup = substr($markup, 2);
            } elseif (!empty($markup)) {
                set_transient('el_' . $uid, $markup, OP_SL_ELEMENT_CACHE_LIFETIME);
            }
        }

        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(true);
        }

        return $markup;
    }

    static function pricing_element($atts,$content=''){
        $content = op_clean_shortcode_content($content);
        $content = str_replace(array('£','€'),array('&pound;','&euro;'),$content);
        $font = op_asset_font_style($atts);
        return '<span class="price-style-1"'.($font==''?'':' style=\''.$font.'\'').'>'.$content.'</span>';
    }

    static function pricing_table($atts,$content=''){
        //Initialize variables
        $html = '';

        //Get data from shortcodes
        $data = shortcode_atts(array(
            'style' => 1,
            'tabs' => array(),
            'id' => op_generate_id()
        ), $atts);

        //Clean up the data before processing it
        $content = op_clean_shortcode_content($content);

        //Get the tabs from the passed data
        $mc = preg_match_all('/'.op_shortcode_regex('tab').'/s',$content,$matches);

        //Ensure there is at least one tab
        if($mc > 0){
            self::$used_items['tabs'] = true;

            //Loop through the tabs
            for($i=0;$i<$mc;$i++){
                //Put this tab in the data array to be passed to template
                array_push($data['tabs'], shortcode_atts(array(
                    'title' => '',
                    'package_description' => '',
                    'price' => '',
                    'pricing_unit' => '',
                    'pricing_variable' => '',
                    'most_popular' => '',
                    'most_popular_text' => '',
                    'order_button_text' => '',
                    'order_button_url' => '',
                    'items' => '',
                    'li_class' => '',
                    'a_class' => ''
                ), shortcode_parse_atts($matches[3][$i])));
            }
        }

        //Clear the template
        _op_tpl('clear');

        //Process the new template and load it
        return _op_tpl('_load_file',OP_ASSETS.'tpls/pricing_table/style_'.$data['style'].'.php',$data,true);
    }

    static function progress_bar($atts,$content=''){
        //Extract shortcodes to variables
        extract(shortcode_atts(array(
            'style' => 1,
            'percentage' => 0,
            'id' => op_generate_id(),
            'color' => ''
        ), $atts));

        //Clean up the data before processing it
        $data['content'] = op_clean_shortcode_content($content);

        //Build style tag, if any
        $style_tag = (!empty($color) && $color!='undefined' ? '
            <style>
                #progressbar-'.$id.' .ui-progressbar-value {
                    background-color: '.$color.';
                    }
            </style>
        ' : '');

        return $style_tag.'
            <div class="progressbar-style-'.$style.'" id="progressbar-'.$id.'">
                <span>'.$percentage.'% '.$content.'</span>
            </div>
            <script type="text/javascript">
                (function ($) {
                    $(function() {
                        $("#progressbar-'.$id.'").progressbar({ value: 1 });
                        $("#progressbar-'.$id.' > .ui-progressbar-value").animate({ width: "'.$percentage.'%"}, 500);
                    });
                }(opjq));
            </script>
        ';
    }

    static function qna_elements($atts,$content=''){
        extract(shortcode_atts(array(
            'style' => 'qa-text',
            'columns' => '2',
        ), $atts));
        switch($columns){
            case 3:
                $columns = 'three';
                break;
            default:
                $columns = 'two';
                break;
        }
        self::$temp_val['qna_elements'] = $style;
        return '<ul class="qanda qanda-'.$columns.'-col '.$style.' cf">'.do_shortcode(op_clean_shortcode_content($content)).'</ul>';
    }

    static function question($atts,$content='',$tag=''){
        extract(shortcode_atts(array(
            'question' => '',
        ), $atts));
        $style = 'qa-text';
        if(isset(self::$temp_val['qna_elements'])){
            $style = self::$temp_val['qna_elements'];
        }
        $q = '<span>' . __('Q.', OP_SN) . '</span>';
        $a = '<span>' . __('A.', OP_SN) . '</span>';
        if($style == 'large-q'){
            $q = '<img width="36" height="41" alt="q-icon" src="'.OP_ASSETS_URL.'images/qna_elements/q-icon.png" />';
            $a = '';
        }
        $content = op_texturize(do_shortcode(op_clean_shortcode_content($content)));
        $content = preg_replace('{<p[^>]*>}i','<p>'.$a,$content,1);
        self::$temp_tag = 'question';
        return '
<li>
    <h3>'.$q.$question.'</h3>
    '.$content.'
</li>';
    }

    static function recent_posts($atts){
        extract(shortcode_atts(array(
            'style' => '',
            'posts_num' => ''
        ), $atts));

        //Get recent posts
        $recent_posts = wp_get_recent_posts(array(
            'numberposts' => $posts_num,
            'post_status' => 'publish'
        ));
        $recent_html = '';
        foreach( $recent_posts as $recent ){
            $img_src = wp_get_attachment_image_src(get_post_thumbnail_id($recent['ID']), 'single-post-thumbnail');
            $img_src = $img_src[0];

            $recent_html .= '<li><div class="thumb"><img src="'.$img_src.'" class="scale-with-grid" /></div><div class="content"><a href="'.get_permalink($recent["ID"]).'">'.$recent["post_title"].'</a><span>'.date(OP_DATE_POSTS, strtotime($recent['post_date'])).'</span></div></li>';
        }

        return '
            <ul class="recent-posts-style-'.$style.'">
                '.$recent_html.'
            </ul>
        ';
    }

    static function social_sharing($atts){
        global $post;
        $page_id = defined('OP_PAGEBUILDER_ID') ? OP_PAGEBUILDER_ID : $post->ID;
        $url = get_permalink($page_id);
        $data = shortcode_atts(array(
            'style' => 'horizontal',
            'fb_like_url' => $url,
            'fb_color' => 'light',
            'fb_lang' => op_default_attr('comments','facebook','language'),
            'fb_text' => 'Like',
            'fb_button_text' => 'Share',
            'tw_text' => 'Share',
            'tw_lang' => 'en',
            'tw_url' => $url,
            'tw_button_text' => 'Share',
            'tw_name' => '',
            'g_url' => $url,
            'g_lang' => 'en-GB',
            'g_button_text' => 'Share',
            'p_url' => $url,
            'p_image_url' => $url,
            'p_description' => '',
            'su_url' => $url,
            'linkedin_url' => $url,
            'linkedin_lang' => 'en_US',
            'alignment' => 'center'
        ),$atts);
        extract($data);

        $attrs = ($style=='horizontal' ? array(
            'fb_button_style' => 'button_count',
            'fb_extra' => ' data-width="89"',
            'tw_extra' => ' style="width:98px"',
            'g_size' => 'medium',
            'g_extra' => ' style="width:85px"',
            'su_style' => '1',
            'p_extra' => ' style="width:85px"',
        ) : array(
            'fb_extra' => '',
            'fb_button_style' => 'box_count',
            'tw_extra' => ' data-count="long"',
            'g_size' => 'tall',
            'su_style' => '5',
            'g_extra' => '',
            'p_extra' => '',
        ));

        $data['id'] = 'social-sharing-'.op_generate_id();

        //Init Facebook html
        $data['fb_html'] = '
            <script>
            (function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s); js.id = id;
                js.src = "//connect.facebook.net/'.$fb_lang.'/all.js#xfbml=1&appId='.op_default_attr('comments','facebook','id').'";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, \'script\', \'facebook-jssdk\'));
            </script>
            <div class="fb-like" data-send="false"'.($fb_like_url!=''?' data-href="'.$fb_like_url.'"':'').' data-layout="'.$attrs['fb_button_style'].'" data-show-faces="false" data-action="'.$fb_text.'" data-colorscheme="'.$fb_color.'"'.$attrs['fb_extra'].'></div>
        ';

        //Init Twitter html
        $data['twitter_html'] = '
            <a href="https://twitter.com/share" class="twitter-share-button"'.(ucfirst($tw_text) != ''?' data-text="'.op_attr($tw_text).'"':'').($tw_url != ''?' data-url="'.op_attr($tw_url).'"':'').($tw_name != ''?' data-via="'.op_attr($tw_name).'"':'').' data-lang="'.$tw_lang.'"'.$attrs['tw_extra'].'>'.__('Tweet',OP_SN).'</a>
            <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
        ';

        //Init Google+ html
        $data['gplus_html'] = '
            <div class="g-plusone" data-size="'.$attrs['g_size'].'" data-href="'.$g_url.'"'.$attrs['g_extra'].'></div>
            <script type="text/javascript">
              window.___gcfg = {lang: \''.$g_lang.'\'};
              (function() {
                var po = document.createElement(\'script\'); po.type = \'text/javascript\'; po.async = true;
                po.src = \'https://apis.google.com/js/plusone.js\';
                var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(po, s);
              })();
            </script>
        ';

        //Init Pinterest html
        $data['pinterest_html'] = '
            <span class="pinbreak"><a href="http://pinterest.com/pin/create/button/?url='.urlencode($p_url).'&media='.urlencode($p_image_url).($p_description!=''?'&description='.$p_description:'').'" class="pin-it-button" count-layout="'.$style.'"'.$attrs['p_extra'].'><img border="0" src="//assets.pinterest.com/images/PinExt.png" title="'.__('Pin It',OP_SN).'" /></a></span>
            <script type="text/javascript" src="//assets.pinterest.com/js/pinit.js"></script>
        ';

        //Init StumbleUpon html
        $data['su_html'] = '
            <su:badge layout="'.$attrs['su_style'].'" location="'.$su_url.'"></su:badge>

            <script type="text/javascript">
              (function() {
                var li = document.createElement(\'script\'); li.type = \'text/javascript\'; li.async = true;
                li.src = (\'https:\' == document.location.protocol ? \'https:\' : \'http:\') + \'//platform.stumbleupon.com/1/widgets.js\';
                var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(li, s);
              })();
            </script>
        ';

        //Init LinkedIn html
        $data['linkedin_html'] = '
            <script src="//platform.linkedin.com/in.js" type="text/javascript">
            lang: '.$linkedin_lang.'
               </script>
               <script type="IN/Share" data-url="'.$linkedin_url.'" data-counter="top"></script>
        ';

        //if (is_admin()) return __('--- Social Sharing Element ---', OP_SN);

        //Clear the template
        _op_tpl('clear');

        //Process the new template and load it
        return _op_tpl('_load_file',OP_ASSETS.'tpls/social_sharing/'.$data['style'].'.php',$data,true);
    }

    static function step_graphics($atts,$content=''){
        //Initialize variables
        $data = shortcode_atts(array(
            'style' => 1,
            'color' => '',
            'steps' => array()
        ), $atts);

        $data['rgb'] = self::_hex2rgb($data['color']);
        $data['id'] = 'step-graphic-'.op_generate_id();

        //Clean up the data before processing it
        $content = op_process_asset_content(do_shortcode(op_clean_shortcode_content($content)));
        //Get the steps from the passed data
        $mc = preg_match_all('/'.op_shortcode_regex('step').'/s',$content,$matches);
        //Ensure there is at least one step
        if($mc > 0){
            //Loop through the tabs
            for($i=0; $i<$mc; $i++){
                //Put this tab in the data array to be passed to template
                array_push($data['steps'], shortcode_atts(array(
                    'text' => '',
                    'headline' => '',
                    'information' => '',
                    'style' => 1
                ), shortcode_parse_atts($matches[3][$i])));
                $data['steps'][$i]['information'] = $matches[5][$i];
            }
        }
        //Clear the template
        _op_tpl('clear');

        //Process the new template and load it
        return _op_tpl('_load_file',OP_ASSETS.'tpls/step_graphics/style_'.$data['style'].'.php',$data,true);
    }

    static function tabs($atts,$content=null){
        $str = '';
        $original_font_str = $GLOBALS['OP_LIVEEDITOR_FONT_STR'];
        if(!is_null($content)){
            $content = op_clean_shortcode_content($content);
            $mc = preg_match_all('/'.op_shortcode_regex('tab').'/s',$content,$matches);
            if($mc > 0){
                self::$used_items['tabs'] = true;
                for($i=0;$i<$mc;$i++){
                    extract(shortcode_atts(array(
                        'title' => '',
                        'li_class' => '',
                        'a_class' => '',
                    ), shortcode_parse_atts($matches[3][$i])));

                    $str .= '<li'.($li_class==''?'':' class="'.$li_class.'"').'><a href="#"'.($a_class==''?'':' class="'.$a_class.'"').'>'.$title.'</a></li>';
                }


                $font = op_asset_font_style($atts);
                if($font != ''){
                    $style_str = ' style=\''.$font.'\'';
                    $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = array('elements' => array('p','a'), 'style_str' => $font);
                }

                $str = '
<div class="tabbed-panel">
    <ul class="tabs cf">
    '.$str.'
    </ul>
    <div class="tab-content-container">
    '.do_shortcode($content).'
    </div>
</div>';
            }
        }
        if($GLOBALS['OP_LIVEEDITOR_DEPTH'] == 1){
            $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = $original_font_str;
        }
        return $str;
    }

    static function tab($atts,$content=''){
        $content = do_shortcode(op_texturize(op_clean_shortcode_content($content)));
        $content = op_process_asset_content($content);
        return '
<div class="tab-content">'.$content.'</div>';
    }

    function _unautop($tags,$pee){
        $pattern =
              '/'
            . '<p>'                              // Opening paragraph
            . '\\s*+'                            // Optional leading whitespace
            . '('                                // 1: The shortcode
            .     '\\['                          // Opening bracket
            .     "($tags)"                 // 2: Shortcode name
            .     '\\b'                          // Word boundary
                                                 // Unroll the loop: Inside the opening shortcode tag
            .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
            .     '(?:'
            .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
            .         '[^\\]\\/]*'               // Not a closing bracket or forward slash
            .     ')*?'
            .     '(?:'
            .         '\\/\\]'                   // Self closing tag and closing bracket
            .     '|'
            .         '\\]'                      // Closing bracket
            .         '(?:'                      // Unroll the loop: Optionally, anything between the opening and closing shortcode tags
            .             '[^\\[]*+'             // Not an opening bracket
            .             '(?:'
            .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
            .                 '[^\\[]*+'         // Not an opening bracket
            .             ')*+'
            .             '\\[\\/\\2\\]'         // Closing shortcode tag
            .         ')?'
            .     ')'
            . ')'
            . '\\s*+'                            // optional trailing whitespace
            . '<\\/p>'                           // closing paragraph
            . '/s';

        return preg_replace( $pattern, '$1', $pee );
    }

    static function terms_conditions($atts,$content=''){
        self::$used_items['terms_conditions'] = true;
        extract(shortcode_atts(array(
            'style' => 1,
            'accept_text' => '',
        ), $atts));
        $terms = '';
        if(preg_match('/'.op_shortcode_regex('terms').'/s',$content,$matches)){
            $terms = $matches[5];
            $content = str_replace($matches[0],'',$content);
        }
        $content = call_user_func_array(array('OptimizePress_Default_Assets','_add_element_field'),array($atts,$content,'terms_conditions','[terms]'.$terms.'[/terms]'));
        return '
            <div class="terms_conditions">
                <div class="terms">
                    <label><input type="checkbox" value="Y" /> '.$accept_text.'</label>
                    <div class="terms_text">
                    '.$terms.'
                    </div>
                </div>
                <div class="terms_content">'.(defined('OP_LIVEEDITOR')?'':'<div class="terms_overlay">&nbsp;</div>').$content.'</div>
            </div>
        ';
    }

    static function testimonials($atts,$content=''){
        //Extract the shortcodes into variables
        extract(shortcode_atts(array(
            'style' => 1,
            'margin_top' => '',
            'margin_bottom' => ''
        ), $atts));

        //Initialize style arrays
        $cont_styles = array(1, 5, 9, 11, 14);
        $tblock_styles = array(5);

        //Initialize the return string
        $str = '';

        //Make sure there is a testimonial in the shortcode
        $mc = preg_match_all('/'.op_shortcode_regex('testimonial').'/s',$content,$matches);

        //Continue if there are matches
        if($mc > 0){
            //Loop through the testimonial elements
            for($i=0;$i<$mc;$i++){
                //Extract the data from the child shortcode
                $data = shortcode_atts(array(
                    'name' => '',
                    'company' => '',
                    'href' => '',
                    'image' => '',
                ), shortcode_parse_atts($matches[3][$i]));

                //Get this testimonials content
                $data['content'] = $matches[5][$i];

                //Generate a font style out of the font attribute
                $data['font_str'] = op_asset_font_style($atts);

                //Get the current font settings
                $original_font_str = $GLOBALS['OP_LIVEEDITOR_FONT_STR'];

                //If font settings are present then set the style string and set the font string
                if(!empty($data['font_string'])) $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = array('elements' => array('p','a'), 'style_str' => $font);

                //Return the font string back to normal
                $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = $original_font_str;

                //Add margin to the data array
                $data['margin_top'] = $margin_top;
                $data['margin_bottom'] = $margin_bottom;

                //Generate the element ID
                $data['id'] = 'testimonial-'.op_generate_id();

                //Clear out the template and reload it with the style of this testimonial, putting the
                //returned HTML into the display string
                _op_tpl('clear');
                $str .= _op_tpl('_load_file',OP_ASSETS.'tpls/testimonials/style_'.$style.'.php',$data,true);
            }

            //If there is a container div in this style, add it to the HTML
            if (in_array($style, $cont_styles))
                $str = '<div class="'.(in_array($style, $tblock_styles) ? 'testimonial-block-three cf' : 'testimonial-block cf').'">'.$str.'</div>';
        }

        return $str;
    }

    static function tour($atts)
    {
        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(false);
        }

        $uid = 'tour_' .md5(serialize($atts));

        if (false === $markup = get_transient('el_' . $uid)) {
            $vars = shortcode_atts(array(
                'style' => '1',
                'get_started_link' => '',
                'get_started_text' => '',
                'tour_link' => '',
                'tour_text' => '',
                'headline' => '',
                'subheadline' => '',
            ), $atts);

            $markup = op_sl_parse('tour', $vars);

            if (is_string($markup) && 0 === strpos($markup, '##')) {
                $markup = substr($markup, 2);
            } elseif (!empty($markup)) {
                set_transient('el_' . $uid, $markup, OP_SL_ELEMENT_CACHE_LIFETIME);
            }
        }

        /*
         * Cache busting
         */
        if (function_exists('wp_using_ext_object_cache')) {
            wp_using_ext_object_cache(true);
        }

        return $markup;
    }

    static function two_column_block($atts,$content=''){
        $chks = array('content1','content2');
        foreach($chks as $chk){
            $$chk = '';
            if(preg_match('/'.op_shortcode_regex($chk).'/s',$content,$matches)){
                // TODO: is this ok?
                add_filter( 'the_content', 'wpautop' );
                $$chk = apply_filters('the_content',$matches[5]);
                remove_filter( 'the_content', 'wpautop' );
            }
        }
        return '
            <div class="double-column cf">
                <div class="col-left">'.$content1.'</div>
                <div class="col-right">'.$content2.'</div>
            </div>';
    }

    static function vertical_spacing($atts){
        extract(shortcode_atts(array(
            'height' => '20'
        ),$atts));
        return '<div style="height:'.$height.'px">'.(defined('OP_LIVEEDITOR')?' -- SPACER -- ':'').'</div>';
    }

    static function video_lightbox($atts,$content=''){

        //Initialize default array
        $default = array(
            'placeholder' => '',
            'type' => 'embed',
            'hide_controls' => 'N',
            'auto_play' => 'N',
            'auto_buffer' => 'N',
            'width' => 511,
            'height' => 288,
            'placeholder_width' => '',
            'placeholder_height' => '',
            'align' => 'center',
            'style' => 1,
            'url1' => '',
            'url2' => '',
        );

        //Extract the attributes into the data array and if a value doesn't exist, use the defaults
        //This will be used for the templates
        $data = shortcode_atts($default, $atts);

        self::$add_pretty_photo = true;

        //If this is an embedded video code, the inline content, which is the embed code, must be processed
        if($data['type'] == 'embed'){
            if(preg_match('{alt=\"(.*?)\"}si',$content,$match)){
                $content = base64_decode(html_entity_decode( $match[1], ENT_QUOTES, get_option('blog_charset') ));
                if(stripos($content, 'id="evp-') !== false) {
                    $content = preg_replace('{<!--\s+_evpInit}si',"<!--\n_evpInit",$content);
                }
            }
            $el = self::_get_element_number('video_lightbox');
            $data['inlinecontent'] = '<div style="display:none"><div id="'.$el.'" style="width:'.$data['width'].'px;height:'.$data['height'].'px">'.$content.'</div></div>';
            $data['url'] = '#'.$el;
        } else { //If this is a URL, then we must process the URL
            $data['url'] = '';
            $content = base64_decode($content);
            if(preg_match('|(https?://[^<"]+)|im',$content,$matches)){
                if(strpos($matches[1],'youtube.com') > 0 || strpos($matches[1],'vimeo.com') > 0){
                    $data['url'] = $matches[1].'&amp;width='.$data['width'].'&amp;height='.$data['height'];
                } else {
                    $arr = array(
                        'playlist' => array(
                            array(
                                'autoPlay' => ($data['auto_play'] == 'Y'),
                                'autoBuffering' => ($data['auto_buffer'] == 'Y'),
                                'url' => $matches[1]
                            )
                        ),
                        'plugins' => array(
                            'controls' => array(
                                'url' => OP_MOD_URL.'blog/video/flowplayer/flowplayer.controls-3.2.5.swf'
                            )
                        )
                    );
                    $data['url'] = '#inline-content';
                    $data['videoUrl'] = $matches[1];

                    /*
                     * Video URL 1
                     */
                    $data['videoUrl1'] = '';
                    $url1 = base64_decode($atts['url1']);
                    if(preg_match('|(https?://[^<"]+)|im',$url1,$matches)){
                        $data['videoUrl1'] = $matches[1];
                    }
                    /*
                     * Video URL 2
                     */
                    $data['videoUrl2'] = '';
                    $url2 = base64_decode($atts['url2']);
                    if(preg_match('|(https?://[^<"]+)|im',$url2,$matches)){
                        $data['videoUrl2'] = $matches[1];
                    }
                }
            }
        }

        //Initialize the output variable
        $output = op_mod('video')->output(array(),array(),$data,true,true);

        //Process any additional template variables
        $data['placeholder_width'] = intval(empty($data['placeholder_width'])?$output['options']['width']:$data['placeholder_width']);
        $data['placeholder_height'] = intval(empty($data['placeholder_height'])?$output['options']['height']:$data['placeholder_height']);
        $data['frame_width'] = $placeholder_width+18;
        $data['align'] = $data['align'] == 'center' ? 'margin:0 auto;' : 'float:'.$data['align'];
        if(!is_admin()){
            unset($output['options']['placeholder']);
            extract($output['options']);
            $data['video_type'] = ' video-type-'.$data['type'];
        } else {
            //if(count($data) == 0) $data = array('width' => $default['values']['width'], 'height' => $default['values']['height']);
            if(empty($data['width'])) $data['width'] = $default['values']['width'];
            if(empty($data['height'])) $data['height'] = $default['values']['height'];
            $data['video_type'] = '';
            $data['url'] = '#';
            $data['inlinecontent'] = '';
        }

        //Clear the template
        _op_tpl('clear');

        //Process the new template and load it
        return _op_tpl('_load_file',OP_ASSETS.'tpls/video_lightbox/style_'.$data['style'].'.php',$data,true);
    }

    static function video_player($atts,$content=''){
        $default = array(
            'type' => 'embed',
            'hide_controls' => 'N',
            'auto_play' => 'N',
            'auto_buffer' => 'N',
            'width' => 511,
            'height' => 288,
            'margin_top' => 0,
            'margin_bottom' => 20,
            'border_size' => 0,
            'border_color' => '#fff',
            'placeholder' => '',
            'align' => 'center',
            'youtube_url' => '',
            'youtube_auto_play' => 'N',
            'youtube_hide_controls' => 'N',
            'youtube_remove_logo' => 'N',
            'youtube_show_title_bar' => 'N',
            'youtube_force_hd' => '',
            'url1' => '',
            'url2' => '',
            'new_syntax' => true
        );
        $vars = shortcode_atts($default, $atts);
        if($vars['type'] == 'embed'){
            if(preg_match('{alt=\"(.*?)\"}si',$content,$match)){
                $content = base64_decode(html_entity_decode( $match[1], ENT_QUOTES, get_option('blog_charset') ));
                if(stripos($content, 'id="evp-') !== false) {
                    $content = preg_replace('{<!--\s+_evpInit}si',"<!--\n_evpInit",$content);
                }
            }
            $vars['embed'] = $content;
        } elseif ($vars['type'] == 'youtube') {
            $content = base64_decode($content);
            if(preg_match('|(https?://[^<"]+)|im',$content,$matches)){
                $vars['youtube_url'] = $matches[1];
            }
        } else {
            $vars['url'] = '';
            $content = base64_decode($content);
            if(preg_match('|(https?://[^<"]+)|im',$content,$matches)){
                $vars['url'] = $matches[1];
            }
            /*
             * Video URL 1
             */
            $vars['url1'] = '';
            $url1 = base64_decode($atts['url1']);
            if(preg_match('|(https?://[^<"]+)|im',$url1,$matches)){
                $vars['url1'] = $matches[1];
            }
            /*
             * Video URL 2
             */
            $vars['url2'] = '';
            $url2 = base64_decode($atts['url2']);
            if(preg_match('|(https?://[^<"]+)|im',$url2,$matches)){
                $vars['url2'] = $matches[1];
            }
        }

        /**
         * Loading video player script
         */
        op_video_player_script();

        $output = op_mod('video')->output(array(),array(),$vars,true,true);

        if(!is_admin()){
            return $output['output'];
        }

        if(count($vars) == 0){
            $vars = array(
                'width' => $default['values']['width'],
                'height' => $default['values']['height'],
                'border_size' => $default['values']['border_size'],
                'border_color' => $default['values']['border_color'],
            );
        }
        if(empty($vars['width'])&& isset($default['values']) && isset($default['values']['width'])){
            $vars['width'] = $default['values']['width'];
        }
        if(empty($vars['height'])&& isset($default['values']) && isset($default['values']['height'])){
            $vars['height'] = $default['values']['height'];
        }
        if(empty($vars['border_size']) && isset($default['values']) && isset($default['values']['border_size'])){
            $vars['border_size'] = $default['values']['border_size'];
        }
        if(empty($vars['border_color'])&& isset($default['values']) && isset($default['values']['border_color'])){
            $vars['border_color'] = $default['values']['border_color'];
        }
        $align = $vars['align'] == 'center' ? 'margin:0 auto;' : 'float:'.$vars['align'].';';

        // This ifelse can be removed for production, it's unnecessary, new syntax will always be present.
        if ($vars['new_syntax']) {
            $video_aspect_ratio = $vars['height'] / $vars['width'];
            $aspect_ratio_string = 'height:0; padding-bottom:' . ($video_aspect_ratio * 100) . '%; padding-top:0;';
            $output = '<div class="video-plugin-new-syntax video-plugin-new" style="width:' . $vars['width'] . 'px; max-width:100%;'.$align.(!empty($vars['margin_top']) ? ' margin-top: '.$vars['margin_top'].'px;' : '').(!empty($vars['margin_bottom']) ? ' margin-bottom: '.$vars['margin_bottom'].'px;' : 'margin-bottom: 20px;'). (' border: '.$vars['border_size'].'px solid ' .$vars['border_color']. ';') . ' padding-top:0; padding-bottom:0;">' . '<div style="' . $aspect_ratio_string . '"></div>' . '</div>';
        } else {
            $output = '<div class="video-plugin" style="width:'.$vars['width'].'px;height:'.$vars['height'].'px;'.$align.(!empty($vars['margin_top']) ? ' margin-top: '.$vars['margin_top'].'px;' : '').(!empty($vars['margin_bottom']) ? ' margin-bottom: '.$vars['margin_bottom'].'px;' : 'margin-bottom: 20px;'). (' border: '.$vars['border_size'].'px solid ' .$vars['border_color']. ';') . '"></div>';
        }

        return $output;
    }

    /**
     * shortcode parsing of file downloads element
     * @param array $atts
     * @param string $content
     * @return String
     */
    static function file_download($atts, $content)
    {
        extract(shortcode_atts(array(
            'style' => 1,
        ), $atts));

        $content = do_shortcode(op_clean_shortcode_content($content));
        $content = op_process_asset_content($content);

        $html = '<ul class="downloadlist-'.$style.' border">' . $content . '</ul>';

        return $html;
    }

    /**
     * shortcode parsing of membership file downloads element
     * @param array $atts
     * @param string $content
     * @return String
     */
    static function membership_download($atts, $content)
    {
        extract(shortcode_atts(array(
            'style' => 1,
        ), $atts));
        //</editor-fold>
        // some preliminary checks
        // creating member dir if it does not exist
        if (!is_dir($files_dir = $GLOBALS['WS_PLUGIN__']['optimizemember']['c']['files_dir'])) {
            if (is_writable( dirname ( c_ws_plugin__optimizemember_utils_dirs::strip_dir_app_data($files_dir )))) {
                mkdir ($files_dir, 0777, true);
            }
        }
        // creating .htaccess file if it does not exist!
        if (is_dir($files_dir) && is_writable($files_dir)) {
            if (!file_exists( $htaccess = $files_dir . '/.htaccess') || !apply_filters ('ws_plugin__s2member_preserve_files_dir_htaccess', false, get_defined_vars())) {
                file_put_contents($htaccess, trim(c_ws_plugin__optimizemember_utilities::evl(file_get_contents($GLOBALS['WS_PLUGIN__']['optimizemember']['c']['files_dir_htaccess']))));
            }
        }
        $content = do_shortcode(op_clean_shortcode_content($content));
        $content = op_process_asset_content($content);

        if (!empty($content)) {
            $html = '<ul class="downloadlist-'.$style.' border">' . $content . '</ul>';
        } else {
            $html = '';
        }

        return $html;

    }

    /**
     *
     * shortcode parsing for child elements of membership download element
     */
    static function download($atts, $content)
    {
        extract(shortcode_atts(array(
            'title' => '',
            'icon' => '',
            'icon_folder' => 'file_download',
            'file' => '',
            'package' => '',
            'level' => '',
            'new_window' => '',
            'hide_alert' => ''
        ), $atts));
        if (empty($file)) {
            return '';
        }
        $hideAlert = '';
        if ((!empty($package) || !empty($level))) {
            if (defined('WS_PLUGIN__OPTIMIZEMEMBER_VERSION')) { // is OPM element present and OPM activated?
                $filesDir = $GLOBALS['WS_PLUGIN__']['optimizemember']['c']['files_dir'];
                $hideFiles = false;
                if (isset($GLOBALS['WS_PLUGIN__']['optimizemember']['o']['filter_wp_query']) && 'all' === $GLOBALS['WS_PLUGIN__']['optimizemember']['o']['filter_wp_query'][0]) {
                    $hideFiles = true;
                }
                $uploads = wp_upload_dir();
                $baseUploadDir = $uploads['basedir'];
                $baseUploadUrl = $uploads['baseurl'];
                $fileNameWithFolder = str_replace($baseUploadUrl, '', $file);
                $onlyFileName = basename($file);
                $fullFilePath = $baseUploadDir . '/' . $fileNameWithFolder;
                // protection needed based on level or package selected
                if (!empty($package)) { // if package selected, level is ignored
                    $destinationFolder = $filesDir . '/access-optimizemember-ccap-' . $package;
                    $forUrl = '/access-optimizemember-ccap-' . $package;
                } else if (!empty($level)) {
                    $destinationFolder = $filesDir . '/access-optimizemember-level' . $level;
                    $forUrl = '/access-optimizemember-level' . $level;
                }
                $amazon = false;
                if (c_ws_plugin__optimizemember_utils_conds::using_amazon_s3_storage()) {
                    $amazon = true;
                }
                if (c_ws_plugin__optimizemember_utils_conds::using_amazon_cf_storage()) {
                    $amazon = true;
                }
                if (!$amazon) {
                    if (!is_dir($destinationFolder)) {
                        mkdir($destinationFolder, 0777, true);
                    }
                    // copying only in admin
                    if (defined('OP_LIVEEDITOR')) {
                        copy($fullFilePath, $destinationFolder . '/' . $onlyFileName);
                    }
                }
                $protected = true;
                // hide alert?
                if (!empty($hide_alert) && $hide_alert == 'Y') {
                    $hideAlert = '&optimizemember_skip_confirmation=yes';
                }
                // dealing with level or packages
                $hideContent = false;
                if (empty($package)) {
                    if (!current_user_can("access_optimizemember_level" . $level)) {
                        $hideContent = true;
                    }
                } else {
                    if (!current_user_can("access_optimizemember_ccap_" . $package)) {
                        $hideContent = true;
                    }
                }
            } else {
                if (defined('OP_LIVEEDITOR')) {
                    return '<p>In order to use this element, you have to enable OptimizeMember plugin !!!</p>';
                } else {
                    return '';
                }
            }
        } else {
           $protected = false;
        }
        if ($protected) {
            if (!$amazon) {
                $fileLink = site_url('?optimizemember_file_download='). $forUrl . '/' .  $onlyFileName . $hideAlert;
            } else {
                $fileLink = site_url('?optimizemember_file_download='). '/' .  $onlyFileName . $hideAlert;
            }
        } else {
            $fileLink = $file;
        }
        $blank = '';
        if (!empty($new_window) && $new_window == 'Y') {
            $blank = ' target="_blank" ';
        }

        $html = '';
        if ($protected && (false === $hideContent || false === $hideFiles)) {
            $html .= '<li>';
            $html .= '<a '.$blank.' href="'. $fileLink.'"><img src="'.OP_ASSETS_URL.'images/' . $icon_folder . '/icons/'.$icon.'" alt="icon" class="thumb" /></a>';
            $html .= '<div class="content">';
            $html .= '<a '.$blank.'href="'. $fileLink.'">'.$title.'</a>';
            $html .= '<p>' . $content . '</p>';
            $html .= '</div>';
            $html .= '</li>';
        } else if (!$protected) {
            $html .= '<li>';
            $html .= '<a '.$blank.' href="'. $fileLink.'"><img src="'.OP_ASSETS_URL.'images/' . $icon_folder . '/icons/'.$icon.'" alt="icon" class="thumb" /></a>';
            $html .= '<div class="content">';
            $html .= '<a '.$blank.' href="'.$fileLink .'">'.$title.'</a>';
            $html .= '<p>' . $content . '</p>';
            $html .= '</div>';
            $html .= '</li>';
        }

        return $html;

    }

    /**
     * Displays login/logout form and user details
     * @author Luka Peharda <luka.peharda@gmail.com>
     * @param  array $atts
     * @return string
     */
    static function membership_login_form($atts)
    {
        // var_dump($atts);
        extract(shortcode_atts(array(
            'style'                     => '1',

            'public_title'              => '',
            'redirection_after_login'   => '',
            'signup_now'                => '',
            'additional_code_1'         => '',

            'profile_title'             => '',
            'display_gravatar'          => '1',
            'link_to_gravatar'          => '1',
            'display_user_name'         => '1',
            'my_account'                => '',
            'edit_profile'              => '',
            'redirection_after_logout'  => '',
            'additional_code_2'         => ''
        ), $atts));

        $form = optimizemember_pro_login_widget(array(
            'title' => $public_title,
            'signup_url' => $signup_now,
            'login_redirect' => $redirection_after_login,
            'logged_out_code' => $additional_code_1,

            'profile_title' => $profile_title,
            'display_gravatar' => $display_gravatar,
            'link_gravatar' => $link_to_gravatar,
            'display_name' => $display_user_name,
            'logged_in_code' => $additional_code_2,
            'logout_redirect' => $redirection_after_logout,
            'my_account_url' => $my_account,
            'my_profile_url' => $edit_profile
        ));

        return '
        <div class="login-form-style-' . $style . '">
            ' . $form . '
        </div>';
    }

    /**
     * shortcode parsing of javascript element
     * @param array $atts
     * @return String
     */
    static function javascript($atts, $content='')
    {
        preg_match_all('#<script(.*?)>(.*?)</script>#is', $content, $matches);
        // foreach $matches[2] if they are not empty and do our magic then
        $scripts = '';
        $output = '';
        $externals = '';
        //die(print_r($matches));
        if (!empty($matches[2])) {
            foreach ($matches[2] as $key => $script) {
                if (!empty($script)) {
                    $script = str_replace('<![CDATA[', '', $script);
                    $script = str_replace(']]>', '', $script);
                    $script = str_replace('// ', '', $script);
                    $scripts .= $script;
                } else {
                    // is it external?
                    if (stripos($matches[0][$key], 'src="') !== false) {
                        $externals .= $matches[0][$key];
                    }
                }
            }
        }
        /* if (!empty($scripts)) {
            $output .= $externals . '<script>try{'.$scripts.'} catch (err) {};</script>';
        } */

        if (defined('OP_LIVEEDITOR')) {
            $output .= '!!!JAVASCRIPT ELEMENT!!!';
        } else {
            $output .= $externals . '<script>try{'.strip_tags($scripts).'} catch (err) {};</script>';
        }
        return $output;
    }

    /**
     * shortcode parsing of membership order button element
     * @param array $atts
     * @param string $content
     * @return String
     */
    static function membership_order_button($atts, $content)
    {
        self::$used_items['membership_order_button'] = true;
        $domain = str_replace('http://', '', home_url());
        $domain = str_replace('https://', '', home_url());
        $temp = parse_url(home_url());
        $domain = $temp['host'];
        extract($atts);
        switch ($gateway) {
            case 'alipay':
                $temp = explode('-', $one_time);
                $output = '[optimizeMember-Pro-AliPay-Button level="'.$level.'" ccaps="' . $ccaps . '" desc="'.$description.'" custom="'.$domain.'" ra="'.$want_to_charge.'" rp="'.$temp[0].'" rt="'.$temp[1].'" image="default" output="anchor"]' . do_shortcode($content) . '[/optimizeMember-Pro-AliPay-Button]';
            break;
            case 'authnet':
                $temp = explode('-', $one_time_auth);
                if (empty($first)) {
                    $at = 0;
                    $first = 0;
                }
                if ($temp[2] == 'BN') {
                    $at = 0;
                    $first = 0;
                }
                $output = '[optimizeMember-Pro-AuthNet-Form level="'.$level.'" ccaps="' . $ccaps . '" desc="'.$description.'" cc="USD" custom="'.$domain.'" ta="'.$at.'" tp="'.$first.'" tt="'.$days.'" ra="'.$want_to_charge.'" rp="'.$temp[0].'" rt="'.$temp[1].'" rr="'.$temp[2].'" rrt="" accept="visa,mastercard,amex,discover" coupon="" accept_coupons="0" default_country_code="US" captcha="0"]' . do_shortcode($content) . '[/optimizeMember-Pro-AuthNet-Form]';
            break;
            case 'ccbill':
                $temp = explode('-', $one_time_cc);
                if (empty($first)) {
                    $at_cc = 0;
                    $first_cc = 0;
                }
                if ($temp[2] == '0') {
                    $at_cc = 0;
                    $first_cc = 0;
                }
                $output = '[optimizeMember-Pro-ccBill-Button level="'.$level.'" ccaps="' . $ccaps . '" desc="'.$description.'" cc="'.$currency_cc.'" custom="'.$domain.'" ta="'.$at_cc.'" tp="'.$first_cc.'" tt="'.$days_cc.'" ra="'.$want_to_charge_cc.'" rp="'.$temp[0].'" rt="'.$temp['1'].'" rr="'.$temp[2].'" image="default" output="anchor"]' . do_shortcode($content) . '[/optimizeMember-Pro-ccBill-Button]';
            break;
            case 'google':
                $temp = explode('-', $one_time_auth);
                if (empty($first)) {
                    $at = 0;
                    $first = 0;
                }
                if ($temp[2] == 'BN') {
                    $at = 0;
                    $first = 0;
                }
                $output = '[optimizeMember-Pro-Google-Button level="'.$level.'" ccaps="' . $ccaps . '" desc="'.$description.'" cc="'.$currency_new.'" custom="'.$domain.'" ta="'.$at.'" tp="'.$first.'" tt="'.$days.'" ra="'.$want_to_charge.'" rp="'.$temp[0].'" rt="'.$temp[1].'" rr="'.$temp[2].'" image="default" output="anchor"]' . do_shortcode($content) . '[/optimizeMember-Pro-Google-Button]';
            break;
            case 'paypal':
                $temp = explode('-', $one_time_auth);
                if (empty($first)) {
                    $at = 0;
                    $first = 0;
                }
                if ($temp[2] == 'BN') {
                    $at = 0;
                    $first = 0;
                }
                if (!empty($success_url)) {
                    $success = ' success="'.$success_url.'"';
                } else {
                    $success = '';
                }
                $output = '[optimizeMember-PayPal-Button level="'.$level.'" ccaps="' . $ccaps . '" desc="'.$description.'" ps="'.$page_style.'" lc="" cc="'.$currency_new.'" dg="0" ns="1" custom="'.$domain.'" ta="'.$at.'" tp="'.$first.'" tt="'.$days.'" ra="'.$want_to_charge.'" rp="'.$temp[0].'" rt="'.$temp[1].'" rr="'.$temp[2].'" rrt="" rra="1" '.$success.' image="default" output="button"]' . do_shortcode($content) . '[/optimizeMember-PayPal-Button]';
            break;
            case 'clickbank':
                if ($click_product_type == 'standard') {
                    $temp = explode('-',$one_time_clickbank);
                    $click_trial_period = '0';
                    $rr = '0';
                    $temp2[0] = '0';
                    $temp2[1] = 'D';
                } else {
                    $temp = explode('-', $click_rebill);
                    $temp2 = explode('-', $click_trial_period);
                    $rr = '1';
                }
                $output = '[optimizeMember-Pro-ClickBank-Button cbp="'.$click_product_item.'" level="'.$level.'" ccaps="' . $ccaps . '" desc="'.$description.'" custom="'.$domain.'" tp="'.$temp2[0].'" tt="'.$temp2[1].'" rp="'.$temp[0].'" rt="'.$temp[1].'" rr="'.$rr.'" image="default" output="anchor" cbskin="' . $click_skin . '"  cbfid="' . $click_fid . '"  cbur="' . $click_ur . '"  cbf="' . $click_f . '"]' . do_shortcode($content) . '[/optimizeMember-Pro-ClickBank-Button]';
            break;
        }
        //die($output);
        return do_shortcode($output);
    }


    static function _print_front_scripts($return=false){
        $keys = array_keys(self::$used_items);
        $js = array();
        foreach($keys as $asset){
            if($asset == 'content_toggle'){
                $js[] = "\$('.toggle-panel > .toggle-panel-toggle-text').click(function(e){
    \$(this).parent().find('.toggle-panel-toggle').trigger('click');
    e.preventDefault();
});
\$('.toggle-panel-toggle').click(function(e){
    var \$t = $(this), p = \$t.parent(), content = p.find('> .toggle-panel-content'), f1 = 'addClass', f2 = 'removeClass', f3 = 'show', f4 = 'hide', t = '-';
    \$(window).trigger('content-toggle');
    if(content.is(':visible')){
        f1 = 'removeClass';
        f2 = 'addClass';
        f3 = 'hide';
        f4 = 'show';
        t = '+';
    }
    p[f1]('panel-open')[f2]('panel-closed').find('.toggle-hide')[f3]().end().find('.toggle-show')[f4]().end().find('.toggle-panel-toggle').html('<span>'+t+'</span>').parent().find('.toggle-panel-content')[f3]();
    e.preventDefault();
});";
            } elseif($asset == 'tabs'){
                $js[] = "\$('.tabbed-panel .tabs li a').click(function(e){
    var li = \$(this).parent(), ul = li.parent(), idx = ul.find('li').index(li), panel = ul.parent();
    panel.find('> .tab-content-container').find('> .tab-content').hide().end().find('> .tab-content:eq('+idx+')').show();
    ul.find('.selected').removeClass('selected');
    li.addClass('selected');
    e.preventDefault();
});".(defined('OP_AJAX_SHORTCODE')?"
op_cur_html.find(":"\$(")."'.tabbed-panel .tabs li:first-child a').click();";
            } elseif($asset == 'terms_conditions' && !defined('OP_LIVEEDITOR')){
                $js[] = "\$('.terms_conditions :checkbox').change(function(){
    var opac = 1, func = 'hide', p = \$(this).closest('.terms_conditions'), el = p.find('.terms_overlay'), c = p.find('.terms_content *:not(.terms_overlay)');
    if(!\$(this).is(':checked')){
        opac = 0.3;
        func = 'show';
    }
    el[func]();
    c.animate({opacity:opac},500);
}).trigger('change');";
            }

        }
        if(count(self::$delayed_timers) > 0){
            $str = '';
            foreach(self::$delayed_timers as $id => $time){
                $str .= "setTimeout(function(){\$('#".$id."').animate({opacity: 1.0});},".$time.");$('#".$id."').css({opacity: 0.0, visibility: 'visible'});";
            }
            $js[] = $str;
        }

        //if(isset(self::$element_count['delayed_content']) && self::$element_count
        $out = '';
        if(!defined('OP_LIVEEDITOR')){
            if(self::$add_pretty_photo === true){
                op_video_player_script();
                wp_enqueue_script(OP_SN.'-prettyphoto', OP_JS.'prettyphoto/jquery.prettyPhoto'.OP_SCRIPT_DEBUG.'.js', array(OP_SN.'flowplayerhtml5'), OP_VERSION);
                wp_enqueue_style(OP_SN.'-prettyphoto', OP_JS.'prettyphoto/prettyPhoto'.OP_SCRIPT_DEBUG.'.css', false, OP_VERSION);
            }
        }
        $js = apply_filters('op_asset_js',$js);
        if(count($js) > 0){
            $out .= '
<script type="text/javascript">
;(function($){
'.implode("\n",$js).'
})(opjq);
</script>
';
        }

        if($return){
            return $out;
        }
        echo $out;
    }

    static function print_css()
    {
        wp_enqueue_style(OP_SN.'-default', OP_ASSETS_URL.'default'.OP_SCRIPT_DEBUG.'.css', false, OP_VERSION);
    }

    function _check_function(){
        $js = apply_filters('op_asset_check_js',array());
        if(count($js) == 0){
            $js = null;
        }
        return $js;
    }

    static function set_lang_keys(){
        self::$lang_keys = array(
            'affiliate_page_description' => __('Provide code for banners or other elements that you want your affiliates to use for promoting your products or services.',OP_SN),

            'arrows_description' => __('Use arrows on your site to highlight areas of important information, or to help funnel your visitors eye to a particular area or element of the page',OP_SN),

            'audio_player_description' => __('Use the audio player to Include audio on your page. We recommend using MP3 format for your audio files.',OP_SN),

            'bullet_block_description' => __('Use bullet blocks or lists to break down bulky information into readable chunks.  Using bullets can help conversions as visitors can quickly scan your bullets for information',OP_SN),

            'button_description' => __('Use buttons on your page for calls to action or to aid navigation through your site. Select from our wide range of buttons to suit your page style and purpose',OP_SN),

            'calendar_date_description' => __('Use the Calendar Date and Time element to show the time and date of a webinar or other online event',OP_SN),

            'content_toggle_description' => __("Use this element to keep lengthly content organised on the page.  Great for FAQ's or similar information where the visitor can view the title and expand for more information.",OP_SN),

            'countdown_timer_description' => __('Insert a countdown timer on your pages. Great for creating urgency for conversions',OP_SN),

            'countdown_cookie_timer_description' => __('Countdown Timer with Cookie',OP_SN),

            'course_description_description' => __('Insert a box showing the title and description of your membership course or product and include an icon',OP_SN),

            'delayed_content_description' => __('Use this to delay an element on your page so it appears after a set time.  You can delay text or use the "Add Element" feature to add more items to delay.',OP_SN),

            'divider_description' => __('Choose from a range of dividing lines and graphics to insert into your page.',OP_SN),

            'dynamic_date_description' => __('Insert the date of the users visit into your page.  This date will always show the date the visitor accessed your page, so gives the impression of updated content',OP_SN),

            'feature_block_description' => __('Use this element to highlight particular features of your product or service and include an icon or image to accompany the feature.',OP_SN),

            'feature_box_description' => __('Insert a styled content box onto your page.  Choose from a range of styles to suit your page and content and insert text or other elements into the box.',OP_SN),

            'feature_box_creator_description' => __('Create your own box style for your content.  Use the Advanced Options section to customize the look and feel of your box before inserting into the page',OP_SN),

            'file_download_description' => __('Files download element can be used to upload files and build file lists.', OP_SN),

            'guarantee_box_description' => __('Highlight your guarantee policy and terms with these boxes and graphics.',OP_SN),

            'headline_description' => __('Choose from a range of different headline styles to highlight features or sections of your page.',OP_SN),

            'hyperlink_description' => __('If you want to include a customized text hyperlink you can use this element to insert one into your page. Customize the styling to fit your site.',OP_SN),

            'images_description' => __('Insert an image into your page with this element.  Choose from a range of border styles and include a caption and larger image if you want to provide a larger lightbox preview.',OP_SN),

            'img_alert_description' => __('Image with javascript alert can be used for a video fakeout or to push people to your optin form',OP_SN),

            'img_text_aside_description' => __('An easy way to add an image and vertically align text content next to it. Great for inserting features illustrated with graphics onto your pages',OP_SN),

            'javascript_description' => __('Add your custom JavaScript on page', OP_SN),

            'live_search_description' => __('Insert a Live Search box which can be used to search your pages or membership content', OP_SN),

            'membership_order_button_description' => __('Insert a button which will allow users to order a membership.',OP_SN),

            'membership_login_form_description' => __('Insert a login form for your members to login to your membership area', OP_SN),

            'membership_download_description' => __('Membership files download can be used to upload and protect files using OptimizePress Member plugin.', OP_SN),

            'navigation_description' => __('Help visitors navigate around your site with these navigation blocks. Create a custom menu within the Wordpress interface and choose the menu to assign to your element',OP_SN),

            'news_bar_description' => __('Insert a bar which you can use to highlight latest news from your company or product',OP_SN),

            'one_time_offer_description' => __('If you are creating a one-time offer page you can use these graphics to help emphasise this and improve conversions',OP_SN),

            'optin_box_description' => __('Insert an optin box into your page with this element.  Choose from a range of styles and integrate your autoresponder to build your email list.',OP_SN),

            /*'optin_modal_description' => __('Optin modal description',OP_SN),*/

            'order_box_description' => __('Use order boxes to highlight your call to action and order buttons.',OP_SN),

            'order_step_graphics_description' => __('Use order step graphics to highlight the step of the checkout process your visitor is on. This can help them how close to completing the transaction they are.',OP_SN),

            'pricing_element_description' => __('If you want to include a high visibility product price on your page you can use this element.  Customize the text and a white border and shadow will be added.',OP_SN),

            'pricing_table_description' => __('Use Pricing Tables to elegantly display the different features or benefits of your product packages in comparison to each other',OP_SN),

            'op_popup_description' => __('Use OverlayOptimizer element to create a lightbox with any OP elements inside of it.',OP_SN),

            'progress_bar_description' => __('Insert a progress bar on your page - great for showing progress through a membership course or free training series',OP_SN),

            'qna_elements_description' => __('If you want to include a Q&amp;A section on your page you can use this element to display your questions and answers in an organised style',OP_SN),

            'recent_posts_description' => __('Insert a list of the most recent posts from your blog. Perfect for creating a custom home page.',OP_SN),

            'social_sharing_description' => __('Insert a block of social sharing icons into your page to help spread your content virally through networks like Facebook, Twitter and Google Plus',OP_SN),

            'step_graphics_description' => __('Use Step Graphics as a great way to illustrate numbered steps for a process on your pages',OP_SN),

            'tabs_description' => __('Use tabs to separate content but make it easily navigable without the user scrolling down the page.',OP_SN),

            'terms_conditions_description' => __('If you would like to require your visitors to agree to a set of terms & conditions before purchasing you can use this element.',OP_SN),

            'testimonials_description' => __('Use testimonials as a great way to provide social proof about your product or service.  Choose from a range of styles to fit your page',OP_SN),

            'tour_description' => __('These elements are a great way to provide a call to action for your visitors to purchase or get more information about your product or service',OP_SN),

            'two_column_block_description' => __('Add two columns of text (each 50% width) to an existing column of your page layout.',OP_SN),

            'vertical_spacing_description' => __('Add vertical spacing to a column of your layout.',OP_SN),

            'video_player_description' => __('Include a video on your page.  Use your own embed code from sites like YouTube, or select URL to use a video you have hosted with Amazon S3 or a similar service.',OP_SN),

            'video_lightbox_description' => __('Insert an image into your page which when clicked will load a video in a lightbox. Great for training pages or to keep feature pages more organised.',OP_SN),

        );
    }

    static function lang_key($key){
        if(isset(self::$lang_keys[$key])){
            return self::$lang_keys[$key];
        }
        return $key;
    }

    function _get_element_number($type){
        if(isset(self::$element_count[$type])){
            self::$element_count[$type]++;
        } else {
            self::$element_count[$type] = 1;
        }
        return 'op-asset-'.$type.'-'.self::$element_count[$type];
    }

    static function _asset_js(){
        $navs = wp_get_nav_menus();
        $nav_array = array();
        foreach($navs as $nav){
            $nav_array[$nav->slug] = $nav->name;
        }
        echo 'var op_nav_lists = '.json_encode($nav_array).';';
        // OPM is activated, get some things for the JS
        if (defined("WS_PLUGIN__OPTIMIZEMEMBER_VERSION")) {
            $opmOut = 'var OPMActivated = 1;';
            $temp = array();

            foreach (c_ws_plugin__optimizemember_pro_gateways::available_gateways () as $key => $val) {
                if (in_array($key, $GLOBALS["WS_PLUGIN__"]["optimizemember"]["o"]["pro_gateways_enabled"]) || $key === 'paypal') {
                    $temp[] = '\'' . $key . '\':\'' . ucfirst($key) . '\'';
                }
            }
            $opmOut .= 'var OPMPaymentGateways = {' .implode(',', $temp). '};';
            $temp = array();
            $temp[] = '\'\':\'---\'';
            for ($n = 1; $n <= $GLOBALS["WS_PLUGIN__"]["optimizemember"]["c"]["levels"]; $n++) /* Starting with Level #1 here. */
                {
                    $labelText = ws_plugin__optimizemember_getMembershipLabel($n);
                    $temp[] = '"' . $n . '":"' . str_replace('"', '', $labelText) . '"';
                }
            $opmOut .= 'var OPMLevels = {' .implode(',', $temp). '};';
            if (count($GLOBALS["WS_PLUGIN__"]["optimizemember"]["o"]["ccp"]) > 0) {
                $temp = array();
                $temp[] = '\'\':\'---\'';
                foreach($GLOBALS["WS_PLUGIN__"]["optimizemember"]["o"]["ccp"] as $key => $val) {
                    $temp[] = '\'' . $val . '\':\'' . $val . '\'';
                }
                $opmOut .= 'var OPMPackages = {' .implode(',', $temp). '};';
            } else {
                $temp = array();
                $temp[] = '\'\':\'---\'';
                $opmOut .= 'var OPMPackages = {' .implode(',', $temp). '};';
            }
            echo $opmOut;
        } else {
            echo 'var OPMActivated = 0;';
        }
        // Membership sidebar element variables
        $products = self::membership_array('product');
        $memOut = 'var opMembershipProducts = ' . $products[0];
        $memOut .= 'var showOnProducts = ' . $products[1];

        $categories = self::membership_array('category');
        $memOut .= 'var opMembershipCategories = ' . $categories[0];
        $memOut .= 'var showOnCategories = ' . $categories[1];

        $subCategories = self::membership_array('subcategory');
        $memOut .= 'var opMembershipSubCategories = ' . $subCategories[0];

        echo $memOut;

        /*
         * GoToWebinar
         */
        require_once(OP_MOD . 'email/ProviderFactory.php');
        $provider = OptimizePress_Modules_Email_ProviderFactory::getFactory('gotowebinar', true);
        if ($provider->isEnabled()) {
            echo 'var opGoToWebinarEnabled = false;';
        } else {
            echo 'var opGoToWebinarEnabled = true;';
        }

        // pageId
        global $post;
        echo 'var opPageId = \''.intval($post->ID).'\';';
    }

    static function membership_array($type, $selected_id=0, $parent_id=0) {
        global $wpdb;
        $temp[] = '\'\':\'\'';
        $t = array();
        $query = "SELECT o.id, o.post_parent, o.post_title FROM {$wpdb->prefix}posts o INNER JOIN {$wpdb->postmeta} p ON o.id = p.post_id WHERE p.meta_key = 'type' AND p.meta_value = '{$type}' ORDER BY o.post_title ASC";
        if($rows = $wpdb->get_results($query)){
            foreach($rows as $row){
                $rowId = $row->id;
                $t[] = $rowId;
                $title = $row->post_title;
                $temp[] = '"'. $row->post_parent . '-' . $rowId . '":"' . str_replace('"', '', $title) . '"';
            }
        }
        $output[0] = '{' .implode(',', $temp). '};';
        $output[1] = '[' . implode(',', $t) . '];';
        return $output;
    }



    function _hex2rgb($hex) {
       $hex = str_replace("#", "", $hex);

       if(strlen($hex) == 3) {
          $r = hexdec(substr($hex,0,1).substr($hex,0,1));
          $g = hexdec(substr($hex,1,1).substr($hex,1,1));
          $b = hexdec(substr($hex,2,1).substr($hex,2,1));
       } else {
          $r = hexdec(substr($hex,0,2));
          $g = hexdec(substr($hex,2,2));
          $b = hexdec(substr($hex,4,2));
       }
       $rgb = array($r, $g, $b);

       return $rgb; // returns an array with the rgb values
    }
}
OptimizePress_Default_Assets::init();
function op_shortcode_regex($tags=''){
    global $shortcode_tags;
    if($tags == ''){
        $tagnames = array_keys($shortcode_tags);
        $tags = join( '|', array_map('preg_quote', $tagnames) );
    }
    return '\[(\[?)('.$tags.')\b([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)';
}