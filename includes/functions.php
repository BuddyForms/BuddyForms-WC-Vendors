<?php

add_filter( 'buddyforms_front_js_css_loader', 'bp_wc_vendors_front_js_css_loader', 10, 1 );
function bp_wc_vendors_front_js_css_loader( $fount ) {
	return true;
}

add_filter( 'wcv_dashboard_quick_links', 'bp_wc_vendors_dashboard_quick_links', 10, 1 );
function bp_wc_vendors_dashboard_quick_links( $quick_links ) {

	$bp_wc_vendors_options = bp_wc_vendors_get_options();

	$quick_links_new = Array();

	if ( ! isset( $bp_wc_vendors_options['tab_products_disabled'] ) ) {
		$quick_links_new['product'] = $quick_links['product'];
	}

	if ( ! isset( $bp_wc_vendors_options['tab_coupons_disabled'] ) ) {
		$quick_links_new['shop_coupon'] = $quick_links['shop_coupon'];
	}

	return $quick_links_new;
}

add_action( 'template_redirect', 'bp_wc_vendors_store_redirect_to_profile' );
function bp_wc_vendors_store_redirect_to_profile() {
	global $bp, $wp_query;

	$pagename = get_query_var( 'pagename' );

//	if( ! WCV_Vendors::is_vendor_page()){
//		return;
//	}

	if( ! in_array('shop_settings', $bp->unfiltered_uri ) ) {
		return;
	}

//	if ( get_query_var( 'pagename' ) != 'edit' ) {
//		return;
//	}

//	if(!in_array('edit', $bp->action_variables)){
//		return;
//	}

	$bp_wc_vendors_options = bp_wc_vendors_get_options();
	if ( ! isset( $bp_wc_vendors_options['redirect_vendor_store_to_profil'] ) && $bp_wc_vendors_options['redirect_vendor_store_to_profil'] == 'none' ) {
		return;
	}

	$vendor_shop = get_query_var( 'vendor_shop' );

	if ( $form_slug == 'none' ) {
		$link = get_bloginfo( 'url' ) . '/' . $bp->pages->members->slug . '/' . $vendor_shop . ' /';
		wp_safe_redirect( $link );
		exit;
	}

	$link = get_bloginfo( 'url' ) . '/' . $bp->pages->members->slug . '/' . $vendor_shop . ' /' . $form_slug . '/';
	wp_safe_redirect( $link );
	exit;
}


add_action( 'template_redirect', 'bp_wc_vendors_dashboard_redirect_to_profile' );
function bp_wc_vendors_dashboard_redirect_to_profile() {
	global $wp_query, $post;

	if ( ! isset( $post->ID ) || ! is_user_logged_in() ) {
		return false;
	}

	$user = wp_get_current_user();
	if ( ! in_array( 'vendor', (array) $user->roles ) ) {
		return false;
	}

	$link = bp_wc_vendors_get_redirect_link( $post->ID );

	if ( ! empty( $link ) ) :
		wp_safe_redirect( $link );
		exit;
	endif;
}

function bp_wc_vendors_get_redirect_link( $post_ID ) {
	global $bp, $current_user, $wp_query;

	$link = '';

	if( class_exists('WCVendors_Pro') ){
		$dashboard_page_id = WCVendors_Pro::get_option( 'dashboard_page_id' );
	} else{
		$dashboard_page_id = WC_Vendors::$pv_options->get_option( 'vendor_dashboard_page' );
	}


	if ( ! $dashboard_page_id ) {
		return $link;
	}

	$current_user = wp_get_current_user();
	$userdata     = get_userdata( $current_user->ID );

	$type   = get_query_var( 'object' );
	$action = get_query_var( 'action' );
	$id     = get_query_var( 'object_id' );

	if ( $dashboard_page_id == $post_ID ) {
		if ( $type == 'shop_coupon' ) {
			$link = get_bloginfo( 'url' ) . '/' . $bp->pages->members->slug . '/' . $userdata->user_nicename . '/vendor-dashboard/vendor-dashboard-coupons/' . $action . '/' . $id;
		} elseif ( $type == 'product' ) {
			$link = get_bloginfo( 'url' ) . '/' . $bp->pages->members->slug . '/' . $userdata->user_nicename . '/vendor-dashboard/vendor-dashboard-products/' . $action . '/' . $id;
		} else {
			$link = get_bloginfo( 'url' ) . '/' . $bp->pages->members->slug . '/' . $userdata->user_nicename . '/vendor-dashboard/';
		}
	}

	return $link;
}

function bp_wc_vendors_no_admin_access() {
	global $current_user, $bp;

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	$bp_wc_vendors_options = bp_wc_vendors_get_options();

	if ( isset( $bp_wc_vendors_options['no_admin_access'] ) ) {
		return;
	}

	$user_roles = $current_user->roles;
	$user_role  = array_shift( $user_roles );
	if ( $user_role === 'vendor' ) {
		bp_core_redirect( get_option( 'home' ) . '/' . $bp->pages->members->slug . '/' . bp_core_get_username( bp_loggedin_user_id() ) . '/vendor-dashboard' );
	}
}

add_action( 'admin_init', 'bp_wc_vendors_no_admin_access', 100 );

/**
 * Check if a subscriber have the needed rights to upload images and add this capabilities if needed.
 *
 * @package BuddyForms
 * @since 0.5 beta
 */
add_action( 'init', 'bp_wc_allow_vendor_uploads' );
function bp_wc_allow_vendor_uploads() {
	if ( current_user_can( 'vendor' ) && ! current_user_can( 'upload_files' ) ) {
		$contributor = get_role( 'vendor' );
		$contributor->add_cap( 'upload_files' );
	}
}

function bp_wc_vendors_view() {
	return true;
}

function bp_wc_vendors_get_options(){
	global $bp_wc_vendors_options;

	if(is_array($bp_wc_vendors_options)){
		return $bp_wc_vendors_options;
	}

	$bp_wc_vendors_options = get_option( 'bp_wc_vendors_options' );

	$options =  Array();
	if( is_array( $bp_wc_vendors_options ) ){
		foreach ( $bp_wc_vendors_options as $key => $options_array ){
			if( is_array( $options_array ) ){
				foreach ( $options_array as $slug => $option ) {
					$options[ $slug ] = $option;
				}
			}
		}
	}
	$bp_wc_vendors_options = $options;

	return $options;

}

add_filter( 'wc_get_template', 'bp_wc_vendors_woocommerce_before_template_part', 10, 5 );
function bp_wc_vendors_woocommerce_before_template_part($located, $template_name, $args, $template_path, $default_path ){

	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args );
	}

	if( $template_name == 'links.php' ){
		$template_path = BP_WCV_TEMPLATE_PATH . 'dashboard/';
		$located = wc_locate_template( $template_name, $template_path, $template_path );
	}

	return $located;
}


add_filter( 'buddyforms_members_parent_tab', 'bp_wc_vendors_buddyforms_members_parent_tab', 10, 2);

function bp_wc_vendors_buddyforms_members_parent_tab( $parent_tab_slug, $form_slug ){
	global $buddyforms;

//	$options = bp_wc_vendors_get_options();

	if( isset( $buddyforms[$form_slug] ) ){
		if( isset( $buddyforms[$form_slug]['wc_vendor_integration'] ) ){
			$parent_tab_slug = 'vendor-dashboard';
		}
	}

	return $parent_tab_slug;

}



add_filter( 'buddyforms_members_parent_setup_nav', 'bp_wcv_buddyforms_members_parent_setup_nav', 10, 2);

function bp_wcv_buddyforms_members_parent_setup_nav( $parent, $form_slug ){
	global $buddyforms;

//	$options = bp_wc_vendors_get_options();

	if( isset( $buddyforms[$form_slug] ) ){
		if( isset( $buddyforms[$form_slug]['wc_vendor_integration'] ) ){
			$parent = false;
		}
	}

	return $parent;

}

