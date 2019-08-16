<?php
function wordpressify_resources() {
	wp_enqueue_style( 'style', get_stylesheet_uri() );
	wp_enqueue_script( 'header_js', get_template_directory_uri() . '/js/header-bundle.js', null, 1.0, false );
	wp_enqueue_script( 'footer_js', get_template_directory_uri() . '/js/footer-bundle.js', null, 1.0, true );
}

add_action( 'wp_enqueue_scripts', 'wordpressify_resources' );

// Customize excerpt word count length
function custom_excerpt_length() {
	return 22;
}

add_filter( 'excerpt_length', 'custom_excerpt_length' );

// Theme setup
function wordpressify_setup() {
	// Handle Titles
	add_theme_support( 'title-tag' );

	// Add featured image support
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'small-thumbnail', 720, 720, true );
	add_image_size( 'square-thumbnail', 80, 80, true );
	add_image_size( 'banner-image', 1024, 1024, true );
}

add_action( 'after_setup_theme', 'wordpressify_setup' );

show_admin_bar( false );

// Checks if there are any posts in the results
function is_search_has_results() {
	return 0 != $GLOBALS['wp_query']->found_posts;
}

// Add Widget Areas
function wordpressify_widgets() {
	register_sidebar(
		array(
			'name'          => 'Sidebar',
			'id'            => 'sidebar1',
			'before_widget' => '<div class="widget-item">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}

add_action( 'widgets_init', 'wordpressify_widgets' );

add_action( 'after_setup_theme', function(){
	register_nav_menus( [
		'header_menu' => 'Header',
		'footer_menu' => 'Footer'
	] );
} );


function remove_linked_products($tabs){
 
				 unset($tabs['inventory']);
 
				 unset($tabs['shipping']);
 
				 unset($tabs['linked_product']);
 
				//  unset($tabs['attribute']);
 
				 unset($tabs['advanced']);
 
				 return($tabs);
 
 }
 
 add_filter('woocommerce_product_data_tabs', 'remove_linked_products', 10, 1);
 
 
 
 
 
// Rename WooCommerce to Shop
 
add_action( 'admin_menu', 'rename_woocoomerce', 999 );
 
function rename_woocoomerce()
{
    global $menu;
 
    // Pinpoint menu item
	$woo = rename_woocommerce( 'WooCommerce', $menu );
	$woo2 = rename_woocommerce( 'Varer', $menu );
 
    // Validate
    if(  ( !$woo ) || ( !$woo2 )  )
		return;
		
	if( $woo ) {
		$menu[$woo][0] = 'Store Settings';
	}
	
	if( $woo2 ) {
		$menu[$woo2][0] = 'Domæner';
	}
	
}
 
function rename_woocommerce( $needle, $haystack )
{
    foreach( $haystack as $key => $value )
    {
        $current_key = $key;
        if(
            $needle === $value
            OR (
                is_array( $value )
                && rename_woocommerce( $needle, $value ) !== false
            )
        )
        {
            return $current_key;
        }
    }
    return false;
}



function domains_widgets_init() {

	register_sidebar( array(
		'name'          => 'Domains Page Widget',
		'id'            => 'domains_page_widget',
		'before_widget' => '<div>',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="rounded">',
		'after_title'   => '</h2>',
	) );

}
add_action( 'widgets_init', 'domains_widgets_init' );





add_filter( 'woocommerce_add_to_cart_validation', 'remove_cart_item_before_add_to_cart', 20, 3 );
function remove_cart_item_before_add_to_cart( $passed, $product_id, $quantity ) {
    if( ! WC()->cart->is_empty())
        WC()->cart->empty_cart();
    return $passed;
}