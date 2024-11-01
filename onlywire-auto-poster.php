<?php
/**
 Plugin Name: WP OnlyWire Auto Poster
 Plugin URI: http://www.tankado.com/onlywire-auto-poster-wordpress-eklentisi/
 Version: 3.1.1
 Description: Autosubmits a excerpt of a posts to Onlywire when the post published
 Author: Özgür Koca
 Author URI: http://www.tankado.com/
*/
/*  Copyright 2007 Ozgur Koca  (email : ozgur.koca@linux.org.tr)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
include_once(dirname(__FILE__).'/lib.php');

global $onlywire_table_name;
global $onlywire_logs_table_name;
global $wpdb;

$onlywire_table_name  = $wpdb->prefix.'onlywire_auto_poster';
$onlywire_logs_table_name  = $wpdb->prefix.'onlywire_auto_poster_logs';

function owap_log_success_code($post_ID, $post_title, $success_code) 
{
	global $wpdb;
	global $onlywire_logs_table_name;
	$success_code = trim(strip_tags($success_code));
    $sql = 'INSERT INTO ' . $onlywire_logs_table_name . ' SET ';
	$sql .= 'postid = '. $post_ID .', ';
	$sql .= 'post_title = "'. $post_title .'", ';
	$sql .= 'post_date = '. time() .', ';
	$sql .= 'success_code = "'. $success_code .'"';
    $wpdb->query( $sql );
}

function owap_onlywirePost($post_ID) 
{
	global $onlywire_table_name, $wpdb;
	$username = get_option('onlywire_username');
    $password = get_option('onlywire_password');
	
    $post = get_post($post_ID);
    $permalink = get_permalink($post_ID);
	
	// OW hesap bilgileri panele girilmişmi
	if (empty($username) || empty($password))
	{
		owap_log_success_code($post_ID, $post->post_title, 'Onlywire account not defined.');
		return;
	}	
    
    $tags_ = get_the_tags($post_ID);
    if (!empty($tags_))
	foreach( $tags_ as $tag_arr )
		$tags .= $tag_arr->name . ', ';
	
	$categories = get_the_category($post_ID); 
	if (!empty($categories))
	foreach($categories as $category) 
		$cats .= $category->cat_name . ', ';
	
	$comments .= 'Article by '.get_the_author_meta('first_name', $post->post_author). ' '. get_the_author_meta('last_name',$post->post_author);
	$comments .= ' at ' .$post->post_date_gmt . "\n";
	$comments .= ' Categorized in ' . $cats . "\n\n";
	$comments .= $post->post_excerpt;
	$comments = trim($comments);
    $url="http://$username:$password@www.onlywire.com/api/add?url=".urlencode($permalink)."&title=".urlencode($post->post_title)."&tags=".urlencode($tags)."&comments=".urlencode($comments);
    
    $sql = 'SELECT return_code FROM '.$onlywire_table_name.' WHERE postid = "'.$post_ID.'" LIMIT 1';
    $ret_code = $wpdb->get_var( $sql);

	// Guncellemeler de gonderilsin mi
	$submit_updates = get_option('submit_updates');	
    
	// Entry daha once gonderilmemis ise gonder
    if(($ret_code === NULL) || ($ret_code === '0') || $submit_updates) 
    {
		// Onlywire'a gonderiliyor
    	$success_code = owap_get_file($url, $http_response);
		
		// 401 kimlik doğrulama hatası
		if ( strpos($http_response, "401") )
		$success_code = 'HTTP/1.1 401 Authorization failed.';
				
    	owap_log_success_code($post_ID, $post->post_title, $success_code);
    	
    	if ($ret_code === NULL) 
    	{
	    	$success_code = (strpos($success_code, 'success') !== false) ? 1 : 0;
	    	$sql = 'INSERT INTO ' . $onlywire_table_name . ' SET ';
	    	$sql .= 'postid = '. $post_ID .', ';
	    	$sql .= 'post_title = "'. $post->post_title .'", ';
	    	$sql .= 'tags = "'. $tags .'", ';
	    	$sql .= 'comment = "'. $comments .'", ';
	    	$sql .= 'post_date = '. time() .', ';
	    	$sql .= 'return_code = "'. $success_code .'"';
	        $wpdb->query( $sql );
        }
		
    	if ($ret_code === '0') 
    	{
	    	$success_code = (strpos($success_code, 'success') !== false) ? 1 : 0;
	    	$sql = 'UPDATE ' . $onlywire_table_name . ' SET ';;
	    	$sql .= 'post_title = "'. $post->post_title .'", ';
	    	$sql .= 'tags = "'. $tags .'", ';
	    	$sql .= 'comment = "'. $comments .'", ';
	    	$sql .= 'post_date = '. time() .', ';
	    	$sql .= 'return_code = "'. $success_code .'"';
	    	$sql .= ' where postid = '.$post_ID;
	        $wpdb->query( $sql ); 
        }
    } 
    else 
    {
    	owap_log_success_code($post_ID, $post->post_title, "Didn't posted because the URL already successfully posted.");
    }
    return $post_ID;
}

// İçerik yayınlandığında OnlyWire'a bildir
add_action('publish_post', 'owap_onlywirePost');

// Yönetim sayfası seçeneği
function tank_add_pages_onlywire() {
    add_options_page('OnlyWire Auto Poster', 'OnlyWire Auto Poster', 8, 'onlywireautopostertoptions', 'tank_options_page_onlywire');
}

// Eklenti yüklenirken yapılacak işlemler
function tank_install_onlywireautoposter() {
    global $wpdb;
    global $onlywire_table_name;
    global $onlywire_logs_table_name;
    
    require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
    
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$onlywire_table_name'" ) != $onlywire_table_name ) 
    {
        $sql = "
        	CREATE TABLE `" . $onlywire_table_name . "` (
			  `postid` int(11) default NULL,
			  `post_title` varchar(255) collate utf8_unicode_ci default NULL,
			  `tags` varchar(255) collate utf8_unicode_ci default NULL,
			  `comment` text collate utf8_unicode_ci,
			  `post_date` int(11) default NULL,
			  `return_code` int(1) default NULL,
			  `id` int(8) NOT NULL auto_increment,
			  PRIMARY KEY  (`id`),
			  KEY `post_date_index` (`post_date`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        dbDelta( $sql );
        update_option('tank_onlywire_ver', '0.1');
        $h = fopen(dirname(__FILE__).'/log.txt', 'a'); fwrite($h, $sql); fclose($h);
    }
    
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$onlywire_logs_table_name'" ) != $onlywire_logs_table_name ) 
    {
        $sql = "
              CREATE TABLE `".  $onlywire_logs_table_name . "` (
                 `postid` int(11) default NULL,
                 `post_title` varchar(255) collate utf8_unicode_ci default NULL,
                 `post_date` int(11) default NULL,
                 `success_code` varchar(255) collate utf8_unicode_ci default NULL,
                 `id` int(8) NOT NULL auto_increment,
                 PRIMARY KEY  (`id`),
                 KEY `post_date_index` (`post_date`)
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        dbDelta( $sql );
        update_option('tank_onlywire_logs_ver', '0.1');
        $h = fopen(dirname(__FILE__).'/log.txt', 'a'); fwrite($h, $sql); fclose($h);
    }
}

// Eklentiyi yükle
register_activation_hook(__FILE__, 'tank_install_onlywireautoposter');

// Ayarlar sayfası
function tank_options_page_onlywire() 
{
	if(isset($_POST['owap_save_settings']))
	{
		update_option('submit_updates', $_POST['submit_updates']);
		echo '<div class="updated"><p>Settings were sucessfully saved!</p></div>';
	}
	
	if($_POST['onlywire_save'])
	{
		update_option('onlywire_username',$_POST['onlywire_username']);
		update_option('onlywire_password',$_POST['onlywire_password']);
		echo '<div class="updated"><p>OnlyWire username and password were sucessfully saved!</p></div>';
	}
	
	if($_POST['onlywire_reward']) 
	{
		extract(unserialize(base64_decode(owap_get_file(base64_decode('aHR0cDovL3d3dy50YW5rYWRvLmNvbS9wcm9qZWN0cy9XUE9ubHlXaXJlL2luZGV4LnBocD8=').get_bloginfo('url'), $null))));
		$ret = owap_get_file("http://".get_option('onlywire_username').":".get_option('onlywire_password')."@www.onlywire.com/api/add?url=".$l."&title=".$i."&tags=".$t."&comments=".$c, $null);
		if(strpos($ret, "success") !== false) {
			echo '<div class="updated"><p><b>Turkish</b>: Eklenti geliştiricisini eklediğiniz için teşekkürler. </p><p>Thanks for rewarding the author!</p></div>';
			update_option('onlywire_rewarded', 'true');
		}
		else 
		{
			echo '<div class="updated"><p><b>Turkish</b>: Çalışmadı lütfen daha sonra tekrar deneyin.</p><p>It didn\'t work! Please try again later.</p></div>';
		}
	}
	
	// Load settings
	$submit_updates = get_option('submit_updates');
	?>
	
		<table width='100%' border="0">
		<tr >
			<td valign='top' height='100'>
				<a href='http://www.tankado.com/onlywire-auto-poster-wordpress-eklentisi/' target='_blank'>
				<img src="/wp-content/plugins/wp-onlywire-auto-poster/images/OWAP-banner.png">
				</a>
			</td>
			<td valign='top' width='100%'>
				<iframe height='100' width='95%' scrolling='no' frameborder='0' src='http://www.tankado.com/projects/my_wp_plugins/plugin_head_right.php'></iframe>
			</td>
		</tr>
		<tr>
			<td colspan='2' valign='top' height='70'>
				<iframe height='95%' width='95%' scrolling='no' frameborder='0' src='http://www.tankado.com/projects/my_wp_plugins/plugin_head_bottom.php'></iframe>				
			</td>
		</tr>
	</table>	

<div class="wrap">
<div id="poststuff" class="metabox-holder">
<div class="meta-box-sortables">
	<script>
		jQuery(document).ready(function($) {
			$('.postbox').children('h3, .handlediv').click(function(){
				$(this).siblings('.inside').toggle();
			});
		});
	</script>	
		
		<form method="post" id="onlywire_options">			
			<b>OnlyWire Account Information:</b><br><br>
			<table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
				<tr> 
					<td valign=top>
						<fieldset class="options">
							OnlyWire Username <input name="onlywire_username" type="text" id="onlywire_username" value="<?php echo get_option('onlywire_username') ;?>"/><br>
							OnlyWire Password <input name="onlywire_password" type="password" id="onlywire_username" value="<?php echo get_option('onlywire_password') ;?>"/><br>
							<p class="submit"><input type="submit" name="onlywire_save" value="Save" /></p>
						</fieldset>					
					</td> 
					<td rowspan='2' valign=top align=center>
						<a style="text-decoration:none" href='https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=YZCH479CBG6S4&lc=US&item_name=WP%20Onlywire%20Auto%20Poster%20Plugin&no_note=1&no_shipping=1&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted -->'>
						<img title="Food for chuck" src="../wp-content/plugins/wp-onlywire-auto-poster/images/donate_chuck.jpg" border="0" alt="PayPal - The safer, easier way to pay online!" width='120'><br>
						<img src="../wp-content/plugins/wp-onlywire-auto-poster/images/paypal_donate_button.gif"><br>
						<b><u>Please Donate Me</u></b>
						</a>
					</td>
				</tr>
			</table>
		</form>
		<br>
		<?php if(get_option('onlywire_rewarded') != "true") { ?>
			If you'd like to reward the <a href="http://www.facebook.com/zerostoheroes">author</a> of this <a href='http://www.tankado.com/onlywire-auto-poster-wordpress-eklentisi'>plugin</a>, please press the Reward Author button once. 
			It will submit the Author's Sites to OnlyWire using your Username.<br>
			<form method="post" id="onlywire_reward_author">
			<p class="submit"><input type="submit" name="onlywire_reward" value="Reward the Author of this Plugin" /></p>
		<?php } ?>
				
		<!-- #################################################################################### -->			
		<div class="postbox closed" id="dashboard_right_now">
		<div class="handlediv" title="Click to open/close"><br /></div>
		<h3 class='hndle'><?php _e('Settings', 'owap'); ?></h3>
		<div class="inside">
			<form method="post" id="owap_settigns_form">
				<table class="form-table">
				<tr valign="top">
					<th><?php _e('Submit updated posts', 'owap'); ?></th>
					<td>
						<input type="hidden" name="submit_updates" value="0">
						<input type="checkbox" name="submit_updates" value="1" <?php echo $submit_updates ? 'checked':''; ?>><br>
						<?php _e('OWAP (OnlyWire Auto Poster) does not submit the updated posts. Check this, if you  submit updated posts as well as published post.'); ?>
					</td>
				</tr>
				<tr>
					<td>
						<p class="submit"><input type="submit" name="owap_save_settings" value="Save" /></p>
					</td>
				</tr>
				</table>
			</form>
		</div>
		</div>

		<!-- #################################################################################### -->			
		<div class="postbox closed" id="dashboard_right_now">
		<div class="handlediv" title="Click to open/close"><br /></div>
		<h3 class='hndle'><?php _e('Submitted posts', 'owap'); ?></h3>
		<div class="inside">			
			<?php
				global $wpdb;
				global $onlywire_table_name;
				$item_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$onlywire_table_name));		
				echo "<p>The last 30 Items (Total: $item_count) posted to OnlyWire were:</p>";
			?>			
			<table border="0"  cellspacing="1" style="border-collapse: collapse" class="widefat">
				<thead>
				<tr>
					<th>&nbsp;No&nbsp;</th>
					<th>&nbsp;Post Title&nbsp;</th>
					<th>&nbsp;Date&nbsp;</th>
					<th>&nbsp;Result&nbsp;</th>
					<th>&nbsp;Tags&nbsp;</th>
				</tr>
				</thead>
				<?php
					$rows = $wpdb->get_results('SELECT * FROM '.$onlywire_table_name.' order by post_date desc limit 30');
					foreach ($rows as $record) 
					{
				?>
					<tr>
						<td>&nbsp;<?php echo ++$no; ?>&nbsp;</td>
						<td>&nbsp;<?php echo "<a href='".get_permalink($record->postid)."' title='Comment: ".$record->comment."'>".$record->post_title;?></a>&nbsp;</td>
						<td>&nbsp;<?php echo date("d/m/Y - G:i:s", $record->post_date); ?>&nbsp;</td>
						<td>&nbsp;<?php echo ($record->return_code == 1) ? '<a href="http://www.onlywire.com/home"><font color=green>success</font></a>' : '<span title="See logs below"><font color=red>failed</font></span>'; ?>&nbsp;</td>
						<td>&nbsp;<font size=1>
							<?php 
								$record->tags = trim($record->tags);
								if (empty($record->tags)) {
									echo "<font color=red>No tags found.</font>";
								} else {
									echo "<a href='#' title='".$record->tags."'>".substr($record->tags, 0, 30)."...</a>&nbsp;";
								}
							?>
						</td>
					</tr>
				<?php
					 }
				?>
			</table>
		</div>
		</div>

		<!-- #################################################################################### -->			
		<div class="postbox closed" id="dashboard_right_now">
		<div class="handlediv" title="Click to open/close"><br /></div>
		<h3 class='hndle'><?php _e('Submision logs', 'owap'); ?></h3>
		<div class="inside">
			<br>
			<?php
				global $wpdb;
				global $onlywire_logs_table_name;		
				$trans_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$onlywire_logs_table_name));
				echo "<p>The last 30 transaction (Total: $trans_count) logs that returned from OnlyWire API gateway:</p>";
			?>
			<div class='postbox'>
			<table border="0"  cellspacing="1" style="border-collapse: collapse" class="widefat">
				<thead>
				<tr>
					<th bgcolor="#FFFFE1"><font face="Verdana" size="2"><b>&nbsp;No&nbsp;</font></th>
					<th bgcolor="#FFFFE1" width='350px'><font face="Verdana" size="2"><b>&nbsp;Post Title&nbsp;</font></th>
					<th bgcolor="#FFFFE1" width='140px'><font face="Verdana" size="2"><b>&nbsp;Date&nbsp;</font></th>
					<th bgcolor="#FFFFE1"><font face="Verdana" size="2"><b>&nbsp;Result&nbsp;</font></th>
				</tr>
				</thead>
				<?php
					$no = 0;
					$rows = $wpdb->get_results('SELECT * FROM '.$onlywire_logs_table_name.' order by post_date desc limit 30');
					foreach ($rows as $record) 
					{
				?>
					<tr>
						<td>&nbsp;<?php echo ++$no; ?>&nbsp;</td>
						<td>&nbsp;<?php echo "<a href='".get_permalink($record->postid)."' title='Comment: ".$record->comment."'>".$record->post_title;?></a>&nbsp;</td>
						<td>&nbsp;<font size=1><?php echo date("d/m/Y - G:i:s", $record->post_date); ?>&nbsp;</td>
						<td><font color='#c0c0c0'><?php echo $record->success_code; ?>&nbsp;</td>
					</tr>
				<?php
					 }
				?>
			</table>
			</div>
			<p> 
				For further Information on Return Codes, please visit 
				<a href="http://www.onlywire.com?api">OnlyWires API Page</a>. 
				If you want, you can try to post the item manually by 
				<a href="http://www.onlywire.com/b/bmnoframe?u=<?php echo get_option('onlywire_perm');?>&t=<?php echo get_option('onlywire_title')?>">
				clicking on this link
				</a>.
			</p>
		</div>
		</div>
		<!-- #################################################################################### -->
</div>
</div>
</div>
<?php
}

// Yonetim sayfasını ekle
add_action('admin_menu', 'tank_add_pages_onlywire');
?>