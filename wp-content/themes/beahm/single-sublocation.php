<?php get_header() ?><?php the_post() ?>
<div class="white-block">
    <div class="container" id="content">
        <div class="row">
            <div class="col-sm-7">
                <div class="city-caption clearfix">
                    <?php $parent = get_field('parent_location') ?>
                    <div class="city-caption-img"><?php echo get_the_post_thumbnail($parent->ID, 'location_thumb'); ?></div>
                    <h2><?php echo get_the_title($parent->ID) ?></h2>
                    <p>
                        <?php the_field('address', $parent->ID) ?>
                        <br><span class="location-phone"><?php the_field('phone', $parent->ID) ?></span>
                        <br><a href="#contact" class="link-at scroll">Contact Us Online</a>
                    </p>
                </div> <!-- .city-caption -->
                <?php the_content() ?>
            </div> <!-- .col-sm-7 -->
            <?php get_sidebar('testimonials') ?>
        </div>
        <hr/>
        <?php get_template_part('section', 'asseenon'); ?>
    </div>
</div>
<?php get_template_part('section', 'contact'); ?>
<?php get_footer() ?>