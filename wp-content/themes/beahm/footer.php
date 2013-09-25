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
                    </div> <!-- #footer-nav -->
                </div> <!-- #footer-top-2 -->
            </div> <!-- .row -->
            <div class="footer-sep"><img src="<?php echo get_template_directory_uri() ?>/images/footer-separator.png" alt="" /></div>
            <div class="row" id="footer-bottom">
                <div id="footer-bottom-1" class="col-sm-6 col-sm-push-6">
                    <a href="#">Terms of Use</a>
                    &nbsp;&nbsp;&nbsp;&nbsp; • &nbsp;&nbsp;&nbsp;&nbsp;
                    <a href="#">Privacy Policy</a>
                </div>
                <div id="footer-bottom-2" class="col-sm-6 col-sm-pull-6">
                    <?php echo str_replace('[year]', date('Y'), ot_get_option('copyright')) ?>
                </div>
            </div> <!-- .row -->
        </div> <!-- .container -->
    </div> <!-- #footer-in -->
</div> <!-- #footer -->
<?php wp_footer() ?>
<script src="<?php echo get_template_directory_uri(); ?>/scripts/jquery.scrollTo.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/scripts/jquery.localscroll.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/scripts/bootstrap.min.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/scripts/main.js"></script>
<script type="text/javascript">var switchTo5x=true;</script>
<script type="text/javascript" src="http://w.sharethis.com/button/buttons.js"></script>
<script type="text/javascript">stLight.options({publisher: "4584cec8-4f04-423c-8043-5ddc85eeb942", doNotHash: false, doNotCopy: false, hashAddressBar: false});</script>
</body>
</html>