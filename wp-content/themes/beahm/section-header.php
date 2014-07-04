<?php if(is_front_page()): ?>
<div id="header" style="background-image: url(<?php echo ot_get_option('header_image') ?>)">
    <div id="header-quote">
        <div id="header-quote-in">
            <?php echo ot_get_option('quote') ?>
        </div>
    </div>
<?php else: ?>
<?php if(have_posts()): the_post(); ?>
<?php if(get_field('header_image')): ?>
<div id="header" style="background-image: url(<?php echo the_field('header_image') ?>)">
<?php else: ?>
<div id="header" style="background-image: url(<?php echo ot_get_option('header_image') ?>)">
<?php endif ?>
    <div id="header-quote">
        <div id="header-quote-in">
            <?php if(get_field('quote')): ?>
            <?php the_field('quote') ?>
            <?php else: ?>
            <?php echo ot_get_option('quote') ?>
            <?php endif ?>
        </div>
    </div>
<?php else: ?>
<div id="header" style="background-image: url(<?php echo ot_get_option('header_image') ?>)">
    <div id="header-quote">
        <div id="header-quote-in">
            <?php echo ot_get_option('quote') ?>
        </div>
    </div>
<?php endif; ?>
<?php endif ?>
    
    <div id="header-bottom">
        <div class="container">
            <div id="header-bottom-buttons">
                <?php if(is_front_page()): ?>
				<div style="display:inline-block; vertical-align:middle; margin-right:20px;">
                    <a href="#video" class="scroll btn btn-default hidden-xs"><?php echo ot_get_option('video_link') ?></a>
                </div>
                <a href="tel:8667659188" class="scroll btn btn-default visible-xs">Click to Call</a>
                <?php else: ?>
                <a href="#content" class="scroll btn btn-default">Learn your options</a>
                <?php endif ?>
                <a href="#contact" class="scroll btn btn-primary"><?php echo ot_get_option('contact_link') ?></a>
            </div> <!-- #header-bottom-buttons -->
            <div id="header-founder">
                <strong><?php echo ot_get_option('founder') ?></strong>
                <br /><?php echo ot_get_option('founder_title') ?>
            </div>
        </div> <!-- .container -->
    </div>
</div> <!-- #header -->