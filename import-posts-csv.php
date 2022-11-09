<?php
/**
 * Plugin Name:       Import Posts CSV
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       immport posts by CSV
 * Version:           1.10.3
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Marmik Patel
 * Author URI:        https://aipxperts.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       my-basics-plugin
 * Domain Path:       /languages
 */


/*---------------------------------------------------
add settings page to menu
----------------------------------------------------*/
function add_rstuff_import_page() {
add_submenu_page( 'tools.php', 'Import CSV Custom', 'Import CSV Custom', 'manage_options', 'import-rstuff', 'import_rstuff' );
}
add_action( 'admin_menu', 'add_rstuff_import_page' );
  
function import_rstuff() {
?>
  
<div class="wrap import-csv">
    <h2>Import CSV Custom</h2>
    <p>Click Process button to do whatever is below in your run process.</p>
<?php
    if ( isset( $_POST['_wpnonce-is-iu-import-users-users-page_import_cn'] ) ) {
    check_admin_referer( 'is-iu-import-users-users-page_import_cn', '_wpnonce-is-iu-import-users-users-page_import_cn' );
         
        ini_set("auto_detect_line_endings", true);
        //start processing the CSV
        if (!empty($_FILES['users_csv_cn']['name'])) {
            // Setup settings variables
            $filename = $_FILES['users_csv_cn']['tmp_name'];
            $file_handle = fopen($filename,"r");
            $i=0;
            $imported = 0;
            $failedusers = array();
            $successusers = array();
            while (!feof($file_handle) ) {
                $block = 0;
                $check = 0;
                $line_of_text = fgetcsv($file_handle, 1024);
                // Let's make sure fields have data and it is not line 1
                if(!empty($line_of_text[0])) {
                    $i++;
                    if($i > 1 && $i < 302) {
                        
                        //start new import
                         $postContent = $line_of_text[2];
 						 $doc = new DOMDocument();
						 $doc->loadHTML($postContent);    
						 $selector = new DOMXPath($doc);
						 $post_title = $line_of_text[1];
						 $post_tags = $line_of_text[8];
						 $post_cat = $line_of_text[9];
						 $post_slug = $line_of_text[10];

						 $meta_desc = $line_of_text[5];
						 $meta_keywords = $line_of_text[6];
						 $meta_title = $line_of_text[7];


						//$result = $selector->query('//img');
						$image_list = $selector->query("//img[@src]");

						for($i=0;$i<$image_list->length; $i++){
							
							$img_src[$i] = $image_list->item($i)->getAttribute("src");
							$new_url[$i] = 'https://throttledownkustoms.com/'.$img_src[$i];

							require_once( ABSPATH . "/wp-load.php");
							require_once( ABSPATH . "/wp-admin/includes/image.php");
							require_once( ABSPATH . "/wp-admin/includes/file.php");
							require_once( ABSPATH . "/wp-admin/includes/media.php");
							
							// Download url to a temp file
							$tmp = download_url( $new_url[$i] );
							if ( is_wp_error( $tmp ) ) return false;
							
							// Get the filename and extension ("photo.png" => "photo", "png")
							$filename = pathinfo($new_url[$i], PATHINFO_FILENAME);
							$extension = pathinfo($new_url[$i], PATHINFO_EXTENSION);
							
							// An extension is required or else WordPress will reject the upload
							if ( ! $extension ) {
								// Look up mime type, example: "/photo.png" -> "image/png"
								$mime = mime_content_type( $tmp );
								$mime = is_string($mime) ? sanitize_mime_type( $mime ) : false;
								
								// Only allow certain mime types because mime types do not always end in a valid extension (see the .doc example below)
								$mime_extensions = array(
									// mime_type         => extension (no period)
									'text/plain'         => 'txt',
									'text/csv'           => 'csv',
									'application/msword' => 'doc',
									'image/jpg'          => 'jpg',
									'image/jpeg'         => 'jpeg',
									'image/gif'          => 'gif',
									'image/png'          => 'png',
									'video/mp4'          => 'mp4',
								);
								
								if ( isset( $mime_extensions[$mime] ) ) {
									// Use the mapped extension
									$extension = $mime_extensions[$mime];
								}else{
									// Could not identify extension
									@unlink($tmp);
									return false;
								}
							}
							
							
							
							// Upload by "sideloading": "the same way as an uploaded file is handled by media_handle_upload"
							$args = array(
								'name' => "$filename.$extension",
								'tmp_name' => $tmp,
							);
							
							// Do the upload
							$attachment_id[$i] = media_handle_sideload( $args, 0, $title);
							
							// Cleanup temp file
							@unlink($tmp);
							
							// Error uploading
							if ( is_wp_error($attachment_id[$i]) ) return false;
						    $wp_url[$i] = wp_get_attachment_image_url($attachment_id[$i], 'full');

						}
						$post_content  = str_replace($img_src,$wp_url,$postContent);
						//Finding category by name & geting id
						if($term = get_term_by( 'name', $post_cat, 'category' ) ){
						 $cat_id= $term->term_id;
						 }else{
						     //creating category for not exists category
						   $terms= wp_insert_term($post_cat, // the term 
						  'category', // the taxonomy
						   array(
						    'slug' => $post_cat
						  )); 
						//geting new category id
						   $cat_id= $terms['term_id'];  
						  }
						   //post start
						$inser_post = array(
						  'post_type'   => 'post',	
						  'post_title'    => wp_strip_all_tags($post_title ),
						  'post_content'  => $post_content,
						  'post_status'   => 'publish',
						  'post_author'   => 1,
						  'post_category' => array($cat_id)
						);
						if (get_page_by_title($post_title, OBJECT, 'post')) {
						 	
						 }else{
						 	global $post;
						 	$post = wp_insert_post( $inser_post );
						 	update_post_meta($post,'_yoast_wpseo_title',$meta_title,true);
						 	update_post_meta($post,'_yoast_wpseo_metadesc',$meta_desc,true);

						 	wp_set_post_tags( $post, $post_tags);

						 }	
						$imported++;
                    }
                }
            }
        fclose($file_handle);
        echo 'Updated ' . $imported . ' terms';
        } else {
            echo 'Fail';
        }
    }
    ?>
  
    <form method="post" action="" enctype="multipart/form-data">
        <input type="file" id="users_csv" name="users_csv_cn" value="" class="all-options" />
        <?php wp_nonce_field( 'is-iu-import-users-users-page_import_cn', '_wpnonce-is-iu-import-users-users-page_import_cn' ); ?>
        <p class="submit">
            <input type="submit" class="button-primary" value="Process Posts" />
        </p>
    </form>
<?php } ?>