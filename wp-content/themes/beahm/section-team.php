<?php $team = ot_get_option('tesm') ?>
<?php if(count($team)): ?>
<div class="white-block">
    <div class="container">
        <div class="wb-caption text-center">
            <?php echo ot_get_option('fighters_header') ?>
        </div> <!-- .wb-caption -->
        <div class="row">
            <?php foreach($team as $p): ?>
            <div class="person col-sm-4" style="margin:auto;">
                <div class="person-photo"><img src="<?php echo $p['photo'] ?>" alt="" /></div>
                <h3 class="person-name"><?php echo $p['title'] ?></h3>
                <div class="person-cv">
                    <div class="person-cv-in">
                        <?php echo $p['description'] ?>
                    </div>
                </div> <!-- .person-cv -->
            </div> <!-- .person -->
            <?php endforeach ?>
        </div> <!-- .row -->
        <hr />
        <?php get_template_part('section', 'asseenon'); ?>
    </div> <!-- .container -->
</div> <!-- .white-block -->
<?php endif ?>