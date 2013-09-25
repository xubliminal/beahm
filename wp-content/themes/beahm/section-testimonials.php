<?php $testimonials = ot_get_option('testimonials_items') ?>
<?php if(count($testimonials) > 0): ?>
<div id="testimonials">
    <div id="testi-top" class="container">
        <?php echo ot_get_option('testimonials') ?>
    </div> <!-- .container -->
    <div class="container">
        <?php foreach($testimonials as $t): ?>
        <div class="testimonial col-sm-4">
            <div class="testi-top"><span class="testi-icon"></span></div>
            <div class="testi-text">
                <p><?php echo $t['text'] ?></p>
                <p><strong><?php echo $t['title'] ?></strong></p>
            </div>
            <div class="testi-logo"><img src="<?php echo $t['logo'] ?>" alt="" /></div>
        </div> <!-- .testimonial -->
        <?php endforeach ?>
    </div> <!-- .container -->
</div> <!-- #testimonials -->
<?php endif ?>