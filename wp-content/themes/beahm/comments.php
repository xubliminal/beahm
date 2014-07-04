<div class="blog-comments">
    <h2 class="post-detail-sub-title"><?php comments_number('No Comments', '1 Comment', '% Comments') ?></h2>
    <div>
        <?php wp_list_comments(array('callback' => 'beahm_comments')) ?>
    </div>
</div> <!-- .blog-comments -->

<div class="blog-form">    
    <?php comment_form(array(
        'comment_notes_before' => '<p><b>Please be advised that your comment here will be displayed publicly and you should never reveal any identifying or incriminating information in a blog comment.</b> Also remember to be cool to other community members on our blog or we will delete your stuff. Be courteous, and have fun!</p>',
        'comment_notes_after' => '<div class="form-submit-wrap clearfix">
            <div class="form-required-notice">All fields are required</div>
            <button type="submit" class="btn btn-primary">SEND MESSAGE</button>
        </div>',
        'title_reply' => 'Leave a Reply',
        'title_reply_to' => 'Leave a Reply',
        'fields' => array(
            'author' =>
                '<div class="form-group">
                    <label for="author">' . __( 'Name', 'domainreference' ) . '</label> ' .
                    '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) .'" size="30" class="form-control"' . $aria_req . ' />
                </div>',
            'email' =>
                '<div class="form-group">
                    <label for="email">' . __( 'Email', 'domainreference' ) . '</label> ' .
                    '<input id="email" name="email" type="text" value="' . esc_attr(  $commenter['comment_author_email'] ) .'" size="30" class="form-control"' . $aria_req . ' />
                </div>',
        ),
        'comment_field' => 
            '<div class="form-group">
                <label for="comment">Your Comment</label>
                <textarea class="form-control" id="comment" name="comment" rows="4" aria-required="true"></textarea>
            </div>'
    )) ?>
</div> <!-- .blog-form -->