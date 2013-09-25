<?php if ( 'location' == get_post_type() ): ?>
<div class="col-sm-5 testi-2-wrap">
    <?php dynamic_sidebar('location_sidebar') ?>
</div> <!-- .col-sm-5 -->
<?php else: ?>
<div class="blog-search-normal hidden-xs clearfix">
    <form class="form-inline search-form" role="form" action="<?php bloginfo('wpurl') ?>">
        <div class="form-group">
            <div class="fg-in">
                <input type="text" name="s" class="form-control" id="blog-search-2" placeholder="Search..." />
            </div> <!-- .fg-in -->
        </div>
        <button type="submit" class="btn btn-primary">GO</button>
    </form>
</div> <!-- .blog-search-normal -->

<div class="hidden-xs">
    <?php dynamic_sidebar('blog_top') ?>
    <?php query_posts(array('posts_per_page' => 3, 'v_sortby' => 'views', 'v_orderby' => 'desc')); ?>
    <?php if(have_posts()): ?> 
    <h2 class="blog-sb-title">Most Viewed</h2>
    <?php while(have_posts()): the_post(); ?>
    <div class="blog-sb-post">
        <p class="sb-post-date"><?php the_time('F j, Y') ?></p>
        <h3 class="sb-post-title"><?php the_title() ?></h3>
        <p class="sb-post-author">Written by <?php the_author() ?></p>
        <div class="sb-post-content clearfix">
            <?php the_post_thumbnail('cutom_thumb', array('class' => 'img-rounded pull-left hidden-xs')) ?>
            <p><?php echo substr(strip_tags(get_the_excerpt()), 0, 80) ?>...</p>
            <p><strong><a href="<?php the_permalink() ?>">Read more</a></strong></p>
        </div>
    </div> <!-- .blog-sb-post -->
    <?php endwhile ?>
    <?php endif; wp_reset_query() ?>
    <?php dynamic_sidebar('blog_bottom') ?>
</div> <!-- .hidden-xs -->
<?php endif ?>
