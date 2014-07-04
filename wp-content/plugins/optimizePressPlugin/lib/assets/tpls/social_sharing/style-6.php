<?php include('style.inc.php'); ?>

<ul id="<?php echo $id; ?>" data-counter="false" class="social-sharing social-media-horizontal-bubble social-sharing-style-6">
    <li><div class="fb-like" data-href="<?php echo $fb_like_url; ?>" data-send="false" data-layout="box_count" data-width="450" data-show-faces="true"></div></li>
    <li><div class="g-plusone" data-size="tall" data-href="<?php echo $g_url; ?>"></div></li>
    <li><a href="https://twitter.com/share" class="twitter-share-button" data-url="<?php echo $tw_url; ?>" <?php echo ($tw_name != ''?' data-via="'.op_attr($tw_name).'"':'');?> <?php echo (ucfirst($tw_text) != ''?' data-text="'.op_attr($tw_text).'"':'');?> data-lang="<?php echo $tw_lang;?>" data-related="anywhereTheJavascriptAPI" data-count="vertical"><?php __('Tweet', OP_SN);?></a></li>
    <li><script src="//platform.linkedin.com/in.js" type="text/javascript">lang: <?php echo $linkedin_lang; ?></script><script type="IN/Share" data-url="<?php echo $linkedin_url; ?>" id="<?php echo $id; ?>" data-counter="top"></script></li>
    <li><a href="http://pinterest.com/pin/create/button/?url=<?php echo urlencode($p_url); ?>&media=<?php echo urlencode($p_image_url); ?>&description=<?php echo urlencode($p_description); ?>" data-pin-do="buttonPin" data-pin-config="above"><img src="http://assets.pinterest.com/images/pidgets/pin_it_button.png" /></a></li>
</ul>

<!-- Facebook -->
<div id="fb-root"></div><script>(function(d, s, id) { var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) return; js = d.createElement(s); js.id = id; js.src = "//connect.facebook.net/en_US/all.js#xfbml=1"; fjs.parentNode.insertBefore(js, fjs); }(document, 'script', 'facebook-jssdk'));</script>
<!-- Twitter -->
<script type="text/javascript">!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
<!-- Google+ -->
<script type="text/javascript">(function() { var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true; po.src = 'https://apis.google.com/js/plusone.js'; var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s); })();</script>
<!-- Pinterest -->
<script type="text/javascript" src="http://assets.pinterest.com/js/pinit.js"></script>