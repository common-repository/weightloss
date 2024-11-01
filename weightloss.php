<?php

/*
Plugin Name: 	Weightloss
Description: 	Checks for any Woocommerce products missing weights, which may cause postage calculators to malfunction upon customer checkout.
Version: 		0.1
Author: 		2blackbelts
Text Domain: 	weightloss
License: 		GPL2
License URI: 	https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    
    add_action('admin_menu', 'add_weight_loss_to_menu');

	function add_weight_loss_to_menu(){
		 add_submenu_page( '/edit.php?post_type=product', 'Weightloss Page', 'Check Weights', 'manage_options', 'weightloss', 'weight_loss_init' );
	}

	function weight_loss_init(){

		echo "<h1>Products missing weights</h1>";
		
		$args = array(
			'post_type' => array('product', 'product_variation')
			);

		$query = new WP_Query($args);

		if($query->have_posts()){
			$count = $query->found_posts;
			$missing_count = 0;

			echo '<p>We scanned ' . $count . ' products and found the following:</p>';

			echo '<ul>';

			// loop through all products and variations
			while ($query->have_posts()) {
				$query->the_post();
				$post_id = get_the_ID();

				// its a variable product
				if( get_post_type() == 'product_variation' ) {

					$product = new WC_Product_Variation($post_id);
					
				} else {
					// using WC_Product caused the parent of variations to show which often is only a shell product holding the variations (no weight or shipping info)
					$product = new WC_Product($post_id);	
				}

				// skip virtual or downloadable products, weights not applicable
				if($product->is_downloadable() || $product->is_virtual() ){
					the_post();
				}
				

				// check if product is missing a weight - strictly exclude virtual products
				if ( !$product->has_weight() && !$product->is_downloadable() && !$product->is_virtual()) {

					// use parent_id for edit url. Cannot edit variation directly.
					if($product->get_type() == 'variation'){
						$link_to = $product->get_parent_id();
					} else {
						$link_to = $post_id;
					}

					echo '<li><a href="' . get_edit_post_link( $link_to ) . '">' . get_the_title() .'</a><li>';
					
					// add one to missing count
					$missing_count ++;
				}
				
			}

			echo '</ul>';

			// Present happy message if no products are missing a weight
			if($missing_count == 0){
				echo '<p><strong>Good news, none of your products are missing a weight :)</strong></p>';
			}
			
			wp_reset_postdata();

		} else {
			// WP_Query returned an empty result
			echo 'No products found.';
		}

	}
}

	
