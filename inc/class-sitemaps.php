<?php
/**
 * Class file for the Core_Sitemaps class.
 * This is the main class integrating all other classes.
 *
 * @package Core_Sitemaps
 */

/**
 * Class Core_Sitemaps
 */
class Core_Sitemaps {
	/**
	 * The main index of supported sitemaps.
	 *
	 * @var Core_Sitemaps_Index
	 */
	public $index;

	/**
	 * The main registry of supported sitemaps.
	 *
	 * @var Core_Sitemaps_Registry
	 */
	public $registry;

	/**
	 * Core_Sitemaps constructor.
	 */
	public function __construct() {
		$this->index    = new Core_Sitemaps_Index();
		$this->registry = new Core_Sitemaps_Registry();
	}

	/**
	 * Initiate all sitemap functionality.
	 *
	 * @return void
	 */
	public function bootstrap() {
		add_action( 'init', array( $this, 'setup_sitemaps_index' ) );
		add_action( 'init', array( $this, 'register_sitemaps' ) );
		add_action( 'init', array( $this, 'setup_sitemaps' ) );
	}

	/**
	 * Set up the main sitemap index.
	 */
	public function setup_sitemaps_index() {
		$this->index->setup_sitemap();
	}

	/**
	 * Register and set up the functionality for all supported sitemaps.
	 */
	public function register_sitemaps() {
		/**
		 * Filters the list of registered sitemap providers.
		 *
		 * @param array $providers Array of Core_Sitemap_Provider objects.
		 *
		 * @since 0.1.0
		 *
		 */
		$providers = apply_filters( 'core_sitemaps_register_providers', array(
			'posts' => new Core_Sitemaps_Posts(),
			'users' => new Core_Sitemaps_Users(),
		) );

		// Register each supported provider.
		foreach ( $providers as $provider ) {
			$this->registry->add_sitemap( $provider->name, $provider );
		}
	}

	/**
	 * Register and set up the functionality for all supported sitemaps.
	 */
	public function setup_sitemaps() {
		$sitemaps = $this->registry->get_sitemaps();

		add_rewrite_tag( '%sub_type%', '([^?]+)' );

		// Set up rewrites and rendering callbacks for each supported sitemap.
		foreach ( $sitemaps as $sitemap ) {
			/** @noinspection PhpUndefinedMethodInspection */
			add_rewrite_rule( $sitemap->route, $sitemap->rewrite_query(), 'top' );
			add_action( 'template_redirect', array( $sitemap, 'render_sitemap' ) );
		}
	}
}
