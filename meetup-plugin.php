<?php
/*
Plugin Name:  STL WordPress Meetup
Plugin URI:   https://developer.wordpress.org/plugins/the-basics/
Description:  An example plugin for the STL WordPress Meetup
Version:      1.0.0
Author:       WordPress.org
Author URI:   https://developer.wordpress.org/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  stl-meetup
Domain Path:  /languages
*/

// Register Post Type on Plugin activation and refresh permalinks
function stl_meetup_setup_post_type() {
    // register the "book" custom post type
    $args = array(
      'public' => true,
      'label'  => 'Books'
    );
    register_post_type( 'book', $args );
}
add_action( 'init', 'stl_meetup_setup_post_type' );

function stl_meetup_install() {
    // trigger our function that registers the custom post type
    stl_meetup_setup_post_type();

    // clear the permalinks after the post type has been registered
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'stl_meetup_install' );



// Refresh permalinks on plugin deactivation when post type is removed
function stl_meetup_deactivation() {
    // unregister the post type, so the rules are no longer in memory
    unregister_post_type( 'book' );
    // clear the permalinks to remove our post type's rules from the database
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'stl_meetup_deactivation' );




// Prepend post tiles with a 🔥 emoji
add_filter( 'the_title', 'stl_meetup_fire_title', 10, 2 );

if ( ! function_exists( 'stl_meetup_fire_title' ) ) {
    function stl_meetup_fire_title( $title, $id ) {

        if ( 'post' === get_post_type($id) ) {
            $title = '🔥' . $title;
        }

        return $title;

    }
}




// Create a shortcode that pulls in WCSTL Pages
add_shortcode( 'wcstl', 'stl_meetup_shortcode' );

// Function to handle shortcode output
function stl_meetup_shortcode( $atts ) {
    // shortcode attributes that can be set when shortcode is used
    $atts = shortcode_atts( array(
        'title' => 'WCSTL Posts',
        'show' => 10,
    ), $atts, 'wcstl' );

    // arguments to pass to our remote request
    $query_args = array(
        'per_page' => $atts['show']
    );

    // Make a request to the WCSTL site rest API
    $wcstl_pages = wp_remote_get(
        'https://2018.stlouis.wordcamp.org/wp-json/wp/v2/pages',
        array(
            'body' => $query_args,
        )
    );

    // get the body (page results) of the response of our request
    $body = json_decode( $wcstl_pages['body'] );

    // Start HTML output of our shortcode
    ob_start(); ?>

    <h2 class="wcstl-title"><?php echo $atts['title']; ?></h2>

    <ul class="wcstl-list">
        <?php foreach ( $body as $page ) : ?>
            <li><a href="<?php echo $page->link; ?>"><?php echo $page->title->rendered; ?></a></li>
        <?php endforeach; ?>
    </ul>

    <?php
    // return cleaned HTML for our shortcode
    return ob_get_clean();

}




// Enqueue plugin specific stylesheets or scripts
function stl_meetup_enqueue_styles() {
    wp_enqueue_style( 'meetup-plugin-styles', plugins_url( '/css/meetup-plugin-styles.css', __FILE__ ) );
}

add_action( 'wp_enqueue_scripts', 'stl_meetup_enqueue_styles' );
