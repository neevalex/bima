<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width">
	<?php wp_head(); ?>
	<script src="https://unpkg.com/popper.js@1"></script>
	<script src="https://unpkg.com/tippy.js@4"></script>
	<link rel="shortcut icon" href="<?php echo get_template_directory_uri(); ?>/img/favicon.png" type="image/x-icon">
</head>

<body <?php body_class(); ?>>
<header id="header" class="header">
	<div class="inner-content">
		<a href="<?php echo home_url(); ?>" class="logo">
			<img src="<?php echo get_template_directory_uri(); ?>/img/logo.png" alt="Logo 🌈">
			<img src="<?php echo get_template_directory_uri(); ?>/img/logo.png" alt="Logo 🌈" class="bright">
		</a>

		<nav class="navigation">
			
			<?php wp_nav_menu([
						'theme-location' => 'header_menu',
						'container'      => FALSE,
						'menu_class'     => 'navbar-nav ml-auto menu',
						'items_wrap'     => '<ul class="%2$s"><li class="nav-item"><a class="nav-link">%3$s</a></li></ul>',
						]
			);?>
		</nav>
		
		<nav class="user has-dropdown">
			<a href="#" class="github lrm-show-if-logged-in ">
					<span><?php echo wp_get_current_user()->user_email; ?></span>
					<img src="<?php echo get_template_directory_uri(); ?>/img/user.svg" alt="GitHub 🝓">				
			</a>
			
			<div class="dropdown lrm-show-if-logged-in">
						<a href="/membership/edit-your-profile/">Konto</a>
						<a href="/membership/your-membership/">Medlemskab</a>
						<a href="/my-account/orders/">Ordre</a>
						<a href="/my-account/downloads/">Downloads</a>
						<a href="/my-account/customer-logout/">Logud</a>
			</div>
			
			<a href="/membership" class="github lrm-hide-if-logged-in"><span>Medlemskab</span></a>
			<a href="#" class="github lrm-login lrm-hide-if-logged-in">
			   <span>Login</span> <img src="<?php echo get_template_directory_uri(); ?>/img/lock.svg" alt="GitHub 🝓">
			</a>
		</nav>
		
	</div>
</header>

