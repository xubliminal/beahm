<div id="op-le-row-options">
    <h1><?php _e('Row options',OP_SN) ?></h1>
    <div class="op-lightbox-content">
        <label><?php _e('Is it a full width row?', OP_SN);?></label>
        <p class="op-micro-copy"><?php _e('If you are using full width rows with background colours this will prevent white bars between your rows.', OP_SN);?></p>
        <input type="checkbox" name="op_full_width_row" />
        
        <label><?php _e('Code before row', OP_SN);?></label>
    	<p class="op-micro-copy"><?php _e('Enter shortcode or similar which will be rendered before the row', OP_SN);?></p>
    	<textarea name="op_row_before" id="op_row_before"></textarea>
    	
    	<label><?php _e('Code after row', OP_SN);?></label>
    	<p class="op-micro-copy"><?php _e('Enter shortcode or similar which will be rendered after the row', OP_SN);?></p>
    	<textarea name="op_row_after" id="op_row_after"></textarea>
        
        <label><?php _e('Row background color start', OP_SN);?></label>
        <div class="font-chooser cf">
        <?php op_color_picker('someField[color]', '','op_section_row_options_bgcolor_start'); ?>
        <a href="#reset" class="reset-link">Reset</a>
        </div>
        <label><?php _e('Row background color end', OP_SN);?></label>
        <div class="font-chooser cf">
        <?php op_color_picker('someField[color]', '','op_section_row_options_bgcolor_end'); ?>
        <a href="#reset" class="reset-link">Reset</a>
        </div>
        <label><?php _e('Top padding (number of pixels)', OP_SN);?></label>
        <input type="text" name="op_row_top_padding" id="op_row_top_padding" />
        <label><?php _e('Bottom padding (number of pixels)', OP_SN);?></label>
        <input type="text" name="op_row_bottom_padding" id="op_row_bottom_padding" />
        <label><?php _e('Row border width (number of pixels, top and bottom border)', OP_SN);?></label>
        <input type="text" name="op_row_border_width" id="op_row_border_width" />
        <label><?php _e('Row border color (top and bottom border)', OP_SN);?></label>
        <div class="font-chooser cf">
        <?php op_color_picker('someField[borderColor]', '','op_section_row_options_borderColor'); ?>
        <a href="#reset" class="reset-link">Reset</a>
        </div>
        <label><?php _e('Row background image', OP_SN);?></label>
        <p class="op-micro-copy"><?php _e('Choose an image to use as the row background', OP_SN);?></p>
        <?php op_upload_field('op_row_background'); ?>
        <br />
        <p class="op-micro-copy"><?php _e('Choose how you would like the background image displayed', OP_SN);?></p>
        <select class="op_row_bg_options" id="op_row_bg_options" name="op_bg_options">
			<option value="center"><?php _e('Center (center your background image)', OP_SN); ?></option>
			<option value="cover"><?php _e('Cover/Stretch (stretch your background image to fit)', OP_SN); ?></option>
			<option value="tile_horizontal"><?php _e('Tile Horizontal (tile the background image horizontally)', OP_SN); ?></option>
			<option value="tile"><?php _e('Tile (tile the background image horizontally and vertically)', OP_SN); ?></option>
		</select>
    </div>
    <div class="op-insert-button cf">
            <button type="button" id="op-le-row-options-update" class="editor-button"><?php _e('Update',OP_SN) ?></button>
    </div>
</div>