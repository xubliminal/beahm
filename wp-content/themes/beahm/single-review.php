<?php get_header(); the_post(); ?>
<?php $url = wp_get_attachment_url( get_post_thumbnail_id($post->ID) ); ?>
<div class="featured-image" style="<?php echo ($url) ? 'background:url('.$url.') center center; background-size: cover' : '' ?>">
	<h2><?php the_title() ?></h2>
</div>
<div class="container">
	<div class="row review-description">
		<div class="col-md-12">
			<h2><?php the_title() ?></h2>
			<?php the_content() ?>
		</div>
	</div>
</div>

<div class="main-review-content">
	<div class="container">
		<div class="row">
			<div class="col-md-8 col-sm-8 col-xs-12 main-review-results">
				<ul class="list-unstyled list-results">
					<li class="top-result cf">
						<img class="thumb" src="<?php the_field('business_photo') ?>" alt="" />
						<div class="yelp-rating">
							<strong>Yelp Rating</strong>
							<ul class="list-unstyled cf">
								<li class="full"></li>
								<li class="full"></li>
								<li class="full"></li>
								<li class="full"></li>
								<li class="full"></li>
							</ul>
							<span class="reviews-amount"><?php echo beahm_get_reviews_count() ?> reviews</span>
						</div>
						<div class="lawyer-info">
							<h3><?php the_field('business_name') ?></h3>
							<p><?php the_field('address') ?></p>
							<a href="#contact" class="scroll btn btn-primary">Request a call</a>
						</div>
						<ul class="list-unstyled contact-info cf">
							<li><a href="tel:<?php echo preg_replace('[\-]', '', get_field('phone_number')) ?>" class="phone"><?php the_field('phone_number') ?></a></li>
							<li><a href="mailto:info@beahmlaw.com" class="email">info@beahmlaw.com</a></li>
							<li><a href="javascript:StartNgageChat();" class="chat">Live chat</a></li>
						</ul>
					</li>
					<?php $result = beahm_yelp_listings(get_field('location'), get_field('query')) ?>
					<?php $black_list = beahm_get_blacklisted_arr(get_field('black_list')); ?>
					<?php foreach($result->businesses as $buss): ?>
					<?php if(beahm_not_blacklisted($buss->name, $black_list)): ?>
					<li class="cf">
						<img class="thumb" src="<?= $buss->image_url ?>" width="72" height="72" alt="" />
						<div class="yelp-rating">
							<strong>Yelp Rating</strong>
							<img src="<?= $buss->rating_img_url ?>" />
							<span class="reviews-amount"><?= $buss->review_count ?> reviews</span>
						</div>
						<div class="lawyer-info">
							<h3><?= $buss->name ?></h3>
							<p><?= $buss->location->cross_streets ?><br/>
							<?= $buss->location->city ?>, <?= $buss->location->state_code ?> <?= $buss->location->postal_code ?></p>
						</div>
					</li>
				<?php endif ?>
					<?php endforeach ?>
				</ul>
			</div>
			<div class="col-md-4 col-sm-4 col-xs-12">
				<?php dynamic_sidebar('reviews-sidebar') ?>
			</div>
		</div>
	</div>
</div>
<?php get_template_part('section', 'contact'); ?>
<?php get_footer() ?>