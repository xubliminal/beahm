<?php if(is_front_page()): ?>
<div id="header" style="background-image: url(<?php echo ot_get_option('header_image') ?>)">
    <div id="header-bottom">
        <div class="container">
            <div class="row">
                <div class="col-md-7 col-sm-12 col-xs-12" id="header-founder">
                    <p id="header-quote-in"><?php echo strip_tags(ot_get_option('quote')) ?></p>
                    <div id="header-cite">
                        <strong><?php echo ot_get_option('founder') ?></strong>
                        <br /><?php echo ot_get_option('founder_title') ?>
                    </div>
                </div>
                <div class="col-md-5 col-sm-12 col-xs-12" id="header-bottom-buttons">
                    <div class="row">
                        <div class="col-md-6 col-sm-6 col-xs-6">
                            <a href="#video" class="scroll btn btn-default hidden-xs"><?php echo ot_get_option('video_link') ?></a>
                            <a href="tel:8667659188" class="scroll btn btn-default visible-xs">Click to Call</a>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-6">
                            <a href="#contact" class="scroll btn btn-primary"><?php echo ot_get_option('contact_link') ?></a>
                        </div>
                    </div>
                </div>
                <!-- #header-bottom-buttons -->
            </div>
        </div> <!-- .container -->
    </div>
<?php else: ?>
    <?php if(have_posts()): the_post(); ?>
        <?php if(get_field('header_image')): ?>
        <div id="header" style="background-image: url(<?php echo the_field('header_image') ?>)">
        <?php else: ?>
        <div id="header" style="background-image: url(<?php echo ot_get_option('header_image') ?>)">
        <?php endif ?>
        <div id="header-bottom">
            <div class="container">
                <div class="row">
                    <div class="col-md-7 col-sm-12 col-xs-12" id="header-founder">
                        <?php if(get_field('quote')): ?>
                        <p id="header-quote-in"><?php the_field('quote') ?></p>
                        <?php else: ?>
                        <p id="header-quote-in"><?php echo strip_tags(ot_get_option('quote')) ?></p>
                        <?php endif ?>
                        <div id="header-cite">
                            <strong><?php echo ot_get_option('founder') ?></strong>
                            <br /><?php echo ot_get_option('founder_title') ?>
                        </div>
                    </div>
                    <div class="col-md-5 col-sm-12 col-xs-12" id="header-bottom-buttons">
                        <div class="row">
                            <div class="col-md-6 col-sm-6 col-xs-6">
                                <a href="#content" class="scroll btn btn-default">Learn your options</a>
                            </div>
                            <div class="col-md-6 col-sm-6 col-xs-6">
                                <a href="#contact" class="scroll btn btn-primary"><?php echo ot_get_option('contact_link') ?></a>
                            </div>
                        </div>
                    </div>
                    <!-- #header-bottom-buttons -->
                </div>
            </div> <!-- .container -->
        </div>
    <?php else: ?>
    <div id="header" style="background-image: url(<?php echo ot_get_option('header_image') ?>)">
        <div id="header-bottom">
            <div class="container">
                <div class="row">
                    <div class="col-md-7 col-sm-12 col-xs-12" id="header-founder">
                        <p id="header-quote-in"><?php echo strip_tags(ot_get_option('quote')) ?></p>
                        <div id="header-cite">
                            <strong><?php echo ot_get_option('founder') ?></strong>
                            <br /><?php echo ot_get_option('founder_title') ?>
                        </div>
                    </div>
                    <div class="col-md-5 col-sm-12 col-xs-12" id="header-bottom-buttons">
                        <div class="row">
                            <div class="col-md-6 col-sm-6 col-xs-6">
                                <a href="#content" class="scroll btn btn-default">Learn your options</a>
                            </div>
                            <div class="col-md-6 col-sm-6 col-xs-6">
                                <a href="#contact" class="scroll btn btn-primary"><?php echo ot_get_option('contact_link') ?></a>
                            </div>
                        </div>
                    </div>
                    <!-- #header-bottom-buttons -->
                </div>
            </div> <!-- .container -->
        </div>
    <?php endif; ?>
<?php endif ?>
</div> <!-- #header -->