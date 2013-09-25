<div id="contact" class="grey-block">
    <div class="grey-block-in">
        <div class="container">
            <div class="row">
                <?php $ratings = ot_get_option('check_us_out') ?>
                <?php if(count($ratings)): ?>
                <div class="col-sm-6 col-md-7 col-lg-6">
                    <div class="wb-caption">
                        <h2>Check us out!</h2>
                    </div>
                    <?php foreach($ratings as $r): ?>
                    <div class="review-wrap clearfix" <?php if($r['title'] == 'Facebook'): ?> id="facebook-widget-wrap" <?php endif ?> >
                        <div class="review-icon"><img src="<?php echo $r['logo'] ?>" alt="" class="img-responsive" /></div>
                        <div class="review-content">
                            <div class="rc-in">
                                <?php if($r['title'] == 'Facebook'): ?>
                                <?php echo $r['fb_code'] ?>
                                <?php else: ?>
                                <a href="<?php echo $r['reviews_link'] ?>" class="btn btn-default-2 pull-right"><span class="hide-x">Read </span>Reviews</a>
                                <h3><?php echo $r['title'] ?></h3>
                                <p><img src="<?php echo $r['rating'] ?>" alt="" /></p>
                                <?php endif ?>
                            </div>
                        </div>
                    </div> <!-- .review-wrap -->
                    <?php endforeach ?> 
                </div> <!-- .col-sm-6 -->
                <?php endif ?>
                
                <?php $contact = ot_get_option('contact_form') ?>
                <?php query_posts(array('page_id' => $contact)); ?>
                <?php if(have_posts()): the_post(); ?>
                <div class="col-sm-6 col-md-5 col-lg-6">
                    <div class="wb-caption">
                        <h2><?php the_title() ?></h2>
                    </div>
                    <div class="contact-form-text">
                        <?php the_content() ?>
                    </div>
                </div> <!-- .col-sm-6 -->
                <?php endif; wp_reset_query() ?>
            </div> <!-- .row -->
        </div> <!-- .container -->
    </div> <!-- .grey-block-in -->
</div> <!-- .grey-block -->