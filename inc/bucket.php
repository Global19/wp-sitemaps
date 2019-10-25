<?php
/**
 * Each page has 50,000 / CORE_SITEMAPS_POSTS_PER_BUCKET buckets.
 */

defined( 'ABSPATH' ) || die();

/**
 * Register the Sitemap Bucket custom post-type.
 */
function core_sitemaps_bucket_register() {
	$labels = array(
		'name'          => _x( 'Sitemap Buckets', 'Sitemap Bucket General Name', 'core-sitemaps' ),
		'singular_name' => _x( 'Sitemap Bucket', 'Sitemap Bucket Singular Name', 'core-sitemaps' ),
	);
	$args   = array(
		'label'           => __( 'Sitemap Bucket', 'core-sitemaps' ),
		'description'     => __( 'Bucket of sitemap links', 'core-sitemaps' ),
		'labels'          => $labels,
		'supports'        => array( 'editor', 'custom-fields' ),
		'can_export'      => false,
		'rewrite'         => false,
		'capability_type' => 'post',
	);
	register_post_type( CORE_SITEMAPS_CPT_BUCKET, $args );
}

/**
 * Calculate the sitemap bucket number the post belongs to.
 *
 * @param int $post_id Post ID.
 *
 * @return int Sitemap Page pagination number.
 */
function core_sitemaps_page_calculate_bucket_num( $post_id ) {
	// TODO this lookup might need to be more refined and set min/max
	return 1 + (int) floor( $post_id / CORE_SITEMAPS_POSTS_PER_BUCKET );
}

/**
 * Get the Sitemap Page for a pagination number.
 *
 * @param string $post_type Registered post-type.
 * @param int    $bucket_num Sitemap Page pagination number.
 *
 * @return bool|int[]|WP_Post[] Zero or more Post objects of the type CORE_SITEMAPS_CPT_PAGE.
 */
function core_sitemaps_bucket_lookup( $post_type, $bucket_num ) {
	$page_query            = new WP_Query();
	$registered_post_types = core_sitemaps_registered_post_types();
	if ( false === isset( $registered_post_types[ $post_type ] ) ) {
		return false;
	}
	$query_result = $page_query->query(
		array(
			'post_type'  => CORE_SITEMAPS_CPT_BUCKET,
			'meta_query' => array(
				array(
					'key'   => 'bucket_num',
					'value' => $bucket_num,
				),
				array(
					'key'   => 'post_type',
					'value' => $post_type,
				),
			),
		)
	);

	return $query_result;
}

/**
 * Create a sitemaps page with post info.
 *
 * @param WP_Post $post Post object.
 * @param int     $bucket_num Sitemap bucket number.
 *
 * @return int|WP_Error @see wp_update_post()
 */
function core_sitemaps_bucket_insert( $post, $bucket_num ) {
	$args = array(
		'post_type'    => CORE_SITEMAPS_CPT_BUCKET,
		'post_content' => wp_json_encode(
			array(
				$post->ID => core_sitemaps_url_content( $post ),
			)
		),
		'meta_input'   => array(
			'bucket_num' => $bucket_num,
			'post_type'  => $post->post_type,
		),
		'post_status'  => 'publish',
	);

	return wp_insert_post( $args );
}

/**
 * Update a sitemap page with post info.
 *
 * @param WP_Post $post Post object.
 * @param WP_Post $page Sitemap Page object.
 *
 * @return int|WP_Error @see wp_update_post()
 */
function core_sitemaps_bucket_update( $post, $page ) {
	$items              = json_decode( $page->post_content, true );
	$items[ $post->ID ] = core_sitemaps_url_content( $post );
	$page->post_content = wp_json_encode( $items );

	return wp_update_post( $page );
}
