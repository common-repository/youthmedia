<?php
/*
Plugin Name: Youthmedia
Plugin URI: http://blog.youthmedia.eu/
Description: Allows you to integrate the photos from your youthmedia account into your site.
Version: 0.1.4
License: GPL
Author: Youthmedia team
Author URI: http://blog.youthmedia.eu/

This plugins is based of the flickrRSS plugin by Dave Kellam < http://eightface.com >

*/

function get_youthmedia() {

    // define default settings to show something after installation without setup
    $default['platform']  = "www.youthmedia.eu";
    $default['type']      = "recent";
    $default['num_items'] = 10;

	// the function can accept up to seven parameters, otherwise it uses option panel defaults 	
  	for($i = 0 ; $i < func_num_args(); $i++) {
    	$args[] = func_get_arg($i);
    }
    
	// Evaluate parameters given to function, read them from database or load defaults
	// Read: Platform
	if (!isset($args[0])) {
		$platform = get_option('youthmedia_platform');
		if($platform == "") {
			$platform = $default['platform'];
		}
	} else {
		$platform = $args[0];
	}
	
	// Read: User
	if (!isset($args[1])) $user = get_option('youthmedia_username'); else $user = $args[1];
	
	// Read: Type
	if (!isset($args[2])) {
		$type = get_option('youthmedia_display_type');
		if($type == "") {
			$type = $default['type'];
		}
	} else {
		$type = $args[2];
	}
	
	// Read: Num-Items
	if (!isset($args[3])) {
		$num_items = get_option('youthmedia_display_numitems');
		if($num_items == "") {
			$num_items = $default['num_items'];
		}
	} else {
		$num_items = $args[3];
	}
	
	// Read: Before_Image
  	if (!isset($args[4])) $before_image = get_option('youthmedia_before'); else $before_image = $args[4];
  	
  	// Read: After_Image
  	if (!isset($args[5])) $after_image = get_option('youthmedia_after'); else $after_image = $args[5];
  	
  	// Read: Pool number
  	if (!isset($args[6])) $pool = get_option('youthmedia_pool'); else $pool = $args[6];
    
	// use image cache & set location
	$useImageCache = get_option('youthmedia_use_image_cache');
	$cachePath     = get_option('youthmedia_image_cache_uri');
	$fullPath      = get_option('youthmedia_image_cache_dest'); 
	
	// Check if another plugin is using RSS, may not work
	if (!function_exists('MagpieRSS')) {
		include_once (ABSPATH . WPINC . '/rss.php');
		error_reporting(E_ERROR);
	}
	
	// get the feeds
	switch ($type) {
		case "user":
    		$rss_url = "http://".$platform."/rss/recentmedia/user/".$user;
    		break;
		case "friends":
    		$rss_url = "http://".$platform."/rss/recentmedia/friends/".$user;
    		break;
/*
        // NO POOLS (GROUP) IMAGES AVAILABLE AT THE MOMENT!
		case "pool":
    		$rss_url = "http://".$platform."/rss/pools/".$pool;
    		break;
*/
		case "recent":
    		$rss_url = "http://".$platform."/rss/recentmedia/Photo";
    		break;
    	default:
    		print "Youthmedia probably needs to be setup!<br /><br />";    	
	}

	# get rss file
	$rss = @ fetch_rss($rss_url);
	
	// if we have the real data, print 	
	if (is_array($rss->items)) {
    	$imgurl = "";
    	# specifies number of pictures
		$items = array_slice($rss->items, 0, $num_items);
		
	    # builds html from array
    	foreach ( $items as $item ) {
           # check if there is an image title (for html validation purposes)
           if($item['title'] !== "") {
           		$title = htmlspecialchars(stripslashes($item['title']));
           } else {
           		$title = "Photo of ".$platform;
           }     
           
           $url    = $item['link'];
		   $imgurl = str_img_src($item['description']);
		   
		   // Grab image name from url
		   $imgnameparts = explode("/", $imgurl);
		   $imgnameparts = explode("?", $imgnameparts[sizeof($imgnameparts)-1]);
		   $imgname = $imgnameparts[0];
	   
		   // Generate URLs
		   $imgpathweb = $cachePath.$imgname;
		   $imgpathlocal = $fullPath.$imgname;

	       # cache images 
	       if ($useImageCache) {
               # check if file already exists in cache
               # if not, grab a copy of it
               if (!file_exists($imgpathlocal)) {
               	 // check for CURL, if not use fopen
                 if (false && function_exists('curl_init') ) {
                    $curl = curl_init();
                    $localimage = fopen($imgpathlocal, "wb");
                    curl_setopt($curl, CURLOPT_URL, $imgurl);
                    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
                    curl_setopt($curl, CURLOPT_FILE, $localimage);
                    curl_exec($curl);
                    curl_close($curl);
                 } else {
                 	$filedata = "";
                    $remoteimage = fopen($imgurl, 'rb');
                  	if ($remoteimage) {
                    	 while(!feof($remoteimage)) {
                         	$filedata.= fread($remoteimage,1024*8);
                       	 }
                 	}
                	fclose($remoteimage);
                	$localimage = fopen($imgpathlocal, 'wb');
                	fwrite($localimage,$filedata);
                	fclose($localimage);
                 }
                }
                # use cached image
                print $before_image . "<a href=\"$url\" title=\"$title\"><img src=\"$imgpathweb\" alt=\"$title\" /></a>" . $after_image;
            } else {
                # grab image direct from youthphotos
                print $before_image . "<a href=\"$url\" title=\"$title\"><img src=\"$imgurl\" alt=\"$title\" /></a>" . $after_image;      
            } // end use imageCache
     	} // end foreach
  } else {
  	print "Can't reach RSS feed at Youthmedia!<br /><br />";
  }
} # end get_youthphotosRSS() function


function widget_youthmedia_init() {
	if (!function_exists('register_sidebar_widget')) return;

	function widget_youthmedia($args) {
		extract($args);
		$options = get_option('widget_youthmedia');
		$title = $options['title'];
		$before_images = $options['before_images'];
		$after_images = $options['after_images'];
		echo $before_widget . $before_title . $title . $after_title . $before_images;
		get_youthmedia();
		echo $after_images . $after_widget;
	}

	function widget_youthmedia_control() {
		$options = get_option('widget_youthmedia');

		if ( $_POST['youthmedia-submit'] ) {
			$options['title'] = strip_tags(stripslashes($_POST['youthmedia-title']));
			$options['before_images'] = stripslashes($_POST['youthmedia-beforeimages']);
			$options['after_images'] = stripslashes($_POST['youthmedia-afterimages']);
			update_option('widget_youthmedia', $options);
		}

		$title         = htmlspecialchars($options['title'], ENT_QUOTES);
		$before_images = htmlspecialchars($options['before_images'], ENT_QUOTES);
		$after_images  = htmlspecialchars($options['after_images'], ENT_QUOTES);
		
		echo '<p style="text-align:right;"><label for="youthmedia-title">Title: <input style="width: 180px;" id="youthmedia-title" name="youthmedia-title" type="text" value="'.$title.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="youthmedia-beforeimages">Before all images: <input style="width: 180px;" id="youthmedia-beforeimages" name="youthmedia-beforeimages" type="text" value="'.$before_images.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="youthmedia-afterimages">After all images: <input style="width: 180px;" id="youthmedia-afterimages" name="youthmedia-afterimages" type="text" value="'.$after_images.'" /></label></p>';
		echo '<input type="hidden" id="youthmedia-submit" name="youthmedia-submit" value="1" />';
	}		

	register_sidebar_widget('youthmedia', 'widget_youthmedia');
	register_widget_control('youthmedia', 'widget_youthmedia_control', 300, 100);
}

function youthmedia_subpanel() {
     if (isset($_POST['save_youthmedia_settings'])) {
     		$option_youthmedia_platform     = $_POST['youthmedia_platform'];
     		$option_youthmedia_username     = $_POST['youthmedia_username'];
       		$option_youthmedia_pool         = $_POST['youthmedia_pool'];
       		$option_display_type            = $_POST['display_type'];
       		$option_display_numitems        = $_POST['display_numitems'];
       		$option_before                  = $_POST['before_image'];
       		$option_after                   = $_POST['after_image'];
       		$option_useimagecache           = $_POST['use_image_cache'];
       		$option_imagecacheuri           = $_POST['image_cache_uri'];
       		$option_imagecachedest          = $_POST['image_cache_dest'];
       		update_option('youthmedia_platform', $option_youthmedia_platform);
       		update_option('youthmedia_username', $option_youthmedia_username);
       		update_option('youthmedia_pool', $option_youthmedia_pool);
       		update_option('youthmedia_display_type', $option_display_type);
       		update_option('youthmedia_display_numitems', $option_display_numitems);
       		update_option('youthmedia_before', $option_before);
       		update_option('youthmedia_after', $option_after);
       		update_option('youthmedia_use_image_cache', $option_useimagecache);
       		update_option('youthmedia_image_cache_uri', $option_imagecacheuri);
       		update_option('youthmedia_image_cache_dest', $option_imagecachedest); ?>
       		<div class="updated"><p>Your Youthmedia settings were saved</p></div>
<?php } ?>

	<div class="wrap">
		<h2>Youthmedia settings</h2>
		
		<h3>General Settings</h3>
		<form method="post">
		<table class="form-table">
		 <tr valign="top">
		  <th scope="row">Your Platform</th>
	      <td>
	      	<?php $youthmedia_platform = get_option('youthmedia_platform');
	      		if($youthmedia_platform == "") {
	      			$youthmedia_platform = "www.youthmedia.eu";
	      		} ?>
        	<select name="youthmedia_platform" id="youthmedia_platform">
        	  <option <?php if($youthmedia_platform == 'www.youthmedia.eu') { echo 'selected'; } ?> value="www.youthmedia.eu">youthmedia.eu</option>
        	  <option <?php if($youthmedia_platform == 'www.jugendfotos.de') { echo 'selected'; } ?> value="www.jugendfotos.de">jugendfotos.de</option>
        	  <option <?php if($youthmedia_platform == 'www.jugendmedien.de') { echo 'selected'; } ?> value="www.jugendmedien.de">jugendmedien.de</option>
        	  <option <?php if($youthmedia_platform == 'www.jugendfotos.at') { echo 'selected'; } ?> value="www.jugendfotos.at">jugendfotos.at</option>
        	  <option <?php if($youthmedia_platform == 'www.ungbild.se') { echo 'selected'; } ?> value="www.ungbild.se">ungbild.se</option>
		    </select>
			Your current home is at <a href="http://<?php echo $youthmedia_platform; ?>/" target="_blank">http://<?php echo $youthmedia_platform; ?>/</a>
	       </td>
         </tr>
		 <tr valign="top">
		  <th scope="row">Your Youthmedia-login</th>
	      <td>
	      	<?php $youthmedia_username = get_option('youthmedia_username'); ?>
	      	<input name="youthmedia_username" type="text" id="youthmedia_username" value="<?php echo $youthmedia_username; ?>" size="10" />
	      	<?php if($youthmedia_username == "") { ?>
        		Your login at Youthmedia.
			<?php } else { ?>
				This should be your userpage: <a href="http://<?php echo $youthmedia_platform; ?>/people/<?php echo $youthmedia_username; ?>" target="_blank">http://<?php echo $youthmedia_platform; ?>/people/<?php echo $youthmedia_username; ?></a>
			<?php } ?>
          </td>
         </tr>
         <tr valign="top">
          <th scope="row">What pictures to show?</th>
          <td>
        	<select name="display_type" id="display_type">
        	  <option <?php if(get_option('youthmedia_display_type') == 'user') { echo 'selected'; } ?> value="user">my pictures</option>
        	  <option <?php if(get_option('youthmedia_display_type') == 'friends') { echo 'selected'; } ?> value="friends">pictures of my friends</option>
        	  <!--<option <?php if(get_option('youthmedia_display_type') == 'pool') { echo 'selected'; } ?> value="pool">pictures of a contest</option>-->
        	  <option <?php if(get_option('youthmedia_display_type') == 'recent') { echo 'selected'; } ?> value="recent">recent pictures</option>
		    </select>
           </td> 
         </tr>
         <tr valign="top">
          <th scope="row">Amount of items to display</th>
          <td>
        	<select name="display_numitems" id="display_numitems">
		      <option <?php if(get_option('youthmedia_display_numitems') == '1') { echo 'selected'; } ?> value="1">1</option>
		      <option <?php if(get_option('youthmedia_display_numitems') == '2') { echo 'selected'; } ?> value="2">2</option>
		      <option <?php if(get_option('youthmedia_display_numitems') == '3') { echo 'selected'; } ?> value="3">3</option>
		      <option <?php if(get_option('youthmedia_display_numitems') == '4') { echo 'selected'; } ?> value="4">4</option>
		      <option <?php if(get_option('youthmedia_display_numitems') == '5') { echo 'selected'; } ?> value="5">5</option>
		      <option <?php if(get_option('youthmedia_display_numitems') == '6') { echo 'selected'; } ?> value="6">6</option>
		      <option <?php if(get_option('youthmedia_display_numitems') == '7') { echo 'selected'; } ?> value="7">7</option>
		      <option <?php if(get_option('youthmedia_display_numitems') == '8') { echo 'selected'; } ?> value="8">8</option>
		      <option <?php if(get_option('youthmedia_display_numitems') == '9') { echo 'selected'; } ?> value="9">9</option>
		      <option <?php if(get_option('youthmedia_display_numitems') == '10') { echo 'selected'; } ?> value="10">10</option>
		    </select>
           </td> 
         </tr>
<!--
         <tr valign="top">
		  <th scope="row">Contest</th>
          <td>
          	 <?php $youthmedia_pool = get_option('youthmedia_pool'); ?>
	  	     	<input name="youthmedia_pool" type="text" id="youthmedia_pool" value="<?php echo $youthmedia_pool; ?>" size="10" />
	      	 <?php if($youthmedia_pool == "") { ?>
        		It's the number in the URL of the contest, like http://<?php echo $youthmedia_platform; ?>/pools/show/ID
			 <?php } else { ?>
				This should be the page of the contest: <a href="http://<?php echo $youthmedia_platform; ?>/pools/show/<?php echo $youthmedia_pool; ?>" target="_blank">http://<?php echo $youthmedia_platform; ?>/pools/show/<?php echo $youthmedia_pool; ?></a>
			<?php } ?>
          </td>
         </tr>
-->
         <tr valign="top">
          <th scope="row">HTML Wrapper</th>
          <td>
          	<label for="before_image">Before each image:</label> <input name="before_image" type="text" id="before_image" value="<?php echo htmlspecialchars(stripslashes(get_option('youthmedia_before'))); ?>" size="10" />
        	<label for="after_image">After each image:</label> <input name="after_image" type="text" id="after_image" value="<?php echo htmlspecialchars(stripslashes(get_option('youthmedia_after'))); ?>" size="10" />
          </td>
         </tr>
         </table>      

        <h3>Cache Settings</h3>
		<p>
			This allows you to store the images on your server and reduce the load on Youthmedia.<br />
			<em>Make sure the plugin works without the cache enabled first.
				<ul>
					<li>Create the directory 'youthmedia' under /wp-content</li>
					<li>Create a directory 'cache' inside /wp-content/youthmedia</li>
					<li>Make the directory 'cache' writable by the webserver</li>
					<li>Input the paths correctly, with an '/' at the end</li>
				</ul>
			If you are not sure what you are doing, leave it untouched ;-)
			</em>
		</p>
		<table class="form-table">
         <tr valign="top">
          <th scope="row">URL</th>
          <td>
          	<input name="image_cache_uri" type="text" id="image_cache_uri" value="<?php echo get_option('youthmedia_image_cache_uri'); ?>" size="50" /><br />
          	<em>f.e. /wp-content/youthmedia/cache/</em>
          </td>
         </tr>
         <tr valign="top">
          <th scope="row">Full Path</th>
          	<td>
          		<input name="image_cache_dest" type="text" id="image_cache_dest" value="<?php echo get_option('youthmedia_image_cache_dest'); ?>" size="50" /> <br />
          		<em>f.e. /home/path/to/wp-content/youthmedia/cache/</em>
          	</td>
         </tr>
		 <tr valign="top">
		  <th scope="row" colspan="2" class="th-full">
		  		<input name="use_image_cache" type="checkbox" id="use_image_cache" value="true" <?php if(get_option('youthmedia_use_image_cache') == 'true') { echo 'checked="checked"'; } ?> />  
		  		<label for="use_image_cache">Enable the image cache</label>
		  </th>
		 </tr>
        </table>
        <div class="submit">
           <input type="submit" name="save_youthmedia_settings" value="<?php _e('Save Settings', 'save_youthmedia_settings') ?>" />
        </div>
        </form>
    </div>

<?php } // end youthmedia_subpanel()

function youthmedia_admin_menu() {
   if (function_exists('add_options_page')) {
        add_options_page('Youthmedia Settings', 'Youthmedia', 8, basename(__FILE__), 'youthmedia_subpanel');
   }
}

// Add default CSS to plugin
function youthmedia_add_css() {
	echo "<link rel=\"stylesheet\" href=\"".get_settings('home')."/wp-content/plugins/youthmedia/youthmedia.css\" type=\"text/css\" media=\"screen\" />\n";
}

// Returns the image tag from a given string
function str_img_src($html) {
	if (stripos($html, '<img') !== false) {
        $imgsrc_regex = '#<\s*img [^\>]*src\s*=\s*(["\'])(.*?)\1#im';
        preg_match($imgsrc_regex, $html, $matches);
        unset($imgsrc_regex);
        unset($html);
        if (is_array($matches) && !empty($matches)) {
            return $matches[2];
        } else {
            return false;
        }
    } else {
        return false;
    }
}

add_action('wp_head', 'youthmedia_add_css');
add_action('admin_menu', 'youthmedia_admin_menu'); 
add_action('plugins_loaded', 'widget_youthmedia_init');

?>