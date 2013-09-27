<div class="white-block" id="locations">
    <div class="container">
        <div class="wb-caption text-center">
            <h2 class="grey-line-ttl"><span>Locations</span></h2>
        </div> <!-- .wb-caption -->
        <div class="row locations">
            <?php query_posts(array('post_type' => 'location', 'order' => 'ASC')) ?>
            <?php if(have_posts()): while(have_posts()): the_post();  ?>
            <div class="col-sm-6 text-center loco">
                <p><a href="<?php the_permalink() ?>"><?php the_post_thumbnail('location') ?></a></p>
                <p><strong><?php the_title() ?></strong></p>
                <p>
                    <?php the_field('address') ?>
                    <br /><span class="location-phone"><?php the_field('phone') ?></span>
                </p>
                <p><a href="<?php the_permalink() ?>" class="btn btn-primary">VIEW INFO</a></p>
            </div> <!-- .col-sm-6 -->
            <?php endwhile; endif; wp_reset_query() ?>
        </div> <!-- .row -->
    </div> <!-- .container -->
</div> <!-- .grey-block -->