<?php

class SearchPostContent extends Search {
	function find( $pattern, $filter, $limit, $offset, $orderby ) {
		global $wpdb;

		$args = [
			'post_status' 	=> ['publish', 'pending', 'draft', 'auto-draft', 'future', 'private'],
			'orderby' 		=> 'ID',
			'order' 		=> $orderby,
			'tax_query' 	=> [],
		];

		// category filter
		if (isset($filter['category'])) {
			$args['post_type'] = ['post'];
			$args['tax_query'][] = [
				'taxonomy' 	=> 'category',
				'field'		=> 'slug',
				'terms'		=> $filter['category'],
			];
		} else {
			$args['post_type'] = ['post', 'page'];
		}

		if ($limit > 0) {
			$args['posts_per_page'] = $limit;
			$args['offset'] = $offset;
		} else {
			$args['nopaging'] = true;
		}

		$query = new WP_Query($args);

		$results = array();
		$posts   = $query->posts;

		if ( count( $posts ) > 0 ) {
			foreach ( $posts as $post ) {
				if ( ( $matches = $this->matches( $pattern, $post->post_content, $post->ID ) ) ) {
					foreach ( $matches AS $match ) {
						$match->title = $post->post_title;
					}

					$results = array_merge( $results, $matches );
				}
			}
		}

		return $results;
	}

	function get_options ($result)
	{
		$options[] = '<a href="'.get_permalink ($result->id).'">'.__ ('view', 'search-regex').'</a>';

		if (current_user_can ('edit_post', $result->id))
			$options[] = '<a href="'.get_bloginfo ('wpurl').'/wp-admin/post.php?action=edit&amp;post='.$result->id.'">'.__ ('edit','search-regex').'</a>';
		return $options;
	}

	function show ($result)
	{
		printf (__ ('Post #%d: %s', 'search-regex'), $result->id, $result->title);
	}

	function name () { return __ ('Post content', 'search-regex'); }

	function get_content ($id)
	{
		global $wpdb;

		$post = $wpdb->get_row ( $wpdb->prepare( "SELECT post_content FROM {$wpdb->prefix}posts WHERE id=%d", $id ) );
		return $post->post_content;
	}

	function replace_content ($id, $content)
	{
		global $wpdb;
		$wpdb->query ($wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content=%s WHERE ID=%d", $content, $id ) );
	}
}
