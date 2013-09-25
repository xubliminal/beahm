<div id="header">
    <div id="header-quote">
        <div id="header-quote-in">
            <?php if(is_front_page()): ?>
            <?php echo ot_get_option('quote') ?>
            <?php else: ?>
                <?php if(have_posts()): the_post(); ?>
                <?php the_field('quote') ?>
                <?php endif; ?>
            <?php endif ?>
        </div>
    </div>
    <div id="header-bottom">
        <div class="container">
            <div id="header-bottom-buttons">
                <?php if(is_front_page()): ?>
                <a href="#video" class="scroll btn btn-default"><?php echo ot_get_option('video_link') ?></a>
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