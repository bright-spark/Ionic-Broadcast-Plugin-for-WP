<?php
/** 
Template Name: Media RSS
**/

// Query the Podcast Custom Post Type and fetch the latest 100 posts
$args = array( 'post_type' => 'podcasts', 'posts_per_page' => 100 );
$loop = new WP_Query( $args );

// Output the XML header
header('Content-Type: '.feed_content_type('rss-http').'; charset='.get_option('blog_charset'), true);
echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>';
?>

<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss">
  
  <channel>
    <title>Hypocritical</title>
    <author><?php echo get_bloginfo('name'); ?></author>
    <link><?php echo get_bloginfo('url'); ?></link>
    <language><?php echo get_bloginfo ( 'language' ); ?></language>
    <copyright><?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?></copyright>
    
	<?php $sitedesc = get_theme_mod('idt_site_description');
	if($sitedesc!==false && !empty($sitedesc)){ ?>
    <description><?php echo get_theme_mod('idt_site_description'); ?></description>
	<?php }else{ ?>
	<description><?php echo get_bloginfo('description'); ?></description>
	<?php } ?>
    
    <?php // Start the loop for Podcast posts
    while ( $loop->have_posts() ) : $loop->the_post(); ?>
	<?php $custom = get_post_custom(); ?>
    <item>
      <title><?php the_title_rss(); ?></title>
      <author><?php echo get_bloginfo('name'); ?></author>
	  <description><?php the_excerpt_rss(); ?></description>
      
      <?php // Get the file field URL and filesize
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
			$oggfileurl = wp_get_attachment_url( $oggattachment['id'] );
			$oggfilesize = filesize( get_attached_file( $oggattachment['id'] ) );
		}else{
			$oggfileurl = false;
		}
      ?>
      
      <enclosure url="<?php echo $fileurl; ?>" length="<?php echo $filesize; ?>" type="audio/mpeg" />
      <guid><?php echo $fileurl; ?></guid>
      <pubDate><?php the_time( 'D, d M Y G:i:s T'); ?></pubDate>
	  <media:content
		url="<?php echo $fileurl; ?>"
		fileSize="<?php echo $filesize; ?>"
		type="audio/mpeg" />
	<?php if(!$oggfileurl){ ?>
	  <media:content
		url="<?php echo $oggfileurl; ?>"
		fileSize="<?php echo $oggfilesize; ?>"
		type="audio/ogg" />
	<?php } ?>
	<?php if(has_post_thumbnail( $post->ID ) ): ?>
        <?php $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'itunes-album' ); ?>
	  <media:thumbnail url="<?php echo $image[0]; ?>" />
    <?php endif; ?>
	</item>
    <?php endwhile; ?>
  
  </channel>

</rss>