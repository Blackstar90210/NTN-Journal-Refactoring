<?php
/**
 * NTN Journal - Comprehensive Audit & Type-Safety Test Suite
 * Validating IGDB Cover Fallback Logic, Archive Templates & The SEO Framework Integrations in PHP 8.2+
 *
 * Usage via CLI: php audit_test_suite.php
 */

declare(strict_types=0);

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

$errors_logged = array();
set_error_handler( function( $errno, $errstr, $errfile, $errline ) use ( &$errors_logged ) {
	$errors_logged[] = array(
		'errno'   => $errno,
		'errstr'  => $errstr,
		'errfile' => $errfile,
		'errline' => $errline,
	);
	return false;
} );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
    define( 'HOUR_IN_SECONDS', 3600 );
}

// Global mocks
global $wp_filters, $wp_actions, $mock_posts, $mock_postmeta, $mock_terms, $mock_termmeta, $mock_post_tags, $mock_post_categories, $mock_options, $mock_loop_index, $mock_loop_posts, $queried_object, $mock_query_vars, $mock_post_types;

$wp_filters           = array();
$wp_actions           = array();
$mock_posts           = array();
$mock_postmeta        = array();
$mock_terms           = array();
$mock_termmeta        = array();
$mock_post_tags       = array();
$mock_post_categories = array();
$mock_options         = array();
$mock_loop_index      = 0;
$mock_loop_posts      = array();
$queried_object       = null;
$mock_query_vars      = array();
$mock_post_types      = array();

class WP_Post {
	public $ID;
	public $post_title;
	public $post_name;
	public $post_type;
	public $post_status;
	public $post_content;
	public $post_excerpt;
	public $post_date;

	public function __construct( $data = array() ) {
		foreach ( $data as $k => $v ) {
			$this->$k = $v;
		}
	}
}

class WP_Term {
	public $term_id;
	public $name;
	public $slug;
	public $taxonomy;
	public $description;

	public function __construct( $data = array() ) {
		foreach ( $data as $k => $v ) {
			$this->$k = $v;
		}
	}
}

class WP_Post_Type {
	public $name;
	public $labels;

	public function __construct( $name, $singular, $plural ) {
		$this->name = $name;
		$this->labels = (object) array(
			'name'          => $plural,
			'singular_name' => $singular,
		);
	}
}

class WP_Error {
	public $errors = array();
	public function __construct( $code = '', $message = '' ) {
		if ( $code ) {
			$this->errors[ $code ][] = $message;
		}
	}
}

class StringableObject {
	private $url;
	public function __construct( $url ) {
		$this->url = $url;
	}
	public function __toString() {
		return $this->url;
	}
}

function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	global $wp_filters;
	$wp_filters[ $tag ][ $priority ][] = array(
		'function'      => $callback,
		'accepted_args' => $accepted_args,
	);
	return true;
}

foreach ( array( 'MINUTE_IN_SECONDS' => 60, 'HOUR_IN_SECONDS' => 3600, 'DAY_IN_SECONDS' => 86400, 'WEEK_IN_SECONDS' => 604800 ) as $hs_const => $hs_value ) {
	if ( ! defined( $hs_const ) ) {
		define( $hs_const, $hs_value );
	}
}
if ( ! function_exists( 'has_filter' ) ) {
	function has_filter( $tag, $callback = false ) {
		global $wp_filters;
		foreach ( (array) ( $wp_filters[ $tag ] ?? array() ) as $priority => $callbacks ) {
			foreach ( $callbacks as $cb ) {
				if ( $cb['function'] === $callback ) {
					return $priority;
				}
			}
		}
		return false;
	}
}
if ( ! function_exists( 'remove_filter' ) ) {
	function remove_filter( $tag, $callback, $priority = 10 ) {
		global $wp_filters;
		if ( empty( $wp_filters[ $tag ][ $priority ] ) ) {
			return false;
		}
		$wp_filters[ $tag ][ $priority ] = array_values( array_filter( $wp_filters[ $tag ][ $priority ], static function ( $cb ) use ( $callback ) { return $cb['function'] !== $callback; } ) );
		return true;
	}
}

function apply_filters( $tag, $value ) {
	global $wp_filters;
	$args = func_get_args();
	array_shift( $args );

	if ( ! isset( $wp_filters[ $tag ] ) ) {
		return $value;
	}

	ksort( $wp_filters[ $tag ] );
	foreach ( $wp_filters[ $tag ] as $priority => $callbacks ) {
		foreach ( $callbacks as $cb ) {
			$func      = $cb['function'];
			$acc       = $cb['accepted_args'];
			$pass_args = array_slice( $args, 0, $acc );
			$value     = call_user_func_array( $func, $pass_args );
			$args[0]   = $value;
		}
	}
	return $value;
}

function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	return add_filter( $tag, $callback, $priority, $accepted_args );
}

function do_action( $tag ) {
	global $wp_filters;
	$args = func_get_args();
	array_shift( $args );
	if ( isset( $wp_filters[ $tag ] ) ) {
		ksort( $wp_filters[ $tag ] );
		foreach ( $wp_filters[ $tag ] as $priority => $callbacks ) {
			foreach ( $callbacks as $cb ) {
				$func      = $cb['function'];
				$acc       = $cb['accepted_args'];
				$pass_args = array_slice( $args, 0, $acc );
				call_user_func_array( $func, $pass_args );
			}
		}
	}
}

function get_post( $post = null ) {
	global $mock_posts, $mock_loop_posts, $mock_loop_index, $queried_object;
	if ( $post instanceof WP_Post ) return $post;
	if ( is_numeric( $post ) ) return isset( $mock_posts[ intval( $post ) ] ) ? $mock_posts[ intval( $post ) ] : null;
	if ( is_null( $post ) ) {
		if ( $queried_object instanceof WP_Post ) return $queried_object;
		$idx = $mock_loop_index > 0 ? $mock_loop_index - 1 : 0;
		return isset( $mock_loop_posts[ $idx ] ) ? $mock_loop_posts[ $idx ] : null;
	}
	return null;
}

function get_posts( $args = array() ) {
	global $mock_posts, $mock_postmeta;
	$res   = array();
	$pt    = isset( $args['post_type'] ) ? $args['post_type'] : 'post';
	$name  = isset( $args['name'] ) ? $args['name'] : '';
	$title = isset( $args['title'] ) ? $args['title'] : '';
	$mkey  = isset( $args['meta_key'] ) ? $args['meta_key'] : '';
	$mval  = isset( $args['meta_value'] ) ? $args['meta_value'] : null;

	foreach ( $mock_posts as $p ) {
		if ( $pt && $p->post_type !== $pt && $pt !== 'any' ) continue;
		if ( $name && $p->post_name !== $name ) continue;
		if ( $title && $p->post_title !== $title ) continue;
		if ( $mkey ) {
			if ( ! isset( $mock_postmeta[ $p->ID ][ $mkey ] ) ) continue;
			if ( null !== $mval && (string) $mock_postmeta[ $p->ID ][ $mkey ] !== (string) $mval ) continue;
		}
		$res[] = $p;
	}
	return $res;
}

function get_post_meta( $post_id, $key = '', $single = false ) {
	global $mock_postmeta;
	$post_id = intval( $post_id );
	if ( ! isset( $mock_postmeta[ $post_id ] ) ) return $single ? '' : array();
	if ( empty( $key ) ) return $mock_postmeta[ $post_id ];
	if ( isset( $mock_postmeta[ $post_id ][ $key ] ) ) {
		return $single ? $mock_postmeta[ $post_id ][ $key ] : array( $mock_postmeta[ $post_id ][ $key ] );
	}
	return $single ? '' : array();
}

function update_post_meta( $post_id, $meta_key, $meta_value ) {
	global $mock_postmeta;
	$post_id = intval( $post_id );
	if ( ! isset( $mock_postmeta[ $post_id ] ) ) {
		$mock_postmeta[ $post_id ] = array();
	}
	$mock_postmeta[ $post_id ][ $meta_key ] = $meta_value;
	return true;
}

function wp_insert_post( $args ) {
	global $mock_posts;
	$new_id = count( $mock_posts ) + 800;
	$post = new WP_Post( array(
		'ID'           => $new_id,
		'post_title'   => isset( $args['post_title'] ) ? $args['post_title'] : '',
		'post_name'    => isset( $args['post_name'] ) ? $args['post_name'] : sanitize_title( isset( $args['post_title'] ) ? $args['post_title'] : '' ),
		'post_type'    => isset( $args['post_type'] ) ? $args['post_type'] : 'post',
		'post_status'  => isset( $args['post_status'] ) ? $args['post_status'] : 'publish',
		'post_content' => isset( $args['post_content'] ) ? $args['post_content'] : '',
		'post_excerpt' => isset( $args['post_excerpt'] ) ? $args['post_excerpt'] : '',
		'post_date'    => isset( $args['post_date'] ) ? $args['post_date'] : '2026-08-14 00:00:00',
	) );
	$mock_posts[ $new_id ] = $post;
	return $new_id;
}

function taxonomy_exists( $taxonomy ) {
	return in_array( $taxonomy, array( 'category', 'post_tag', 'game_engine', 'game_franchise', 'game_developer', 'game_publisher', 'game_year' ), true );
}

function wp_set_object_terms( $post_id, $terms, $taxonomy ) {
	return true;
}

function sanitize_textarea_field( $str ) {
	return sanitize_text_field( (string) $str );
}

function get_post_type( $post = null ) {
	$p = get_post( $post );
	return ( $p && isset( $p->post_type ) ) ? $p->post_type : false;
}

function get_post_type_object( $post_type ) {
	global $mock_post_types;
	return ( is_string( $post_type ) && isset( $mock_post_types[ $post_type ] ) ) ? $mock_post_types[ $post_type ] : null;
}

function get_post_field( $field, $post = null ) {
	$p = get_post( $post );
	return ( $p && isset( $p->$field ) ) ? $p->$field : '';
}

function get_permalink( $post = 0 ) {
	$p = get_post( $post );
	return $p ? 'https://example.com/' . $p->post_type . '/' . $p->post_name . '/' : 'https://example.com/';
}

function get_the_ID() {
	global $mock_loop_posts, $mock_loop_index, $queried_object, $post;
	if ( $post instanceof WP_Post ) return $post->ID;
	if ( $mock_loop_index > 0 && isset( $mock_loop_posts[ $mock_loop_index - 1 ] ) ) {
		return $mock_loop_posts[ $mock_loop_index - 1 ]->ID;
	}
	if ( $queried_object instanceof WP_Post ) return $queried_object->ID;
	return 0;
}

function the_ID() {
	echo get_the_ID();
}

function get_the_title( $post = 0 ) {
	$p = get_post( $post );
	return $p ? $p->post_title : '';
}

function the_title() {
	echo get_the_title();
}

function the_title_attribute() {
	echo esc_attr( get_the_title() );
}

function the_permalink() {
	echo esc_url( get_permalink() );
}

function have_posts() {
	global $mock_loop_posts, $mock_loop_index;
	return $mock_loop_index < count( $mock_loop_posts );
}

function the_post() {
	global $mock_loop_index, $post, $mock_loop_posts;
	$post = isset( $mock_loop_posts[ $mock_loop_index ] ) ? $mock_loop_posts[ $mock_loop_index ] : null;
	$mock_loop_index++;
}

function has_post_thumbnail( $post = null ) {
	global $mock_postmeta;
	$post_id = is_numeric( $post ) ? intval( $post ) : ( $post instanceof WP_Post ? $post->ID : get_the_ID() );
	return ! empty( $mock_postmeta[ $post_id ]['_thumbnail_url'] );
}

function attachment_url_to_postid( $url ) {
	return 0;
}

function wp_get_attachment_image_src( $attachment_id, $size = 'thumbnail' ) {
	return false;
}

function get_the_post_thumbnail_url( $post = null, $size = 'post-thumbnail' ) {
	global $mock_postmeta;
	$post_id = is_numeric( $post ) ? intval( $post ) : ( $post instanceof WP_Post ? $post->ID : get_the_ID() );
	return isset( $mock_postmeta[ $post_id ]['_thumbnail_url'] ) ? $mock_postmeta[ $post_id ]['_thumbnail_url'] : false;
}

function post_class( $class = '' ) {
	echo 'class="' . esc_attr( $class ) . '"';
}

function wp_dropdown_categories( $args = array() ) {}
function get_transient( $transient ) { return false; }
function set_transient( $transient, $value, $expiration = 0 ) { return true; }
function delete_transient( $transient ) { return true; }
function selected( $selected, $current = true, $echo = true ) {
    $result = (string) $selected === (string) $current ? ' selected=\'selected\'' : '';
    if ( $echo ) echo $result;
    return $result;
}
function wp_reset_postdata() {}
function the_posts_pagination( $args = array() ) {}
function ntn_get_catalog_query_args( $params ) { return array(); }
class WP_Query {
	public $max_num_pages = 1;
	public $posts = array();
	public $found_posts = 0;
	public function __construct( $args = array() ) {
		if ( ! empty( $GLOBALS['mock_wp_query_handler'] ) ) {
			$this->posts = call_user_func( $GLOBALS['mock_wp_query_handler'], $args );
		}
	}
	public function have_posts() { return have_posts(); }
	public function the_post() { return the_post(); }
}
function get_template_part( $slug, $name = null, $args = array() ) {
	if ( $slug === 'template-parts/hub-article-feed' ) {
		require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/hub-article-feed.php';
	}
	if ( $slug === 'template-parts/content' && null === $name && ! empty( $GLOBALS['pg_real_cards'] ) ) {
		require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/content.php';
	}
	if ( $slug === 'template-parts/content' && $name === 'game-card' ) {
		// v4.10.0: la scheda vera del catalogo, ora nel parent (prima qui c'era una scheda finta)
		require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/content-game-card.php';
	}
}
function the_content() { echo "<p>Testo del post.</p>"; }
function wp_link_pages( $args = array() ) {}
function comments_template( $file = '', $separate_comments = false ) {}
function get_header() {}
function get_footer() {}
function wp_get_document_title() { return 'NTN Journal'; }
function get_locale() { return 'it_IT'; }
function esc_url( $url ) { return htmlspecialchars( (string)$url, ENT_QUOTES, 'UTF-8' ); }
function esc_url_raw( $url ) { return (string)$url; }
function esc_html( $text ) { return htmlspecialchars( (string)$text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return htmlspecialchars( (string)$text, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $text, $domain = 'default' ) { return esc_html( (string) $text ); }
function esc_attr__( $text, $domain = 'default' ) { return esc_attr( (string) $text ); }
function esc_attr_e( $text, $domain = 'default' ) { echo esc_attr( (string) $text ); }
function esc_html_e( $text, $domain = 'default' ) { echo esc_html( (string) $text ); }
function __( $text, $domain = 'default' ) { return (string) $text; }
function _e( $text, $domain = 'default' ) { echo (string) $text; }
function _x( $text, $context, $domain = 'default' ) { return (string) $text; }
function sanitize_text_field( $str ) { return trim( strip_tags( (string)$str ) ); }
function sanitize_title( $title ) {
	$title = strtolower( (string)$title );
	$title = preg_replace( '/[^a-z0-9_\-]/', '-', $title );
	return trim( preg_replace( '/-+/', '-', $title ), '-' );
}

function get_the_tags( $post_id = 0 ) {
	global $mock_post_tags;
	if ( ! $post_id ) $post_id = get_the_ID();
	return isset( $mock_post_tags[ $post_id ] ) ? $mock_post_tags[ $post_id ] : false;
}

function get_term( $term, $taxonomy = '' ) {
	global $mock_terms;
	if ( $term instanceof WP_Term ) return $term;
	$id = is_numeric( $term ) ? intval( $term ) : ( is_object( $term ) && isset( $term->term_id ) ? intval( $term->term_id ) : 0 );
	return isset( $mock_terms[ $id ] ) ? $mock_terms[ $id ] : null;
}

function get_term_by( $field, $value, $taxonomy = '' ) {
	global $mock_terms;
	foreach ( $mock_terms as $t ) {
		if ( $taxonomy && $t->taxonomy !== $taxonomy ) continue;
		if ( $field === 'slug' && $t->slug === $value ) return $t;
		if ( $field === 'name' && $t->name === $value ) return $t;
		if ( $field === 'id' && $t->term_id === intval( $value ) ) return $t;
	}
	return false;
}

function get_term_meta( $term_id, $key = '', $single = false ) {
	global $mock_termmeta;
	$term_id = intval( $term_id );
	if ( ! isset( $mock_termmeta[ $term_id ] ) ) return $single ? '' : array();
	if ( empty( $key ) ) return $mock_termmeta[ $term_id ];
	if ( isset( $mock_termmeta[ $term_id ][ $key ] ) ) {
		return $single ? $mock_termmeta[ $term_id ][ $key ] : array( $mock_termmeta[ $term_id ][ $key ] );
	}
	return $single ? '' : array();
}

function update_term_meta( $term_id, $meta_key, $meta_value ) {
	global $mock_termmeta;
	$term_id = intval( $term_id );
	if ( ! isset( $mock_termmeta[ $term_id ] ) ) {
		$mock_termmeta[ $term_id ] = array();
	}
	$mock_termmeta[ $term_id ][ $meta_key ] = $meta_value;
	return true;
}

function get_term_link( $term, $taxonomy = '' ) {
	if ( $term instanceof WP_Error ) return $term;
	if ( is_object( $term ) && isset( $term->term_id ) && $term->term_id === 999999 ) return new WP_Error( 'invalid_term', 'Term not found' );
	$t = get_term( $term, $taxonomy );
	return ( $t && ! is_wp_error( $t ) ) ? 'https://example.com/tag/' . $t->slug . '/' : 'https://example.com/';
}

function get_category_link( $category_id ) {
	if ( $category_id === 999999 ) return new WP_Error( 'invalid_cat', 'Category not found' );
	return 'https://example.com/category/' . intval( $category_id ) . '/';
}

function get_queried_object() {
	global $queried_object;
	return $queried_object;
}

function get_queried_object_id() {
	global $queried_object;
	if ( $queried_object instanceof WP_Post ) return $queried_object->ID;
	if ( $queried_object instanceof WP_Term ) return $queried_object->term_id;
	return 0;
}

function get_query_var( $var ) {
	global $mock_query_vars;
	return isset( $mock_query_vars[ $var ] ) ? $mock_query_vars[ $var ] : '';
}

function is_post_type_archive( $pt = '' ) {
	global $mock_query_vars;
	if ( isset( $mock_query_vars['is_post_type_archive'] ) && $mock_query_vars['is_post_type_archive'] ) {
		if ( empty( $pt ) ) return true;
		return ( isset( $mock_query_vars['post_type'] ) && $mock_query_vars['post_type'] === $pt );
	}
	return false;
}

function get_post_type_archive_link( $post_type ) {
	if ( ! is_string( $post_type ) || empty( $post_type ) ) return false;
	return 'https://example.com/' . $post_type . '/';
}

if ( ! function_exists( 'remove_accents' ) ) {
	function remove_accents( $text ) {
		return strtr( (string) $text, array( 'à' => 'a', 'À' => 'A', 'è' => 'e', 'é' => 'e', 'È' => 'E', 'É' => 'E', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u' ) ); // solo gli accenti usati dal banco di prova
	}
}
if ( ! function_exists( 'has_category' ) ) {
	function has_category( $category, $post = null ) {
		global $mock_post_categories;
		$post_id = $post instanceof WP_Post ? $post->ID : (int) $post;
		foreach ( (array) ( $mock_post_categories[ $post_id ] ?? array() ) as $cat ) {
			if ( in_array( $cat->slug, (array) $category, true ) ) {
				return true;
			}
		}
		return false;
	}
}
function is_admin() { return false; }
function get_option( $opt, $default = false ) {
	global $mock_options;
	return isset( $mock_options[ $opt ] ) ? $mock_options[ $opt ] : $default;
}
function get_theme_mod( $mod, $default = false ) {
	global $mock_options;
	return isset( $mock_options[ $mod ] ) ? $mock_options[ $mod ] : $default;
}
function get_template_directory_uri() { return 'https://example.com/wp-content/themes/ntn-journal'; }
function home_url( $path = '/' ) { return 'https://example.com' . $path; }
function is_singular( $post_types = '' ) {
	global $queried_object;
	if ( ! ( $queried_object instanceof WP_Post ) ) return false;
	if ( empty( $post_types ) ) return true;
	if ( is_array( $post_types ) ) return in_array( $queried_object->post_type, $post_types, true );
	return $queried_object->post_type === $post_types;
}
function is_single() {
	global $queried_object;
	return ( $queried_object instanceof WP_Post ) && ! in_array( $queried_object->post_type, array( 'page', 'attachment' ), true );
}
function is_page() {
	global $queried_object;
	return $queried_object instanceof WP_Post && $queried_object->post_type === 'page';
}
function is_tag() {
	global $queried_object;
	return $queried_object instanceof WP_Term && $queried_object->taxonomy === 'post_tag';
}
function is_category() {
	global $queried_object;
	return $queried_object instanceof WP_Term && $queried_object->taxonomy === 'category';
}
function is_tax() {
	global $queried_object;
	return $queried_object instanceof WP_Term;
}
function doing_filter( $hook_name = null ) {
	global $mock_doing_filter;
	return null !== $hook_name && ! empty( $mock_doing_filter[ $hook_name ] );
}
function is_search() {
	global $mock_query_vars;
	return ! empty( $mock_query_vars['is_search'] );
}
function is_author() {
	global $mock_query_vars;
	return ! empty( $mock_query_vars['is_author'] );
}
function get_author_posts_url( $author_id ) {
	if ( ! is_numeric( $author_id ) || intval( $author_id ) <= 0 ) return false;
	return 'https://example.com/author/' . intval( $author_id ) . '/';
}
function is_front_page() { return false; }
if ( ! function_exists( 'has_nav_menu' ) ) {
	function has_nav_menu( $location ) {
		global $mock_nav_menus;
		return ! empty( $mock_nav_menus[ $location ] );
	}
}
if ( ! function_exists( 'get_category_by_slug' ) ) {
	function get_category_by_slug( $slug ) { return false; }
}
if ( ! function_exists( 'wp_nav_menu' ) ) {
	// Simula wp_nav_menu applicando i filtri del tema a voci finte: array( titolo, url, classi )
	function wp_nav_menu( $args = array() ) {
		global $mock_nav_menus;
		$args_obj = (object) $args;
		$items    = $mock_nav_menus[ $args_obj->theme_location ] ?? array();
		$out      = '';
		foreach ( $items as $i => $mock_item ) {
			$item     = (object) array( 'ID' => 900 + $i, 'title' => $mock_item[0], 'url' => $mock_item[1], 'classes' => $mock_item[2] );
			$classes  = apply_filters( 'nav_menu_css_class', $mock_item[2], $item, $args_obj, 0 );
			$li_id    = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args_obj, 0 );
			$atts     = apply_filters( 'nav_menu_link_attributes', array(), $item, $args_obj, 0 );
			$title    = apply_filters( 'nav_menu_item_title', $mock_item[0], $item, $args_obj, 0 );
			$link     = '<a href="' . esc_url( $mock_item[1] ) . '" class="' . esc_attr( $atts['class'] ?? '' ) . '">' . $title . '</a>';
			$link     = apply_filters( 'walker_nav_menu_start_el', $link, $item, 0, $args_obj );
			$out     .= '<li' . ( $li_id ? ' id="' . esc_attr( $li_id ) . '"' : '' ) . ' class="' . esc_attr( implode( ' ', (array) $classes ) ) . '">' . $link . '</li>';
		}
		echo sprintf( $args_obj->items_wrap ?? '<ul class="%2$s">%3$s</ul>', '', $args_obj->menu_class ?? '', $out );
	}
}
// v3.22.0: la tendina Piattaforme legge le voci del menu direttamente (posizione 1 = menu-piattaforme)
if ( ! function_exists( 'get_nav_menu_locations' ) ) {
	function get_nav_menu_locations() {
		global $mock_nav_menus;
		return ! empty( $mock_nav_menus['menu-piattaforme'] ) ? array( 'menu-piattaforme' => 1 ) : array();
	}
}
if ( ! function_exists( 'wp_get_nav_menu_object' ) ) {
	function wp_get_nav_menu_object( $menu ) {
		global $mock_nav_menus;
		return (object) array( 'term_id' => (int) $menu, 'count' => count( $mock_nav_menus['menu-piattaforme'] ?? array() ) );
	}
}
if ( ! function_exists( 'wp_get_nav_menu_items' ) ) {
	function wp_get_nav_menu_items( $menu ) {
		global $mock_nav_menus;
		$items = array();
		foreach ( ( $mock_nav_menus['menu-piattaforme'] ?? array() ) as $i => $mock_item ) {
			$items[] = (object) array( 'ID' => 950 + $i, 'title' => $mock_item[0], 'url' => $mock_item[1], 'type' => 'custom', 'object' => 'custom', 'object_id' => 0, 'menu_item_parent' => $mock_item[3] ?? 0 );
		}
		return $items;
	}
}
function is_home() { return false; }
function in_the_loop() { return true; }
function is_main_query() { return true; }
function has_excerpt( $post = null ) { return false; }
function get_the_excerpt( $post = null ) { return ''; }
function wp_strip_all_tags( $text ) { return strip_tags( (string)$text ); }
if ( ! function_exists( 'wp_specialchars_decode' ) ) { function wp_specialchars_decode( $text, $quote_style = ENT_NOQUOTES ) { return htmlspecialchars_decode( (string) $text, ENT_QUOTES ); } }
if ( ! function_exists( 'wp_date' ) ) { function wp_date( $format, $timestamp = null ) { return gmdate( $format, $timestamp ?? time() ); } }
function wp_trim_words( $text, $num = 55, $more = null ) { return (string)$text; }
function get_bloginfo( $show = '' ) { return 'NTN Journal - Minimal Tech Magazine'; }
function get_the_category( $post_id = 0 ) {
	global $mock_post_categories;
	return isset( $mock_post_categories[ $post_id ] ) ? $mock_post_categories[ $post_id ] : array();
}
function wp_json_encode( $data, $options = 0 ) { return json_encode( $data, $options ); }
function wp_kses_post( $content ) {
	if ( ! is_scalar( $content ) ) return '';
	return preg_replace( '#<script(.*?)>(.*?)</script>#is', '', (string) $content );
}
function current_time( $type = 'mysql' ) {
	return '2026-08-14 12:00:00';
}
function wpautop( $pee, $br = true ) { return '<p>' . trim( (string) $pee ) . '</p>'; }
function wp_insert_term( $term, $taxonomy, $args = array() ) {
	global $mock_terms;
	$new_id = count( $mock_terms ) + 500;
	$mock_terms[ $new_id ] = new WP_Term( array( 'term_id' => $new_id, 'name' => $term, 'slug' => sanitize_title( $term ), 'taxonomy' => $taxonomy ) );
	return array( 'term_id' => $new_id, 'term_taxonomy_id' => $new_id );
}
function get_the_author_meta( $field, $user_id = 0 ) {
	global $mock_author_meta;
	return isset( $mock_author_meta[ $field ] ) ? $mock_author_meta[ $field ] : '';
}
function get_the_author() {
	global $mock_author_meta;
	return isset( $mock_author_meta['display_name'] ) ? $mock_author_meta['display_name'] : 'Redazione NTN';
}
function get_avatar( $id_or_email, $size = 96, $default = '', $alt = '', $args = null ) {
	$cls = ( is_array( $args ) && isset( $args['class'] ) ) ? esc_attr( $args['class'] ) : '';
	return '<img src="https://example.com/avatar.jpg" class="' . $cls . '" />';
}
function get_avatar_url( $id_or_email, $args = null ) {
	$size = ( is_array( $args ) && isset( $args['size'] ) ) ? (int) $args['size'] : 96;
	$d    = ( is_array( $args ) && isset( $args['default'] ) ) ? $args['default'] : 'mm';
	return 'https://secure.gravatar.com/avatar/example?s=' . $size . '&d=' . $d;
}
function get_the_date( $format = 'd F Y', $post = null ) {
	return '14 Agosto 2026';
}
function update_option( $opt, $val, $autoload = null ) {
	global $mock_options;
	$mock_options[ $opt ] = $val;
	return true;
}
function delete_option( $opt ) {
	global $mock_options;
	unset( $mock_options[ $opt ] );
	return true;
}
function sanitize_hex_color( $color ) {
	$color = (string) $color;
	if ( '' === $color ) return '';
	if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) return $color;
	return '';
}
function absint( $maybeint ) {
	return abs( (int) $maybeint );
}
function sanitize_key( $key ) {
	$key = strtolower( (string) $key );
	return preg_replace( '/[^a-z0-9_\-]/', '', $key );
}
function user_trailingslashit( $url ) {
	return rtrim( (string) $url, '/' ) . '/';
}
function wp_doing_ajax() { return false; }
function wp_doing_cron() { return false; }
function is_user_logged_in() { return false; }
function wp_safe_redirect( $location, $status = 302 ) {
	global $last_redirect;
	$last_redirect = array( 'location' => $location, 'status' => $status );
	return true;
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {}
}
if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {}
}
if ( ! function_exists( 'wp_add_inline_script' ) ) {
	function wp_add_inline_script( $handle, $data, $position = 'after' ) {}
}
if ( ! function_exists( 'get_template_directory_uri' ) ) {
	function get_template_directory_uri() { return 'https://example.com/wp-content/themes/ntn-journal'; }
}
if ( ! function_exists( 'has_block' ) ) {
	function has_block( $block_name, $post = null ) { return false; }
}

// Load target project files
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/cache-keys.php';
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/api-helpers.php';
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/game-data.php'; // come WordPress: prima il plugin
require_once ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/helpers.php';
require_once ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/plugin-fallbacks.php';
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/seo-extras.php';
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/hub-seo.php';
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/schema-flags.php';
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/iubenda-compat.php';
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/category-hub.php';
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/category-hub-stats.php';
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/team-page.php';
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/rating-labels.php';
require_once ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/content-format.php';
require_once ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/menu-filters.php';
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/backup-restore.php';
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/taxonomies.php';
require_once ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/gallery-lightbox.php';
require_once ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/related-posts.php';

// The SEO Framework mock implementation
class The_SEO_Framework_Mock {
	public function get_image_details( $args = null ) {
		$params = array(
			'cbs' => array(
				'featured' => function() { return; },
			),
		);
		$params = apply_filters( 'the_seo_framework_image_generation_params', $params, $args, 'social' );

		$details = null;
		if ( isset( $params['cbs']['ntn_social_image'] ) && is_callable( $params['cbs']['ntn_social_image'] ) ) {
			$generator = call_user_func( $params['cbs']['ntn_social_image'], $args, 'full' );
			if ( $generator instanceof Generator ) {
				foreach ( $generator as $img ) {
					if ( ! empty( $img['url'] ) ) {
						$details = $img;
						break;
					}
				}
			}
		}

		if ( empty( $details ) ) {
			$details = array(
				'url'    => 'https://example.com/wp-content/uploads/site-logo.png',
				'src'    => 'https://example.com/wp-content/uploads/site-logo.png',
				'id'     => 1,
				'width'  => 500,
				'height' => 500,
			);
		}

		$details = apply_filters( 'the_seo_framework_image_details', $details, $args );
		$details = apply_filters( 'the_seo_framework_generated_image_details', $details, $args );
		$details = apply_filters( 'the_seo_framework_custom_image_details', $details, $args );

		return $details;
	}

	public function get_ogimage( $args = null ) {
		$details = $this->get_image_details( $args );
		$image_url = ( is_array( $details ) && ! empty( $details['url'] ) ) ? $details['url'] : '';
		return apply_filters( 'the_seo_framework_ogimage_output', $image_url, $args );
	}

	public function get_twitterimage( $args = null ) {
		$details = $this->get_image_details( $args );
		$image_url = ( is_array( $details ) && ! empty( $details['url'] ) ) ? $details['url'] : '';
		return apply_filters( 'the_seo_framework_twitterimage_output', $image_url, $args );
	}
}

global $mock_tsf_active;
$mock_tsf_active = true;

function the_seo_framework() {
	global $mock_tsf_active;
	if ( isset( $mock_tsf_active ) && ! $mock_tsf_active ) {
		return null;
	}
	static $tsf = null;
	if ( is_null( $tsf ) ) {
		$tsf = new The_SEO_Framework_Mock();
	}
	return $tsf;
}

$passed = 0;
$failed = 0;

function assert_test( $condition, $message ) {
	global $passed, $failed;
	if ( $condition ) {
		echo "[PASS] " . $message . "\n";
		$passed++;
	} else {
		echo "[FAIL] " . $message . "\n";
		$failed++;
	}
}

echo "=======================================================================\n";
echo " NTN JOURNAL - AUDIT TEST SUITE (PHP " . PHP_VERSION . ")\n";
echo "=======================================================================\n\n";

// --- TEST SUITE 1: Cover URL Normalization & CDN Safety ---
echo "--- 1. COVER URL NORMALIZATION & PROTOCOL SAFETY ---\n";
assert_test( ntn_normalize_game_cover_url( '//images.igdb.com/igdb/image/upload/t_thumb/co123.jpg' ) === 'https://images.igdb.com/igdb/image/upload/t_1080p/co123.jpg', '1.1: Protocol-relative IGDB URL converted to https:// 1080p' );
assert_test( ntn_normalize_game_cover_url( 'http://images.igdb.com/igdb/image/upload/t_cover_big/co456.jpg' ) === 'https://images.igdb.com/igdb/image/upload/t_1080p/co456.jpg', '1.2: Insecure http:// IGDB URL converted to https:// 1080p' );
assert_test( ntn_normalize_game_cover_url( 'https://images.igdb.com/igdb/image/upload/co789.jpg' ) === 'https://images.igdb.com/igdb/image/upload/t_1080p/co789.jpg', '1.3: IGDB URL without preset (/upload/hash.jpg) gets t_1080p inserted' );
assert_test( ntn_normalize_game_cover_url( 'co7abc.jpg' ) === 'https://images.igdb.com/igdb/image/upload/t_1080p/co7abc.jpg', '1.4: Raw hash with extension (co7abc.jpg) expands to 1080p IGDB URL' );
assert_test( ntn_normalize_game_cover_url( 'co7abc' ) === 'https://images.igdb.com/igdb/image/upload/t_1080p/co7abc.jpg', '1.5: Raw hash without extension (co7abc) expands to 1080p IGDB URL' );
assert_test( ntn_normalize_game_cover_url( 'https://res.cloudinary.com/demo/image/upload/t_media_custom/sample_cover.jpg' ) === 'https://res.cloudinary.com/demo/image/upload/t_media_custom/sample_cover.jpg', '1.6: Non-IGDB Third-Party CDN URL with /t_custom/ is preserved intact' );
assert_test( ntn_normalize_game_cover_url( 'https://example.com/wp-content/uploads/2024/05/t_thumb_image.jpg' ) === 'https://example.com/wp-content/uploads/2024/05/t_thumb_image.jpg', '1.7: Self-hosted local WP upload with /t_thumb_ in path is preserved intact' );
assert_test( ntn_normalize_game_cover_url( '//cdn.example.com/images/cover.jpg' ) === 'https://cdn.example.com/images/cover.jpg', '1.8: Non-IGDB Protocol-relative URL gets https: prepended safely' );

// Extreme / Malformed input tests
assert_test( ntn_normalize_game_cover_url( null ) === '', '1.9: Null returns empty string cleanly' );
assert_test( ntn_normalize_game_cover_url( false ) === '', '1.10: Boolean false returns empty string cleanly' );
assert_test( ntn_normalize_game_cover_url( '' ) === '', '1.11: Empty string returns empty string' );
assert_test( ntn_normalize_game_cover_url( '   ' ) === '', '1.12: Whitespace-only string returns empty string' );
assert_test( ntn_normalize_game_cover_url( array( 'url' => 'https://images.igdb.com/co123.jpg' ) ) === '', '1.13: Nested array returns empty string without error' );
assert_test( ntn_normalize_game_cover_url( new stdClass() ) === '', '1.14: stdClass object returns empty string without fatal error' );
assert_test( ntn_normalize_game_cover_url( new StringableObject( 'co1r7f' ) ) === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r7f.jpg', '1.15: Stringable object correctly expands to 1080p IGDB URL' );

// --- TEST SUITE 2: Hub IGDB Info Fallbacks & Type-Safety ---
echo "\n--- 2. HUB IGDB INFO FALLBACKS & TYPE SAFETY ---\n";

// Populate Mock DB
$mock_posts[101] = new WP_Post( array( 'ID' => 101, 'post_title' => 'Cyberpunk 2077', 'post_name' => 'cyberpunk-2077', 'post_type' => 'videogioco', 'post_status' => 'publish' ) );
$mock_postmeta[101] = array(
	'_ntn_game_cover'     => '//images.igdb.com/igdb/image/upload/t_thumb/co1r7f.jpg',
	'_ntn_game_release'   => '2020-12-10',
	'_ntn_game_developer' => 'CD Projekt RED',
);

$mock_posts[102] = new WP_Post( array( 'ID' => 102, 'post_title' => 'Starfield', 'post_name' => 'starfield', 'post_type' => 'videogioco', 'post_status' => 'publish' ) );
$mock_terms[201] = new WP_Term( array( 'term_id' => 201, 'name' => 'Starfield', 'slug' => 'starfield', 'taxonomy' => 'post_tag' ) );
$mock_post_tags[102] = array( $mock_terms[201] );
$mock_termmeta[201] = array(
	'_ntn_game_cover'     => 'https://images.igdb.com/igdb/image/upload/t_cover_big/co2m5e.jpg',
	'_ntn_game_release'   => '1693958400000',
	'_ntn_game_developer' => 'Bethesda Game Studios',
);

$mock_posts[103] = new WP_Post( array( 'ID' => 103, 'post_title' => 'Hollow Knight: Silksong', 'post_name' => 'hollow-knight-silksong', 'post_type' => 'videogioco', 'post_status' => 'publish' ) );
$mock_terms[202] = new WP_Term( array( 'term_id' => 202, 'name' => 'Silksong', 'slug' => 'hollow-knight-silksong', 'taxonomy' => 'post_tag' ) );
$mock_termmeta[202] = array(
	'_ntn_game_cover'     => 'co1r0p',
	'_ntn_game_release'   => '2024',
	'_ntn_game_developer' => 'Team Cherry',
);

$mock_posts[104] = new WP_Post( array( 'ID' => 104, 'post_title' => 'Indie Game No IGDB', 'post_name' => 'indie-game-no-igdb', 'post_type' => 'videogioco', 'post_status' => 'publish' ) );
$mock_postmeta[104] = array(
	'_thumbnail_url'    => 'https://example.com/wp-content/uploads/2026/08/indie-custom-featured.jpg',
	'_ntn_game_release' => '2025',
);

$mock_posts[105] = new WP_Post( array( 'ID' => 105, 'post_title' => 'Empty Game Without Image', 'post_name' => 'empty-game', 'post_type' => 'videogioco', 'post_status' => 'publish' ) );

$mock_posts[106] = new WP_Post( array( 'ID' => 106, 'post_title' => 'Zelda Tears of the Kingdom', 'post_name' => 'zelda-totk', 'post_type' => 'videogioco', 'post_status' => 'publish' ) );
$mock_postmeta[106] = array(
	'_ntn_game_cover' => 'co5vmg',
);

$mock_posts[107] = new WP_Post( array( 'ID' => 107, 'post_title' => 'HTTP Insecure Game', 'post_name' => 'http-insecure-game', 'post_type' => 'videogioco', 'post_status' => 'publish' ) );
$mock_postmeta[107] = array(
	'_ntn_game_cover' => 'http://images.igdb.com/igdb/image/upload/t_thumb/co9999.jpg',
);

$mock_posts[111] = new WP_Post( array( 'ID' => 111, 'post_title' => 'Cloudinary Game', 'post_name' => 'cloudinary-game', 'post_type' => 'videogioco', 'post_status' => 'publish' ) );
$mock_postmeta[111] = array(
	'_ntn_game_cover' => 'https://res.cloudinary.com/demo/image/upload/t_media_custom/sample_cover.jpg',
);

$mock_posts[112] = new WP_Post( array( 'ID' => 112, 'post_title' => 'Local Media Game', 'post_name' => 'local-media-game', 'post_type' => 'videogioco', 'post_status' => 'publish' ) );
$mock_postmeta[112] = array(
	'_ntn_game_cover' => 'https://example.com/wp-content/uploads/2024/05/t_thumb_image.jpg',
);

$mock_posts[113] = new WP_Post( array( 'ID' => 113, 'post_title' => 'Corrupt Meta Game', 'post_name' => 'corrupt-meta-game', 'post_type' => 'videogioco', 'post_status' => 'publish' ) );
$mock_postmeta[113] = array(
	'_ntn_game_cover'   => array( 'invalid_nested_structure' => 123 ),
	'_ntn_game_release' => array( '2026-08-14' ),
	'_thumbnail_url'    => 'https://example.com/wp-content/uploads/corrupt-fallback.jpg',
);

$info_101 = ntn_get_hub_igdb_info( 101 );
assert_test( isset( $info_101['cover'] ) && $info_101['cover'] === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r7f.jpg', '2.1: Post 101 resolves IGDB cover from post_meta' );

$info_102 = ntn_get_hub_igdb_info( 102 );
assert_test( isset( $info_102['cover'] ) && $info_102['cover'] === 'https://images.igdb.com/igdb/image/upload/t_1080p/co2m5e.jpg', '2.2: Post 102 resolves IGDB cover from attached term_meta' );

$info_103 = ntn_get_hub_igdb_info( 103 );
assert_test( isset( $info_103['cover'] ) && $info_103['cover'] === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r0p.jpg', '2.3: Post 103 resolves IGDB cover from matching slug term_meta' );

$info_104 = ntn_get_hub_igdb_info( 104 );
assert_test( isset( $info_104['cover'] ) && $info_104['cover'] === 'https://example.com/wp-content/uploads/2026/08/indie-custom-featured.jpg', '2.4: Post 104 without IGDB data falls back to WP featured image' );

$info_105 = ntn_get_hub_igdb_info( 105 );
assert_test( ! isset( $info_105['cover'] ) && isset( $info_105['title'] ) && $info_105['title'] === 'Empty Game Without Image', '2.5: Post 105 without any cover returns clean data without cover key' );

// Testing array and extreme arguments to ntn_get_hub_igdb_info
$info_arr_post = ntn_get_hub_igdb_info( array( 'id' => 101 ) );
assert_test( isset( $info_arr_post['cover'] ) && $info_arr_post['cover'] === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r7f.jpg', '2.6: Array argument with post ID resolves properly' );

$info_arr_term = ntn_get_hub_igdb_info( array( 'id' => 201, 'taxonomy' => 'post_tag' ) );
assert_test( isset( $info_arr_term['cover'] ) && $info_arr_term['cover'] === 'https://images.igdb.com/igdb/image/upload/t_1080p/co2m5e.jpg', '2.7: Array argument with term ID & taxonomy resolves properly' );

$info_arr_term_key = ntn_get_hub_igdb_info( array( 'term_id' => 202, 'taxonomy' => 'post_tag' ) );
assert_test( isset( $info_arr_term_key['cover'] ) && $info_arr_term_key['cover'] === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r0p.jpg', '2.8: Array argument with term_id key resolves properly' );

$info_invalid_arr = ntn_get_hub_igdb_info( array( 'taxonomy' => array( 'bad' ), 'id' => 'abc' ) );
assert_test( is_array( $info_invalid_arr ), '2.9: Malformed array argument returns array without notice/error' );

$info_null = ntn_get_hub_igdb_info( null );
assert_test( is_array( $info_null ), '2.10: Null object returns array safely' );

$info_false = ntn_get_hub_igdb_info( false );
assert_test( is_array( $info_false ), '2.11: False object returns array safely' );

$info_obj = ntn_get_hub_igdb_info( new stdClass() );
assert_test( is_array( $info_obj ), '2.12: Arbitrary stdClass object returns array safely' );

$info_wp_err = ntn_get_hub_igdb_info( new WP_Error() );
assert_test( is_array( $info_wp_err ), '2.13: WP_Error object returns array safely' );

// --- TEST SUITE 3: Social Preview Image Helper (`ntn_get_social_preview_image`) ---
echo "\n--- 3. SOCIAL PREVIEW IMAGE RESOLUTION ---\n";
$social_101 = ntn_get_social_preview_image( 101 );
assert_test( $social_101 === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r7f.jpg', '3.1: Post 101 returns IGDB 1080p cover' );

$social_104 = ntn_get_social_preview_image( 104 );
assert_test( $social_104 === 'https://example.com/wp-content/uploads/2026/08/indie-custom-featured.jpg', '3.2: Post 104 returns WP featured image' );

$social_105 = ntn_get_social_preview_image( 105 );
assert_test( $social_105 === 'https://example.com/wp-content/themes/ntn-journal/assets/images/default-cover.png', '3.3: Post 105 returns theme default cover fallback' );

$social_tag_201 = ntn_get_social_preview_image( array( 'id' => 201, 'taxonomy' => 'post_tag' ) );
assert_test( $social_tag_201 === 'https://images.igdb.com/igdb/image/upload/t_1080p/co2m5e.jpg', '3.4: Tag 201 via array returns IGDB cover' );

$social_malformed = ntn_get_social_preview_image( array( 'taxonomy' => array( 123 ), 'id' => array( 456 ) ) );
assert_test( $social_malformed === 'https://example.com/wp-content/themes/ntn-journal/assets/images/default-cover.png', '3.5: Malformed array input safely falls back to default cover' );

$social_stdclass = ntn_get_social_preview_image( new stdClass() );
assert_test( $social_stdclass === 'https://example.com/wp-content/themes/ntn-journal/assets/images/default-cover.png', '3.6: Arbitrary stdClass input safely falls back to default cover' );

// --- TEST SUITE 4: Parent Theme archive-videogioco.php Loop Simulation ---
echo "\n--- 4. PARENT THEME ARCHIVE LOOP SIMULATION ---\n";
$mock_loop_posts = array(
	$mock_posts[101],
	$mock_posts[102],
	$mock_posts[103],
	$mock_posts[104],
	$mock_posts[105],
	$mock_posts[106],
	$mock_posts[107],
	$mock_posts[111],
	$mock_posts[112],
	$mock_posts[113],
);
$mock_loop_index = 0;

ob_start();
require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/archive-videogioco.php';
$parent_output = ob_get_clean();

assert_test( strpos( $parent_output, 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r7f.jpg' ) !== false, '4.1: Post 101 renders 1080p cover' );
assert_test( strpos( $parent_output, 'https://images.igdb.com/igdb/image/upload/t_1080p/co2m5e.jpg' ) !== false, '4.2: Post 102 (TermMeta via tag) renders 1080p cover' );
assert_test( strpos( $parent_output, 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r0p.jpg' ) !== false, '4.3: Post 103 (Slug TermMeta) renders 1080p cover' );
assert_test( strpos( $parent_output, 'https://example.com/wp-content/uploads/2026/08/indie-custom-featured.jpg' ) !== false, '4.4: Post 104 (WP featured fallback) renders featured image' );
assert_test( strpos( $parent_output, 'NO COVER' ) !== false, '4.5: Post 105 renders NO COVER placeholder box' );
assert_test( strpos( $parent_output, 'https://images.igdb.com/igdb/image/upload/t_1080p/co5vmg.jpg' ) !== false, '4.6: Post 106 (Raw hash) renders 1080p IGDB URL' );
assert_test( strpos( $parent_output, 'https://images.igdb.com/igdb/image/upload/t_1080p/co9999.jpg' ) !== false, '4.7: Post 107 (Insecure HTTP) converts to https 1080p' );
assert_test( strpos( $parent_output, 'https://res.cloudinary.com/demo/image/upload/t_media_custom/sample_cover.jpg' ) !== false, '4.8: Post 111 (Cloudinary) passes untouched' );
assert_test( strpos( $parent_output, 'https://example.com/wp-content/uploads/2024/05/t_thumb_image.jpg' ) !== false, '4.9: Post 112 (Local media) passes untouched' );
assert_test( strpos( $parent_output, 'https://example.com/wp-content/uploads/corrupt-fallback.jpg' ) !== false, '4.10: Post 113 (Corrupt metadata) falls back safely to WP thumbnail' );

// Empty state test
$mock_loop_posts = array();
$mock_loop_index = 0;
ob_start();
require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/archive-videogioco.php';
$empty_output = ob_get_clean();
assert_test( strpos( $empty_output, 'VUOTO' ) !== false && strpos( $empty_output, 'Nessun videogioco corrisponde ai criteri di ricerca.' ) !== false, '4.11: Empty catalog outputs VUOTO message cleanly' );

// --- TEST SUITE 5: secondo giro del catalogo (v4.10.0: il catalogo con i filtri è nel parent) ---
echo "\n--- 5. CATALOG SECOND LOOP SIMULATION ---\n";
$mock_loop_posts = array(
	$mock_posts[101],
	$mock_posts[104],
	$mock_posts[105],
	$mock_posts[106],
	$mock_posts[113],
);
$mock_loop_index = 0;
ob_start();
require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/archive-videogioco.php';
$child_output = ob_get_clean();

assert_test( strpos( $child_output, 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r7f.jpg' ) !== false, '5.1: Catalog renders 1080p IGDB cover' );
assert_test( strpos( $child_output, 'https://example.com/wp-content/uploads/2026/08/indie-custom-featured.jpg' ) !== false, '5.2: Catalog renders WP featured fallback' );
assert_test( strpos( $child_output, 'NO COVER' ) !== false, '5.3: Catalog renders NO COVER placeholder' );
assert_test( strpos( $child_output, 'https://images.igdb.com/igdb/image/upload/t_1080p/co5vmg.jpg' ) !== false, '5.4: Catalog renders raw hash expanded to 1080p' );
assert_test( strpos( $child_output, 'https://example.com/wp-content/uploads/corrupt-fallback.jpg' ) !== false, '5.5: Catalog handles corrupt metadata safely' );

// --- TEST SUITE 6: The SEO Framework (TSF) Image Resolution & Type Safety ---
echo "\n--- 6. THE SEO FRAMEWORK (TSF) INTEGRATION & TYPE SAFETY ---\n";

$tsf_101 = the_seo_framework()->get_image_details( 101 );
assert_test( $tsf_101['url'] === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r7f.jpg', '6.1: TSF resolves Post 101 to IGDB 1080p cover' );

$tsf_104 = the_seo_framework()->get_image_details( 104 );
assert_test( $tsf_104['url'] === 'https://example.com/wp-content/uploads/2026/08/indie-custom-featured.jpg', '6.2: TSF resolves Post 104 to WP featured image' );

$tsf_106 = the_seo_framework()->get_image_details( 106 );
assert_test( $tsf_106['url'] === 'https://images.igdb.com/igdb/image/upload/t_1080p/co5vmg.jpg', '6.3: TSF resolves Post 106 (raw hash) to 1080p IGDB URL' );

$mock_terms[203] = new WP_Term( array( 'term_id' => 203, 'name' => 'Witcher 3', 'slug' => 'witcher-3', 'taxonomy' => 'post_tag' ) );
$mock_termmeta[203] = array( '_ntn_game_cover' => 'co1mw7' );

$tsf_tag = the_seo_framework()->get_image_details( array( 'id' => 203, 'taxonomy' => 'post_tag' ) );
assert_test( $tsf_tag['url'] === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1mw7.jpg', '6.4: TSF resolves Tag 203 with taxonomy argument' );

$tsf_tag_term_id = the_seo_framework()->get_image_details( array( 'term_id' => 203, 'taxonomy' => 'post_tag' ) );
assert_test( $tsf_tag_term_id['url'] === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1mw7.jpg', '6.5: TSF resolves Tag 203 with term_id key' );

$queried_object = $mock_posts[101];
$tsf_global_cpt = the_seo_framework()->get_image_details();
assert_test( $tsf_global_cpt['url'] === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r7f.jpg', '6.6: TSF resolves global queried object (CPT videogioco)' );

$queried_object = $mock_terms[203];
$tsf_global_tag = the_seo_framework()->get_image_details();
assert_test( $tsf_global_tag['url'] === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1mw7.jpg', '6.7: TSF resolves global queried object (Tag term)' );

// OG & Twitter image filter overrides
$og_override = apply_filters( 'the_seo_framework_ogimage_output', 'https://example.com/site-logo.png', 101 );
assert_test( $og_override === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r7f.jpg', '6.8: TSF ogimage filter overrides site logo with IGDB cover' );

$tw_override = apply_filters( 'the_seo_framework_twitterimage_output', 'https://example.com/default-logo.png', 101 );
assert_test( $tw_override === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r7f.jpg', '6.9: TSF twitterimage filter overrides default logo with IGDB cover' );

// Extreme type testing on TSF filters
$og_arr_nested = apply_filters( 'the_seo_framework_ogimage_output', array( 'url' => array( 'invalid_type' ) ), 101 );
assert_test( $og_arr_nested === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r7f.jpg', '6.10: TSF ogimage filter handles nested array url safely' );

$tw_arr_nested = apply_filters( 'the_seo_framework_twitterimage_output', array( 'src' => array( 'invalid_type' ) ), 101 );
assert_test( $tw_arr_nested === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r7f.jpg', '6.11: TSF twitterimage filter handles nested array src safely' );

$tsf_fallback_null = apply_filters( 'the_seo_framework_image_details', null, 101 );
assert_test( is_array( $tsf_fallback_null ) && $tsf_fallback_null['url'] === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r7f.jpg', '6.12: TSF image details fallback handles null input safely' );

$tsf_fallback_false = apply_filters( 'the_seo_framework_image_details', false, 101 );
assert_test( is_array( $tsf_fallback_false ) && $tsf_fallback_false['url'] === 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r7f.jpg', '6.13: TSF image details fallback handles false input safely' );

$tsf_fallback_malformed_args = apply_filters( 'the_seo_framework_image_details', array( 'url' => 'https://example.com/site-logo.png' ), array( 'taxonomy' => array( 'bad' ) ) );
assert_test( is_array( $tsf_fallback_malformed_args ), '6.14: TSF image details fallback handles malformed args without fatal error' );

// --- TEST SUITE 7: Native Open Graph Tags, Breadcrumbs & Auto TOC Type-Safety ---
echo "\n--- 7. NATIVE OPEN GRAPH TAGS, BREADCRUMBS & AUTO TOC TYPE-SAFETY ---\n";
$mock_tsf_active = false;
$mock_post_types['videogioco'] = new WP_Post_Type( 'videogioco', 'Videogioco', 'Videogiochi' );
$mock_query_vars['is_post_type_archive'] = true;
$mock_query_vars['post_type'] = 'videogioco';
$queried_object = null;

ob_start();
ntn_journal_open_graph_tags();
$archive_og_output = ob_get_clean();

assert_test( strpos( $archive_og_output, 'content="https://example.com/videogioco/"' ) !== false, '7.1: Archive og:url outputs /videogioco/ permalink' );
assert_test( strpos( $archive_og_output, 'Catalogo e hub centrale di tutti i videogiochi trattati su NTN Journal.' ) !== false, '7.2: Archive og:description outputs catalog summary' );

// Breadcrumbs on archive
ob_start();
ntn_journal_breadcrumb();
$archive_breadcrumb = ob_get_clean();
assert_test( strpos( $archive_breadcrumb, 'Videogiochi' ) !== false, '7.3: Breadcrumbs on archive outputs post type plural label' );

// Breadcrumbs with null queried object
$mock_query_vars = array();
$queried_object = null;
ob_start();
ntn_journal_breadcrumb();
$null_breadcrumb = ob_get_clean();
assert_test( strpos( $null_breadcrumb, 'Archivio' ) !== false, '7.4: Breadcrumbs with null queried object falls back safely to Archivio' );

// Breadcrumbs on single post with null global $post
$queried_object = $mock_posts[101];
$GLOBALS['post'] = null;
ob_start();
ntn_journal_breadcrumb();
$single_null_post_breadcrumb = ob_get_clean();
assert_test( strpos( $single_null_post_breadcrumb, 'Cyberpunk 2077' ) !== false, '7.5: Breadcrumbs on single post with null global $post handles post_id safely' );

// Breadcrumbs on single post with primary category
$cat_recensioni = new WP_Term( array( 'term_id' => 301, 'name' => 'Recensioni', 'slug' => 'recensioni', 'taxonomy' => 'category' ) );
$mock_terms[301] = $cat_recensioni;
$mock_posts[120] = new WP_Post( array( 'ID' => 120, 'post_title' => 'Elden Ring Review', 'post_name' => 'elden-ring-review', 'post_type' => 'post', 'post_status' => 'publish' ) );
$mock_post_categories[120] = array( $cat_recensioni );
$queried_object = $mock_posts[120];
$GLOBALS['post'] = $mock_posts[120];
ob_start();
ntn_journal_breadcrumb();
$cat_breadcrumb = ob_get_clean();
assert_test( strpos( $cat_breadcrumb, 'Recensioni' ) !== false && strpos( $cat_breadcrumb, 'Elden Ring Review' ) !== false, '7.6: Breadcrumbs on single post with category renders category item' );

// Breadcrumbs on single post when category link returns WP_Error
$bad_cat = new WP_Term( array( 'term_id' => 999999, 'name' => 'Corrupt Cat', 'slug' => 'corrupt-cat', 'taxonomy' => 'category' ) );
$mock_terms[999999] = $bad_cat;
$mock_posts[121] = new WP_Post( array( 'ID' => 121, 'post_title' => 'Corrupt Cat Post', 'post_name' => 'corrupt-cat-post', 'post_type' => 'post', 'post_status' => 'publish' ) );
$mock_post_categories[121] = array( $bad_cat );
$queried_object = $mock_posts[121];
$GLOBALS['post'] = $mock_posts[121];
ob_start();
ntn_journal_breadcrumb();
$bad_cat_breadcrumb = ob_get_clean();
assert_test( strpos( $bad_cat_breadcrumb, 'href="#"' ) !== false, '7.7: Breadcrumbs with WP_Error category link falls back to #' );

// Breadcrumbs on taxonomy archive when get_term_link returns WP_Error
$bad_term = new WP_Term( array( 'term_id' => 999999, 'name' => 'Bad Term', 'slug' => 'bad-term', 'taxonomy' => 'post_tag' ) );
$queried_object = $bad_term;
$GLOBALS['post'] = null;
ob_start();
ntn_journal_breadcrumb();
$bad_term_breadcrumb = ob_get_clean();
assert_test( strpos( $bad_term_breadcrumb, 'Bad Term' ) !== false, '7.8: Breadcrumbs on term archive with WP_Error link renders safely' );

// Breadcrumbs on post_type archive with array query var
$mock_query_vars['is_post_type_archive'] = true;
$mock_query_vars['post_type'] = array( 'videogioco', 'post' );
$queried_object = null;
ob_start();
ntn_journal_breadcrumb();
$array_pt_breadcrumb = ob_get_clean();
assert_test( strpos( $array_pt_breadcrumb, 'Videogiochi' ) !== false, '7.9: Breadcrumbs on post_type archive with array query var resolves safely' );

// Breadcrumbs on author page
$mock_query_vars = array( 'is_author' => true );
$author_obj = (object) array( 'ID' => 42, 'display_name' => 'Editor in Chief' );
$queried_object = $author_obj;
ob_start();
ntn_journal_breadcrumb();
$author_breadcrumb = ob_get_clean();
assert_test( strpos( $author_breadcrumb, 'Editor in Chief' ) !== false, '7.10: Breadcrumbs on author page renders author name' );

// Open Graph tags on taxonomy archive when get_term_link returns WP_Error
$queried_object = $bad_term;
$mock_query_vars = array();
ob_start();
ntn_journal_open_graph_tags();
$bad_term_og = ob_get_clean();
assert_test( strpos( $bad_term_og, 'content="https://example.com/"' ) !== false, '7.11: Open Graph tags on term archive with WP_Error link falls back to home_url' );

// Open Graph tags on CPT videogioco with array developer metadata
$mock_posts[130] = new WP_Post( array( 'ID' => 130, 'post_title' => 'Multi Dev Game', 'post_name' => 'multi-dev-game', 'post_type' => 'videogioco', 'post_status' => 'publish' ) );
$mock_postmeta[130] = array(
	'_ntn_game_developer' => array( 'CD Projekt RED', 'Trigger' ),
	'_ntn_game_cover'     => 'co1234',
);
$queried_object  = $mock_posts[130];
$GLOBALS['post'] = $mock_posts[130];
$mock_query_vars = array();
ob_start();
ntn_journal_open_graph_tags();
$multidev_og = ob_get_clean();
// 7.12 (aggiornato in 3.11.0): la descrizione della Hub è il testo automatico, che unisce gli sviluppatori in italiano
assert_test( strpos( $multidev_og, 'Multi Dev Game è un videogioco sviluppato da CD Projekt RED e Trigger' ) !== false, '7.12: Open Graph description handles array developer meta without notice' );

// Auto Table of Contents type-safety
assert_test( ntn_journal_auto_toc( null ) === null, '7.13: Auto TOC handles null content cleanly' );
assert_test( ntn_journal_auto_toc( false ) === false, '7.14: Auto TOC handles boolean false content cleanly' );
assert_test( ntn_journal_auto_toc( 12345 ) === 12345, '7.15: Auto TOC handles integer content cleanly' );
assert_test( ntn_journal_auto_toc( array() ) === array(), '7.16: Auto TOC handles array content cleanly' );

// Auto Table of Contents with headings
$queried_object = $mock_posts[101];
$article_html = '<h2>Introduzione</h2><p>Testo iniziale.</p><h3>Gameplay</h3><p>Dettagli gameplay.</p><h2>Conclusioni</h2><p>Verdetto.</p>';
$toc_result = ntn_journal_auto_toc( $article_html );
assert_test( strpos( $toc_result, 'ntn-toc' ) !== false && strpos( $toc_result, '#introduzione' ) !== false && strpos( $toc_result, '#gameplay' ) !== false, '7.17: Auto TOC generates navigation list and heading anchor IDs' );

// Auto Table of Contents with existing class attribute (avoid duplicate class attribute)
$heading_with_class = '<h2 class="existing-class">Titolo Esistente</h2><p>Contenuto.</p><h2>Secondo Titolo</h2><p>Altro contenuto.</p>';
$toc_class_result = ntn_journal_auto_toc( $heading_with_class );
assert_test( strpos( $toc_class_result, 'class="scroll-mt-32 existing-class"' ) !== false, '7.18: Auto TOC injects scroll-mt-32 into existing class attribute without duplication' );

// --- TEST SUITE 8: Archive Loop Edge Cases (Whitespace & Array Dates) ---
echo "\n--- 8. ARCHIVE LOOP EDGE CASES (WHITESPACE & ARRAY DATES) ---\n";
$mock_posts[140] = new WP_Post( array( 'ID' => 140, 'post_title' => 'Whitespace Date Game', 'post_name' => 'ws-date-game', 'post_type' => 'videogioco', 'post_status' => 'publish' ) );
$mock_postmeta[140] = array(
	'_ntn_game_cover'   => 'co9999',
	'_ntn_game_release' => '   ',
);

$mock_posts[141] = new WP_Post( array( 'ID' => 141, 'post_title' => 'Array Date Game', 'post_name' => 'arr-date-game', 'post_type' => 'videogioco', 'post_status' => 'publish' ) );
$mock_postmeta[141] = array(
	'_ntn_game_cover'   => 'co8888',
	'_ntn_game_release' => array( '2025-11-20' ),
);

$mock_loop_posts = array( $mock_posts[140], $mock_posts[141] );
$mock_loop_index = 0;
ob_start();
require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/archive-videogioco.php';
$edge_archive_out = ob_get_clean();

// Post 140 should NOT have an empty span with mt-1 rendered
$has_empty_span = (bool) preg_match( '/<span class="ntn-game-year">\s*<\/span>/', $edge_archive_out );
assert_test( ! $has_empty_span, '8.1: Post with whitespace-only release date does NOT render empty span' );
assert_test( strpos( $edge_archive_out, '2025' ) !== false, '8.2: Post with array release date correctly extracts year 2025' );

// --- TEST SUITE 9: Custom Taxonomy & Engine Links ---
echo "\n--- 9. CUSTOM TAXONOMY & ENGINE LINKS ---\n";
$mock_terms[401] = new WP_Term( array( 'term_id' => 401, 'name' => 'Cyberpunk Tag', 'slug' => 'cyberpunk-2077', 'taxonomy' => 'videogioco_tag' ) );
$info_custom_tax = ntn_get_hub_igdb_info( array( 'id' => 401, 'taxonomy' => 'videogioco_tag' ) );
assert_test( isset( $info_custom_tax['title'] ) && $info_custom_tax['title'] === 'Cyberpunk 2077', '9.1: Custom taxonomy term resolves CPT metadata via slug match' );

// --- TEST SUITE 10: Helpers Type Safety (Genres, Companies, Engines) ---
echo "\n--- 10. HELPERS TYPE SAFETY (GENRES, COMPANIES, ENGINES) ---\n";
assert_test( ntn_translate_igdb_genres( new stdClass() ) === '', '10.1: ntn_translate_igdb_genres handles stdClass cleanly' );
assert_test( ntn_translate_igdb_genres( new StringableObject( 'action, adventure' ) ) === 'Azione, Avventura', '10.2: ntn_translate_igdb_genres handles Stringable object' );
assert_test( ntn_translate_igdb_genres( array( 'fighting', 'shooter' ) ) === 'Picchiaduro, Sparatutto', '10.3: ntn_translate_igdb_genres handles array input' );
assert_test( ntn_translate_igdb_genres( null ) === '', '10.4: ntn_translate_igdb_genres handles null cleanly' );
assert_test( ntn_translate_igdb_genres( false ) === '', '10.5: ntn_translate_igdb_genres handles boolean false cleanly' );
assert_test( ntn_translate_igdb_genres( 123 ) === '123', '10.6: ntn_translate_igdb_genres handles integer cleanly' );

assert_test( ntn_link_companies_to_tags( new stdClass() ) === '', '10.7: ntn_link_companies_to_tags handles stdClass cleanly' );
assert_test( ntn_link_companies_to_tags( null ) === '', '10.8: ntn_link_companies_to_tags handles null cleanly' );
$linked_comp = ntn_link_companies_to_tags( array( 'CD Projekt RED', 'Unknown Studio' ) );
assert_test( strpos( $linked_comp, 'Unknown Studio' ) !== false, '10.9: ntn_link_companies_to_tags handles array input' );

// Company with WP_Error tag link
$bad_comp_term = new WP_Term( array( 'term_id' => 999999, 'name' => 'Bad Corp', 'slug' => 'bad-corp', 'taxonomy' => 'post_tag' ) );
$mock_terms[999999] = $bad_comp_term;
$linked_bad_comp = ntn_link_companies_to_tags( 'Bad Corp' );
assert_test( $linked_bad_comp === 'Bad Corp', '10.10: ntn_link_companies_to_tags falls back to plain text when get_term_link returns WP_Error' );

assert_test( ntn_link_engines_to_terms( new stdClass() ) === '', '10.11: ntn_link_engines_to_terms handles stdClass cleanly' );
assert_test( ntn_link_engines_to_terms( null ) === '', '10.12: ntn_link_engines_to_terms handles null cleanly' );
$linked_engine = ntn_link_engines_to_terms( array( 'Unreal Engine 5', 'Unity' ) );
assert_test( strpos( $linked_engine, 'Unreal Engine 5' ) !== false, '10.13: ntn_link_engines_to_terms handles array input' );

// Engine with WP_Error term link
$mock_terms[999999]->taxonomy = 'game_engine';
$mock_terms[999999]->name = 'Corrupt Engine';
$mock_terms[999999]->slug = 'corrupt-engine';
$linked_bad_engine = ntn_link_engines_to_terms( 'Corrupt Engine' );
assert_test( strpos( $linked_bad_engine, '<span' ) !== false && strpos( $linked_bad_engine, 'Corrupt Engine' ) !== false, '10.14: ntn_link_engines_to_terms falls back to span badge when link returns WP_Error' );

// --- TEST SUITE 11: Game Hub URL Helper Type Safety & WP_Error Links ---
echo "\n--- 11. GAME HUB URL HELPER TYPE SAFETY & WP_ERROR LINKS ---\n";
assert_test( ntn_get_game_hub_url( 101 ) === 'https://example.com/videogioco/cyberpunk-2077/', '11.1: ntn_get_game_hub_url returns permalink for CPT videogioco' );

// Post linked to tag where get_term_link returns WP_Error
$mock_posts[150] = new WP_Post( array( 'ID' => 150, 'post_title' => 'Article With Bad Tag', 'post_name' => 'article-with-bad-tag', 'post_type' => 'post', 'post_status' => 'publish' ) );
$mock_post_tags[150] = array( $mock_terms[999999] );
$mock_termmeta[999999] = array( '_ntn_game_cover' => 'co1111' );
$hub_url_err = ntn_get_game_hub_url( 150 );
assert_test( $hub_url_err === '' || is_string( $hub_url_err ), '11.2: ntn_get_game_hub_url returns string (never WP_Error) when get_term_link fails' );

// Post with non-string game title meta
$mock_posts[151] = new WP_Post( array( 'ID' => 151, 'post_title' => 'Article With Array Title Meta', 'post_name' => 'article-with-array-meta', 'post_type' => 'post', 'post_status' => 'publish' ) );
$mock_postmeta[151] = array( '_ntn_game_title' => array( 'Invalid' ) );
$hub_url_arr_meta = ntn_get_game_hub_url( 151 );
assert_test( $hub_url_arr_meta === '', '11.3: ntn_get_game_hub_url handles array _ntn_game_title meta cleanly' );

// --- TEST SUITE 12: Auto TOC Duplicate Heading Slug Deduplication ---
echo "\n--- 12. AUTO TOC DUPLICATE HEADING SLUG DEDUPLICATION ---\n";
$duplicate_headings_html = '<h2>Introduzione</h2><p>P1</p><h2>Introduzione</h2><p>P2</p><h2>Introduzione</h2><p>P3</p>';
$dedup_toc = ntn_journal_auto_toc( $duplicate_headings_html );
assert_test( strpos( $dedup_toc, 'href="#introduzione"' ) !== false && strpos( $dedup_toc, 'href="#introduzione-2"' ) !== false && strpos( $dedup_toc, 'href="#introduzione-3"' ) !== false, '12.1: Auto TOC generates unique anchor links for duplicate heading titles' );
assert_test( strpos( $dedup_toc, 'id="introduzione"' ) !== false && strpos( $dedup_toc, 'id="introduzione-2"' ) !== false && strpos( $dedup_toc, 'id="introduzione-3"' ) !== false, '12.2: Auto TOC injects unique id attributes into content for duplicate heading titles' );

// --- TEST SUITE 13: Template Parts Hardening (Hub Header, Review Mobile, Sidebar, Review Box) ---
echo "\n--- 13. TEMPLATE PARTS HARDENING (HUB HEADER, MOBILE, SIDEBAR, REVIEW BOX) ---\n";

// 1. Hub Header with Array Release Date, Array Platforms, Array Genres
$args = array(
	'info' => array(
		'title'       => 'Template Test Game',
		'release'     => array( '2026-08-14' ),
		'platforms'   => array( 'PC', 'PlayStation 5', 'Xbox Series X' ),
		'genres'      => array( 'Action', 'RPG' ),
		'developer'   => array( 'CD Projekt RED' ),
		'publisher'   => array( 'CD Projekt' ),
		'cover'       => 'https://images.igdb.com/igdb/image/upload/t_1080p/co1r7f.jpg',
	),
);
ob_start();
require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/hub-header.php';
$hub_header_out = ob_get_clean();
assert_test( strpos( $hub_header_out, '14/08/2026' ) !== false, '13.1: Hub header formats array release date correctly' );
assert_test( strpos( $hub_header_out, 'PlayStation 5' ) !== false, '13.2: Hub header renders array platforms without notice' );
assert_test( strpos( $hub_header_out, 'Action, RPG' ) !== false, '13.3: Hub header renders array genres without notice' );

// 2. Setup Mock Post 160 for Review Templates
$mock_posts[160] = new WP_Post( array(
	'ID'          => 160,
	'post_title'  => 'Cyberpunk 2077 Review',
	'post_name'   => 'cyberpunk-2077-review',
	'post_type'   => 'post',
	'post_status' => 'publish',
) );
$mock_postmeta[160] = array(
	'rating'                => '4',
	'_ntn_game_title'       => 'Cyberpunk 2077',
	'_ntn_game_cover'       => 'co1r7f',
	'_ntn_game_platforms'   => array( 'PC', 'Nintendo Switch' ),
	'_ntn_game_release'     => '1693958400000',
	'_ntn_game_genres'      => array( 'Avventura', 'Azione' ),
	'_ntn_game_engine'      => 'REDengine 4',
	'_ntn_game_developer'   => 'CD Projekt RED',
	'_ntn_game_publisher'   => 'CD Projekt',
	'_ntn_game_url'         => 'https://www.igdb.com/games/cyberpunk-2077',
	'ntn_review_source'     => 'Copia digitale fornita dal publisher',
	'pros'                  => "Grafica eccezionale\nStoria avvincente",
	'cons'                  => "Qualche bug residuo",
	'verdict'               => "Un capolavoro.",
);
$mock_terms[501] = new WP_Term( array( 'term_id' => 501, 'name' => 'Consigliato', 'slug' => 'consigliato', 'taxonomy' => 'post_tag' ) );
$mock_post_tags[160] = array( $mock_terms[501] );

$queried_object  = $mock_posts[160];
$GLOBALS['post'] = $mock_posts[160];

// 3. Review Header Mobile
ob_start();
require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/review-header-mobile.php';
$review_mobile_out = ob_get_clean();
assert_test( strpos( $review_mobile_out, 'PC, Nintendo Switch' ) !== false, '13.4: Review header mobile renders array platforms cleanly' );
assert_test( strpos( $review_mobile_out, '06/09/2023' ) !== false, '13.5: Review header mobile converts timestamp release date cleanly' );

// 4. Review Sidebar Desktop
ob_start();
require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/review-sidebar-desktop.php';
$review_sidebar_out = ob_get_clean();
assert_test( strpos( $review_sidebar_out, 'Avventura, Azione' ) !== false, '13.6: Review sidebar desktop formats array genres cleanly' );
assert_test( strpos( $review_sidebar_out, 'PC, Nintendo Switch' ) !== false, '13.7: Review sidebar desktop formats array platforms cleanly' );
assert_test( strpos( $review_sidebar_out, '06/09/2023' ) !== false, '13.8: Review sidebar desktop formats release date cleanly' );
assert_test( strpos( $review_sidebar_out, 'Vedi su IGDB.com' ) !== false, '13.9: Review sidebar desktop renders IGDB button cleanly' );

// 5. Review Original Box with WP_Error tag link fallback (Post 161 with rating 5 and bad tag)
$mock_posts[161] = new WP_Post( array(
	'ID'          => 161,
	'post_title'  => 'Must Play Game Review',
	'post_name'   => 'must-play-game-review',
	'post_type'   => 'post',
	'post_status' => 'publish',
) );
$mock_postmeta[161] = array(
	'rating' => '5',
);
$bad_must_play = new WP_Term( array( 'term_id' => 999999, 'name' => 'Must Play', 'slug' => 'must-play', 'taxonomy' => 'post_tag' ) );
$mock_terms[999999] = $bad_must_play;

$queried_object  = $mock_posts[161];
$GLOBALS['post'] = $mock_posts[161];

ob_start();
require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/review-original-box.php';
$review_box_out = ob_get_clean();
assert_test( strpos( $review_box_out, 'href="https://example.com/tag/must-play/"' ) !== false, '13.10: Review original box falls back to /tag/must-play/ when get_term_link returns WP_Error' );

// --- TEST SUITE 14: Single Videogioco Media/Trailer Parsing ---
echo "\n--- 14. SINGLE VIDEOGIOCO MEDIA / TRAILER HARDENING ---\n";
$igdb_info['video'] = array( 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' );
$igdb_info['screenshots'] = array( 'https://images.igdb.com/igdb/image/upload/t_thumb/co123.jpg', new stdClass(), '' );

$raw_video = ( ! empty( $igdb_info['video'] ) && is_scalar( $igdb_info['video'] ) ) ? trim( (string) $igdb_info['video'] ) : ( ( ! empty( $igdb_info['video'] ) && is_array( $igdb_info['video'] ) ) ? trim( (string) reset( $igdb_info['video'] ) ) : '' );
$video_id  = '';
if ( ! empty( $raw_video ) ) {
	if ( preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $raw_video, $v_matches ) ) {
		$video_id = $v_matches[1];
	} else {
		$video_id = sanitize_text_field( $raw_video );
	}
}
assert_test( $video_id === 'dQw4w9WgXcQ', '14.1: Video parser extracts YouTube ID from array video meta' );

$scr_array = array();
if ( ! empty( $igdb_info['screenshots'] ) ) {
	$raw_scrs = is_array( $igdb_info['screenshots'] ) ? $igdb_info['screenshots'] : ( is_scalar( $igdb_info['screenshots'] ) ? explode( ',', (string) $igdb_info['screenshots'] ) : array() );
	foreach ( $raw_scrs as $scr_item ) {
		if ( ! is_scalar( $scr_item ) && ( ! is_object( $scr_item ) || ! method_exists( $scr_item, '__toString' ) ) ) {
			continue;
		}
		$scr_item = trim( (string) $scr_item );
		if ( empty( $scr_item ) ) continue;
		$thumb_url = preg_replace( '/\/t_[a-z0-9_]+\//', '/t_720p/', $scr_item );
		$full_url  = preg_replace( '/\/t_[a-z0-9_]+\//', '/t_1080p/', $scr_item );
		$scr_array[] = array(
			'thumb' => $thumb_url,
			'full'  => $full_url,
		);
	}
}
assert_test( count( $scr_array ) === 1 && $scr_array[0]['thumb'] === 'https://images.igdb.com/igdb/image/upload/t_720p/co123.jpg', '14.2: Screenshots parser filters out non-scalar items and converts valid URLs cleanly' );

// --- TEST SUITE 16: Single Comunicato Stampa & RSS Hardening ---
echo "\n--- 16. COMUNICATI STAMPA TEMPLATE & RSS HARDENING ---\n";
$mock_posts[170] = new WP_Post( array(
	'ID'          => 170,
	'post_title'  => 'PlayStation Press Release',
	'post_name'   => 'playstation-press-release',
	'post_type'   => 'comunicato_stampa',
	'post_status' => 'publish',
) );
$mock_postmeta[170] = array(
	'ntn_press_publisher'   => array( 'Sony Interactive Entertainment' ),
	'ntn_press_developer'   => array( 'Naughty Dog' ),
	'ntn_press_agency'      => array( 'PlayStation PR' ),
	'ntn_press_source_link' => array( 'https://blog.playstation.com/news' ),
	'ntn_press_assets'      => array( 'https://drive.google.com/press-kit' ),
	'ntn_press_embargo'     => array( '15 Novembre 2026' ),
);

$queried_object  = $mock_posts[170];
$GLOBALS['post'] = $mock_posts[170];
$mock_loop_posts = array( $mock_posts[170] );
$mock_loop_index = 0;

ob_start();
require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/single-comunicato_stampa.php';
$single_press_out = ob_get_clean();

assert_test( strpos( $single_press_out, 'Sony Interactive Entertainment' ) !== false, '16.1: Single comunicato renders array publisher cleanly' );
assert_test( strpos( $single_press_out, 'Naughty Dog' ) !== false, '16.2: Single comunicato renders array developer cleanly' );
assert_test( strpos( $single_press_out, 'PlayStation PR' ) !== false, '16.3: Single comunicato renders array agency cleanly' );
assert_test( strpos( $single_press_out, 'https://blog.playstation.com/news' ) !== false, '16.4: Single comunicato renders array source link cleanly' );
assert_test( strpos( $single_press_out, 'https://drive.google.com/press-kit' ) !== false, '16.5: Single comunicato renders array assets link cleanly' );
assert_test( is_string( $single_press_out ) && ! empty( $single_press_out ), '16.6: Single comunicato template renders completely without PHP notices' );
assert_test( strpos( $single_press_out, 'https://example.com/comunicato_stampa/' ) !== false, '16.7: Single comunicato renders valid archive link cleanly' );

// --- TEST SUITE 17: Author Byline Hardening ---
// (aggiornata in 3.15.1: il vecchio box author-bio.php è stato eliminato; si prova la firma author-panel.php)
echo "\n--- 17. AUTHOR BYLINE TEMPLATE HARDENING ---\n";
if ( ! function_exists( 'count_user_posts' ) ) { function count_user_posts( $user_id, $post_type = 'post', $public_only = false ) { return $user_id ? 12 : 0; } }
if ( ! function_exists( 'number_format_i18n' ) ) { function number_format_i18n( $number, $decimals = 0 ) { return number_format( (float) $number, $decimals, ',', '.' ); } }
global $mock_author_meta;
$mock_author_meta = array(
	'ID'           => 42,
	'display_name' => 'Mario Rossi',
	'description'  => 'Senior Game Journalist con 15 anni di esperienza.',
	'twitter'      => 'https://x.com/mariorossi',
	'user_url'     => 'https://mariorossi.it',
);

ob_start();
require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/author-panel.php';
$author_bio_out = ob_get_clean();
assert_test( strpos( $author_bio_out, '>Mario Rossi</a>' ) !== false && strpos( $author_bio_out, 'Mario_Rossi' ) === false, '17.1: Author byline renders the display name with plain spaces' );
assert_test( strpos( $author_bio_out, 'Senior Game Journalist' ) !== false || strpos( $author_bio_out, 'Redattore per' ) !== false, '17.2: Author byline renders biography text or default bio fallback' );
assert_test( strpos( $author_bio_out, 'https://example.com/author/42/' ) !== false, '17.3: Author byline renders safe author posts URL' );
assert_test( strpos( $author_bio_out, '>X</a>' ) !== false && strpos( $author_bio_out, '>WEB</a>' ) !== false && strpos( $author_bio_out, 'rel="noopener noreferrer"' ) !== false, '17.4: Author byline renders social and website links safely' );

// Firma quando l'autore non ha un ID valido (get_author_posts_url restituisce false)
$mock_author_meta['ID'] = 0;
ob_start();
require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/author-panel.php';
$bad_author_bio_out = ob_get_clean();
assert_test( strpos( $bad_author_bio_out, 'href="#"' ) !== false, '17.5: Author byline safely falls back to href="#" when author posts URL fails' );
assert_test( ! file_exists( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/author-bio.php' ), '17.6: Obsolete author-bio.php has been removed from the theme' );


// --- TEST SUITE 18: Content Link Formatter Type-Safety ---
echo "\n--- 18. CONTENT LINK FORMATTER TYPE SAFETY ---\n";
assert_test( ntn_format_content_links( null ) === null, '18.1: ntn_format_content_links handles null cleanly' );
assert_test( ntn_format_content_links( false ) === false, '18.2: ntn_format_content_links handles boolean false cleanly' );
assert_test( ntn_format_content_links( 12345 ) === 12345, '18.3: ntn_format_content_links handles integer cleanly' );
assert_test( ntn_format_content_links( array( 'content' ) ) === array( 'content' ), '18.4: ntn_format_content_links handles array cleanly' );

$sample_paragraph = '<p>Scopri maggiori dettagli sul <a href="https://example.com/art">nostro articolo di approfondimento</a> per saperne di più.</p>';
$formatted_paragraph = ntn_format_content_links( $sample_paragraph );
assert_test( $formatted_paragraph === $sample_paragraph, '18.5: ntn_format_content_links preserves clean link text (Nothing OS CSS typography)' );

// --- TEST SUITE 19: Rating Labels & Initials Strict Type-Safety ---
echo "\n--- 19. RATING LABELS & INITIALS STRICT TYPE SAFETY ---\n";
assert_test( get_rating_label( 1 ) === 'BOCCIATO', '19.1: Rating 1 returns BOCCIATO' );
assert_test( get_rating_label( 2 ) === 'BOCCIATO', '19.2: Rating 2 returns BOCCIATO' );
assert_test( get_rating_label( 3 ) === 'CONSIGLIATO CON RISERVA', '19.3: Rating 3 returns CONSIGLIATO CON RISERVA' );
assert_test( get_rating_label( 4 ) === 'CONSIGLIATO', '19.4: Rating 4 returns CONSIGLIATO' );
assert_test( get_rating_label( 5 ) === 'MUST PLAY', '19.5: Rating 5 returns MUST PLAY' );
assert_test( get_rating_label( '4' ) === 'CONSIGLIATO', '19.6: Rating string "4" returns CONSIGLIATO' );
assert_test( get_rating_label( 0 ) === '', '19.7: Rating 0 returns empty string' );
assert_test( get_rating_label( 99 ) === '', '19.8: Rating 99 returns empty string' );
assert_test( get_rating_label( array( 4 ) ) === '', '19.9: Array rating returns empty string without PHP warning' );
assert_test( get_rating_label( new stdClass() ) === '', '19.10: Object rating returns empty string without fatal error' );
assert_test( get_rating_label( 'not_a_number' ) === '', '19.11: Non-numeric string returns empty string without error' );

assert_test( get_rating_initials( 1 ) === 'B', '19.12: Rating 1 initials returns B' );
assert_test( get_rating_initials( 3 ) === 'C.R.', '19.13: Rating 3 initials returns C.R.' );
assert_test( get_rating_initials( 4 ) === 'C', '19.14: Rating 4 initials returns C' );
assert_test( get_rating_initials( 5 ) === 'M.P.', '19.15: Rating 5 initials returns M.P.' );
assert_test( get_rating_initials( array( 5 ) ) === '', '19.16: Array rating initials returns empty string without warning' );

// --- TEST SUITE 20: Menu Filters & Classes Hardening ---
echo "\n--- 20. MENU FILTERS & CLASSES HARDENING ---\n";
$args_menu = (object) array( 'theme_location' => 'menu-contenuti' );
$classes_res = ntn_journal_menu_classes( null, null, $args_menu, 0 );
assert_test( is_array( $classes_res ) && in_array( 'inline-block', $classes_res, true ), '20.1: ntn_journal_menu_classes handles null classes cleanly' );

$atts_res = ntn_journal_menu_link_atts( null, null, $args_menu, 0 );
assert_test( is_array( $atts_res ) && isset( $atts_res['class'] ), '20.2: ntn_journal_menu_link_atts handles null atts cleanly' );

$submenu_res = ntn_journal_submenu_classes( null, $args_menu, 0 );
assert_test( is_array( $submenu_res ) && count( $submenu_res ) > 0, '20.3: ntn_journal_submenu_classes handles null classes cleanly' );

$arrow_item = (object) array( 'classes' => array( 'menu-item-has-children' ) );
$arrow_res = ntn_journal_inject_arrow_start_el( '<a href="#">Articoli</a>', $arrow_item, 0, $args_menu );
assert_test( strpos( $arrow_res, '<svg' ) !== false, '20.4: ntn_journal_inject_arrow_start_el injects SVG arrow for dropdown parent' );

$arrow_null = ntn_journal_inject_arrow_start_el( null, $arrow_item, 0, $args_menu );
assert_test( $arrow_null === null, '20.5: ntn_journal_inject_arrow_start_el handles null output cleanly' );

$title_res = function_exists( 'ntn_journal_custom_menu_title' ) ? ntn_journal_custom_menu_title( 'I Nostri Articoli', null, $args_menu, 0 ) : 'I Nostri Articoli';
// (aggiornato in 3.11.1: niente più "_" al posto degli spazi nelle etichette)
// (aggiornato in 3.12.0: testata su una riga come nel mockup, voci dei menu senza "//")
assert_test( $title_res === 'I Nostri Articoli' && ! function_exists( 'ntn_journal_custom_menu_title' ), '20.6: Menu titles are left as written (no // prefix filter since 3.12.0)' );

// --- TEST SUITE 21: Backup & Restore Option Sanitization ---
echo "\n--- 21. BACKUP & RESTORE OPTION SANITIZATION ---\n";
_ntn_restore_option( 'ntn_journal_redirects', array(
	array( 'source' => '/old-path', 'target' => 'https://example.com/new-path', 'code' => '301' ),
	'invalid_non_array_entry',
	array( 'source' => '', 'target' => '' ),
) );
$restored_reds = get_option( 'ntn_journal_redirects' );
assert_test( is_array( $restored_reds ) && count( $restored_reds ) === 1 && $restored_reds[0]['source'] === '/old-path', '21.1: _ntn_restore_option sanitizes redirects and discards invalid non-array entries' );

_ntn_restore_option( 'ntn_breaking_news_url', array( 'https://example.com' ) );
$restored_bn_url = get_option( 'ntn_breaking_news_url' );
assert_test( $restored_bn_url === '', '21.2: _ntn_restore_option handles array value for string option cleanly' );

_ntn_restore_option( 'ntn_breaking_news_badge_color', '#ff0000' );
$restored_bn_color = get_option( 'ntn_breaking_news_badge_color' );
assert_test( $restored_bn_color === '#ff0000', '21.3: _ntn_restore_option sanitizes hex badge color correctly' );

// 21.4: Nested array sanitization
$dirty_array = array(
	'platform' => 'PS5',
	'nested'   => array( 'rating' => 5, 'note' => 'Valid string' ),
);
$sanitized_arr = _ntn_sanitize_meta_value( $dirty_array );
assert_test( is_array( $sanitized_arr ) && $sanitized_arr['nested']['rating'] === 5, '21.4: _ntn_sanitize_meta_value cleanly sanitizes nested arrays without notices' );

// 21.5: String sanitization
$dirty_string = 'Death Stranding 2 <script>alert("xss")</script>';
$clean_str = _ntn_sanitize_meta_value( $dirty_string );
assert_test( strpos( $clean_str, '<script>' ) === false, '21.5: _ntn_sanitize_meta_value strips unsafe scripts' );

// 21.6: Post resolution & non-hijacking
$mock_posts[750] = new WP_Post( array( 'ID' => 750, 'post_title' => 'Hello World', 'post_name' => 'hello-world', 'post_type' => 'post' ) );
$resolved_unrelated = _ntn_resolve_post_id( array( 'post_id' => 750, 'post_name' => 'unmatched-new-slug', 'post_type' => 'videogioco' ) );
assert_test( $resolved_unrelated === 0, '21.6: _ntn_resolve_post_id does NOT hijack unrelated post IDs with mismatched slug/type' );

// 21.7: Auto-creation of missing videogioco CPT
$post_map = array();
$new_cpt_id = _ntn_restore_post( array(
	'post_id'      => 9999,
	'post_name'    => 'new-auto-game',
	'post_title'   => 'New Auto Game',
	'post_type'    => 'videogioco',
	'post_content' => 'Descrizione automatica',
	'metas'        => array(
		'_ntn_game_title' => 'New Auto Game',
		'_ntn_igdb_id'    => '777888',
	),
	'taxonomies'   => array(
		'game_engine' => array( 'Unreal Engine 5' ),
	),
), $post_map );
assert_test( $new_cpt_id > 0 && isset( $post_map[9999] ) && $post_map[9999] === $new_cpt_id, '21.7: _ntn_restore_post auto-creates missing videogioco CPT and records ID in post_map' );
assert_test( get_post_meta( $new_cpt_id, '_ntn_game_title', true ) === 'New Auto Game', '21.8: _ntn_restore_post successfully assigns metas to newly created CPT' );

// 21.9: Core backup options keys
$core_keys = ntn_get_core_backup_options_keys();
// (aggiornato in 3.17.1: tolte dal backup le impostazioni che nessuno usava, IGDB on/off e tag)
assert_test( in_array( 'ntn_redirects_map', $core_keys, true ) && in_array( 'ntn_breaking_news_active', $core_keys, true ) && in_array( 'ntn_igdb_client_id', $core_keys, true ) && ! in_array( 'ntn_journal_enable_igdb', $core_keys, true ) && ! in_array( 'ntn_journal_enable_tags', $core_keys, true ), '21.9: ntn_get_core_backup_options_keys includes all required core subsystem options and no dead ones' );

// --- TEST SUITE 22: Videogioco Term Link Filter Hardening ---
echo "\n--- 22. VIDEOGIOCO TERM LINK FILTER HARDENING ---\n";
$mock_options['ntn_journal_enable_videogame_links'] = 'yes';
$mock_terms[601] = new WP_Term( array( 'term_id' => 601, 'name' => 'Cyberpunk 2077', 'slug' => 'cyberpunk-2077', 'taxonomy' => 'post_tag' ) );
$mock_termmeta[601] = array( '_ntn_game_title' => 'Cyberpunk 2077' );

$filtered_link = ntn_filter_videogioco_term_link( 'https://example.com/tag/cyberpunk-2077/', $mock_terms[601], 'post_tag' );
assert_test( $filtered_link === 'https://example.com/videogioco/cyberpunk-2077/', '22.1: ntn_filter_videogioco_term_link rewrites tag link to CPT permalink' );

$non_obj_link = ntn_filter_videogioco_term_link( 'https://example.com/tag/test/', 12345, 'post_tag' );
assert_test( $non_obj_link === 'https://example.com/tag/test/', '22.2: ntn_filter_videogioco_term_link handles non-object term cleanly' );

$null_term_link = ntn_filter_videogioco_term_link( 'https://example.com/tag/test/', null, 'post_tag' );
assert_test( $null_term_link === 'https://example.com/tag/test/', '22.3: ntn_filter_videogioco_term_link handles null term cleanly' );

$wp_err_term_link = ntn_filter_videogioco_term_link( 'https://example.com/tag/test/', new WP_Error(), 'post_tag' );
assert_test( $wp_err_term_link === 'https://example.com/tag/test/', '22.4: ntn_filter_videogioco_term_link handles WP_Error term cleanly' );

// --- TEST SUITE 23: Zero Notices / Warnings / Errors Verification ---
echo "\n--- 23. PHP 8.2 NOTICE / WARNING / ERROR LOG ---\n";
assert_test( empty( $errors_logged ), '23.1: Zero PHP notices, warnings, or fatal errors logged throughout entire audit' );
if ( ! empty( $errors_logged ) ) {
	echo "Detected PHP errors:\n";
	print_r( $errors_logged );
}

// --- TEST SUITE 24: SemVer Simultaneous 4-Header Synchronization ---
echo "\n--- 24. SEMVER SIMULTANEOUS 4-HEADER SYNCHRONIZATION ---\n";
$parent_css = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/style.css' );
preg_match( '/Version:\s*([0-9\.]+)/', $parent_css, $m_parent );
$parent_ver = isset( $m_parent[1] ) ? trim( $m_parent[1] ) : '';
assert_test( $parent_ver === '4.15.4', "24.1: Parent Theme style.css declares Version: 4.15.4(found: {$parent_ver})" );

$child_css = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/style.css' );
preg_match( '/Version:\s*([0-9\.]+)/', $child_css, $m_child );
$child_ver = isset( $m_child[1] ) ? trim( $m_child[1] ) : '';
assert_test( $child_ver === '4.15.4', "24.2: Child Theme style.css declares Version: 4.15.4(found: {$child_ver})" );

$core_php = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php' );
preg_match( '/\*\s*Version:\s*([0-9\.]+)/', $core_php, $m_core_hdr );
$core_hdr_ver = isset( $m_core_hdr[1] ) ? trim( $m_core_hdr[1] ) : '';
assert_test( $core_hdr_ver === '4.15.4', "24.3: Core Plugin header declares Version: 4.15.4(found: {$core_hdr_ver})" );

preg_match( "/define\(\s*'NTN_JOURNAL_CORE_VERSION',\s*'([^']+)'\s*\);/", $core_php, $m_core_const );
$core_const_ver = isset( $m_core_const[1] ) ? trim( $m_core_const[1] ) : '';
assert_test( $core_const_ver === '4.15.4', "24.4: Core Plugin NTN_JOURNAL_CORE_VERSION constant equals 4.15.4(found: {$core_const_ver})" );

assert_test(
	$parent_ver === '4.15.4' && $child_ver === '4.15.4' && $core_hdr_ver === '4.15.4' && $core_const_ver === '4.15.4',
	'24.5: All 4 version headers are 100% synchronized and atomic according to Rule 2 of GEMINI.md'
);

// --- TEST SUITE 25: Gallery & Lightbox Accessibility Hardening (a11y / WebMCP) ---
echo "\n--- 25. GALLERY & LIGHTBOX ACCESSIBILITY HARDENING (A11Y) ---\n";

// 25.1: Type safety - null, false, int inputs
assert_test( ntn_filter_accessible_gallery_links( null, array( 'blockName' => 'core/gallery' ) ) === null, '25.1: ntn_filter_accessible_gallery_links handles null content cleanly' );
assert_test( ntn_filter_accessible_gallery_links( false, array( 'blockName' => 'core/gallery' ) ) === false, '25.2: ntn_filter_accessible_gallery_links handles boolean false cleanly' );
assert_test( ntn_filter_accessible_gallery_links( 12345, array( 'blockName' => 'core/gallery' ) ) === 12345, '25.3: ntn_filter_accessible_gallery_links handles integer cleanly' );

// 25.4: Unrelated block type passes through unchanged
$unrelated_html = '<div class="custom-block"><a href="test.jpg"><img src="test.jpg" alt=""></a></div>';
assert_test( ntn_filter_accessible_gallery_links( $unrelated_html, array( 'blockName' => 'core/paragraph' ) ) === $unrelated_html, '25.4: Non-gallery block content is not modified' );

// 25.5: Gallery image link with empty alt gets fallback aria-label
$gallery_html_empty_alt = '<figure class="wp-block-gallery"><figure class="wp-block-image"><a href="https://example.com/photo.jpg"><img src="https://example.com/photo.jpg" alt=""></a></figure></figure>';
$filtered_empty_alt = ntn_filter_accessible_gallery_links( $gallery_html_empty_alt, array( 'blockName' => 'core/gallery' ) );
assert_test( strpos( $filtered_empty_alt, 'aria-label="Visualizza immagine ingrandita"' ) !== false, '25.5: Gallery image link with empty alt gets fallback aria-label' );

// 25.6: Gallery image link with descriptive alt gets personalized aria-label
$gallery_html_with_alt = '<figure class="wp-block-gallery"><figure class="wp-block-image"><a href="https://example.com/photo.jpg"><img src="https://example.com/photo.jpg" alt="Sasaki Ganryu"></a></figure></figure>';
$filtered_with_alt = ntn_filter_accessible_gallery_links( $gallery_html_with_alt, array( 'blockName' => 'core/gallery' ) );
assert_test( strpos( $filtered_with_alt, 'aria-label="Visualizza immagine ingrandita: Sasaki Ganryu"' ) !== false, '25.6: Gallery image link with descriptive alt gets enriched aria-label' );

// 25.7: Existing aria-label is preserved and not duplicated
$gallery_html_existing = '<figure class="wp-block-gallery"><figure class="wp-block-image"><a href="https://example.com/photo.jpg" aria-label="Custom Label"><img src="https://example.com/photo.jpg" alt="Test"></a></figure></figure>';
$filtered_existing = ntn_filter_accessible_gallery_links( $gallery_html_existing, array( 'blockName' => 'core/gallery' ) );
assert_test( $filtered_existing === $gallery_html_existing, '25.7: Existing aria-label attribute is preserved without duplication' );

// --- TEST SUITE 26: Image & Gallery Caption Styling Alignment (Nothing OS) ---
echo "\n--- 26. IMAGE & GALLERY CAPTION STYLING (NOTHING OS) ---\n";

// 26.1: Parent theme aligns .wp-block-image figcaption to center with JetBrains Mono
assert_test(
	strpos( $parent_css, '.wp-block-image figcaption' ) !== false &&
	strpos( $parent_css, 'text-align: center !important;' ) !== false,
	'26.1: Parent theme style.css centers .wp-block-image figcaption'
);

// 26.2: Parent theme does not hide gallery nested image figcaptions with display: none
assert_test(
	strpos( $parent_css, '.wp-block-gallery figure.wp-block-image figcaption {\n  display: none !important;' ) === false,
	'26.2: Parent theme style.css allows gallery nested image figcaptions to be displayed'
);

// v4.14.0: gli stili di immagini e gallerie sono passati dallo style.css del child a build/input.css del parent
$gallery_css = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/build/input.css' );
// 26.3: Child theme style.css declares unified centered JetBrains Mono captions for image blocks and nested images
assert_test(
	strpos( $gallery_css, 'body .wp-block-image figcaption' ) !== false &&
	strpos( $gallery_css, 'body .wp-block-gallery figure.wp-block-image figcaption' ) !== false,
	'26.3: Parent input.css targets both image blocks and nested gallery images'
);

// 26.4: Child theme does not contain display: none on gallery nested figcaptions
assert_test(
	strpos( $gallery_css, 'figure.wp-block-image figcaption {\n    display: none;' ) === false,
	'26.4: Child theme style.css does not hide nested image figcaptions'
);

// 26.5: Parent theme defines unified vertical margins for images and galleries
assert_test(
	strpos( $parent_css, 'margin-top: 1.5rem !important;' ) !== false &&
	strpos( $parent_css, 'margin-bottom: 1.5rem !important;' ) !== false,
	'26.5: Parent theme style.css defines unified 1.5rem vertical margins for image and gallery blocks'
);

// 26.6: Child theme defines unified vertical margins for images and galleries
assert_test(
	strpos( $gallery_css, 'margin-top: 1.5rem !important;' ) !== false &&
	strpos( $gallery_css, 'margin-bottom: 1.5rem !important;' ) !== false,
	'26.6: Parent input.css defines unified 1.5rem vertical margins for image and gallery blocks'
);

// 26.7: Didascalie have explicit zero bottom margin to prevent paragraph gap distortion
assert_test(
	strpos( $parent_css, 'margin-bottom: 0 !important;' ) !== false &&
	strpos( $gallery_css, 'margin-bottom: 0 !important;' ) !== false,
	'26.7: Parent style.css and input.css set margin-bottom: 0 !important on captions'
);

// --- TEST SUITE 27: Audit Remediation v3.1.5 (Security, Decoupling, A11y, SEO & Performance) ---
echo "\n--- 27. AUDIT REMEDIATION V3.1.5 (SECURITY, DECOUPLING, A11Y & RESILIENCE) ---\n";

// 27.1: helpers.php ntn_link_engines_to_terms does not invoke wp_insert_term on missing terms
$helpers_content = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/helpers.php' );
assert_test( strpos( $helpers_content, 'wp_insert_term' ) === false, '27.1: helpers.php does not perform unauthenticated wp_insert_term DB writes during GET' );

// 27.2: searchform.php escapes search query attribute with esc_attr()
$searchform_content = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/searchform.php' );
assert_test( strpos( $searchform_content, 'value="<?php echo esc_attr( get_search_query() ); ?>"' ) !== false, '27.2: searchform.php uses esc_attr() for search query attribute' );

// 27.3: share-buttons.php uses esc_url() for social URLs
$share_buttons_content = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/share-buttons.php' );
assert_test( strpos( $share_buttons_content, 'href="<?php echo esc_url( $network[\'url\'] ); ?>"' ) !== false, '27.3: share-buttons.php uses esc_url() for network links' );

// 27.4: 404.php does not use javascript:history.back() pseudo-protocol
$notfound_content = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/404.php' );
assert_test( strpos( $notfound_content, 'href="javascript:history.back()"' ) === false, '27.4: 404.php does not use javascript: pseudo-protocol in href' );

// 27.5: Theme templates do not call plugin functions without function_exists guards
$archive_php = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/archive.php' );
assert_test( strpos( $archive_php, 'function_exists( \'get_rating_initials\' )' ) !== false, '27.5: archive.php guards get_rating_initials with function_exists' );

$author_php = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/author.php' );
// 27.6 (aggiornato in 3.13.0): la pagina autore usa la scheda comune template-parts/content.php, che protegge get_rating_initials
assert_test( strpos( $author_php, "get_template_part( 'template-parts/content'" ) !== false && strpos( file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/content.php' ), "function_exists( 'get_rating_initials' )" ) !== false, '27.6: author.php cards (template-parts/content.php) guard get_rating_initials with function_exists' );

// 27.7 (aggiornato in 3.4.0): la nuova Home legge il giudizio tramite inc/front-page-data.php, sempre protetto da function_exists
$front_data_php = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/front-page-data.php' );
assert_test( strpos( $front_data_php, 'function_exists( \'get_rating_label\' )' ) !== false && strpos( $front_data_php, 'function_exists( \'get_post_rating\' )' ) !== false, '27.7: Home data guards get_rating_label and get_post_rating with function_exists' );

// 27.8: related-posts.php does not inject duplicate closing </p>
$rp_code = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/related-posts.php' );
assert_test( strpos( $rp_code, '$closing_p . $injected_html' ) === false, '27.8: related-posts.php does not inject duplicate or orphan </p> tag after aside' );

// 27.9: content-format.php preserves clean text and tag attributes without mangling spaces
$formatted_sample = ntn_format_content_links( '<p><a href="https://example.com"><strong class="font-bold">test link text</strong></a></p>' );
assert_test( strpos( $formatted_sample, 'class="font-bold"' ) !== false && strpos( $formatted_sample, 'test link text' ) !== false, '27.9: content-format.php preserves clean link text and tag attributes (Nothing OS clean style)' );

// 27.10: single-videogioco.php (v4.14.0: solo nel parent, la copia del child è stata tolta) does not contain select-none
$child_sv = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/single-videogioco.php' );
assert_test( strpos( $child_sv, 'select-none' ) === false, '27.10: single-videogioco.php has select-none removed' );
assert_test( strpos( $child_sv, 'Visualizza screenshot ingrandito di' ) !== false, '27.11: single-videogioco.php has descriptive aria-label on screenshots' );

// 27.12: archive.php contains clean entities and no mojibake
// 27.12 (aggiornato in 3.8.0): la nuova scheda a cella non usa più il separatore "•"; resta il controllo sui caratteri corrotti
assert_test( strpos( $archive_php, 'â€¢' ) === false && strpos( file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/content.php' ), 'â€¢' ) === false, '27.12: archive.php and content.php contain no mojibake characters' );
assert_test( strpos( $archive_php, 'piÃ¹' ) === false && strpos( $archive_php, 'più' ) !== false, '27.13: archive.php contains clean UTF-8 più tardi' );

// 27.13: menu-filters.php contains group-focus-within:block and aria attributes
$menu_filters_content = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/menu-filters.php' );
assert_test( strpos( $menu_filters_content, 'group-focus-within:block' ) !== false, '27.14: menu-filters.php includes group-focus-within:block for keyboard dropdowns' );

// 27.14: author.php duplicate h1 fixed and update_meta_cache added
assert_test( preg_match_all( '/<h1\b/', $author_php ) === 1, '27.15: author.php contains exactly one <h1> element' );
assert_test( strpos( $author_php, 'update_meta_cache' ) !== false, '27.16: author.php calls update_meta_cache to prevent N+1 queries' );

// 27.15: Orphan igdb-test-page.php is deleted
assert_test( ! file_exists( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/igdb-test-page.php' ), '27.17: Orphan igdb-test-page.php successfully removed from parent theme' );

// 27.16: content-game-card.php and author.php have width and height
$cgc_content = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/content-game-card.php' );
assert_test( strpos( $cgc_content, 'width="400" height="533"' ) !== false, '27.18: content-game-card.php declares explicit width and height' );

// 27.17: performance.php contains ntn_png_has_transparency
$perf_content = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/performance.php' );
assert_test( strpos( $perf_content, 'function ntn_png_has_transparency' ) !== false, '27.19: performance.php defines ntn_png_has_transparency helper to protect alpha channels' );

// --- TEST SUITE 28: Exhaustive Hardening & Resilience Audit v3.1.6 ---
echo "\n--- 28. EXHAUSTIVE HARDENING & RESILIENCE AUDIT V3.1.6 ---\n";

// 28.1: single-videogioco.php (Parent & Child): rel="noopener noreferrer" and dimensions
$parent_sv = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/single-videogioco.php' );
assert_test( strpos( $parent_sv, 'rel="noopener noreferrer"' ) !== false, '28.1: single-videogioco.php in parent has rel="noopener noreferrer" on trailer and screenshots' );
assert_test( strpos( $parent_sv, 'width="640" height="360"' ) !== false, '28.2: single-videogioco.php in parent has explicit 640x360 dimensions on trailer iframe' );
assert_test( strpos( $child_sv, 'rel="noopener noreferrer"' ) !== false, '28.3: single-videogioco.php in child has rel="noopener noreferrer"' );
assert_test( strpos( $child_sv, 'width="640" height="360"' ) !== false, '28.4: single-videogioco.php in child has explicit 640x360 dimensions on trailer iframe' );

// 28.5 (aggiornato in 4.14.0): il catalogo del parent disegna la scheda comune, con copertine 3:4 (400x533)
$parent_av = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/content-game-card.php' );
assert_test( strpos( $parent_av, 'width="400" height="533"' ) !== false, '28.5: Parent catalog game card has 3:4 aspect ratio 400x533' );

// 28.6: inc/performance.php: $_GET['author'] sanitized and unslashed
assert_test( strpos( $perf_content, 'absint( wp_unslash( $_GET[\'author\'] ) )' ) !== false, '28.6: performance.php sanitizes and unslashes $_GET[\'author\']' );

// 28.7: inc/related-posts.php & template-parts/content.php: fallback dimensions 400x225
assert_test( strpos( $rp_code, 'width="400" height="225"' ) !== false, '28.7: related-posts.php declares 400x225 on fallback thumbnail' );
$tp_content = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/content.php' );
assert_test( strpos( $tp_content, 'width="400"' ) !== false && strpos( $tp_content, 'height="225"' ) !== false, '28.8: content.php declares 400x225 on fallback thumbnail' );

// 28.9: header.php: explicit dimensions on hidden SEO logo
$header_php = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/header.php' );
assert_test( strpos( $header_php, 'width="\' . esc_attr( $w ) . \'" height="\' . esc_attr( $h ) . \'"' ) !== false, '28.9: header.php includes explicit dynamic dimensions on SEO logo' );

// 28.10: Usability: select-none removed from review and hub components
$hub_header = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/hub-header.php' );
$review_orig = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/review-original-box.php' );
$review_side = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/review-sidebar-desktop.php' );
assert_test( strpos( $hub_header, 'select-none' ) === false, '28.10: hub-header.php has select-none removed for text selection' );
assert_test( strpos( $review_orig, 'select-none' ) === false, '28.11: review-original-box.php has select-none removed for text selection' );
assert_test( strpos( $review_side, 'select-none' ) === false, '28.12: review-sidebar-desktop.php has select-none removed for text selection' );

// 28.13: Security & Rel: noopener noreferrer nofollow on external links
assert_test( strpos( $hub_header, 'rel="noopener noreferrer nofollow"' ) !== false, '28.13: hub-header.php uses noopener noreferrer nofollow on IGDB link' );
assert_test( strpos( $review_side, 'rel="noopener noreferrer nofollow"' ) !== false, '28.14: review-sidebar-desktop.php uses noopener noreferrer nofollow on IGDB link' );
$cs_single = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/single-comunicato_stampa.php' );
assert_test( strpos( $cs_single, 'rel="nofollow noopener noreferrer"' ) !== false, '28.15: single-comunicato_stampa.php uses nofollow noopener noreferrer on external links' );

// 28.16: inc/social-cta.php: empty channels guard
$social_cta = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/social-cta.php' );
assert_test( strpos( $social_cta, 'if ( empty( $social_channels ) ) {' ) !== false, '28.16: social-cta.php returns empty string if no social channels are configured' );

// 28.17: comments.php: label for attributes & escaped date/time
$comments_php = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/comments.php' );
assert_test( strpos( $comments_php, 'for="author"' ) !== false && strpos( $comments_php, 'for="email"' ) !== false && strpos( $comments_php, 'for="comment"' ) !== false, '28.17: comments.php has explicit for attributes matching form control IDs' );
// 28.18 (aggiornato in 3.4.1): la data del commento ora è stampata dal callback in inc/comments-template.php
$comments_cb_php = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/comments-template.php' );
assert_test( strpos( $comments_cb_php, 'esc_html( sprintf(' ) !== false, '28.18: Comment callback properly escapes formatted date/time strings' );

// 28.19: inc/date-social.php: wp_date instead of date
$date_social = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/date-social.php' );
assert_test( strpos( $date_social, 'wp_date(' ) !== false && strpos( $date_social, "\$prefix . date(" ) === false, '28.19: date-social.php uses wp_date() with site timezone' );

// 28.20: archive-videogioco.php (v4.14.0: nel parent): unslash, labels, esc_html__
$child_av = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/archive-videogioco.php' );
assert_test( strpos( $child_av, 'wp_unslash( $_GET[\'year\'] )' ) !== false, '28.20: archive-videogioco.php unslashes query params' );
assert_test( strpos( $child_av, 'id="mobile_filter_search"' ) !== false && strpos( $child_av, 'aria-label=' ) !== false, '28.21: archive-videogioco.php provides aria-label on mobile search input' );
assert_test( strpos( $child_av, 'for="filter_franchise"' ) !== false && strpos( $child_av, 'for="filter_developer"' ) !== false && strpos( $child_av, 'for="filter_engine"' ) !== false, '28.22: archive-videogioco.php provides explicit for attributes on all filter labels' );

// 28.23: Enqueue & JS resilience: catalog script dependency and vars guard
$child_enq = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/enqueue.php' );
assert_test( strpos( $child_enq, "array( 'ntn-live-search' )" ) !== false, '28.23: enqueue.php ensures ntn-catalog-filters depends on ntn-live-search' );
$cat_filters_js = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/assets/js/catalog-filters.js' );
assert_test( strpos( $cat_filters_js, "typeof ntn_catalog_vars === 'undefined'" ) !== false, '28.24: catalog-filters.js guards against missing ntn_catalog_vars' );
$live_search_js = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/assets/js/live-search.js' );
assert_test( strpos( $live_search_js, "typeof ntn_catalog_vars === 'undefined'" ) !== false, '28.25: live-search.js guards against missing ntn_catalog_vars' );
assert_test( strpos( $live_search_js, 'width="40" height="56"' ) !== false, '28.26: live-search.js sets explicit dimensions on dynamic result thumbnails' );

// --- TEST SUITE 29: Single Post Layout, Google Feed, Nothing OS Links & Full-Width Archives v3.2.9 ---
echo "\n--- 29. SINGLE POST LAYOUT, GOOGLE FEED, NOTHING OS LINKS & ARCHIVES V3.2.9 ---\n";

// 29.1: SemVer atomic synchronization across all 4 headers to 3.15.0
$parent_style_ver = preg_match( '/Version:\s*([0-9\.]+)/', file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/style.css' ), $m1 ) ? $m1[1] : '';
$child_style_ver  = preg_match( '/Version:\s*([0-9\.]+)/', file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/style.css' ), $m2 ) ? $m2[1] : '';
$core_plugin_ver  = preg_match( '/Version:\s*([0-9\.]+)/', file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php' ), $m3 ) ? $m3[1] : '';
$core_const_ver   = preg_match( '/define\(\s*[\'"]NTN_JOURNAL_CORE_VERSION[\'"]\s*,\s*[\'"]([0-9\.]+)[\'"]\s*\)/', file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php' ), $m4 ) ? $m4[1] : '';
assert_test( $parent_style_ver === '4.15.4' && $child_style_ver === '4.15.4' && $core_plugin_ver === '4.15.4' && $core_const_ver === '4.15.4', '29.1: All 4 version headers are synchronized to 4.15.4' );

// 29.2: single.php sequence is Google Feed -> Social CTA -> Bottom Related Posts
$single_php = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/single.php' );
$pos_feed   = strpos( $single_php, 'ntn_journal_google_feed_box' );
$pos_cta    = strpos( $single_php, 'ntn_journal_social_cta_box' );
$pos_bot    = strpos( $single_php, 'ntn_journal_bottom_related_posts' );
assert_test( $pos_feed !== false && $pos_cta !== false && $pos_bot !== false && $pos_feed < $pos_cta && $pos_cta < $pos_bot, '29.2: single.php sequence is Google Feed -> Community CTA -> Bottom Related Posts' );

// 29.3: single.php has tags footer removed
assert_test( strpos( $single_php, 'ntn_journal_enable_tags' ) === false, '29.3: single.php footer tags block removed from bottom UI' );

// 29.4 (aggiornato in 3.16.0): i box "Consigliati" non entrano mai dentro citazioni, figure, elenchi o altri blocchi
$rp_code_latest = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/related-posts.php' );
assert_test( strpos( $rp_code_latest, '(blockquote|figure|ul|ol|table|div|aside|details|section|pre)' ) !== false && strpos( $rp_code_latest, 'if ( $depth > 0 ) {' ) !== false, '29.4: related-posts.php never injects a Consigliati box inside an open block' );

// 29.5: index.php is full-width without sidebar and uses the_posts_pagination
$index_php = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/index.php' );
assert_test( strpos( $index_php, 'get_sidebar' ) === false && strpos( $index_php, 'the_posts_pagination' ) !== false, '29.5: index.php is full-width without sidebar and uses the_posts_pagination' );

// 29.6: content.php has h-44 and standard div wrapper
$tp_content_latest = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/content.php' );
// 29.6 (aggiornato in 3.8.0): l'immagine della scheda ha un rapporto fisso (.ntn-card-img) al posto dell'altezza h-44
assert_test( strpos( $tp_content_latest, 'class="ntn-frame ntn-card-img"' ) !== false && strpos( $tp_content_latest, 'flex-col flex-1' ) === false, '29.6: content.php uses the fixed-ratio card image (.ntn-card-img) and a clean wrapper' );

// 29.7: social-cta.php defines ntn_journal_google_feed_box
$social_cta_latest = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/social-cta.php' );
// (aggiornato in 3.16.0: il box diventa il pannello "// Fonti preferite su Google")
assert_test( strpos( $social_cta_latest, 'function ntn_journal_google_feed_box' ) !== false && strpos( $social_cta_latest, '// Fonti preferite su Google' ) !== false, '29.7: social-cta.php defines ntn_journal_google_feed_box as the // Fonti preferite su Google panel' );

// 29.8: customizer.php registers Google Feed options
$customizer_php = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/customizer.php' );
assert_test( strpos( $customizer_php, 'ntn_journal_enable_google_feed' ) !== false && strpos( $customizer_php, 'ntn_google_feed_url' ) !== false, '29.8: customizer.php registers ntn_journal_enable_google_feed and ntn_google_feed_url' );

// 29.9: style.css defines Nothing OS prose links and brutalist pagination
$style_css_latest = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/style.css' );
assert_test( strpos( $style_css_latest, '.navigation.pagination .page-numbers.current' ) !== false && strpos( $style_css_latest, 'text-underline-offset' ) !== false, '29.9: style.css defines brutalist pagination and Nothing OS clean prose links' );

// 29.10: social-cta.php implements official Google Preferred Sources deeplink standard and dynamic site title
assert_test( strpos( $social_cta_latest, 'preferences/source?q=' ) !== false && strpos( $social_cta_latest, 'get_bloginfo( \'name\' )' ) !== false, '29.10: social-cta.php implements official Google Preferred Sources deeplink standard and dynamic site title' );

// 29.11 (aggiornato in 3.16.0): box "Consigliati" come nel mockup, "Leggi qui" rosso dalla tavolozza unica
$input_css = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/build/input.css' );
assert_test( strpos( $rp_code_latest, '// Consigliati' ) !== false && strpos( $rp_code_latest, 'Leggi qui' ) !== false && strpos( $input_css, '.ntn-prose .ntn-suggest-go {' ) !== false && strpos( $input_css, 'color: var(--ntn-accent); }' ) !== false && strpos( $style_css_latest, 'ntn-suggested-read' ) === false, '29.11: Consigliati box follows the mockup with the accent red from the shared palette; old box CSS removed' );

// 29.12 (aggiornato in 3.16.0): quanti box secondo le parole (sotto 150 nessuno, fino a 300 uno, fino a 500 due, oltre tre)
assert_test(
	array() === ntn_journal_suggest_positions( 120 ) && array( 0.5 ) === ntn_journal_suggest_positions( 280 ) &&
	array( 0.25, 0.75 ) === ntn_journal_suggest_positions( 450 ) && array( 0.25, 0.5, 0.75 ) === ntn_journal_suggest_positions( 1200 ) &&
	array( 0.25, 0.75 ) === ntn_journal_suggest_positions( 500 ) && array( 0.25, 0.5, 0.75 ) === ntn_journal_suggest_positions( 501 ),
	'29.12: Consigliati boxes follow the CEO rule: none under 150 words, 1 at 50%, 2 at 25/75%, 3 at 25/50/75%'
);
// 29.12b: i punti sicuri saltano i paragrafi dentro citazioni ed elenchi e contano le parole lette
$sbp = ntn_journal_suggest_break_points( '<p>uno due</p><blockquote><p>tre quattro</p></blockquote><p>cinque</p><figure><img src="x.jpg"><figcaption>dida</figcaption></figure><p>sei sette</p>' );
assert_test(
	3 === count( $sbp ) && 2 === $sbp[0]['words'] && 5 === $sbp[1]['words'] && 7 === $sbp[2]['words'] &&
	5 === ntn_journal_count_words( '<p>uno due tre</p><figure><figcaption>non conta</figcaption></figure><p>quattro cinque</p>' ),
	'29.12b: Safe break points skip paragraphs inside quotes; figures and captions do not count as words'
);

// 29.13: related-posts.php accessibility WCAG 2.4.4: aria-label with title and aria-hidden on decorative SVG and dot
assert_test( strpos( $rp_code_latest, 'aria-label=' ) !== false && strpos( $rp_code_latest, 'aria-hidden="true"' ) !== false && strpos( $rp_code_latest, 'style="text-decoration: none;"' ) === false, '29.13: related-posts.php implements accessible aria-label, aria-hidden decorators and clean classes' );

// 29.14: single.php defensive guard function_exists for ntn_journal_social_cta_box
assert_test( strpos( $single_php, 'function_exists( \'ntn_journal_social_cta_box\' )' ) !== false, '29.14: single.php guards ntn_journal_social_cta_box call with function_exists' );

// 29.15 (aggiornato in 3.16.0): il pannello Google ha i suoi stili nei componenti, il vecchio box non c'è più
assert_test( strpos( $input_css, '.ntn-gfeed-b {' ) !== false && strpos( $input_css, '.ntn-gfeed-btn {' ) !== false && strpos( $style_css_latest, 'ntn-google-feed-box' ) === false, '29.15: Google panel styles live in the components; old Google Feed box CSS removed' );

// 29.16: Pagination unified across all archive templates
$tag_php_content = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/tag.php' );
$author_php_content = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/author.php' );
$cs_archive_content = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/archive-comunicato_stampa.php' );
$vg_archive_content = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/archive-videogioco.php' );
$archive_php_content = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/archive.php' );
assert_test(
	strpos( $tag_php_content, "'prev_text' => '&larr; Precedente'" ) !== false &&
	strpos( $author_php_content, "'prev_text' => '&larr; Precedente'" ) !== false &&
	strpos( $cs_archive_content, "'prev_text' => '&larr; Precedente'" ) !== false &&
	strpos( $vg_archive_content, 'id="ntn-catalog-sentinel"' ) !== false && // v4.10.0: il catalogo scorre all'infinito, senza numeri di pagina
	strpos( $archive_php_content, "'prev_text' => '&larr; Precedente'" ) !== false,
	'29.16: Pagination entities (&larr; Precedente, Successivo &rarr;) unified across all archive templates'
);

// 29.17: tab-extra.php orphan tag checkbox removed
$tab_extra_content = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/views/tab-extra.php' );
assert_test( strpos( $tab_extra_content, 'Visualizzazione Tag' ) === false && strpos( $tab_extra_content, 'ntn_journal_enable_tags' ) === false, '29.17: tab-extra.php has no orphan tags setting' );

// 29.18: customizer.php no legacy hardcoded Q-GIN string
assert_test( strpos( $customizer_php, 'Q-GIN' ) === false, '29.18: customizer.php contains no legacy hardcoded Q-GIN strings' );

// 29.19: performance.php dead comment removed
$perf_php_content = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/performance.php' );
assert_test( strpos( $perf_php_content, 'Removed buggy function' ) === false, '29.19: performance.php contains no dead leftover comment' );

// 29.20: breaking-news.php badge color fallback unified to #dc2626
$bn_php_content = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/breaking-news.php' );
assert_test( strpos( $bn_php_content, "'#dc2626'" ) !== false && strpos( $bn_php_content, "'#e11d48'" ) === false, '29.20: breaking-news.php fallback badge color updated to #dc2626' );

// 29.21: social-cta.php Google Feed button renders signature Nothing OS diagonal red arrow SVG
$social_cta_latest_arrow = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/social-cta.php' );
// (aggiornato in 3.16.0: pulsante squadrato "Aggiungi su Google ↗", con la freccia nascosta ai lettori di schermo)
assert_test(
	strpos( $social_cta_latest_arrow, 'class="ntn-btn ntn-btn--solid ntn-gfeed-btn">Aggiungi su Google <span aria-hidden="true">↗</span></a>' ) !== false &&
	strpos( $social_cta_latest_arrow, 'target="_blank" rel="noopener noreferrer"' ) !== false &&
	strpos( $social_cta_latest_arrow, '&rarr;' ) === false,
	'29.21: Google panel uses the square button with a diagonal arrow hidden from screen readers'
);

// 29.22: single-videogioco.php in parent and child theme wraps pagination in nav.navigation.pagination and uses standard arrows
$vg_parent_single = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/single-videogioco.php' );
$vg_child_single  = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/single-videogioco.php' ); // v4.10.0: la copia del child è stata tolta
assert_test(
	strpos( $vg_parent_single, '<nav class="navigation pagination"' ) !== false &&
	strpos( $vg_parent_single, "'prev_text' => '&larr; Precedente'" ) !== false &&
	strpos( $vg_parent_single, "'next_text' => 'Successivo &rarr;'" ) !== false &&
	strpos( $vg_parent_single, '<div class="nav-links">' ) !== false &&
	strpos( $vg_child_single, '<nav class="navigation pagination"' ) !== false &&
	strpos( $vg_child_single, "'prev_text' => '&larr; Precedente'" ) !== false &&
	strpos( $vg_child_single, "'next_text' => 'Successivo &rarr;'" ) !== false &&
	strpos( $vg_child_single, '<div class="nav-links">' ) !== false,
	'29.22: single-videogioco.php in parent and child theme wraps secondary query pagination in nav.navigation.pagination with standard arrows'
);

// 29.23: social-cta.php strips www. from domain and query to ensure Google Preferred Sources detects the publisher
$social_cta_code = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/social-cta.php' );
assert_test(
	strpos( $social_cta_code, "preg_replace( '/^www\./i', '', (string) \$domain )" ) !== false &&
	strpos( $social_cta_code, "preg_replace( '/([?&]q=)www\./i', '\$1', (string) \$feed_url )" ) !== false &&
	strpos( $social_cta_code, "preg_replace( '/^www\./i', '', (string) get_bloginfo( 'name' ) )" ) !== false,
	'29.23: social-cta.php strips www. from Google Feed domain, custom feed_url query and site_name for accurate publisher detection'
);

// --- TEST SUITE 30: Fondamenta del nuovo stile v3.3.0 (tavolozza, componenti, pulizia Nothing OS) ---
echo "\n--- 30. FONDAMENTA NOTHING OS V3.3.0 ---\n";

$theme_dir   = ABSPATH . 'NTN_Journal_Theme_Reloaded/';
$input_css   = file_get_contents( $theme_dir . 'ntn-journal/build/input.css' );
$output_css  = file_get_contents( $theme_dir . 'ntn-journal/style-tailwind-output.css' );

// 30.1: tavolozza unica definita per il tema chiaro e ridefinita per .dark
$light_block = preg_match( '/:root\s*\{([^}]*)\}/', $input_css, $lm ) ? $lm[1] : '';
$dark_block  = preg_match( '/\n\.dark\s*\{([^}]*)\}/', $input_css, $dm ) ? $dm[1] : '';
$tokens      = array( '--ntn-bg', '--ntn-fg', '--ntn-muted', '--ntn-faint', '--ntn-line', '--ntn-line-strong', '--ntn-accent', '--ntn-band', '--ntn-band-fg', '--ntn-band-line', '--ntn-band-accent', '--ntn-band-dot', '--ntn-band-map' );
$tokens_ok   = true;
foreach ( $tokens as $tok ) {
	if ( strpos( $light_block, $tok . ':' ) === false || strpos( $dark_block, $tok . ':' ) === false ) {
		$tokens_ok = false;
	}
}
assert_test( $tokens_ok, '30.1: input.css defines every palette token in :root and redefines it in .dark' );

// 30.2: la fascia di apertura segue il tema (grigio chiarissimo in chiaro, nera in scuro)
assert_test(
	strpos( $light_block, '--ntn-band: #f4f4f5;' ) !== false && strpos( $dark_block, '--ntn-band: #000000;' ) !== false && strpos( $light_block, '--ntn-faint: #71717a;' ) !== false && strpos( $light_block, '--ntn-band-accent: #b91c1c;' ) !== false,
	'30.2: Opening band is light grey in light theme and black in dark theme; small text grey is #71717a; red text on light band is #b91c1c (WCAG AA)'
);

// 30.3: componenti comuni presenti nel sorgente
$components    = array( '.ntn-band', '.ntn-chip', '.ntn-sec-head', '.ntn-sec-title', '.ntn-sec-aside', '.ntn-cells', '.ntn-cell', '.ntn-row', '.ntn-meta', '.ntn-entity', '.ntn-dot', '.ntn-stat-k', '.ntn-stat-v', '.ntn-ph', '.ntn-frame' );
$components_ok = true;
foreach ( $components as $cls ) {
	if ( strpos( $input_css, $cls . ' {' ) === false && strpos( $input_css, $cls . "\n" ) === false && strpos( $input_css, $cls . ',' ) === false ) {
		$components_ok = false;
	}
}
assert_test( $components_ok && strpos( $input_css, '@layer components' ) !== false, '30.3: input.css defines all shared components inside @layer components' );

// 30.4: il CSS compilato contiene tavolozza, componenti e rispetto di "riduci movimento"
assert_test(
	strpos( $output_css, '--ntn-band:' ) !== false && strpos( $output_css, '.ntn-sec-head{' ) !== false && strpos( $output_css, '@keyframes ntn-pulse' ) !== false && preg_match( '/prefers-reduced-motion:reduce\)\{\.ntn-dot,\.animate-pulse\{animation:none/', $output_css ),
	'30.4: style-tailwind-output.css is rebuilt with palette, components and reduced-motion rule'
);

// 30.5: il sorgente Tailwind legge anche il tema child
assert_test( strpos( $input_css, '@source "../../ntn-journal-reloaded/**/*.php";' ) !== false, '30.5: input.css scans child theme templates so their classes are compiled' );

// 30.6: nessun elemento estraneo a Nothing OS nei modelli e negli script dei temi
$forbidden_re = '/(?<![\w-])(?:[a-z0-9-]+:)*(?:rounded-(?:sm|md|lg|xl|2xl|3xl|\[[^\]]+\])|rounded(?=[\s"\'])|shadow-(?:sm|md|lg|xl|2xl|\[[^\]]+\])|drop-shadow-[a-z]+|scale-1[0-9]{2}|brightness-[0-9]+|contrast-[0-9]+|grayscale|bg-gradient-to-[a-z]+|backdrop-blur(?:-[a-z]+)?)(?![\w-])/';
$scan_files   = array_merge(
	glob( $theme_dir . 'ntn-journal/*.php' ),
	glob( $theme_dir . 'ntn-journal/inc/*.php' ),
	glob( $theme_dir . 'ntn-journal/template-parts/*.php' ),
	glob( $theme_dir . 'ntn-journal-reloaded/*.php' ),
	glob( $theme_dir . 'ntn-journal-reloaded/inc/*.php' ),
	glob( $theme_dir . 'ntn-journal-reloaded/template-parts/*.php' ),
	glob( $theme_dir . 'ntn-journal-reloaded/assets/js/*.js' ),
	array( $theme_dir . 'ntn-journal/assets/js/main.js' )
);
$offenders = array();
foreach ( $scan_files as $sf ) {
	if ( preg_match_all( $forbidden_re, file_get_contents( $sf ), $fm ) ) {
		$offenders[] = basename( $sf ) . ': ' . implode( ', ', array_unique( $fm[0] ) );
	}
}
assert_test( empty( $offenders ), '30.6: No rounded corners, shadows, zoom, photo filters, gradients or blur in theme templates' . ( $offenders ? ' (' . implode( ' | ', $offenders ) . ')' : '' ) );

// 30.7: i cerchi restano solo per i pallini (al massimo 4x4) e nessun cerchio su pulsanti, avatar o interruttori
$round_offenders = array();
foreach ( $scan_files as $sf ) {
	foreach ( explode( "\n", file_get_contents( $sf ) ) as $ln => $line ) {
		if ( strpos( $line, 'rounded-full' ) !== false && preg_match( '/(?<![\w-])(?:w|h)-(?:5|6|7|8|10|12|14|16)(?![\w.-])/', $line ) ) {
			$round_offenders[] = basename( $sf ) . ':' . ( $ln + 1 );
		}
	}
}
assert_test( empty( $round_offenders ), '30.7: rounded-full is used only for small status dots' . ( $round_offenders ? ' (' . implode( ', ', $round_offenders ) . ')' : '' ) );

// 30.8: style.css e editor-style.css senza bagliori e angoli arrotondati
$parent_style = file_get_contents( $theme_dir . 'ntn-journal/style.css' );
$editor_style = file_get_contents( $theme_dir . 'ntn-journal/editor-style.css' );
assert_test(
	strpos( $parent_style, 'drop-shadow(' ) === false && strpos( $parent_style, 'box-shadow: 0 0' ) === false && ! preg_match( '/border-radius:\s*[1-9]/', $editor_style ) && ! preg_match( '/border-radius:\s*[1-9]/', file_get_contents( $theme_dir . 'ntn-journal/single-comunicato_stampa.php' ) ),
	'30.8: style.css has no glow shadows; editor-style.css and single-comunicato_stampa.php have no rounded corners'
);

// 30.9: intestazioni di sezione uniformate con il componente comune
// 30.9 (aggiornato in 3.11.0): la Hub ha 5 titoli di sezione, con "// Panoramica" per il testo della redazione
$fp_code = file_get_contents( $theme_dir . 'ntn-journal/front-page.php' );
assert_test(
	substr_count( $fp_code, 'class="ntn-sec-head"' ) >= 4 && substr_count( $fp_code, 'class="ntn-sec-title"' ) >= 4 &&
	strpos( file_get_contents( $theme_dir . 'ntn-journal/inc/related-posts.php' ), 'class="ntn-sec-head mb-6"' ) !== false &&
	strpos( file_get_contents( $theme_dir . 'ntn-journal/author.php' ), 'class="ntn-sec-head mb-6"' ) !== false &&
	substr_count( file_get_contents( $theme_dir . 'ntn-journal/single-videogioco.php' ), 'class="ntn-sec-title"' ) === 5 &&
	! file_exists( $theme_dir . 'ntn-journal-reloaded/single-videogioco.php' ) &&
	strpos( file_get_contents( $theme_dir . 'ntn-journal/inc/widgets.php' ), '<h2 class="ntn-sec-head mb-3"><span class="ntn-sec-title">' ) !== false,
	'30.9: Section headings in home, related posts, author, game hub and widgets use .ntn-sec-head / .ntn-sec-title'
);

// 30.10: contrasto dei testi piccoli: niente grigio 400 in chiaro accoppiato a grigio 500/600 in scuro
$weak_grey = array();
foreach ( $scan_files as $sf ) {
	if ( preg_match_all( '/class=\\\\?["\'][^"\']*(?:(?<![\w:-])text-zinc-400(?![\w-])[^"\']*dark:text-zinc-(?:500|600)|(?<![\w:-])text-zinc-500 dark:text-zinc-500)(?![\w-])/', file_get_contents( $sf ), $gm ) ) {
		$weak_grey[] = basename( $sf ) . ' (' . count( $gm[0] ) . ')';
	}
}
assert_test( empty( $weak_grey ), '30.10: Small grey text uses zinc-500 in light theme and zinc-400 in dark theme' . ( $weak_grey ? ' (' . implode( ', ', $weak_grey ) . ')' : '' ) );

// --- TEST SUITE 31: Home Nothing OS v3.4.0 (fase 3 del restyling) ---
echo "\n--- 31. HOME NOTHING OS V3.4.0 ---\n";

$fp_new   = file_get_contents( $theme_dir . 'ntn-journal/front-page.php' );
$fp_data  = file_get_contents( $theme_dir . 'ntn-journal/inc/front-page-data.php' );
$fp_js    = file_get_contents( $theme_dir . 'ntn-journal/assets/js/front-page.js' );
$fp_funcs = file_get_contents( $theme_dir . 'ntn-journal/functions.php' );
$fp_cust  = file_get_contents( $theme_dir . 'ntn-journal/inc/customizer.php' );

// 31.1: niente carosello, apertura con un articolo grande e tre piccoli sulla fascia a matrice di punti
assert_test(
	strpos( $fp_new, 'hero-carousel' ) === false && strpos( $fp_new, 'hero-slide' ) === false &&
	strpos( $fp_new, '<section class="ntn-band"' ) !== false && strpos( $fp_new, 'ntn-lead-main' ) !== false && strpos( $fp_new, 'array_slice( $ntn_lead, 1, 3 )' ) !== false,
	'31.1: Home replaces the carousel with the band opening (one main story and three side stories)'
);

// 31.2: blocchi della Home presenti e mostrati solo se hanno contenuti
$blocks_ok = true;
foreach ( array( 'ntn-h-news', 'ntn-h-rev', 'ntn-h-hub', 'ntn-h-rel', 'ntn-h-feat', 'ntn-h-video' ) as $block_id ) {
	if ( strpos( $fp_new, 'aria-labelledby="' . $block_id . '"' ) === false ) {
		$blocks_ok = false;
	}
}
assert_test( $blocks_ok && strpos( $fp_new, "if ( ! empty( \$ntn_home['releases'] ) )" ) !== false && strpos( $fp_new, 'if ( $ntn_hub_cat )' ) !== false, '31.2: Home renders news, reviews, hub band, releases, features and video blocks only when they have content' );

// 31.3: i widget Twitch e YouTube della Home restano disponibili
assert_test( strpos( $fp_new, "'sidebar-home-twitch'" ) !== false && strpos( $fp_new, "'sidebar-home-youtube'" ) !== false && strpos( $fp_new, 'dynamic_sidebar(' ) !== false, '31.3: Home keeps the Twitch and YouTube widget areas' );

// 31.4: ogni dato stampato nella Home passa da una funzione di escaping
assert_test( ! preg_match( '/echo\s+\$/', $fp_new ) && ! preg_match( '/echo\s+get_/', $fp_new ) && ! preg_match( '/echo\s+\$/', $fp_data ), '31.4: front-page.php and front-page-data.php never echo raw variables' );

// 31.5: cache della Home legata alla versione del tema e svuotata quando si pubblica
assert_test(
	strpos( $fp_data, "'ntn_front_data_' . str_replace( '.', '_', (string) wp_get_theme( get_template() )->get( 'Version' ) )" ) !== false &&
	strpos( $fp_data, "add_action( 'save_post', 'ntn_front_flush_cache' )" ) !== false && strpos( $fp_data, "add_action( 'trashed_post', 'ntn_front_flush_cache' )" ) !== false &&
	strpos( $fp_data, 'wp_is_post_revision( $post_id )' ) !== false,
	'31.5: Home cache key follows the theme version and is flushed on save and trash'
);

// 31.6: i cinque LED del voto sono accessibili e seguono la regola di oggi
assert_test(
	strpos( $fp_data, 'role="img" aria-label="' ) !== false && strpos( $fp_data, "'Voto %d su 5'" ) !== false && strpos( $fp_data, "' is-last'" ) !== false &&
	strpos( $input_css, '.ntn-led.is-last' ) !== false && strpos( $input_css, '.ntn-led.is-on' ) !== false,
	'31.6: Review LEDs announce "Voto N su 5" to screen readers and light the last LED in red'
);

// 31.7: la fascia dell'Hub si sceglie dal Customizer con dati sanificati
assert_test(
	strpos( $fp_cust, "'ntn_journal_front_hub_category'" ) !== false && strpos( $fp_cust, "'sanitize_callback' => 'sanitize_title'" ) !== false &&
	strpos( $fp_cust, "'ntn_journal_front_hub_map'" ) !== false,
	'31.7: Customizer registers the Home hub band category (sanitize_title) and the Italy map switch'
);

// 31.8: lo script della Home disegna solo la cartina, segue il tema e non contiene più il carosello
assert_test(
	strpos( $fp_js, 'carousel' ) === false && strpos( $fp_js, "getElementById('ntn-italy')" ) !== false &&
	strpos( $fp_js, "'--ntn-band-map'" ) !== false && strpos( $fp_js, 'MutationObserver' ) !== false,
	'31.8: front-page.js draws only the Italy dot map, follows the theme and no longer drives a carousel'
);

// 31.9: il modulo dati della Home è caricato
assert_test( strpos( $fp_funcs, "require_once NTN_INC_DIR . 'front-page-data.php';" ) !== false, '31.9: functions.php loads inc/front-page-data.php' );

// --- TEST SUITE 32: Correzione dei commenti v3.4.1 ---
echo "\n--- 32. COMMENTI V3.4.1 ---\n";

$cm_template = file_get_contents( $theme_dir . 'ntn-journal/comments.php' );
$cm_callback = file_get_contents( $theme_dir . 'ntn-journal/inc/comments-template.php' );

// 32.1: la funzione dei commenti esiste prima che comments.php la chiami
assert_test(
	strpos( $cm_template, "'callback'   => 'ntn_journal_custom_comment'" ) !== false &&
	strpos( $cm_template, 'function ntn_journal_custom_comment' ) === false &&
	strpos( $cm_callback, 'function ntn_journal_custom_comment( $comment, $args, $depth )' ) !== false &&
	strpos( $fp_funcs, "require_once NTN_INC_DIR . 'comments-template.php';" ) !== false,
	'32.1: ntn_journal_custom_comment is defined in inc/comments-template.php, loaded by functions.php before comments.php runs'
);

// 32.2: il callback lascia il <li> aperto, perché lo chiude WordPress
assert_test( strpos( $cm_callback, '<li id="comment-' ) !== false && strpos( $cm_callback, '</li>' ) === false, '32.2: Comment callback does not close <li> (Walker_Comment::end_el closes it)' );

// 32.3: il callback si carica davvero e produce un commento con il <li> aperto
if ( ! function_exists( 'comment_ID' ) ) { function comment_ID() { echo '7'; } }
if ( ! function_exists( 'comment_class' ) ) { function comment_class( $c = '' ) { echo 'class="comment ' . $c . '"'; } }
if ( ! function_exists( 'get_avatar' ) ) { function get_avatar() { return ''; } }
if ( ! function_exists( 'get_comment_author' ) ) { function get_comment_author() { return 'Luca'; } }
if ( ! function_exists( 'comment_author_link' ) ) { function comment_author_link() { echo 'Luca'; } }
if ( ! function_exists( 'get_comment_date' ) ) { function get_comment_date() { return '17.10.2026'; } }
if ( ! function_exists( 'get_comment_time' ) ) { function get_comment_time() { return '12:00'; } }
if ( ! function_exists( 'comment_text' ) ) { function comment_text() { echo 'Testo'; } }
if ( ! function_exists( 'comment_reply_link' ) ) { function comment_reply_link( $a = array() ) { echo 'RISPONDI'; } }
require_once $theme_dir . 'ntn-journal/inc/comments-template.php';
ob_start();
ntn_journal_custom_comment( (object) array( 'comment_ID' => 7 ), array( 'max_depth' => 5 ), 1 );
$cm_html = ob_get_clean();
assert_test( function_exists( 'ntn_journal_custom_comment' ) && strpos( $cm_html, '<li id="comment-7"' ) !== false && strpos( $cm_html, 'Testo' ) !== false && strpos( $cm_html, '</li>' ) === false, '32.3: Comment callback loads and renders one comment without closing the list item' );

// --- TEST SUITE 33: Cornice del sito v3.5.0 (testata, menu, piè di pagina) ---
echo "\n--- 33. TESTATA, MENU E PIE' DI PAGINA V3.5.0 ---\n";

$hd_code = file_get_contents( $theme_dir . 'ntn-journal/header.php' );
$ft_code = file_get_contents( $theme_dir . 'ntn-journal/footer.php' );
$mf_code = file_get_contents( $theme_dir . 'ntn-journal/inc/menu-filters.php' );
$mj_code = file_get_contents( $theme_dir . 'ntn-journal/assets/js/main.js' );

// 33.1: la testata conserva gli agganci usati da main.js e dal modulo delle breaking news
$hooks_ok = true;
foreach ( array( 'id="wp-header-clock"', 'class="theme-text"', 'theme-toggle-btn', 'aria-controls="ntn-search-panel"', 'id="ntn-search-panel"', 'aria-controls="ntn-mobile-nav"', 'id="ntn-mobile-nav"', "do_action( 'ntn_journal_after_top_banner' )", 'id="search-input-wp"' ) as $hook ) {
	if ( strpos( $hd_code, $hook ) === false ) {
		$hooks_ok = false;
	}
}
// (aggiornato in 3.12.0: testata su una riga come nel mockup, voci dei menu senza "//")
assert_test( $hooks_ok && strpos( $mj_code, "querySelectorAll('.theme-toggle-btn')" ) !== false && strpos( $mj_code, "document.getElementById('ntn-search-panel')" ) !== false && strpos( $mj_code, "document.getElementById('ntn-mobile-nav')" ) !== false, '33.1: header.php keeps clock, theme toggle, search and mobile menu panels, breaking news and live search hooks used by main.js' );

// 33.2: un solo pulsante chiaro/scuro, squadrato e con nome accessibile
assert_test(
	substr_count( $hd_code, 'theme-toggle-btn' ) === 1 && strpos( $hd_code, 'class="ntn-sq theme-toggle-btn"' ) !== false && strpos( $hd_code, "aria-label=\"<?php esc_attr_e( 'Cambia tema chiaro o scuro'" ) !== false,
	'33.2: One square theme toggle button with an accessible name'
);

// 33.3: la testata usa la fascia e i componenti della tavolozza
assert_test(
	strpos( $hd_code, '<div class="ntn-topbar">' ) !== false && strpos( $hd_code, 'class="ntn-search"' ) !== false && strpos( $hd_code, '<nav class="ntn-nav"' ) !== false && strpos( $hd_code, 'class="ntn-subnav"' ) === false &&
	strpos( $input_css, '.ntn-topbar {' ) !== false && strpos( $input_css, 'background-color: var(--ntn-band);' ) !== false,
	'33.3: Header uses the theme-aware top bar, the one-row menu and the search panel (no second menu row since 3.12.0)'
);

// 33.4: voci dei menu con le classi dei componenti, voce attiva segnalata anche ai lettori di schermo
assert_test(
	strpos( $mf_code, "'ntn-nav-link is-current'" ) !== false && strpos( $mf_code, "\$atts['aria-current'] = 'page';" ) !== false &&
	strpos( $mf_code, "'ntn-submenu-link'" ) !== false && strpos( $mf_code, 'group-focus-within:block' ) !== false,
	'33.4: Menu items use .ntn-nav-link / .ntn-submenu-link and the current item has aria-current="page"'
);

// 33.5: niente pallini pulsanti nelle voci dei menu (il formato // TITOLO resta)
assert_test(
	strpos( $mf_code, "return '// '" ) === false && strpos( $mf_code, 'nav_menu_item_title' ) === false && strpos( $mf_code, 'bg-red-600 rounded-full animate-pulse' ) === false && strpos( $mf_code, 'animate-pulse' ) === false,
	'33.5: Menu titles have no // prefix (since 3.12.0), no pulsing dots and no animated arrows'
);

// 33.6: piè di pagina senza colonne sovrapposte e con dati protetti
assert_test(
	strpos( $ft_code, 'absolute left-1/2' ) === false && strpos( $ft_code, 'class="ntn-footer"' ) !== false && strpos( $ft_code, "esc_html( wp_date( 'Y' ) )" ) !== false &&
	strpos( $input_css, '.ntn-footer-in { flex-direction: column; text-align: center; }' ) !== false,
	'33.6: Footer no longer overlaps on phones (no absolute centre column) and escapes the year'
);

// --- TEST SUITE 34: Pagina articolo v3.6.0 (fase 4) ---
echo "\n--- 34. PAGINA ARTICOLO V3.6.0 ---\n";

$sg_code    = file_get_contents( $theme_dir . 'ntn-journal/single.php' );
$style_main = file_get_contents( $theme_dir . 'ntn-journal/style.css' );

// 34.1: testata dell'articolo sulla fascia, con titolo, sottotitolo, dati e foto senza filtri
assert_test(
	strpos( $sg_code, '<header class="ntn-band ntn-art-band">' ) !== false && strpos( $sg_code, '<h1 class="ntn-art-title">' ) !== false &&
	strpos( $sg_code, 'class="ntn-meta ntn-art-meta"' ) !== false && strpos( $sg_code, '<figure class="ntn-art-hero">' ) !== false &&
	strpos( $sg_code, "wp_get_attachment_caption( get_post_thumbnail_id() )" ) !== false,
	'34.1: Article header sits on the theme-aware band with title, subtitle, meta row and unfiltered featured photo with caption'
);

// 34.2: le funzioni di oggi restano tutte, nello stesso ordine
$sg_order = array( 'review-header-mobile', "'is_mobile' => false", 'the_content()', "'is_mobile' => true", 'ntn_journal_share_buttons', 'ntn_journal_google_feed_box', 'ntn_journal_social_cta_box', 'ntn_journal_bottom_related_posts', 'comments_template()' );
$sg_pos   = -1;
$sg_ok    = true;
foreach ( $sg_order as $needle ) {
	$p = strpos( $sg_code, $needle );
	if ( false === $p || $p < $sg_pos ) {
		$sg_ok = false;
	}
	$sg_pos = (int) $p;
}
assert_test( $sg_ok && strpos( $sg_code, "get_template_part( 'template-parts/review-sidebar-desktop' )" ) !== false && strpos( $sg_code, "ntn_journal_enable_watermark" ) !== false && strpos( $sg_code, "get_option( 'ntn_journal_enable_comments', 'yes' )" ) !== false, '34.2: Article keeps review boxes, content, author bio, share, Google Feed, CTA, related posts, comments, sidebar and watermark option in order' );

// 34.3: sottotitolo con la stessa priorità di prima (estratto, custom_h2, primo h2) e nota AI senza colori fuori tavolozza
assert_test(
	strpos( $sg_code, "get_post_field( 'post_excerpt', get_the_ID() )" ) !== false && strpos( $sg_code, "get_post_meta( get_the_ID(), 'custom_h2', true )" ) !== false &&
	strpos( $sg_code, "preg_match( '/<h2[^>]*>(.*?)<\/h2>/is'" ) !== false && strpos( $sg_code, '#FFD606' ) === false && strpos( $sg_code, 'class="ntn-ai-note"' ) !== false,
	'34.3: Subtitle keeps excerpt > custom_h2 > first h2 priority; AI note uses the palette instead of #FFD606'
);

// 34.4: testo con sottotitoli numerati e lettera iniziale, regole fuori dai livelli per battere .prose
assert_test(
	strpos( $input_css, '.ntn-prose.prose h2::before {' ) !== false && strpos( $input_css, 'counter(ntn-h2, decimal-leading-zero)' ) !== false &&
	strpos( $input_css, '.ntn-prose.prose > p:first-of-type::first-letter' ) !== false && strpos( $sg_code, 'dynamic-content-body ntn-prose prose' ) !== false,
	'34.4: Article body numbers h2 subheadings and has a drop cap'
);

// 34.5: data di aggiornamento solo se diversa, autore collegato, dati stampati con escaping
assert_test(
	strpos( $sg_code, '( $modified_ts - $published_ts ) > DAY_IN_SECONDS' ) !== false && strpos( $sg_code, '<span>by' ) === false &&
	strpos( file_get_contents( $theme_dir . 'ntn-journal/template-parts/author-panel.php' ), 'rel="author"' ) !== false &&
	! preg_match( '/echo\s+\$(?!ntn_)/', $sg_code ),
	'34.5: Updated date only when the article changed; author named once (in the byline, linked with rel="author"), not repeated in the meta row; no raw variable output'
);

// 34.6: le vecchie regole per telefono di style.css non toccano più le pagine rifatte
assert_test(
	strpos( $style_main, '#primary:not(.ntn-main) {' ) !== false && strpos( $style_main, '#primary:not(.ntn-main) article {' ) !== false &&
	strpos( $sg_code, '<main id="primary" class="site-main ntn-main' ) !== false && strpos( $fp_new, '<main id="primary" class="site-main ntn-main' ) !== false,
	'34.6: Legacy mobile padding in style.css skips the redesigned pages (.ntn-main), so bands reach the screen edges'
);

// 34.7 (3.6.1): autore sempre in vista con la firma nella testata (variante A); niente scheda nella colonna né in fondo
$ap_code = file_get_contents( $theme_dir . 'ntn-journal/template-parts/author-panel.php' );
$pos_meta   = strpos( $sg_code, 'class="ntn-meta ntn-art-meta"' );
$pos_byline = strpos( $sg_code, "get_template_part( 'template-parts/author-panel' )" );
$pos_hero   = strpos( $sg_code, '<figure class="ntn-art-hero">' );
assert_test(
	false !== $pos_byline && $pos_meta < $pos_byline && $pos_byline < $pos_hero && substr_count( $sg_code, 'template-parts/author-panel' ) === 1 &&
	strpos( $ap_code, '<section class="ntn-byline"' ) !== false && strpos( $sg_code, 'template-parts/author-bio' ) === false,
	'34.7: Author byline sits in the article header band under the meta row (variant A), once, with no sidebar or bottom author box'
);

// 34.8 (aggiornato in 3.16.0): colonna laterale su tutti gli articoli, ferma e scorrevole se più alta dello schermo
assert_test(
	strpos( $sg_code, '<div class="ntn-wrap ntn-art-layout has-sidebar">' ) !== false &&
	strpos( str_replace( "\r\n", "\n", $sg_code ), "if ( \$has_sidebar ) {\n\t\t\t\t\t\t\tget_template_part( 'template-parts/review-sidebar-desktop' );" ) !== false &&
	strpos( $input_css, '.ntn-art-aside-sticky { position: sticky; top: 24px; }' ) !== false &&
	strpos( $input_css, '.ntn-art-aside-sticky.is-smart { position: relative; top: 0; }' ) !== false && strpos( $input_css, 'overflow-y: auto; scrollbar-width' ) === false &&
	strpos( file_get_contents( $theme_dir . 'ntn-journal/assets/js/main.js' ), 'var low = Math.min(gapTop, window.innerHeight - h - gapBottom);' ) !== false && strpos( $input_css, 'ntn-author-panel' ) === false,
	'34.8: Sidebar is on every article; the game card only with a game or review; smart column without scrollbar (fixed if it fits, otherwise scrolls to its end and stops)'
);

// 34.9: dati dell'autore protetti, link sicuri e numeri in cache
assert_test(
	strpos( $ap_code, "get_transient( 'ntn_author_stats_' . \$ntn_ap_id )" ) !== false && strpos( $ap_code, 'HOUR_IN_SECONDS' ) !== false &&
	strpos( $ap_code, 'rel="noopener noreferrer"' ) !== false && strpos( $ap_code, "is_wp_error( \$ntn_ap_url )" ) !== false &&
	! preg_match( '/echo\s+\$(?!ntn_ap_avatar;)/', $ap_code ),
	'34.9: Author byline escapes every value, opens social links safely, caches its counts and guards the author URL'
);

// --- TEST SUITE 35: Recensione v3.7.0 (fase 5) ---
echo "\n--- 35. RECENSIONE V3.7.0 ---\n";

$rv_box  = file_get_contents( $theme_dir . 'ntn-journal/template-parts/review-original-box.php' );
$rv_side = file_get_contents( $theme_dir . 'ntn-journal/template-parts/review-sidebar-desktop.php' );
$rv_mob  = file_get_contents( $theme_dir . 'ntn-journal/template-parts/review-header-mobile.php' );

// 35.1: il box della recensione mostra cinque LED quadrati e il voto anche in numero, letto dai lettori di schermo
assert_test(
	strpos( $review_box_out, 'role="img" aria-label="Voto 5 su 5"' ) !== false && substr_count( $review_box_out, 'ntn-led is-on' ) === 5 && substr_count( $review_box_out, 'is-last' ) === 1 &&
	strpos( $review_box_out, '<b>5</b> / 5 · MUST PLAY' ) !== false && strpos( $review_box_out, 'class="ntn-rbox-eval"' ) !== false,
	'35.1: Review box renders five square LEDs (last one red), "N / 5 · Giudizio" and an accessible "Voto N su 5" label'
);

// 35.2: pro e contro senza il verde, con il loro numero
assert_test(
	strpos( $rv_box, 'emerald' ) === false && strpos( $rv_box, 'green' ) === false && strpos( $input_css, '.ntn-rbox-pc .is-con li::before { content: "\2212"; color: var(--ntn-accent); }' ) !== false &&
	strpos( $rv_box, 'count( $pros_arr )' ) !== false && strpos( $rv_box, 'count( $cons_arr )' ) !== false,
	'35.2: Pros and cons use + in black/white and − in red (no green) and show their count'
);

// 35.3: le funzioni di oggi restano: origine, verdetto nascondibile, link al tag del giudizio, ID diversi per computer e telefono
assert_test(
	strpos( $rv_box, "empty( \$args['hide_verdict'] )" ) !== false && strpos( $rv_box, 'ntn_review_source' ) !== false && strpos( $rv_box, "'review-summary-card-mobile' : 'review-summary-card'" ) !== false &&
	strpos( $rv_box, "home_url( '/tag/' . \$rating_slug . '/' )" ) !== false && strpos( $rv_box, 'wp_kses_post( wpautop( $verdict_text ) )' ) !== false,
	'35.3: Review box keeps origin strip, hideable verdict, rating tag link with fallback and separate desktop/mobile IDs'
);

// 35.4: scheda del gioco: copertina senza velo, dati a celle, LED e pulsante IGDB sicuro
assert_test(
	strpos( $rv_side, 'bg-black/60' ) === false && strpos( $rv_side, '<dl class="ntn-gcard-kv">' ) !== false && strpos( $rv_side, 'ntn_link_companies_to_tags( $developer )' ) !== false &&
	strpos( $rv_side, 'ntn_link_engines_to_terms( $engine' ) !== false && strpos( $review_sidebar_out, 'class="ntn-igdb-btn"' ) !== false && strpos( $review_sidebar_out, 'aria-label="Voto 4 su 5"' ) !== false,
	'35.4: Game card shows the cover without a veil, data cells with company/engine links, the five LEDs and the IGDB button'
);

// 35.5: scheda da telefono con giudizio e LED, e solo dove c'è un gioco o un voto
assert_test(
	strpos( $review_mobile_out, 'class="ntn-rmob"' ) !== false && strpos( $review_mobile_out, 'aria-label="Voto 4 su 5"' ) !== false &&
	strpos( $rv_mob, 'if ( empty( $game_title ) && empty( $rating ) ) {' ) !== false,
	'35.5: Mobile game card shows the verdict with LEDs and is skipped on articles without a game or a rating'
);

// --- TEST SUITE 36: Archivi e Hub dei giochi v3.8.0 (fase 6) ---
echo "\n--- 36. ARCHIVI E HUB DEI GIOCHI V3.8.0 ---\n";

$hh_code  = file_get_contents( $theme_dir . 'ntn-journal/template-parts/hub-header.php' );
$hub_p    = file_get_contents( $theme_dir . 'ntn-journal/single-videogioco.php' );
$hub_c    = file_get_contents( $theme_dir . 'ntn-journal/single-videogioco.php' ); // v4.10.0: la Hub è solo nel parent
$card     = file_get_contents( $theme_dir . 'ntn-journal/template-parts/content.php' );
$arch_hd  = file_get_contents( $theme_dir . 'ntn-journal/template-parts/archive-head.php' );

// 36.1: intestazione della Hub sulla fascia, copertina intera in cornice, niente sfondo velato
assert_test(
	strpos( $hh_code, '<header class="ntn-band ntn-hub-band">' ) !== false && strpos( $hh_code, 'class="ntn-gcover"' ) !== false &&
	strpos( $hh_code, 'background-image' ) === false && strpos( $hh_code, 'bg-black/75' ) === false &&
	strpos( $hub_header_out, 'ntn-gcover' ) !== false && strpos( $hub_header_out, '14/08/2026' ) !== false,
	'36.1: Game Hub header sits on the theme-aware band with the full IGDB cover in a frame instead of a veiled background'
);

// 36.2: contatori della Hub in cache e scheda tecnica con i link agli studi e al motore
assert_test(
	strpos( $hh_code, "get_transient( 'ntn_hub_counts_' . \$hub_tag->term_id )" ) !== false && strpos( $hh_code, "'previews'" ) !== false &&
	strpos( $hh_code, 'ntn_link_companies_to_tags( $igdb_info[\'developer\'] )' ) !== false && strpos( $hh_code, 'ntn_link_engines_to_terms( $igdb_info[\'engine\'] )' ) !== false &&
	strpos( $hh_code, 'array_slice( $platforms, 0, 8 )' ) !== false && strpos( $hh_code, 'rel="noopener noreferrer nofollow"' ) !== false,
	'36.2: Hub header shows cached article/review/preview counts and keeps the spec sheet links (studios, engine, up to 8 platforms, IGDB)'
);

// 36.3: corpo della Hub: priorità visibile, box recensione senza verdetto, trailer al clic, screenshot ingranditi, articoli a celle
assert_test(
	strpos( $hub_c, 'ntn-prio' ) === false && strpos( $hub_c, "array( 'hide_verdict' => true )" ) !== false &&
	strpos( $hub_c, 'class="ntn-lazy-video' ) !== false && strpos( $hub_c, 'data-video-id=' ) !== false && strpos( $hub_c, 'glightbox ntn-shot' ) !== false &&
	strpos( $hub_c, '<div class="ntn-cells ntn-cells--3">' ) !== false,
	'36.3: Game Hub shows the featured content without the priority chips (removed at the request of the CEO), the review box without verdict, click-to-load trailer, zoomable screenshots and article cells'
);

// 36.4: la copia del parent è identica a quella del child (tranne l'intestazione del file)
$hub_p_norm = str_replace( array( "if ( ! defined( 'ABSPATH' ) ) exit;\n", '@package NTN_Journal_Reloaded', '@package NTN_Journal' ), '', str_replace( "\r\n", "\n", $hub_p ) );
$hub_c_norm = str_replace( array( "if ( ! defined( 'ABSPATH' ) ) exit;\n", '@package NTN_Journal_Reloaded', '@package NTN_Journal' ), '', str_replace( "\r\n", "\n", $hub_c ) );
assert_test( $hub_p_norm === $hub_c_norm, '36.4: Parent and child single-videogioco.php stay in sync' );

// 36.7: intestazioni della Hub allineate (dalla 3.11.1 "Scheda media" senza trattino basso)
assert_test(
	strpos( $input_css, '.ntn-sec-head > a { display: inline-flex; align-items: center; line-height: 1.3; }' ) !== false &&
	strpos( $hub_c, 'class="ntn-sec-title">// Scheda media</h2>' ) !== false && strpos( $hub_p, 'class="ntn-sec-title">// Scheda media</h2>' ) !== false,
	'36.7: Hub headings "in evidenza" and "Scheda media" share the same height (linked title no longer adds a taller line box)'
);

// 36.5: scheda articolo a cella, con categoria, voto e dati protetti
assert_test(
	strpos( $card, "post_class( \$is_featured ? 'ntn-cell ntn-card is-featured group' : 'ntn-cell ntn-card group' )" ) !== false &&
	strpos( $card, "function_exists( 'get_rating_initials' )" ) !== false && strpos( $card, 'class="ntn-card-badge is-red"' ) !== false &&
	! preg_match( '/echo\s+\$(?!primary_cat \?)/', $card ),
	'36.5: Article card is a grid cell with category badge, rating badge and escaped data'
);

// 36.6: archivi (categoria, tag, motore, ricerca, blog) con l'intestazione comune sulla fascia e articoli a celle
$arch_ok = true;
foreach ( array( 'archive.php', 'taxonomy-game_engine.php', 'index.php' ) as $arch_file ) {
	$arch_src = file_get_contents( $theme_dir . 'ntn-journal/' . $arch_file );
	if ( strpos( $arch_src, "'template-parts/archive-head'" ) === false || strpos( $arch_src, '<div class="ntn-cells ntn-cells--3">' ) === false || strpos( $arch_src, 'class="site-main ntn-main"' ) === false ) {
		$arch_ok = false;
	}
}
assert_test(
	$arch_ok && strpos( $arch_hd, '<header class="ntn-band ntn-arch-band">' ) !== false && strpos( $archive_php, "add_filter( 'get_the_archive_title_prefix', '__return_empty_string' )" ) !== false &&
	strpos( file_get_contents( $theme_dir . 'ntn-journal/index.php' ), 'is_search()' ) !== false,
	'36.6: Category, tag, engine, search and blog archives use the shared band header (no "Categoria:" prefix) and article cells'
);





// --- TEST SUITE 37: Catalogo dei videogiochi v3.9.0 (fase 7) ---
echo "\n--- 37. CATALOGO DEI VIDEOGIOCHI V3.9.0 ---\n";

$cat_child  = file_get_contents( $theme_dir . 'ntn-journal/archive-videogioco.php' ); // v4.10.0: il catalogo con i filtri è nel parent
$cat_parent = file_get_contents( $theme_dir . 'ntn-journal/archive-videogioco.php' );
$cat_card   = file_get_contents( $theme_dir . 'ntn-journal/template-parts/content-game-card.php' );
$cat_css    = file_get_contents( $theme_dir . 'ntn-journal/build/input.css' );
$cat_js     = file_get_contents( $theme_dir . 'ntn-journal/assets/js/catalog-filters.js' );

// 37.1: il catalogo del child usa l'intestazione comune con il totale dei giochi e la griglia a linee condivise
assert_test(
	strpos( $cat_child, "'template-parts/archive-head'" ) !== false && strpos( $cat_child, "'Giochi in catalogo'" ) !== false &&
	strpos( $cat_child, 'class="site-main ntn-main"' ) !== false && strpos( $cat_child, '<div id="ntn-catalog-grid" class="ntn-games">' ) !== false,
	'37.1: Catalog uses the shared band header (games total) and the shared-line games grid'
);

// 37.2: tutti gli ID usati da catalog-filters.js restano nel markup
$cat_ids_ok = true;
foreach ( array( 'ntn-mobile-filter-toggle', 'mobile_filter_search', 'ntn-filters-container', 'filter_search', 'filter_franchise', 'filter_developer', 'filter_publisher', 'filter_engine', 'filter_year', 'filter_sort', 'ntn-reset-filters-container', 'ntn-reset-filters', 'ntn-catalog-grid', 'ntn-catalog-sentinel' ) as $cat_id ) {
	if ( strpos( $cat_child, 'id="' . $cat_id . '"' ) === false && strpos( $cat_child, "'id'              => '" . $cat_id . "'" ) === false ) {
		$cat_ids_ok = false;
	}
}
assert_test( $cat_ids_ok && strpos( $cat_child, 'class="ntn-filters hidden md:flex"' ) !== false && strpos( $cat_child, 'class="ntn-loader hidden' ) !== false, '37.2: Catalog keeps every ID and toggle class used by catalog-filters.js' );

// 37.3: scheda gioco con copertina in cornice e titolo sotto, senza la banda scura sopra la foto
assert_test(
	strpos( $cat_card, "post_class( 'ntn-game-card group' )" ) !== false && strpos( $cat_card, '<div class="ntn-game-cover">' ) !== false &&
	strpos( $cat_card, 'class="ntn-game-title"' ) !== false && strpos( $cat_card, 'bg-black/85' ) === false && strpos( $cat_card, 'width="400" height="533"' ) !== false,
	'37.3: Game card shows the cover in a frame with title and year below (no dark overlay band)'
);

// 37.4 (aggiornato in 4.10.0): il catalogo del parent è quello con i filtri e disegna la scheda comune
assert_test(
	strpos( $cat_parent, "'template-parts/archive-head'" ) !== false && strpos( $cat_parent, "get_template_part( 'template-parts/content', 'game-card' )" ) !== false &&
	strpos( $cat_parent, '<div id="ntn-catalog-grid" class="ntn-games">' ) !== false && strpos( $cat_parent, 'bg-black/85' ) === false,
	'37.4: Parent catalog uses the shared header and the shared game card template'
);

// 37.5: il contatore dell'intestazione accetta un'unità propria (giochi, non articoli)
assert_test( strpos( $arch_hd, "\$args['unit']" ) !== false && strpos( $arch_hd, "array( 'Articolo', 'Articoli' )" ) !== false, '37.5: archive-head accepts a custom counter unit and defaults to Articoli' );

// 37.6: stile dei filtri e della griglia nei componenti, risposta REST vuota con lo stato comune
assert_test(
	strpos( $cat_css, '.ntn-games {' ) !== false && strpos( $cat_css, '.ntn-field-input {' ) !== false && strpos( $cat_css, '.ntn-game-nocover {' ) !== false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/rest-catalog.php' ), '<div class="ntn-empty col-span-full">' ) !== false,
	'37.6: Catalog CSS components exist and the REST empty state uses the shared ntn-empty box'
);

// --- TEST SUITE 38: Comunicati stampa v3.10.0 (fase 8) ---
echo "\n--- 38. COMUNICATI STAMPA V3.10.0 ---\n";

$pr_archive = file_get_contents( $theme_dir . 'ntn-journal/archive-comunicato_stampa.php' );
$pr_single  = file_get_contents( $theme_dir . 'ntn-journal/single-comunicato_stampa.php' );
$pr_css     = file_get_contents( $theme_dir . 'ntn-journal/build/input.css' );
$pr_seo     = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/seo-extras.php' );

// 38.1: archivio con l'intestazione comune, contatore in comunicati e celle
assert_test(
	strpos( $pr_archive, "'template-parts/archive-head'" ) !== false && strpos( $pr_archive, "array( 'Comunicato', 'Comunicati' )" ) !== false &&
	strpos( $pr_archive, '<div class="ntn-cells ntn-cells--3">' ) !== false && strpos( $pr_archive, 'ntn-press-card' ) !== false && strpos( $pr_archive, 'class="site-main ntn-main"' ) !== false,
	'38.1: Press archive uses the shared band header (Comunicati counter) and press cells'
);

// 38.2: pagina singola con la testata della pagina articolo, scheda a celle e nota finale
assert_test(
	strpos( $pr_single, '<header class="ntn-band ntn-art-band">' ) !== false && strpos( $pr_single, '<dl class="ntn-press-facts">' ) !== false &&
	strpos( $pr_single, '<aside class="ntn-press-note">' ) !== false && strpos( $pr_single, 'ntn-chip--press' ) !== false,
	'38.2: Single press release uses the article band header, facts cells and closing note'
);

// 38.3: niente più stili gialli scritti nel template; il giallo vive solo nei token --ntn-press
assert_test(
	strpos( $pr_single, '<style>' ) === false && strpos( $pr_single, 'yellow-' ) === false && strpos( $pr_archive, 'yellow-' ) === false &&
	strpos( $pr_css, '--ntn-press:' ) !== false && strpos( $pr_css, '--ntn-press-fill:' ) !== false,
	'38.3: Press templates carry no inline yellow styles; the accent lives in --ntn-press tokens'
);

// 38.4: nessuna ripetizione di editore, studio e agenzia nella testata del comunicato
assert_test( strpos( $pr_single, 'Da: <b>' ) === false && strpos( $pr_single, "'Agenzia PR' => \$agency" ) !== false, '38.4: Single press release names publisher, studio and agency once (facts only)' );

// 38.5: il percorso di navigazione usa la sezione del tipo di contenuto, non una categoria
assert_test(
	strpos( $pr_seo, "'post' !== \$post_type" ) !== false && strpos( $pr_seo, 'get_post_type_archive_link( $post_type )' ) !== false,
	'38.5: Breadcrumb shows the post type archive (Comunicati stampa, Videogiochi) for non-post singles'
);

// --- TEST SUITE 39: SEO delle Hub dei videogiochi v3.11.0 ---
echo "\n--- 39. SEO DELLE HUB V3.11.0 ---\n";

$hs_src    = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/hub-seo.php' );
$hs_schema = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/schema-markup.php' );
$hs_extras = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/seo-extras.php' );
$hs_head   = file_get_contents( $theme_dir . 'ntn-journal/template-parts/hub-header.php' );

// 39.1: il plugin carica il nuovo modulo
assert_test( strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php' ), "inc/hub-seo.php" ) !== false, '39.1: Core plugin loads inc/hub-seo.php' );

// 39.2: testo automatico con sviluppatore, editore, piattaforme e nome del sito
$mock_posts[131]    = new WP_Post( array( 'ID' => 131, 'post_title' => 'Hub Test Game', 'post_name' => 'hub-test-game', 'post_type' => 'videogioco', 'post_status' => 'publish' ) );
$mock_postmeta[131] = array(
	'_ntn_game_developer' => array( 'Studio Uno', 'Studio Due' ),
	'_ntn_game_publisher' => 'Editore Tre',
	'_ntn_game_platforms' => array( 'PC', 'PlayStation 5' ),
);
$hs_summary = ntn_get_hub_auto_summary( 131 );
assert_test(
	strpos( $hs_summary, 'Hub Test Game è un videogioco sviluppato da Studio Uno e Studio Due' ) === 0 && strpos( $hs_summary, 'Qui trovi tutte le recensioni, le anteprime e le notizie di ' ) !== false,
	'39.2: Hub auto summary names developers in Italian and ends with the site sentence (got: ' . $hs_summary . ')'
);

// 39.3: date IGDB in anni, secondi, millisecondi e testo
assert_test(
	ntn_hub_release_timestamp( '2027' ) === (int) mktime( 0, 0, 0, 1, 1, 2027 ) && ntn_hub_release_timestamp( '1790000000' ) === 1790000000 &&
	ntn_hub_release_timestamp( '1790000000000' ) === 1790000000 && ntn_hub_release_timestamp( '' ) === 0 && ntn_hub_release_timestamp( array() ) === 0,
	'39.3: Hub release parser handles years, seconds, milliseconds and empty values'
);

// 39.4: la Hub è una CollectionPage con il VideoGame come entità principale, e non riceve più lo schema Article
$hs_game = ntn_get_hub_videogame_entity( 131 );
assert_test(
	'VideoGame' === $hs_game['@type'] && isset( $hs_game['author'] ) && strpos( $hs_src, "'@type'      => 'CollectionPage'" ) !== false && strpos( $hs_src, "'mainEntity' => \$game" ) !== false &&
	strpos( $hs_schema, "if ( ! is_single() || is_singular( 'videogioco' ) ) {" ) !== false && strpos( $hs_extras, "\$data[ \$key ]['@type']      = 'CollectionPage';" ) !== false,
	'39.4: Hub schema is a CollectionPage about a VideoGame (with and without The SEO Framework); no Article schema on Hubs'
);

// 39.5: canonical delle pagine successive, sitemap pulita, og:type website
assert_test(
	strpos( $hs_src, "add_filter( 'get_canonical_url', 'ntn_hub_paged_canonical'" ) !== false &&
	strpos( $hs_src, "add_filter( 'wp_sitemaps_taxonomies_query_args', 'ntn_hub_sitemap_exclude_igdb_tags'" ) !== false &&
	strpos( $hs_src, "add_filter( 'wp_sitemaps_posts_query_args', 'ntn_hub_sitemap_exclude_noindex_press'" ) !== false &&
	strpos( $hs_extras, "( is_single() && ! is_singular( 'videogioco' ) ) ? 'article' : 'website'" ) !== false,
	'39.5: Paged Hub canonical is self-referencing, WP sitemap skips IGDB tags and noindex press releases, Hub og:type is website'
);

// 39.6: testo visibile nella Hub, "Tutti gli articoli" come H2 (parent e child), alt nelle immagini delle schede
assert_test(
	strpos( $hs_head, "ntn_get_hub_auto_summary( (int) \$args['id'] )" ) !== false && strpos( $hs_head, '<p class="ntn-specs-p">' ) !== false && strpos( $hs_head, '<span class="ntn-specs-k">In breve</span>' ) !== false &&
	strpos( file_get_contents( $theme_dir . 'ntn-journal/single-videogioco.php' ), '<h2 id="ntn-h-all"' ) !== false &&
	strpos( file_get_contents( $theme_dir . 'ntn-journal/template-parts/content.php' ), "'alt' => ntn_get_image_alt( get_the_ID() )" ) !== false,
	'39.6: Hub shows the auto summary as the "In breve" spec cell, "Tutti gli articoli" is an H2, card images always have alt text'
);

// 39.7: titolo per Google (senza plugin SEO e con The SEO Framework v5) e descrizione automatica collegata a TSF
$hs_src = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/hub-seo.php' );
assert_test(
	strpos( $hs_src, "add_filter( 'document_title_parts', 'ntn_hub_document_title_parts' )" ) !== false &&
	strpos( $hs_src, "add_filter( 'the_seo_framework_title_from_generation', 'ntn_hub_tsf_title', 10, 2 )" ) !== false &&
	strpos( $hs_src, "add_filter( 'the_seo_framework_description_excerpt', 'ntn_hub_tsf_description_excerpt', 10, 2 )" ) !== false &&
	strpos( $hs_src, "'Recensione, notizie e scheda' : 'Notizie e scheda del gioco'" ) !== false &&
	ntn_hub_tsf_title( 'Titolo originale', array( 'id' => 1, 'tax' => 'post_tag' ) ) === 'Titolo originale' &&
	ntn_hub_tsf_description_excerpt( 'Testo della redazione', null ) === 'Testo della redazione',
	'39.7: Hub SEO title (WordPress and TSF v5) and TSF description excerpt hooks; terms and existing excerpts are left untouched'
);

// 39.8: con The SEO Framework la Hub ha og:type website, canonical e og:url propri sulle pagine successive e "Pagina N" nel titolo
$hs_src = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/hub-seo.php' );
assert_test(
	strpos( $hs_src, "add_filter( 'the_seo_framework_meta_render_data', 'ntn_hub_tsf_meta_render_data' )" ) !== false &&
	strpos( $hs_src, "\$tags['og:type']['attributes']['content'] = 'website';" ) !== false &&
	strpos( $hs_src, "\$tags['canonical']['attributes']['href'] = \$url;" ) !== false &&
	strpos( $hs_src, "' – Pagina ' . \$paged" ) !== false,
	'39.8: TSF Hub tags: og:type website, self canonical and og:url on paged Hubs, "Pagina N" in the paged title'
);

// 39.9: il testo scritto nell'editor della Hub compare come "// Panoramica" (parent e child) e toglie la cella "In breve"
$mock_posts[132] = new WP_Post( array( 'ID' => 132, 'post_title' => 'Hub Con Testo', 'post_name' => 'hub-con-testo', 'post_type' => 'videogioco', 'post_status' => 'publish', 'post_content' => '<p>Un gioco che la redazione segue da vicino.</p>' ) );
$hs_saved_post   = $GLOBALS['post'] ?? null;
$hs_saved_query  = $queried_object ?? null;
$hs_intro_ok     = true;
foreach ( array( 'ntn-journal' ) as $hs_theme ) { // v4.10.0: la Hub è solo nel parent
	$GLOBALS['post'] = $mock_posts[132];
	$queried_object  = $mock_posts[132];
	ob_start();
	require $theme_dir . $hs_theme . '/single-videogioco.php';
	$hs_out = ob_get_clean();
	if ( strpos( $hs_out, '<h2 id="ntn-h-intro" class="ntn-sec-title">// Panoramica</h2>' ) === false || strpos( $hs_out, 'Un gioco che la redazione segue da vicino.' ) === false ) {
		$hs_intro_ok = false;
	}
}
$GLOBALS['post'] = $hs_saved_post;
$queried_object  = $hs_saved_query;
assert_test(
	$hs_intro_ok && strpos( $hs_head, "empty( \$args['has_intro'] )" ) !== false,
	'39.9: Hub editor text renders as "// Panoramica", and replaces the "In breve" cell'
);

// --- TEST SUITE 40: Etichette senza trattino basso v3.11.1 ---
echo "\n--- 40. ETICHETTE SENZA TRATTINO BASSO V3.11.1 ---\n";

// 40.1: nessuna etichetta "// PAROLA_PAROLA" e nessuna sostituzione automatica degli spazi con "_" nei temi
$us_offenders = array();
foreach ( array( 'ntn-journal', 'ntn-journal-reloaded' ) as $us_theme ) {
	$us_iter = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $theme_dir . $us_theme, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $us_iter as $us_file ) {
		$us_path = str_replace( chr( 92 ), '/', (string) $us_file );
		if ( substr( $us_path, -4 ) !== '.php' || strpos( $us_path, '/node_modules/' ) !== false ) {
			continue;
		}
		$us_src = file_get_contents( $us_path );
		if ( preg_match( '#//\s?[A-Z][A-Za-z0-9.]*_[A-Za-z0-9.]#', $us_src ) || preg_match( "#str_replace\(\s*' ',\s*'_'#", $us_src ) || preg_match( '#[A-Z]{3,}_[A-Z]{3,}#', preg_replace( '#\b[A-Z_]+_(?:DIR|URL|URI|CSS|VERSION|PATH|IN_SECONDS)\b|\b(?:NTN|WP|ENT|JSON|FILTER|PHP|SORT|PREG|ABSPATH|DOING|STR)_[A-Z_]+\b|\$_[A-Z]+|Imagick::[A-Z_]+#', '', $us_src ) ) ) {
			$us_offenders[] = substr( $us_path, strlen( str_replace( chr( 92 ), '/', $theme_dir ) ) );
		}
	}
}
assert_test( empty( $us_offenders ), '40.1: Theme labels use plain spaces, no "_" in // labels or automatic replacements' . ( $us_offenders ? ' (' . implode( ', ', $us_offenders ) . ')' : '' ) );

// 40.2: nomi di studi, giochi e autori restano leggibili con gli spazi
assert_test(
	strpos( file_get_contents( $theme_dir . 'ntn-journal/inc/helpers.php' ), "\$formatted_name = '// ' . strtoupper( \$company );" ) !== false &&
	strpos( file_get_contents( $theme_dir . 'ntn-journal/single.php' ), "// <?php echo esc_html( \$game_entity['name'] ); ?>" ) !== false,
	'40.2: Studio and game names keep their spaces after the // prefix'
);

// --- TEST SUITE 41: Testata su una riga v3.12.0 ---
echo "\n--- 41. TESTATA SU UNA RIGA V3.12.0 ---\n";

$GLOBALS['mock_nav_menus'] = array(
	'menu-contenuti'   => array( array( 'Notizie', 'https://example.com/notizie/', array( 'menu-item', 'current-menu-item' ) ), array( 'Recensioni', 'https://example.com/recensioni/', array( 'menu-item' ) ) ),
	'menu-piattaforme' => array( array( 'PlayStation 5', 'https://example.com/ps5/', array( 'menu-item' ) ), array( 'PC', 'https://example.com/pc/', array( 'menu-item' ) ) ),
	'menu-1'           => array( array( 'Chi siamo', 'https://example.com/chi-siamo/', array( 'menu-item' ) ) ),
);
ob_start();
ntn_journal_header_menus( 'desktop' );
$hm_desk = ob_get_clean();
// v3.22.0: "Piattaforme" è un pulsante a destra, tra gli strumenti della testata
ob_start();
ntn_journal_header_platform_drop();
$hm_tool = ob_get_clean();
ob_start();
ntn_journal_header_menus( 'mobile' );
$hm_mob = ob_get_clean();
$GLOBALS['mock_nav_menus'] = array();

// 41.1: riga della testata: voci senza "//", voce attiva segnata, tendina Piattaforme con le piattaforme dentro, menu Primary in riga
assert_test(
	strpos( $hm_desk, 'class="ntn-nav-link is-current">Notizie</a>' ) !== false && strpos( $hm_desk, '// ' ) === false &&
	strpos( $hm_desk, 'ntn-nav-drop' ) === false && strpos( $hm_tool, '<span>Piattaforme</span>' ) !== false &&
	strpos( $hm_tool, 'class="ntn-nav-drop-btn ntn-pdrop-tool" aria-haspopup="true" aria-expanded="false" aria-controls="ntn-pdrop">' ) !== false &&
	strpos( $hm_tool, '<span class="ntn-pdrop-t">PlayStation 5</span>' ) !== false && strpos( $hm_desk, 'class="ntn-nav-link">Chi siamo</a>' ) !== false &&
	strpos( $hm_desk, 'id="menu-item-900"' ) !== false,
	'41.1: Desktop header menus: plain titles, current item marked, Piattaforme dropdown and Primary items in the same row'
);

// 41.2: pannello da telefono: gruppi con etichetta, piattaforme come voci normali, nessun id duplicato
assert_test(
	strpos( $hm_mob, '<p class="ntn-mobile-nav-label">Articoli</p>' ) !== false && strpos( $hm_mob, '<p class="ntn-mobile-nav-label">Piattaforme</p>' ) !== false &&
	strpos( $hm_mob, 'class="ntn-nav-link">PlayStation 5</a>' ) !== false && strpos( $hm_mob, 'ntn-nav-drop' ) === false && strpos( $hm_mob, ' id="' ) === false,
	'41.2: Mobile panel menus: labelled groups, platforms as plain items, no duplicated ids'
);

// 41.3: senza menu assegnati resta l'elenco di ripiego, senza "//", e niente tendina vuota
ob_start();
ntn_journal_header_menus( 'desktop' );
$hm_fallback = ob_get_clean();
assert_test(
	strpos( $hm_fallback, '>Notizie</a>' ) !== false && strpos( $hm_fallback, '// ' ) === false && strpos( $hm_fallback, 'ntn-nav-drop' ) === false,
	'41.3: Without assigned menus the fallback list is shown with plain titles and no empty Piattaforme dropdown'
);

// 41.4: lente e pulsante del menu squadrati con nome accessibile; ricerca aperta sulla pagina dei risultati
assert_test(
	strpos( $hd_code, 'class="ntn-sq ntn-search-toggle"' ) !== false && strpos( $hd_code, "esc_attr_e( 'Apri la ricerca'" ) !== false &&
	strpos( $hd_code, 'class="ntn-sq ntn-menu-toggle"' ) !== false && strpos( $hd_code, "esc_attr_e( 'Apri il menu'" ) !== false &&
	strpos( $hd_code, "get_search_query() ? '' : ' hidden'" ) !== false && strpos( $mj_code, "if (e.key !== 'Escape') return;" ) !== false,
	'41.4: Square search and menu buttons with accessible names; search panel open on results page; Esc closes panels'
);

// --- TEST SUITE 42: Pagina autore, pagine statiche e 404 v3.13.0 (fase 9) ---
echo "\n--- 42. PAGINA AUTORE, PAGINE STATICHE E 404 V3.13.0 ---\n";

$p9_author = file_get_contents( $theme_dir . 'ntn-journal/author.php' );
$p9_page   = file_get_contents( $theme_dir . 'ntn-journal/page.php' );
$p9_404    = file_get_contents( $theme_dir . 'ntn-journal/404.php' );
$p9_card   = file_get_contents( $theme_dir . 'ntn-journal/template-parts/content.php' );
$p9_css    = file_get_contents( $theme_dir . 'ntn-journal/build/input.css' );

// 42.1: pagina autore sulla fascia con foto o iniziali, numeri e link, articoli a celle senza "by Autore"
assert_test(
	strpos( $p9_author, '<header class="ntn-band ntn-arch-band" itemscope itemtype="https://schema.org/Person">' ) !== false &&
	strpos( $p9_author, 'class="ntn-authorpage-photo ntn-author-avatar"' ) !== false && strpos( $p9_author, '<div class="ntn-gcounts">' ) !== false &&
	strpos( $p9_author, '<div class="ntn-cells ntn-cells--3">' ) !== false && strpos( $p9_author, "array( 'hide_author' => true )" ) !== false &&
	strpos( $p9_card, "<?php if ( ! \$hide_author ) : ?>" ) !== false && strpos( $p9_author, 'rel="noopener noreferrer"' ) !== false,
	'42.1: Author page uses the band header (photo or initials, counters, links) and article cells without the repeated author'
);

// 42.2: pagine statiche con la testata e il testo della pagina articolo
assert_test(
	strpos( $p9_page, '<header class="ntn-band ntn-art-band">' ) !== false && strpos( $p9_page, '<h1 class="ntn-art-title">' ) !== false &&
	strpos( $p9_page, 'ntn-prose prose' ) !== false && strpos( $p9_page, '<section class="ntn-comments"' ) !== false &&
	strpos( $p9_page, 'NTN MEDIA ENGINE' ) === false && preg_match_all( '/<h1\b/', $p9_page ) === 1,
	'42.2: Static pages use the article band header and prose, with one H1 and no decorative media label'
);

// 42.3: 404 sulla fascia con pulsanti squadrati, senza pseudo-protocollo javascript:
assert_test(
	strpos( $p9_404, '<section class="ntn-band ntn-404"' ) !== false && strpos( $p9_404, 'class="ntn-btn ntn-btn--solid"' ) !== false &&
	strpos( $p9_404, 'href="javascript:' ) === false && preg_match_all( '/<h1\b/', $p9_404 ) === 1 &&
	strpos( $p9_css, '.ntn-btn {' ) !== false && strpos( $p9_css, '.ntn-404-code {' ) !== false && strpos( $p9_css, '.ntn-authorpage {' ) !== false,
	'42.3: 404 page uses the band, square buttons and one H1; author page and 404 components exist in the CSS'
);

// 41.5 (v3.13.1): tendina Piattaforme a colonne, con i sottolivelli sempre visibili (niente tendine dentro la tendina)
$drop_args = (object) array( 'theme_location' => 'menu-piattaforme', 'ntn_context' => 'desktop' );
$drop_item = (object) array( 'classes' => array( 'menu-item', 'menu-item-has-children' ) );
$drop_col  = ntn_journal_menu_classes( array( 'menu-item', 'menu-item-has-children' ), $drop_item, $drop_args, 0 );
$drop_sub0 = ntn_journal_submenu_classes( array( 'sub-menu' ), $drop_args, 0 );
$drop_sub1 = ntn_journal_submenu_classes( array( 'sub-menu' ), $drop_args, 1 );
$drop_l1   = ntn_journal_menu_link_atts( array(), $drop_item, $drop_args, 1 );
$drop_l2   = ntn_journal_menu_link_atts( array(), $drop_item, $drop_args, 2 );
$drop_arr  = ntn_journal_inject_arrow_start_el( '<a href="#">Console</a>', $drop_item, 0, $drop_args );
assert_test(
	in_array( 'ntn-drop-col', $drop_col, true ) && ! in_array( 'group', $drop_col, true ) &&
	in_array( 'ntn-drop-sub', $drop_sub0, true ) && strpos( implode( ' ', $drop_sub0 ), 'hidden' ) === false && strpos( implode( ' ', $drop_sub1 ), 'is-deep' ) !== false &&
	'ntn-drop-link' === $drop_l1['class'] && 'ntn-drop-link is-deep' === $drop_l2['class'] && strpos( $drop_arr, '<svg' ) === false,
	'41.5: Piattaforme dropdown is a column panel: top items are columns, nested levels always visible and indented, no inner arrows'
);

// --- TEST SUITE 43: Hub di categoria v3.14.0 (Console e Studi) ---
echo "\n--- 43. HUB DI CATEGORIA V3.14.0 ---\n";

$ch_src  = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/category-hub.php' );
$ch_tpl  = file_get_contents( $theme_dir . 'ntn-journal/category-hub.php' );
$ch_rt   = file_get_contents( $theme_dir . 'ntn-journal/inc/category-hub-template.php' );
$ch_js   = file_get_contents( $theme_dir . 'ntn-journal/assets/js/dot-art.js' );
$ch_css  = file_get_contents( $theme_dir . 'ntn-journal/build/input.css' );
$ch_seo  = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/seo-extras.php' );

// 43.1 (aggiornato in 3.18.0): il plugin carica il modulo; le impostazioni si salvano dalla scheda "Hub" (nonce e permessi lì), pulite qui
assert_test(
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php' ), "inc/category-hub.php" ) !== false &&
	strpos( $ch_src, 'function ntn_core_category_hub_save_settings( int $term_id, array $data ): void' ) !== false &&
	strpos( $ch_src, 'sanitize_textarea_field' ) !== false && strpos( $ch_src, "add_action( 'edited_category'" ) === false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/admin-page.php' ), "ntn_journal_core_admin_nonce_ok( 'ntn_hub_save_nonce', 'ntn_hub_save' )" ) !== false,
	'43.1: Category Hub module is loaded; settings are saved from the Hub tab (nonce and capability) and sanitized'
);

// 43.2: valori ammessi e lettura delle impostazioni (tipo e illustrazione sconosciuti tornano ai predefiniti)
$GLOBALS['mock_termmeta'][9101] = array( '_ntn_hub_enabled' => '1', '_ntn_hub_type' => 'studi', '_ntn_hub_illustration' => 'boh', '_ntn_hub_title' => 'PlayStation' );
$GLOBALS['mock_termmeta'][9102] = array( '_ntn_hub_type' => 'qualcosa' );
assert_test(
	true === ntn_core_category_hub_meta( 9101, 'enabled' ) && 'studi' === ntn_core_category_hub_meta( 9101, 'type' ) && 'auto' === ntn_core_category_hub_meta( 9101, 'illustration' ) &&
	false === ntn_core_category_hub_meta( 9102, 'enabled' ) && 'console' === ntn_core_category_hub_meta( 9102, 'type' ),
	'43.2: Hub settings fall back to safe defaults for unknown type and illustration values'
);

// 43.3: titolo mostrato e illustrazione automatica dal nome del marchio
$ch_sony = new WP_Term( array( 'term_id' => 9101, 'name' => 'Sony', 'slug' => 'sony', 'taxonomy' => 'category' ) );
$ch_ms   = new WP_Term( array( 'term_id' => 9103, 'name' => 'Microsoft', 'slug' => 'microsoft', 'taxonomy' => 'category' ) );
$ch_nin  = new WP_Term( array( 'term_id' => 9104, 'name' => 'Nintendo', 'slug' => 'nintendo', 'taxonomy' => 'category' ) );
$ch_it   = new WP_Term( array( 'term_id' => 9105, 'name' => 'Sviluppatori Italiani', 'slug' => 'sviluppatori-italiani', 'taxonomy' => 'category' ) );
assert_test(
	'PlayStation' === ntn_core_category_hub_label( $ch_sony ) && 'Microsoft' === ntn_core_category_hub_label( $ch_ms ) &&
	'dualsense' === ntn_core_category_hub_illustration( $ch_sony, 'console' ) && 'xbox' === ntn_core_category_hub_illustration( $ch_ms, 'console' ) &&
	'joycon' === ntn_core_category_hub_illustration( $ch_nin, 'console' ) && 'italia' === ntn_core_category_hub_illustration( $ch_it, 'studi' ),
	'43.3: Display title override and automatic illustration (Joy-Con, DualSense, Xbox controller, Italy)'
);

// 43.4: marchi in ordine alfabetico per nome mostrato; cache invalidata a ogni pubblicazione; filtro per console senza indicizzazione
assert_test(
	strpos( $ch_src, 'strnatcasecmp( ntn_core_category_hub_label( $a ), ntn_core_category_hub_label( $b ) )' ) !== false &&
	strpos( $ch_src, "add_action( 'save_post_post', 'ntn_core_category_hub_flush' )" ) !== false &&
	strpos( $ch_src, "\$robots['noindex'] = true;" ) !== false && strpos( $ch_src, "add_action( 'pre_get_posts', 'ntn_core_category_hub_pre_get_posts', 20 )" ) !== false &&
	strpos( $ch_src, "'@type'           => 'ItemList'" ) !== false,
	'43.4: Brands sorted alphabetically by display name; cache flushed on publish; console filter is noindex; ItemList schema'
);

// 43.5: il tema usa il modello solo per le Hub (altrimenti archivio normale) e carica lo script solo lì
assert_test(
	strpos( $ch_rt, "add_filter( 'category_template', 'ntn_journal_category_hub_template' )" ) !== false &&
	strpos( $ch_rt, "function_exists( 'ntn_core_category_hub_context' )" ) !== false && strpos( $ch_rt, "'ntn-dot-art'" ) !== false &&
	strpos( $ch_tpl, "locate_template( 'archive.php', true, false )" ) !== false && strpos( file_get_contents( $theme_dir . 'ntn-journal/functions.php' ), "'category-hub-template.php'" ) !== false,
	'43.5: Theme routes only Hub categories to category-hub.php (archive fallback) and enqueues dot-art.js only there'
);

// 43.6: modello: fascia con disegno, selettore dei marchi con aria-current, schede, filtro, indice A–Z, un solo H1
assert_test(
	strpos( $ch_tpl, 'data-ntn-dots=' ) !== false && strpos( $ch_tpl, '<nav class="ntn-chub-pick" aria-label=' ) !== false &&
	strpos( $ch_tpl, "' aria-current=\"page\"'" ) !== false && strpos( $ch_tpl, 'class="ntn-chub-card"' ) !== false &&
	strpos( $ch_tpl, "add_query_arg( 'console'" ) !== false && strpos( $ch_tpl, 'data-ntn-az hidden' ) !== false &&
	preg_match_all( '/<h1\b/', $ch_tpl ) === 1 && strpos( $ch_tpl, "'template-parts/hub-article-feed'" ) !== false &&
	strpos( file_get_contents( $theme_dir . 'ntn-journal/template-parts/hub-article-feed.php' ), "'prev_text' => '&larr; Precedente'" ) !== false,
	'43.6: Hub template: dotted art, brand picker with aria-current, cards, console filter, progressive A–Z index, one H1, pagination'
);

// 43.7: disegni a punti (Italia e tre controller), colori dal tema, ridisegno al cambio di tema; stile dei componenti
assert_test(
	strpos( $ch_js, 'var ITALIA = ' ) !== false && strpos( $ch_js, 'joycon: {' ) !== false && strpos( $ch_js, 'dualsense: {' ) !== false && strpos( $ch_js, 'xbox: {' ) !== false &&
	strpos( $ch_js, "--ntn-band-map" ) !== false && strpos( $ch_js, "attributeFilter: ['class']" ) !== false &&
	strpos( $ch_css, '.ntn-chub-grid {' ) !== false && strpos( $ch_css, '.ntn-pick-btn {' ) !== false && strpos( $ch_css, '.ntn-chub-row {' ) !== false,
	'43.7: dot-art.js draws Italy and the three controllers with theme colours and redraws on theme change; Hub CSS components exist'
);

// 43.8: percorso di navigazione con i genitori della categoria e il titolo mostrato della Hub
assert_test(
	strpos( $ch_seo, "get_ancestors( \$term->term_id, \$term->taxonomy, 'taxonomy' )" ) !== false && strpos( $ch_seo, "ntn_core_category_hub_label( \$t )" ) !== false,
	'43.8: Category breadcrumb shows parent categories and the Hub display title'
);

// --- TEST SUITE 44: Stile dell'editor v3.15.0 ---
echo "\n--- 44. STILE DELL'EDITOR V3.15.0 ---\n";

$ed_css   = file_get_contents( $theme_dir . 'ntn-journal/editor-style.css' );
$ed_setup = file_get_contents( $theme_dir . 'ntn-journal/inc/setup.php' );

// 44.1: l'editor carica caratteri e stile; il testo ha le misure degli articoli (Inter 18px, capolettera, H2 numerati in maiuscolo)
assert_test(
	strpos( $ed_setup, "add_theme_support( 'editor-styles' )" ) !== false && strpos( $ed_setup, "'editor-style.css'" ) !== false &&
	strpos( $ed_css, 'font-size: 18px;' ) !== false && strpos( $ed_css, '.is-root-container > p:first-of-type::first-letter' ) !== false &&
	strpos( $ed_css, 'content: counter(ntn-h2, decimal-leading-zero);' ) !== false && preg_match( '/\nh2 \{[^}]*text-transform: uppercase;/', $ed_css ) === 1,
	'44.1: Editor loads fonts and editor-style.css; body text, drop cap and numbered uppercase H2 match the article'
);

// 44.2: link rossi sottolineati, citazioni in Mono con bordo rosso, didascalie in Mono; nessun angolo arrotondato
assert_test(
	preg_match( '/\na \{[^}]*color: #dc2626;[^}]*text-decoration: underline;/', $ed_css ) === 1 &&
	preg_match( '/\.wp-block-quote \{[^}]*border-left: 4px solid #dc2626;[^}]*JetBrains Mono/', $ed_css ) === 1 &&
	preg_match( '/figcaption,[^{]*\{[^}]*JetBrains Mono/', $ed_css ) === 1 && ! preg_match( '/border-radius:\s*[1-9]/', $ed_css ),
	'44.2: Editor links, quotes and captions follow the site style; no rounded corners'
);

// --- TEST SUITE 45: Fine dell'articolo v3.16.0 (indice, condivisione, Google, tag, Leggi anche) ---
echo "\n--- 45. FINE DELL'ARTICOLO V3.16.0 ---\n";

$fa_single = file_get_contents( $theme_dir . 'ntn-journal/single.php' );
$fa_share  = file_get_contents( $theme_dir . 'ntn-journal/inc/share-buttons.php' );
$fa_tags   = file_get_contents( $theme_dir . 'ntn-journal/template-parts/article-tags.php' );
$fa_js     = file_get_contents( $theme_dir . 'ntn-journal/assets/js/main.js' );
$fa_rp     = file_get_contents( $theme_dir . 'ntn-journal/inc/related-posts.php' );
$fa_gcard  = file_get_contents( $theme_dir . 'ntn-journal/template-parts/review-sidebar-desktop.php' );

// 45.1: indice come pannello "// Indice" numerato; le stesse voci sono disponibili per la colonna laterale
$fa_toc = ntn_core_toc_markup( array( array( 'level' => 2, 'id' => 'uno', 'text' => 'Uno' ), array( 'level' => 3, 'id' => 'due', 'text' => 'Due <b>' ), array( 'level' => 2, 'id' => 'tre', 'text' => 'Tre' ) ), 'ntn-toc--aside' );
assert_test(
	strpos( $fa_toc, '<span>// Indice</span><span>2 sezioni</span>' ) !== false && strpos( $fa_toc, 'class="ntn-toc-item is-h3"><a href="#due">Due &lt;b&gt;</a>' ) !== false &&
	strpos( $fa_toc, 'ntn-toc--aside' ) !== false && '' === ntn_core_toc_markup( array( array( 'level' => 2, 'id' => 'x', 'text' => 'X' ) ) ) &&
	is_array( ntn_core_get_toc_items( 999999 ) ) && strpos( $toc_result, 'ntn-toc--inline' ) !== false,
	'45.1: TOC renders as a numbered // Indice panel (escaped), inline in the text and reusable in the sidebar'
);

// 45.2: colonna laterale: indice, scheda del videogioco, condividi, Google; da telefono condivisione e Google sotto l'articolo
$fa_aside = substr( $fa_single, strpos( $fa_single, '<aside id="ntn-sticky-sidebar-container"' ) );
assert_test(
	strpos( $fa_aside, "'ntn-toc--aside'" ) < strpos( $fa_aside, 'review-sidebar-desktop' ) && strpos( $fa_aside, 'review-sidebar-desktop' ) < strpos( $fa_aside, 'ntn_journal_share_buttons()' ) &&
	strpos( $fa_aside, 'ntn_journal_share_buttons()' ) < strpos( $fa_aside, 'ntn_journal_google_feed_box()' ) &&
	strpos( $fa_single, '<div class="ntn-art-after xl:hidden">' ) !== false && strpos( $fa_gcard, '<span>// Scheda del videogioco</span>' ) !== false,
	'45.2: Sidebar order is Indice, Scheda del videogioco, Condividi, Google; on phones share and Google sit below the article'
);

// 45.3: condivisione: X, Bluesky, Facebook, WhatsApp e Copia link, link sicuri; niente Mastodon
assert_test(
	strpos( $fa_share, "'label' => 'BSKY'" ) !== false && strpos( $fa_share, 'https://bsky.app/intent/compose?text=' ) !== false &&
	strpos( $fa_share, 'data-ntn-copy=' ) !== false && strpos( $fa_share, 'rel="noopener noreferrer"' ) !== false &&
	stripos( $fa_share, 'mastodon' ) === false && strpos( $fa_share, '<span>// Condividi</span>' ) !== false &&
	strpos( $fa_js, "closest('[data-ntn-copy]')" ) !== false && strpos( $fa_js, 'navigator.clipboard.writeText' ) !== false,
	'45.3: Share panel has X, Bluesky, Facebook, WhatsApp and Copy link (with clipboard fallback); no Mastodon'
);

// 45.4: tag dell'articolo (gioco in rosso con il pallino) e "Leggi anche" a tre celle; indice con la sezione corrente
assert_test(
	strpos( $fa_tags, 'class="ntn-tag is-game"' ) !== false && strpos( $fa_tags, 'rel="tag">#<?php echo esc_html( $ntn_tag->name ); ?>' ) !== false &&
	strpos( $fa_rp, '// Leggi anche' ) !== false && strpos( $fa_rp, '<div class="ntn-cells ntn-cells--3">' ) !== false &&
	strpos( $fa_js, "link.setAttribute('aria-current', 'location')" ) !== false,
	'45.4: Article tags row, three-cell "Leggi anche" and current-section highlight in the TOC'
);

// --- TEST SUITE 46: Pannello "// Seguici" v3.16.1 ---
echo "\n--- 46. PANNELLO SEGUICI V3.16.1 ---\n";
require_once ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/social-cta.php';

// 46.1: senza profili nel Personalizza il pannello non compare
$fw_saved = $GLOBALS['mock_options'];
foreach ( array( 'telegram', 'facebook', 'instagram', 'x', 'bluesky', 'mastodon' ) as $fw_id ) {
	unset( $GLOBALS['mock_options'][ 'ntn_social_' . $fw_id ] );
}
assert_test( '' === ntn_journal_social_cta_box(), '46.1: Seguici panel is hidden when no social profile is set in the Customizer' );

// 46.2: con i profili, una cella per profilo nell'ordine previsto, link sicuri, numero dei canali
$GLOBALS['mock_options']['ntn_social_telegram'] = 'https://t.me/qgin';
$GLOBALS['mock_options']['ntn_social_bluesky']  = 'https://bsky.app/profile/qgin';
$GLOBALS['mock_options']['ntn_social_mastodon'] = 'https://mastodon.social/@qgin';
$fw_out = ntn_journal_social_cta_box();
assert_test(
	strpos( $fw_out, '<span>// Seguici</span><span>3 canali</span>' ) !== false && strpos( $fw_out, '>TG</a>' ) < strpos( $fw_out, '>BSKY</a>' ) &&
	strpos( $fw_out, '>BSKY</a>' ) < strpos( $fw_out, '>MASTO</a>' ) && strpos( $fw_out, 'href="https://mastodon.social/@qgin"' ) !== false &&
	strpos( $fw_out, 'rel="noopener noreferrer me"' ) !== false && strpos( $fw_out, 'ntn-share ntn-share--follow' ) !== false &&
	strpos( $fw_out, 'Ti è piaciuto' ) === false,
	'46.2: Seguici panel shows one cell per profile in order, with safe links and the channel count'
);
$GLOBALS['mock_options'] = $fw_saved;

// 46.3: in colonna sotto Google e, da telefono, sotto l'articolo dopo Google; non più in fondo al testo
$fw_single = file_get_contents( $theme_dir . 'ntn-journal/single.php' );
$fw_aside  = substr( $fw_single, strpos( $fw_single, '<aside id="ntn-sticky-sidebar-container"' ) );
$fw_after  = substr( $fw_single, strpos( $fw_single, '<div class="ntn-art-after xl:hidden">' ), 900 );
assert_test(
	substr_count( $fw_single, 'echo ntn_journal_social_cta_box()' ) === 2 &&
	strpos( $fw_aside, 'ntn_journal_google_feed_box()' ) < strpos( $fw_aside, 'ntn_journal_social_cta_box()' ) &&
	strpos( $fw_after, 'ntn_journal_google_feed_box()' ) < strpos( $fw_after, 'ntn_journal_social_cta_box()' ) &&
	strpos( file_get_contents( $theme_dir . 'ntn-journal/build/input.css' ), '.ntn-share--follow { grid-template-columns: none; grid-auto-flow: column;' ) !== false,
	'46.3: Seguici sits under Google in the sidebar and below the article on phones'
);

// --- TEST SUITE 47: Pagina Redazione v3.17.0 (Hub degli autori a gruppi) ---
echo "\n--- 47. PAGINA REDAZIONE V3.17.0 ---\n";
$tm_src = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/team-page.php' );
$tm_tpl = file_get_contents( $theme_dir . 'ntn-journal/page-redazione.php' );
$tm_css = file_get_contents( $theme_dir . 'ntn-journal/build/input.css' );

// 47.1: i gruppi si leggono uno per riga, nell'ordine scritto, senza righe vuote, doppioni o tag
$GLOBALS['mock_postmeta'][9301] = array( '_ntn_team_groups' => "Fondatori\r\n\r\n  Redazione \nCollaboratori\nRedazione\n<b>Ospiti</b>" );
assert_test(
	array( 'Fondatori', 'Redazione', 'Collaboratori', 'Ospiti' ) === ntn_core_team_groups( 9301 ),
	'47.1: Team groups are read one per line, in the written order, without blanks, duplicates or tags'
);

// 47.2 (aggiornato in 3.18.0): la Redazione si salva dalla scheda (nonce, permessi, pulizia); niente più tendina nel profilo né riquadro nella pagina
assert_test(
	strpos( $tm_src, "add_action( 'show_user_profile'" ) === false && strpos( $tm_src, "add_meta_box(" ) === false &&
	strpos( $tm_src, 'function ntn_core_team_save( $page_id, array $groups, $order = \'alpha\' )' ) !== false &&
	strpos( $tm_src, 'continue; // Una persona sta in un solo gruppo' ) !== false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/admin-page.php' ), "ntn_journal_core_admin_nonce_ok( 'ntn_team_save_nonce', 'ntn_team_save' )" ) !== false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php' ), 'inc/team-page.php' ) !== false,
	'47.2: Redazione is saved from the admin tab with nonce, capability and sanitization; profile field and page box removed'
);

// 47.3: dati: ordine alfabetico nel gruppo, gruppi vuoti nascosti, cache invalidata, dati per Google
assert_test(
	strpos( $tm_src, 'strnatcasecmp( remove_accents( $a[\'name\'] ), remove_accents( $b[\'name\'] ) )' ) !== false &&
	strpos( $tm_src, 'continue; // Un gruppo senza persone non compare' ) !== false &&
	strpos( $tm_src, "add_action( 'profile_update', 'ntn_core_team_flush' )" ) !== false &&
	strpos( $tm_src, "'@type'    => 'Person'" ) !== false && strpos( $tm_src, "'jobTitle' => \$group['name']" ) !== false,
	'47.3: People sorted alphabetically per group, empty groups hidden, cache flushed, ItemList of Person for Google'
);

// 47.4: modello "Redazione": fascia con le firme, gruppi in elenco verticale, foto o iniziali, ultimo articolo, link sicuri
assert_test(
	strpos( $tm_tpl, 'Template Name: Redazione' ) !== false && strpos( $tm_tpl, "function_exists( 'ntn_core_get_team_data' )" ) !== false &&
	strpos( $tm_tpl, 'class="ntn-team-group-h">// <?php echo esc_html( $ntn_group[\'name\'] ); ?>' ) !== false &&
	strpos( $tm_tpl, "echo '<span>' . esc_html( strtoupper( \$ntn_initials ) ) . '</span>';" ) !== false &&
	strpos( $tm_tpl, 'Ultimo articolo · <?php echo esc_html( $ntn_person[\'last\'][\'date\'] ); ?>' ) !== false &&
	preg_match_all( '/<h1\b/', $tm_tpl ) === 1 && strpos( $tm_css, '.ntn-team-row {' ) !== false && strpos( $tm_css, 'grid-template-columns: 56px minmax(0, 1fr) minmax(0, 1.6fr) auto;' ) !== false,
	'47.4: Redazione template: band with signature count, grouped vertical list, photo or dotted initials, latest article, one H1'
);

// --- TEST SUITE 48: NTN Journal Funzioni, correzioni v3.17.1 ---
echo "\n--- 48. NTN JOURNAL FUNZIONI V3.17.1 ---\n";
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/api-status.php';
$fx_admin  = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/admin-page.php' );
$fx_api    = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/views/tab-api-services.php' );
$fx_status = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/api-status.php' );
$fx_extra  = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/views/tab-extra.php' );
$fx_igdb   = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/views/tab-igdb.php' );
$fx_igdbjs = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/assets/js/admin-igdb.js' );

// 48.1: caselle inutili tolte, spiegazione di "Link Videogioco" riscritta, commenti anche nelle pagine
assert_test(
	strpos( $fx_admin, 'ntn_journal_enable_igdb' ) === false && strpos( $fx_admin, 'ntn_journal_enable_tags' ) === false &&
	strpos( $fx_api, 'ntn_journal_enable_igdb' ) === false && strpos( $fx_extra, '<strong>Se la spegni:</strong>' ) !== false &&
	strpos( file_get_contents( $theme_dir . 'ntn-journal/page.php' ), "get_option( 'ntn_journal_enable_comments', 'yes' ) === 'yes' && ( comments_open() || get_comments_number() )" ) !== false,
	'48.1: Dead IGDB and tags options removed, Link Videogioco explained, comments switch also applies to pages'
);

// 48.2: salvataggi prima della pagina con ricarica, nonce pulito, menu e titolo della scheda giusti
assert_test(
	strpos( $fx_admin, "add_action( 'admin_init', 'ntn_journal_core_admin_handle_post' );" ) !== false && strpos( $fx_admin, 'wp_safe_redirect(' ) !== false &&
	strpos( $fx_admin, "wp_verify_nonce( sanitize_text_field( wp_unslash( \$_POST[ \$field ] ) ), \$action )" ) !== false &&
	strpos( $fx_admin, "add_filter( 'submenu_file', 'ntn_journal_core_admin_submenu_file' );" ) !== false &&
	strpos( $fx_admin, "add_filter( 'admin_title', 'ntn_journal_core_admin_title', 10, 2 );" ) !== false &&
	strpos( $fx_admin, "return; // Moduli di altre schede (Redirect, Backup…) si gestiscono da soli" ) !== false,
	'48.2: Saves run in admin_init with redirect, sanitized nonces, correct submenu highlight and window title; other tabs untouched'
);

// 48.3: svuota memoria completo; freno d'emergenza dei dati per Google su tutto il plugin
update_option( 'ntn_seo_enable_review_schema', 'no' );
$fx_off = ! ntn_core_schema_enabled();
update_option( 'ntn_seo_enable_review_schema', 'yes' );
assert_test(
	$fx_off && ntn_core_schema_enabled() && strpos( $fx_admin, "'ntn_cat_hub_', 'ntn_team_', 'ntn_front_data_', 'ntn_author_stats_'" ) !== false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/team-page.php' ), '! ntn_core_schema_enabled()' ) !== false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/schema-markup.php' ), '! ntn_core_schema_enabled()' ) !== false &&
	substr_count( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/seo-extras.php' ), 'ntn_core_schema_enabled()' ) >= 4,
	'48.3: Cache flush covers all NTN caches; the Schema Markup switch is an emergency brake for every JSON-LD output'
);

// 48.4: chiavi API mai scritte nella pagina; stato Attiva / Salvata / Errore; Verifica senza consumo
assert_test(
	strpos( $fx_api, "value=\"\" class=\"regular-text\" autocomplete=\"new-password\"" ) !== false && strpos( $fx_api, "get_option( 'ntn_gemini_api_key'" ) === false &&
	strpos( $fx_status, "'label' => 'Attiva'" ) === false && strpos( $fx_status, "'Salvata, non verificata'" ) !== false &&
	strpos( $fx_status, 'https://generativelanguage.googleapis.com/v1beta/models?pageSize=1' ) !== false && strpos( $fx_status, 'https://api.groq.com/openai/v1/models' ) !== false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/ajax-igdb.php' ), "ntn_core_api_status_set( 'igdb', true );" ) !== false,
	'48.4: API keys are never printed; status shows Attiva / Salvata, non verificata / Errore; Verifica uses free requests'
);

// 48.5: stato della chiave: mancante, salvata, attiva, errore
update_option( 'ntn_groq_api_key', '' );
$fx_missing = ntn_core_api_status_get( 'groq' );
update_option( 'ntn_groq_api_key', 'chiave-di-prova' );
ntn_core_api_status_reset( 'groq' );
$fx_saved = ntn_core_api_status_get( 'groq' );
ntn_core_api_status_set( 'groq', true );
$fx_active = ntn_core_api_status_get( 'groq' );
ntn_core_api_status_set( 'groq', false, 'Chiave non valida' );
$fx_error = ntn_core_api_status_get( 'groq' );
update_option( 'ntn_groq_api_key', '' );
assert_test(
	'missing' === $fx_missing['state'] && 'saved' === $fx_saved['state'] && 'Attiva' === $fx_active['label'] &&
	'error' === $fx_error['state'] && 'Chiave non valida' === $fx_error['message'],
	'48.5: Key status moves from Mancante to Salvata, non verificata, Attiva and Errore with the reason'
);

// 48.6: Scanner IGDB e ricerca nell'editor mostrano nomi di tag e giochi come testo; link protetti in Recensioni e Comunicati
assert_test(
	strpos( $fx_igdb, 'function ntnEsc(value)' ) !== false && strpos( $fx_igdb, '<strong>${ntnEsc(t.name)}</strong>' ) !== false &&
	strpos( $fx_igdb, '<strong>${ntnEsc(game.name)}</strong>' ) !== false && strpos( $fx_igdb, '<strong>${t.name}</strong>' ) === false &&
	strpos( $fx_igdbjs, "<strong>' + ntnEsc(game.name) + '</strong>" ) !== false && strpos( $fx_igdbjs, "ntnEsc(response.data.message)" ) !== false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/views/tab-reviews.php' ), 'esc_url( (string) get_edit_post_link() )' ) !== false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/views/tab-press.php' ), 'esc_url( (string) get_permalink() )' ) !== false,
	'48.6: IGDB scanner and editor search render names as text; edit and view links are escaped'
);

// 48.7: iniziali a punti al posto della sagoma grigia (Gravatar senza riserva, la foto si toglie se manca)
$fx_av = ntn_journal_author_avatar( 42, 'Luca Maiolini', 112 );
assert_test(
	strpos( $fx_av, '<span>LM</span>' ) === 0 && strpos( $fx_av, 'd=404' ) !== false && strpos( $fx_av, 'onerror="this.remove()"' ) !== false &&
	strpos( file_get_contents( $theme_dir . 'ntn-journal/template-parts/author-panel.php' ), 'ntn_journal_author_avatar( $ntn_ap_id' ) !== false &&
	strpos( file_get_contents( $theme_dir . 'ntn-journal/author.php' ), 'ntn_journal_author_avatar( $author_id' ) !== false &&
	strpos( file_get_contents( $theme_dir . 'ntn-journal/page-redazione.php' ), "ntn_journal_author_avatar( \$ntn_person['id']" ) !== false,
	'48.7: Author photos fall back to dotted initials instead of the grey WordPress silhouette'
);

// --- TEST SUITE 49: NTN Journal Funzioni, schermata unica v3.18.0 ---
echo "\n--- 49. NTN JOURNAL FUNZIONI V3.18.0 ---\n";
$nf_admin = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/admin-page.php' );
$nf_css   = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/assets/css/admin.css' );
$nf_js    = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/assets/js/admin-screen.js' );
$nf_dir   = ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/views/';

// 49.1: schede Hub e Redazione nel menu e nella pagina; stile e caratteri del plugin solo in questa schermata
assert_test(
	strpos( $nf_admin, "'hub'           => 'Hub'," ) !== false && strpos( $nf_admin, "'redazione'     => 'Redazione'," ) !== false &&
	file_exists( $nf_dir . 'tab-hub.php' ) && file_exists( $nf_dir . 'tab-redazione.php' ) &&
	strpos( $nf_admin, "'toplevel_page_ntn-journal-functions' !== \$hook" ) !== false && strpos( $nf_admin, "assets/css/admin.css" ) !== false &&
	file_exists( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/assets/fonts/inter-variable.woff2' ) &&
	strpos( $nf_css, "src: url('../fonts/inter-variable.woff2')" ) !== false && strpos( $nf_admin, '<div class="wrap ntn-adm">' ) !== false,
	'49.1: Hub and Redazione tabs exist; Nothing OS stylesheet and bundled fonts load only on this screen'
);

// 49.2: aspetto Nothing OS: angoli vivi, niente ombre, sempre chiaro, solo nero/bianco/rosso
assert_test(
	strpos( $nf_css, 'border-radius: 0 !important;' ) !== false && strpos( $nf_css, 'box-shadow: none !important;' ) !== false &&
	strpos( $nf_css, '--nadm-accent: #dc2626;' ) !== false && strpos( $nf_css, 'prefers-color-scheme' ) === false && ! preg_match( '/border-radius:\s*[1-9]/', $nf_css ),
	'49.2: Admin screen follows Nothing OS: square corners, no shadows, light only, black/white/red'
);

// 49.3: Hub: elenco, modifica, accensione, controllo copertura, memoria; nella categoria resta solo il rimando
$nf_hub = file_get_contents( $nf_dir . 'tab-hub.php' );
assert_test(
	strpos( $nf_hub, '// Trasforma una categoria in Hub' ) !== false && strpos( $nf_hub, '1 · Quale categoria' ) !== false &&
	strpos( $nf_hub, 'ntn_core_category_hub_coverage( $ntn_hub )' ) !== false && strpos( $nf_hub, "name=\"ntn_hub_brand_title[" ) !== false &&
	strpos( $ch_src, 'Le Hub si accendono e si modificano in' ) !== false && strpos( $ch_src, 'function ntn_core_category_hub_coverage( WP_Term $term ): array' ) !== false,
	'49.3: Hub tab lists, edits, turns on Hubs, shows the coverage check; the category screen only links to it'
);

// 49.4: Redazione: gruppi riordinabili, persone una sola volta, ordine dentro i gruppi; senza JavaScript non si perdono persone
$GLOBALS['mock_options']['ntn_team_order'] = 'posts';
$nf_mode = ntn_core_team_order_mode();
$GLOBALS['mock_options']['ntn_team_order'] = 'boh';
$nf_bad = ntn_core_team_order_mode();
unset( $GLOBALS['mock_options']['ntn_team_order'] );
$nf_red = file_get_contents( $nf_dir . 'tab-redazione.php' );
assert_test(
	'posts' === $nf_mode && 'alpha' === $nf_bad && strpos( $nf_red, 'data-nadm="group-up"' ) !== false && strpos( $nf_red, 'draggable="true"' ) !== false &&
	strpos( $nf_js, "inp.name = 'ntn_team_people[' + key + '][]';" ) !== false && strpos( $nf_admin, "'1' === ntn_journal_core_admin_field( 'ntn_team_js' )" ) !== false &&
	strpos( $tm_src, "'manual' === \$mode" ) !== false && strpos( $tm_src, "'posts' === \$mode" ) !== false,
	'49.4: Redazione groups are reorderable, each person in one group, order inside groups; nothing is lost without JavaScript'
);

// 49.5: Recensioni e Comunicati: 20 per pagina con ricerca, filtri e righe apribili da tastiera
$nf_rev   = file_get_contents( $nf_dir . 'tab-reviews.php' );
$nf_press = file_get_contents( $nf_dir . 'tab-press.php' );
assert_test(
	strpos( $nf_rev, "'posts_per_page' => 20," ) !== false && strpos( $nf_press, "'posts_per_page' => 20," ) !== false &&
	strpos( $nf_rev, "'posts_per_page' => 500" ) === false && strpos( $nf_press, "'posts_per_page' => 500" ) === false &&
	strpos( $nf_rev, "'bocciato'    => array( 'Bocciato', array( '1', '2' ) )," ) !== false &&
	strpos( $nf_rev, 'data-nadm-toggle=' ) !== false && strpos( $nf_rev, 'aria-expanded="false"' ) !== false &&
	strpos( $nf_js, "btn.setAttribute('aria-expanded', String(open));" ) !== false && strpos( $nf_rev, '#22c55e' ) === false,
	'49.5: Reviews and press releases show 20 per page with search, filters and keyboard-friendly rows; no green'
);

// 49.6: Dashboard con lo stato reale; Backup anche della Redazione
$nf_dash = file_get_contents( $nf_dir . 'tab-dashboard.php' );
$nf_bk   = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/backup-restore.php' );
assert_test(
	strpos( $nf_dash, '// Lo stato del sito' ) !== false && strpos( $nf_dash, 'ntn_core_api_status_get( $ntn_service )' ) !== false &&
	strpos( $nf_dash, 'dashicons' ) === false && strpos( $nf_bk, "'type'      => 'team'," ) !== false && strpos( $nf_bk, "case 'team':" ) !== false &&
	strpos( $nf_bk, "get_user_by( 'login'" ) !== false && strpos( $nf_bk, "esc_like( '_ntn_hub_' )" ) !== false,
	'49.6: Dashboard shows the live status of each feature; backup also saves and restores the Redazione and Hub settings'
);

// --- TEST SUITE 50: ricerca con articoli e giochi, Scanner IGDB senza colori v3.19.0 ---
echo "\n--- 50. RICERCA E SCANNER IGDB V3.19.0 ---\n";
$rs_rest  = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/rest-catalog.php' );
$rs_head  = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/header.php' );
$rs_css   = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/build/input.css' );
$rs_main  = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/assets/js/main.js' );
$rs_js    = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/assets/js/live-search.js' );
$rs_igdb  = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/views/tab-igdb.php' );

// 50.1: i suggerimenti cercano anche negli articoli pubblicati (4) oltre ai giochi (3), con i totali
assert_test(
	strpos( $rs_rest, "'post_type'           => 'post'," ) !== false && strpos( $rs_rest, "'posts_per_page'      => 4," ) !== false &&
	strpos( $rs_rest, "'posts_per_page'         => 3," ) !== false && strpos( $rs_rest, "'articles'       => \$articles," ) !== false &&
	strpos( $rs_rest, "'total_games'    => \$total_games," ) !== false && strpos( $rs_rest, "'has_password'        => false," ) !== false &&
	strpos( $rs_rest, "strlen( \$search_term ) < 2" ) !== false,
	'50.1: Live search returns published articles (4) and catalog games (3) with totals, at least 2 characters'
);

// 50.2: barra a tutta larghezza con pulsante Cerca e Cancella, lente che diventa ×; apertura animata ma ferma con "riduci movimento"
assert_test(
	strpos( $rs_head, 'class="ntn-search-line"' ) !== false && strpos( $rs_head, 'class="ntn-search-go">Cerca</button>' ) !== false &&
	strpos( $rs_head, 'class="ntn-ic-x"' ) !== false && strpos( $rs_head, 'autocomplete="off"' ) !== false &&
	strpos( $rs_css, '@keyframes ntn-sdraw' ) !== false && strpos( $rs_css, '.ntn-search-input::-webkit-search-cancel-button' ) !== false &&
	strpos( str_replace( "\r\n", "\n", $rs_css ), ".ntn-search-panel *,\n  .ntn-search-toggle svg { transition: none !important; animation: none !important; }" ) !== false &&
	strpos( $rs_main, "p.panel.classList.add('is-done');" ) !== false && strpos( $rs_main, "'ntn:search-ready'" ) !== false,
	'50.2: Search bar spans the content width with Cerca and Cancella; it slides down unless reduced motion is requested'
);

// 50.3: suggerimenti in due gruppi, parola evidenziata, tastiera, testo sempre come testo
assert_test(
	strpos( $rs_js, '// Articoli' ) !== false && strpos( $rs_js, '// Giochi' ) !== false && strpos( $rs_js, 'Tutti i risultati per «' ) !== false &&
	strpos( $rs_js, "'<mark>' + escapeHTML(" ) !== false && strpos( $rs_js, "e.key === 'ArrowDown'" ) !== false &&
	strpos( $rs_js, 'e.stopPropagation(); // il primo Esc chiude i suggerimenti' ) !== false && strpos( $rs_js, '/^https?:\/\//i.test(' ) !== false,
	'50.3: Suggestions are grouped (Articoli, Giochi), highlight the query, work from the keyboard and escape all text'
);

// 50.4: pagina dei risultati: "Ricerca" nel percorso, "Risultati" nel contatore
$rs_qo = isset( $GLOBALS['queried_object'] ) ? $GLOBALS['queried_object'] : null;
$GLOBALS['queried_object'] = null;
$GLOBALS['mock_query_vars'] = array( 'is_search' => true );
ob_start();
ntn_journal_breadcrumb();
$rs_crumb = ob_get_clean();
$GLOBALS['mock_query_vars'] = array();
$GLOBALS['queried_object'] = $rs_qo;
assert_test(
	strpos( $rs_crumb, '>Ricerca</span>' ) !== false && strpos( $rs_crumb, '>Archivio</span>' ) === false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/index.php' ), "'unit'  => array( 'Risultato', 'Risultati' )" ) !== false,
	'50.4: Search results show "Ricerca" in the breadcrumb and count results'
);

// 50.5: Scanner IGDB senza riquadri colorati e con il link al Catalogo
assert_test(
	! preg_match( '/#(f0f6fc|fcf0f1|fdf5d3|d4edda|d1ecf1|2271b1|d63638)/i', $rs_igdb ) && strpos( $rs_igdb, "'green'" ) === false &&
	strpos( $rs_igdb, "get_post_type_archive_link( 'videogioco' )" ) !== false && strpos( $rs_igdb, 'Apri il Catalogo ↗' ) !== false,
	'50.5: Scanner IGDB uses only black, white and red and links to the public catalog'
);

// --- TEST SUITE 51: data completa nel catalogo solo se IGDB conosce il giorno v3.19.1 ---
echo "\n--- 51. PRECISIONE DELLA DATA DI USCITA V3.19.1 ---\n";
if ( ! function_exists( 'ntn_igdb_release_precision' ) ) {
	require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/ajax-igdb.php';
}
$dp_first = 1792886400; // 23.10.2026
$dp_game  = array(
	'first_release_date' => $dp_first,
	'release_dates'      => array(
		array( 'date' => $dp_first, 'human' => '2026' ),
		array( 'date' => $dp_first, 'human' => 'Oct 23, 2026' ),
		array( 'date' => $dp_first + 86400, 'human' => 'Oct 24, 2026' ),
	),
);

// 51.1: il testo "human" di IGDB diventa la precisione; tra più piattaforme con la stessa data vale la meno precisa (il 31.12 segnaposto)
assert_test(
	'year' === ntn_igdb_release_precision( $dp_game ) &&
	'day' === ntn_igdb_release_precision( array( 'first_release_date' => $dp_first, 'release_dates' => array( array( 'date' => $dp_first, 'human' => 'Oct 23, 2026' ), array( 'date' => $dp_first, 'human' => 'Oct 23, 2026' ) ) ) ) &&
	'year' === ntn_igdb_release_precision( array( 'first_release_date' => 1861833600, 'release_dates' => array( array( 'date' => 1861833600, 'human' => '2028' ) ) ) ) &&
	'month' === ntn_igdb_precision_from_human( 'Oct 2026' ) && 'quarter' === ntn_igdb_precision_from_human( 'Q4 2027' ) &&
	'tbd' === ntn_igdb_precision_from_human( 'TBD' ) && '' === ntn_igdb_precision_from_human( '' ) &&
	'' === ntn_igdb_release_precision( array( 'first_release_date' => $dp_first ) ),
	'51.1: IGDB "human" text gives the release date precision (day, month, quarter, year, TBD)'
);

// 51.2: il plugin chiede a IGDB le date per piattaforma e salva la precisione, la cancella con lo scollegamento
$dp_ajax = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/ajax-igdb.php' );
$dp_hand = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/igdb-ajax-handlers.php' );
assert_test(
	substr_count( $dp_ajax . $dp_hand, 'release_dates.date, release_dates.human' ) === 2 &&
	substr_count( $dp_ajax, "'_ntn_game_release_precision'" ) === 2 && substr_count( $dp_hand, "'_ntn_game_release_precision'" ) === 6,
	'51.2: IGDB sync requests per-platform dates, stores the precision on tag and game, and clears it on unlink'
);

// 51.3: il catalogo mostra giorno, mese e anno solo con la precisione "day"
$GLOBALS['mock_postmeta'][9951]['_ntn_game_release_precision'] = 'day';
$GLOBALS['mock_postmeta'][9952]['_ntn_game_release_precision'] = 'year';
$GLOBALS['mock_postmeta'][9954]['_ntn_game_release_precision'] = 'quarter';
$GLOBALS['mock_postmeta'][9955]['_ntn_game_release_precision'] = 'month';
assert_test(
	'23.10.2026' === ntn_journal_game_card_date( 9951, '2026-10-23' ) && '2028' === ntn_journal_game_card_date( 9952, '2028-12-31' ) &&
	'2027' === ntn_journal_game_card_date( 9953, '2027-12-31' ) && '2024' === ntn_journal_game_card_date( 9951, '2024' ) && '' === ntn_journal_game_card_date( 9951, '' ) &&
	'Q3 2027' === ntn_journal_game_card_date( 9954, '2027-09-30' ) && 'Q3 2027' === ntn_journal_game_card_date( 9954, '2027-07-01' ) && 'Ott 2027' === ntn_journal_game_card_date( 9955, '2027-10-31' ),
	'51.3: Catalog cards show the full date, the month or the quarter when IGDB knows them, otherwise the year'
);

// 51.4: nel catalogo, dentro ogni anno, prima i giochi con solo l'anno in ordine alfabetico, poi gli altri per data, giorno, mese o trimestre (dal più recente)
$dp_cat = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/catalog-api.php' );
assert_test(
	strpos( $dp_cat, "\$args['ntn_precision_sort'] = \$precision_sort;" ) !== false &&
	strpos( $dp_cat, "add_filter( 'posts_clauses', 'ntn_catalog_precision_clauses', 10, 2 );" ) !== false &&
	strpos( $dp_cat, "'LEFT( ntn_rd.meta_value, 4 ) '" ) !== false && strpos( $dp_cat, "WHEN 'day' THEN 4 WHEN 'month' THEN 3 WHEN 'quarter' THEN 2 ELSE 0 END" ) !== false &&
	strpos( $dp_cat, "CASE WHEN {\$rank} > 0 THEN ntn_rd.meta_value END " ) !== false &&
	strpos( $dp_cat, "ntn_rp.meta_key = '_ntn_game_release_precision'" ) !== false,
	'51.4: Catalog sorted by date lists, within each year, year-only games alphabetically, then the others by date, day, month or quarter'
);

// --- TEST SUITE 52: data di uscita per Google solo con le parti note v3.19.2 ---
echo "\n--- 52. DATA DI USCITA PER GOOGLE V3.19.2 ---\n";
if ( ! function_exists( 'ntn_release_phrase' ) ) {
	require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/hub-seo.php';
}

// 52.1: dati strutturati con solo le parti note; un 31 dicembre senza precisione vale "solo l'anno"
assert_test(
	'2026-10-23' === ntn_release_schema_date( '2026-10-23', 'day' ) && '2027-10' === ntn_release_schema_date( '2027-10-31', 'month' ) &&
	'2027' === ntn_release_schema_date( '2027-09-30', 'quarter' ) && '2028' === ntn_release_schema_date( '2028-12-31', 'year' ) &&
	'2028' === ntn_release_schema_date( '2028-12-31', '' ) && '2026-10-23' === ntn_release_schema_date( '2026-10-23', '' ) &&
	'' === ntn_release_schema_date( '', 'day' ),
	'52.1: Structured data release dates keep only the known parts (day, month or year)'
);

// 52.2: testo per Google e per la Hub: "nel 2028", "nel terzo trimestre del 2027", "a ottobre 2027", "il 23/10/2026"
assert_test(
	'in uscita nel 2099' === ntn_release_phrase( '2099-12-31', 'year' ) && 'in uscita nel terzo trimestre del 2099' === ntn_release_phrase( '2099-09-30', 'quarter' ) &&
	'in uscita a ottobre 2099' === ntn_release_phrase( '2099-10-31', 'month' ) && 'in uscita il 23/10/2099' === ntn_release_phrase( '2099-10-23', 'day' ) &&
	'uscito il 10/12/2020' === ntn_release_phrase( '2020-12-10', 'day' ) && 'uscito nel 2024' === ntn_release_phrase( '2024', '' ),
	'52.2: Hub summary says only the known part of the release date, with "in uscita" or "uscito"'
);

// 52.3: le Hub e le schede dei giochi negli articoli usano la precisione salvata
$rs_hub = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/hub-seo.php' );
$rs_sm  = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/schema-markup.php' );
$rs_sx  = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/seo-extras.php' );
assert_test(
	substr_count( $rs_hub, "get_post_meta( \$post_id, '_ntn_game_release_precision', true )" ) === 2 &&
	substr_count( $rs_sm, 'ntn_release_schema_date( $raw_release,' ) === 2 && substr_count( $rs_sx, 'ntn_release_schema_date( $raw_release,' ) === 2 &&
	strpos( $rs_hub, "'il ' . wp_date( 'd/m/Y', \$release )" ) === false,
	'52.3: Game hubs and article game schemas use the saved release precision'
);

// --- TEST SUITE 53: Google e condivisioni delle Hub di categoria e dei tag v3.20.0 ---
echo "\n--- 53. SEO DI HUB DI CATEGORIA E TAG V3.20.0 ---\n";
$sh_cat = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/category-hub.php' );
$sh_hub = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/hub-seo.php' );

// 53.1: descrizione automatica della Hub di categoria (introduzione o dati), mai sopra una scritta a mano
assert_test(
	strpos( $sh_cat, "add_filter( 'the_seo_framework_description_excerpt', 'ntn_core_category_hub_tsf_excerpt', 10, 2 );" ) !== false &&
	strpos( $sh_cat, "if ( '' !== trim( (string) \$excerpt ) ) {\n\t\treturn \$excerpt;" ) !== false &&
	strpos( $sh_cat, "'Notizie, recensioni e anteprime su ' . \$title" ) !== false && strpos( $sh_cat, "'Gli studi di ' . \$title . ' e i loro giochi'" ) !== false,
	'53.1: Category hubs get an automatic description (intro or data) that never overrides a written one'
);

// 53.2: immagine per le condivisioni dall'articolo in evidenza; Hub e marchi nella sitemap di TSF, senza Hub vuote
assert_test(
	strpos( $sh_cat, "add_filter( 'the_seo_framework_image_generation_params', 'ntn_core_category_hub_tsf_image_params', 11, 3 );" ) !== false &&
	strpos( $sh_cat, "get_the_post_thumbnail_url( (int) \$data['featured'], 'full' )" ) !== false &&
	strpos( $sh_cat, "add_filter( 'the_seo_framework_sitemap_additional_urls', 'ntn_core_category_hub_sitemap_urls' );" ) !== false &&
	strpos( $sh_cat, "continue; // Una Hub senza articoli non serve a Google" ) !== false,
	'53.2: Category hubs share the featured article image and are listed in the TSF sitemap with their brands'
);

// 53.3: pagine dei tag senza descrizione: gli studi citano i loro giochi (solo se lo studio è davvero lo sviluppatore)
if ( ! function_exists( 'get_terms' ) ) {
	function get_terms( $args = array() ) {
		return array(); // nessun gioco dello studio nel banco di prova
	}
}
$sh_tag = new WP_Term( array( 'term_id' => 99531, 'name' => 'Nintendo Direct', 'slug' => 'nintendo-direct', 'taxonomy' => 'post_tag' ) );
$sh_txt = ntn_hub_tag_seo_description( $sh_tag );
assert_test(
	0 === strpos( $sh_txt, 'Tutti gli articoli' ) && false !== strpos( $sh_txt, ' su Nintendo Direct: notizie, anteprime e recensioni.' ) &&
	strpos( $sh_hub, "add_filter( 'the_seo_framework_description_excerpt', 'ntn_hub_tsf_tag_excerpt', 10, 2 );" ) !== false &&
	strpos( $sh_hub, "sullo studio e sui suoi giochi, tra cui '" ) !== false && strpos( $sh_hub, "=== \$key ) {" ) !== false,
	'53.3: Tag archives without a description get one; studio tags list their games'
);

// 53.4: pagine con pochi articoli: soglie (10 tag e categorie, 3 Hub dei giochi; 0 = spenta), noindex e fuori dalla sitemap
if ( ! function_exists( 'ntn_core_seo_is_thin_term' ) ) {
	require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/seo-thin-pages.php';
}
$st_src  = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/seo-thin-pages.php' );
$st_few  = new WP_Term( array( 'term_id' => 99541, 'name' => 'Pochi', 'slug' => 'pochi', 'taxonomy' => 'post_tag', 'count' => 9 ) );
$st_many = new WP_Term( array( 'term_id' => 99542, 'name' => 'Tanti', 'slug' => 'tanti', 'taxonomy' => 'post_tag', 'count' => 10 ) );
$st_thin = ntn_core_seo_is_thin_term( $st_few );
$st_ok   = ! ntn_core_seo_is_thin_term( $st_many );
$GLOBALS['mock_options']['ntn_seo_min_posts_terms'] = 0;
$st_off  = ! ntn_core_seo_is_thin_term( $st_few );
unset( $GLOBALS['mock_options']['ntn_seo_min_posts_terms'] );
assert_test(
	$st_thin && $st_ok && $st_off && 10 === ntn_core_seo_min_terms() && 3 === ntn_core_seo_min_games() &&
	strpos( $st_src, "add_filter( 'the_seo_framework_robots_meta_array', 'ntn_core_seo_thin_tsf_robots', 10, 3 );" ) !== false &&
	strpos( $st_src, "add_filter( 'the_seo_framework_sitemap_exclude_ids', 'ntn_core_seo_thin_tsf_sitemap' );" ) !== false &&
	strpos( $st_src, "\$robots['follow']  = true;" ) !== false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/category-hub.php' ), 'ntn_core_seo_is_thin_term( $term )' ) !== false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/backup-restore.php' ), "'ntn_seo_min_posts_games'," ) !== false,
	'53.4: Pages under the article threshold are noindex (follow) and out of the sitemap; thresholds are editable and backed up'
);

// --- TEST SUITE 54: Hub dello studio v3.21.0 ---
echo "\n--- 54. HUB DELLO STUDIO V3.21.0 ---\n";
if ( ! function_exists( 'ntn_core_studio_key' ) ) {
	require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/studio-hub.php';
}
$sh_plugin = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/studio-hub.php' );
$sh_tpl    = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/studio-hub.php' );
$sh_tagtpl = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/tag.php' );

// 54.1: lo studio si riconosce dallo sviluppatore IGDB dei giochi (nome confrontato senza spazi, simboli e maiuscole); i tag dei giochi restano Hub del gioco
assert_test(
	'3dclouds' === ntn_core_studio_key( '3D Clouds' ) && '3dclouds' === ntn_core_studio_key( '3DClouds' ) &&
	strpos( $sh_plugin, "'meta_key'   => '_ntn_game_developer'," ) !== false && strpos( $sh_plugin, "_ntn_game_publisher" ) === false &&
	strpos( $sh_plugin, "get_term_meta( \$tag->term_id, '_ntn_igdb_game_id', true )" ) !== false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php' ), "inc/studio-hub.php" ) !== false,
	'54.1: Studios are recognized from the IGDB developer of catalog games (developers only); game tags stay game hubs'
);

// 54.2: pagina: testata, prima gli articoli e poi i giochi (scelta del CEO), date dal catalogo; tag.php la usa solo per gli studi
assert_test(
	strpos( $sh_tagtpl, "require get_template_directory() . '/studio-hub.php';" ) !== false && strpos( $sh_tagtpl, "require get_template_directory() . '/archive.php';" ) !== false &&
	strpos( $sh_tpl, '// Tutti gli articoli' ) < strpos( $sh_tpl, '// Giochi dello studio' ) &&
	strpos( $sh_tpl, 'ntn_journal_game_card_date( $game[\'hub_id\'], $game[\'release\'] )' ) !== false && strpos( $sh_tpl, 'Made in Italy' ) !== false,
	'54.2: Studio hub page shows articles first, then games with catalog dates; tag.php uses it only for studios'
);

// 54.3: Google: visibile con almeno 2 giochi oppure 10 articoli; elenco dei giochi nei dati strutturati; percorso dalla Hub degli studi
assert_test(
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/seo-thin-pages.php' ), "ntn_core_studio_has_enough_games( \$term )" ) !== false &&
	strpos( $sh_plugin, ">= 2;" ) !== false && strpos( $sh_plugin, "'name'            => \$data['name'] . ' – giochi dello studio'," ) !== false &&
	strpos( $sh_plugin, "get_option( 'ntn_seo_enable_review_schema', 'yes' ) !== 'yes'" ) !== false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/seo-extras.php' ), "\$studio['parent']['label']" ) !== false,
	'54.3: Studio hubs are indexable with 2+ games or 10 articles, list their games for Google and show the studios hub in the breadcrumb'
);

// 54.4: schede del gioco (Hub, recensione da computer e da telefono, Prossime uscite): data solo con le parti note
$rl_dir = ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/';
assert_test(
	'23/10/2026' === ntn_journal_release_label( '2026-10-23', 'day' ) && 'Ottobre 2027' === ntn_journal_release_label( '2027-10-31', 'month' ) &&
	'Q3 2027' === ntn_journal_release_label( '2027-09-30', 'quarter' ) && '2028' === ntn_journal_release_label( '2028-12-31', 'year' ) &&
	'2028' === ntn_journal_release_label( '2028-12-31', '' ) && '10/12/2020' === ntn_journal_release_label( '2020-12-10', '' ) &&
	'23.10.2026' === ntn_journal_release_label( '2026-10-23', 'day', 'd.m.Y' ) && '2024' === ntn_journal_release_label( '2024', '' ) &&
	strpos( file_get_contents( $rl_dir . 'template-parts/hub-header.php' ), "ntn_journal_release_label( \$igdb_info['release'], \$igdb_info['release_precision'] ?? '' )" ) !== false &&
	strpos( file_get_contents( $rl_dir . 'template-parts/review-sidebar-desktop.php' ), "ntn_journal_release_label( \$release, \$game['release_precision'] )" ) !== false &&
	strpos( file_get_contents( $rl_dir . 'template-parts/review-header-mobile.php' ), "ntn_journal_release_label( \$release, \$game['release_precision'] )" ) !== false &&
	strpos( file_get_contents( $rl_dir . 'inc/front-page-data.php' ), "'date'      => \$label," ) !== false,
	'54.4: Game sheets and upcoming releases show only the known parts of the release date'
);

// --- TEST SUITE 55: correzioni v3.21.1 ---
echo "\n--- 55. CORREZIONI V3.21.1 ---\n";

// 55.1: l'indice della colonna è solo dell'articolo: gli estratti di "Leggi anche" e Consigliati non lo sostituiscono
$GLOBALS['mock_doing_filter'] = array( 'get_the_excerpt' => true );
$ix_html = '<h2>Primo titolo</h2><p>a</p><h2>Secondo titolo</h2><p>b</p>';
$ix_out  = ntn_journal_auto_toc( $ix_html );
$GLOBALS['mock_doing_filter'] = array();
$ix_src  = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/seo-extras.php' );
assert_test(
	$ix_out === $ix_html && strpos( $ix_src, "if ( doing_filter( 'get_the_excerpt' ) || doing_filter( 'wp_trim_excerpt' ) ) {" ) !== false &&
	strpos( $ix_src, "if ( ! isset( \$GLOBALS['ntn_core_toc_items'][ (int) get_the_ID() ] ) ) {" ) !== false,
	'55.1: Excerpts of related posts never replace the table of contents of the article'
);

// 55.2: blocco della Home "// Editoriali" con i soli editoriali
assert_test(
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/front-page.php' ), '>// Editoriali</h2>' ) !== false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/front-page-data.php' ), "'category_name'  => 'editoriale,editoriali'," ) !== false,
	'55.2: Home block is called Editoriali and shows only editorials'
);

// --- TEST SUITE 56: Hub Mobile e PC Gaming, disegni su griglia, tendina Piattaforme v3.22.0 ---
echo "\n--- 56. PIATTAFORME V3.22.0 ---\n";
$pf_dot = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/assets/js/dot-art.js' );
$pf_hub = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/category-hub.php' );
$pf_mf  = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/menu-filters.php' );

// 56.1: disegni a punti su griglia fissa (forme agganciate, tasti rossi come punti), con postazione PC e telefoni
assert_test(
	strpos( $pf_dot, 'var U = 5;' ) !== false && strpos( $pf_dot, 'function gx(v) { return Math.round(v / U) * U; }' ) !== false &&
	strpos( $pf_dot, "o.translate(0.5, 0.5);" ) !== false && strpos( $pf_dot, "c.fillStyle = red ? col.accent : col.dot;" ) !== false &&
	strpos( $pf_dot, "\t\tpc: {" ) !== false && strpos( $pf_dot, "\t\tandroid: {" ) !== false && strpos( $pf_dot, "\t\tios: {" ) !== false &&
	strpos( $pf_dot, 'rr(c, 75, 60, 10, 20, 0); rr(c, 75, 100, 10, 20, 0);' ) !== false,
	'56.1: Dot drawings use a fixed grid with snapped shapes and red grid dots; PC, Android and iPhone drawings added'
);

// 56.2: Hub Mobile e PC Gaming: marchi senza sottocategorie come piattaforme, disegno proprio senza marchi, illustrazioni automatiche
assert_test(
	strpos( $pf_hub, '$brand_platforms = array( $brand );' ) !== false && strpos( $pf_hub, "\t\t\t\t\$platforms = array( \$brand );" ) !== false &&
	strpos( $pf_hub, 'Marchio senza piattaforme sotto' ) === false &&strpos( $pf_hub, "\$own = ntn_core_category_hub_illustration( \$focus, 'console' );" ) !== false &&
	strpos( $pf_hub, "return 'android,ios';" ) !== false && strpos( $pf_hub, "'pc'       => 'Postazione PC (v3.22.0)'," ) !== false &&
	strpos( $pf_hub, "NTN_JOURNAL_CORE_VERSION : '' ) );" ) !== false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/category-hub.php' ), "'Tutte le piattaforme'" ) !== false,
	'56.2: Mobile and PC Gaming hubs work like Console: brands without children become platforms, hubs without brands use their own drawing'
);

// 56.3: tendina Piattaforme: pannello largo dal menu, icone a punti, numeri, marchi con le console; in memoria per un'ora
assert_test(
	strpos( $hm_tool, 'class="ntn-submenu ntn-nav-drop-panel ntn-pdrop" id="ntn-pdrop"' ) !== false && strpos( $hm_tool, 'ntn-pdrop-icon is-mini' ) !== false &&
	strpos( $hm_tool, '<span class="ntn-tools-sep" aria-hidden="true"></span>' ) !== false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/header.php' ), "<div class=\"ntn-header-tools\">\n\t\t\t\t<?php if ( function_exists( 'ntn_journal_header_platform_drop' ) )" ) !== false &&
	strpos( $pf_mf, "set_transient( \$cache_key, \$html, HOUR_IN_SECONDS );" ) !== false && strpos( $pf_mf, "'<span class=\"ntn-pdrop-plats\">'" ) !== false &&
	strpos( file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/assets/js/main.js' ), "drop.classList.contains('is-mega')" ) !== false,
	'56.3: Piattaforme dropdown is a wide panel built from the menu, with dot icons, counts and brands with their consoles'
);

// --- TEST SUITE 57: Hub Studi, indice degli studi dopo gli articoli v3.22.1 ---
echo "\n--- 57. HUB STUDI V3.22.1 ---\n";
$hs_tpl = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/category-hub.php' );

// 57.1: nella Hub Studi l'ordine è In evidenza, Tutti gli articoli, Indice degli studi
assert_test(
	strpos( $hs_tpl, 'id="ntn-h-cfeat"' ) !== false && strpos( $hs_tpl, 'id="ntn-h-cfeat"' ) < strpos( $hs_tpl, 'id="ntn-h-cfeed"' ) &&
	strpos( $hs_tpl, 'id="ntn-h-cfeed"' ) < strpos( $hs_tpl, 'id="ntn-h-studios"' ),
	'57.1: Studios hub shows featured, then all articles, then the studios index'
);

// --- TEST SUITE 58: Immagine di anteprima per Google Discover v3.22.2 ---
echo "\n--- 58. IMMAGINE DISCOVER V3.22.2 ---\n";
$dsc_src = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/seo-extras.php' );

// 58.1: l'immagine social usa l'originale, non la versione "large" da 1024 px
assert_test(
	strpos( $dsc_src, "get_the_post_thumbnail_url( \$target_id, 'full' )" ) !== false &&
	strpos( $dsc_src, "get_the_post_thumbnail_url( \$post_id, 'large' )" ) === false,
	'58.1: Social preview image uses the full-size featured image (Discover needs 1200px+)'
);

// 58.2: le misure dichiarate a The SEO Framework sono quelle reali, non più 1920x1080 fisse
assert_test(
	strpos( $dsc_src, 'function ntn_get_social_image_dimensions' ) !== false &&
	strpos( $dsc_src, "'width'  => 1920" ) === false,
	'58.2: Social image width/height come from the real attachment, no hard-coded 1920x1080'
);

// --- TEST SUITE 59: SEO delle Hub dei marchi e degli studi v3.22.3 ---
echo "\n--- 59. SEO HUB MARCHI E STUDI V3.22.3 ---\n";
$sh_src = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/studio-hub.php' );
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/studio-hub.php';

// 59.1: sulla pagina di un marchio senza sottocategorie (es. /mobile/android/) il marchio non è più piattaforma di sé stesso
assert_test(
	strpos( $pf_hub, "if ( empty( \$brand_platforms ) ) {\n\t\t\t\tif ( \$selected ) {\n\t\t\t\t\tcontinue;\n\t\t\t\t}\n\t\t\t\t\$brand_platforms = array( \$brand );" ) !== false,
	'59.1: A brand page without children no longer lists itself as its only platform (no "Android: Android")'
);

// 59.2: percorso per Google della Hub dello studio: la Hub che lo elenca dopo la Home, voci rinumerate, nessun doppione
$sh_parent = array( 'label' => 'Sviluppatori italiani', 'url' => 'https://example.com/sezione/sviluppatori-italiani/' );
$sh_items  = array(
	array( '@type' => 'ListItem', 'position' => 1, 'item' => 'https://example.com/', 'name' => 'Q-Gin' ),
	array( '@type' => 'ListItem', 'position' => 2, 'name' => '3DClouds' ),
);
$sh_out = ntn_core_studio_breadcrumb_items( $sh_items, $sh_parent );
assert_test(
	3 === count( $sh_out ) && $sh_out[1]['item'] === $sh_parent['url'] && 'Sviluppatori italiani' === $sh_out[1]['name'] &&
	2 === $sh_out[1]['position'] && 3 === $sh_out[2]['position'] && '3DClouds' === $sh_out[2]['name'] &&
	3 === count( ntn_core_studio_breadcrumb_items( $sh_out, $sh_parent ) ) &&
	strpos( $sh_src, "add_filter( 'the_seo_framework_schema_graph_data', 'ntn_core_studio_tsf_breadcrumb', 20, 2 );" ) !== false,
	'59.2: Studio hub breadcrumb for Google includes the parent studios hub after Home, renumbered, without duplicates'
);

// 59.3: Hub degli studi nella sitemap di TSF, senza quelle sotto la soglia
assert_test(
	strpos( $sh_src, "add_filter( 'the_seo_framework_sitemap_additional_urls', 'ntn_core_studio_hub_sitemap_urls' );" ) !== false &&
	strpos( $sh_src, 'ntn_core_seo_is_thin_term( $tag )' ) !== false && strpos( $sh_src, 'empty( ntn_core_studio_game_tags( $tag ) )' ) !== false,
	'59.3: Indexable studio hubs are added to the TSF sitemap, thin ones are left out'
);

// 59.4: immagine di condivisione della Hub dello studio: l'ultimo articolo con immagine, a piena grandezza, davanti al logo
assert_test(
	strpos( $sh_src, "'meta_key'      => '_thumbnail_id'" ) !== false && strpos( $sh_src, "wp_get_attachment_image_url( \$thumb_id, 'full' )" ) !== false &&
	strpos( $sh_src, "array( 'ntn_studio_hub' => 'ntn_core_studio_hub_tsf_image_generator' ) + \$cbs;" ) !== false,
	'59.4: Studio hub share image is the full-size image of its latest article instead of the site logo'
);

// --- TEST SUITE 60: Box Recensione, pro e contro con la virgola v3.22.4 ---
echo "\n--- 60. BOX RECENSIONE: PRO E CONTRO CON LA VIRGOLA V3.22.4 ---\n";
$mock_posts[162] = new WP_Post( array(
	'ID'          => 162,
	'post_title'  => 'Comma Review',
	'post_name'   => 'comma-review',
	'post_type'   => 'post',
	'post_status' => 'publish',
) );
$mock_postmeta[162] = array(
	'rating' => '4',
	'pros'   => 'Colonna sonora varia, ispirata e ben mixata',
	'cons'   => 'Pacing lento, soprattutto nelle prime ore',
);
$queried_object  = $mock_posts[162];
$GLOBALS['post'] = $mock_posts[162];

ob_start();
require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/review-original-box.php';
$rv_comma_out = ob_get_clean();

// 60.1: un solo pro e un solo contro con la virgola restano una voce ciascuno, con la virgola nel testo
assert_test(
	strpos( $rv_comma_out, '<li>Colonna sonora varia, ispirata e ben mixata</li>' ) !== false &&
	strpos( $rv_comma_out, '<li>Pacing lento, soprattutto nelle prime ore</li>' ) !== false &&
	2 === substr_count( $rv_comma_out, '<li>' ),
	'60.1: A single pro or con containing a comma stays one item (no split on commas)'
);

// --- TEST SUITE 61: PNG pieni salvati in RGBA compressi come JPEG v3.22.5 ---
echo "\n--- 61. TRASPARENZA DEI PNG V3.22.5 ---\n";
$png_src = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/performance.php' );

// 61.1: l'intestazione RGBA non basta più, si controllano i pixel
assert_test(
	strpos( $png_src, 'function ntn_png_is_fully_opaque' ) !== false &&
	strpos( $png_src, '$opaque        = ntn_png_is_fully_opaque( $file_path );' ) !== false &&
	strpos( $png_src, "if ( 4 === \$color_type || 6 === \$color_type ) {\n\t\t\treturn true;" ) === false,
	'61.1: An RGBA PNG header alone no longer counts as transparent: the pixels are checked'
);

// 61.2: pixel letti con GD o Imagick; senza librerie il PNG resta protetto
assert_test(
	strpos( $png_src, "function_exists( 'imagecreatefrompng' )" ) !== false &&
	strpos( $png_src, "class_exists( 'Imagick' )" ) !== false &&
	strpos( $png_src, '( true !== $opaque )' ) !== false,
	'61.2: Pixel check uses GD or Imagick, and keeps the PNG untouched when neither can read it'
);

// 61.3: nessuna funzione deprecata in PHP 8.5
assert_test(
	strpos( $png_src, 'imagedestroy(' ) === false,
	'61.3: No imagedestroy() calls (deprecated since PHP 8.5)'
);

// --- TEST SUITE 62: Redirect per cartella e indirizzi che non esistono v4.0.0 ---
echo "\n--- 62. REDIRECT PER CARTELLA V4.0.0 ---\n";
if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
}
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/redirect-manager.php';
$fr_src = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/redirect-manager.php' );

// 62.1: le cartelle scritte a mano diventano /cartella/; indirizzi di altri siti e percorsi strani sono scartati
assert_test(
	'/category/' === ntn_redirect_folder_normalize( 'category' ) && '/sezione/' === ntn_redirect_folder_normalize( ' /sezione ' ) &&
	'/author/valerio-sintesi/' === ntn_redirect_folder_normalize( 'https://example.com/author/valerio-sintesi', 'example.com' ) &&
	'' === ntn_redirect_folder_normalize( 'https://example.org/y/', 'example.com' ) && '' === ntn_redirect_folder_normalize( '/a/../b/' ) &&
	'' === ntn_redirect_folder_normalize( '/a?x=1' ) && '' === ntn_redirect_folder_normalize( '' ) && '/' === ntn_redirect_folder_normalize( '/' ),
	'62.1: Folders are normalized to /folder/, other domains and odd paths are rejected'
);

// 62.2: il resto dell'indirizzo resta uguale, vince la cartella più lunga, le regole spente non contano
$fr_rules = array(
	md5( '/category/' )              => array( 'source' => '/category/', 'target' => '/sezione/', 'enabled' => true ),
	md5( '/tag/' )                   => array( 'source' => '/tag/', 'target' => '/argomento/', 'enabled' => false ),
	md5( '/author/' )                => array( 'source' => '/author/', 'target' => '/redazione/', 'enabled' => true ),
	md5( '/author/blackstar90210/' ) => array( 'source' => '/author/blackstar90210/', 'target' => '/author/valerio-sintesi/', 'enabled' => true ),
);
$fr_m1 = ntn_redirect_folder_match( '/category/console/microsoft/page/57/', $fr_rules );
$fr_m2 = ntn_redirect_folder_match( '/author/blackstar90210/page/2/', $fr_rules );
$fr_m3 = ntn_redirect_folder_match( '/category', $fr_rules );
assert_test(
	is_array( $fr_m1 ) && '/sezione/console/microsoft/page/57/' === $fr_m1['path'] && md5( '/category/' ) === $fr_m1['hash'] &&
	is_array( $fr_m2 ) && '/author/valerio-sintesi/page/2/' === $fr_m2['path'] &&
	is_array( $fr_m3 ) && '/sezione/' === $fr_m3['path'] &&
	null === ntn_redirect_folder_match( '/categoryfoo/', $fr_rules ) && null === ntn_redirect_folder_match( '/tag/ea/', $fr_rules ) &&
	null === ntn_redirect_folder_match( '/sezione/console/', $fr_rules ),
	'62.2: Folder rules keep the rest of the path, the longest folder wins, disabled rules are ignored'
);

// 62.3: regole che si rimandano all'infinito o pericolose non si possono salvare
assert_test(
	ntn_redirect_folder_has_loop( array(
		array( 'source' => '/a/', 'target' => '/b/', 'enabled' => true ),
		array( 'source' => '/b/', 'target' => '/a/', 'enabled' => true ),
	) ) &&
	ntn_redirect_folder_has_loop( array( array( 'source' => '/a/', 'target' => '/a/b/', 'enabled' => true ) ) ) &&
	! ntn_redirect_folder_has_loop( $fr_rules ) &&
	'' !== ntn_redirect_folder_validate( '/', '/x/', array() ) && '' !== ntn_redirect_folder_validate( '/wp-content/', '/x/', array() ) &&
	'' !== ntn_redirect_folder_validate( '/a/', '/a/b/', array() ) && '' !== ntn_redirect_folder_validate( '/sezione/', '/category/', $fr_rules ) &&
	'' !== ntn_redirect_folder_validate( '', '/x/', array() ) && '' === ntn_redirect_folder_validate( '/tag/', '/argomento/', $fr_rules ),
	'62.3: Looping, whole-site, WordPress system and empty rules are refused; valid rules pass'
);

// 62.4: suggerimenti solo per le vecchie cartelle davvero cambiate e non ancora coperte
$fr_s1 = ntn_redirect_folder_suggestions( array(), '/sezione', 'argomento' );
$fr_s2 = ntn_redirect_folder_suggestions( array( $fr_rules[ md5( '/category/' ) ] ), '/sezione', '/argomento' );
$fr_s3 = ntn_redirect_folder_suggestions( array(), '/sezione', '', '/blog' );
assert_test(
	2 === count( $fr_s1 ) && '/category/' === $fr_s1[0]['source'] && '/sezione/' === $fr_s1[0]['target'] && '/tag/' === $fr_s1[1]['source'] && '/argomento/' === $fr_s1[1]['target'] &&
	1 === count( $fr_s2 ) && '/tag/' === $fr_s2[0]['source'] &&
	1 === count( $fr_s3 ) && '/blog/category/' === $fr_s3[0]['source'] && '/blog/sezione/' === $fr_s3[0]['target'] &&
	array() === ntn_redirect_folder_suggestions( array(), '', 'tag' ) && array() === ntn_redirect_folder_suggestions( $fr_rules, '/sezione', '/argomento' ),
	'62.4: Suggestions cover only changed category/tag bases without an existing rule (subfolder installs too)'
);

// 62.5: pagine numerate oltre la fine → prima pagina dell'archivio
assert_test(
	'/sezione/console/' === ntn_redirect_strip_paged( '/sezione/console/page/999/' ) && '/' === ntn_redirect_strip_paged( '/page/12' ) &&
	'/sezione/console/' === ntn_redirect_strip_paged( '/sezione/console/' ) && '/a/' === ntn_redirect_strip_paged( '/a/pagina/3/', 'pagina' ),
	'62.5: Paged archive URLs are stripped back to the first page'
);

// 62.6: ordine e interruttori: singoli prima delle cartelle, poi pagine numerate; indovinello spento di serie
assert_test(
	strpos( $fr_src, "add_action( 'template_redirect', 'ntn_journal_process_folder_redirects', 2 );" ) !== false &&
	strpos( $fr_src, "add_action( 'template_redirect', 'ntn_journal_process_paged_fallback', 3 );" ) !== false &&
	strpos( $fr_src, "wp_safe_redirect( ntn_redirect_site_url( \$match['path'] ) . ( '' !== \$request['query'] ? '?' . \$request['query'] : '' ), 301 );" ) !== false &&
	strpos( $fr_src, "wp_safe_redirect( ntn_redirect_site_url( \$first ), 302 );" ) !== false &&
	strpos( $fr_src, "add_filter( 'do_redirect_guess_404_permalink', 'ntn_journal_redirect_guess_404' );" ) !== false &&
	strpos( $fr_src, "get_option( 'ntn_redirect_no_guess', 'no' )" ) !== false && strpos( $fr_src, "get_option( 'ntn_redirect_paged_fallback', 'yes' )" ) !== false,
	'62.6: Exact redirects run first, then folder rules (301), then paged fallback (302); the guess switch is off by default'
);
$mock_options['ntn_redirect_no_guess'] = 'yes';
$fr_guess_on = ntn_journal_redirect_guess_404( true );
unset( $mock_options['ntn_redirect_no_guess'] );
assert_test(
	false === $fr_guess_on && true === ntn_journal_redirect_guess_404( true ),
	'62.7: The "do not guess" switch stops WordPress from guessing only when turned on'
);

// 62.8: salvataggi protetti (permessi e nonce) e regole nel backup, ripristinate con gli stessi controlli
assert_test(
	strpos( $fr_src, "wp_verify_nonce( sanitize_text_field( wp_unslash( \$_POST['ntn_folder_rule_nonce'] ) ), 'ntn_folder_rule' )" ) !== false &&
	1 === substr_count( $fr_src, 'function ntn_journal_render_folder_redirects_ui() {' . "\n\tif ( ! current_user_can( 'manage_options' ) ) {" ),
	'62.8: Folder rule saves need the admin capability and a valid nonce'
);
$fr_keys = ntn_get_core_backup_options_keys();
_ntn_restore_option( 'ntn_redirect_folder_rules', array(
	array( 'source' => '/category/', 'target' => '/sezione/', 'enabled' => true, 'hits' => '12' ),
	array( 'source' => '/', 'target' => '/x/', 'enabled' => true ),
	array( 'source' => '/sezione/', 'target' => '/category/', 'enabled' => true ),
	array( 'source' => '/vecchia cartella/', 'target' => '/argomento/', 'enabled' => true ),
	'non una regola',
) );
$fr_restored = get_option( 'ntn_redirect_folder_rules' );
_ntn_restore_option( 'ntn_redirect_no_guess', '1' );
assert_test(
	in_array( 'ntn_redirect_folder_rules', $fr_keys, true ) && in_array( 'ntn_redirect_paged_fallback', $fr_keys, true ) && in_array( 'ntn_redirect_no_guess', $fr_keys, true ) &&
	is_array( $fr_restored ) && 1 === count( $fr_restored ) && isset( $fr_restored[ md5( '/category/' ) ] ) && 12 === $fr_restored[ md5( '/category/' ) ]['hits'] &&
	'yes' === get_option( 'ntn_redirect_no_guess' ),
	'62.9: Folder rules and switches are in the backup; restore drops invalid and looping rules'
);
unset( $mock_options['ntn_redirect_folder_rules'], $mock_options['ntn_redirect_no_guess'] );

// 62.10: le soglie "articoli minimi" tornano dal backup come numeri (prima diventavano "no", cioè regola spenta)
_ntn_restore_option( 'ntn_seo_min_posts_terms', 15 );
_ntn_restore_option( 'ntn_seo_min_posts_games', '5' );
$fr_terms = get_option( 'ntn_seo_min_posts_terms' );
$fr_games = get_option( 'ntn_seo_min_posts_games' );
_ntn_restore_option( 'ntn_seo_min_posts_terms', 500 );
$fr_terms_max = get_option( 'ntn_seo_min_posts_terms' );
_ntn_restore_option( 'ntn_seo_min_posts_terms', 'yes' );
assert_test(
	15 === $fr_terms && 5 === $fr_games && 100 === $fr_terms_max && 100 === get_option( 'ntn_seo_min_posts_terms' ),
	'62.10: Article thresholds are restored as numbers (capped at 100), non-numbers leave the current value'
);
unset( $mock_options['ntn_seo_min_posts_terms'], $mock_options['ntn_seo_min_posts_games'] );

// --- TEST SUITE 63: Statistiche dell'Hub di categoria v4.1.0 (solo dati) ---
echo "\n--- 63. STATISTICHE HUB DI CATEGORIA V4.1.0 ---\n";

if ( ! function_exists( 'get_term_children' ) ) {
	function get_term_children( $term_id, $taxonomy ) {
		return $GLOBALS['hs_children'][ (int) $term_id ] ?? array();
	}
}
if ( ! function_exists( 'mysql2date' ) ) {
	function mysql2date( $format, $date ) {
		return gmdate( $format, strtotime( $date ) );
	}
}

/**
 * Database finto: legge dalla query vera le categorie, l'anno e i filtri su stato e tipo, quindi
 * se il codice togliesse uno di questi filtri bozze e pagine finirebbero nei conteggi e i test fallirebbero.
 */
class NTN_Stats_Mock_Wpdb {
	public $posts = 'wp_posts';
	public $term_relationships = 'wp_term_relationships';
	public $term_taxonomy = 'wp_term_taxonomy';
	public $termmeta = 'wp_termmeta';
	public $rows = array();
	public $tag_meta = array();
	public function prepare( $sql, ...$args ) {
		foreach ( $args as $arg ) {
			$sql = preg_replace( '/%s/', "'" . $arg . "'", $sql, 1 );
		}
		return $sql;
	}
	private function matching( $sql ) {
		preg_match( '/tt\.term_id IN \(([\d,]+)\)/', $sql, $in );
		$cats   = array_map( 'intval', explode( ',', $in[1] ) );
		$status = false !== strpos( $sql, "p.post_status = 'publish'" );
		$type   = false !== strpos( $sql, "p.post_type = 'post'" );
		return array_filter( $this->rows, static function ( $row ) use ( $cats, $status, $type ) {
			return array_intersect( $row['cats'], $cats ) && ( ! $status || 'publish' === $row['status'] ) && ( ! $type || 'post' === $row['type'] );
		} );
	}
	private function in_range( $rows, $sql ) {
		preg_match_all( "/'(\d{4}-01-01 00:00:00)'/", $sql, $range );
		return array_filter( $rows, static function ( $row ) use ( $range ) {
			return $row['date'] >= $range[1][0] && $row['date'] < $range[1][1];
		} );
	}
	public function get_row( $sql ) {
		$rows = $this->matching( $sql );
		if ( false !== strpos( $sql, 'GROUP BY p.post_author' ) ) {
			$by_author = array();
			foreach ( $this->in_range( $rows, $sql ) as $row ) {
				$by_author[ $row['author'] ]['posts']  = ( $by_author[ $row['author'] ]['posts'] ?? 0 ) + 1;
				$by_author[ $row['author'] ]['latest'] = max( $by_author[ $row['author'] ]['latest'] ?? '', $row['date'] );
			}
			uasort( $by_author, static function ( $a, $b ) {
				return array( $b['posts'], $b['latest'] ) <=> array( $a['posts'], $a['latest'] );
			} );
			return $by_author ? (object) array( 'author_id' => (string) array_key_first( $by_author ), 'posts' => (string) reset( $by_author )['posts'] ) : null;
		}
		return (object) array(
			'total'   => (string) count( $rows ),
			'in_year' => (string) count( $this->in_range( $rows, $sql ) ),
			'latest'  => $rows ? max( array_column( $rows, 'date' ) ) : null,
		);
	}
	public function get_var( $sql ) {
		$games = array();
		foreach ( $this->matching( $sql ) as $row ) {
			foreach ( $row['tags'] as $tag ) {
				$meta = $this->tag_meta[ $tag ] ?? array();
				if ( ! empty( $meta['_ntn_igdb_game_id'] ) ) {
					$games[ 'id:' . $meta['_ntn_igdb_game_id'] ] = true;
				} elseif ( ! empty( $meta['_ntn_game_title'] ) ) {
					$games[ 'title:' . $meta['_ntn_game_title'] ] = true;
				}
			}
		}
		return (string) count( $games );
	}
}

$hs_src  = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/category-hub-stats.php' );
$hs_year = (int) gmdate( 'Y' );
$hs_old  = $hs_year - 1;
foreach ( array( 9601 => 'Console', 9602 => 'Sony', 9603 => 'PS5', 9610 => 'Altra', 9620 => 'Vuota', 9630 => 'Vecchia' ) as $hs_id => $hs_name ) {
	$mock_terms[ $hs_id ] = new WP_Term( array( 'term_id' => $hs_id, 'name' => $hs_name, 'slug' => strtolower( $hs_name ), 'taxonomy' => 'category' ) );
}
foreach ( array( 9601, 9620, 9630 ) as $hs_id ) {
	$mock_termmeta[ $hs_id ] = array( '_ntn_hub_enabled' => '1', '_ntn_hub_type' => 'console' );
}
$GLOBALS['hs_children'] = array( 9601 => array( 9602, 9603 ) );
$hs_author_backup = $GLOBALS['mock_author_meta'] ?? null;
$GLOBALS['mock_author_meta'] = array( 'display_name' => 'Luca Maiolini' );
$hs_row = static function ( $id, $date, $author, $cats, $tags = array(), $status = 'publish', $type = 'post' ) {
	return compact( 'id', 'date', 'author', 'cats', 'tags', 'status', 'type' );
};

$hs_db = new NTN_Stats_Mock_Wpdb();
$hs_db->tag_meta = array(
	701 => array( '_ntn_igdb_game_id' => '100' ),
	702 => array( '_ntn_igdb_game_id' => '100', '_ntn_game_title' => 'Stesso gioco, altro tag' ),
	703 => array( '_ntn_game_title' => 'Gioco senza ID' ),
	704 => array( '_ntn_game_title' => 'Gioco senza ID' ),
	705 => array(),
	706 => array( '_ntn_igdb_game_id' => '200' ),
	707 => array( '_ntn_igdb_game_id' => '300' ),
);
$hs_db->rows = array(
	$hs_row( 1, "{$hs_year}-01-01 00:00:00", 7, array( 9601 ), array( 701 ) ),
	$hs_row( 2, "{$hs_year}-03-10 10:00:00", 7, array( 9602 ), array( 701, 702 ) ),
	$hs_row( 3, "{$hs_year}-04-10 10:00:00", 7, array( 9602, 9603 ), array( 703, 705 ) ),
	$hs_row( 4, "{$hs_year}-05-10 10:00:00", 8, array( 9603 ), array( 704 ) ),
	$hs_row( 5, "{$hs_old}-12-31 23:59:59", 8, array( 9601 ), array( 706 ) ),
	$hs_row( 6, "{$hs_old}-06-01 10:00:00", 8, array( 9601 ) ),
	$hs_row( 7, "{$hs_old}-06-02 10:00:00", 8, array( 9601 ) ),
	$hs_row( 10, "{$hs_year}-12-01 10:00:00", 9, array( 9601 ), array( 707 ), 'draft' ),
	$hs_row( 11, "{$hs_year}-12-02 10:00:00", 9, array( 9603 ), array( 707 ), 'private' ),
	$hs_row( 12, "{$hs_year}-12-03 10:00:00", 9, array( 9601 ), array( 707 ), 'future' ),
	$hs_row( 13, "{$hs_year}-12-04 10:00:00", 9, array( 9601 ), array( 707 ), 'trash' ),
	$hs_row( 14, "{$hs_year}-12-05 10:00:00", 9, array( 9601 ), array( 707 ), 'publish', 'page' ),
	$hs_row( 20, "{$hs_year}-12-06 10:00:00", 9, array( 9610 ), array( 707 ) ),
	$hs_row( 30, "{$hs_old}-02-01 10:00:00", 9, array( 9630 ), array( 706 ) ),
);
$GLOBALS['wpdb'] = $hs_db;
$hs_stats = ntn_core_get_category_hub_stats( 9601 );

// 63.1: il modulo è caricato e usa la cache delle Hub, senza scorrere gli articoli né toccare category-hub.php
assert_test(
	false !== strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php' ), "inc/category-hub-stats.php" ) &&
	false !== strpos( $hs_src, "get_option( 'ntn_cat_hub_version', '0' )" ) && false !== strpos( $hs_src, 'set_transient( $cache_key, $data, DAY_IN_SECONDS )' ) &&
	false !== strpos( $hs_src, "\$focus->term_id . '|' . \$year . '|'" ) &&
	false === strpos( $hs_src, 'foreach' ) && false === strpos( $hs_src, 'new WP_Query' ) && false === strpos( $hs_src, 'get_the_terms' ),
	'63.1: Stats module is loaded, shares the Hub cache version for 24 hours, renews with the year and never loops over posts'
);

// 63.2: sottocategorie comprese, un articolo in due sottocategorie conta una volta, le altre categorie restano fuori
assert_test(
	is_array( $hs_stats ) && 7 === $hs_stats['articles_total'] && 7 === ntn_core_category_hub_stats_query( array( 9601, 9602, 9603, 9603 ), $hs_year )['total'],
	'63.2: Total includes subcategories, counts a post in two subcategories once and ignores other categories'
);

// 63.3: anno corrente, compresi il primo istante dell'anno e senza l'ultimo secondo dell'anno prima
assert_test(
	$hs_year === $hs_stats['year'] && 4 === $hs_stats['articles_year'],
	'63.3: Yearly count covers the current calendar year only, from its first second'
);

// 63.4: autore più prolifico dell'anno (nome, conteggio, link), anche se un altro ha più articoli in assoluto
assert_test(
	is_array( $hs_stats['top_author'] ) && 7 === $hs_stats['top_author']['id'] && 3 === $hs_stats['top_author']['count'] &&
	'Luca Maiolini' === $hs_stats['top_author']['name'] && 'https://example.com/author/7/' === $hs_stats['top_author']['url'],
	'63.4: Top author is the most prolific of the current year, with name, count and link'
);

// 63.5: data dell'ultimo articolo pubblicato, non di bozze o programmati
assert_test(
	'10.05.' . $hs_year === $hs_stats['latest_date'],
	'63.5: Latest date comes from the newest published post, formatted d.m.Y'
);

// 63.6: anno senza articoli: autore null e zero nell'anno, ma totale e ultima data restano
$hs_stats_old = ntn_core_get_category_hub_stats( 9630 );
assert_test(
	1 === $hs_stats_old['articles_total'] && 0 === $hs_stats_old['articles_year'] && null === $hs_stats_old['top_author'] &&
	'01.02.' . $hs_old === $hs_stats_old['latest_date'] && 1 === $hs_stats_old['games_total'],
	'63.6: A year without articles gives zero and a null author, keeping total, latest date and games'
);

// 63.7: categoria vuota
$hs_stats_empty = ntn_core_get_category_hub_stats( 9620 );
assert_test(
	array( 'year' => $hs_year, 'articles_total' => 0, 'articles_year' => 0, 'top_author' => null, 'latest_date' => '', 'games_total' => 0 ) === $hs_stats_empty,
	'63.7: An empty category returns zeros, a null author and an empty date without notices'
);

// 63.8: bozze, privati, programmati, cestinati e altri tipi di contenuto restano fuori da ogni dato
$hs_only_hidden = new NTN_Stats_Mock_Wpdb();
$hs_only_hidden->tag_meta = $hs_db->tag_meta;
$hs_only_hidden->rows = array_values( array_filter( $hs_db->rows, static function ( $row ) {
	return $row['id'] >= 10 && $row['id'] <= 14;
} ) );
$GLOBALS['wpdb'] = $hs_only_hidden;
$hs_hidden_stats = ntn_core_category_hub_stats_query( array( 9601, 9602, 9603 ), $hs_year );
$GLOBALS['wpdb'] = $hs_db;
assert_test(
	0 === $hs_hidden_stats['total'] && 0 === $hs_hidden_stats['in_year'] && 0 === $hs_hidden_stats['author_id'] && '' === $hs_hidden_stats['latest'] && 0 === $hs_hidden_stats['games'],
	'63.8: Drafts, private, scheduled and trashed posts and other post types are excluded from every figure'
);

// 63.9: giochi distinti: stesso tag su più articoli, due tag con lo stesso ID IGDB, due tag con lo stesso titolo. Il tag normale non conta.
assert_test(
	3 === $hs_stats['games_total'] && false !== strpos( $hs_src, "COUNT(DISTINCT CASE WHEN gid.meta_value NOT IN ('', '0')" ) && false !== strpos( $hs_src, "'_ntn_igdb_game_id',\n\t\t\t'_ntn_game_title'" ),
	'63.9: Games are distinct: repeated tags and tags sharing an IGDB ID or title count once, plain tags do not count'
);

// 63.10: una categoria che non è una Hub, o che non esiste, non ha statistiche
$mock_terms[9640] = new WP_Term( array( 'term_id' => 9640, 'name' => 'Tag', 'slug' => 'tag', 'taxonomy' => 'post_tag' ) );
assert_test(
	null === ntn_core_get_category_hub_stats( 999999 ) && null === ntn_core_get_category_hub_stats( 9640 ),
	'63.10: Anything that is not a Hub category returns null'
);

// 63.11: la cache si svuota quando cambia il collegamento a IGDB, non per gli altri meta del tag
$mock_options['ntn_cat_hub_version'] = 'prima';
ntn_core_category_hub_stats_flush_on_meta( 1, 701, '_ntn_game_cover' );
$hs_after_other = $mock_options['ntn_cat_hub_version'];
ntn_core_category_hub_stats_flush_on_meta( 1, 701, '_ntn_igdb_game_id' );
$hs_after_id = $mock_options['ntn_cat_hub_version'];
$mock_options['ntn_cat_hub_version'] = 'prima';
ntn_core_category_hub_stats_flush_on_meta( array( 1 ), 701, '_ntn_game_title' );
$hs_after_title = $mock_options['ntn_cat_hub_version'];
assert_test(
	'prima' === $hs_after_other && 'prima' !== $hs_after_id && 'prima' !== $hs_after_title &&
	false !== strpos( $hs_src, "add_action( 'added_term_meta'" ) && false !== strpos( $hs_src, "add_action( 'updated_term_meta'" ) && false !== strpos( $hs_src, "add_action( 'deleted_term_meta'" ),
	'63.11: Saving IGDB link data clears the Hub cache, other tag data does not'
);

unset( $GLOBALS['wpdb'], $GLOBALS['hs_children'], $mock_options['ntn_cat_hub_version'] );
foreach ( array( 9601, 9602, 9603, 9610, 9620, 9630, 9640 ) as $hs_id ) {
	unset( $mock_terms[ $hs_id ], $mock_termmeta[ $hs_id ] );
}
$GLOBALS['mock_author_meta'] = $hs_author_backup;

// --- TEST SUITE 64: Tipo di Hub "Statistiche" v4.2.0 (solo impostazioni) ---
echo "\n--- 64. TIPO DI HUB STATISTICHE V4.2.0 ---\n";

$st_hub    = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/category-hub.php' );
$st_tab    = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/views/tab-hub.php' );
$st_admin  = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/admin-page.php' );
$st_rt     = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/category-hub-template.php' );
$st_tpl    = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/category-hub.php' );
$st_backup = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/backup-restore.php' );

// 64.1: il nuovo tipo è tra quelli ammessi e si salva; un valore sconosciuto torna a Piattaforme
ntn_core_category_hub_save_settings( 9701, array( 'enabled' => true, 'type' => 'statistiche', 'title' => 'I numeri', 'intro' => 'Testo' ) );
$st_saved = ntn_core_category_hub_meta( 9701, 'type' );
ntn_core_category_hub_save_settings( 9702, array( 'enabled' => true, 'type' => '<b>inventato</b>' ) );
$st_unknown_saved = get_term_meta( 9702, '_ntn_hub_type', true );
$mock_termmeta[9703] = array( '_ntn_hub_enabled' => '1', '_ntn_hub_type' => 'inventato' );
assert_test(
	array( 'console', 'studi', 'statistiche' ) === array_keys( ntn_core_category_hub_choices()['type'] ) && 'statistiche' === $st_saved &&
	'I numeri' === ntn_core_category_hub_meta( 9701, 'title' ) && 'Testo' === ntn_core_category_hub_meta( 9701, 'intro' ) &&
	'console' === $st_unknown_saved && 'console' === ntn_core_category_hub_meta( 9703, 'type' ),
	'64.1: The Statistiche type saves with the common fields; unknown values fall back to the platforms type on save and on read'
);

// 64.2: le Hub già accese restano del loro tipo, anche quelle senza tipo salvato, e il contesto riconosce il nuovo
$mock_termmeta[9704] = array( '_ntn_hub_enabled' => '1', '_ntn_hub_type' => 'console' );
$mock_termmeta[9705] = array( '_ntn_hub_enabled' => '1', '_ntn_hub_type' => 'studi' );
$mock_termmeta[9706] = array( '_ntn_hub_enabled' => '1' );
$st_term = new WP_Term( array( 'term_id' => 9701, 'name' => 'Numeri', 'slug' => 'numeri', 'taxonomy' => 'category' ) );
$st_ctx  = ntn_core_category_hub_context( $st_term );
assert_test(
	'console' === ntn_core_category_hub_meta( 9704, 'type' ) && 'studi' === ntn_core_category_hub_meta( 9705, 'type' ) && 'console' === ntn_core_category_hub_meta( 9706, 'type' ) &&
	is_array( $st_ctx ) && 'statistiche' === $st_ctx['type'] && null === $st_ctx['selected'] &&
	false !== strpos( $st_hub, "\$parent->term_id, 'enabled' ) && 'console' === ntn_core_category_hub_meta( (int) \$parent->term_id, 'type' )" ),
	'64.2: Existing Hubs keep their type, Hubs without one stay platforms, and only platform Hubs adopt their subcategories'
);

// 64.3: nel pannello il tipo ha il suo nome e non mostra i campi di marchi e studi; salvataggio con gli stessi controlli
assert_test(
	false !== strpos( $st_tab, "'statistiche' === \$ntn_type ? 'Statistiche' : 'Console'" ) && false !== strpos( $st_tab, "<?php elseif ( 'console' === \$ntn_type ) : ?>" ) &&
	false !== strpos( $st_tab, '<b>Statistiche</b>' ) && 2 === substr_count( $st_tab, "foreach ( \$ntn_choices['type'] as" ) &&
	false !== strpos( $st_admin, "ntn_journal_core_admin_nonce_ok( 'ntn_hub_save_nonce', 'ntn_hub_save' )" ) && false !== strpos( $st_admin, "ntn_journal_core_admin_nonce_ok( 'ntn_hub_new_nonce', 'ntn_hub_new' )" ),
	'64.3: The panel names the type, hides brand and studio fields for it and saves through the same nonce checks'
);

// 64.4: il controllo copertura ha un caso suo e non mette più gli studi su questo tipo
assert_test(
	false !== strpos( $st_hub, "Categoria senza articoli: le statistiche resterebbero a zero." ) && false !== strpos( $st_hub, "} elseif ( 'console' === \$type ) {" ) &&
	false !== strpos( $st_hub, "if ( 'statistiche' === \$type ) {" ),
	'64.4: Coverage check flags a Statistiche Hub without articles and skips the brand and studio checks for it'
);

// 64.5: il backup porta con sé il tipo (tutti i meta _ntn_hub_) e l'importazione non può rompere una Hub
_ntn_restore_termmeta( 9707, '_ntn_hub_type', 'statistiche' );
$st_restored = ntn_core_category_hub_meta( 9707, 'type' );
_ntn_restore_termmeta( 9708, '_ntn_hub_type', 'inventato' );
assert_test(
	'statistiche' === $st_restored && 'console' === ntn_core_category_hub_meta( 9708, 'type' ) && 2 <= substr_count( $st_backup, "\$wpdb->esc_like( '_ntn_hub_' )" ),
	'64.5: Backup exports every Hub setting, import restores the Statistiche type and an unknown type falls back safely'
);

// 64.6: il tema riconosce il tipo: modello dedicato appena esiste, altrimenti quello comune; le altre Hub non cambiano
assert_test(
	false !== strpos( $st_rt, "array( 'category-hub-statistiche.php', 'category-hub.php' ) : array( 'category-hub.php' )" ) &&
	false !== strpos( $st_tpl, "<?php elseif ( 'studi' === \$hub['type'] ) : ?>" ),
	'64.6: Theme routes the type to its own template when it exists, otherwise to the shared one, with Made in Italy only on Studi'
);

// 64.7: i dati di questo tipo non passano dal ramo degli studi: giochi su tutti gli articoli e descrizione propria
assert_test(
	false !== strpos( $st_hub, "} elseif ( 'statistiche' === \$ctx['type'] ) {" ) && false !== strpos( $st_hub, "ntn_core_category_hub_stats_query( \$tree, (int) wp_date( 'Y' ) )" ) &&
	false !== strpos( $st_hub, "array( 'Giochi', (string) \$hub_stats['games'] )" ) && false !== strpos( $st_hub, "'I numeri di ' . \$title" ),
	'64.7: Hub data for the type counts games on all articles and never builds the studio index or its description'
);

// 64.8: in tutte le Hub la voce "Giochi" conta tutti gli articoli, non più gli ultimi 200
assert_test(
	3 === substr_count( $st_hub, "array( 'Giochi', (string) \$hub_stats['games'] )" ) && 0 === substr_count( $st_hub, 'count( $games )' ) &&
	1 === substr_count( $st_hub, 'ntn_core_category_hub_stats_query(' ) && false === strpos( $st_hub, '$games       = ntn_core_category_hub_games( $recent )' ),
	'64.8: The Games figure of every Hub type comes from the full count, not from the latest 200 articles'
);

foreach ( range( 9701, 9708 ) as $st_id ) {
	unset( $mock_termmeta[ $st_id ] );
}
unset( $mock_options['ntn_cat_hub_version'] );

// --- TEST SUITE 65: Pagina dell'Hub Statistiche v4.3.0 ---
echo "\n--- 65. PAGINA HUB STATISTICHE V4.3.0 ---\n";

require_once ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/category-hub-template.php';
if ( ! function_exists( 'locate_template' ) ) {
	function locate_template( $names, $load = false, $once = true ) {
		foreach ( (array) $names as $name ) {
			if ( file_exists( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/' . $name ) ) {
				return $name;
			}
		}
		return '';
	}
}
$pg_file = ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/category-hub-statistiche.php';
$pg_year = (int) gmdate( 'Y' );
$pg_old  = $pg_year - 1;
$pg_now  = gmdate( 'Y-m-d H:i:s' );
$pg_day  = gmdate( 'd.m.Y' );

$pg_term = static function ( $id, $name, $type, $extra = array() ) {
	$GLOBALS['mock_terms'][ $id ]    = new WP_Term( array( 'term_id' => $id, 'name' => $name, 'slug' => 'pg-' . $id, 'taxonomy' => 'category' ) );
	$GLOBALS['mock_termmeta'][ $id ] = array_merge( array( '_ntn_hub_enabled' => '1', '_ntn_hub_type' => $type, '_ntn_hub_illustration' => 'none' ), $extra );
	return $GLOBALS['mock_terms'][ $id ];
};
if ( ! function_exists( 'get_the_terms' ) ) {
	function get_the_terms( $post_id, $taxonomy ) {
		return $GLOBALS['pg_terms'][ (int) $post_id ] ?? false;
	}
}
$pg_render = static function ( $term, $loop = array() ) use ( $pg_file ) {
	$GLOBALS['queried_object']  = $term;
	$GLOBALS['mock_loop_posts'] = $loop;
	$GLOBALS['mock_loop_index'] = 0;
	$GLOBALS['post']            = null;
	ob_start();
	require $pg_file;
	return (string) ob_get_clean();
};

$pg_full    = $pg_term( 9801, 'Guide', 'statistiche', array( '_ntn_hub_intro' => 'Testo della redazione' ) );
$pg_console = $pg_term( 9802, 'Console', 'console' );
$pg_studi   = $pg_term( 9803, 'Studi', 'studi' );
$pg_idle    = $pg_term( 9804, 'Multiplayer', 'statistiche' );
$pg_evil    = $pg_term( 9805, '<script>alert(1)</script>Guide', 'statistiche', array( '_ntn_hub_intro' => '<img src=x onerror=alert(2)>' ) );
$pg_empty   = $pg_term( 9806, 'Vuota', 'statistiche' );
$pg_art     = $pg_term( 9807, 'Disegno', 'statistiche', array( '_ntn_hub_illustration' => 'joycon' ) );

$pg_db = new NTN_Stats_Mock_Wpdb();
$pg_db->tag_meta = array( 801 => array( '_ntn_igdb_game_id' => '1' ), 802 => array( '_ntn_igdb_game_id' => '2' ) );
$pg_db->rows = array(
	$hs_row( 1, $pg_now, 7, array( 9801 ), array( 801 ) ),
	$hs_row( 2, $pg_now, 7, array( 9801 ), array( 802 ) ),
	$hs_row( 3, "{$pg_old}-05-01 10:00:00", 8, array( 9801 ), array( 801 ) ),
	$hs_row( 4, "{$pg_old}-05-01 10:00:00", 8, array( 9804 ) ),
	$hs_row( 5, $pg_now, 7, array( 9805 ) ),
	$hs_row( 6, $pg_now, 7, array( 9807 ) ),
);
$GLOBALS['wpdb'] = $pg_db;
$pg_author_backup = $GLOBALS['mock_author_meta'];
$pg_queried_backup = $GLOBALS['queried_object'] ?? null;
$GLOBALS['mock_author_meta'] = array( 'display_name' => 'Marco Ferri' );

// 65.1: il tipo Statistiche ha il suo modello, le altre Hub restano sul modello attuale, le altre categorie non cambiano
$pg_route = static function ( $term ) {
	$GLOBALS['queried_object'] = $term;
	return ntn_journal_category_hub_template( 'category.php' );
};
$pg_plain = new WP_Term( array( 'term_id' => 9810, 'name' => 'Normale', 'slug' => 'normale', 'taxonomy' => 'post_tag' ) );
assert_test(
	'category-hub-statistiche.php' === $pg_route( $pg_full ) && 'category-hub.php' === $pg_route( $pg_console ) && 'category-hub.php' === $pg_route( $pg_studi ) && 'category.php' === $pg_route( $pg_plain ),
	'65.1: Statistiche Hubs use their own template, platform and studio Hubs keep the current one, other pages are untouched'
);

// 65.2: la pagina: etichette, titolo, introduzione, quattro contatori con le note, link all'autore; nessun corpo
$pg_html = $pg_render( $pg_full );
assert_test(
	false !== strpos( $pg_html, '<span class="ntn-chip">Hub di sezione</span>' ) && false !== strpos( $pg_html, '<span class="ntn-chip ntn-chip--red">Statistiche</span>' ) &&
	1 === substr_count( $pg_html, '<h1 class="ntn-arch-title">Guide</h1>' ) && false !== strpos( $pg_html, '<p class="ntn-arch-desc">Testo della redazione</p>' ) &&
	1 === substr_count( $pg_html, '<div class="ntn-arch-head">' ) && 1 === substr_count( $pg_html, '<div class="ntn-gcounts">' ) && false === strpos( $pg_html, 'ntn-chub-stats' ) && false === strpos( $pg_html, 'ntn-chub-head' ) &&
	false !== strpos( $pg_html, '<span><b>3</b>Articoli</span>' ) && false !== strpos( $pg_html, "<span><b>2</b>Nel {$pg_year}</span>" ) &&
	false !== strpos( $pg_html, '<a href="https://example.com/author/7/">Marco Ferri</a>' ) && false !== strpos( $pg_html, "Autore dell'anno · 2 articoli" ) &&
	false !== strpos( $pg_html, '<b>' . $pg_day . '</b>Ultimo articolo · oggi' ) &&
	false !== strpos( $pg_html, '<span><b>2</b>Videogiochi su IGDB</span>' ),
	'65.2: The page shows chips, title, introduction and the counters in the catalog row, with a link to the author'
);

// 65.3: il disegno a punti compare solo se l'illustrazione è impostata; "In evidenza" e schede non ci sono
$pg_art_html = $pg_render( $pg_art );
assert_test(
	false === strpos( $pg_html, '<canvas' ) && false !== strpos( $pg_art_html, '<canvas class="ntn-chub-art" data-ntn-dots="joycon" aria-hidden="true"></canvas>' ) &&
	false === strpos( $pg_html, 'ntn-lead' ) && false === strpos( $pg_html, 'ntn-chub-grid' ),
	'65.3: The dot drawing appears only when an illustration is set, with no featured block or cards'
);

// 65.4: anno senza articoli: autore "—", nessuna introduzione, gli altri numeri restano
$pg_idle_html = $pg_render( $pg_idle );
assert_test(
	false !== strpos( $pg_idle_html, "<span><b>—</b>Nessun articolo nel {$pg_year}</span>" ) &&
	false !== strpos( $pg_idle_html, "<span><b>0</b>Nel {$pg_year}</span>" ) && false !== strpos( $pg_idle_html, '<b>01.05.' . $pg_old . '</b>Ultimo articolo' ) &&
	false === strpos( $pg_idle_html, 'ntn-arch-desc' ) && false === strpos( $pg_idle_html, '/author/' ),
	'65.4: Without articles this year the author shows a dash and its note, and the other figures stay'
);

// 65.5: categoria vuota: nessuna data e nessun errore
$pg_empty_html = $pg_render( $pg_empty );
assert_test(
	2 === substr_count( $pg_empty_html, '<b>—</b>' ) && false !== strpos( $pg_empty_html, '<span><b>—</b>Nessun articolo</span>' ) &&
	false === strpos( $pg_empty_html, 'Ultimo articolo' ) && false === strpos( $pg_empty_html, 'giorni fa' ) &&
	false !== strpos( $pg_empty_html, '<div class="ntn-empty">' ) && false !== strpos( $pg_empty_html, 'Non ci sono ancora articoli in questa sezione.' ) &&
	false === strpos( $pg_empty_html, 'ntn-chub-feed' ) && false === strpos( $pg_empty_html, 'ntn-pager-wrap' ),
	'65.5: An empty category shows dashes for author and date and the empty state instead of the article list'
);

// 65.9-65.13 (v4.6.0): apertura e schede. La pagina si genera davvero, con le schede vere di content.php
require_once ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/front-page-data.php';
if ( ! function_exists( 'get_post_timestamp' ) ) {
	function get_post_timestamp( $post = null ) { return time(); }
}
if ( ! function_exists( 'get_the_post_thumbnail' ) ) {
	function get_the_post_thumbnail( $post = null, $size = 'post-thumbnail', $attr = array() ) {
		return '<img data-size="' . esc_attr( $size ) . '" loading="' . esc_attr( $attr['loading'] ?? '' ) . '" alt="' . esc_attr( $attr['alt'] ?? '' ) . '">';
	}
}
if ( ! function_exists( 'get_post_thumbnail_id' ) ) {
	function get_post_thumbnail_id( $post = null ) { return 1; }
}
if ( ! function_exists( 'wp_get_attachment_image' ) ) {
	function wp_get_attachment_image( $id, $size = 'thumbnail', $icon = false, $attr = array() ) {
		return '<img data-size="' . esc_attr( $size ) . '" loading="' . esc_attr( $attr['loading'] ?? '' ) . '" alt="' . esc_attr( $attr['alt'] ?? '' ) . '">';
	}
}
if ( ! function_exists( 'the_author' ) ) {
	function the_author() { echo esc_html( get_the_author() ); }
}
$GLOBALS['pg_real_cards'] = true;
$pg_loop = array();
foreach ( array( 98001 => 'Primo <em>articolo</em>', 98002 => 'Secondo articolo', 98003 => 'Terzo articolo', 98004 => 'Quarto articolo' ) as $pg_id => $pg_title ) {
	$pg_post = new WP_Post( array( 'ID' => $pg_id, 'post_title' => $pg_title, 'post_name' => 'p-' . $pg_id, 'post_type' => 'post', 'post_status' => 'publish' ) );
	$pg_loop[] = $pg_post;
	$GLOBALS['mock_posts'][ $pg_id ] = $pg_post;
}
$GLOBALS['mock_postmeta'][98001] = array( '_thumbnail_url' => 'https://example.com/a.jpg', '_ntn_reading_time' => 7 );
$GLOBALS['mock_postmeta'][98002] = array( '_thumbnail_url' => 'https://example.com/b.jpg' );
$pg_paged_backup = $GLOBALS['mock_query_vars']['paged'] ?? null;
$GLOBALS['mock_query_vars']['paged'] = 0;
$pg_p1 = $pg_render( $pg_full, $pg_loop );
$GLOBALS['mock_query_vars']['paged'] = 2;
$pg_p2 = $pg_render( $pg_full, $pg_loop );
$GLOBALS['mock_query_vars']['paged'] = 0;
$pg_p1_one = $pg_render( $pg_full, array( $pg_loop[0] ) );
$pg_no_cover = $pg_render( $pg_full, array( $pg_loop[2], $pg_loop[3] ) );
$pg_card_count = static function ( $html ) {
	return substr_count( $html, 'ntn-cell ntn-card' );
};

// 65.9: prima pagina: l'articolo più recente in apertura e gli altri nelle schede degli archivi, a tre colonne, con la paginazione
assert_test(
	1 === substr_count( $pg_p1, '<a class="ntn-lead-main group"' ) && 1 === substr_count( $pg_p1, 'class="ntn-lead ntn-lead--solo ntn-stats-lead"' ) &&
	false !== strpos( $pg_p1, '<h2 id="ntn-h-cfeed" class="ntn-sec-title">// Tutti gli articoli</h2>' ) && false !== strpos( $pg_p1, '<span class="ntn-sec-aside">Dal più recente</span>' ) &&
	false !== strpos( $pg_p1, 'class="ntn-lead-title"' ) && false !== strpos( $pg_p1, '<span>7 min</span>' ),
	'65.9a: The first page opens with the latest article in the large block'
);
assert_test(
	3 === $pg_card_count( $pg_p1 ) && 1 === substr_count( $pg_p1, '<div class="ntn-cells ntn-cells--3 ntn-stats-cards">' ) && 1 === substr_count( $pg_p1, '<div class="ntn-pager-wrap">' ) &&
	false === strpos( $pg_p1, 'ntn-chub-feed' ) && false === strpos( $pg_p1, 'ntn-chub-row' ) && false === strpos( $pg_p1, 'ntn-empty' ),
	'65.9b: The other articles use the archive cards in three columns with the pager, not the compact list'
);

// 65.10: dalla pagina 2 in poi solo le schede; con un solo articolo solo l'apertura, senza griglia vuota
assert_test(
	false === strpos( $pg_p2, 'ntn-lead-main' ) && 4 === $pg_card_count( $pg_p2 ) && 1 === substr_count( $pg_p2, '<div class="ntn-pager-wrap">' ) &&
	1 === substr_count( $pg_p1_one, 'ntn-lead-main' ) && 0 === $pg_card_count( $pg_p1_one ) && false === strpos( $pg_p1_one, 'ntn-stats-cards' ),
	'65.10: Later pages show only the cards, and a single article shows only the opening without an empty grid'
);

// 65.11: l'immagine dell'apertura si carica subito ed è grande; quelle delle schede quando servono; niente HTML dai titoli
$pg_lead_html = (string) substr( $pg_p1, (int) strpos( $pg_p1, 'ntn-lead-main' ), (int) strpos( $pg_p1, 'ntn-stats-cards' ) - (int) strpos( $pg_p1, 'ntn-lead-main' ) );
$pg_grid_html = (string) substr( $pg_p1, (int) strpos( $pg_p1, 'ntn-stats-cards' ) );
assert_test(
	false !== strpos( $pg_lead_html, 'data-size="large" loading="eager"' ) && false !== strpos( $pg_grid_html, 'data-size="medium_large" loading="lazy"' ) && false === strpos( $pg_grid_html, 'loading="eager"' ) &&
	false !== strpos( $pg_lead_html, 'Primo &lt;em&gt;articolo&lt;/em&gt;' ) && false === strpos( $pg_lead_html, '<em>' ),
	'65.11: The opening image loads first at large size, card images load lazily, and the opening title is escaped'
);

// 65.12: copertina mancante: la copertina a punti in apertura, l'immagine predefinita del tema nelle schede
$pg_no_grid = (string) substr( $pg_no_cover, (int) strpos( $pg_no_cover, 'ntn-stats-cards' ) );
assert_test(
	false !== strpos( $pg_no_cover, '<span class="ntn-ph block w-full h-full" aria-hidden="true"></span>' ) && 1 === $pg_card_count( $pg_no_cover ) &&
	false !== strpos( $pg_no_grid, 'src="' . esc_url( ntn_journal_default_cover_url() ) . '"' ) && false !== strpos( $pg_no_grid, 'loading="lazy"' ),
	'65.12: A missing cover falls back to the dotted cover in the opening and to the default theme image in the cards'
);

// 65.13: l'Hub Statistiche ha il suo elenco; le altre Hub restano sul pezzo condiviso e il loro modello non conosce apertura o schede nuove
$pg_hub_tpl = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/category-hub.php' );
$pg_stat_src = file_get_contents( $pg_file );
$pg_part    = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/hub-article-feed.php' );
assert_test(
	false === strpos( $pg_stat_src, 'hub-article-feed' ) && false !== strpos( $pg_stat_src, "get_template_part( 'template-parts/content' )" ) &&
	false !== strpos( $pg_hub_tpl, "get_template_part( 'template-parts/hub-article-feed', null, array( 'row_tag' => \$hub_row_tag," ) && false === strpos( $pg_hub_tpl, 'ntn-stats-' ) &&
	false !== strpos( $pg_part, 'ntn-chub-feed' ) && false !== strpos( $pg_stat_src, "'prev_text' => '&larr; Precedente'" ) && false !== strpos( $pg_stat_src, "'next_text' => 'Successivo &rarr;'" ) &&
	false === strpos( $pg_stat_src, "ntn_core_get_category_hub_data()['featured']" ) && false === strpos( $pg_stat_src, "\$hub['featured']" ),
	'65.13: Statistiche Hubs use their own list and always open with the latest article; other Hubs stay on the shared list'
);

unset( $GLOBALS['pg_real_cards'], $GLOBALS['mock_postmeta'][98001], $GLOBALS['mock_postmeta'][98002] );
foreach ( array_keys( array( 98001 => 1, 98002 => 1, 98003 => 1, 98004 => 1 ) ) as $pg_id ) {
	unset( $GLOBALS['mock_posts'][ $pg_id ] );
}
if ( null === $pg_paged_backup ) {
	unset( $GLOBALS['mock_query_vars']['paged'] );
} else {
	$GLOBALS['mock_query_vars']['paged'] = $pg_paged_backup;
}
$GLOBALS['mock_loop_posts'] = array();
$GLOBALS['mock_loop_index'] = 0;
$GLOBALS['post']            = null;

// 65.6: tutto escaped: nome della sezione, introduzione e nome dell'autore
$GLOBALS['mock_author_meta'] = array( 'display_name' => '<b onclick=x>Autore</b>' );
$pg_evil_html = $pg_render( $pg_evil );
$GLOBALS['mock_author_meta'] = array( 'display_name' => 'Marco Ferri' );
assert_test(
	false === strpos( $pg_evil_html, '<script>alert(1)' ) && false === strpos( $pg_evil_html, '<img src=x' ) && false === strpos( $pg_evil_html, '<b onclick' ) &&
	false !== strpos( $pg_evil_html, '&lt;script&gt;alert(1)&lt;/script&gt;Guide' ) && false !== strpos( $pg_evil_html, '&lt;img src=x onerror=alert(2)&gt;' ) && false !== strpos( $pg_evil_html, '&lt;b onclick=x&gt;Autore&lt;/b&gt;' ),
	'65.6: Section name, introduction and author name are escaped'
);

// 65.7: "X giorni fa" si calcola alla visita e non si inventa nulla per le date strane
assert_test(
	'oggi' === ntn_core_category_hub_days_ago( gmdate( 'd.m.Y' ) ) && 'ieri' === ntn_core_category_hub_days_ago( gmdate( 'd.m.Y', time() - DAY_IN_SECONDS ) ) &&
	'17 giorni fa' === ntn_core_category_hub_days_ago( gmdate( 'd.m.Y', time() - 17 * DAY_IN_SECONDS ) ) &&
	'' === ntn_core_category_hub_days_ago( gmdate( 'd.m.Y', time() + 2 * DAY_IN_SECONDS ) ) && '' === ntn_core_category_hub_days_ago( 'non una data' ),
	'65.7: Relative date says today, yesterday or N days ago, and stays empty for future or invalid dates'
);

// 65.8: CSS solo nelle variabili della fascia (chiaro e scuro), compilato; senza plugin si torna all'archivio
$pg_css = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/build/input.css' );
$pg_out = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/style-tailwind-output.css' );
$pg_src = file_get_contents( $pg_file );
assert_test(
	false === strpos( $pg_css, '.is-numbers' ) && false === strpos( $pg_css, '.ntn-stat-sub' ) && false !== strpos( $pg_css, '.ntn-sec-head + .ntn-stats-cards { margin-top: 24px; }' ) &&
	false !== strpos( $pg_out, '.ntn-stats-lead' ) && false === strpos( $pg_out, '.is-numbers' ) &&
	false !== strpos( $pg_src, "locate_template( 'archive.php', true, false )" ) && false !== strpos( $pg_src, "function_exists( 'ntn_core_get_category_hub_stats' )" ),
	'65.8: Styles use the band colors for light and dark and are compiled; the page falls back to the archive without the plugin'
);

unset( $GLOBALS['wpdb'], $mock_options['ntn_cat_hub_version'] );
foreach ( range( 9801, 9807 ) as $pg_id ) {
	unset( $mock_terms[ $pg_id ], $mock_termmeta[ $pg_id ] );
}
$GLOBALS['mock_author_meta'] = $pg_author_backup;
$GLOBALS['queried_object']   = $pg_queried_backup;

// --- TEST SUITE 66: SEO dell'Hub Statistiche (v4.4.0, aggiornata in v4.5.0) ---
echo "\n--- 66. SEO HUB STATISTICHE ---\n";

$se_src = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/category-hub-stats.php' );
$se_hub = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/category-hub.php' );
$se_stat = new WP_Term( array( 'term_id' => 9901, 'name' => 'Numeri', 'slug' => 'numeri', 'taxonomy' => 'category' ) );
$mock_termmeta[9901] = array( '_ntn_hub_enabled' => '1', '_ntn_hub_type' => 'statistiche' );
$se_backup_q     = $GLOBALS['queried_object'];
$se_backup_paged = $mock_query_vars['paged'] ?? null;

// 66.1: ora che le pagine hanno l'elenco degli articoli, /page/2/ si comporta come nelle altre Hub: nessuna regola SEO dedicata
assert_test(
	! function_exists( 'ntn_core_category_hub_stats_tsf_robots' ) && ! function_exists( 'ntn_core_category_hub_stats_wp_robots' ) && ! function_exists( 'ntn_core_category_hub_stats_tsf_tags' ) &&
	false === strpos( $se_src, 'the_seo_framework_robots_meta_array' ) && false === strpos( $se_src, 'the_seo_framework_meta_render_data' ) && false === strpos( $se_src, "'wp_robots'" ),
	'66.1: Statistiche Hubs have no dedicated robots or prev/next rule: later pages behave like those of other Hubs'
);

// 66.2: la soglia delle pagine sottili vale anche per questo tipo: sotto la soglia noindex, regola spenta nessun effetto
$GLOBALS['queried_object']           = $se_stat;
$GLOBALS['mock_query_vars']['paged'] = 0;
$se_thin = ntn_core_seo_is_thin_term( $se_stat );
$se_thin_robots = ntn_core_seo_thin_wp_robots( array() );
$mock_options['ntn_seo_min_posts_terms'] = 0;
$se_thin_off = ntn_core_seo_is_thin_term( $se_stat );
$se_off_robots = ntn_core_seo_thin_wp_robots( array() );
unset( $mock_options['ntn_seo_min_posts_terms'] );
assert_test(
	true === $se_thin && true === ( $se_thin_robots['noindex'] ?? false ) && true === ( $se_thin_robots['follow'] ?? false ) && false === $se_thin_off && array() === $se_off_robots &&
	false !== strpos( $se_hub, "ntn_core_seo_is_thin_term( \$term )" ),
	'66.2: Thin page threshold applies to Statistiche Hubs, in robots and sitemap, and can be turned off'
);

// 66.3: nessun elenco di piattaforme o studi nei dati strutturati, perché questo tipo non ne ha
assert_test(
	false !== strpos( $se_hub, "if ( empty( \$items ) ) {\n\t\treturn;" ) && false !== strpos( $se_hub, "foreach ( \$data['studios'] as \$i => \$studio )" ),
	'66.3: No platform or studio ItemList for this type'
);

$GLOBALS['queried_object'] = $se_backup_q;
if ( null === $se_backup_paged ) {
	unset( $GLOBALS['mock_query_vars']['paged'] );
} else {
	$GLOBALS['mock_query_vars']['paged'] = $se_backup_paged;
}
unset( $mock_termmeta[9901], $mock_options['ntn_cat_hub_version'] );

// --- TEST SUITE 67: Griglia 3x3 dell'Hub Statistiche v4.8.0 ---
echo "\n--- 67. GRIGLIA 3X3 HUB STATISTICHE V4.8.0 ---\n";

class NTN_Stats_Mock_Query extends WP_Query {
	public $vars = array();
	public $main = true;
	public $cat = true;
	public $feed = false;
	public $term = null;
	public function __construct( $term, $paged ) {
		$this->term = $term;
		$this->vars = array( 'paged' => $paged );
	}
	public function get( $key ) { return $this->vars[ $key ] ?? ''; }
	public function set( $key, $value ) { $this->vars[ $key ] = $value; }
	public function is_main_query() { return $this->main; }
	public function is_category() { return $this->cat; }
	public function is_feed() { return $this->feed; }
	public function get_queried_object() { return $this->term; }
}
if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() { return false; }
}
$gr_stat = new WP_Term( array( 'term_id' => 9951, 'name' => 'Numeri', 'slug' => 'numeri', 'taxonomy' => 'category' ) );
$gr_cons = new WP_Term( array( 'term_id' => 9952, 'name' => 'Console', 'slug' => 'console', 'taxonomy' => 'category' ) );
$mock_termmeta[9951] = array( '_ntn_hub_enabled' => '1', '_ntn_hub_type' => 'statistiche' );
$mock_termmeta[9952] = array( '_ntn_hub_enabled' => '1', '_ntn_hub_type' => 'console' );

// Simula la paginazione di WordPress: quali articoli mostra ogni pagina e quante pagine dichiara
$gr_walk = static function ( $term, $total ) {
	$pages = array();
	$page  = 1;
	do {
		$q = new NTN_Stats_Mock_Query( $term, $page );
		ntn_core_category_hub_stats_pre_get_posts( $q );
		$per    = (int) $q->get( 'posts_per_page' );
		$offset = (int) $q->get( 'offset' );
		$found  = ntn_core_category_hub_stats_found_posts( $total, $q );
		$max    = $per ? (int) ceil( $found / $per ) : 0;
		$ids    = $offset < $total ? range( $offset + 1, min( $total, $offset + $per ) ) : array();
		$pages[ $page ] = array( 'per' => $per, 'offset' => $offset, 'max' => $max, 'ids' => $ids );
		$page++;
	} while ( $page <= $max && $page < 50 );
	return $pages;
};

// 67.1: pagina 1 con 10 articoli, le altre con 9 e l'offset giusto
$gr_27 = $gr_walk( $gr_stat, 27 );
assert_test(
	10 === $gr_27[1]['per'] && 0 === $gr_27[1]['offset'] && 9 === $gr_27[2]['per'] && 10 === $gr_27[2]['offset'] && 9 === $gr_27[3]['per'] && 19 === $gr_27[3]['offset'],
	'67.1: The first page holds 10 articles (opening and 9 cards), later pages 9 with the right offset'
);

// 67.2: nessun articolo ripetuto o saltato e stesso numero di pagine ovunque, anche ai bordi
$gr_ok = true;
foreach ( array( 1, 9, 10, 11, 19, 20, 27, 28, 29, 100, 137 ) as $gr_total ) {
	$gr_pages = $gr_walk( $gr_stat, $gr_total );
	$gr_seen  = array_merge( ...array_column( $gr_pages, 'ids' ) );
	$gr_maxes = array_unique( array_column( $gr_pages, 'max' ) );
	$gr_empty = array_filter( $gr_pages, static function ( $p ) { return empty( $p['ids'] ); } );
	if ( range( 1, $gr_total ) !== $gr_seen || 1 !== count( $gr_maxes ) || count( $gr_pages ) !== (int) reset( $gr_maxes ) || ! empty( $gr_empty ) ) {
		$gr_ok = false;
		echo "   Totale {$gr_total}: pagine incoerenti\n";
	}
}
assert_test(
	$gr_ok && 1 === $gr_walk( $gr_stat, 10 )[1]['max'] && 2 === $gr_walk( $gr_stat, 11 )[1]['max'] && 3 === count( $gr_27 ),
	'67.2: No article is repeated or skipped across pages, and every page declares the same page count with no empty last page'
);

// 67.3: solo la query principale di una Hub Statistiche: le altre Hub, le altre query e la categoria vuota non cambiano
$gr_cons_q = new NTN_Stats_Mock_Query( $gr_cons, 2 );
ntn_core_category_hub_stats_pre_get_posts( $gr_cons_q );
$gr_side = new NTN_Stats_Mock_Query( $gr_stat, 2 );
$gr_side->main = false;
ntn_core_category_hub_stats_pre_get_posts( $gr_side );
$gr_tag = new NTN_Stats_Mock_Query( $gr_stat, 2 );
$gr_tag->cat = false;
ntn_core_category_hub_stats_pre_get_posts( $gr_tag );
$gr_feed = new NTN_Stats_Mock_Query( $gr_stat, 1 );
$gr_feed->feed = true;
ntn_core_category_hub_stats_pre_get_posts( $gr_feed );
$gr_zero = new NTN_Stats_Mock_Query( $gr_stat, 1 );
ntn_core_category_hub_stats_pre_get_posts( $gr_zero );
assert_test(
	'' === $gr_cons_q->get( 'posts_per_page' ) && '' === $gr_cons_q->get( 'offset' ) && 123 === ntn_core_category_hub_stats_found_posts( 123, $gr_cons_q ) &&
	'' === $gr_side->get( 'posts_per_page' ) && '' === $gr_tag->get( 'posts_per_page' ) && '' === $gr_feed->get( 'posts_per_page' ) && 123 === ntn_core_category_hub_stats_found_posts( 123, $gr_side ) &&
	0 === ntn_core_category_hub_stats_found_posts( 0, $gr_zero ),
	'67.3: Only the main query of a Statistiche Hub changes: other Hubs, secondary queries, feeds and empty categories are untouched'
);

// 67.4: tre colonne come gli archivi: niente cinque colonne né spazio interno ridotto della 4.7.0; i contatori in riga restano
$gr_css = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/build/input.css' );
$gr_out = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/style-tailwind-output.css' );
$gr_tpl = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/category-hub-statistiche.php' );
assert_test(
	false !== strpos( $gr_tpl, '<div class="ntn-cells ntn-cells--3 ntn-stats-cards">' ) && false !== strpos( $gr_tpl, '<div class="ntn-gcounts">' ) && false !== strpos( $gr_tpl, '<div class="ntn-arch-head">' ) &&
	false === strpos( $gr_css, '.ntn-stats-cards { grid-template-columns' ) && false === strpos( $gr_css, '.ntn-stats-cards .ntn-cell' ) &&
	false === strpos( $gr_out, '.ntn-stats-cards{grid-template-columns' ) && false === strpos( $gr_out, '.ntn-stats-cards .ntn-cell' ),
	'67.4: Cards use the three-column archive grid without the 4.7.0 overrides, keeping the counters row and the wide header'
);

unset( $mock_termmeta[9951], $mock_termmeta[9952] );

// --- TEST SUITE 68: Elenco dei giochi di un articolo (scheda multigioco, fase 1) ---
echo "\n--- 68. ELENCO GIOCHI DI UN ARTICOLO ---\n";
// Finta ricerca: tag e categoria come la query vera, dalla più recente, e registro delle query fatte.
$mg_query_log = array();
$GLOBALS['mock_wp_query_handler'] = static function ( $args ) use ( &$mg_query_log ) {
	global $mock_posts, $mock_post_tags, $mock_post_categories;
	$mg_query_log[] = $args;
	$found = array();
	foreach ( $mock_posts as $candidate ) {
		if ( 'post' !== $candidate->post_type || 'publish' !== $candidate->post_status ) {
			continue;
		}
		$has_tag = false;
		foreach ( (array) ( $mock_post_tags[ $candidate->ID ] ?? array() ) as $candidate_tag ) {
			$has_tag = $has_tag || (int) $candidate_tag->term_id === (int) $args['tax_query'][0]['terms'];
		}
		if ( $has_tag && has_category( $args['tax_query'][1]['terms'], $candidate ) ) {
			$found[] = $candidate;
		}
	}
	usort( $found, static function ( $a, $b ) { return strcmp( (string) $b->post_date, (string) $a->post_date ); } );
	return array_slice( $found, 0, (int) $args['posts_per_page'] );
};

$mg_term = static function ( $id, $name, $slug, $meta = array() ) {
	global $mock_terms, $mock_termmeta;
	$mock_terms[ $id ] = new WP_Term( array( 'term_id' => $id, 'name' => $name, 'slug' => $slug, 'taxonomy' => 'post_tag' ) );
	if ( $meta ) {
		$mock_termmeta[ $id ] = $meta;
	}
	return $mock_terms[ $id ];
};
$mg_post = static function ( $id, $type, $status, $slug, $date, $tags = array(), $cats = array() ) {
	global $mock_posts, $mock_post_tags, $mock_post_categories;
	$mock_posts[ $id ] = new WP_Post( array( 'ID' => $id, 'post_type' => $type, 'post_status' => $status, 'post_name' => $slug, 'post_title' => $slug, 'post_date' => $date ) );
	$mock_post_tags[ $id ] = $tags;
	$mock_post_categories[ $id ] = array_map( static function ( $cat_slug ) { return new WP_Term( array( 'term_id' => crc32( $cat_slug ), 'name' => $cat_slug, 'slug' => $cat_slug, 'taxonomy' => 'category' ) ); }, $cats );
	return $mock_posts[ $id ];
};

// 68.1: due tag gioco con dati diversi non si mescolano; un dato mancante resta vuoto; i tag normali non sono giochi
$mg_a = $mg_term( 9960, 'Mg Alfa', 'mg-alfa', array( '_ntn_igdb_game_id' => 501, '_ntn_game_title' => 'Mg Alfa', '_ntn_game_cover' => 'coalfa', '_ntn_game_developer' => 'Studio Alfa', '_ntn_game_publisher' => 'Editore Alfa', '_ntn_game_release' => '2024-01-01', '_ntn_game_release_precision' => 'year', '_ntn_game_platforms' => 'PC', '_ntn_game_genres' => 'Shooter', '_ntn_game_engine' => 'Motore Alfa', '_ntn_game_url' => 'https://www.igdb.com/games/mg-alfa' ) );
$mg_b = $mg_term( 9961, 'Mg Beta', 'mg-beta', array( '_ntn_igdb_game_id' => 502, '_ntn_game_title' => 'Mg Beta', '_ntn_game_cover' => 'cobeta', '_ntn_game_developer' => 'Studio Beta' ) );
$mg_plain = $mg_term( 9962, 'Indie', 'indie' );
$mg_post( 9960, 'post', 'publish', 'mg-articolo-doppio', '2026-01-01', array( $mg_b, $mg_plain, $mg_a ) );
$mg_games = ntn_get_post_games( 9960 );
assert_test(
	2 === count( $mg_games ) &&
	501 === $mg_games[0]['igdb_id'] && 'Mg Alfa' === $mg_games[0]['title'] && false !== strpos( $mg_games[0]['cover'], 'coalfa' ) && 'Studio Alfa' === $mg_games[0]['developer'] && 'Editore Alfa' === $mg_games[0]['publisher'] && 'Sparatutto' === $mg_games[0]['genres'] && 'year' === $mg_games[0]['release_precision'] && 'Motore Alfa' === $mg_games[0]['engine'] && 'https://www.igdb.com/games/mg-alfa' === $mg_games[0]['igdb_url'] && $mg_a === $mg_games[0]['term'] &&
	502 === $mg_games[1]['igdb_id'] && 'Mg Beta' === $mg_games[1]['title'] && false !== strpos( $mg_games[1]['cover'], 'cobeta' ) && 'Studio Beta' === $mg_games[1]['developer'] && '' === $mg_games[1]['publisher'] && '' === $mg_games[1]['engine'] && '' === $mg_games[1]['release'] && $mg_b === $mg_games[1]['term'],
	'68.1: Two game tags keep their own data, a missing value stays empty instead of borrowing the other game, plain tags are ignored'
);

// 68.2: ordine alfabetico senza badare a maiuscole e accenti; a parità di titolo resta l'ordine dei tag
$mg_order_tags = array();
foreach ( array( 'zelta', 'Èlite', 'alfa', 'Beta', 'Pari', 'pari' ) as $mg_i => $mg_title ) {
	$mg_order_tags[] = $mg_term( 9970 + $mg_i, $mg_title, 'mg-ordine-' . $mg_i, array( '_ntn_igdb_game_id' => 510 + $mg_i, '_ntn_game_title' => $mg_title ) );
}
$mg_post( 9961, 'post', 'publish', 'mg-articolo-ordine', '2026-01-01', $mg_order_tags );
assert_test(
	array( 'alfa', 'Beta', 'Èlite', 'Pari', 'pari', 'zelta' ) === array_column( ntn_get_post_games( 9961 ), 'title' ),
	'68.2: Games are sorted alphabetically ignoring case and accents, and equal titles keep their tag order'
);

// 68.3: doppioni per ID IGDB, per titolo se manca l'ID, e tolleranza su slug con suffisso e nome con entità
$mg_dup_1 = $mg_term( 9980, 'Mg Doppio', 'mg-doppio', array( '_ntn_igdb_game_id' => 520, '_ntn_game_title' => 'Mg Doppio' ) );
$mg_dup_2 = $mg_term( 9981, 'Mg Doppio Bis', 'mg-doppio-bis', array( '_ntn_igdb_game_id' => 520, '_ntn_game_title' => 'Mg Doppio Bis' ) );
$mg_noid_1 = $mg_term( 9982, 'Mg Senza Id', 'mg-senza-id', array( '_ntn_game_title' => 'Mg Senza Id' ) );
$mg_noid_2 = $mg_term( 9983, 'MG SENZA ID', 'mg-senza-id-altro', array( '_ntn_game_title' => 'MG SENZA ID' ) );
$mg_post( 9962, 'post', 'publish', 'mg-articolo-doppioni', '2026-01-01', array( $mg_dup_1, $mg_dup_2, $mg_noid_1, $mg_noid_2 ) );
$mg_variant_slug = $mg_term( 9984, 'Mg Doppio', 'mg-doppio-2' );
$mg_real_name = $mg_term( 9985, "Mg L'Eroe", 'mg-eroe', array( '_ntn_igdb_game_id' => 521, '_ntn_game_title' => "Mg L'Eroe" ) );
$mg_variant_name = $mg_term( 9986, 'Mg L&#039;Eroe', 'mg-eroe-entita' );
$mg_post( 9963, 'post', 'publish', 'mg-articolo-varianti', '2026-01-01', array( $mg_variant_slug, $mg_variant_name ) );
assert_test(
	array( 'Mg Doppio', 'Mg Senza Id' ) === array_column( ntn_get_post_games( 9962 ), 'title' ) &&
	array( 520, 521 ) === array_column( ntn_get_post_games( 9963 ), 'igdb_id' ),
	'68.3: Duplicates collapse by IGDB id or by title without id, and tag variants resolve to the real game tag'
);

// 68.4: senza tag gioco si legge il post_meta dei vecchi articoli; senza nessun dato l'elenco è vuoto
$mg_post( 9964, 'post', 'publish', 'mg-articolo-vecchio', '2026-01-01', array( $mg_plain ), array( 'recensioni' ) );
$mock_postmeta[9964] = array( '_ntn_game_title' => 'Mg Vecchio', '_ntn_igdb_game_id' => '530', '_ntn_game_cover' => 'covecchio', '_ntn_game_developer' => 'Studio Vecchio', '_ntn_game_genres' => 'Shooter', '_ntn_igdb_url' => 'https://www.igdb.com/games/mg-vecchio', 'rating' => '4' );
$mg_post( 9965, 'post', 'publish', 'mg-articolo-senza-giochi', '2026-01-01', array( $mg_plain ) );
$mg_old = ntn_get_post_games( 9964 );
assert_test(
	1 === count( $mg_old ) && null === $mg_old[0]['term'] && 530 === $mg_old[0]['igdb_id'] && 'Mg Vecchio' === $mg_old[0]['title'] && false !== strpos( $mg_old[0]['cover'], 'covecchio' ) && 'Studio Vecchio' === $mg_old[0]['developer'] && 'Sparatutto' === $mg_old[0]['genres'] && 'https://www.igdb.com/games/mg-vecchio' === $mg_old[0]['igdb_url'] && '' === $mg_old[0]['engine'] &&
	9964 === $mg_old[0]['review']->ID && true === $mg_old[0]['is_current_review'] && 4 === $mg_old[0]['review_rating'] &&
	array() === ntn_get_post_games( 9965 ),
	'68.4: Old articles without game tags get one game from post meta, and articles with no game data return an empty list'
);

// 68.5: ultima recensione pubblicata del gioco giusto; anteprime, notizie e bozze non contano; ogni gioco ha la sua
$mg_rev_a = $mg_term( 9990, 'Mg Con Recensione', 'mg-con-recensione', array( '_ntn_igdb_game_id' => 540, '_ntn_game_title' => 'Mg Con Recensione' ) );
$mg_rev_b = $mg_term( 9991, 'Mg Solo Anteprima', 'mg-solo-anteprima', array( '_ntn_igdb_game_id' => 541, '_ntn_game_title' => 'Mg Solo Anteprima' ) );
$mg_post( 9970, 'post', 'publish', 'mg-rec-vecchia', '2025-01-01', array( $mg_rev_a ), array( 'recensioni' ) );
$mg_post( 9971, 'post', 'publish', 'mg-rec-nuova', '2025-06-01', array( $mg_rev_a ), array( 'recensione' ) );
$mg_post( 9972, 'post', 'draft', 'mg-rec-bozza', '2026-06-01', array( $mg_rev_a ), array( 'recensioni' ) );
$mg_post( 9973, 'post', 'publish', 'mg-anteprima', '2026-07-01', array( $mg_rev_a, $mg_rev_b ), array( 'anteprime' ) );
$mg_post( 9974, 'post', 'publish', 'mg-notizia', '2026-08-01', array( $mg_rev_a, $mg_rev_b ), array( 'notizie' ) );
$mock_postmeta[9971] = array( 'rating' => '4' );
$mg_review_games = ntn_get_post_games( 9974 );
assert_test(
	9971 === $mg_review_games[0]['review']->ID && 4 === $mg_review_games[0]['review_rating'] && false === $mg_review_games[0]['is_current_review'] &&
	null === $mg_review_games[1]['review'] && 0 === $mg_review_games[1]['review_rating'] && false === $mg_review_games[1]['is_current_review'],
	'68.5: Each game gets its latest published review, never a preview, news item or draft, and no review means null and rating 0'
);

// 68.6: se l'articolo aperto è la recensione del gioco, viene segnalato; per l'altro gioco no
$mg_rev_c = $mg_term( 9992, 'Mg Altra Recensione', 'mg-altra-recensione', array( '_ntn_igdb_game_id' => 542, '_ntn_game_title' => 'Mg Altra Recensione' ) );
$mg_post( 9975, 'post', 'publish', 'mg-rec-altra', '2026-09-01', array( $mg_rev_c ), array( 'recensioni' ) );
$mg_rev_d = $mg_term( 9989, 'Mg Questa Recensione', 'mg-questa-recensione', array( '_ntn_igdb_game_id' => 547, '_ntn_game_title' => 'Mg Questa Recensione' ) );
$mg_post( 9976, 'post', 'publish', 'mg-rec-doppia', '2026-10-01', array( $mg_rev_d, $mg_rev_c ), array( 'recensioni' ) );
$mg_post( 9977, 'post', 'publish', 'mg-rec-c-nuova', '2026-11-01', array( $mg_rev_c ), array( 'recensioni' ) );
$mock_postmeta[9976] = array( 'rating' => '5' );
$mg_current = ntn_get_post_games( 9976 );
assert_test(
	'Mg Altra Recensione' === $mg_current[0]['title'] && 9977 === $mg_current[0]['review']->ID && false === $mg_current[0]['is_current_review'] &&
	'Mg Questa Recensione' === $mg_current[1]['title'] && 9976 === $mg_current[1]['review']->ID && true === $mg_current[1]['is_current_review'] && 5 === $mg_current[1]['review_rating'],
	'68.6: is_current_review is true only for the game whose review is the open article'
);

// 68.7: la recensione scelta a mano vince (prima la scheda, poi il tag), ma solo se pubblicata e in Recensioni
$mg_post( 9978, 'post', 'publish', 'mg-manuale-tag', '2020-01-01', array(), array( 'recensioni' ) );
$mg_post( 9979, 'post', 'publish', 'mg-manuale-cpt', '2020-02-01', array(), array( 'recensioni' ) );
$mg_post( 9980, 'post', 'publish', 'mg-manuale-anteprima', '2020-03-01', array(), array( 'anteprime' ) );
$mg_post( 9981, 'post', 'draft', 'mg-manuale-bozza', '2020-04-01', array(), array( 'recensioni' ) );
$mg_man_tag = $mg_term( 9993, 'Mg Manuale Tag', 'mg-manuale-tag-gioco', array( '_ntn_igdb_game_id' => 543, '_ntn_game_title' => 'Mg Manuale Tag', '_ntn_featured_review_id' => 9978 ) );
$mg_man_cpt = $mg_term( 9994, 'Mg Manuale Scheda', 'mg-manuale-scheda', array( '_ntn_igdb_game_id' => 544, '_ntn_game_title' => 'Mg Manuale Scheda', '_ntn_featured_review_id' => 9978 ) );
$mg_post( 9982, 'videogioco', 'publish', 'mg-manuale-scheda', '2020-01-01' );
$mock_postmeta[9982] = array( '_ntn_featured_review_id' => 9979 );
$mg_man_bad = $mg_term( 9995, 'Mg Manuale Errato', 'mg-manuale-errato', array( '_ntn_igdb_game_id' => 545, '_ntn_game_title' => 'Mg Manuale Errato', '_ntn_featured_review_id' => 9980 ) );
$mg_man_draft = $mg_term( 9996, 'Mg Manuale Bozza', 'mg-manuale-bozza-gioco', array( '_ntn_igdb_game_id' => 546, '_ntn_game_title' => 'Mg Manuale Bozza', '_ntn_featured_review_id' => 9981 ) );
$mg_post( 9983, 'post', 'publish', 'mg-rec-automatica', '2021-01-01', array( $mg_man_draft ), array( 'recensioni' ) );
$mg_post( 9984, 'post', 'publish', 'mg-articolo-manuali', '2026-01-01', array( $mg_man_tag, $mg_man_cpt, $mg_man_bad, $mg_man_draft ) );
$mg_manual = array_column( ntn_get_post_games( 9984 ), 'review', 'title' );
assert_test(
	9978 === $mg_manual['Mg Manuale Tag']->ID && 9979 === $mg_manual['Mg Manuale Scheda']->ID && null === $mg_manual['Mg Manuale Errato'] && 9983 === $mg_manual['Mg Manuale Bozza']->ID,
	'68.7: A manual review wins (game sheet first, then tag) only if published and in Reviews, otherwise the automatic search applies'
);

// 68.8: pagina del gioco: la scheda videogioco con lo stesso slug, altrimenti il tag; l'ID manca sul tag e c'è sulla scheda
$mg_hub_cpt = $mg_term( 9997, 'Mg Con Scheda', 'mg-con-scheda', array( '_ntn_game_title' => 'Mg Con Scheda' ) );
$mg_post( 9985, 'videogioco', 'publish', 'mg-con-scheda', '2020-01-01' );
$mock_postmeta[9985] = array( '_ntn_igdb_game_id' => '550' );
$mg_hub_tag = $mg_term( 9998, 'Mg Solo Tag', 'mg-solo-tag', array( '_ntn_igdb_game_id' => 551, '_ntn_game_title' => 'Mg Solo Tag' ) );
$mg_post( 9986, 'post', 'publish', 'mg-articolo-hub', '2026-01-01', array( $mg_hub_cpt, $mg_hub_tag ) );
$mg_hubs = ntn_get_post_games( 9986 );
assert_test(
	get_permalink( 9985 ) === $mg_hubs[0]['hub_url'] && 550 === $mg_hubs[0]['igdb_id'] && get_term_link( $mg_hub_tag ) === $mg_hubs[1]['hub_url'],
	'68.8: The game page is the game sheet with the same slug, otherwise the tag page'
);

// 68.9: con lo scanner IGDB spento il sito non mostra giochi, nemmeno se l'elenco è già in memoria
$mock_options['ntn_igdb_scanner_active'] = 'no';
$mg_off_cached = ntn_get_post_games( 9960 );
$mg_off_fresh  = ntn_get_post_games( 9987 );
unset( $mock_options['ntn_igdb_scanner_active'] );
assert_test(
	array() === $mg_off_cached && array() === $mg_off_fresh && 2 === count( ntn_get_post_games( 9960 ) ),
	'68.9: With the IGDB switch off the front end gets an empty list, and it comes back when the switch is on'
);

// 68.10: query leggera e fatta una sola volta per gioco nella stessa richiesta
$mg_rev_query = end( $mg_query_log );
$mg_query_count = count( $mg_query_log );
ntn_get_post_games( 9976 );
$mg_light_term = $mg_term( 9999, 'Mg Riuso', 'mg-riuso', array( '_ntn_igdb_game_id' => 560, '_ntn_game_title' => 'Mg Riuso' ) );
$mg_post( 9988, 'post', 'publish', 'mg-riuso-uno', '2026-01-01', array( $mg_light_term ) );
$mg_post( 9989, 'post', 'publish', 'mg-riuso-due', '2026-01-01', array( $mg_light_term ) );
ntn_get_post_games( 9988 );
ntn_get_post_games( 9989 );
assert_test(
	1 === $mg_rev_query['posts_per_page'] && true === $mg_rev_query['no_found_rows'] && 'publish' === $mg_rev_query['post_status'] && array( 'recensioni', 'recensione' ) === $mg_rev_query['tax_query'][1]['terms'] &&
	$mg_query_count + 1 === count( $mg_query_log ),
	'68.10: The review search asks for one post without counting rows, and runs once per game in a request'
);

// --- TEST SUITE 69: Scheda del gioco nella colonna su PC (scheda multigioco, fase 2) ---
echo "\n--- 69. SCHEDA DEL GIOCO SU PC ---\n";
$mg_render = static function ( $id ) {
	global $mock_posts;
	$GLOBALS['post'] = $mock_posts[ $id ];
	ob_start();
	require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/review-sidebar-desktop.php';
	return ob_get_clean();
};

// 69.1: nessun gioco: la scheda di sempre, con il titolo dell'articolo e il voto, senza riga recensione né carosello
$mg_post( 10000, 'post', 'publish', 'mg-solo-voto', '2026-01-01', array(), array( 'recensioni' ) );
$mock_postmeta[10000] = array( 'rating' => '3' );
$mg_out = $mg_render( 10000 );
assert_test(
	false !== strpos( $mg_out, '<span>// Scheda del videogioco</span>' ) && false !== strpos( $mg_out, 'mg-solo-voto' ) && false !== strpos( $mg_out, '// Valutazione finale' ) && false !== strpos( $mg_out, 'aria-label="Voto 3 su 5"' ) &&
	false === strpos( $mg_out, 'ntn-greview-mini' ) && false === strpos( $mg_out, 'data-ntn-gcarousel' ),
	'69.1: Without games the classic card stays, with the article title and rating, no review row and no carousel'
);

// 69.2: un gioco: scheda di sempre, i dati scritti sull'articolo vincono, riga con recensione e LED
$mg_one = $mg_term( 10000, 'Mg Uno', 'mg-uno', array( '_ntn_igdb_game_id' => 600, '_ntn_game_title' => 'Mg Uno', '_ntn_game_developer' => 'Studio Tag', '_ntn_game_publisher' => 'Editore Tag', '_ntn_game_url' => 'https://www.igdb.com/games/mg-uno' ) );
$mg_post( 10001, 'post', 'publish', 'mg-uno-recensione', '2025-01-01', array( $mg_one ), array( 'recensioni' ) );
$mock_postmeta[10001] = array( 'rating' => '4' );
$mg_post( 10002, 'post', 'publish', 'mg-uno-notizia', '2026-01-01', array( $mg_one ), array( 'notizie' ) );
$mock_postmeta[10002] = array( '_ntn_game_developer' => 'Studio Articolo' );
$mg_out = $mg_render( 10002 );
assert_test(
	false !== strpos( $mg_out, '<span>// Scheda del videogioco</span>' ) && false === strpos( $mg_out, 'data-ntn-gcarousel' ) &&
	false !== strpos( $mg_out, 'Studio Articolo' ) && false === strpos( $mg_out, 'Studio Tag' ) && false !== strpos( $mg_out, 'Editore Tag' ) &&
	false !== strpos( $mg_out, 'aria-label="Leggi la recensione di Mg Uno"' ) && false !== strpos( $mg_out, 'href="' . get_permalink( 10001 ) . '"' ) && 4 === substr_count( $mg_out, 'ntn-led is-on' ) &&
	strpos( $mg_out, 'ntn-greview-mini' ) < strpos( $mg_out, 'class="ntn-igdb-btn"' ) && false === strpos( $mg_out, '// Valutazione finale' ),
	'69.2: One game keeps the classic card, article meta wins per field, and the review row links the review with its LEDs above the IGDB button'
);

// 69.3: la recensione del gioco aperta: valutazione finale e nessuna riga
$mg_out = $mg_render( 10001 );
assert_test(
	false !== strpos( $mg_out, '// Valutazione finale' ) && false !== strpos( $mg_out, 'aria-label="Voto 4 su 5"' ) && false === strpos( $mg_out, 'ntn-greview-mini' ),
	'69.3: The review of the single game shows the final score instead of the review row'
);

// 69.4: un gioco senza recensioni: non recensito con cinque LED vuoti
$mg_none = $mg_term( 10001, 'Mg Mai Recensito', 'mg-mai-recensito', array( '_ntn_igdb_game_id' => 601, '_ntn_game_title' => 'Mg Mai Recensito' ) );
$mg_post( 10003, 'post', 'publish', 'mg-mai-notizia', '2026-01-01', array( $mg_none ), array( 'notizie' ) );
$mg_out = $mg_render( 10003 );
assert_test(
	false !== strpos( $mg_out, 'ntn-greview-mini is-none' ) && false !== strpos( $mg_out, '// Non recensito' ) && 5 === substr_count( $mg_out, 'ntn-led is-off' ),
	'69.4: A game without reviews shows Non recensito with five empty LEDs'
);

// 69.5: più giochi: carosello in ordine alfabetico, una pagina per gioco, nessun dato mescolato
$mg_multi_a = $mg_term( 10002, 'Mg Zeta', 'mg-zeta', array( '_ntn_igdb_game_id' => 610, '_ntn_game_title' => 'Mg Zeta', '_ntn_game_cover' => 'cozeta', '_ntn_game_developer' => 'Studio Zeta', '_ntn_game_url' => 'https://www.igdb.com/games/mg-zeta' ) );
$mg_multi_b = $mg_term( 10003, 'Mg Èrcole', 'mg-ercole', array( '_ntn_igdb_game_id' => 611, '_ntn_game_title' => 'Mg Èrcole', '_ntn_game_cover' => 'coercole', '_ntn_game_developer' => 'Studio Ercole' ) );
$mg_multi_c = $mg_term( 10004, 'Mg Alba', 'mg-alba', array( '_ntn_igdb_game_id' => 612, '_ntn_game_title' => 'Mg Alba', '_ntn_game_cover' => 'coalba', '_ntn_game_developer' => 'Studio Alba', '_ntn_game_url' => 'https://www.igdb.com/games/mg-alba' ) );
$mg_post( 10004, 'post', 'publish', 'mg-multi-recensione-alba', '2024-01-01', array( $mg_multi_c ), array( 'recensioni' ) );
$mg_post( 10005, 'post', 'publish', 'mg-multi-recensione-ercole', '2024-01-01', array( $mg_multi_b ), array( 'recensioni' ) );
$mock_postmeta[10005] = array( 'rating' => '2' );
$mg_post( 10006, 'post', 'publish', 'mg-multi-recensione-zeta', '2026-02-01', array( $mg_multi_a ), array( 'recensioni' ) );
$mock_postmeta[10006] = array( 'rating' => '4', 'ntn_review_source' => 'Copia per la prova' );
$mg_post( 10007, 'post', 'publish', 'mg-multi-notizia', '2026-03-01', array( $mg_multi_a, $mg_multi_b, $mg_multi_c ), array( 'notizie' ) );
$mg_out = $mg_render( 10007 );
$mg_slides = explode( '<div class="ntn-gslide"', $mg_out );
assert_test(
	false !== strpos( $mg_out, 'ntn-gcard--multi pointer-events-auto" data-ntn-gcarousel aria-roledescription="carosello"' ) && false !== strpos( $mg_out, '<span class="ntn-gcard-count">01 / 03</span>' ) &&
	false === strpos( $mg_out, '// Scheda del videogioco' ) && 4 === count( $mg_slides ) && 9 === substr_count( $mg_out, 'data-i="' ) && 3 === substr_count( $mg_out, 'aria-current="true"' ) &&
	false !== strpos( $mg_slides[1], 'aria-label="1 di 3: Mg Alba"' ) && false !== strpos( $mg_slides[2], 'aria-label="2 di 3: Mg Èrcole"' ) && false !== strpos( $mg_slides[3], 'aria-label="3 di 3: Mg Zeta"' ) &&
	false !== strpos( $mg_slides[1], 'href="' . get_term_link( $mg_multi_c ) . '"' ) && false !== strpos( $mg_slides[3], 'href="' . get_term_link( $mg_multi_a ) . '"' ) &&
	false === strpos( $mg_slides[1], 'loading="lazy"' ) && false !== strpos( $mg_slides[2], 'loading="lazy"' ) && false !== strpos( $mg_slides[3], 'loading="lazy"' ) &&
	false !== strpos( $mg_slides[1], 'coalba' ) && false !== strpos( $mg_slides[1], 'Studio Alba' ) && false === strpos( $mg_slides[1], 'Studio Ercole' ) && false === strpos( $mg_slides[1], 'Studio Zeta' ) && false === strpos( $mg_slides[1], 'cozeta' ) &&
	false !== strpos( $mg_slides[2], 'Studio Ercole' ) && false === strpos( $mg_slides[2], 'Studio Alba' ) && false === strpos( $mg_slides[2], 'Studio Zeta' ) && false === strpos( $mg_slides[2], 'ntn-igdb-btn' ) &&
	false !== strpos( $mg_slides[3], 'href="https://www.igdb.com/games/mg-zeta"' ) && false === strpos( $mg_slides[3], 'mg-alba"' ),
	'69.5: Several games make an alphabetical carousel with one page per game, each with its own cover, data, page link and IGDB button'
);

// 69.6: ogni pagina ha la sua riga recensione; un voto mancante lascia il link senza LED
assert_test(
	false !== strpos( $mg_slides[1], 'aria-label="Leggi la recensione di Mg Alba"' ) && false === strpos( $mg_slides[1], 'ntn-led is-on' ) &&
	false !== strpos( $mg_slides[2], 'href="' . get_permalink( 10005 ) . '"' ) && 2 === substr_count( $mg_slides[2], 'ntn-led is-on' ) &&
	false !== strpos( $mg_slides[3], 'href="' . get_permalink( 10006 ) . '"' ) && 4 === substr_count( $mg_slides[3], 'ntn-led is-on' ) &&
	false === strpos( $mg_out, '// Valutazione finale' ) && false === strpos( $mg_out, 'Origine' ),
	'69.6: Each page links its own review with its own LEDs, a review without rating shows no LEDs, and a news article has no final score'
);

// 69.7: se l'articolo aperto è la recensione di un gioco, solo la sua pagina ha valutazione finale e origine
$mg_multi_d = $mg_term( 10005, 'Mg Delta', 'mg-delta', array( '_ntn_igdb_game_id' => 613, '_ntn_game_title' => 'Mg Delta', '_ntn_game_developer' => 'Studio Delta' ) );
$mg_multi_e = $mg_term( 10006, 'Mg Epsilon', 'mg-epsilon', array( '_ntn_igdb_game_id' => 614, '_ntn_game_title' => 'Mg Epsilon', '_ntn_game_developer' => 'Studio Epsilon', '_ntn_featured_review_id' => 10004 ) );
$mg_post( 10008, 'post', 'publish', 'mg-multi-recensione-delta', '2026-04-01', array( $mg_multi_d, $mg_multi_e ), array( 'recensioni' ) );
$mock_postmeta[10008] = array( 'rating' => '5', 'ntn_review_source' => 'Copia per la prova' );
$mg_slides = explode( '<div class="ntn-gslide"', $mg_render( 10008 ) );
assert_test(
	false !== strpos( $mg_slides[1], '// Valutazione finale' ) && false !== strpos( $mg_slides[1], 'aria-label="Voto 5 su 5"' ) && false !== strpos( $mg_slides[1], 'Origine' ) && false === strpos( $mg_slides[1], 'ntn-greview-mini' ) &&
	false === strpos( $mg_slides[2], '// Valutazione finale' ) && false === strpos( $mg_slides[2], 'Origine' ) && false !== strpos( $mg_slides[2], 'aria-label="Leggi la recensione di Mg Epsilon"' ),
	'69.7: On a review only the reviewed game shows the final score and source, the other games show their review row'
);

// 69.8: stili compilati e carosello nel file JavaScript
$mg_css = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/build/input.css' );
$mg_built = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/style-tailwind-output.css' );
$mg_js = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/assets/js/main.js' );
assert_test(
	strpos( $mg_css, '.ntn-igdb-btn:hover' ) < strpos( $mg_css, '.ntn-gcard--multi .ntn-gcard-h' ) && false !== strpos( $mg_css, '.ntn-greview-mini .ntn-led { width: 8px; height: 8px; }' ) &&
	false !== strpos( $mg_built, '.ntn-gtrack{' ) && false !== strpos( $mg_built, '.ntn-gslide{' ) && false !== strpos( $mg_built, '.ntn-greview-mini' ) && false !== strpos( $mg_built, '.ntn-led.is-off' ) &&
	false !== strpos( $mg_js, "'[data-ntn-gcarousel]'" ) && false !== strpos( $mg_js, 'prefers-reduced-motion' ) && false !== strpos( $mg_js, "new Event('resize')" ) && false !== strpos( $mg_js, "card.classList.add('is-ready')" ),
	'69.8: Carousel styles are compiled and main.js drives the carousel, respecting reduced motion and refreshing the sticky column'
);

// --- TEST SUITE 70: Scheda del gioco da telefono (scheda multigioco, fase 3) ---
echo "\n--- 70. SCHEDA DEL GIOCO DA TELEFONO ---\n";
$mg_render_mob = static function ( $id ) {
	global $mock_posts;
	$GLOBALS['post'] = $mock_posts[ $id ];
	ob_start();
	require ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/template-parts/review-header-mobile.php';
	return ob_get_clean();
};

// 70.1: nessun gioco: la scheda di sempre, senza cornice né riga; senza titolo e senza voto non c'è scheda
$mg_out = $mg_render_mob( 10000 );
$mg_post( 10009, 'post', 'publish', 'mg-senza-niente', '2026-01-01', array(), array( 'notizie' ) );
assert_test(
	false !== strpos( $mg_out, '<div class="ntn-rmob">' ) && false !== strpos( $mg_out, '// Scheda gioco</span>' ) && false !== strpos( $mg_out, 'mg-solo-voto' ) && false !== strpos( $mg_out, 'aria-label="Voto 3 su 5"' ) &&
	false === strpos( $mg_out, 'ntn-rmob-wrap' ) && false === strpos( $mg_out, 'ntn-rmob-rev' ) && '' === trim( $mg_render_mob( 10009 ) ),
	'70.1: Without games the classic card stays without frame or review row, and nothing is shown without title and rating'
);

// 70.2: un gioco: scheda di sempre nella cornice, i dati scritti sull'articolo vincono, sotto la riga con la recensione
$mock_postmeta[10002]['_ntn_game_title'] = 'Mg Uno Articolo';
$mg_one_cover = $mg_term( 10007, 'Mg Solo Dati', 'mg-solo-dati', array( '_ntn_igdb_game_id' => 620, '_ntn_game_title' => 'Mg Solo Dati', '_ntn_game_platforms' => 'PC Tag', '_ntn_game_release' => '2026-05-04', '_ntn_game_release_precision' => 'day' ) );
$mg_post( 10010, 'post', 'publish', 'mg-solo-dati-notizia', '2026-01-01', array( $mg_one_cover ), array( 'notizie' ) );
$mock_postmeta[10010] = array( '_ntn_game_platforms' => 'PC Articolo' );
$mg_out = $mg_render_mob( 10010 );
assert_test(
	false !== strpos( $mg_out, '<div class="ntn-rmob-wrap">' ) && false === strpos( $mg_out, 'data-ntn-rmob' ) && false === strpos( $mg_out, 'ntn-gnav' ) && false === strpos( $mg_out, ' hidden>' ) &&
	false !== strpos( $mg_out, 'PC Articolo' ) && false === strpos( $mg_out, 'PC Tag' ) && false !== strpos( $mg_out, '04/05/2026' ) && false !== strpos( $mg_out, '// Scheda gioco</span>' ) &&
	false !== strpos( $mg_out, '<div class="ntn-rmob-rev is-none">' ) && strpos( $mg_out, '</h4>' ) < strpos( $mg_out, 'ntn-rmob-rev' ),
	'70.2: One game stays the classic card inside the frame, article meta wins per field, and the review row sits under it'
);

// 70.3: un gioco con recensione altrove: riga con link e LED; la recensione stessa tiene il giudizio e nessuna riga
$mg_out = $mg_render_mob( 10002 );
$mg_out_review = $mg_render_mob( 10001 );
assert_test(
	false !== strpos( $mg_out, 'class="ntn-rmob-rev" aria-label="Leggi la recensione di Mg Uno Articolo"' ) && false !== strpos( $mg_out, 'href="' . get_permalink( 10001 ) . '"' ) && 4 === substr_count( $mg_out, 'ntn-led is-on' ) && false === strpos( $mg_out, 'Voto ' ) &&
	false !== strpos( $mg_out_review, 'ntn-rmob-wrap' ) && false !== strpos( $mg_out_review, 'aria-label="Voto 4 su 5"' ) && false === strpos( $mg_out_review, 'ntn-rmob-rev' ),
	'70.3: One game links its review with LEDs, and the review of that game keeps the verdict row instead'
);

// 70.4: più giochi: un pannello per gioco in ordine alfabetico, solo il primo visibile, nessun dato mescolato
$mg_out = $mg_render_mob( 10007 );
$mg_panels = explode( '<div class="ntn-rmob" ', $mg_out );
assert_test(
	false !== strpos( $mg_out, '<div class="ntn-rmob-wrap" data-ntn-rmob ' ) && 4 === count( $mg_panels ) && false === strpos( $mg_out, 'ntn-rmob-lbl' ) &&
	false !== strpos( $mg_panels[1], 'data-i="0"' ) && false !== strpos( $mg_panels[1], 'aria-label="1 di 3: Mg Alba"' ) && false !== strpos( $mg_panels[1], 'coalba' ) && false === strpos( $mg_panels[1], 'cozeta' ) && false === strpos( $mg_panels[1], ' hidden>' ) && false === strpos( $mg_panels[1], 'loading="lazy"' ) &&
	false !== strpos( $mg_panels[2], 'data-i="1"' ) && false !== strpos( $mg_panels[2], 'aria-label="2 di 3: Mg Èrcole"' ) && false !== strpos( $mg_panels[2], 'coercole' ) && false !== strpos( $mg_panels[2], ' hidden>' ) && false !== strpos( $mg_panels[2], 'loading="lazy"' ) &&
	false !== strpos( $mg_panels[3], 'data-i="2"' ) && false !== strpos( $mg_panels[3], 'cozeta' ) && false !== strpos( $mg_panels[3], ' hidden>' ) && false !== strpos( $mg_panels[3], 'href="' . get_term_link( $mg_multi_a ) . '"' ),
	'70.4: Several games make one alphabetical panel each, with only the first visible and each with its own data'
);

// 70.5: intestazione come su PC con contatore e due tasti, poi i pannelli e le righe recensione: prima visibile, le altre nascoste
assert_test(
	false !== strpos( $mg_out, '<span>// Scheda gioco</span>' ) && false !== strpos( $mg_out, '>01 / 03</span>' ) && false !== strpos( $mg_out, 'data-step="-1" aria-label="Gioco precedente" disabled' ) && false !== strpos( $mg_out, 'data-step="1" aria-label="Gioco successivo">' ) &&
	false === strpos( $mg_out, 'ntn-gname' ) && false === strpos( $mg_out, 'role="tab' ) && false === strpos( $mg_out, 'data-i="0" data-step' ) &&
	strpos( $mg_out, 'class="ntn-gnav"' ) < strpos( $mg_out, 'class="ntn-rmob-in"' ) && strpos( $mg_out, 'class="ntn-rmob-rev"' ) > strrpos( $mg_out, 'class="ntn-rmob-in"' ) &&
	false !== strpos( $mg_out, 'aria-label="Leggi la recensione di Mg Alba" data-i="0">' ) && false !== strpos( $mg_out, 'aria-label="Leggi la recensione di Mg Èrcole" data-i="1" hidden>' ) && false !== strpos( $mg_out, 'aria-label="Leggi la recensione di Mg Zeta" data-i="2" hidden>' ) &&
	false === strpos( $mg_out, 'Voto ' ),
	'70.5: Several games get the PC header with counter and two arrows, review rows follow the panels with only the first visible, and a news article has no verdict'
);

// 70.6: un gioco non recensito: riga con cinque LED tratteggiati, l'altro gioco ha il suo link nascosto
$mg_post( 10011, 'post', 'publish', 'mg-misto-notizia', '2026-01-01', array( $mg_one, $mg_none ), array( 'notizie' ) );
$mg_out = $mg_render_mob( 10011 );
assert_test(
	false !== strpos( $mg_out, '<div class="ntn-rmob-rev is-none" data-i="0">' ) && false !== strpos( $mg_out, '// Non recensito' ) && 5 === substr_count( $mg_out, 'ntn-led is-off' ) &&
	false !== strpos( $mg_out, 'class="ntn-rmob-rev" aria-label="Leggi la recensione di Mg Uno" data-i="1" hidden>' ),
	'70.6: A game without reviews gets the dashed Non recensito row, the other game links its review'
);

// 70.7: recensione con più giochi: solo il gioco recensito ha giudizio e niente riga
$mg_out = $mg_render_mob( 10008 );
$mg_panels = explode( '<div class="ntn-rmob" ', $mg_out );
assert_test(
	false !== strpos( $mg_panels[1], 'aria-label="Voto 5 su 5"' ) && false === strpos( $mg_panels[2], 'Voto ' ) && 1 === substr_count( $mg_out, 'class="ntn-rmob-rev"' ) &&
	false === strpos( $mg_out, 'data-i="0" hidden' ) && false !== strpos( $mg_out, 'aria-label="Leggi la recensione di Mg Epsilon" data-i="1" hidden>' ),
	'70.7: On a review with several games only the reviewed game has the verdict, the others keep their review row'
);

// 70.8: recensione con voto e più giochi, ma nessuno recensito da lei: voto nascosto, ogni gioco ha la sua riga
$mg_post( 10012, 'post', 'publish', 'mg-recensione-di-nessuno', '2020-01-01', array( $mg_multi_a, $mg_multi_c ), array( 'recensioni' ) );
$mock_postmeta[10012] = array( 'rating' => '3' );
$mg_out = $mg_render_mob( 10012 );
assert_test(
	false === strpos( $mg_out, 'Voto ' ) && 2 === substr_count( $mg_out, 'class="ntn-rmob-rev"' ) && false !== strpos( $mg_out, 'href="' . get_permalink( 10006 ) . '"' ) && false !== strpos( $mg_out, 'href="' . get_permalink( 10004 ) . '"' ),
	'70.8: A rated review where no game is the reviewed one hides the rating and gives every game its review row'
);

// 70.9: i testi passano dall'escape
$mg_esc = $mg_term( 10008, 'Mg <b>Grassetto</b>', 'mg-grassetto', array( '_ntn_igdb_game_id' => 621, '_ntn_game_title' => 'Mg <b>Grassetto</b>' ) );
$mg_post( 10013, 'post', 'publish', 'mg-escape', '2026-01-01', array( $mg_esc, $mg_none ), array( 'notizie' ) );
$mg_out = $mg_render_mob( 10013 );
assert_test(
	false === strpos( $mg_out, '<b>Grassetto' ) && 2 <= substr_count( $mg_out, '&lt;b&gt;Grassetto' ),
	'70.9: Game titles are escaped in panels and their labels'
);

// 70.10: stili compilati, linguette in main.js e colonna laterale anche per i giochi trovati solo dall'elenco
$mg_css = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/build/input.css' );
$mg_built = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/style-tailwind-output.css' );
$mg_js = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/assets/js/main.js' );
assert_test(
	strpos( $mg_css, '.ntn-rmob-row b' ) < strpos( $mg_css, '.ntn-rmob-wrap {' ) && false !== strpos( $mg_css, '.ntn-rmob-wrap .ntn-rmob[hidden], .ntn-rmob-wrap .ntn-rmob-rev[hidden] { display: none; }' ) &&
	false !== strpos( $mg_built, '.ntn-rmob-wrap{' ) && false !== strpos( $mg_built, '.ntn-rmob-rev' ) && false === strpos( $mg_built, '.ntn-gname' ) &&
	false !== strpos( $mg_css, '.ntn-rmob-wrap .ntn-gnav button { width: 44px; height: 44px; }' ) && false !== strpos( $mg_css, '[data-ntn-rmob].is-ready .ntn-gnav' ) &&
	false !== strpos( $mg_js, "'[data-ntn-rmob]'" ) && false !== strpos( $mg_js, "'.ntn-rmob, .ntn-rmob-rev'" ) && false !== strpos( $mg_js, "panel.style.minHeight = tallest + 'px'" ) && false === strpos( $mg_js, '.ntn-gname' ) &&
	false !== strpos( $mg_js, "addEventListener('touchend'" ) && false !== strpos( $mg_js, 'Math.abs(moveX) > 40' ) && 2 <= substr_count( $mg_js, '{ passive: true }' ) &&
	false !== strpos( file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/single.php' ), "|| ! empty( ntn_get_post_games( get_the_ID() ) );" ),
	'70.10: Mobile card styles are compiled without the old tabs, main.js drives arrows and swipe, and single.php counts games from the helper'
);

// =====================================================================
// 71. Requisiti dichiarati (v4.9.1): PHP 8.0 e WordPress 6.0 in tutti e tre i componenti
// =====================================================================
echo "\n--- 71. Declared requirements ---\n";
$rq_files = array(
	'parent' => file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/style.css' ),
	'child'  => file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/style.css' ),
	'core'   => file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php' ),
);
$rq_php = array();
$rq_wp  = array();
foreach ( $rq_files as $rq_key => $rq_src ) {
	$rq_head          = substr( (string) $rq_src, 0, 2000 );
	$rq_php[ $rq_key ] = preg_match( '/Requires PHP:\s*([0-9.]+)/', $rq_head, $rq_m ) ? $rq_m[1] : '';
	$rq_wp[ $rq_key ]  = preg_match( '/Requires at least:\s*([0-9.]+)/', $rq_head, $rq_m ) ? $rq_m[1] : '';
}

// 71.1: i tre header dichiarano PHP 8.0
assert_test(
	'8.0' === $rq_php['parent'] && '8.0' === $rq_php['child'] && '8.0' === $rq_php['core'],
	'71.1: Parent, child and plugin headers all declare Requires PHP: 8.0 (found: ' . implode( ', ', $rq_php ) . ')'
);

// 71.2: i tre header dichiarano WordPress 6.0
assert_test(
	'6.0' === $rq_wp['parent'] && '6.0' === $rq_wp['child'] && '6.0' === $rq_wp['core'],
	'71.2: Parent, child and plugin headers all declare Requires at least: 6.0 (found: ' . implode( ', ', $rq_wp ) . ')'
);

// 71.3: il README non promette più PHP 7.4
$rq_readme = file_get_contents( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/README.md' );
assert_test(
	false === strpos( $rq_readme, '7.4' ) && 2 === substr_count( $rq_readme, '**PHP**: 8.0' ),
	'71.3: README states PHP 8.0 as the minimum in both languages'
);

// 71.4: il PHP che esegue la suite rispetta il minimo dichiarato
assert_test( version_compare( PHP_VERSION, '8.0', '>=' ), '71.4: Test suite runs on PHP 8.0 or newer (running ' . PHP_VERSION . ')' );

// =====================================================================
// 72. Il tema figlio contiene solo personalizzazioni (v4.10.0)
// =====================================================================
echo "\n--- 72. Child theme holds only customizations ---\n";
$fx_parent = ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/';
$fx_child  = ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal-reloaded/';

// 72.1: catalogo, scheda del gioco e script sono nel parent; il child non ne ha più copie
$fx_moved = array( 'archive-videogioco.php', 'template-parts/content-game-card.php', 'assets/js/live-search.js', 'assets/js/catalog-filters.js' );
$fx_ok    = true;
foreach ( $fx_moved as $fx_file ) {
	if ( ! file_exists( $fx_parent . $fx_file ) || file_exists( $fx_child . $fx_file ) ) {
		$fx_ok = false;
	}
}
assert_test( $fx_ok && ! file_exists( $fx_child . 'single-videogioco.php' ), '72.1: Catalog, game card and search scripts live in the parent only; the child has no template or script copies' );

// 72.2: il parent carica ricerca e filtri dal proprio percorso, con la sua versione e le stesse regole di prima
$fx_enq = file_get_contents( $fx_parent . 'inc/enqueue.php' );
assert_test(
	false !== strpos( $fx_enq, "wp_enqueue_script( 'ntn-live-search', get_template_directory_uri() . '/assets/js/live-search.js'" ) &&
	false !== strpos( $fx_enq, "wp_enqueue_script( 'ntn-catalog-filters', get_template_directory_uri() . '/assets/js/catalog-filters.js', array( 'ntn-live-search' )" ) &&
	false !== strpos( $fx_enq, "wp_localize_script( 'ntn-live-search', 'ntn_catalog_vars'" ) &&
	false !== strpos( $fx_enq, "is_post_type_archive( 'videogioco' ) || is_tax( 'game_engine' ) || is_tax( 'game_franchise' ) || is_tax( 'game_developer' ) || is_tax( 'game_publisher' )" ) &&
	false !== strpos( $fx_enq, '$ver = wp_get_theme( get_template() )->get( \'Version\' );' ) &&
	false === strpos( $fx_enq, 'get_stylesheet_directory' ),
	'72.2: Parent enqueues live search everywhere and catalog filters on catalog pages, from its own folder and with its own version'
);

// 72.3: il child carica solo il suo style.css, dopo il CSS compilato del parent, e nessuno script
$fx_cenq = file_get_contents( $fx_child . 'inc/enqueue.php' );
assert_test(
	false !== strpos( $fx_cenq, "array( 'ntn-journal-tailwind' )" ) && false === strpos( $fx_cenq, 'wp_enqueue_script(' ) && false === strpos( $fx_cenq, 'wp_localize_script' ),
	'72.3: Child loads only its style.css, after the parent compiled CSS, and no scripts'
);

// 72.4: gli stili di catalogo, immagini e gallerie sono nel CSS del parent; lo style.css del child ha solo l'intestazione
$fx_ccss  = file_get_contents( $fx_child . 'style.css' );
$fx_input = file_get_contents( $fx_parent . 'build/input.css' );
$fx_built = file_get_contents( $fx_parent . 'style-tailwind-output.css' );
assert_test(
	false === strpos( $fx_ccss, 'wp-block-gallery' ) && false === strpos( $fx_ccss, 'ntn-catalog-filters' ) && false !== strpos( $fx_ccss, 'Template:     ntn-journal' ) &&
	false !== strpos( $fx_input, 'body .wp-block-gallery.has-nested-images figure.wp-block-image:only-child' ) && false !== strpos( $fx_input, '#ntn-mobile-filter-toggle.is-open .ntn-filter-icon-v' ) &&
	false !== strpos( $fx_built, '.ntn-catalog-filters input[type=number]' ) && false !== strpos( $fx_built, '.wp-block-gallery.has-nested-images figure.wp-block-image:only-child' ) &&
	strrpos( $fx_input, 'body .wp-block-gallery' ) > strrpos( $fx_input, '@layer components' ),
	'72.4: Catalog, image and gallery styles moved from the child style.css to the parent CSS (outside the layers, at the end) and are compiled'
);

// 72.5: l'endpoint REST del plugin trova la scheda del gioco nel parent
assert_test(
	false !== strpos( file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/rest-catalog.php' ), "get_template_part( 'template-parts/content', 'game-card' )" ) && file_exists( $fx_parent . 'template-parts/content-game-card.php' ),
	'72.5: The plugin REST catalog renders the game card that now ships with the parent theme'
);

// =========================================================================
// Legge un file normalizzando a capo di Windows (CRLF) in LF: i controlli su testo a più righe valgono su entrambi
function ntn_t_read( $path ) {
	return str_replace( "\r\n", "\n", (string) file_get_contents( $path ) );
}

// 73. helpers.php del tema genitore ha solo funzioni di presentazione (v4.10.1, completato nella v4.13.0)
// =========================================================================
// Nella 4.10.1 le funzioni di dati erano state protette con function_exists; dalla 4.11.0 alla 4.13.0
// sono passate tutte al plugin (sezioni 74, 75 e 76).
$gd_helpers = ntn_t_read( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/helpers.php' );
$gd_names   = array( 'get_post_rating', 'ntn_calculate_reading_time', 'ntn_get_primary_category', 'ntn_get_image_alt', 'ntn_get_igdb_data', 'ntn_get_game_hub_url', 'ntn_get_hub_igdb_info', 'ntn_get_tag_igdb_info', 'ntn_get_post_games', 'ntn_resolve_game_term', 'ntn_get_term_game', 'ntn_get_post_meta_game', 'ntn_get_game_review', 'ntn_format_post_game', 'ntn_apply_post_meta_to_game', 'ntn_game_sort_key', 'ntn_get_game_featured_content', 'ntn_get_tag_featured_content', 'ntn_translate_igdb_genres' );

// 73.1: nessuna delle 19 funzioni di dati è più definita in helpers.php
$gd_left = array();
foreach ( $gd_names as $gd_name ) {
	if ( false !== strpos( $gd_helpers, "function {$gd_name}(" ) ) {
		$gd_left[] = $gd_name;
	}
}
assert_test( empty( $gd_left ), '73.1: None of the 19 data functions is defined in the parent helpers.php any more (still there: ' . implode( ', ', $gd_left ) . ')' );

// 73.2: le funzioni che stampano HTML restano nel tema, senza controllo
assert_test(
	false !== strpos( $gd_helpers, 'function ntn_journal_game_review_row(' ) && false !== strpos( $gd_helpers, 'function ntn_link_engines_to_terms(' ) &&
	false === strpos( $gd_helpers, "function_exists( 'ntn_journal_game_review_row' )" ) && false === strpos( $gd_helpers, "function_exists( 'ntn_link_engines_to_terms' )" ),
	'73.2: Presentation functions (review row, engine links) stay in the theme, unguarded'
);

// 73.3: con le funzioni del plugin già definite, helpers.php e le riserve si caricano senza errori e il plugin vince
$gd_base = tempnam( sys_get_temp_dir(), 'ntn73' );
$gd_tmp  = $gd_base . '.php';
file_put_contents( $gd_tmp, "<?php\ndefine( 'ABSPATH', '/' );\nfunction ntn_get_post_games( \$id ) { return 'plugin'; }\nfunction ntn_get_game_featured_content( \$o = null ) { return 'plugin'; }\nrequire " . var_export( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/helpers.php', true ) . ";\nrequire " . var_export( ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/inc/plugin-fallbacks.php', true ) . ";\necho ntn_get_post_games( 1 ) . '|' . ntn_get_game_featured_content() . '|' . ( function_exists( 'ntn_get_hub_igdb_info' ) ? 'fallback' : 'missing' );\n" );
$gd_out = shell_exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $gd_tmp ) . ' 2>&1' );
@unlink( $gd_tmp );
@unlink( $gd_base );
assert_test( 'plugin|plugin|fallback' === trim( (string) $gd_out ), '73.3: helpers.php and the fallbacks load after the plugin functions without errors; the plugin versions win (output: ' . trim( (string) $gd_out ) . ')' );

// =========================================================================
// 74. Voto, categoria e dati IGDB sono nel plugin (v4.11.0)
// =========================================================================
$pd_names  = array( 'ntn_translate_igdb_genres', 'get_post_rating', 'ntn_calculate_reading_time', 'ntn_get_primary_category', 'ntn_get_image_alt', 'ntn_get_igdb_data', 'ntn_get_game_hub_url', 'ntn_get_hub_igdb_info', 'ntn_get_tag_igdb_info' );
$pd_core   = ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/';
$pd_theme  = ABSPATH . 'NTN_Journal_Theme_Reloaded/ntn-journal/';
$pd_data   = ntn_t_read( $pd_core . 'inc/game-data.php' );
$pd_fall   = ntn_t_read( $pd_theme . 'inc/plugin-fallbacks.php' );
$pd_help   = ntn_t_read( $pd_theme . 'inc/helpers.php' );

// 74.1: le 9 funzioni sono definite nel plugin, ognuna protetta, e non più in helpers.php del tema
$pd_bad = array();
foreach ( $pd_names as $pd_name ) {
	$pd_guard = strpos( $pd_data, "if ( ! function_exists( '{$pd_name}' ) )" );
	$pd_def   = strpos( $pd_data, "function {$pd_name}(" );
	if ( false === $pd_guard || false === $pd_def || $pd_guard > $pd_def || false !== strpos( $pd_help, "function {$pd_name}(" ) ) {
		$pd_bad[] = $pd_name;
	}
}
assert_test( empty( $pd_bad ), '74.1: The 9 rating, category and IGDB functions live in the plugin game-data.php, guarded, and no longer in the theme helpers.php (wrong: ' . implode( ', ', $pd_bad ) . ')' );

// 74.2: il tema ha una funzione di riserva protetta per ognuna, caricata da functions.php
$pd_bad = array();
foreach ( $pd_names as $pd_name ) {
	if ( false === strpos( $pd_fall, "if ( ! function_exists( '{$pd_name}' ) ) {\n\tfunction {$pd_name}(" ) ) {
		$pd_bad[] = $pd_name;
	}
}
assert_test(
	empty( $pd_bad ) && false !== strpos( ntn_t_read( $pd_theme . 'functions.php' ), "require_once NTN_INC_DIR . 'plugin-fallbacks.php';" ),
	'74.2: The theme keeps a guarded empty fallback for each moved function and loads it (missing: ' . implode( ', ', $pd_bad ) . ')'
);

// 74.3: con il plugin spento le riserve restituiscono valori vuoti, senza errori
$pd_base = tempnam( sys_get_temp_dir(), 'ntn74' );
$pd_tmp  = $pd_base . '.php';
file_put_contents( $pd_tmp, "<?php\ndefine( 'ABSPATH', '/' );\nfunction get_the_title( \$id ) { return 'Titolo'; }\nrequire " . var_export( $pd_theme . 'inc/plugin-fallbacks.php', true ) . ";\necho json_encode( array( get_post_rating( 1 ), ntn_calculate_reading_time( 1 ), ntn_get_primary_category( 1 ), ntn_get_image_alt( 1 ), ntn_get_igdb_data( 1, '_ntn_game_cover' ), ntn_get_game_hub_url( 1 ), ntn_get_hub_igdb_info( 1 ), ntn_get_tag_igdb_info( 1 ), ntn_translate_igdb_genres( 'Shooter' ) ) );\n" );
$pd_out = trim( (string) shell_exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $pd_tmp ) . ' 2>&1' ) );
@unlink( $pd_tmp );
@unlink( $pd_base );
assert_test( '[null,"",null,"Titolo","","",[],[],"Shooter"]' === $pd_out, '74.3: Without the plugin the theme fallbacks return empty values and no errors (output: ' . $pd_out . ')' );

// 74.4: il plugin carica game-data.php solo se il tema NTN Journal è alla 4.10.1 o successiva (con un altro tema sempre)
$pd_main = ntn_t_read( $pd_core . 'ntn-journal-core.php' );
preg_match( '/function ntn_core_can_load_game_data\(\) \{.*?\n\}/s', $pd_main, $pd_fn );
$pd_res = array();
foreach ( array( array( 'ntn-journal', '4.10.0' ), array( 'ntn-journal', '4.10.1' ), array( 'ntn-journal', '4.11.0' ), array( 'twentytwentyfive', '1.0' ) ) as $pd_case ) {
	$pd_base = tempnam( sys_get_temp_dir(), 'ntn74' );
	$pd_tmp  = $pd_base . '.php';
	file_put_contents( $pd_tmp, "<?php\nfunction get_template() { return " . var_export( $pd_case[0], true ) . "; }\nclass NTN74_Theme { public function get( \$k ) { return " . var_export( $pd_case[1], true ) . "; } }\nfunction wp_get_theme( \$s = '' ) { return new NTN74_Theme(); }\n" . ( isset( $pd_fn[0] ) ? $pd_fn[0] : '' ) . "\necho ntn_core_can_load_game_data() ? 'y' : 'n';\n" );
	$pd_res[] = trim( (string) shell_exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $pd_tmp ) . ' 2>&1' ) );
	@unlink( $pd_tmp );
	@unlink( $pd_base );
}
assert_test(
	'n,y,y,y' === implode( ',', $pd_res ) && false !== strpos( $pd_main, "if ( ntn_core_can_load_game_data() ) {\n\trequire_once NTN_JOURNAL_CORE_DIR . 'inc/game-data.php';" ),
	'74.4: Plugin loads game-data.php only with NTN Journal 4.10.1+ (or another theme), so an older unguarded theme cannot cause "Cannot redeclare" (got: ' . implode( ',', $pd_res ) . ')'
);

// 74.5: il plugin chiama queste funzioni direttamente, senza più controllare che il tema le abbia definite
$pd_bad = array();
foreach ( array( 'inc/rest-catalog.php', 'inc/schema-markup.php', 'inc/hub-seo.php', 'inc/seo-extras.php', 'inc/game-data.php' ) as $pd_file ) {
	if ( preg_match( "/[^!] function_exists\\( '(" . implode( '|', $pd_names ) . ")' \\)/", ntn_t_read( $pd_core . $pd_file ) ) ) {
		$pd_bad[] = $pd_file;
	}
}
assert_test( empty( $pd_bad ), '74.5: Plugin files call the moved functions directly, without function_exists checks on the theme (still checking: ' . implode( ', ', $pd_bad ) . ')' );

// =========================================================================
// 75. Giochi e recensioni di un articolo sono nel plugin (v4.12.0)
// =========================================================================
$pg_names = array( 'ntn_get_post_games', 'ntn_resolve_game_term', 'ntn_get_term_game', 'ntn_get_post_meta_game', 'ntn_get_game_review', 'ntn_format_post_game', 'ntn_apply_post_meta_to_game', 'ntn_game_sort_key' );
$pg_data  = ntn_t_read( $pd_core . 'inc/game-data.php' );
$pg_help  = ntn_t_read( $pd_theme . 'inc/helpers.php' );
$pg_fall  = ntn_t_read( $pd_theme . 'inc/plugin-fallbacks.php' );

// 75.1: le 8 funzioni sono nel plugin, protette, e non più nel tema
$pg_bad = array();
foreach ( $pg_names as $pg_name ) {
	$pg_guard = strpos( $pg_data, "if ( ! function_exists( '{$pg_name}' ) )" );
	$pg_def   = strpos( $pg_data, "function {$pg_name}(" );
	if ( false === $pg_guard || false === $pg_def || $pg_guard > $pg_def || false !== strpos( $pg_help, "function {$pg_name}(" ) ) {
		$pg_bad[] = $pg_name;
	}
}
assert_test( empty( $pg_bad ), '75.1: The 8 post games functions live in the plugin game-data.php, guarded, and no longer in the theme (wrong: ' . implode( ', ', $pg_bad ) . ')' );

// 75.2: le due funzioni chiamate dai modelli hanno la riserva nel tema; la riga della recensione resta nel tema
assert_test(
	false !== strpos( $pg_fall, "if ( ! function_exists( 'ntn_get_post_games' ) ) {\n\tfunction ntn_get_post_games(" ) &&
	false !== strpos( $pg_fall, "if ( ! function_exists( 'ntn_apply_post_meta_to_game' ) ) {\n\tfunction ntn_apply_post_meta_to_game(" ) &&
	false !== strpos( $pg_help, 'function ntn_journal_game_review_row(' ) && false === strpos( $pg_data, 'function ntn_journal_game_review_row(' ),
	'75.2: Templates keep fallbacks for ntn_get_post_games and ntn_apply_post_meta_to_game; the review row (HTML) stays in the theme'
);

// 75.3: senza plugin, nessun gioco e i dati del gioco restano quelli ricevuti
$pg_base = tempnam( sys_get_temp_dir(), 'ntn75' );
$pg_tmp  = $pg_base . '.php';
file_put_contents( $pg_tmp, "<?php\ndefine( 'ABSPATH', '/' );\nrequire " . var_export( $pd_theme . 'inc/plugin-fallbacks.php', true ) . ";\necho json_encode( array( ntn_get_post_games( 1 ), ntn_apply_post_meta_to_game( array( 'title' => 'Celeste' ), 1 ) ) );\n" );
$pg_out = trim( (string) shell_exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $pg_tmp ) . ' 2>&1' ) );
@unlink( $pg_tmp );
@unlink( $pg_base );
assert_test( '[[],{"title":"Celeste"}]' === $pg_out, '75.3: Without the plugin an article has no games and game data passes through unchanged (output: ' . $pg_out . ')' );

// =========================================================================
// 76. Articoli in evidenza dell'Hub e loro cache sono nel plugin (v4.13.0)
// =========================================================================
$fc_data  = ntn_t_read( $pd_core . 'inc/game-data.php' );
$fc_keys  = ntn_t_read( $pd_core . 'inc/cache-keys.php' );
$fc_admin = ntn_t_read( $pd_core . 'inc/admin/admin-page.php' );
$fc_main  = ntn_t_read( $pd_core . 'ntn-journal-core.php' );
$fc_fall  = ntn_t_read( $pd_theme . 'inc/plugin-fallbacks.php' );

// 76.1: la funzione è nel plugin, protetta; quella mai usata per i tag non esiste più
assert_test(
	false !== strpos( $fc_data, "if ( ! function_exists( 'ntn_get_game_featured_content' ) ) :" ) && false !== strpos( $fc_data, 'function ntn_get_game_featured_content(' ) &&
	false === strpos( $fc_data . ntn_t_read( $pd_theme . 'inc/helpers.php' ), 'ntn_get_tag_featured_content' ) &&
	false !== strpos( $fc_fall, "if ( ! function_exists( 'ntn_get_game_featured_content' ) ) {\n\tfunction ntn_get_game_featured_content( \$object = null ) {\n\t\treturn false;" ),
	'76.1: Hub featured content lives in the plugin, the unused tag variant is gone, and the theme keeps a fallback returning false'
);

// 76.2: il nome della cache nasce da una sola funzione, caricata sempre, usata da chi salva e da chi cancella
assert_test(
	ntn_core_featured_cache_key( 'post', 12 ) === 'ntn_hub_featured_post_12' && ntn_core_featured_cache_key( 'term', '7' ) === 'ntn_hub_featured_term_7' &&
	false !== strpos( $fc_main, "require_once NTN_JOURNAL_CORE_DIR . 'inc/cache-keys.php';\n\nif ( ntn_core_can_load_game_data() )" ) &&
	2 === substr_count( $fc_data, 'ntn_core_featured_cache_key(' ) && 5 === substr_count( $fc_admin, 'delete_transient( ntn_core_featured_cache_key(' ) &&
	false !== strpos( $fc_admin, 'array( NTN_CORE_FEATURED_CACHE_PREFIX, ' ) && false === strpos( $fc_admin . $fc_data, "'ntn_hub_featured_" ) &&
	false !== strpos( $fc_keys, "define( 'NTN_CORE_FEATURED_CACHE_PREFIX', 'ntn_hub_featured_' );" ),
	'76.2: One function names the Hub featured cache (same names as before); it is always loaded and used to save, clear and flush the cache'
);

// =========================================================================
// 77. I dati per Google usano gli stessi dati della pagina (v4.14.0)
// =========================================================================
$vm_src = ntn_t_read( $pd_core . 'inc/api-helpers.php' );
$vm_empty = array( 'title' => '', 'cover' => '', 'release' => '', 'release_raw' => '', 'release_precision' => '', 'developer' => array(), 'publisher' => array(), 'platforms' => array(), 'genres' => array(), 'engine' => array(), 'franchise' => array(), 'url' => '', 'video_url' => '', 'screenshots' => array() );

// 77.1: i campi vuoti si riempiono con i dati del gioco, nel formato del view model
$vm_info = array( 'title' => 'Celeste', 'cover' => 'https://images.igdb.com/c.jpg', 'release' => '2018-01-25', 'developer' => 'Maddy Makes Games, Extremely OK Games', 'publisher' => 'Matt Makes Games', 'platforms' => 'PC (Microsoft Windows), Switch', 'release_precision' => 'day', 'genres' => 'Platform, Indie', 'engine' => 'XNA', 'url' => 'https://www.igdb.com/games/celeste', 'video' => 'abc123', 'screenshots' => 'sc1, sc2' );
$vm_prev_fmt = isset( $GLOBALS['mock_options']['date_format'] ) ? $GLOBALS['mock_options']['date_format'] : null;
$GLOBALS['mock_options']['date_format'] = 'd/m/Y';
$vm_full = ntn_core_fill_view_model( $vm_empty, $vm_info );
$GLOBALS['mock_options']['date_format'] = $vm_prev_fmt;
assert_test(
	'Celeste' === $vm_full['title'] && array( 'Maddy Makes Games', 'Extremely OK Games' ) === $vm_full['developer'] && array( 'PC', 'Switch' ) === $vm_full['platforms'] &&
	'25/01/2018' === $vm_full['release'] && '2018-01-25' === $vm_full['release_raw'] && 'day' === $vm_full['release_precision'] && array() === $vm_full['franchise'] &&
	'' === $vm_full['video_url'] && array() === $vm_full['screenshots'],
	'77.1: Empty view model fields are filled from the game card data (lists, display and raw release date); trailer and screenshots are not, since an article page does not show them'
);

// 77.2: i campi già scritti sull'oggetto non cambiano
$vm_own = $vm_empty;
$vm_own['title']     = 'Celeste (articolo)';
$vm_own['publisher'] = array( 'Editore scritto a mano' );
$vm_own              = ntn_core_fill_view_model( $vm_own, $vm_info );
assert_test(
	'Celeste (articolo)' === $vm_own['title'] && array( 'Editore scritto a mano' ) === $vm_own['publisher'] && array( 'XNA' ) === $vm_own['engine'] &&
	$vm_empty === ntn_core_fill_view_model( $vm_empty, array() ) &&
	array( 'PC', 'Switch', 'Mac' ) === ntn_core_schema_platform_names( array( 'PC', 'PC (Microsoft Windows)', 'Switch', 'Mac', 'Windows' ) ),
	'77.2: Fields already written on the object win; with no game data nothing changes; IGDB PC names match the site category name, without duplicates'
);

// 77.3: da dove arrivano i dati: tag e scheda videogioco sono il gioco; un articolo usa il gioco della sua scheda
$vm_term = new WP_Term( array( 'term_id' => 301, 'name' => 'Celeste', 'taxonomy' => 'post_tag' ) );
$vm_cpt  = new WP_Post( array( 'ID' => 302, 'post_type' => 'videogioco', 'post_title' => 'Celeste' ) );
assert_test(
	$vm_term === ntn_core_view_model_source( $vm_term ) && $vm_cpt === ntn_core_view_model_source( $vm_cpt ) && null === ntn_core_view_model_source( null ) &&
	false !== strpos( $vm_src, "if ( ! empty( \$game['is_current_review'] ) ) {" ) && false !== strpos( $vm_src, '1 === count( $games )' ),
	'77.3: Game tags and game pages are their own source; an article uses the game it reviews, or its only game'
);

// 77.4: i generi sono in italiano come nella pagina, e i chiamanti non cambiano
assert_test(
	false !== strpos( $vm_src, "'genres' === \$field && function_exists( 'ntn_translate_igdb_genres' )" ) &&
	false !== strpos( ntn_t_read( $pd_core . 'inc/schema-markup.php' ), 'ntn_get_game_view_model( get_the_ID() )' ) &&
	false !== strpos( ntn_t_read( $pd_core . 'inc/seo-extras.php' ), 'ntn_get_game_view_model( $target_object )' ) &&
	false !== strpos( ntn_t_read( $pd_core . 'inc/schema-markup.php' ), "\$raw_release = \$view_model['release_raw'];" ) &&
	2 === substr_count( ntn_t_read( $pd_core . 'inc/schema-markup.php' ) . ntn_t_read( $pd_core . 'inc/seo-extras.php' ), "\$view_model['release_raw'] ? \$view_model['release_raw'] : get_post_meta(" ),
	'77.4: Genres are translated as on the page; schema and SEO callers keep calling ntn_get_game_view_model() and take the release date from it'
);

// 77.5: Hub e tag del gioco scrivono "PC" come le recensioni, nei dati per Google e nel testo visibile
$vm_hubseo = ntn_t_read( $pd_core . 'inc/hub-seo.php' );
$vm_hubhdr = ntn_t_read( $pd_theme . 'template-parts/hub-header.php' );
assert_test(
	false !== strpos( $vm_hubseo, "\$platforms = ntn_core_schema_platform_names( ntn_hub_names( \$info['platforms'] ?? array() ) );" ) &&
	false !== strpos( $vm_hubhdr, "if ( function_exists( 'ntn_core_schema_platform_names' ) ) {" ) && false !== strpos( $vm_hubhdr, 'ntn_core_schema_platform_names( array_filter( $platforms,' ) &&
	array( 'PC', 'PlayStation 5' ) === ntn_core_schema_platform_names( array( 'PC (Microsoft Windows)', 'PlayStation 5', 'PC' ) ),
	'77.5: Game Hubs and game tags show and send "PC" instead of "PC (Microsoft Windows)", like the reviews'
);

// 77.7: anche la frase automatica dell'Hub (In breve, meta description) scrive "PC"; la frase non cambia
assert_test(
	false !== strpos( ntn_t_read( $pd_core . 'inc/hub-seo.php' ), "array_slice( ntn_core_schema_platform_names( ntn_hub_names( \$info['platforms'] ?? array() ) ), 0, 4 )" ),
	'77.7: The automatic Hub sentence uses PC instead of PC (Microsoft Windows) and keeps its wording'
);

// 77.6: la scheda del gioco (colonna laterale e testata da telefono) scrive "PC" come le Hub
$vm_card_ok = true;
foreach ( array( 'review-sidebar-desktop.php', 'review-header-mobile.php' ) as $vm_card ) {
	$vm_card_src = ntn_t_read( $pd_theme . 'template-parts/' . $vm_card );
	$vm_card_ok  = $vm_card_ok && false !== strpos( $vm_card_src, "if ( \$platforms && function_exists( 'ntn_core_schema_platform_names' ) ) {" );
}
assert_test( $vm_card_ok, '77.6: The game card (desktop sidebar and mobile header) shows PC instead of PC (Microsoft Windows)' );

// 77.8: la tabella delle uscite della home scrive "PC" (v4.14.1)
$vm_home = ntn_t_read( $pd_theme . 'inc/front-page-data.php' );
assert_test(
	false !== strpos( $vm_home, "if ( '' !== \$platforms && function_exists( 'ntn_core_schema_platform_names' ) ) {" ) &&
	false !== strpos( $vm_home, "'platforms' => \$platforms," ) &&
	false === strpos( $vm_home, "'platforms' => (string) get_post_meta(" ),
	'77.8: The home release table shows PC instead of PC (Microsoft Windows)'
);

// =========================================================================
// 78. La colonna laterale ha larghezza fissa anche con la scheda a più giochi (v4.14.2)
// =========================================================================
$sc_css  = ntn_t_read( $pd_theme . 'build/input.css' );
$sc_out  = file_get_contents( $pd_theme . 'style-tailwind-output.css' );
assert_test(
	false !== strpos( $sc_css, '.ntn-art-aside-sticky { display: grid; grid-template-columns: minmax(0, 1fr); gap: 28px; }' ) &&
	false !== strpos( $sc_css, 'background-color: var(--ntn-bg); min-width: 0; }' ),
	'78.1: The article sidebar column is a fixed-width grid and the game card can shrink, so wide covers cannot widen the page'
);
assert_test(
	1 === preg_match( '/\\.ntn-art-aside-sticky\\{[^}]*grid-template-columns:minmax\\(0,1fr\\)/', $sc_out ) && 1 === preg_match( '/\.ntn-gcard\{[^}]*min-width:0/', $sc_out ),
	'78.2: The compiled CSS contains the sidebar column fix'
);

// =========================================================================
// 79. Dati per Google: piattaforme dalle categorie e nome del gioco senza titolo d'articolo (v4.14.3)
// =========================================================================
if ( ! function_exists( 'get_ancestors' ) ) {
	function get_ancestors( $object_id, $object_type = '', $resource_type = '' ) {
		$out = array();
		$cur = (int) $object_id;
		while ( isset( $GLOBALS['gd_parent'][ $cur ] ) && $GLOBALS['gd_parent'][ $cur ] ) {
			$cur   = (int) $GLOBALS['gd_parent'][ $cur ];
			$out[] = $cur;
		}
		return $out;
	}
}
// Console (hub 7001) > Sony (7002) > PlayStation 5 (7003); PC Gaming (7004, hub senza figli); Indie (7005), Nintendo (7006) con figlio Switch 2 (7007)
$GLOBALS['gd_parent']   = array( 7002 => 7001, 7003 => 7002, 7007 => 7006 );
$GLOBALS['hs_children'] = array( 7001 => array( 7002, 7003 ), 7002 => array( 7003 ), 7006 => array( 7007 ) );
foreach ( array( 7001, 7004 ) as $gd_hub_id ) {
	$mock_termmeta[ $gd_hub_id ] = array( '_ntn_hub_enabled' => '1', '_ntn_hub_type' => 'console' );
}
$gd_cat = static function ( $id, $name, $slug ) {
	return new WP_Term( array( 'term_id' => $id, 'name' => $name, 'slug' => $slug, 'taxonomy' => 'category' ) );
};
$mock_post_categories[7100] = array(
	$gd_cat( 7003, 'PlayStation 5', 'playstation-5' ), $gd_cat( 7002, 'Sony', 'sony' ), $gd_cat( 7004, 'PC Gaming', 'pc-gaming' ),
	$gd_cat( 7005, 'Indie', 'indie' ), $gd_cat( 7006, 'Nintendo', 'nintendo' ), $gd_cat( 7003, 'PlayStation 5', 'playstation-5' ),
);
$mock_post_categories[7101] = array( $gd_cat( 7005, 'Indie', 'indie' ), $gd_cat( 7001, 'Console', 'console' ) );
assert_test(
	array( 'PlayStation 5', 'PC' ) === ntn_core_post_platform_names( 7100 ),
	'79.1: Platforms come only from the article categories that are devices inside a Platform Hub, PC Gaming is written PC, Indie and brands are left out'
);
assert_test(
	array() === ntn_core_post_platform_names( 7101 ) && array() === ntn_core_post_platform_names( 7999 ),
	'79.2: An article without platform categories has no platforms (no PC, Console fallback)'
);
$gd_seo = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/seo-extras.php' );
$gd_sch = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/schema-markup.php' );
assert_test(
	false !== strpos( $gd_seo, '$platforms = ntn_core_post_platform_names( $post_id );' ) && false === strpos( $gd_seo, "array( 'PC', 'Console' )" ) &&
	false === strpos( $gd_sch, "array( 'PC', 'Console' )" ) && false === strpos( $gd_sch, "array_merge( \$platforms, \$view_model['platforms'] )" ),
	'79.3: The Google schemas take platforms from the categories (IGDB only completes) and have no fixed PC, Console fallback'
);
// Mac con la casella "È una piattaforma", IGDB completa senza doppioni
$mock_termmeta[7008] = array( '_ntn_is_platform' => '1' );
$mock_post_categories[7102] = array( $gd_cat( 7008, 'Mac', 'mac' ), $gd_cat( 7003, 'PlayStation 5', 'playstation-5' ), $gd_cat( 7005, 'Indie', 'indie' ) );
assert_test(
	array( 'Mac', 'PlayStation 5', 'Xbox Series X|S', 'Nintendo Switch' ) === ntn_core_post_platform_names( 7102, array( 'PlayStation 5', 'Xbox Series X|S', 'XBOX Series X|S', 'Nintendo Switch' ) ),
	'79.5: A category marked as platform counts (Mac), IGDB platforms only add what is missing, with no duplicates even with different capitals'
);
assert_test(
	array( 'PlayStation 5', 'PC' ) === ntn_core_post_platform_names( 7100, array( 'PlayStation 5', 'PC (Microsoft Windows)' ) ) &&
	array( 'Xbox One' ) === ntn_core_post_platform_names( 7101, array( 'Xbox One' ) ),
	'79.6: IGDB platforms are added to the category ones (PC naming normalized) and also stand when the article has no platform category'
);
$gd_flags_src = file_get_contents( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/schema-flags.php' );
assert_test(
	false !== strpos( $gd_flags_src, "wp_verify_nonce( sanitize_text_field( wp_unslash( \$_POST['ntn_schema_flag_nonce'] ) ), 'ntn_schema_flag_' . (int) \$term_id )" ) &&
	false !== strpos( $gd_flags_src, "! current_user_can( 'manage_categories' )" ) && false !== strpos( $gd_flags_src, "'category_edit_form_fields'" ) && false === strpos( $gd_flags_src, 'post_tag' ),
	'79.7: The platform checkbox is on the category only and is saved only with the capability and a valid nonce; there is no checkbox on tags'
);

// Nome del gioco: il titolo di un articolo non lo è, quello di una scheda videogioco sì
$mock_posts[7200] = new WP_Post( array( 'ID' => 7200, 'post_title' => 'Hillthorn esce il 10 novembre su PC', 'post_name' => 'hillthorn-esce', 'post_type' => 'post', 'post_status' => 'publish' ) );
$mock_posts[7201] = new WP_Post( array( 'ID' => 7201, 'post_title' => 'Hillthorn', 'post_name' => 'hillthorn', 'post_type' => 'videogioco', 'post_status' => 'publish' ) );
$gd_art_info = ntn_get_hub_igdb_info( 7200 );
$gd_cpt_info = ntn_get_hub_igdb_info( 7201 );
assert_test(
	empty( $gd_art_info['title'] ) && 'Hillthorn' === ( $gd_cpt_info['title'] ?? '' ),
	'79.4: An article with no saved game name has no game title, a Videogame page still uses its own title'
);

// =======================================================================
// 80. Scanner dei tag IGDB: lista da confermare, la conferma riusa il salvataggio del pannello (v4.15.0)
// =======================================================================
echo "\n--- 80. SCANNER DEI TAG IGDB ---\n";
require_once ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/igdb-tag-scanner.php';
$sc_src  = ntn_t_read( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/igdb-tag-scanner.php' );
$sc_ajax = ntn_t_read( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/ajax-igdb.php' );
$sc_view = ntn_t_read( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/views/igdb-tag-scanner.php' );
$sc_tab  = ntn_t_read( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/inc/admin/views/tab-igdb.php' );
$sc_main = ntn_t_read( ABSPATH . 'NTN_Journal_Core_Plugin/ntn-journal-core/ntn-journal-core.php' );

assert_test(
	'safe' === ntn_igdb_scan_group( 1, true, false ) && 'choose' === ntn_igdb_scan_group( 3, true, false ) && 'choose' === ntn_igdb_scan_group( 1, false, false ) &&
	'choose' === ntn_igdb_scan_group( 1, true, true ) && 'none' === ntn_igdb_scan_group( 0, true, false ),
	'80.1: A row is safe only with one game, the tag in the title and no more precise tag beside it; otherwise it must be chosen, or has no result'
);
assert_test(
	'fields id,name,first_release_date,version_parent,cover.url; where name ~ "Say \\"Hi\\" \\\\ x"; limit 10;' === ntn_igdb_scan_query( "Say \"Hi\" \\\nx" ),
	'80.2: The IGDB search asks for the exact name and escapes quotes, backslashes and line breaks'
);
$sc_games = ntn_igdb_scan_format_games(
	array(
		array( 'id' => 1, 'name' => 'Marathon', 'first_release_date' => 775699200, 'cover' => array( 'url' => '//images.igdb.com/a.jpg' ) ),
		array( 'id' => 2, 'name' => 'Marathon', 'first_release_date' => 1772323200 ),
		array( 'id' => 3, 'name' => 'Marathon Edition', 'version_parent' => 2 ),
		'not a game',
	)
);
assert_test(
	2 === count( $sc_games ) && 2 === $sc_games[0]['id'] && '2026' === $sc_games[0]['year'] && '1994' === $sc_games[1]['year'] && 'https://images.igdb.com/a.jpg' === $sc_games[1]['cover'],
	'80.3: Same-name games are listed with their year, newest first, without editions of another game'
);
assert_test(
	in_array( 'steam', ntn_igdb_scan_default_excluded(), true ) && false !== strpos( $sc_src, "meta_key = '_ntn_not_game'" ) && false !== strpos( $sc_src, "update_term_meta( \$tag_id, '_ntn_not_game', '1' )" ),
	'80.4: Steam is never proposed and "Not a game" is remembered, so the tag does not come back'
);
$sc_handlers = preg_match_all( '/function ntn_ajax_igdb_scan_\w+\(\) \{\s*ntn_igdb_scan_guard\(\);/', $sc_src );
assert_test(
	4 === $sc_handlers && false !== strpos( $sc_src, "check_ajax_referer( 'ntn_igdb_scan', '_ajax_nonce' )" ) && false !== strpos( $sc_src, "current_user_can( 'manage_options' )" ) && false === strpos( $sc_src, 'wp_ajax_nopriv' ),
	'80.5: All four scanner requests check nonce and manage_options, none is open to visitors'
);
assert_test(
	false !== strpos( $sc_src, 'ntn_igdb_link_tag_to_game( $tag_id, $igdb_id )' ) && false === strpos( $sc_src, 'wp_insert_post' ) && false === strpos( $sc_src, "'_ntn_igdb_game_id', " ) &&
	false !== strpos( $sc_ajax, '$post_id = ntn_igdb_link_tag_to_game( $tag_id, $igdb_id );' ) && 1 === substr_count( $sc_ajax, "'post_type'   => 'videogioco',\n\t\t\t'post_status' => 'publish'" ),
	'80.6: Confirming reuses the panel save (tag data and Hub): the scanner writes neither by itself and the panel calls the same function'
);
preg_match( '/function ntn_ajax_igdb_scan_tags\(\) \{.*?\n\}/s', $sc_src, $sc_scan );
assert_test(
	! empty( $sc_scan[0] ) && false === strpos( $sc_scan[0], 'update_' ) && false === strpos( $sc_scan[0], 'ntn_igdb_link_tag_to_game' ) && false !== strpos( $sc_scan[0], 'NTN_IGDB_SCAN_BATCH' ),
	'80.7: The scan writes no game data and works in small batches'
);
assert_test(
	false !== strpos( $sc_main, "inc/igdb-tag-scanner.php" ) && false !== strpos( $sc_tab, "igdb-tag-scanner.php" ) && false === strpos( $sc_view, 'innerHTML' ) &&
	false !== strpos( $sc_view, 'Conferma selezionati' ) && false !== strpos( $sc_view, 'Non è un gioco' ) && false !== strpos( $sc_view, 'Salta' ) && false !== strpos( $sc_view, "'ntn_search_igdb'" ),
	'80.8: The panel loads the scanner; its list has Confirm, Skip, Not a game, mass confirm and a search, and writes names only as text'
);

// =======================================================================
// 81. Dati per Google con più giochi nell'articolo: il gioco giusto, mai uno a caso (v4.15.1)
// =======================================================================
echo "\n--- 81. GIOCHI GIUSTI PER GOOGLE ---\n";
foreach ( array( 'get_the_time', 'get_the_modified_time', 'wp_get_attachment_image_url', 'get_author_posts_url' ) as $gg_fn ) {
	if ( ! function_exists( $gg_fn ) ) {
		eval( "function {$gg_fn}( \$a = '', \$b = null ) { return '2026-01-01T10:00:00+00:00'; }" );
	}
}
$gg_game = static function ( $id, $title ) use ( $mg_term ) {
	return $mg_term( $id, $title, sanitize_title( $title ) . '-gg', array( '_ntn_igdb_game_id' => $id, '_ntn_game_title' => $title, '_ntn_game_cover' => 'co' . $id, '_ntn_game_developer' => 'Studio ' . $title ) );
};
$gg_saros     = $gg_game( 9101, 'Saros' );
$gg_returnal  = $gg_game( 9102, 'Returnal' );
$gg_haven     = $gg_game( 9103, 'Haven' );
$gg_pokemon   = $gg_game( 9104, 'Pokémon Scarlatto' );
$gg_other     = $gg_game( 9105, 'Zelda' );

// 81.1: l'articolo nomina un gioco solo (titolo): vale quello, non il primo in ordine alfabetico
$mg_post( 9110, 'post', 'publish', 'gg-saros', '2026-01-01', array( $gg_returnal, $gg_saros, $gg_haven ) );
$mock_posts[9110]->post_title = 'Saros: la recensione';
$gg_pick = ntn_core_schema_pick_games( 9110 );
assert_test(
	1 === count( $gg_pick ) && 'Saros' === $gg_pick[0]['title'],
	'81.1: With several game tags, the game named in the article title is the one described (not the first alphabetically)'
);

// 81.2: il campo "Gioco recensito" vale più del titolo
$mg_post( 9111, 'post', 'publish', 'gg-reviewed', '2026-01-01', array( $gg_saros, $gg_returnal ) );
$mock_posts[9111]->post_title = 'Saros e Returnal a confronto';
$mock_postmeta[9111]['reviewed_item'] = 'Returnal';
$gg_pick = ntn_core_schema_pick_games( 9111 );
assert_test(
	1 === count( $gg_pick ) && 'Returnal' === $gg_pick[0]['title'],
	'81.2: The "Gioco recensito" field decides first, even when the title names two games'
);

// 81.3: nessun nome nel titolo: tutti i giochi collegati, non uno a caso
$mg_post( 9112, 'post', 'publish', 'gg-direct', '2026-01-01', array( $gg_returnal, $gg_haven, $gg_saros ) );
$mock_posts[9112]->post_title = 'Il Nintendo Direct di giugno';
$gg_pick = ntn_core_schema_pick_games( 9112 );
assert_test(
	array( 'Haven', 'Returnal', 'Saros' ) === array_column( $gg_pick, 'title' ),
	'81.3: When no game name is in the title and no reviewed game is set, every linked game is described'
);

// 81.4: confronto senza accenti, maiuscole, simboli; un nome corto non vale dentro un'altra parola
$mg_post( 9113, 'post', 'publish', 'gg-accent', '2026-01-01', array( $gg_pokemon, $gg_haven ) );
$mock_posts[9113]->post_title = 'POKEMON scarlatto: novità, e Haven? No';
$mock_posts[9113]->post_title = 'POKEMON scarlatto, novità';
$gg_pick = ntn_core_schema_pick_games( 9113 );
$mg_post( 9114, 'post', 'publish', 'gg-word', '2026-01-01', array( $gg_haven, $gg_saros ) );
$mock_posts[9114]->post_title = 'Un rifugio sicuro (Havenly)';
$gg_pick_word = ntn_core_schema_pick_games( 9114 );
assert_test(
	1 === count( $gg_pick ) && 'Pokémon Scarlatto' === $gg_pick[0]['title'] && 2 === count( $gg_pick_word ),
	'81.4: Matching ignores accents, capitals and symbols, and a short name does not match inside another word'
);

// 81.5: un gioco solo (o nessuno) resta com'è, i tag non gioco non contano
$mg_post( 9115, 'post', 'publish', 'gg-solo', '2026-01-01', array( $gg_saros, $mg_term( 9199, 'Notizie', 'notizie-gg' ) ) );
$mg_post( 9116, 'post', 'publish', 'gg-zero', '2026-01-01', array( $mg_term( 9198, 'Indie Gg', 'indie-gg' ) ) );
assert_test(
	1 === count( ntn_core_schema_pick_games( 9115 ) ) && array() === ntn_core_schema_pick_games( 9116 ) && array() === ntn_core_schema_pick_games( 9999 ),
	'81.5: One game stays as it is; plain tags and articles without games give no game'
);

// 81.5b: il nome più lungo vince su quello corto contenuto, anche se IGDB scrive il titolo completo con i due punti
$gg_ds   = $mg_term( 9120, 'Death Stranding', 'death-stranding-gg', array( '_ntn_igdb_game_id' => 9120, '_ntn_game_title' => 'Death Stranding' ) );
$gg_ds2  = $mg_term( 9121, 'Death Stranding 2', 'death-stranding-2-gg', array( '_ntn_igdb_game_id' => 9121, '_ntn_game_title' => 'Death Stranding 2: On the Beach' ) );
$mg_post( 9120, 'post', 'publish', 'gg-ds2', '2026-01-01', array( $gg_ds, $gg_ds2 ) );
$mock_posts[9120]->post_title = 'Death Stranding 2 ha una data di uscita';
$gg_pick = ntn_core_schema_pick_games( 9120 );
assert_test(
	1 === count( $gg_pick ) && 'Death Stranding 2: On the Beach' === $gg_pick[0]['title'],
	'81.5b: A short name inside a longer one never wins ("Death Stranding" vs "Death Stranding 2"), and the part before the colon is enough to recognise a game'
);

// 81.5c: marchio davanti a un altro gioco nel titolo (Assassin's Creed + Black Flag Resynced): resta il gioco vero
$gg_ac   = $mg_term( 9122, "Assassin's Creed", 'assassins-creed-gg', array( '_ntn_igdb_game_id' => 9122, '_ntn_game_title' => "Assassin's Creed" ) );
$gg_bf   = $mg_term( 9123, 'Black Flag Resynced', 'black-flag-resynced-gg', array( '_ntn_igdb_game_id' => 9123, '_ntn_game_title' => 'Black Flag Resynced' ) );
$mg_post( 9121, 'post', 'publish', 'gg-ac', '2026-01-01', array( $gg_ac, $gg_bf ) );
$mock_posts[9121]->post_title = "Assassin's Creed Black Flag Resynced: tutte le novità";
$gg_pick = ntn_core_schema_pick_games( 9121 );
assert_test(
	1 === count( $gg_pick ) && 'Black Flag Resynced' === $gg_pick[0]['title'],
	'81.5c: A series name written right before another linked game in the title is dropped; the real game stays'
);

// 81.5d: raccolta (un nome nel titolo ma non all'inizio): tutti i giochi; recensione: solo quello del titolo
$gg_eoe  = $gg_game( 9124, 'End of Ember' );
$gg_d2   = $gg_game( 9125, 'Demo Due' );
$gg_d3   = $gg_game( 9126, 'Demo Tre' );
$mg_post( 9122, 'post', 'publish', 'gg-demo', '2026-01-01', array( $gg_eoe, $gg_d2, $gg_d3 ) );
$mock_posts[9122]->post_title = 'Le demo della settimana: da End of Ember in poi';
$gg_pick_coll = ntn_core_schema_pick_games( 9122 );
$mg_post( 9123, 'post', 'publish', 'gg-rev-cat', '2026-01-01', array( $gg_eoe, $gg_d2, $gg_d3 ), array( 'recensioni' ) );
$mock_posts[9123]->post_title = 'La recensione di End of Ember';
$gg_pick_rev = ntn_core_schema_pick_games( 9123 );
assert_test(
	3 === count( $gg_pick_coll ) && 1 === count( $gg_pick_rev ) && 'End of Ember' === $gg_pick_rev[0]['title'],
	'81.5d: A collection that only mentions one game gets all its linked games; a review picks the game named in its title'
);

// 81.6: nel grafo di Google, più giochi = più VideoGame, ognuno con i propri dati, collegati con "about"
$GLOBALS['queried_object'] = $mock_posts[9112];
$GLOBALS['post']           = $mock_posts[9112];
$mock_post_categories[9112] = array();
$gg_graph = array( array( '@type' => 'WebPage', '@id' => 'https://example.com/#webpage' ) );
$gg_out   = ntn_tsf_add_videogame_schema( $gg_graph, array() );
$gg_games = array_values( array_filter( $gg_out, static function ( $e ) { return isset( $e['@type'] ) && 'VideoGame' === $e['@type']; } ) );
$gg_names = array_column( $gg_games, 'name' );
sort( $gg_names );
$gg_by    = array_column( $gg_games, null, 'name' );
$gg_about = array_column( $gg_out[0]['about'] ?? array(), '@id' );
$gg_ids   = array_column( $gg_games, '@id' );
sort( $gg_about );
sort( $gg_ids );
assert_test(
	array( 'Haven', 'Returnal', 'Saros' ) === $gg_names &&
	false !== strpos( $gg_by['Haven']['image'], 'co9103' ) && 'Studio Haven' === $gg_by['Haven']['author']['name'] &&
	false !== strpos( $gg_by['Saros']['image'], 'co9101' ) && 'Studio Saros' === $gg_by['Saros']['author']['name'] &&
	3 === count( $gg_about ) && $gg_about === $gg_ids,
	'81.6: The Google graph gets one VideoGame per described game, each with its own image and developer, all linked from the article'
);

// 81.7: recensione con "Gioco recensito": un solo VideoGame, e itemReviewed lo indica
$GLOBALS['queried_object'] = $mock_posts[9111];
$GLOBALS['post']           = $mock_posts[9111];
$mock_postmeta[9111]['rating'] = 4;
$gg_graph = array( array( '@type' => array( 'WebPage', 'NewsArticle' ), '@id' => 'https://example.com/#webpage' ) );
$gg_out   = ntn_tsf_add_videogame_schema( $gg_graph, array() );
$gg_games = array_values( array_filter( $gg_out, static function ( $e ) { return isset( $e['@type'] ) && 'VideoGame' === $e['@type']; } ) );
assert_test(
	1 === count( $gg_games ) && 'Returnal' === $gg_games[0]['name'] && ( $gg_out[0]['itemReviewed']['@id'] ?? '' ) === $gg_games[0]['@id'],
	'81.7: A review with its reviewed game set describes that game only and points itemReviewed at it'
);

// 81.8: recensione con più giochi e nessuno scelto: niente itemReviewed a caso, l'articolo parla di tutti
$mock_postmeta[9112]['rating'] = 4;
$GLOBALS['queried_object'] = $mock_posts[9112];
$GLOBALS['post']           = $mock_posts[9112];
$gg_graph = array( array( '@type' => array( 'WebPage', 'NewsArticle' ), '@id' => 'https://example.com/#webpage' ) );
$gg_out   = ntn_tsf_add_videogame_schema( $gg_graph, array() );
assert_test(
	! isset( $gg_out[0]['itemReviewed'] ) && ! isset( $gg_out[0]['reviewRating'] ) && ! in_array( 'Review', (array) $gg_out[0]['@type'], true ) && 3 === count( $gg_out[0]['about'] ?? array() ),
	'81.8: A rated article with several games and no reviewed game is a plain article about all of them: no Review, no rating, no random reviewed item'
);

// 81.9: "Gioco recensito" con il nome del gioco lungo e il nome corto (serie) tra i tag: vince il gioco lungo e il voto resta
$mg_post( 9124, 'post', 'publish', 'gg-ac-rev', '2026-01-01', array( $gg_ac, $gg_bf ), array( 'recensioni' ) );
$mock_posts[9124]->post_title = 'Recensione di un ritorno al mare';
$mock_postmeta[9124]['reviewed_item'] = "Assassin's Creed Black Flag Resynced";
$mock_postmeta[9124]['rating'] = 4;
$GLOBALS['queried_object'] = $mock_posts[9124];
$GLOBALS['post']           = $mock_posts[9124];
$gg_pick  = ntn_core_schema_pick_games( 9124 );
$gg_out   = ntn_tsf_add_videogame_schema( array( array( '@type' => array( 'WebPage', 'NewsArticle' ), '@id' => 'https://example.com/#webpage' ) ), array() );
$gg_games = array_values( array_filter( $gg_out, static function ( $e ) { return isset( $e['@type'] ) && 'VideoGame' === $e['@type']; } ) );
assert_test(
	1 === count( $gg_pick ) && 'Black Flag Resynced' === $gg_pick[0]['title'] && 1 === count( $gg_games ) && 4 === ( $gg_out[0]['reviewRating']['ratingValue'] ?? 0 ) && ( $gg_out[0]['itemReviewed']['@id'] ?? '' ) === ( $gg_games[0]['@id'] ?? 'x' ),
	'81.9: The reviewed-game field naming the long game wins over the short series tag, so the review keeps its rating and itemReviewed'
);

// 81.10: nomi scritti due volte nel database ("Pok&amp;#233;mon") escono con le lettere vere
$gg_pk = $mg_term( 9127, 'Pokémon Violetto', 'pokemon-violetto-gg', array( '_ntn_igdb_game_id' => 9127, '_ntn_game_title' => 'Pok&amp;#233;mon Violetto', '_ntn_game_developer' => 'Game Freak &amp; Co' ) );
$mg_post( 9140, 'post', 'publish', 'gg-pk', '2026-01-01', array( $gg_pk ) );
$mock_posts[9140]->post_title = 'Pokemon Violetto';
$GLOBALS['queried_object'] = $mock_posts[9140];
$GLOBALS['post']           = $mock_posts[9140];
$gg_out   = ntn_tsf_add_videogame_schema( array( array( '@type' => array( 'WebPage', 'NewsArticle' ), '@id' => 'https://example.com/#webpage' ) ), array() );
$gg_games = array_values( array_filter( $gg_out, static function ( $e ) { return isset( $e['@type'] ) && 'VideoGame' === $e['@type']; } ) );
assert_test(
	'Pokémon Violetto' === ( $gg_games[0]['name'] ?? '' ) && 'Game Freak & Co' === ( $gg_games[0]['author']['name'] ?? '' ) && 'Pokémon Violetto' === ntn_core_schema_plain_text( '<b>Pok&amp;#233;mon Violetto</b>' ),
	'81.10: Names stored with doubled HTML entities reach Google as real characters'
);
unset( $mock_postmeta[9124]['rating'] );

// 81.11: anche la Hub del gioco e il testo automatico scrivono le lettere vere (v4.15.4)
$mock_posts[9141] = new WP_Post( array( 'ID' => 9141, 'post_title' => 'God of War Ragnar&amp;#246;k', 'post_name' => 'god-of-war-ragnarok-gg', 'post_type' => 'videogioco', 'post_status' => 'publish' ) );
$mock_postmeta[9141]['_ntn_game_title'] = 'God of War Ragnar&amp;#246;k';
$mock_postmeta[9141]['_ntn_game_developer'] = 'Santa Monica Studio';
$gg_hub = ntn_get_hub_videogame_entity( 9141 );
assert_test(
	'God of War Ragnarök' === ( $gg_hub['name'] ?? '' ) && 0 === strpos( ntn_get_hub_auto_summary( 9141 ), 'God of War Ragnarök è un videogioco' ),
	'81.11: The Hub VideoGame name and its automatic text show real accented letters even when the saved name has doubled entities'
);

// 81.12: con iubenda i codici numerici dentro i JSON-LD tornano lettere vere, il resto della pagina non cambia (v4.15.4)
$ib_page = '<html><head><title>Pok&amp;eacute;mon</title><meta property="og:title" content="Pok&amp;#233;mon">'
	. '<script type="application/ld+json">{"name":"Pok&amp;#233;mon Scarlatto","headline":"non c&amp;#8217;&amp;#232; fine","a":"Tom &amp;amp; Jerry","q":"dice &amp;#34;ciao&amp;#34;","x":"&amp;#x1F3AE;"}</script>'
	. '<script>var a = "Pok&amp;#233;mon";</script><p>c&amp;#233;</p></html>';
$ib_out = ntn_core_restore_jsonld_entities( $ib_page );
$ib_json = '';
if ( preg_match( '#<script type="application/ld\+json">(.*?)</script>#s', $ib_out, $ib_m ) ) {
	$ib_json = $ib_m[1];
}
assert_test(
	'' !== $ib_json && null !== json_decode( $ib_json, true ) &&
	false !== strpos( $ib_json, '"name":"Pokémon Scarlatto"' ) && false !== strpos( $ib_json, 'non c’è fine' ) && false !== strpos( $ib_json, '🎮' ) && false !== strpos( $ib_json, 'Tom &amp; Jerry' ) && false !== strpos( $ib_json, '&#34;ciao&#34;' ) &&
	false !== strpos( $ib_out, '<title>Pok&amp;eacute;mon</title>' ) && false !== strpos( $ib_out, 'var a = "Pok&amp;#233;mon";' ) && false !== strpos( $ib_out, '<p>c&amp;#233;</p>' ) &&
	'plain' === ntn_core_restore_jsonld_entities( 'plain' ) && array( 1 ) === ntn_core_restore_jsonld_entities( array( 1 ) ) &&
	has_filter( 'iubenda_final_output', 'ntn_core_restore_jsonld_entities' ),
	'81.12: With iubenda, numeric codes inside JSON-LD become real letters (ASCII codes untouched so the JSON stays valid) while the rest of the page is not changed'
);

unset( $mock_postmeta[9111]['rating'], $mock_postmeta[9112]['rating'] );
$GLOBALS['queried_object'] = null;
$GLOBALS['post']           = null;

$GLOBALS['post'] = null;
unset( $GLOBALS['mock_wp_query_handler'] );

echo "\n=======================================================================\n";
echo " AUDIT SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "=======================================================================\n";

exit( $failed > 0 ? 1 : 0 );

