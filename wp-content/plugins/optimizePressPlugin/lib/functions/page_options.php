<?php

class OptimizePress_Page_Options {
    private $_options = array();
    private $_configs = array();
    private $_page_id = null;
    private $_temp_filters = null;
    private $_disabled_filters = null;

    function __construct(){
        if(defined('OP_PAGEBUILDER_ID')){
            $this->_page_id = OP_PAGEBUILDER_ID;
        } elseif(is_admin() && op_get_var($_GET,'page') == OP_SN.'-page-builder' && isset($_GET['page_id'])){
            $this->_page_id = $_GET['page_id'];
        }
    }

    function get($args=array(),$page_id=0){
        if(!$this->_check_args($args,$page_id)){
            return false;
        }
        if(is_array($args[0])){
            $key = array_shift($args[0]);
            if(count($args[0]) == 0){
                array_shift($args);
            }
        } else {
            $key = is_array($args) ? array_shift($args) : $args;
        }
        /*
         * There was a PHP notice on the line #35 of array to string conversion ($key was an array). It was impossible to me (Luka) to figure out the what
         * would the correct thing to do is. The issue was with feature_area/optin(2)
         */
        if (is_array($key)) {
            $key = $key[0];
        }
        $name = '_'.OP_SN.'_'.$key;
        if(!isset($this->_options[$name])){
            if ($page_id == 0) {
                $page_id = $this->_page_id;
            }
            $temp = get_post_meta($page_id, $name, true);
            $this->_options[$name] = mb_unserialize($temp);
        }
        return _op_traverse_array($this->_options[$name],$args);
    }

    function delete($args=array()){
        if(!$this->_check_args($args)){
            return false;
        }
        $name = '_'.OP_SN.'_'.$args[0];
        if(count($args) > 1){
            $key = array_pop($args);
            if($opt = $this->get($args)){
                if(is_array($opt) && isset($opt[$key])){
                    unset($opt[$key]);
                }
                array_push($args,$opt);
                $this->update($args);
            }
        } else {
            if(isset($this->_options[$name])){
                unset($this->_options[$name]);
            }
            delete_post_meta($this->_page_id,$name);
        }
    }

    function update($args=array(),$page_id=0){
        if(!$this->_check_args($args,$page_id)){
            return false;
        }

        $name = '_'.OP_SN.'_'.$args[0];
        $val = array_pop($args);
        $cur = $this->get($args,$page_id);
        $update_val = false;
        if(count($args) > 1){
            $option = array_shift($args);
            $options = $this->get(array($option),$page_id);
            $options = $options ? $options : array();
            for($i=0,$al=count($args);$i<$al;$i++){
                $is_array = ($i >= $al-1);
                if(!isset($tmp)){
                    $tmp =& $options;
                }
                if(!isset($tmp[$args[$i]])){
                    $tmp[$args[$i]] = $is_array ? array() : false;
                }
                $tmp =& $tmp[$args[$i]];
            }
            $tmp = $val;
            $this->_options[$name] = $options;
            $update_val = $options;
        } else {
            $this->_options[$name] = $val;
            $update_val = $val;
        }

        if(isset($update_val)){
            update_post_meta(($page_id > 0 ? $page_id : $this->_page_id),$name,maybe_serialize($update_val));
        }
    }

    function theme_config($args=array()){
        static $page_type;
        if(!isset($page_type)){
            $page_type = $this->get(array('theme','type'));
        }
        if($this->_check_args($args)){
            $found = false;
            $config = array();
            if(is_array($args[0])){
                $key = array_shift($args[0]);
                if(count($args[0]) == 0){
                    array_shift($args);
                }
            } else {
                $key = array_shift($args);
            }
            if(!isset($this->_configs[$key])){
                $path = OP_PAGES.$page_type.'/'.$key;
                $theme_url = OP_URL.'pages/'.$page_type.'/'.$key.'/';
                if(file_exists($path.'/config.php')){
                    op_textdomain(OP_SN.'_p_'.$key,$path.'/');
                    require_once $path.'/config.php';
                    $this->_configs[$key] = $config;
                    return _op_traverse_array($this->_configs[$key],$args);
                }
            } else {
                return _op_traverse_array($this->_configs[$key],$args);
            }
        }
        return false;
    }

    function _check_args($args=array(),$page_id=0){
        if(is_null($this->_page_id) && $page_id < 1){
            return false;
        }
        if(count($args) == 0){
            return false;
        }
        return true;
    }

    function load_layout($type='body',$array=false,$id='',$class='',$default=array(),$one_col=false){
        global $wpdb;

        // are we trying to get the revision?
        if (isset($_GET['op_revision_id']) && !empty($_GET['op_revision_id']) && is_user_logged_in()) {
            $revisionId = esc_attr($_GET['op_revision_id']);
            $entry = $wpdb->get_var( $wpdb->prepare(
                "SELECT layout FROM `{$wpdb->prefix}optimizepress_post_layouts` WHERE `id` = %d",
                $revisionId
            ));
        } else {
            $entry = $wpdb->get_var( $wpdb->prepare(
                "SELECT layout FROM `{$wpdb->prefix}optimizepress_post_layouts` WHERE `post_id` = %d AND `type` = %s AND `status` = 'publish'",
                $this->_page_id,
                $type
            ));
        }
        $layout = $default;
        if($entry){
            $layout = unserialize(base64_decode($entry));
        }
        if($array){
            return $layout;
        }
        if (('feature_area_liveeditor_above' == $type || 'feature_area_liveeditor_below' == $type) && !is_admin()) {
            $layout = $this->generate_layout($layout, $type, $one_col);
            if (!empty($layout)) {
                return '<div class="row cf"><div class="fixed-width"><div id="'.$id.'" class="'.$class.'"'.(defined('OP_LIVEEDITOR')?' data-layout="'.$type.'" data-one_col="'.($one_col?'Y':'N').'"':'').'>'.$layout.'</div></div></div>';
            }
        } else {
            return '<div id="'.$id.'" class="'.$class.'"'.(defined('OP_LIVEEDITOR')?' data-layout="'.$type.'" data-one_col="'.($one_col?'Y':'N').'"':'').'>'.$this->generate_layout($layout,$type,$one_col).'</div>';
        }
    }

    /**
     *
     * Generate inline CSS from array of params for row styling options!
     * @param object
     */
    function generateRowStyle($style)
    {
        // gradient
        if (!isset($style->backgroundImage) || (isset($style->backgroundImage) && null === $style->backgroundImage)) {
            if ((isset($style->backgroundColorStart) && null !== $style->backgroundColorStart) && (isset($style->backgroundColorEnd) && null !== $style->backgroundColorEnd)) {
                $styles['container']['background'][] = $style->backgroundColorStart;
                $styles['container']['background'][] = '-webkit-gradient(linear, left top, left bottom, color-stop(0%, ' . $style->backgroundColorStart . '), color-stop(100%, ' . $style->backgroundColorEnd .'))';
                $styles['container']['background'][] = '-webkit-linear-gradient(top, ' . $style->backgroundColorStart . ' 0%, ' . $style->backgroundColorEnd . ' 100%)';
                $styles['container']['background'][] = '-moz-linear-gradient(top, ' . $style->backgroundColorStart . ' 0%, ' . $style->backgroundColorEnd. ' 100%)';
                $styles['container']['background'][] = '-ms-linear-gradient(top, ' . $style->backgroundColorStart . ' 0%, ' . $style->backgroundColorEnd . ' 100%)';
                $styles['container']['background'][] = '-o-linear-gradient(top, ' . $style->backgroundColorStart . ' 0%, ' . $style->backgroundColorEnd . ' 100%)';
                $styles['container']['background'][] = 'linear-gradient(to bottom, ' . $style->backgroundColorStart . ' 0%, ' . $style->backgroundColorEnd . ' 100%)';
                $styles['container']['filter'] = 'progid:DXImageTransform.Microsoft.gradient(startColorstr=' . $style->backgroundColorStart . ', endColorstr=' . $style->backgroundColorEnd . ', GradientType=0)';
            } else if (isset($style->backgroundColorStart) && null !== $style->backgroundColorStart) {
                $styles['container']['background'][] = $style->backgroundColorStart;
            }
        } else {
            if (isset($style->backgroundPosition)) {
                switch ($style->backgroundPosition) {
                    case 'center':
                        $styles['container']['background-image'] = $style->backgroundImage;
                        $styles['container']['background-repeat'] = 'no-repeat';
                        $styles['container']['background-position'] = 'center';
                    break;
                    case 'cover':
                        $styles['container']['background-image'] = $style->backgroundImage;
                        $styles['container']['background-repeat'] = 'no-repeat';
                        $styles['container']['background-size'] = 'cover';
                    break;
                    case 'tile_horizontal':
                        $styles['container']['background-image'] = $style->backgroundImage;
                        $styles['container']['background-repeat'] = 'repeat-x';
                    break;
                    case 'tile':
                        $styles['container']['background-image'] = $style->backgroundImage;
                        $styles['container']['background-repeat'] = 'repeat';
                    break;
                }
            }
            if (isset($style->backgroundColorStart) && null !== $style->backgroundColorStart) {
                $styles['container']['background-color'] = $style->backgroundColorStart;
            }
        }
        // padding top
        if (isset($style->paddingTop) && null !== $style->paddingTop) {
            $styles['container']['padding-top'] = $style->paddingTop . 'px';
        }
        // padding bottom
        if (isset($style->paddingBottom) && null !== $style->paddingBottom) {
            $styles['container']['padding-bottom'] = $style->paddingBottom . 'px';
        }
        // border width
        if (isset($style->borderWidth) && null !== $style->borderWidth) {
            $styles['container']['border-top-width'] = $style->borderWidth . 'px';
            $styles['container']['border-bottom-width'] = $style->borderWidth . 'px';
            $styles['container']['border-style'] = 'solid';
        }
        // border color
        if (isset($style->borderColor) && null !== $style->borderColor) {
            $styles['container']['border-top-color'] = $style->borderColor;
            $styles['container']['border-bottom-color'] = $style->borderColor;
        }
        $style_content = '';
        if (!empty ($styles)) {
            foreach ($styles['container'] as $property => $value) {
                switch ($property) {
                    case 'background':
                        foreach ($value as $item) {
                            $style_content .= $property . ':' . $item . ';';
                        }
                        break;
                    default:
                        $style_content .= $property . ':' . $value . ';';
                }
            }
        }

        return "style='" . $style_content . "' ";
    }

    function generate_layout($layout,$type,$one_col=false)
    {
        $this->remove_disabled_filters();

        $row_start = $row_end = $element_start = $element_end = $col_start = $col_end = '';
        $measures = array(
            'split-half' => 0.5,
            'split-one-third' => 0.33,
            'split-two-thirds' => 0.66,
            'split-one-fourth' => 0.25,
            'split-three-fourths' => 0.75
        );
        if(defined('OP_LIVEEDITOR')){
            if($one_col && count($layout) == 0){
                $layout = array(
                    array(
                        'row_class' => 'row one-col cf ui-sortable',
                        'columns' => array(
                            array(
                                'col_class' => 'one column cols',
                                'elements' => array()
                            )
                        ),
                        'children' => array(
                            array(
                                'col_class' => 'one column cols',
                                'elements' => array()
                            )
                        ),
                    )
                );
            }
            $row_start = ($one_col?'':'<div class="op-row-links"><div class="op-row-links-content"><a title="'.__('Copy Row', OP_SN).'" href="#copy-row" class="copy-row"></a><a title="'.__('Edit Row Options', OP_SN).'" href="#options" class="edit-row" id="row_options"></a><a title="'.__('Clone Row', OP_SN).'" href="#clone-row" class="clone-row"></a><a href="#add-new-row" class="add-new-row"><img src="'.OP_IMG.'/live_editor/add_new.png" alt="'.__('Add New Row',OP_SN).'" /><span>'.__('Add New Row',OP_SN).'</span></a><a title="'.__('Move Row', OP_SN).'" href="#move" class="move-row"></a><a title="'.__('Paste Row', OP_SN).'" href="#paste-row" class="paste-row"></a><a title="'.__('Delete Row', OP_SN).'" href="#delete-row" class="delete-row"></a></div></div>');
            $row_end = '';
            $col_start = $subcol_start = '';//<div class="op-col-links"><a class="move-col" href="#move"><img alt="'.__('Move',OP_SN).'" src="'.OP_IMG.'move-icon.png" /></a></div>';
            $col_end = '<div class="element-container sort-disabled"></div><div class="add-element-container"><a href="#add_element" class="add-new-element"><img src="'.OP_IMG.'/live_editor/add_new.png" alt="'.__('Add Element',OP_SN).'" /><span>'.__('Add Element',OP_SN).'</span></a></div>';
            $element_start = '<div class="op-element-links"><a class="element-settings" koko="setingkoko" href="#settings"><img alt="'.__('Edit Element',OP_SN).'" title="'.__('Edit Element',OP_SN).'" src="'.OP_IMG.'pencil.png" /></a><a class="element-clone" href="#clone-element"><img alt="'.esc_attr__('Clone Element',OP_SN).'" title="'.esc_attr__('Clone Element',OP_SN).'" src="'.OP_IMG.'pencil.png" /></a><a class="element-advanced" href="#op-le-advanced"><img alt="'.__('Advanced Element Options',OP_SN).'" title="'.__('Advanced Element Options',OP_SN).'" src="'.OP_IMG.'pencil.png" /></a><a class="element-move" href="#move"><img alt="'.__('Move',OP_SN).'" src="'.OP_IMG.'move-icon.png" /></a><a class="element-delete" href="#delete"><img alt="'.__('Remove Element',OP_SN).'" src="'.OP_IMG.'remove-row.png" /></a></div><div class="op-hidden op-waiting"><img class="op-bsw-waiting op-show-waiting" alt="" src="images/wpspin_light.gif" /></div>';
            $element_end = '<div class="op-hidden"><textarea class="op-le-shortcode" name="shortcode[]">{element_str}</textarea></div>';
            $after_element = '<div class="element-container sort-disabled"></div><div class="add-element-container"><a href="#add_element" class="add-new-element"><img src="'.OP_IMG.'/live_editor/add_new.png" alt="'.__('Add Element',OP_SN).'" /><span>'.__('Add Element',OP_SN).'</span></a></div>';
        }
        $html = '';
        $pref = 'le_'.$type.'_row_';
        $rcounter = 1;
        $le = defined('OP_LIVEEDITOR');
        $clear = '';
        //check for default wordpress password protection
        global $post;
        if (!$le && post_password_required($post->ID)) {
            $html .= '<div class="row one-column cf ui-sortable"><div class="fixed-width">';
            $html .= get_the_password_form();
        } else {
            foreach($layout as $row){
                // generating new styles from row_data_styles!!!
                if (!empty($row['row_data_style'])) {
                    $rowStyle = base64_decode($row['row_data_style']);
                    $rowStyle = json_decode($rowStyle);
                    $row_style = $this->generateRowStyle($rowStyle);
                } else {
                    $row_style = '';
                    $rowStyle = '';
                }
                if (!isset($row['row_data_style'])) {
                    $row['row_data_style'] = '';
                }
                if (isset($rowStyle->codeBefore) and !empty($rowStyle->codeBefore)) {
                    if ($le) {
                        $html .= '<span class="op-row-code-before">'.$rowStyle->codeBefore.'</span>';
                    } else {
                        $html .= $rowStyle->codeBefore;
                    }
                }
                $html .= '
                    <div ' . $row_style . ' class="'.$row['row_class'].'" id="'.$pref.$rcounter.'" data-style="' . $row['row_data_style'] . '"><div class="fixed-width">'.$row_start;
                $ccounter = 1;
                foreach($row['children'] as $col) {
                    //do we split or not
                    switch ($col['col_class']) {
                        case 'one-half column cols':
                        case 'two-thirds column cols':
                        case 'two-fourths column cols':
                        case 'three-fourths column cols':
                        case 'three-fifths column cols':
                        case 'four-fifths column cols':
                            $td = substr($col['col_class'], 0, -12);
                            $splitColumns = '<a href="#'.$td.'" class="split-column"><img src="'.OP_IMG.'live_editor/split_column.png" alt="Split Column" /></a>';
                        break;
                        default:
                            $splitColumns = '';
                        break;
                    }
                    if (is_admin()) {
                        $col_end = '<div class="element-container sort-disabled"></div><div class="add-element-container">'.$splitColumns.'<a href="#add_element" class="add-new-element"><img src="'.OP_IMG.'/live_editor/add_new.png" alt="'.__('Add Element',OP_SN).'" /><span>'.__('Add Element',OP_SN).'</span></a></div>';
                    }
                    $html .= '
                        <div class="'.$col['col_class'].'" id="'.$pref.$rcounter.'_col_'.$ccounter.'">'.$col_start;
                    if (!empty($col['children']) and count($col['children'])) {
                        $ecounter = 1;
                        $elNumber = 1;
                        $subcolNumber = 100;
                        $subcolumn = false;
                        $nrChildren = count($col['children']);
                        $previous = '';
                        $subcounter = 0;
                        $fullWidth = 0;
                        foreach($col['children'] as $child) {
                            $flag = false;
                            if ($child['type'] != $previous && $previous != '') {
                                $flag = true;
                            }
                            if ($ecounter == $nrChildren && $subcolumn === true && $child['type'] != 'element') {
                                $clear .= '<div class="clearcol"></div>';
                                $subcolumn = false;
                            } else {
                                $clear = '';
                            }
                            switch ($child['type']) {
                                case 'element':
                                    if ($subcolumn === true) {
                                        $html .= '<div class="clearcol"></div>';
                                        $subcolumn = false;
                                        $flag = false;
                                    }
                                    $GLOBALS['OP_LIVEEDITOR_DEPTH'] = 0;
                                    $GLOBALS['OP_PARSED_SHORTCODE'] = '';
                                    $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = array();
                                    $GLOBALS['OP_LIVEEDITOR_DISABLE_NEW'] = false;
                                    $sc = op_fix_embed_url_shortcodes(stripslashes($child['object']));
                                    // removing new line before shortcode entered in content
                                    $sc = str_replace(array("\n[", "\r[", "\r\n[", "\n\r["), array("[", "[", "[", "["), $sc);
                                    // getting and processing before and after elements
                                    $elemBefore = '';
                                    $elemAfter = '';
                                    if (empty($child['element_class'])) {
                                        $elClass = 'element-container cf';
                                    } else {
                                        $elClass = $child['element_class'];
                                    }
                                    if (!empty($child['element_data_style'])) {
                                        $elementStyle = base64_decode($child['element_data_style']);
                                        $elementStyle = json_decode($elementStyle);
                                        if (!empty($elementStyle->codeBefore)) {
                                            $elemBefore = $elementStyle->codeBefore;
                                        }
                                        if (!empty($elementStyle->codeAfter)) {
                                            $elemAfter = $elementStyle->codeAfter;
                                        }
                                        if (!empty($elementStyle->fadeIn)) {
                                            $data_fade = ' data-fade="' . $elementStyle->fadeIn . '" style="display:none;" ';
                                        } else {
                                            $data_fade = ' ';
                                        }
                                        $elementDataStyle = $child['element_data_style'];
                                    } else {
                                        $elemBefore = ' ';
                                        $elemAfter = ' ';
                                        $data_fade = ' ';
                                        $elementDataStyle = '';
                                    }
                                    // $html .= '<div class="'.$elClass.'"'.$data_fade.'data-style="'.$elementDataStyle.'" id="'.$pref.$rcounter.'_col_'.$ccounter.'_el_'.$elNumber.'">'.$element_start;
                                    $new_element_start = $element_start;
                                    if(preg_match('/'.op_shortcode_regex('op_liveeditor_elements').'/s',$sc,$matches)){
                                        $GLOBALS['OP_LIVEEDITOR_DISABLE_NEW'] = true;
                                        $sc = str_replace($matches[0],'#OP_CHILD_ELEMENTS#',$sc);
                                        $GLOBALS['OP_LIVEEDITOR_DEPTH'] = 1;

                                        $child_data = op_page_parse_child_elements($matches[0]);
                                        $matches[0] = $child_data['liveeditorElements'];
                                        $processed = apply_filters('the_content',$sc);

                                        $child_html = op_process_asset_content(apply_filters('the_content',$matches[0])).($le?'<div class="op-hidden"><textarea class="op-le-child-shortcode" name="shortcode[]">'.op_attr(shortcode_unautop($matches[0])).'</textarea></div>':'');
                                        /*
                                         * $ needs to be escaped
                                         */
                                        $child_html = str_replace('$', '\$', $child_html);
                                        $processed = preg_replace(array('{<p[^>]*>\s*#OP_CHILD_ELEMENTS#\s*<\/p>}i','{#OP_CHILD_ELEMENTS#}i'),$child_html,$processed);
                                        if (defined('OP_LIVEEDITOR')) {
                                            $new_element_start = substr($element_start, 0, 30) . '<a class="element-parent-settings" xtrasett="daasex" href="#parent-settings"><img alt="'.__('Edit Parent Element',OP_SN).'" title="'.__('Edit Parent Element',OP_SN).'" src="'.OP_IMG.'pencil.png" /></a>' . substr($element_start, 30);
                                        }
                                    } else {
                                        $processed = apply_filters('the_content',$sc);
                                    }

                                    if (strpos($sc, '[op_popup ') !== false && defined('OP_LIVEEDITOR')) {
                                        $new_element_start = substr($element_start, 0, 30) . '<a class="element-parent-settings" xtrasett="daasex" href="#parent-settings"><img alt="'.__('Edit Parent Element',OP_SN).'" title="'.__('Edit Parent Element',OP_SN).'" src="'.OP_IMG.'pencil.png" /></a>' . substr($element_start, 30);
                                    }

                                    $html .= '<div class="'.$elClass.'"'.$data_fade.'data-style="'.$elementDataStyle.'" id="'.$pref.$rcounter.'_col_'.$ccounter.'_el_'.$elNumber.'">'.$new_element_start;
                                    //$html .= $elemBefore .'<div class="element">' . $processed . '</div>' . $elemAfter;
                                    $content = $elemBefore. $processed .$elemAfter;
                                    if (!is_admin()) {
                                        $content = do_shortcode($content);
                                    }
                                    $html .= '<div class="element">' .$content. '</div>';
                                    if(isset($GLOBALS['OP_PARSED_SHORTCODE']) && !empty($GLOBALS['OP_PARSED_SHORTCODE'])){
                                        $sc = $GLOBALS['OP_PARSED_SHORTCODE'];
                                    }
                                    $html .= str_replace('{element_str}',op_attr($sc),$element_end).'</div>';
                                    if ($flag && $ecounter < $nrChildren) {
                                        $html .= $after_element;
                                    }
                                    $elNumber++;
                                    $previous = 'element';
                                break;
                                case 'subcolumn':
                                    if ($previous == '') {
                                        $html .= $after_element;
                                    }
                                    if ($flag == true) {
                                        $html .= $after_element;
                                    }
                                    $temp = explode(' ', $child['subcol_class']);
                                    if (!$flag && ($fullWidth == 1 || $fullWidth == 0.99)) {
                                        $html .= '<div class="clearcol"></div>' . $after_element;
                                        $fullWidth = 0;
                                    }
                                    $subcolumn = true;
                                    $html .= '<div class="'.$child['subcol_class'].'" id="'.$pref.$rcounter.'_col_'.$subcolNumber.'">'.$subcol_start;
                                    if (!empty($child['children']) and count($child['children']) > 0) {
                                        //elements
                                        $elNumber = 1;
                                        foreach ($child['children'] as $kid) {
                                            $GLOBALS['OP_LIVEEDITOR_DEPTH'] = 0;
                                            $GLOBALS['OP_PARSED_SHORTCODE'] = '';
                                            $GLOBALS['OP_LIVEEDITOR_FONT_STR'] = array();
                                            $GLOBALS['OP_LIVEEDITOR_DISABLE_NEW'] = false;
                                            $sc = op_fix_embed_url_shortcodes(stripslashes($kid['object']));
                                            // removing new line before shortcode entered in content
                                            $sc = str_replace(array("\n[", "\r[", "\r\n[", "\n\r["), array("[", "[", "[", "["), $sc);
                                            // getting and processing before and after elements
                                            $elemBefore = '';
                                            $elemAfter = '';
                                            if (empty($kid['element_class'])) {
                                                $elClass = 'element-container cf';
                                            } else {
                                                $elClass = $kid['element_class'];
                                            }
                                            if (!empty($kid['element_data_style'])) {
                                                $elementStyle = base64_decode($kid['element_data_style']);
                                                $elementStyle = json_decode($elementStyle);
                                                if (!empty($elementStyle->codeBefore)) {
                                                    $elemBefore = $elementStyle->codeBefore;
                                                }
                                                if (!empty($elementStyle->codeAfter)) {
                                                    $elemAfter = $elementStyle->codeAfter;
                                                }
                                                if (!empty($elementStyle->fadeIn)) {
                                                    $data_fade = ' data-fade="' . $elementStyle->fadeIn . '" style="display:none;" ';
                                                } else {
                                                    $data_fade = '';
                                                }
                                                $elementDataStyle = $kid['element_data_style'];
                                            } else {
                                                $elemBefore = ' ';
                                                $elemAfter = ' ';
                                                $data_fade = ' ';
                                                $elementDataStyle = '';
                                            }
                                            // $html .= '<div class="'.$elClass.'"'.$data_fade.'data-style="'.$elementDataStyle.'" id="'.$pref.$rcounter.'_col_'.$subcolNumber.'_el_'.$elNumber.'">'.$element_start;
                                            $new_element_start = $element_start;
                                            if(preg_match('/'.op_shortcode_regex('op_liveeditor_elements').'/s',$sc,$matches)){
                                                $GLOBALS['OP_LIVEEDITOR_DISABLE_NEW'] = true;
                                                $sc = str_replace($matches[0],'#OP_CHILD_ELEMENTS#',$sc);
                                                $processed = apply_filters('the_content',$sc);
                                                $GLOBALS['OP_LIVEEDITOR_DEPTH'] = 1;
                                                $child_data = op_page_parse_child_elements($matches[0]);
                                                $matches[0] = $child_data['liveeditorElements'];
                                                $child_html = op_process_asset_content(apply_filters('the_content',$matches[0])).($le?'<div class="op-hidden"><textarea class="op-le-child-shortcode" name="shortcode[]">'.op_attr(shortcode_unautop($matches[0])).'</textarea></div>':'');
                                                /*
                                                 * $ needs to be escaped
                                                 */
                                                $child_html = str_replace('$', '\$', $child_html);
                                                $processed = preg_replace(array('{<p[^>]*>\s*#OP_CHILD_ELEMENTS#\s*<\/p>}i','{#OP_CHILD_ELEMENTS#}i'),$child_html,$processed);
                                                if (defined('OP_LIVEEDITOR')) {
                                                    $new_element_start = substr($element_start, 0, 30) . '<a class="element-parent-settings" href="#parent-settings"><img alt="'.__('Edit Parent Element',OP_SN).'" title="'.__('Edit Parent Element',OP_SN).'" src="'.OP_IMG.'pencil.png" /></a>' . substr($element_start, 30);
                                                }
                                            } else {
                                                $processed = apply_filters('the_content',$sc);
                                            }
                                            $html .= '<div class="'.$elClass.'"'.$data_fade.'data-style="'.$elementDataStyle.'" id="'.$pref.$rcounter.'_col_'.$subcolNumber.'_el_'.$elNumber.'">'.$new_element_start;
                                            //$html .= $elemBefore .'<div class="element">' . $processed . '</div>' . $elemAfter;
                                            $content = $elemBefore. $processed .$elemAfter;
                                            if (!is_admin()) {
                                                $content = do_shortcode($content);
                                            }
                                            $html .= '<div class="element">' .$content . '</div>';
                                            if(isset($GLOBALS['OP_PARSED_SHORTCODE']) && !empty($GLOBALS['OP_PARSED_SHORTCODE'])){
                                                $sc = $GLOBALS['OP_PARSED_SHORTCODE'];
                                            }
                                            $html .= str_replace('{element_str}',op_attr($sc),$element_end).'</div>';
                                            $previous = 'element';
                                            $elNumber++;
                                        }
                                        $html .= $after_element;
                                        $subcolNumber++;
                                    } else {
                                        $html .= $after_element;
                                    }
                                    $html .= $subcol_end.'</div>';
                                    $next = next($child['children']);
                                    $html .= $clear;
                                    $previous = 'subcolumn';
                                    $subcounter++;
                                    $fullWidth += $measures[$temp[0]];
                                break;
                            }
                            $ecounter++;
                        }
                    }

                    $ccounter++;
                    $html .= $col_end . '</div>';
                }
                $html .= $row_end . '</div></div>';

                if (isset($rowStyle->codeAfter) and !empty($rowStyle->codeAfter)) {
                    if ($le) {
                        $html .= '<span class="op-row-code-after">'.$rowStyle->codeAfter.'</span>';
                    } else {
                        $html .= $rowStyle->codeAfter;
                    }
                }
                $rcounter++;
            } // end row foreach
        } // end else

        $this->revert_disabled_filters();

        // return normal content in LE, but parse shortcodes on frontend to deal with code before and after rows!
        if ($le) {
            return $html;
        } else {
            return do_shortcode($html);
        }

    }

    function remove_disabled_filters()
    {
        global $wp_filter;

        if (null === $this->_temp_filters) {
            $temp_filters = array();
            $disabled_filters = $this->get_disabled_filters();

            if (!empty($disabled_filters)) {
                foreach ($wp_filter['the_content'] as $priority => $filters) {
                    foreach ($filters as $id => $filter) {
                        if (is_string($filter['function'])) {
                            $name = $filter['function'];
                        } else {
                            $name = $filter['function'][1];
                        }
                        if (!in_array($name, $disabled_filters)) {
                            $temp_filters[$priority][$id] = $filter;
                        }
                    }
                }

                $this->_temp_filters = $temp_filters;
            } else {
                $this->_temp_filters = $wp_filter['the_content'];
            }
        }

        $temp                       = $wp_filter['the_content'];
        $wp_filter['the_content']   = $this->_temp_filters;
        $this->_temp_filters        = $temp;
    }

    function get_disabled_filters()
    {
        if (null === $this->_disabled_filters) {
            $filters = op_default_option('advanced_filter');

            $disabled = array();
            if (!empty($filters)) {
                foreach ($filters as $key => $filter) {
                    if ($filter === '1') {
                        $disabled[] = $key;
                    }
                }
            }

            $this->_disabled_filters = $disabled;
        }
        return $this->_disabled_filters;
    }

    function revert_disabled_filters()
    {
        global $wp_filter;

        $temp                       = $wp_filter['the_content'];
        $wp_filter['the_content']   = $this->_temp_filters;
        $this->_temp_filters        = $temp;
    }

    function update_layout($layout,$type='body'){
        global $wpdb;
        //echo serialize($layout);exit;
        //echo $type.' ==== '.print_r($layout,true);exit;
        $table = $wpdb->prefix.'optimizepress_post_layouts';
        /*$entry = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM `{$table}` WHERE `post_id` = %d AND `type` = %s",
            $this->_page_id,
            $type
        ));*/
        $entry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$table}` WHERE `post_id` = %d AND `type` = %s AND `status` = 'publish'",
                $this->_page_id,
                $type
            )
        );

        $layout = base64_encode(serialize($layout));
        if (null == $entry) {
            $wpdb->insert($table,array('post_id' => $this->_page_id, 'type' => $type, 'layout' => $layout));
        } else {
            // actually saving only if changes are present
            if ($layout !== $entry->layout) {
                // adding a revision by copying last published entry
                $wpdb->insert($table, array(
                    'post_id'   => $entry->post_id,
                    'type'      => $entry->type,
                    'layout'    => $entry->layout,
                    'status'    => 'revision'
                    //'modified'  => $entry->modified
                ));
                //
                $wpdb->update($table, array('layout' => $layout), array('id' => $entry->id));
                // delete obsolete revisions
                $revisions = $wpdb->get_results($wpdb->prepare(
                    "SELECT id FROM `{$table}` WHERE `post_id` = %d AND `type` = %s AND status = 'revision' ORDER BY modified",
                    $this->_page_id,
                    $type
                ));

                // delete only if number of revisions is higher than configuration number
                if (count($revisions) > OP_REVISION_NUMBER) {
                    $deleteNr = count($revisions) - OP_REVISION_NUMBER;

                    $i = 0;
                    foreach ($revisions as $revision) {
                        if ($i == $deleteNr) break;
                        $wpdb->delete($table, array('id' => $revision->id));
                        $i++;
                    }
                }
            }
        }
    }

    function clearSettings()
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'postmeta';
        $sql = "DELETE FROM $tableName WHERE post_id = ".$this->_page_id." AND meta_key LIKE '_optimizepress_%' AND meta_key != '_optimizepress_pagebuilder'";

        $wpdb->query($sql);
    }

    /**
     * Removes layouts for current page that aren't updated or created in this cycle
     * @param  array $types
     * @return void
     */
    function clean_layouts($types)
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'optimizepress_post_layouts';

        /*
         * If there are types that was created/updated in this cycle we won't delete them
         */
        if (count($types) > 0) {
            $preparedQuery = sprintf(
                "DELETE FROM %s WHERE type NOT IN ('%s') AND post_id = %d",
                $tableName,
                implode("','", $types),
                $this->_page_id
            );
        } else {
            $preparedQuery = $wpdb->prepare(
                "DELETE FROM $tableName WHERE post_id = %d",
                $this->_page_id
            );
        }
        $wpdb->query($preparedQuery);
    }

    /**
     * Parses shortcode with child elements and adds advanced options to it
     *
     * @param  [string] $liveeditor_elements_sc [liveeditor elements shortcode]
     * @return [array] child advanced options
     */
    function parse_child_elements($liveeditor_elements_sc){

        $childRows = '[op_liveeditor_elements] ';
        preg_match_all('/\[op_liveeditor_element[ d|\]].*?\[\/op_liveeditor_element\]/is', $liveeditor_elements_sc, $children);
        foreach($children[0] as $child) {
            $childDataStyle = '';
            preg_match('/data-style="(.*?)"{1}/i', $child, $childDataStyle);

            $childElementStyle = base64_decode($childDataStyle[1]);
            $childElementStyle = json_decode($childElementStyle);

            if (!empty($childElementStyle->codeBefore)) {
                $childElemBefore = $childElementStyle->codeBefore;
            } else {
                $childElemBefore = '';
            }

            if (!empty($childElementStyle->codeAfter)) {
                $childElemAfter = $childElementStyle->codeAfter;
            } else {
                $childElemAfter = '';
            }

            if (!empty($childElementStyle->fadeIn)) {
                $childDataFade = ' data-fade="' . $childElementStyle->fadeIn . '"';
                $childDataFade .= defined('OP_LIVEEDITOR') ? '' : ' style="display:none;" ';
            } else {
                $childDataFade = ' ';
            }

            if (!empty($childElementStyle->advancedClass)) {
                $childAdvancedClass = $childElementStyle->advancedClass;
            } else {
                $childAdvancedClass = '';
            }

            $hideClasses = '';
            if (isset($childElementStyle->hideMobile) && !empty($childElementStyle->hideMobile))  {
                $hideClasses .= ' hide-mobile';
            }
            if (isset($childElementStyle->hideTablet) && !empty($childElementStyle->hideTablet))  {
                $hideClasses .= ' hide-tablet';
            }

            $child = $childElemBefore . $child . $childElemAfter;
            $childRows .= '<div class="row element-container cf ' . $childAdvancedClass . $hideClasses . '"' . $childDataFade . '>' . $child . '</div>';
        }
        $childRows .= '[/op_liveeditor_elements]';

        return array(
            'liveeditorElements' => $childRows,
            'childElemBefore' => $childElemBefore,
            'childElemAfter' => $childElemAfter,
            'childDataFade' => $childDataFade,
            'childAdvancedClass' => $childAdvancedClass
        );

    }
}
function _op_page_func(){
    static $op_ops;
    if(!isset($op_ops)){
        $op_ops = new OptimizePress_Page_Options;
    }
    $args = func_get_args();
    $func = array_shift($args);
    return call_user_func_array(array($op_ops,$func),$args);
}
function op_page_generate_layout($layout=array(),$type='body'){
    return _op_page_func('generate_layout',$layout,$type);
}
function op_page_layout($type='body',$array=false,$id='',$class='',$default=array(),$one_col=false){
    return _op_page_func('load_layout',$type,$array,$id,$class,$default,$one_col);
}
function op_page_update_layout($layout,$type='body'){
    return _op_page_func('update_layout',$layout,$type);
}

function op_page_parse_child_elements($shortcode){
    return _op_page_func('parse_child_elements',$shortcode);
}
/**
 * Remove layouts that are not used
 * @param  array $types
 * @return void
 */
function op_page_clean_layouts($types)
{
    return _op_page_func('clean_layouts', $types);
}

function op_page_clear_settings()
{
    return _op_page_func('clearSettings');
}
function op_page_option(){
    $args = func_get_args();
    return _op_page_func('get',$args);
}
function op_update_page_option(){
    $args = func_get_args();
    return _op_page_func('update',$args);
}
function op_load_page_config(){
    $args = func_get_args();
    return _op_page_func('theme_config',$args);
}
function op_page_config(){
    static $tpl_dir;
    if(!isset($tpl_dir)){
        $tpl_dir = op_page_option('theme','dir');
    }
    $args = func_get_args();
    array_unshift($args,$tpl_dir);
    return _op_page_func('theme_config',$args);
}

function op_default_page_option(){
    static $tpl_dir;
    if(!isset($tpl_dir)){
        $tpl_dir = op_page_option('theme','dir');
    }
    $args = func_get_args();
    if(($option = _op_page_func('get',$args)) === false){
        array_unshift($args,$tpl_dir,'default_config');
        $option = _op_page_func('theme_config',$args);
    }
    return $option === false ? '' : $option;
}
function op_page_attr(){
    $args = func_get_args();
    return op_attr(call_user_func_array('op_default_page_option',$args));
}
function op_page_attr_e(){
    $args = func_get_args();
    echo op_attr(call_user_func_array('op_default_page_option',$args));
}
function op_delete_page_option(){
    $args = func_get_args();
    return _op_page_func('delete',$args);
}
function op_update_page_id_option($page_id,$args){
    if(!is_array($args)){
        $args = array($args);
    }
    return _op_page_func('update',$args,$page_id);
}
function op_page_id_option($page_id,$args){
    if(!is_array($args)){
        $args = array($args);
    }
    return _op_page_func('get',$args,$page_id);
}
function op_page_set_saved_settings($result,$keep_options=array()){
    $get_layout = true;
    $merge_scripts = false;
    $layout_settings = unserialize(base64_decode($result->settings));
    foreach($keep_options as $keep){
        if($keep == 'content'){
            $get_layout = false;
        } elseif($keep == 'scripts'){
            $merge_scripts = true;
        } elseif($keep == 'color_scheme'){
            if(isset($layout_settings['color_scheme_advanced'])){
                unset($layout_settings['color_scheme_advanced']);
            }
            if(isset($layout_settings['color_scheme_template'])){
                unset($layout_settings['color_scheme_template']);
            }
        } elseif(isset($layout_settings[$keep])){
            unset($layout_settings[$keep]);
        }
    }
    foreach($layout_settings as $option => $settings){
        if(!empty($settings)){
            $settings = unserialize(base64_decode($settings));
            $current = op_page_option($option);
            if($option == 'scripts'){
                if($merge_scripts === true){
                    $new_scripts = array();
                    $script_opts = array('header','footer','css');
                    foreach($script_opts as $opt){
                        $cur = op_get_var($current,$opt,array());
                        $new = op_get_var($settings,$opt,array());
                        foreach($new as $n){
                            $cur[] = $n;
                        }
                        $new_scripts[$opt] = $cur;
                    }
                    $current = $new_scripts;
                } else {
                    $current = $settings;
                }
            } else {
                if(is_array($current) && is_array($settings)){
                    $current = array_merge($current,$settings);
                } else {
                    $current = $settings;
                }
            }
            op_update_page_option($option,$current);
        }
    }
    if($get_layout === true){
        $layouts = unserialize(base64_decode($result->layouts));
        if (is_array($layouts)) {
            foreach($layouts as $type => $layout){
                op_page_update_layout($layout,$type);
            }
        }
    }
}