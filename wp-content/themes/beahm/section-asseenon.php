<?php $seenOn = ot_get_option('seen_on') ?>
<?php if(count($seenOn)): ?>
<div class="wb-caption text-center">
    <h2>As seen on</h2>
</div>
<div class="row publicaction-logos text-center">
    <?php foreach($seenOn as $l): ?>
    <div class="col-xs-4 col-sm-2"><a href="#"><img src="<?php echo $l['logo'] ?>" alt="" title="<?php echo $l['title'] ?>" class="img-responsive" /></a></div>
    <?php endforeach ?>
</div> <!-- .row -->
<p class="text-center"><a href="#" class="link-more">View All Publications</a></p>
<?php endif ?>