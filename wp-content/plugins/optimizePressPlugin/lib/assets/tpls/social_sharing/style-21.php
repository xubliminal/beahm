<?php include('style.inc.php'); ?>

<script id="js_<?php echo $id; ?>">
(function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/<?php echo $fb_lang; ?>/all.js#xfbml=1&appId=<?php echo op_default_attr('comments','facebook','id'); ?>";
        fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

window.fbAsyncInit = function(){
    FB.Event.subscribe('edge.create', function(response) {
        jQuery('#<?php echo $id; ?> span:first-of-type').height((jQuery('#<?php echo $id; ?> span:first-of-type').height() + 145) + 'px');
    });
    
    FB.Event.subscribe('message.send', function(response) {
        jQuery('#<?php echo $id; ?> span:first-of-type').height((jQuery('#<?php echo $id; ?> span:first-of-type').height() + 161) + 'px');
    });
};

</script>
<script>
    jQuery('#js_<?php echo $id; ?>').remove();
</script>
<div id="<?php echo $id; ?>" class="social-sharing social-sharing-style-21">
    <div class="fb-like" data-href="<?php echo $fb_like_url; ?>" data-send="true" data-width="450" data-show-faces="true"></div>
</div>