<?php

register_nav_menus(array(
    'main' => 'Main'
));

add_theme_support('post-thumbnails');

add_image_size('location', 254, 254, true);
add_image_size('location_thumb', 150, 150, true);
add_image_size('cutom_thumb', 65, 60, true);
add_image_size('cutom_thumb2', 145, 120, true);

register_sidebar(array(
    'name'          => __( 'Location Sidebar', 'beahm' ),
    'id'            => 'location_sidebar',
    'description'   => 'Widgets will be display on Location Pages',
    'before_widget' => '<div id="%1$s" class="widget %2$s">',
    'after_widget'  => '</div>',
    'before_title'  => '<h2>',
    'after_title'   => '</h2>'
));

register_sidebar(array(
    'name'          => __( 'Blog Bottom', 'beahm' ),
    'id'            => 'blog_bottom',
    'description'   => 'Widgets will be display below Most Viewed Posts widget',
    'before_widget' => '<div id="%1$s" class="blog-sb-post widget %2$s">',
    'after_widget'  => '</div>',
    'before_title'  => '<h2 class="blog-sb-title">',
    'after_title'   => '</h2>'
));

register_sidebar(array(
    'name'          => __( 'Blog Top', 'beahm' ),
    'id'            => 'blog_top',
    'description'   => 'Widgets will be display obove Most Viewed Posts widget',    
    'before_widget' => '<div id="%1$s" class="blog-sb-post widget %2$s">',
    'after_widget'  => '</div>',
    'before_title'  => '<h2 class="blog-sb-title">',
    'after_title'   => '</h2>'
));

function beahm_comments( $comment, $args, $depth ) {
    $GLOBALS['comment'] = $comment; global $post; ?>
    <div class="post-comment">
        <div class="clearfix">
            <div class="post-comment-pic"><?php echo get_avatar($comment, 50); ?></div>
            <div class="post-comment-content">
                <div class="pcc-in">
                    <p>
                        <span class="pcc-author"><?php comment_author() ?></span>
                        <span class="pcc-date"><?php echo human_time_diff(get_comment_time('U')) ?> ago</span>
                    </p>
                    <?php comment_text() ?>
                    <p><?php comment_reply_link(array_merge($args, array('reply_text' => 'Reply', 'depth' => $depth, 'max_depth' => $args['max_depth']))); ?></p>
                </div> <!-- .pcc-in -->
            </div> <!-- .post-comment-content -->
        </div> <!-- .clearfix -->
    </div> <!-- .post-comment -->
    <?php
}

function beahmlaw_clean_phone($phone) {
	$cycle = true;
	while($cycle) {
		if(strpos($phone, '(') !== false){
			$phone = str_replace('(','',$phone);
		} elseif(strpos($phone, ')') !== false){
			$phone = str_replace(')','',$phone);
		} elseif(strpos($phone, ' ') !== false){
			$phone = str_replace(' ','',$phone);
		} elseif(strpos($phone, '-') !== false){
			$phone = str_replace('-','',$phone);
		} else {
			$cycle = false;
		}
	}
	return $phone;
}

/**
 * Remove the slug from published post permalinks. Only affect our CPT though.
 */
function vipx_remove_cpt_slug( $post_link, $post, $leavename ) {
 
    if ( ! in_array( $post->post_type, array( 'location' ) ) || 'publish' != $post->post_status )
        return $post_link;
 
    $post_link = str_replace( '/' . $post->post_type . '/', '/', $post_link );
 
    return $post_link;
}
add_filter( 'post_type_link', 'vipx_remove_cpt_slug', 10, 3 );

/**
 * Some hackery to have WordPress match postname to any of our public post types
 * All of our public post types can have /post-name/ as the slug, so they better be unique across all posts
 * Typically core only accounts for posts and pages where the slug is /post-name/
 */
function vipx_parse_request_tricksy( $query ) {
 
    // Only noop the main query
    if ( ! $query->is_main_query() )
        return;
 
    // Only noop our very specific rewrite rule match
    if ( 2 != count( $query->query )
        || ! isset( $query->query['page'] ) )
        return;
 
    // 'name' will be set if post permalinks are just post_name, otherwise the page rule will match
    if ( ! empty( $query->query['name'] ) )
        $query->set( 'post_type', array( 'post', 'location', 'page' ) );
}
add_action( 'pre_get_posts', 'vipx_parse_request_tricksy' );
