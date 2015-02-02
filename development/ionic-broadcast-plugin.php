<?php
/**
Plugin Name: Ionic Broadcast Plugin
Description: Create media and iTunes compatible RSS feeds.
Version: 1.2
**/

add_image_size( 'itunes-album', 2048, 2048, true );

add_action('init', 'podcast_rss');
function podcast_rss(){
  add_feed('hypocritical', 'ionic_podcast_rss');
}
add_action('init', 'media_podcast_rss');
function media_podcast_rss(){
  add_feed('hypocriticalmedia', 'ionic_media_podcast_rss');
}
add_action('init', 'media_podcast_json');
function media_podcast_json(){
  add_feed('hypocriticaljson', 'ionic_media_podcast_json');
}

function ionic_podcast_rss(){
  require_once( dirname( __FILE__ ) . '/podcasts/podcast-rss-template.php' );
}
function ionic_media_podcast_rss(){
  require_once( dirname( __FILE__ ) . '/podcasts/media-rss-template.php' );
}
function ionic_media_podcast_json(){
  require_once( dirname( __FILE__ ) . '/podcasts/media-json-feed.php' );
}

function ionic_podcasts_cpt() {

  $labels = array(
    'name'                => _x( 'Podcasts', 'Podcast General Name', 'text_domain' ),
    'singular_name'       => _x( 'Podcast', 'Course Singular Name', 'text_domain' ),
    'menu_name'           => __( 'Podcasts', 'text_domain' ),
    'parent_item_colon'   => __( 'Parent Podcast:', 'text_domain' ),
    'all_items'           => __( 'All Podcasts', 'text_domain' ),
    'view_item'           => __( 'View Podcast', 'text_domain' ),
    'add_new_item'        => __( 'Add New Podcast', 'text_domain' ),
    'add_new'             => __( 'Add New', 'text_domain' ),
    'edit_item'           => __( 'Edit Podcast', 'text_domain' ),
    'update_item'         => __( 'Update Podcast', 'text_domain' ),
    'search_items'        => __( 'Search Podcasts', 'text_domain' ),
    'not_found'           => __( 'Not found', 'text_domain' ),
    'not_found_in_trash'  => __( 'Not found in Trash', 'text_domain' ),
  );
  $args = array(
    'label'               => __( 'podcasts', 'text_domain' ),
    'description'         => __( 'Podcast Description', 'text_domain' ),
    'labels'              => $labels,
    'supports'            => array( 'title', 'editor', 'thumbnail' ),
    'taxonomies'          => array( 'category', 'post_tag' ),
    'hierarchical'        => false,
    'public'              => false,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'show_in_nav_menus'   => true,
    'show_in_admin_bar'   => true,
    'menu_position'       => 5,
    'menu_icon'           => 'dashicons-format-audio',
    'can_export'          => true,
    'has_archive'         => false,
    'exclude_from_search' => true,
    'publicly_queryable'  => false,
    'capability_type'     => 'page'
  );
  register_post_type( 'podcasts', $args );
}

add_action( 'init', 'ionic_podcasts_cpt', 0 );

//SHORTCODE----------------------------------------------
add_action('init', 'register_idp_resources');
add_action('wp_footer', 'print_idp_resources');

function register_idp_resources() {
	wp_register_script('idpPlayer-script', plugins_url('player/idpPlayer.js', __FILE__), array('jquery'), '1.0', true);
	wp_register_style('idpPlayer-style', plugins_url('player/idpPlayer.css', __FILE__));
}

function print_idp_resources() {
	global $add_my_script;

	if ( ! $add_my_script )
		return;

	wp_print_scripts('idpPlayer-script');
	wp_print_styles('idpPlayer-style');
}

add_shortcode( 'ionic_podcasts_player' , 'ionic_podcasts_player_embed' );

function ionic_podcasts_player_embed( $atts, $content ){
	//Set defaults for attributes
	extract( shortcode_atts( array(
		'episode' => 0,
	), $atts, 'ionic_podcast_player' ) );
	
	global $add_my_script;
	$add_my_script = true;
	
	// The Query
	$the_query = new WP_Query( array(
		'post_type' => 'podcasts',
		'meta_key' => 'episodenum',
		'meta_value' => $episode )
	);

	// The Loop
	if ( $the_query->have_posts() ) {
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			$mp3attachment = get_field('mp3_file');
			$oggattachment = get_field('ogg_file');
			if(!$mp3attachment){
				$custom = get_post_custom();
				$fileurl = $custom['audiourl'][0];
			}else{
				$fileurl = $mp3attachment['url'];
			}
			$embed .= "<div class=\"idp_player\"";
			$embed .= " data-live_source=\"http://stream.hypocriticreviews.com:8000/hypocritical\"";
			$embed .= " data-track=\"".get_the_title()."\"";
			$embed .= " data-artist=\"".get_bloginfo('name')."\"";
			$embed .= " data-album=\"Hypocritical\"";
			$embed .= " data-duration=\"".$custom['duration']."\"";
			$embed .= " data-source=\"".$fileurl."\"";
			$embed .= " data-playlist_source=\"http://hypocriticreviews.com/feed/hypocriticaljson\"";
			if(has_post_thumbnail( $post->ID )){
				$embed .= " data-album_art=\"".wp_get_attachment_image_src(get_post_thumbnail_id( $post->ID ),'large')[0]."\"";
			}
			if($oggattachment){
				$oggurl = $oggattachment['url'];
				$embed .= " data-ogg_source=\"".$oggurl."\"";
			}
			$embed .= ">";
			$embed .= "<p>Sorry, you might need a newer browser.</p>";
			$embed .= "</div>\n";
		}
	} else {
		// no posts found
		$embed = "<p>Sorry! Episode ".$episode." is not yet available.</p>";
	}
	/* Restore original Post Data */
	wp_reset_postdata();
	
	return $embed;
}

//ADD META TO CPT--------------------------------------
add_action("admin_init", "ionic_podcasts_add_meta");  

function ionic_podcasts_add_meta(){  
    add_meta_box("ionic-podcasts-meta", "More Information", "ionic_podcasts_meta_options", "podcasts", "normal", "high");   
}  

//Create area for extra fields
function ionic_podcasts_meta_options(){  
        global $post; 
		$tz = date_default_timezone_get(); // get current PHP timezone
		date_default_timezone_set ( 'America/New_York' );
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
        
        $custom = get_post_custom($post->ID);
		$subtitle = $custom["subtitle"][0];
		$episodenum = $custom["episodenum"][0];
		$audioduration = $custom["audioduration"][0];
		$audiourl = $custom["audiourl"][0];
		$airdate = $custom["airdate"][0];
		$bytesize = $custom["bytesize"][0];
?>  
<style type="text/css">
.ionic_podcasts_manager_extras div{
margin: 10px;
}

.ionic_podcasts_manager_extras div label{
width: 100px;
float: left;
}
</style>
<div class="ionic_podcasts_manager_extras">
	<div><label>Subtitle:</label><input name="subtitle" value="<?php echo $subtitle; ?>" placeholder="iTunes only" /></div>
	<div><label>Episode #:</label><input name="episodenum" value="<?php echo $episodenum; ?>" placeholder="Required" required /></div>
	<div><label>Duration:</label><input name="audioduration" value="<?php echo $audioduration; ?>" placeholder="HH:MM:SS" required /></div>
	<div><label>Air Date:</label><input name="airdate" value="<?php echo date('g:iA M d\, Y', $airdate) ?>" /></div>
	<div><label>Audio URL:</label><input name="audiourl" value="<?php echo $audiourl; ?>" placeholder="Optional"/></div>
	<div><label>Size (Bytes):</label><input name="bytesize" value="<?php echo $bytesize; ?>" placeholder="Required for Audio URL" /></div>
	<input type="hidden" name="prevent_delete_meta_movetotrash" id="prevent_delete_meta_movetotrash" value="<?php echo wp_create_nonce(plugin_basename(__FILE__).$post->ID); ?>" />
</div>
<?php date_default_timezone_set($tz); // set the PHP timezone back the way it was
    }
add_action('save_post', 'ionic_podcasts_save_extras'); 
  
function ionic_podcasts_save_extras(){  
    global $post;  
    $tz = date_default_timezone_get(); // get current PHP timezone
	date_default_timezone_set ( 'America/New_York' );
	if (!wp_verify_nonce($_POST['prevent_delete_meta_movetotrash'], plugin_basename(__FILE__).$post->ID)) { return $post_id; } //fix delete-custom-meta-on-trash bug
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ){ //if you remove this the sky will fall on your head.
		return $post_id;
	}else{
		update_post_meta($post->ID, "subtitle", $_POST["subtitle"]);
		update_post_meta($post->ID, "episodenum", $_POST["episodenum"]);
		update_post_meta($post->ID, "audioduration", $_POST["audioduration"]);
		update_post_meta($post->ID, "audiourl", $_POST["audiourl"]);
		update_post_meta($post->ID, "airdate", strtotime($_POST["airdate"]));
		update_post_meta($post->ID, "bytesize", $_POST["bytesize"]);
    }
	date_default_timezone_set($tz); // set the PHP timezone back the way it was
}  