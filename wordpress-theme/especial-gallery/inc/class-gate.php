<?php
/**
 * The mailing-list gate.
 *
 * A port of the source project's middleware.ts. Every front-end request except
 * the gate itself, the legal pages, admin, feeds and anything with a file
 * extension requires a valid signed cookie; everyone else is sent to the gate,
 * which remembers where they were headed.
 *
 * THIS IS A MAILING-LIST CAPTURE, NOT ACCESS CONTROL. The crawler allowlist
 * below matches on User-Agent, which is trivially spoofable, and that is
 * deliberate: gating crawlers deletes organic traffic and breaks link unfurls,
 * which is the whole reason the editorial copy on the front page can rank at
 * all. Never put anything genuinely sensitive behind it.
 *
 * @package Especial_Gallery
 */

namespace Especial_Gallery;

defined( 'ABSPATH' ) || exit;

/**
 * Gate enforcement and cookie signing.
 */
class Gate {

	const COOKIE   = 'eg_ml_pass';
	const MAX_AGE  = 15552000; // 180 days, per the original spec.

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'enforce' ), 1 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Whether the gate is switched on at all.
	 *
	 * @return bool
	 */
	public static function enabled() {
		return (bool) get_theme_mod( 'eg_gate_enabled', true );
	}

	/**
	 * Runs the gate on every front-end request.
	 *
	 * @return void
	 */
	public function enforce() {
		if ( ! self::enabled() || is_admin() || wp_doing_ajax() || is_feed() || is_robots() ) {
			return;
		}

		// A logged-in editor should never be locked out of their own storefront.
		if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
			return;
		}

		if ( $this->is_exempt() ) {
			return;
		}

		// 1. A valid cookie goes straight through.
		if ( self::has_pass() ) {
			return;
		}

		// 2. ?eg_preview=<token> sets the cookie, strips the parameter and
		//    continues. For QA and client demos. An unset token disables the
		//    bypass entirely rather than allowing an empty match.
		$preview = get_theme_mod( 'eg_preview_token', '' );
		$offered = isset( $_GET['eg_preview'] ) ? sanitize_text_field( wp_unslash( $_GET['eg_preview'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $preview && $offered && hash_equals( (string) $preview, $offered ) ) {
			self::issue_pass();
			wp_safe_redirect( remove_query_arg( 'eg_preview' ) );
			exit;
		}

		// 3. Search and social crawlers pass. See the class docblock.
		if ( self::is_crawler() ) {
			return;
		}

		// 4. Everyone else goes to the gate, remembering where they were headed.
		$gate = eg_page_url( 'gate' );
		if ( ! $gate ) {
			return; // No gate page exists; failing open beats a redirect loop.
		}

		$requested = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';

		wp_safe_redirect( add_query_arg( 'eg_next', rawurlencode( eg_safe_next( $requested ) ), $gate ) );
		exit;
	}

	/**
	 * Pages that are always reachable, gate or no gate.
	 *
	 * The legal pages sit outside because a payment provider, an email footer or
	 * an app store review may link straight to them. The gate page itself is
	 * exempt for the obvious reason.
	 *
	 * @return bool
	 */
	private function is_exempt() {
		if ( is_404() ) {
			return true;
		}

		$exempt_pages = array( 'gate', 'privacy', 'terms', 'returns', 'contact' );

		foreach ( $exempt_pages as $key ) {
			$page_id = (int) eg_page_id( $key );
			if ( $page_id && is_page( $page_id ) ) {
				return true;
			}
		}

		// The site's own privacy policy, wherever it has been pointed.
		$privacy_id = (int) get_option( 'wp_page_for_privacy_policy' );
		if ( $privacy_id && is_page( $privacy_id ) ) {
			return true;
		}

		/**
		 * Filters whether the current request bypasses the gate.
		 *
		 * @param bool $exempt Whether to let the request through.
		 */
		return (bool) apply_filters( 'eg_gate_exempt', false );
	}

	/**
	 * Adds a class so the gate page can drop the store chrome.
	 *
	 * @param array $classes Existing classes.
	 * @return array
	 */
	public function body_class( $classes ) {
		if ( self::enabled() && ! self::has_pass() ) {
			$classes[] = 'eg-gated';
		}
		return $classes;
	}

	/* ── cookie signing ──────────────────────────────────────────────────── */

	/**
	 * The signing key.
	 *
	 * A missing key does not break the gate. It falls back to a per-site value
	 * derived from WordPress's own salts, which every installation has and which
	 * is not in this source file. That is a deliberate trade: this cookie is a
	 * mailing-list capture whose crawler allowlist is already spoofable, so a
	 * forgeable cookie is a far smaller problem than a storefront whose front
	 * door 500s at every customer because one constant is missing.
	 *
	 * @return string
	 */
	private static function secret() {
		if ( defined( 'EG_GATE_SECRET' ) && strlen( (string) EG_GATE_SECRET ) >= 16 ) {
			return (string) EG_GATE_SECRET;
		}

		// wp_salt() is unique per installation and never shipped in the theme.
		return wp_salt( 'eg_gate' );
	}

	/**
	 * Signs a payload.
	 *
	 * @param string $payload Payload to sign.
	 * @return string Hex signature.
	 */
	private static function sign( $payload ) {
		return hash_hmac( 'sha256', $payload, self::secret() );
	}

	/**
	 * Mints a cookie value: `<issuedAt>.<signature>`.
	 *
	 * The payload is only a timestamp. The cookie asserts "this browser passed
	 * the gate" and nothing more, so there is no personal data in it.
	 *
	 * @return string
	 */
	public static function token() {
		$issued = (string) time();
		return $issued . '.' . self::sign( $issued );
	}

	/**
	 * Verifies a cookie value.
	 *
	 * @param string $token Cookie value.
	 * @return bool
	 */
	public static function verify( $token ) {
		if ( ! is_string( $token ) || '' === $token ) {
			return false;
		}

		$split = strrpos( $token, '.' );
		if ( false === $split || $split < 1 ) {
			return false;
		}

		$issued   = substr( $token, 0, $split );
		$provided = substr( $token, $split + 1 );

		if ( ! ctype_digit( $issued ) ) {
			return false;
		}

		// Reject an expired token even if the browser kept sending it.
		$age = time() - (int) $issued;
		if ( $age < 0 || $age > self::MAX_AGE ) {
			return false;
		}

		// hash_equals is constant time, so the signature cannot be recovered
		// one byte at a time by measuring how long the comparison took.
		return hash_equals( self::sign( $issued ), $provided );
	}

	/**
	 * Whether this browser currently holds a valid pass.
	 *
	 * @return bool
	 */
	public static function has_pass() {
		if ( ! isset( $_COOKIE[ self::COOKIE ] ) ) {
			return false;
		}

		return self::verify( sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) );
	}

	/**
	 * Sets the pass cookie.
	 *
	 * @return void
	 */
	public static function issue_pass() {
		$token = self::token();

		setcookie(
			self::COOKIE,
			$token,
			array(
				'expires'  => time() + self::MAX_AGE,
				'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);

		// So code later in this same request sees the pass too.
		$_COOKIE[ self::COOKIE ] = $token;
	}

	/* ── crawler allowlist ───────────────────────────────────────────────── */

	/**
	 * Whether the request looks like a search or social crawler.
	 *
	 * Matching on User-Agent is spoofable. That is the point: see the class
	 * docblock. Nothing sensitive may sit behind this.
	 *
	 * @return bool
	 */
	public static function is_crawler() {
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) )
			: '';

		if ( '' === $agent ) {
			return false;
		}

		$allow = array(
			// Search.
			'googlebot',
			'bingbot',
			'slurp',
			'duckduckbot',
			'baiduspider',
			'yandexbot',
			'applebot',
			'petalbot',
			// Social unfurlers — gating these breaks every shared link's preview.
			'facebookexternalhit',
			'facebot',
			'twitterbot',
			'linkedinbot',
			'pinterest',
			'slackbot',
			'discordbot',
			'whatsapp',
			'telegrambot',
			'redditbot',
			'embedly',
			'skypeuripreview',
			// Tooling that a shop owner will legitimately point at the site.
			'chrome-lighthouse',
			'gtmetrix',
			'ahrefsbot',
			'semrushbot',
		);

		foreach ( $allow as $needle ) {
			if ( false !== strpos( $agent, $needle ) ) {
				return true;
			}
		}

		return false;
	}
}
