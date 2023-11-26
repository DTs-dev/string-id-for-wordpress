<?php
// Add activation function of meta box "string_id_field"
function string_id_field() {
	add_meta_box( 'string_id_field', __( 'String ID (short slug)', 'your-lang-domain' ), 'string_id_field_input', [ 'page', 'post' ], 'side', 'high' );
}
add_action( 'add_meta_boxes', 'string_id_field', 1 );
?>
<?php
// Frontend for meta box "string_id_field"
function string_id_field_input( $post ) {
?>
	<p><label><input type="text" name="field_st[_string_id]" value="<?php echo get_post_meta( $post->ID, '_string_id', 1 ); ?>" style="width:100%" /></label></p>
	<input type="hidden" name="string_id_field_nonce" value="<?php echo wp_create_nonce(__FILE__); ?>" />
<?php } ?>
<?php

// Saving data when updating a post
function string_id_field_update( $post_id ) {
	if( empty( $_POST['field_st'] ) || !wp_verify_nonce( $_POST['string_id_field_nonce'], __FILE__ ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return false;
	}
	$_POST['field_st'] = array_map( 'sanitize_text_field', $_POST['field_st'] ); // clear data from spaces
	foreach( $_POST['field_st'] as $key => $value ) {
		if( empty( $value ) ) {
			delete_post_meta( $post_id, $key ); // remove the field if the value is empty
			continue;
		}
		global $wpdb;
		$_sql = $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s", $key, $value );
		$current_post_meta_value = get_post_meta( $post_id, $key, true );
		if( $wpdb->get_var( $_sql ) && $value !== $current_post_meta_value ) {
			add_filter( 'redirect_post_location', 'add_notice_query_var_string_id', 99 );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
	return $post_id;
}
add_action( 'save_post', 'string_id_field_update', 0 );

// Notification of an already existing custom field value
function add_notice_query_var_string_id( $location ) {
	remove_filter( 'redirect_post_location', 'add_notice_query_var', 99 );
	return add_query_arg( array( 'string_id_value_exist' => 'ID' ), $location );
}
function string_id_admin_notices() {
	if( !isset( $_GET['string_id_value_exist'] ) )
		return;
	_e( '<div class="notice notice-error is-dismissible"><p><strong>This value of the custom field "String ID" already exists!</strong></p></div>', 'your-lang-domain' );
}
add_action( 'admin_notices', 'string_id_admin_notices' );

// Add function for getting of post id by string post ID (short slug)
function get_post_by_string_id( $string_id, $translate = true, $post_type = [ 'post', 'page' ] ) {
	is_array( $string_id ) ? $string_ids = $string_id : $string_ids[] = $string_id;
	foreach( $string_ids as $string_id ) {
		$args = array(
			'post_type' => $post_type,
			'lang'    => '',
			'meta_query' => array(
				array(
					'key'     => '_string_id',
					'value'   => $string_id
				)
			)
		);
		$string_posts_query = new WP_Query( $args );

		if( $string_posts_query->have_posts() ) {
			while( $string_posts_query->have_posts() ) {
				$string_posts_query->the_post();
				$string_post = get_the_ID();
				$string_posts[] = $translate ? ( function_exists( 'pll_get_post' ) ? ( pll_get_post( $string_post ) ?: $string_post ) : $string_post ) : $string_post;
			}
		} else {
			continue;
		}
		wp_reset_postdata();
	}
	return $string_posts = ( !isset( $string_posts[1] ) ? $string_posts[0] : $string_posts ) ?? false;
}

// Add boolean function for post checking on string post id
function is_string_id( $string_id, $subpage = false, $translate = true, $post_type = [ 'post', 'page' ] ) {
	global $post;
	$string_id_posts = get_post_by_string_id( $string_id, $translate, $post_type );
	$string_id_posts = !is_array( $string_id_posts ) ? [ $string_id_posts ] : $string_id_posts;
	if( $post_type === 'page' ) {
		if( is_page( $string_id_posts ) && $string_id_posts !== false || ( $subpage == 1 && is_subpage( $string_id_posts ) ) || ( $subpage == 0 && $subpage !== false && is_subpage( $string_id_posts, $subpage ) ) ) {
			return true;
		}
	} elseif( $post_type === 'post' ) {
		if( is_single( $string_id_posts ) && get_post_type() === $post_type && $string_id_posts !== false ) {
			return true;
		}
	} else {
		if( ( is_page() || is_single() ) && ( in_array( $post->ID, $string_id_posts ) && ( $subpage === false || ( $subpage !== false && !is_subpage( $string_id_posts ) ) ) || ( $subpage == 1 && is_subpage( $string_id_posts ) ) || ( $subpage == 0 && $subpage !== false && is_subpage( $string_id_posts, $subpage ) ) ) ) {
			return true;
		}
	}
}

// Get post string id by post id
function get_string_id( $post_id = 0 ) {
	$post = get_post( $post_id );
	$post_id = $post->ID;
	return pll_get_post_language( $post_id ) == pll_current_language() ? get_post_meta( pll_get_post( $post_id, pll_default_language() ), '_string_id', 1 ) : false;
}

// Add class with value for custom field "string_id" to body
add_filter( 'body_class', 'dts_add_class_cfvalue' );
function dts_add_class_cfvalue( $classes ) {
	global $post;
	if ( $cfvalue = get_post_meta( $post->ID, '_string_id', true ) ) {
		$classes[] = $cfvalue;
	}
	return $classes;
}

// Add shortcode for get content by string ID
add_shortcode( 'content', 'get_content' );
function get_content( $atts ) {
	if( !empty( $atts['sid'] ) ) {
		$post_id = get_post_by_string_id( $atts['sid'] );
	} else {
		$post_id = NULL;
	}

	$content = '<h2>' . get_the_title( $post_id ) . '</h2>';
	$content .= apply_filters( 'the_content', get_the_content( NULL, NULL, $post_id ) );

	return $content;
}
?>
