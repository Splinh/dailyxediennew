<?php
/**
 * REST API Security Gateway.
 *
 * Centralized access control for the WordPress REST API:
 * - Namespace allowlisting (unauthenticated access)
 * - Browser direct-access blocking
 * - Core endpoint capability enforcement
 * - Theme-level endpoint orchestration
 *
 * This is NOT a REST controller — it does not register any routes itself.
 * Module-owned endpoints register their own routes via rest_api_init.
 *
 * @author HD
 */

namespace SPL\API;

use SPL\Contracts\Bootable;

defined( 'ABSPATH' ) || exit;

final class API implements Bootable {

	/* ---------- CONFIGURATION ------------------------------------------ */

	/**
	 * Allowlisted REST API namespaces - accessible without authentication.
	 */
	private readonly array $allowedNamespaces;

	/**
	 * Namespaces blocked from direct browser access (GET via URL bar).
	 * Only blocks GET requests without AJAX headers.
	 * POST/PUT/DELETE requests are allowed (handled by endpoint's own nonce check).
	 */
	private readonly array $browserBlockedNamespaces;

	/**
	 * Blocked WP core endpoints - require admin capability to access.
	 * Format: 'endpoint_type' => 'required_capability'
	 */
	private readonly array $blockedEndpoints;

	/* ---------- PRIVATE ------------------------------------------ */

	private array $endpointInstances = [];

	/**
	 * Explicit theme-level endpoint class list.
	 * Module-owned endpoints are NOT listed here — they self-register.
	 *
	 * @return string[]
	 */
	private function endpointClasses(): array {
		return [];
	}

	public function __construct() {

		/**
		 * Allowlisted REST API namespaces for unauthenticated access.
		 * These namespaces are accessible without login.
		 */
		$this->allowedNamespaces = [
			REST_NAMESPACE,                // hd/v1 — also in browserBlockedNamespaces (Layer 1 handles it first)
			'hdat/v1',                     // HDAT public AI gateway (bearer-token authenticated by plugin)
			'contact-form-7/v1',           // Contact Form 7
			'rankmath/v1',                 // Rank Math SEO
			'wc/store/v1',                 // WooCommerce Store API (cart, checkout, blocks)
			'wc/store',                    // WooCommerce Store API (legacy format)
		];

		/**
		 * Namespaces blocked from direct browser GET access.
		 * Prevents viewing API responses by typing URL in browser.
		 * Note: contact-form-7/v1 is handled by its own plugin, not included here.
		 */
		$this->browserBlockedNamespaces = [
			REST_NAMESPACE,
			'wc/store/v1',                 // WooCommerce Store API (called via JS fetch only)
			'wc/store',                    // WooCommerce Store API (legacy)
		];

		/**
		 * Blocked WP core endpoints with required capabilities.
		 * Format: 'type' => 'capability'
		 * - 'post', 'page', 'attachment' → require 'edit_posts'
		 * - 'users' → require 'list_users' (admin only)
		 */
		$this->blockedEndpoints = [
			'post'       => 'edit_posts',
			'page'       => 'edit_posts',
			'attachment' => 'edit_posts',
			'users'      => 'list_users',
		];
	}

	/**
	 * Register WordPress hooks for REST API security and orchestration.
	 *
	 * @return void
	 */
	public function boot(): void {
		add_action( 'init', $this->initRestClasses( ... ) );

		// Define RESTAPI_URL on init — rest_url() requires rewrite rules to be loaded.
		add_action(
			'init',
			static function (): void {
				if ( ! defined( 'RESTAPI_URL' ) ) {
					define( 'RESTAPI_URL', esc_url_raw( rest_url( REST_NAMESPACE . '/' ) ) );
				}
			}
		);

		add_action( 'rest_api_init', $this->registerEndpoints( ... ) );

		// Main REST API access control (handles authentication + capability check)
		add_filter( 'rest_authentication_errors', $this->restrictRestApi( ... ), 99 );

		// Hide blocked endpoints from REST discovery (security through obscurity)
		add_filter( 'rest_endpoints', $this->filterRestEndpoints( ... ) );
	}

	/* ---------- ENDPOINT ORCHESTRATION -------------------------------- */

	/**
	 * Initialize REST endpoint classes from the explicit registry.
	 */
	private function initRestClasses(): void {
		foreach ( $this->endpointClasses() as $className ) {
			if ( class_exists( $className ) && is_subclass_of( $className, \WP_REST_Controller::class ) ) {
				$this->endpointInstances[] = new $className();
			}
		}
	}

	/**
	 * Register routes for all theme-level endpoint instances.
	 */
	private function registerEndpoints(): void {
		foreach ( $this->endpointInstances as $api ) {
			if ( method_exists( $api, 'register_routes' ) ) {
				$api->register_routes();
			}
		}
	}

	/* ---------- MAIN ACCESS CONTROL ----------------------------------- */

	/**
	 * Main REST API access restriction.
	 *
	 * Security layers:
	 *  1. Block direct browser GET access to protected namespaces
	 *  2. Allow whitelisted namespaces (allowedNamespaces) - public access
	 *  3. Block ALL wp-json/* for guests (not logged in)
	 *  4. Require edit_posts for wp/v2 endpoints; check blockedEndpoints capabilities
	 *
	 * @param mixed $result Current authentication result.
	 *
	 * @return mixed
	 */
	public function restrictRestApi( mixed $result ): mixed {
		if ( ! empty( $result ) ) {
			return $result;
		}

		// Normalize REST route from both /wp-json/... and ?rest_route=/... formats.
		$restRoute = $this->extractRestRoute();
		if ( '' === $restRoute ) {
			return $result;
		}

		// Layer 1: Block direct browser GET access to protected namespaces
		foreach ( $this->browserBlockedNamespaces as $ns ) {
			if ( $this->routeMatchesNamespace( $restRoute, $ns ) ) {
				if ( $this->isDirectBrowserAccess() ) {
					return $this->createRestError(
						'rest_direct_access_forbidden',
						__( 'Direct browser access not allowed. Use AJAX to call this API.', 'SPL' )
					);
				}

				// Not direct browser access, allow this request
				return $result;
			}
		}

		// Layer 2: Allow whitelisted namespaces (contact-form-7, api/v1, etc.)
		foreach ( $this->allowedNamespaces as $ns ) {
			if ( $this->routeMatchesNamespace( $restRoute, $ns ) ) {
				return $result;
			}
		}

		// Layer 3: Block ALL REST endpoints for guests (not logged in)
		if ( ! is_user_logged_in() ) {
			return $this->createRestError( 'rest_not_logged_in', __( 'Authentication required.', 'SPL' ) );
		}

		// Layer 4: Require edit_posts for ALL wp/v2 endpoints.
		// Subscriber/Customer don't need REST — they use the frontend.
		if ( str_starts_with( $restRoute, '/wp/v2' ) ) {
			if ( ! current_user_can( 'edit_posts' ) ) {
				// Exception: /users/me is allowed for any logged-in user.
				if ( ! preg_match( '#^/wp/v2/users/me\b#', $restRoute ) ) {
					return $this->createRestError( 'rest_forbidden', __( 'Insufficient permissions.', 'SPL' ) );
				}

				return $result;
			}

			// Editor+ → check specific blocked endpoints (users listing, etc.)
			$error = $this->checkBlockedEndpointAccess( $restRoute );
			if ( $error instanceof \WP_Error ) {
				return $error;
			}
		}

		return $result;
	}

	/**
	 * Check if request is a direct browser access (typing URL in browser).
	 *
	 * Direct browser access characteristics:
	 *  - GET request method
	 *  - No X-Requested-With header (AJAX)
	 *  - Accept header contains text/html (browser default)
	 *
	 * @return bool True if direct browser access, false if AJAX/programmatic.
	 */
	private function isDirectBrowserAccess(): bool {
		// Only check GET requests - POST/PUT/DELETE are likely from forms/AJAX
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		if ( $method !== 'GET' ) {
			return false;
		}

		// Has X-Requested-With header = AJAX request
		$xRequestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
		if ( strtolower( $xRequestedWith ) === 'xmlhttprequest' ) {
			return false;
		}

		// Has X-WP-Nonce header = programmatic request
		$nonce = $_SERVER['HTTP_X_WP_NONCE'] ?? '';
		if ( ! empty( $nonce ) ) {
			return false;
		}

		// Accept header contains text/html = browser
		// Fetch/AJAX typically sends application/json or */*
		$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
		if ( str_contains( $accept, 'text/html' ) ) {
			return true;
		}

		// No clear indicators, allow by default (could be curl, Postman, etc.)
		return false;
	}

	/**
	 * Check if logged-in user has permission to access blocked endpoint.
	 *
	 * @param string $restRoute The request URI (REST route).
	 *
	 * @return \WP_Error|null WP_Error if blocked, null if allowed.
	 */
	private function checkBlockedEndpointAccess( string $restRoute ): ?\WP_Error {
		// Exception: /users/me is allowed for any logged-in user
		if ( preg_match( '#^/wp/v2/users/me\b#', $restRoute ) ) {
			return null;
		}

		foreach ( $this->blockedEndpoints as $type => $capability ) {
			// Get REST base for this type
			$restBase = $this->getRestBaseForType( $type );

			if ( ! $restBase ) {
				continue;
			}

			// Check if request matches this endpoint
			if ( str_starts_with( $restRoute, "/wp/v2/{$restBase}" ) ) {
				if ( ! current_user_can( $capability ) ) {
					return $this->createRestError(
						'rest_forbidden',
						/* translators: %s: capability name */
						sprintf( __( 'You need "%s" capability to access this endpoint.', 'SPL' ), $capability )
					);
				}

				// Found matching endpoint and user has permission
				return null;
			}
		}

		// No matching blocked endpoint found - allow by default
		return null;
	}

	/**
	 * Get REST base name for a given type.
	 *
	 * @param string $type Endpoint type (post type or 'users').
	 *
	 * @return string|null REST base or null if not found.
	 */
	private function getRestBaseForType( string $type ): ?string {
		if ( $type === 'users' ) {
			return 'users';
		}

		$obj = get_post_type_object( $type );

		if ( ! $obj ) {
			return null;
		}

		return $obj->rest_base ?: $obj->name;
	}

	/**
	 * Create a WP_Error for REST API responses.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param int    $status  HTTP status code.
	 *
	 * @return \WP_Error
	 */
	private function createRestError( string $code, string $message, int $status = 403 ): \WP_Error {
		return new \WP_Error( $code, $message, [ 'status' => $status ] );
	}

	/**
	 * Extract normalized REST route from the request.
	 * Handles both pretty (/wp-json/ns/...) and plain (?rest_route=/ns/...) permalink formats.
	 *
	 * @return string REST route path (e.g., '/hd/v1/submit') or empty string if not a REST request.
	 */
	private function extractRestRoute(): string {
		// Plain permalink: ?rest_route=/namespace/...
		if ( isset( $_GET['rest_route'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '/' . ltrim( sanitize_text_field( wp_unslash( $_GET['rest_route'] ) ), '/' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		// Pretty permalink: /wp-json/namespace/...
		$requestUri = $_SERVER['REQUEST_URI'] ?? '';
		if ( '' === $requestUri ) {
			return '';
		}

		$requestUri = sanitize_text_field( wp_unslash( $requestUri ) );
		$prefix     = '/' . rest_get_url_prefix() . '/';
		$pos        = strpos( $requestUri, $prefix );

		if ( false === $pos ) {
			// Check for exact /wp-json (no trailing slash)
			$prefixNoSlash = rtrim( $prefix, '/' );
			if ( str_contains( $requestUri, $prefixNoSlash ) ) {
				return '/';
			}

			return '';
		}

		$route = substr( $requestUri, $pos + strlen( $prefix ) - 1 );

		// Strip query string
		$qPos = strpos( $route, '?' );
		if ( false !== $qPos ) {
			$route = substr( $route, 0, $qPos );
		}

		return '/' . ltrim( $route, '/' );
	}

	/**
	 * Check if a normalized REST route matches a namespace with boundary awareness.
	 * Prevents substring collisions (e.g., 'hd/v1' matching 'hd/v10').
	 *
	 * @param string $restRoute The normalized REST route (e.g., '/hd/v1/submit').
	 * @param string $ns        The namespace to match (e.g., 'hd/v1').
	 *
	 * @return bool
	 */
	private function routeMatchesNamespace( string $restRoute, string $ns ): bool {
		$prefix = '/' . $ns;

		return $restRoute === $prefix
			|| str_starts_with( $restRoute, $prefix . '/' );
	}

	/* ---------- ENDPOINT FILTERING ------------------------------------ */

	/**
	 * Hide unwanted routes from REST discovery.
	 *
	 * @param array $endpoints All registered endpoints.
	 *
	 * @return array Filtered endpoints.
	 */
	public function filterRestEndpoints( array $endpoints ): array {
		// Hide root discovery endpoints for guests only.
		// Logged-in users need these for Gutenberg's entity config resolution.
		if ( ! is_user_logged_in() ) {
			unset( $endpoints['/'], $endpoints['/wp/v2'] );
		}

		// Hide blocked endpoints ONLY for users who lack the required capability.
		// Logged-in users with proper permissions (e.g., editors) need these endpoints
		// for the Gutenberg block editor to function — it relies on REST route discovery.
		foreach ( $this->blockedEndpoints as $type => $capability ) {
			if ( current_user_can( $capability ) ) {
				continue;
			}

			if ( $type === 'users' ) {
				// Note: /users/me is NOT hidden - it's allowed for logged-in users
				unset(
					$endpoints['/wp/v2/users'],
					$endpoints['/wp/v2/users/(?P<id>[\d]+)']
				);
			} else {
				$obj = get_post_type_object( $type );
				if ( ! $obj ) {
					continue;
				}

				$base = $obj->rest_base ?: $obj->name;
				unset(
					$endpoints[ "/wp/v2/{$base}" ],
					$endpoints[ "/wp/v2/{$base}/(?P<id>[\d]+)" ]
				);
			}
		}

		return $endpoints;
	}
}
