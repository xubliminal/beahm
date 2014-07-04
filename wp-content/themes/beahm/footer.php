</div>
<div id="footer">
    <div id="footer-in">
        <div class="container">
            <div class="row" id="footer-top">
                <div id="footer-logo-wrap">
                    <div id="footer-logo"></div>
                    <div id="footer-logo-text"><?php echo ot_get_option('slogan'); ?></div>
                </div> <!-- #footer-logo-wrap -->
                <div id="footer-top-1">
                    <div id="footer-phone"><span id="footer-phone-1"><span id="footer-phone-2"><?php echo ot_get_option('phone') ?></span></span></div>
                    <div id="footer-cards">
                        <img src="<?php echo get_template_directory_uri() ?>/images/clipart/card-1.png" alt="" />
                        <img src="<?php echo get_template_directory_uri() ?>/images/clipart/card-2.png" alt="" />
                        <img src="<?php echo get_template_directory_uri() ?>/images/clipart/card-3.png" alt="" />
                    </div>
                </div> <!-- #footer-top-1 -->
                <div id="footer-top-2">
                    <?php wp_nav_menu(array(
                            'theme_location'  => 'main',
                            'container_id'    => 'footer-nav',
                            'menu_class'      => 'list-unstyled menu'
                    )) ?>
                </div> <!-- #footer-top-2 -->
            </div> <!-- .row -->
            <div class="footer-sep"><img src="<?php echo get_template_directory_uri() ?>/images/footer-separator.png" alt="" /></div>
            <div class="row" id="footer-bottom">
                <div id="footer-bottom-1" class="col-sm-5 col-sm-push-7">
                    <a href="http://www.beahmlaw.com/terms-of-service/">Terms of Service</a>
                    &nbsp;&nbsp;&nbsp;&nbsp; • &nbsp;&nbsp;&nbsp;&nbsp;
                    <a href="http://www.beahmlaw.com/privacy-policy/">Privacy Policy</a>
                </div>
                <div id="footer-bottom-2" class="col-sm-7 col-sm-pull-5">
                    <?php echo str_replace('[year]', date('Y'), ot_get_option('copyright')) ?>
                </div>
            </div> <!-- .row -->
        </div> <!-- .container -->
    </div> <!-- #footer-in -->
</div> <!-- #footer -->
</div>
<?php wp_footer() ?>
<script src="<?php echo get_template_directory_uri(); ?>/scripts/jquery.scrollTo.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/scripts/jquery.localscroll.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/scripts/bootstrap.min.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/scripts/main.js"></script>



<script type="text/javascript">
adroll_adv_id = "4KUBUBY3YZAS5CKWFDCKF7";
adroll_pix_id = "VPV6ZC6O5VF3JNHYTRHQWM";
(function () {
var oldonload = window.onload;
window.onload = function(){
   __adroll_loaded=true;
   var scr = document.createElement("script");
   var host = (("https:" == document.location.protocol) ? "https://s.adroll.com" : "http://a.adroll.com");
   scr.setAttribute('async', 'true');
   scr.type = "text/javascript";
   scr.src = host + "/j/roundtrip.js";
   ((document.getElementsByTagName('head') || [null])[0] ||
    document.getElementsByTagName('script')[0].parentNode).appendChild(scr);
   if(oldonload){oldonload()}};
}());
</script>

<!-- beahmlaw.com -->
<!-- Start Of NGage -->
<div id="nGageLH" style="visibility:hidden; display: block; padding: 0; position: fixed; left: 0px; bottom: 50%; z-index: 5000;"></div>
<script type="text/javascript" src="https://messenger.ngageics.com/ilnksrvr.aspx?websiteid=129-106-6-149-100-233-255-49"></script>
<!-- End Of NGage -->



</body>
</html>