<?php $seenOn = ot_get_option('seen_on') ?>
<?php if(count($seenOn)): ?>
<div class="wb-caption text-center">
    <h2>As seen on</h2>
</div>
<div class="row publicaction-logos text-center">
    <?php foreach($seenOn as $l): ?>
    <div class="col-xs-4 col-sm-2"><img src="<?php echo $l['logo'] ?>" alt="" title="<?php echo $l['title'] ?>" class="img-responsive" /></div>
    <?php endforeach ?>
</div> <!-- .row -->
<?php endif ?>