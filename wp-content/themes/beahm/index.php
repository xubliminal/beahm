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

        <div class="blog-content col-sm-7 col-lg-8">
            <h1>Recent News <?php wp_title('|') ?></h1>
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
                    <p class="post-social">
                        <span class="post-social-lbl">Share this:</span>
                        <a st_url="<?php the_permalink() ?>" st_title="<?php the_title() ?>" class='st_twitter_custom btn btn-default-2' ><img src="<?php echo get_template_directory_uri() ?>/images/icon-blog-twitter.png" alt="" /></a>
                        <a st_url="<?php the_permalink() ?>" st_title="<?php the_title() ?>" class='st_googleplus_custom btn btn-default-2'><img src="<?php echo get_template_directory_uri() ?>/images/icon-blog-google.png" alt="" /></a>
                        <a st_url="<?php the_permalink() ?>" st_title="<?php the_title() ?>" class='st_facebook_custom btn btn-default-2'><img src="<?php echo get_template_directory_uri() ?>/images/icon-blog-facebook.png" alt="" /></a>
                        <a st_url="<?php the_permalink() ?>" st_title="<?php the_title() ?>" class='st_email_custom btn btn-default-2'><img src="<?php echo get_template_directory_uri() ?>/images/icon-blog-email.png" alt="" /></a>
                    </p>
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
    </div> <!-- .container -->
</div> <!-- #blog-wrap -->
<?php get_footer() ?>
