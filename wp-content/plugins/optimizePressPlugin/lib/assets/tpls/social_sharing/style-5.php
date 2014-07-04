<?php include('style.inc.php'); ?>

<ul id="<?php echo $id; ?>" data-counter="false" class="social-sharing social-media-horizontal-bubble social-sharing-style-5">
    <li><div class="fb-like" data-href="<?php echo $fb_like_url; ?>" data-send="false" data-layout="box_count" data-width="450" data-show-faces="true"></div></li>
    <li><div class="g-plusone" data-size="tall" data-href="<?php echo $g_url; ?>"></div></li>
</ul>

<!-- Facebook -->
<div id="fb-root"></div><script>(function(d, s, id) { var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) return; js = d.createElement(s); js.id = id; js.src = "//connect.facebook.net/en_US/all.js#xfbml=1"; fjs.parentNode.insertBefore(js, fjs); }(document, 'script', 'facebook-jssdk'));</script>
<!-- Google+ -->
<script type="text/javascript">(function() { var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true; po.src = 'https://apis.google.com/js/plusone.js'; var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s); })();</script>