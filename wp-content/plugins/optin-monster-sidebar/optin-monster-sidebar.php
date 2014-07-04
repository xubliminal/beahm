<?php
/**
 * OptinMonster is the #1 lead generation and email list building tool.
 *
 * @package   OptinMonster
 * @author    Thomas Griffin
 * @license   GPL-2.0+
 * @link      http://optinmonster.com/
 * @copyright 2013 Retyp, LLC. All rights reserved.
 *
 * @wordpress-plugin
 * Plugin Name:  OptinMonster Sidebar
 * Plugin URI:   http://optinmonster.com/
 * Description:  Adds a new optin type - Sidebar - to the available optins.
 * Version:      1.0.1
 * Author:       Thomas Griffin
 * Author URI:   http://thomasgriffinmedia.com/
 * Text Domain:  optin-monster-sidebar
 * Contributors: griffinjt
 * License:      GPL-2.0+
 * License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:  /lang
 */

add_action( 'init', 'om_sidebar_automatic_upgrades', 20 );
function om_sidebar_automatic_upgrades() {

    global $optin_monster_license;

    // Load the plugin updater.
    if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) :
        if ( ! empty( $optin_monster_license['key'] ) ) {
			$args = array(
				'remote_url' 	=> 'http://optinmonster.com/',
				'version' 		=> '1.0.1',
				'plugin_name'	=> 'OptinMonster Sidebar',
				'plugin_slug' 	=> 'optin-monster-sidebar',
				'plugin_path' 	=> plugin_basename( __FILE__ ),
				'plugin_url' 	=> WP_PLUGIN_URL . '/optin-monster-sidebar',
				'time' 			=> 43200,
				'key' 			=> $optin_monster_license['key']
			);

			// Load the updater class.
			$optin_monster_sidebar_updater = new optin_monster_updater( $args );
		}
    endif;

}

add_action( 'optin_monster_optin_types', 'om_sidebar_optin_type' );
function om_sidebar_optin_type() {

    echo '<div class="optin-item one-fourth first" data-optin-type="sidebar">';
		echo '<h4>Sidebar</h4>';
		echo '<img src="' . plugins_url( 'images/sidebaricon.png', __FILE__ ) . '" />';
	echo '</div>';

}

add_action( 'optin_monster_config', 'om_sidebar_hide_options', 0 );
function om_sidebar_hide_options() {

    // Return early if not a sidebar optin.
    if ( empty( $_GET['type'] ) || isset( $_GET['type'] ) && 'sidebar' !== $_GET['type'] )
        return;

    echo '<style type="text/css">.optin-config-box:nth-of-type(2),.optin-config-box:nth-of-type(3),.optin-config-box:nth-of-type(5){display:none}</style>';

}

add_action( 'optin_monster_code', 'om_sidebar_hide_output_options', 0 );
function om_sidebar_hide_output_options() {

    // Return early if not a sidebar optin.
    if ( empty( $_GET['type'] ) || isset( $_GET['type'] ) && 'sidebar' !== $_GET['type'] )
        return;

    echo '<style type="text/css">.optin-config-box:nth-of-type(2),.optin-config-box:nth-of-type(3),.optin-config-box:nth-of-type(4),.optin-config-box:nth-of-type(5){display:none}</style>';

}

add_action( 'optin_monster_code_top', 'om_sidebar_code_message', 0 );
function om_sidebar_code_message() {

    // Return early if not a sidebar optin.
    if ( empty( $_GET['type'] ) || isset( $_GET['type'] ) && 'sidebar' !== $_GET['type'] )
        return;

    echo '<p><strong>Since this optin type is used in widgets, there are no output settings available other than enabling/disabling the optin. You can manage the display of this optin via a widget area in your theme.</strong></p>';

}

add_action( 'optin_monster_design_sidebar', 'om_sidebar_design_output' );
function om_sidebar_design_output() {

    global $optin_monster_tab_optins;
    $tab = $optin_monster_tab_optins;

    echo '<div class="optin-select-wrap clearfix">';
		echo '<div class="optin-item one-fourth first ' . ( isset( $tab->meta['theme'] ) && 'action-theme' == $tab->meta['theme'] ? 'selected' : '' ) . '" data-optin-theme="Action Theme">';
			echo '<h4>Action Theme</h4>';
			echo '<img src="' . plugins_url( 'images/sidebaricon.png', __FILE__ ) . '" />';
			echo '<form id="action-theme" data-optin-theme="action-theme">';
			    echo om_sidebar_get_action_theme( 'action-theme' );
            echo '</form>';
		echo '</div>';
	echo '</div>';

}

add_filter( 'optin_monster_template_sidebar', 'om_sidebar_template_optin_sidebar', 10, 7 );
function om_sidebar_template_optin_sidebar( $html, $theme, $base_class, $hash, $optin, $env, $ssl ) {

    // Load template based on theme.
    switch ( $theme ) {
        case 'action-theme' :
            $template = 'sidebar-' . $theme;
            require_once plugin_dir_path( __FILE__ ) . $template . '.php';
            $class = 'optin_monster_build_' . str_replace( '-', '_', $template );
    		$build = new $class( 'sidebar', $theme, $hash, $optin, $env, $ssl, $base_class );
    		$html  = $build->build();
        break;
    }

    // Return the HTML of the optin type and theme.
    return $html;

}

add_action( 'optin_monster_save_sidebar', 'om_sidebar_save_optin_sidebar', 10, 4 );
function om_sidebar_save_optin_sidebar( $type, $theme, $optin, $data ) {

    require_once plugin_dir_path( __FILE__ ) . 'save-' . $type . '-' . $theme . '.php';
	$class = 'optin_monster_save_' . $type . '_' . str_replace( '-', '_', $theme );
	$save  = new $class( $type, $theme, $optin, $data );
	$save->save_optin();

}

function om_sidebar_get_action_theme( $theme_type ) {

    global $optin_monster_tab_optins;
    $tab = $optin_monster_tab_optins;

    ob_start();
    echo '<div class="design-customizer-ui" data-optin-theme="action-theme">';
		echo '<div class="design-sidebar">';
			echo '<div class="controls-area clearfix">';
    			echo '<a class="button button-secondary button-large grey pull-left close-design" href="#" title="Close Customizer">Close</a>';
    			echo '<a class="button button-primary button-large orange pull-right save-design" href="#" title="Save Changes">Save</a>';
    		echo '</div>';
    		echo '<div class="title-area clearfix">';
    			echo '<p class="no-margin">You are now previewing:</p>';
    			echo '<h3 class="no-margin">' . ucwords( str_replace( '-', ' ', $theme_type ) ) . '</h3>';
    		echo '</div>';
			echo '<div class="accordion-area clearfix">';
			    echo '<h3>Background Colors</h3>';
    			echo '<div class="colors-area">';
    				echo '<p>';
    					echo '<label for="om-sidebar-' . $theme_type . '-content-bg">Content Background Color</label>';
    					echo '<input type="text" id="om-sidebar-' . $theme_type . '-content-bg" class="om-bgcolor-picker" name="optin_content_bg" value="' . $tab->get_field( 'background', 'content' ) . '" data-default-color="#fff" data-target="om-sidebar-' . $theme_type . '-optin" />';
    				echo '</p>';
    			echo '</div>';

				echo '<h3>Title and Tagline</h3>';
				echo '<div class="title-tag-area">';
					echo '<p>';
						echo '<label for="om-sidebar-' . $theme_type . '-headline">Optin Title</label>';
						echo '<input id="om-sidebar-' . $theme_type . '-headline" class="main-field" data-target="om-sidebar-' . $theme_type . '-optin-title" name="optin_title" type="text" value="' . $tab->get_field( 'title', 'text' ) . '" placeholder="e.g. OptinMonster Rules!" />';
						echo '<span class="input-controls">';
							echo $tab->get_meta_controls( 'title' );
							foreach ( (array) $tab->get_field( 'title', 'meta' ) as $prop => $style )
								echo '<input type="hidden" name="optin_title_' . str_replace( '_', '-', $prop ) . '" value="' . $style . '" />';
						echo '</span>';
					echo '</p>';
					echo '<div class="optin-input-meta">';
						echo '<p>';
							echo '<label for="om-sidebar-' . $theme_type . '-headline-color">Optin Title Color</label>';
							echo '<input type="text" id="om-sidebar-' . $theme_type . '-headline-color" class="om-color-picker" name="optin_title_color" value="' . $tab->get_field( 'title', 'color' ) . '" data-default-color="#ffffff" data-target="om-sidebar-' . $theme_type . '-optin-title" />';
						echo '</p>';
						echo '<p>';
							echo '<label for="om-sidebar-' . $theme_type . '-headline-font">Optin Title Font</label>';
							echo '<select id="om-sidebar-' . $theme_type . '-headline-font" class="main-field optin-font" data-target="om-sidebar-' . $theme_type . '-optin-title" data-property="font-family" name="optin_title_font">';
							foreach ( $tab->account->get_available_fonts() as $font ) :
								$selected = $tab->get_field( 'title', 'font' ) == $font ? ' selected="selected"' : '';
								echo '<option value="' . $font . '"' . $selected . '>' . $font . '</option>';
							endforeach;
							echo '</select>';
						echo '</p>';
						echo '<p>';
							echo '<label for="om-sidebar-' . $theme_type . '-headline-size">Optin Title Font Size</label>';
							echo '<input id="om-sidebar-' . $theme_type . '-headline-size" data-target="om-sidebar-' . $theme_type . '-optin-title" name="optin_title_size" class="optin-size" type="text" value="' . $tab->get_field( 'title', 'size' ) . '" placeholder="e.g. 36" />';
						echo '</p>';
					echo '</div>';
					echo '<p>';
    					echo '<label for="om-sidebar-' . $theme_type . '-tagline">Optin Tagline</label>';
    					echo '<textarea id="om-sidebar-' . $theme_type . '-tagline" class="main-field" data-target="om-sidebar-' . $theme_type . '-optin-tagline" type="text" name="optin_tagline" placeholder="e.g. OptinMonster explodes your email list!" rows="4">' . htmlentities( $tab->get_field( 'tagline', 'text' ) ) . '</textarea>';
    					echo '<span class="input-controls">';
    						echo $tab->get_meta_controls( 'tagline' );
    						foreach ( (array) $tab->get_field( 'tagline', 'meta' ) as $prop => $style )
    							echo '<input type="hidden" name="optin_tagline_' . str_replace( '_', '-', $prop ) . '" value="' . $style . '" />';
    					echo '</span>';
    				echo '</p>';
    				echo '<div class="optin-input-meta last">';
    					echo '<p>';
    						echo '<label for="om-sidebar-' . $theme_type . '-tagline-color">Optin Tagline Color</label>';
    						echo '<input type="text" id="om-sidebar-' . $theme_type . '-tagline-color" class="om-color-picker" name="optin_tagline_color" value="' . $tab->get_field( 'tagline', 'color' ) . '" data-default-color="#282828" data-target="om-sidebar-' . $theme_type . '-optin-tagline" />';
    					echo '</p>';
    					echo '<p>';
    						echo '<label for="om-sidebar-' . $theme_type . '-tagline-font">Optin Tagline Font</label>';
    						echo '<select id="om-sidebar-' . $theme_type . '-tagline-font" class="main-field optin-font" data-target="om-sidebar-' . $theme_type . '-optin-tagline" data-property="font-family" name="optin_tagline_font">';
    						foreach ( $tab->account->get_available_fonts() as $font ) :
    							$selected = $tab->get_field( 'tagline', 'font' ) == $font ? ' selected="selected"' : '';
    							echo '<option value="' . $font . '"' . $selected . '>' . $font . '</option>';
    						endforeach;
    						echo '</select>';
    					echo '</p>';
    					echo '<p>';
    						echo '<label for="om-sidebar-' . $theme_type . '-tagline-size">Optin Tagline Font Size</label>';
    						echo '<input id="om-sidebar-' . $theme_type . '-headline-size" data-target="om-sidebar-' . $theme_type . '-optin-tagline" name="optin_tagline_size" class="optin-size" type="text" value="' . $tab->get_field( 'tagline', 'size' ) . '" placeholder="e.g. 36" />';
    					echo '</p>';
    				echo '</div>';
				echo '</div>';

                if ( ! $tab->meta['custom_html'] ) :
				echo '<h3>Fields and Buttons</h3>';
				echo '<div class="fields-area">';
					echo '<p>';
						echo '<label for="om-sidebar-' . $theme_type . '-name"><input style="display:inline;width:auto;margin-right:3px;" type="checkbox" id="om-sidebar-' . $theme_type . '-name" name="optin_name_show" value="' . $tab->get_field( 'name', 'show' ) . '"' . checked( $tab->get_field( 'name', 'show' ), 1, false ) . ' /> Show Optin Name Field?</label>';
						echo '<input id="om-sidebar-' . $theme_type . '-name-placeholder" class="main-field" data-target="om-sidebar-' . $theme_type . '-optin-name" type="text" name="optin_name_placeholder" value="' . $tab->get_field( 'name', 'placeholder' ) . '" placeholder="e.g. Your Name" />';
					echo '</p>';
					echo '<div class="optin-input-meta">';
						echo '<p>';
							echo '<label for="om-sidebar-' . $theme_type . '-name-color">Optin Name Field Color</label>';
							echo '<input type="text" id="om-sidebar-' . $theme_type . '-name-color" class="om-color-picker" name="optin_name_color" value="' . $tab->get_field( 'name', 'color' ) . '" data-default-color="#282828" data-target="om-sidebar-' . $theme_type . '-optin-name" />';
						echo '</p>';
						echo '<p>';
							echo '<label for="om-sidebar-' . $theme_type . '-name-font">Optin Name Field Font</label>';
							echo '<select id="om-sidebar-' . $theme_type . '-name-font" class="main-field optin-font" data-target="om-sidebar-' . $theme_type . '-optin-name" data-property="font-family" name="optin_name_font">';
							foreach ( $tab->account->get_available_fonts() as $font ) :
								$selected = $tab->get_field( 'name', 'font' ) == $font ? ' selected="selected"' : '';
								echo '<option value="' . $font . '"' . $selected . '>' . $font . '</option>';
							endforeach;
							echo '</select>';
						echo '</p>';
						echo '<p>';
							echo '<label for="om-sidebar-' . $theme_type . '-name-size">Optin Name Field Font Size</label>';
							echo '<input id="om-sidebar-' . $theme_type . '-name-size" data-target="om-sidebar-' . $theme_type . '-optin-name" name="optin_name_size" class="optin-size" type="text" value="' . $tab->get_field( 'name', 'size' ) . '" placeholder="e.g. 36" />';
						echo '</p>';
					echo '</div>';
					echo '<p>';
						echo '<label for="om-sidebar-' . $theme_type . '-email">Optin Email Field</label>';
						echo '<input id="om-sidebar-' . $theme_type . '-email-placeholder" class="main-field" data-target="om-sidebar-' . $theme_type . '-optin-email" type="text" name="optin_email_placeholder" value="' . $tab->get_field( 'email', 'placeholder' ) . '" placeholder="e.g. Your Email" />';
					echo '</p>';
					echo '<div class="optin-input-meta">';
						echo '<p>';
							echo '<label for="om-sidebar-' . $theme_type . '-email-color">Optin Email Field Color</label>';
							echo '<input type="text" id="om-sidebar-' . $theme_type . '-email-color" class="om-color-picker" name="optin_email_color" value="' . $tab->get_field( 'email', 'color' ) . '" data-default-color="#282828" data-target="om-sidebar-' . $theme_type . '-optin-email" />';
						echo '</p>';
						echo '<p>';
							echo '<label for="om-sidebar-' . $theme_type . '-email-font">Optin Email Field Font</label>';
							echo '<select id="om-sidebar-' . $theme_type . '-email-font" class="main-field optin-font" data-target="om-sidebar-' . $theme_type . '-optin-email" data-property="font-family" name="optin_email_font">';
							foreach ( $tab->account->get_available_fonts() as $font ) :
								$selected = $tab->get_field( 'email', 'font' ) == $font ? ' selected="selected"' : '';
								echo '<option value="' . $font . '"' . $selected . '>' . $font . '</option>';
							endforeach;
							echo '</select>';
						echo '</p>';
						echo '<p>';
							echo '<label for="om-sidebar-' . $theme_type . '-email-size">Optin Email Field Font Size</label>';
							echo '<input id="om-sidebar-' . $theme_type . '-email-size" data-target="om-sidebar-' . $theme_type . '-optin-email" name="optin_email_size" class="optin-size" type="text" value="' . $tab->get_field( 'email', 'size' ) . '" placeholder="e.g. 36" />';
						echo '</p>';
					echo '</div>';
					echo '<p>';
						echo '<label for="om-sidebar-' . $theme_type . '-submit">Optin Submit Field</label>';
						echo '<input id="om-sidebar-' . $theme_type . '-submit-placeholder" class="main-field" data-target="om-sidebar-' . $theme_type . '-optin-submit" type="text" name="optin_submit_placeholder" value="' . $tab->get_field( 'submit', 'placeholder' ) . '" placeholder="e.g. Sign Me Up!" />';
						echo '<span class="input-controls">';
							echo $tab->get_meta_controls( 'submit' );
							foreach ( (array) $tab->get_field( 'submit', 'meta' ) as $prop => $style )
								echo '<input type="hidden" name="optin_submit_' . str_replace( '_', '-', $prop ) . '" value="' . $style . '" />';
						echo '</span>';
					echo '</p>';
					echo '<div class="optin-input-meta last">';
						echo '<p>';
							echo '<label for="om-sidebar-' . $theme_type . '-submit-field-color">Optin Submit Field Color</label>';
							echo '<input type="text" id="om-sidebar-' . $theme_type . '-submit-field-color" class="om-color-picker" name="optin_submit_field_color" value="' . $tab->get_field( 'submit', 'field_color' ) . '" data-default-color="#fff" data-target="om-sidebar-' . $theme_type . '-optin-submit" />';
						echo '</p>';
						echo '<p>';
							echo '<label for="om-sidebar-' . $theme_type . '-submit-bg-color">Optin Submit Background Color</label>';
							echo '<input type="text" id="om-sidebar-' . $theme_type . '-submit-bg-color" class="om-bgcolor-picker" name="optin_submit_bg_color" value="' . $tab->get_field( 'submit', 'bg_color' ) . '" data-default-color="#484848" data-target="om-sidebar-' . $theme_type . '-optin-submit" />';
						echo '</p>';
						echo '<p>';
							echo '<label for="om-sidebar-' . $theme_type . '-submit-font">Optin Submit Field Font</label>';
							echo '<select id="om-sidebar-' . $theme_type . '-submit-font" class="main-field optin-font" data-target="om-sidebar-' . $theme_type . '-optin-submit" data-property="font-family" name="optin_submit_font">';
							foreach ( $tab->account->get_available_fonts() as $font ) :
								$selected = $tab->get_field( 'submit', 'font' ) == $font ? ' selected="selected"' : '';
								echo '<option value="' . $font . '"' . $selected . '>' . $font . '</option>';
							endforeach;
							echo '</select>';
						echo '</p>';
						echo '<p>';
							echo '<label for="om-sidebar-' . $theme_type . '-submit-size">Optin Submit Field Font Size</label>';
							echo '<input id="om-sidebar-' . $theme_type . '-submit-size" data-target="om-sidebar-' . $theme_type . '-optin-submit" name="optin_submit_size" class="optin-size" type="text" value="' . $tab->get_field( 'submit', 'size' ) . '" placeholder="e.g. 36" />';
						echo '</p>';
					echo '</div>';
				echo '</div>';
				endif;

				echo '<h3>Custom Optin CSS</h3>';
    			echo '<div class="custom-css-area">';
    				echo '<p><small>' . __( 'The textarea below is for adding custom CSS to this particular optin. Each of your custom CSS statements should be on its own line and be prefixed with the following declaration:', 'optin-monster' ) . '</small></p>';
    				echo '<p><strong><code>html div#om-' . $tab->optin->post_name . '</code></strong></p>';
    				echo '<textarea id="om-sidebar-' . $theme_type . '-custom-css" name="optin_custom_css" placeholder="e.g. html div#om-' . $tab->optin->post_name . ' input[type=submit], html div#' . $tab->optin->post_name . ' button { background: #ff6600; }" class="om-custom-css">' . $tab->get_field( 'custom_css' ) . '</textarea>';
    				echo '<small><a href="http://optinmonster.com/docs/custom-css/" title="' . __( 'Custom CSS with OptinMonster', 'optin-monster' ) . '" target="_blank"><em>Click here for help on using custom CSS with OptinMonster.</em></a></small>';
    			echo '</div>';
			echo '</div>';
		echo '</div>';
		echo '<div class="design-content">';
		echo '</div>';
	echo '</div>';

	return ob_get_clean();

}