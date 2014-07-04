<?php get_header() ?>
<div id="blog-wrap">
    <div class="container">
        <div class="blog-search-mobile visible-xs col-xs-12 clearfix">
            <form class="form-inline search-form" role="form" action="<?php bloginfo('wpurl') ?>">
                <div class="form-group">
                    <div class="fg-in">
                        <input type="text" name="s" class="form-control" id="blog-search-1" placeholder="Search..." />
                    </div> <!-- .fg-in -->
                </div>
                <button type="submit" class="btn btn-primary">GO</button>
            </form>
        </div> <!-- .blog-search-mobile -->
        <div class="clearfix">
            <div class="blog-content col-sm-7 col-lg-8">
                <?php if(have_posts()): while(have_posts()): the_post() ?>
                <div class="blog-post">
                    <h2 class="post-title"><?php the_title() ?></h2>
                    <p class="post-info">
                        <span class="post-date">
                            <span class="post-date-month"><?php the_time('M') ?></span> 
                            <span class="post-date-day"><?php the_time('j') ?></span>
                            <span class="post-date-year">, <?php the_time('Y') ?></span>
                        </span>
                        <span class="post-author">Written by <?php the_author() ?></span>
                    </p>
                    <div class="post-content clearfix">
                        <?php the_post_thumbnail('cutom_thumb2', array('class'=> 'img-rounded pull-left hidden-xs')) ?>
                        <?php the_excerpt() ?>
                        <p class="post-links">
                            <a href="<?php the_permalink() ?>" class="btn btn-default-2">Read MORE</a>
                            <span class="post-comments"><?php comments_number('No Comments', '1 Comment', '% Comments') ?></span>
                        </p>
                        <!-- AddThis Button BEGIN -->
                        <div class="addthis_toolbox addthis_default_style addthis_16x16_style">
                            <p class="post-social">
                                <span class="post-social-lbl">Share this:</span>
                                <a class="addthis_button_twitter btn btn-default-2">
                                	<img src="<?php echo get_template_directory_uri() ?>/images/icon-blog-twitter.png" alt="" /></a>
                                <a class="addthis_button_google_plusone_share btn btn-default-2">
                                	<img src="<?php echo get_template_directory_uri() ?>/images/icon-blog-google.png" alt="" /></a>
                                <a class="addthis_button_facebook btn btn-default-2">
                                	<img src="<?php echo get_template_directory_uri() ?>/images/icon-blog-facebook.png" alt="" /></a>
                                <a class="addthis_button_email btn btn-default-2">
                                	<img src="<?php echo get_template_directory_uri() ?>/images/icon-blog-email.png" alt="" /></a>
                            </p>
                        </div>
                        <script type="text/javascript">var addthis_config = {"data_track_addressbar":true};</script>
                        <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-52573cde6ac70d20"></script>
                        <!-- AddThis Button END -->
                    </div> <!-- .post-content -->
                </div> <!-- .blog-post -->
                <?php endwhile; endif ?>
                <div class="clearfix blog-nav">
                    <span class="link-more pull-right">
                        <?php next_posts_link('Next') ?>
                    </span>
                    <span class="link-less pull-left">
                        <?php previous_posts_link('Prev') ?>
                    </span>
                </div> <!-- .clearfix -->
            </div> <!-- .blog-content -->
            <div class="blog-sidebar col-sm-5 col-lg-4">
                <?php get_sidebar() ?>
            </div> <!-- .blog-sidebar -->
        </div>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <?php get_template_part('section', 'asseenon'); ?>
    </div>
</div>
<?php get_template_part('section', 'contact'); ?>
<?php get_footer() ?>
