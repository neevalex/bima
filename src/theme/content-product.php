<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Ensure visibility.
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
?>

<li <?php wc_product_class( '', $product ); ?>>

<?php $image = wp_get_attachment_image_src( get_post_thumbnail_id( $loop->post->ID ), 'single-post-thumbnail' );
$image=$image[0];
if(empty($image)) $image = wc_placeholder_img_src();
?>
	
	<div class="inner">
	<div class="badges">
	<?php 
				$total_sales = get_post_meta( get_the_ID(), 'total_sales', true );
				if ( ($total_sales > 0) && (!($total_sales % 2 == 0)) ) {
					echo  '<span class="hot"><img src="'.get_template_directory_uri().'/img/deal.svg" class="tippy" data-tippy-content="Hurtig levering, da en anden bruger har allerede har bestilt links fra dette domæne. "></span>';
				}
				
				if ( ($total_sales > 4) ) {
					echo  '<span class="hot"><img src="'.get_template_directory_uri().'/img/burn.svg" class="tippy" data-tippy-content="Mange brugere har bestilt links fra dette domæne"></span>';
				}
	?>
	</div>
		<h3><?php the_title(); ?></h3>
		<div class="url"><?php echo '<a href="'.get_field('url', get_the_ID()).'" target="_blank">'.get_field('url', get_the_ID()).'</a>'; ?></div>
		<div class="extras">
			<ul>
				<li class="tippy" data-tippy-content="DR (Domain rating)" ><img src="<?php echo get_template_directory_uri(); ?>/img/dr.svg"><span><?php echo get_field('dr'); ?></span></li>
				<li class="tippy" data-tippy-content="Traffik" ><img src="<?php echo get_template_directory_uri(); ?>/img/traffic.svg"><span><?php echo get_field('traffic'); ?></span></li>
				<li class="tippy" data-tippy-content="Reffering domains" ><img src="<?php echo get_template_directory_uri(); ?>/img/ref_domain.svg"><span><?php echo get_field('ref_domains'); ?></span></li>
				<li class="tippy" data-tippy-content="Domæneregistrering" ><img src="<?php echo get_template_directory_uri(); ?>/img/hourglass.svg"><span><?php echo get_field('age'); ?></span></li>
				<li class="tippy" data-tippy-content="AD tag" ><img src="<?php echo get_template_directory_uri(); ?>/img/ad.svg"><span><?php echo get_field('ad'); ?></span></li>
			</ul>
		</div> 
		
		<div class="categories">
			<?php foreach(get_the_terms( get_the_ID(), 'product_cat' ) as $cat) { if( $cat->name !== 'Uncategorized' ) { echo '<span>'.$cat->name.'</span> '; } } ?>
		</div>
		
		<div class="content">
			<?php echo wp_trim_words( get_the_content(), 50, '...' ); ?>
		</div>
		
		<div class="purchase">
		<?php 
		    global $woocommerce, $product, $post;
			$product = wc_get_product(get_the_ID());
			
			if ( $product->get_type() == 'variable' ) {
			$variations = $product->get_available_variations();
				foreach ($variations as $variation){	
					// print_r($variation);	
				    $var_name = str_replace('-', ' ', $variation['attributes']['attribute_pa_purchase']);
					echo '<a class="tippy" href="/cart/?add-to-cart='.get_the_ID().'&variation_id='.$variation['variation_id'].'" data-tippy-content="'.$variation['variation_description'].'">'.$var_name.' <span class="price">'.$variation['display_price'].' '.get_woocommerce_currency_symbol().'</span></a>';
				}
			} else {
				echo '<a href="/cart/?add-to-cart='.get_the_ID().'" ><span class="price">'.$product->get_price().' '.get_woocommerce_currency_symbol().'</span></a>';
			}
 		?>
		</div>
		
	</div>
</li>
