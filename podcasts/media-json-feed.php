<?php
/** 
Template Name: IDP Media JSON
**/

// Query the Podcast Custom Post Type and fetch the latest 100 posts
$args = array( 'post_type' => 'podcasts', 'posts_per_page' => 100 );
$loop = new WP_Query( $args );
$cb = $_GET['callback'];

// Output the XML header
//header('Content-Type: '.feed_content_type('rss-http').'; charset='.get_option('blog_charset'), true);
//echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>';
?>
<?php echo $cb; ?>({
	"title":"Hypocritical",
	"copyright":"<?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?>",
	"author":{
		"name":"<?php echo get_bloginfo('name'); ?>",
		"url":"<?php echo get_bloginfo('url'); ?>"
	},
	"feed":[
<?php // Start the loop for Podcast posts
	$postcount = 0;
    while ( $loop->have_posts() ) : $loop->the_post();
		$custom = get_post_custom();
		// Get the file field URL and filesize
		$mp3attachment = get_field('mp3_file');
		if(!$mp3attachment){
			$fileurl = $custom['audiourl'][0];
			$filesize = $custom['bytesize'][0];
		}else{
			$fileurl = wp_get_attachment_url( $mp3attachment['id'] );
			$filesize = filesize( get_attached_file( $mp3attachment['id'] ) );
		}
		$oggattachment = get_field('ogg_file');
		if(!$oggattachment){
			$oggfileurl = false;
		}else{
			$oggfileurl = wp_get_attachment_url( $oggattachment['id'] );
			$oggfilesize = filesize( get_attached_file( $oggattachment['id'] ) );
		}
	?>
	{
		"title":"<?php the_title_rss(); ?>",
		"duration":"<?php echo $custom['audioduration'][0]; ?>",
		"published":"<?php the_time( 'D, d M Y G:i:s T'); ?>",
		"aired":"<?php echo $custom['airdate'][0]; ?>",
		"oggavailable":<?php if($oggfileurl!==false){ echo "true"; }else{ echo "false"; } ?>,
		"mp3":{
			"filesize":"<?php echo $filesize; ?>",
			"url":"<?php echo $fileurl; ?>"
		},
		"ogg":{
	<?php if($oggfileurl!==false){ ?>
			"url":"<?php echo $oggfileurl; ?>",
			"filesize":"<?php echo $oggfilesize; ?>"
	<?php } ?>
		},
	<?php if(has_post_thumbnail( $post->ID ) ): ?>
        <?php $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'itunes-album' ); ?>
		"albumart":"<?php echo $image[0]; ?>"
    <?php endif; ?>
	}<?php if(++$postcount!=$loop->found_posts && $postcount<100) echo ", "; ?>
    <?php endwhile; ?>
	]
});