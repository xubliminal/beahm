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
            <h1>Recent News</h1>
            <?php if(have_posts()): the_post() ?>
            <div class="blog-post post-detail">
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
                    <p><?php the_post_thumbnail('custom_thumb', array('class' => 'img-rounded img-responsive')) ?></p>
                    <?php the_content() ?>
                    <p class="post-share">
                        <span class='st_facebook_hcount post-share-item' displayText='Facebook Recommend'></span>
                        <span class='st_twitter_hcount post-share-item' displayText='Tweet'></span>
                        <span class='st_plusone_hcount post-share-item' displayText='Google +1'></span>
                    </p>
                </div> <!-- .post-content -->
            </div> <!-- .blog-post -->
            
            <?php query_posts( array( 
                    'category__in' => wp_get_post_categories($post->ID), 
                    'posts_per_page' => 2, 
                    'post__not_in' => array($post->ID) 
            )); ?>
            <?php if(have_posts()): ?>
            <div class="related-posts">
                <h2 class="post-detail-sub-title">Related Posts</h2>
                <div class="row">
                    <?php while(have_posts()): the_post() ?>
                    <div class="col-md-6">
                        <div class="blog-sb-post">
                            <p class="sb-post-date"><?php the_time('F j, Y') ?></p>
                            <h3 class="sb-post-title"><?php the_title() ?></h3>
                            <p class="sb-post-author">Written by <?php the_author() ?></p>
                            <div class="sb-post-content clearfix">
                                <?php the_post_thumbnail('cutom_thumb', array('class' => 'img-rounded pull-left')) ?>
                                <p><?php echo substr(strip_tags(get_the_excerpt()), 0, 80) ?>...</p>
                                <p><strong><a href="<?php the_permalink() ?>">Read more</a></strong></p>
                            </div>
                        </div> <!-- .blog-sb-post -->
                    </div> <!-- .col-md-6 -->
                    <?php endwhile ?>
                </div> <!-- .row -->
            </div> <!-- .related-posts -->
            <?php endif; wp_reset_query() ?>
            <?php comments_template('', true) ?>
            <?php endif ?>
        </div> <!-- .blog-content -->

        <div class="blog-sidebar col-sm-5 col-lg-4">
            <?php get_sidebar() ?>
        </div> <!-- .blog-sidebar -->
    </div> <!-- .container -->
</div> <!-- #blog-wrap -->

<?php get_footer() ?>