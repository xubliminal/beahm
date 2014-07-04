<!DOCTYPE html>
<!--[if IE 7]>
<html class="ie ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html class="ie ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 7) | !(IE 8)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php wp_title('|', true, 'right'); ?> <?php bloginfo('name') ?></title>
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
    <link rel="icon" type="image/x-icon" href="<?php echo ot_get_option('favicon') ?>" />
    <link href="<?php echo get_template_directory_uri(); ?>/styles/bootstrap.css" rel="stylesheet" media="screen" />
    <link href="<?php echo get_template_directory_uri(); ?>/styles/fonts.css" rel="stylesheet" media="screen" />
    <link href="<?php echo get_template_directory_uri(); ?>/styles/main.css" rel="stylesheet" media="screen" />
    <!--[if lt IE 9]>
    <script src="<?php echo get_template_directory_uri(); ?>/scripts/html5shiv.js"></script>
    <script src="<?php echo get_template_directory_uri(); ?>/scripts/respond.min.js"></script>
    <![endif]-->
    <?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>
    <?php wp_head() ?>
</head>
<body>
<div id="main-container">
<div id="nav-wrap">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <a href="<?php bloginfo('wpurl') ?>" class="logo"></a>
                <div id="header-phone"><span><?php echo ot_get_option('phone') ?></span></div>
                <a class="navbar-toggle" data-target=".navbar-collapse" data-toggle="collapse" href="#"></a>                
                <?php wp_nav_menu(array(
                        'theme_location'  => 'main',
                        'container'       => false,
                        'menu_class'      => 'nav nav-pills collapse navbar-collapse menu'
                )) ?>
            </div> <!-- .col-xs-12 -->
        </div> <!-- .row -->
    </div> <!-- .container -->
</div> <!-- #nav-wrap -->
<div id="main-content-container">