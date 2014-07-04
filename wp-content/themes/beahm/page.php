<?php get_header() ?>
<div class="white-block">
    <div class="container" id="content">
        <div class="row">
            <div class="col-sm-7">
                <div class="city-caption clearfix">
                    <h2><?php the_title() ?></h2>
                </div> <!-- .city-caption -->
                <?php the_content() ?>
            </div> <!-- .col-sm-7 -->
            <?php // get_sidebar() ?>
        </div>
    </div>
</div>
<?php get_footer() ?>