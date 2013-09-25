<?php get_header() ?>
<?php get_template_part('section', 'header') ?>
<div class="white-block">
    <div class="container">
        <?php if(have_posts()): the_post() ?>
        <div class="row">
            <div class="col-sm-7">
                <div class="city-caption clearfix">
                    <div class="city-caption-img"><?php the_post_thumbnail('location_thumb') ?></div>
                    <h2><?php the_title() ?></h2>
                    <p>
                        <?php the_field('address') ?>
                        <br><span class="location-phone"><?php the_field('phone') ?></span>
                        <br><a href="#contact" class="link-at scroll">Contact Us Online</a>
                    </p>
                </div> <!-- .city-caption -->
                <?php the_content() ?>
            </div> <!-- .col-sm-7 -->
            <?php get_sidebar('testimonials') ?>
        </div>
        <hr/>
        <?php endif ?>
        <?php get_template_part('section', 'asseenon'); ?>
    </div>
</div>
<?php get_template_part('section', 'contact'); ?>
<?php get_footer() ?>