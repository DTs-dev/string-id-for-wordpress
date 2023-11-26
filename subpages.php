<?php
function is_subpage( $pids, $url_level = 1 ) {
	global $post;
	$pids = !is_array( $pids ) ? [ $pids ] : $pids;
	foreach ( $pids as $pid ) {
		if( $url_level == 1 && $pid === $post->post_parent ) {
			return true;
		} elseif( $url_level == 0 ) {
			$ancestors = get_post_ancestors( $post->ID );			// An array of parent IDs of the current page
			foreach ( $ancestors as $anc ) {				// ID of an individual parent from the array
				if ( $post->post_type == 'page' && $anc == $pid ) {	// If this is a page, and if the ID of the parent is equal to the ID of the specified parent
					return true;
				}
			}
		}
	}
	return false;
}
?>
