<div id="header">
    <div id="header-quote">
        <div id="header-quote-in">
            <?php echo ot_get_option('quote') ?>
        </div>
    </div>
    <div id="header-bottom">
        <div class="container">
            <div id="header-bottom-buttons">
                <a href="#video" class="scroll btn btn-default"><?php echo ot_get_option('video_link') ?></a>
                <a href="#contact" class="scroll btn btn-primary"><?php echo ot_get_option('contact_link') ?></a>
            </div> <!-- #header-bottom-buttons -->
            <div id="header-founder">
                <strong><?php echo ot_get_option('founder') ?></strong>
                <br /><?php echo ot_get_option('founder_title') ?>
            </div>
        </div> <!-- .container -->
    </div>
</div> <!-- #header -->