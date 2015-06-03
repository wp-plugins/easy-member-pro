<?php
/*
Plugin Name: wp-EasyMemberPro!
Plugin URI: http://www.easymemberpro.com
Description: An Extension to Easy Member Pro, Allowing for the Viewing of Pages and Posts Based on Membership Levels, and drip feed options.
Version: 1.2.0
Author: EasyMemberPro
Author URI: http://www.easymemberpro.com
*/

class wp_EasyMemberPro
{
	var $message;
	var $err;

	function __construct()
	{
		if ( ! defined( 'WP_PLUGIN_URL' ) 	) 	define( 'WP_PLUGIN_URL'		, get_option("siteurl") . '/wp-content/plugins' );
		if ( ! defined( 'WP_PLUGIN_DIR' ) 	) 	define( 'WP_PLUGIN_DIR'		, ABSPATH . 'wp-content/plugins' );
		if ( basename(dirname(__FILE__)) == 'plugins' )
		{
			define("WPEMP_DIR"		, WP_PLUGIN_DIR.'/');
			define("WPEMP_URL"		, WP_PLUGIN_URL.'/');
		}
		else 
		{
			define("WPEMP_DIR" 		, WP_PLUGIN_DIR.'/'.basename(dirname(__FILE__)) . '/');
			define("WPEMP_URL"		, WP_PLUGIN_URL.'/'.basename(dirname(__FILE__)) . '/');
		}

		define("WPEMP_LOADED" 		, true);
		define("WPEMP_FILE" 		, __FILE__ );
		define("WPEMP_VER"		, "1.1.0" );

		register_activation_hook   ( WPEMP_FILE	, array( &$this, 'wpemp_activate'		)); 
		register_deactivation_hook ( WPEMP_FILE	, array( &$this, 'wpemp_deactivate'		)); // not required //

		$this->message 			= array();
		$this->err	 			= array();

		
		add_action( 'init'				, array( &$this, 'wpemp_session'), 0);
		add_action( 'init'				, array( &$this, 'wpemp_login'), 1);
		add_action( 'admin_menu'			, array( &$this, 'wpemp_admin_menu'		));
		add_action( 'wpemp_options_page_save'	, array( &$this, 'wpemp_options_page_save'));
		add_action( 'admin_menu'			, array( &$this, 'wpemp_add_custom_box'	));
		add_action( 'wp_print_styles'			, array( &$this, 'wpemp_style'		));
		add_action( 'wp_print_scripts'		, array( &$this, 'wpemp_script'		));
		add_action( 'save_post'				, array( &$this, 'wpemp_save_postdata'	), 1, 2);
		add_action( 'manage_pages_custom_column'	, array( &$this, 'wpemp_columns_vals'	), 10, 2);
		add_action( 'manage_posts_custom_column'	, array( &$this, 'wpemp_columns_vals'	), 10, 2);
		// Category Management
		add_action ('edit_category_form', array(&$this, 'wpemp_category_levels_form' ));
		add_action('edit_category',array(&$this, 'wpemp_save_category_levels_form'));

		add_filter( 'the_content'			, array( &$this, 'wpemp_content'), 100);
		add_filter( 'manage_pages_columns'		, array( &$this, 'wpemp_columns_heads'	));
		add_filter( 'manage_posts_columns'		, array( &$this, 'wpemp_columns_heads'	));
		add_filter( 'comments_template'		, array( &$this, 'wpemp_comments_template'));
		add_filter( 'plugin_action_links'		, array( &$this, 'wpemp_actions'		), 10, 2);
		
		add_filter('wp_list_categories',array(&$this,'wpemp_category_filter'));
		
		add_shortcode('emp-member-profile',array(&$this,'wpemp_display_profile'));
		add_shortcode('emp-member-memberships',array(&$this,'wpemp_display_memberships'));
		
		add_shortcode('emp-firstname',array(&$this,'wpemp_firstname'));
		add_shortcode('emp-lastname',array(&$this,'wpemp_lastname'));
		add_shortcode('emp-email',array(&$this,'wpemp_email'));
		add_shortcode('emp-addr',array(&$this,'wpemp_addr'));
		add_shortcode('emp-addr2',array(&$this,'wpemp_addr2'));
		add_shortcode('emp-addr3',array(&$this,'wpemp_addr3'));
		add_shortcode('emp-city',array(&$this,'wpemp_city'));
		add_shortcode('emp-state',array(&$this,'wpemp_state'));
		add_shortcode('emp-zipcode',array(&$this,'wpemp_zipcode'));
		add_shortcode('emp-country',array(&$this,'wpemp_country'));
		add_shortcode('emp-telephone',array(&$this,'wpemp_telephone'));
		add_shortcode('emp-mobile',array(&$this,'wpemp_mobile'));	
	}

	/*Handles Activation and Deactivation of Plugin*/
	function wpemp_activate(){
		update_option ("wp_wpemp_ver", WPEMP_VER);
		update_option ("wpemp_show_powered", "yes");

		// check if fopen is disabled //
		ini_set('allow_url_fopen','1');
		$old_ua = @ ini_get('user_agent');
		@ ini_set('user_agent','Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3');
		$f = @ fopen('http://localhost.com', "r");
		
		if(ini_get('allow_url_fopen')) update_option ("wp_wpemp_fopen", 'on');
		else update_option ("wp_wpemp_fopen", 'off');
		/*
		if( ! $f )
			update_option ("wp_wpemp_fopen", 'off');
		else
		{
			@fclose($f);
			update_option ("wp_wpemp_fopen", 'on');
		} 
		*/
		@ini_set('user_agent',$old_ua);
		
		
	}

	function wpemp_deactivate(){
		delete_option ( 'wp_wpemp_ver'	);
		delete_option ( 'wp_wpemp_fopen'	);
		delete_option ( 'wpemp_url'		);
		delete_option ( 'wpemp_name'		);
		delete_option ( 'wpemp_show_powered');
		delete_option ( 'wpemp_powered_link');
		delete_option ( 'wpemp_signup_link'	);
		delete_option ( 'wpemp_show_post'	);
		delete_option ( 'wpemp_show_page'	);
		delete_option ( 'wpemp_member_levels');
		// remove generated options from database
		$opts = get_option('wpemp_cat_options');
		foreach($opts as $v){delete_option ( 'wpemp_cat_levels_'.$v);}
		delete_option ( 'wpemp_cat_options');
		
	}
	
	/*Load Up Style Sheets and Javascripts*/
	function wpemp_style() {
		$myStyleUrl 	= WPEMP_URL . 'css/wp-easymemberpro.css';
		$myStyleFile 	= WPEMP_DIR . 'css/wp-easymemberpro.css';
	
		if ( file_exists($myStyleFile) ) {
			wp_register_style( 'wpemp_style', $myStyleUrl);
			wp_enqueue_style ( 'wpemp_style' );
		}
	}
	
	function wpemp_script(){
		wp_enqueue_script('wpemp_script', WPEMP_URL . 'js/wp-easymemberpro.js', array('jquery'));
	}
	
	/*Handles Global Options Page*/
	function wpemp_options_page(){
		if (!current_user_can('manage_options')) wp_die(__('Sorry, but you have no permissions to change settings'));

		do_action('wpemp_options_page_save');

		?>
		<div class="wrap">
		<h2>WP EasyMemberPro</h2>

		<?php

		if(!empty($this->err) && is_array($this->err) )
		{
			echo '<div class="error fade"><p><b>'. __('Error: ') .'</b><ul>';
			foreach($this->err as $e)
				echo '<li>'. $e .'</li>';
			echo '</ul></p></div>';
		}

		if(!empty($this->message) && is_array($this->message) )
		{
			echo '<div id="message" class="updated fade"><p><ul>';
			foreach($this->message as $m)
				echo '<li>'. $m .'</li>';
			echo '</ul></p></div>';
		}
		?>

		<form method="post" action="" name="form1"/>
		<?php wp_nonce_field('wpemp-update-options'); ?>
			<input type="hidden" name="call" value="save"/>
			<script type="text/javascript">
            var counter = 1;
function addInput(divName){
     
          var newdiv = document.createElement('div');
          newdiv.innerHTML = 'Level ID: &nbsp;' +
		  '<input name="levelIds[]" type="text"  size="2" /> &nbsp;Name &nbsp;' + 
		  '<input type="text" name="levelNames[]" /> ';
          document.getElementById(divName).appendChild(newdiv);
          counter++;
}
            </script>
            <table class="form-table">
				<tr valign="top">
				<th scope="row" colspan="2"><strong>Basic Settings</strong></th>
				</tr>
				<tr valign="top">
				<th scope="row">EasyMemberPro URL</th>
				<td><input type="text" name="wpemp_url" value="<?php echo (isset($_POST['wpemp_url'])? trim(strip_tags($_POST['wpemp_url'])):get_option('wpemp_url'));?>" size="40"/><br/>
				<span class="description">Please enter the URL to your EasyMemberPro Installation</span>
				</td>
				</tr>
				<tr valign="top">
				  <th scope="row">Membership Name</th>
				  <td><input name="wpemp_name" type="text" id="wpemp_name" value="<?php echo (isset($_POST['wpemp_name'])? trim(strip_tags($_POST['wpemp_name'])):get_option('wpemp_name'));?>" size="40"/>
				    <br/>
                  <span class="description">This will be displayed on the login screen</span></td>
		      </tr>
				<tr valign="top">
				<th scope="row">Display the "Powered By" Link?</th>
				<td><input type="checkbox" name="wpemp_show_powered" value="yes" <?php checked(get_option('wpemp_show_powered'), "yes");?>/><br/>
				</td>
				</tr>
				<tr valign="top">
				<th scope="row">Clickbank Affiliate URL</th>
				<td><input type="text" name="wpemp_powered_link" value="<?php echo (isset($_POST['wpemp_powered_link'])? trim(strip_tags($_POST['wpemp_powered_link'])):get_option('wpemp_powered_link'));?>" size="40"/><br/>
				<span class="description">"Powered By EasyMemberPro" link will be your affiliate link, earning you 65% of all sales you refer to us.<br/>
					<a href="https://www.clickbank.com/affiliateAccountSignup.htm?key=">Click Here</a> To Signup As A Clickbank Affiliate Free</span>
				</td>
				</tr>
				<tr valign="top">
				<th scope="row">Become a Member URL</th>
				<td><input type="text" name="wpemp_signup_link" value="<?php echo (isset($_POST['wpemp_signup_link'])? trim(strip_tags($_POST['wpemp_signup_link'])):get_option('wpemp_signup_link'));?>" size="40"/><br/>
				<span class="description">"Become a Member" link can be a link to your sale page. Leave it blank to link to EasyMemberPro Sign up page.</span>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row" colspan="2"><strong>Member Access Settings</strong></th>
				</tr>

				<tr valign="top">
				<th scope="row">Default Content to Protect<br/><small>(Individual Posts &amp; Pages can also be configured)</small></th>
				<td>
				<label><strong>Posts:</strong> <input type="checkbox" name="wpemp_show_post" value="yes" <?php checked(get_option('wpemp_show_post'), "yes");?>"/><br/>
				<span class="description">Only logged in users can see posts.</label><br/>
				<label><strong>Pages:</strong> <input type="checkbox" name="wpemp_show_page" value="yes" <?php checked(get_option('wpemp_show_page'), "yes");?>"/><br/>
				<span class="description">Only logged in users can see pages.</label><br/>
				</td>
				</tr>
				<tr valign="top">
				  <th scope="row">Membership Levels</th>
				  <td><?php 
				 
				  $levelstring = get_option('wpemp_member_levels');
				  $levelarray = explode(',',$levelstring);
				  $levelcount = count($levelarray);
				  //die(var_dump($levelcount));
				  ?>
                  
                  
                  <div id="dynamic_fields">
                 <?php  
				 if($levelcount > 0){
					  foreach($levelarray as $v){
						  $level = explode(':',$v);
						  
						  ?>
                          Level ID: &nbsp;
				    <input name="levelIds[]" type="text" value="<?php echo $level[0] ?>"  size="2" /> 
				    Name &nbsp; 
				   <input name="levelNames[]" type="text" value="<?php echo $level[1] ?>" /> <br />
                          
                          <?php 
						  }
					  }
				else{
                  
                  ?>
                  Level ID: &nbsp;
				    <input name="levelIds[]" type="text"  size="2" /> 
				    Name &nbsp; 
				   <input type="text" name="levelNames[]" /> 
                   <?php } ?>
			        </div>
                     
                  <input type="button" name="button" id="button" value="Add Another level" onClick="addInput('dynamic_fields')";/></td>
		      </tr>
			</table>
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		  </p>
		</form>
		</div>
		<?php
		$this->wpemp_footer();
	}

	function wpemp_options_page_save(){
		
		if(isset($_REQUEST['call']) && trim($_REQUEST['call']) == 'save')
		{
//die(var_dump($_REQUEST));			
check_admin_referer('wpemp-update-options');

			if(!isset($_REQUEST['wpemp_url']) || trim($_REQUEST['wpemp_url']) == '')
				$this->err[] = __('Please enter your EasyMemberPro URL');
			else
			{
				$_REQUEST['wpemp_url'] = trim(strip_tags($_REQUEST['wpemp_url']));
				if(!$this->wpemp_is_url($_REQUEST['wpemp_url']) )
					$this->err[] = __('Please enter a Valid EasyMemberPro URL');
			}

		//=
		// MDP - 7-17-12
		if(!isset($_REQUEST['wpemp_name']) || trim($_REQUEST['wpemp_name']) == '')
				$this->err[] = __('Please enter your Membership name');
		//=
			if(isset($_REQUEST['wpemp_show_powered']) && trim($_REQUEST['wpemp_show_powered']) == 'yes' )
				$_REQUEST['wpemp_show_powered'] = 'yes';
			else
				$_REQUEST['wpemp_show_powered'] = 'no';
		//=
			if(isset($_REQUEST['wpemp_powered_link']) && trim($_REQUEST['wpemp_powered_link']) != '')
			{
				$_REQUEST['wpemp_powered_link'] = trim(strip_tags($_REQUEST['wpemp_powered_link']));
				if(!$this->wpemp_is_url($_REQUEST['wpemp_powered_link']) )
					$this->err[] = __('Please enter a Valid Clickbank Affiliate URL');
			}

		//=
			if(isset($_REQUEST['wpemp_signup_link']) && trim($_REQUEST['wpemp_signup_link']) != '')
			{
				$_REQUEST['wpemp_signup_link'] = trim(strip_tags($_REQUEST['wpemp_signup_link']));
				if(!$this->wpemp_is_url($_REQUEST['wpemp_signup_link']) )
					$this->err[] = __('Please enter a Valid Sign up URL');
			}
		//=
			if(isset($_REQUEST['wpemp_show_post']) && trim($_REQUEST['wpemp_show_post']) == 'yes' )
				$_REQUEST['wpemp_show_post'] = 'yes';
			else
				$_REQUEST['wpemp_show_post'] = 'no';
		//=

			if(isset($_REQUEST['wpemp_show_page']) && trim($_REQUEST['wpemp_show_page']) == 'yes' )
				$_REQUEST['wpemp_show_page'] = 'yes';
			else
				$_REQUEST['wpemp_show_page'] = 'no';
				
				foreach ($_REQUEST['levelIds'] as $k=>$v){
					if($v == "" && $_REQUEST['levelNames'][$k]!=""){$this->err[] = __('Each Member Level Must Have An Id');}
				}
				foreach ($_REQUEST['levelNames'] as $k=>$v){
					if($v == "" && $_REQUEST['levelIds'][$k]!=""){$this->err[] = __('Each Member Level Must Have A Name');}
				}
			// no errors , save everything;
			// Create a string to store for member levels
			// levelID:LevelName,levelID:levelName,
			$levelsStore = '';
			foreach ($_REQUEST['levelIds'] as $k=>$v){
					if($_REQUEST['levelIds'] != '' && $_REQUEST['levelNames'][$k] != ''){$levelsStore .= $v.':'.$_REQUEST['levelNames'][$k].',';}
			}
			// Remove last ,
			$levelsStore = substr($levelsStore,0,-1);
			//$array = explode(",", $levelsStore);
			//die(var_dump($array));
			if(empty($this->err))
			{
				update_option('wpemp_url'		, trim($_REQUEST['wpemp_url']));
				update_option('wpemp_name'		, trim($_REQUEST['wpemp_name']));
				update_option('wpemp_show_powered'	, trim($_REQUEST['wpemp_show_powered']));
				update_option('wpemp_powered_link'	, trim($_REQUEST['wpemp_powered_link']));
				update_option('wpemp_signup_link'	, trim($_REQUEST['wpemp_signup_link']));
				update_option('wpemp_show_post'	, trim($_REQUEST['wpemp_show_post']));
				update_option('wpemp_show_page'	, trim($_REQUEST['wpemp_show_page']));
				update_option('wpemp_member_levels'	, $levelsStore);
				$this->message[] = __('Settings Saved');
			}
		}
	}

	function wpemp_admin_menu() {
		add_options_page('WP-EasyMemberPro', 'WP-EasyMemberPro', 8, 'wpemp_options', array( &$this, 'wpemp_options_page') );
	}
	
	/*Handles The Per Post & Per Page Options*/
	function wpemp_page_custom_box($post) {
		ob_start();
		?>
		<script type='text/javascript'>
		function showLevels(ele,div){
			if(ele.options[ele.selectedIndex].value == 'yes'){
				document.getElementById('levelsLabel').style.display = 'block';
				document.getElementById(div).style.display = 'block';
			}
			else{document.getElementById(div).style.display = 'none';document.getElementById('levelsLabel').style.display = 'none';}
		}
		</script>
<?php
		
		$levelstring = get_option('wpemp_member_levels');
		$levelarray = explode(',',$levelstring);
		//$levelArray = unserialize($levelstring);
		//die(var_dump($levelArray));
		$levelcount = count($levelarray);
		$setLevels = unserialize(get_post_meta($post->ID, '_wpemp_levels', true));
		//die(var_dump($setLevels));
		//$pos = strpos($setLevels, ',');
		//if ($pos === false) {}
		//else{$setLevels = explode(',',$setLevels);}
		$vis = get_post_meta($post->ID, '_wpemp_dropdown', true);
		
		if($vis == 'yes'){$display = 'display:inline';$display2 = 'display:block';}
		else{$display = 'display:none';$display2 = 'display:none';}

		$wpemp_options = array('none'=> 'Default [Global Settings]', 'yes'=>'Visible to Members only', 'no'=>'Visible to Everyone');
		?>

<input type="hidden" name="wpemp_nonce" id="wpemp_nonce" value="<?php echo wp_create_nonce( plugin_basename(__FILE__) ) ?>" />
<table border="0" width="95%" style="text-align:left">
	<?php
		if(get_option('wpemp_show_page') == 'yes'){ ?>
	<tr>
		<th colspan="3"><?php echo __('By default, Pages are currently visible only to members') ?></th>
	</tr>
	<?php }else{ ?>
	<tr>
		<th colspan="3"><?php echo __('By default, Pages are currently visible to everybody ')?></th>
	</tr>
	<?php } ?>
	<tr>
		<td>&raquo;
			<label for="wpemp_visibility"><?php echo  __("Set Page visibility with wp-EasyMemberPro")  ?></label>
			<div id="levelsLabel" style=" <?php echo $display2 ?>">&raquo;&raquo;
				<label for="wpemp_levels"><?php echo  __("Membership Level Required")  ?></label>
			</div></td>
		<td colspan="2"><select name="wpemp_dropdown" id="wpemp_dropdown" onChange= "showLevels(this,'allLevels')">
				<?php
		foreach($wpemp_options as $id => $opt)
		{?>
				<option value="<?php echo $id ?>" <?php echo  selected($id, get_post_meta($post->ID, '_wpemp_dropdown', true), false) ?>><?php echo $opt ?></option>
				<?php } ?>
			</select>
			';
			<div id="allLevels">
			<table>
				<?php 
		if($levelcount > 0){
			foreach($levelarray as $v){
				// Level is broken into an array
				// $level[0] is Id 
				// $level 1 is name
				//$days = 0;
				$checked = "";
				$level = explode(':',$v);
				
				if(is_array($setLevels)){
					
					foreach($setLevels as $k1=>$v1){
						//$k1 is the Id Of The User Level
						if($k1 == $level[0]){
							// This is the right array
							$checked = 'checked=checked';
							// Grab The Days
							//var_dump($v1);
							foreach ($v1 as $k2=>$v2){
								
								if($k2 == 'days') $days[$k1] = $v2;
							}
						}
						
					}
				}?>
				<tr>
				<td>
				<input name="wpemp_levels[]" type="checkbox" <?php echo $checked ?> value="<?php echo $level[0] ?>" style=" <?php echo $display ?>"/></td>
				<td>
				<?php echo $level[1] ?>-> Membership Days Required</td>
				<td><span style="text-align:right"><input type="text" name="wpemp_levels_days[]" size="5" value="<?php echo $days[$level[0]] ?>"></span></td>
				<?php

			}
		}
		?>
			</tr></table></div></td>
	</tr>
	<tr>
		<td width="35%">&raquo;
			<label for="wpemp_excerpt"><?php echo  __("Show Page Excerpts")  ?></label></td>
		<td width="65%" colspan="2"><input type="checkbox" name="wpemp_excerpt" id="wpemp_excerpt" value="yes" <?php echo  checked(get_post_meta($post->ID, '_wpemp_excerpt', true), 'yes', false) ?>/></td>
	</tr>
	<tr>
		<td colspan="3">&nbsp;</td>
	</tr>
	<tr>
		<td><strong>Member Profile Tags</strong><br>
			[emp-firstname]<br>
			[emp-lastname]<br>
			[emp-email]<br>
			[emp-addr]<br>
			[emp-addr2]<br>
			[emp-addr3]<br>
			[emp-city]<br>
			[emp-state]<br>
			[emp-zipcode]<br>
			[emp-country]<br>
			[emp-telephone]<br>
			[emp-mobile]</td>
		<td valign="top">
		<p><strong>Member Data Table Tags </strong></p>
			<p><strong>Shows Member Profile in a table</strong><br>
				[emp-member-profile]</p>
			<p><strong>Shows Active Memberships in a table</strong><br>
				[emp-member-memberships]</p>
		</td>
	</tr>
</table>
<?php
	echo ob_get_clean();
		
	}

	function wpemp_post_custom_box($post) {
		$html = "";
		$html .="<script type='text/javascript'>
		function showLevels(ele,div){
			if(ele.options[ele.selectedIndex].value == 'yes'){
				document.getElementById('levelsLabel').style.display = 'block';
				document.getElementById(div).style.display = 'block';
			}
			else{document.getElementById(div).style.display = 'none';document.getElementById('levelsLabel').style.display = 'none';}
		}
		</script>";
		$levelstring = get_option('wpemp_member_levels');
		$levelarray = explode(',',$levelstring);
		//$levelArray = unserialize($levelstring);
		//die(var_dump($levelArray));
		$levelcount = count($levelarray);
		$setLevels = unserialize(get_post_meta($post->ID, '_wpemp_levels', true));
		//die(var_dump($setLevels));
		//$pos = strpos($setLevels, ',');
		//if ($pos === false) {}
		//else{$setLevels = explode(',',$setLevels);}
		$vis = get_post_meta($post->ID, '_wpemp_dropdown', true);
		
		if($vis == 'yes'){$display = 'display:inline';$display2 = 'display:block';}
		else{$display = 'display:none';$display2 = 'display:none';}

		$wpemp_options = array('none'=> 'Default [Global Settings]', 'yes'=>'Visible to Members only', 'no'=>'Visible to Everyone');
		$html .= '<input type="hidden" name="wpemp_nonce" id="wpemp_nonce" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
		$html .= '<table border="0" width="95%" style="text-align:left">';

		if(get_option('wpemp_show_post') == 'yes')
			$html .= '<tr><th colspan="2">'.__('By default, Posts are currently visible only to members.').'</th></tr>';
		else
			$html .= '<tr><th colspan="2">'.__('By default, Posts are currently visible to everybody.').'</th></tr>';

		$html .= '<tr><td> &raquo; <label for="wpemp_visibility">' . __("Set Posts visibility with wp-EasyMemberPro") . '</label>
		<div id="levelsLabel" style="'.$display2.'">&raquo;&raquo; <label for="wpemp_levels">' . __("Membership Level Required") . '</label></div>
		</td>';
		$html .= '<td><select name="wpemp_dropdown" id="wpemp_dropdown" onChange= "showLevels(this,\'allLevels\')">';
		foreach($wpemp_options as $id => $opt)
		{
			$html .= '<option value="'.$id.'" '. selected($id, get_post_meta($post->ID, '_wpemp_dropdown', true), false).'>'.$opt.'</option>';
		}
		$html .= '</select>';
		$html .= '<div id="allLevels">
			<table>';
		if($levelcount > 0){
			foreach($levelarray as $v){
				// Level is broken into an array
				// $level[0] is Id 
				// $level 1 is name
				//$days = 0;
				$checked = "";
				$level = explode(':',$v);
				
				if(is_array($setLevels)){
					
					foreach($setLevels as $k1=>$v1){
						//$k1 is the Id Of The User Level
						if($k1 == $level[0]){
							// This is the right array
							$checked = 'checked=checked';
							// Grab The Days
							//var_dump($v1);
							foreach ($v1 as $k2=>$v2){
								
								if($k2 == 'days') $days[$k1] = $v2;
							}
						}
						
					}
				}
				$html .= '<tr>
				<td>
				<input name="wpemp_levels[]" type="checkbox" '.$checked.' value="'.$level[0].'" style=" '.$display.'"/></td>
				<td>
				'.$level[1] .'-> Membership Days Required</td>
				<td><span style="text-align:right"><input type="text" name="wpemp_levels_days[]" size="5" value="'.$days[$level[0]].'"></span></td>';

			}
		}
		
			$html .= '</tr></table></div>';
		$html .= '</td></tr>';
		$html .= '<tr><td width="35%"> &raquo; <label for="wpemp_excerpt">' . __("Show Post Excerpts") . '</label></td>';
		$html .= '<td width="65%"><input type="checkbox" name="wpemp_excerpt" id="wpemp_excerpt" value="yes" '. checked(get_post_meta($post->ID, '_wpemp_excerpt', true), 'yes', false).'/></td></tr>';
		
		$html .='<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td><strong>Member Profile Tags</strong><br>
			[emp-firstname]<br>
			[emp-lastname]<br>
			[emp-email]<br>
			[emp-addr]<br>
			[emp-addr2]<br>
			[emp-addr3]<br>
			[emp-city]<br>
			[emp-state]<br>
			[emp-zipcode]<br>
			[emp-country]<br>
			[emp-telephone]<br>
			[emp-mobile]</td>
		<td valign="top">
		<p><strong>Member Data Table Tags </strong></p>
			<p><strong>Shows Member Profile in a table</strong><br>
				[emp-member-profile]</p>
			<p><strong>Shows Active Memberships in a table</strong><br>
				[emp-member-memberships]</p>
		</td>
	</tr></table>';
		echo $html;}

	function wpemp_add_custom_box() {
		if( function_exists( 'add_meta_box' )) {
			foreach (array('post','page') as $type) 
				add_meta_box( 'wpemp_section', __( 'wp-EasyMemberPro Options'), array( &$this, 'wpemp_'.$type.'_custom_box'), $type, 'normal', 'high' );
		}
	}
	
	

	function wpemp_is_url(&$obj){
		if (! ( ($c = strpos($obj, 'http')) == 0 && $c !== false ) )
			$obj = 'http://' . $obj;

		// In php 5 parse_url may fail if the URL query part contains http://, bug #38143
		$obj = ( $cut = strpos($obj, '?') ) ? substr( $obj, 0, $cut ) : $obj;

		$url  = parse_url($obj);
		if ( $url === false || empty($url['host']))
			return false;
		else
			return true;
	}

	function wpemp_footer() {
		$plugin_data = get_plugin_data( __FILE__ );
		echo '<br/><div id="page_footer" style="text-align:center"><em>';
		printf('%1$s | Version %2$s | by %3$s<br />', $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']); 
		echo '</em></div>';
	}
	
	/*Handles The Page Protection And Login Box Features*/

	function wpemp_actions($links, $file){
		if( strpos( $file, basename(__FILE__)) !== false )
		{
			$link = '<a href="options-general.php?page=wpemp_options">'.__('Settings').'</a>';
			array_unshift( $links, $link );
		}
		return $links;
	}
	
	function wpemp_display_profile(){
		$data = unserialize($_SESSION['wpemp_userData']);
		
		ob_start();
		require(WPEMP_DIR.'/inc/profile.php');
		
		return ob_get_clean();
		//return 'Hello!';
		
	}
	
	function wpemp_display_memberships(){
		$data = unserialize($_SESSION['wpemp_userData']);
		
		ob_start();
		//var_dump($data);
		require(WPEMP_DIR.'/inc/membership-details.php');
		
		return ob_get_clean();
		
	}
	
	function wpemp_content($content = ''){
		@session_start();
		if(get_option('wpemp_url') == '' && (get_option('wpemp_show_post') == 'yes' || get_option('wpemp_show_page') == 'yes'))
			return $content . '<div id="wpemp_message"><strong>'.__('EasyMemberPro URL Required').'</strong><br/>'.__('Please goto settings page and set the URL to EasyMemberPro').'</div>';

		global $post;
		$post_option = get_post_meta($post->ID, "_wpemp_dropdown", true);
		$post_level = get_post_meta($post->ID, "_wpemp_levels", true);
		$post_option = $post_option?$post_option:'none';
		$post_excerpt = get_post_meta($post->ID, "_wpemp_excerpt", true);
		
		
		// Lets check if Level is required
		$userlevels = unserialize($_SESSION['wpemp_userData']);
		//die(var_dump($_SESSION['wpemp_userData']));
		
		//die(var_dump($userlevels->activeLevelData));
		
		$levelData = (array)$userlevels->activeLevelData;
		
		//die(var_dump($levelData));
		foreach ($levelData as $k=>$v){
			
			//die(var_dump((array)$v));
			$memberRights[$k] = (array)$v;
			
			
		}
		//die(var_dump($memberRights));
		
		$postRights = unserialize($post_level);
		//$memberRights = $userlevels;
		
		
		$approved = 0;
		$notEnoughDays = array();
		if(isset( $_SESSION['user']['email'] )){
			//die(var_dump($postRights));
			foreach($postRights as $k=>$v){
				//die(var_dump($memberRights));
				$nd = 0;
				
				// Lets Check If User Has member Levels
				if(is_array($memberRights)){
					foreach($memberRights as $k2 =>$v2){
					//var_dump($v2);die();
					if($v['id'] == $k2){
						// Found A Matching Assigned Level
						$html .= 'This is a match<br />';
						// Check Drip Feed days
						if($v['days'] <= $v2['days']){$approved = 1;break;}
						else{
							
							$dayDiff = $v['days'] - $v2['days'];
							$notEnoughDays[$k2]['id'] = $k2;
							$notEnoughDays[$k2]['by'] = $dayDiff;
							$nd++;

						}
					}
				}
					
				}
				
			}
			//die(var_dump($notEnoughDays));
			//die(var_dump($notAMember));
		}
		//die(var_dump($notEnoughDays));
		// we have nothing, fall back on global options
		if($post_option == 'none')
		{
			$post_type	 = $post->post_type;
			$post_option = get_option('wpemp_show_'.$post_type);
		}

		if($post_option == 'yes' && is_feed() )
		{
			$html = __('Requires valid login to view posts. ');
			if(get_option('wpemp_show_powered') == 'yes')
				$html .= '<br/><br/>Powered by EasyMemberPro';
			return $html;
		}
		else if( $post_option == 'yes' && !isset( $_SESSION['user']['email'] ) )
		{
			//die(var_dump($_SESSION));
			$scontent = '';
			if($post_excerpt == 'yes')
			{
				remove_filter('the_content', array( &$this, 'wpemp_content'), 100);
				$scontent = get_the_excerpt();
				add_filter('the_content', array(&$this, 'wpemp_content'), 100);
			}
			//die('No Log In');
			//die($scontent. $this->wpemp_get_login_box($post->ID));
			return $scontent. $this->wpemp_get_login_box($post->ID);
		}
		else if( $post_option == 'yes' && isset( $_SESSION['user']['email'] ) ){
			//die(var_dump($memberRights));
			//die($sContent);
			//return $sContent;
			if($approved == 0){
				$scontent = '';
				if($post_excerpt == 'yes'){
					remove_filter('the_content', array( &$this, 'wpemp_content'), 100);
					$scontent = get_the_excerpt();
					add_filter('the_content', array(&$this, 'wpemp_content'), 100);
				}
				
				// Figure Out If Disapproved By Level Or Days
				if(!empty($notEnoughDays)){
					return $scontent. $this->wpemp_get_dripfeed_box($notEnoughDays, $notEnoughDays);
				}
				return $scontent. $this->wpemp_get_upgrade_box($post->ID);
			}
			else{return $content. '<div id="logout" align="right">'.$this->wpemp_get_logout_link().'</div>';}
	}
		else return $content;
	}

	function wpemp_get_logout_link(){
		return  '<a href="'.get_option("siteurl").'/?wpemp=logout" title="'.__('Logout').'">'.__('Logout').'</a><br/>'."\n";
	}

	function wpemp_get_login_link(){
		$html  = '<div class="wpemp_message">';
		$html .= '<p class="wpemp_p">'."\n";
		$html .= 'This post is for '.get_option('wpemp_name').'members only.<br/>';
		$html .= '<a href="'.$this->wpemp_get_singup_link().'" title="'.__('Register').'" target="_blank">'.__('Become a Member').'</a> | '."\n";
		$html .= '<a href="'.trim(get_option('wpemp_url'),'/').'/index.php?page=login" title="'.__('Login').'">'.__('Login').'</a><br/>'."\n";

		if(get_option('wpemp_show_powered') == 'yes')
		{
			$html .= '<span class="wpemp_small">Powered By <a href="'.$this->wpemp_get_aff_link().'" target="_blank"';
			$html .= ' title="Powered by EasyMemberPro">EasyMemberPro</a></span>'."\n";
		}

		$html .= '</p>'."\n";
		$html .= '</div>';
		return $html;
	}

	function wpemp_get_login_box($post_id){
		// check if fopen is disabled //
		//die(get_option ("wp_wpemp_fopen"));
		$msg = '';
		if(isset($_SESSION['wmemp_loginError'])) $msg = '<div style="color: red; text-align: center; font-weight: bold; padding-bottom: 10px;">Username or password incorrect</div>';
		if( get_option ("wp_wpemp_fopen") == 'off' ) {
			return $msg.$this->wpemp_get_login_link(true);
		}
		
		/*Login Box Html*/
		ob_start();
		?>
		
		<div class="wpemp_message" id="wpemp_message-<?php echo $post_id ?>">
		<?php echo $msg?>
		<p class="wpemp_p"><strong><?php echo __('This content is for <strong>'.get_option('wpemp_name').' </strong> members only.')?></strong></p>

		<div id="wpemp_login_div-<?php echo $post_id ?>" style="display:none">
		<form id="wpemp_login_form-<?php echo $post_id ?>" class="wpemp_login_form" name="wpemp_login_form" action="<?php echo get_option("siteurl")?>/?wpemp=login" method="post">
		<input type="hidden" name="ajax_nonce" id="ajax_nonce-<?php echo $post_id ?>" value="<?php echo wp_create_nonce( 'wpemp_ajax' )?>"/>
		<p class="wpemp_p">
			<label for="wpemp_user"><?php echo __('Username')?>: </label><br/>
			<input name="wpemp_user" id="wpemp_user-<?php echo $post_id ?>" class="wpemp_user" value="" title="User Name"/>
		</p>
		<p class="wpemp_p">
			<label for="wpemp_pass"><?php echo __('Password')?>: </label><br/>
			<input name="wpemp_pass" id="wpemp_pass-<?php echo $post_id ?>" class="wpemp_pass" value="" type="password" title="'.__('Password').'"/>
		</p>
		<p class="wpemp_pl">
			<span id="wpemp_login_note-<?php echo $post_id ?>" class="wpemp_login_note">&nbsp;</span>
		</p>
		<p class="wpemp_pr">
			<img src="<?php echo  WPEMP_URL ?>css/loader.gif" id="wpemp_loader-<?php echo $post_id ?>" class="wpemp_loader" alt="Loading..." style="display:none"/>
			<input value="Login" name="wpemp_submit" id="wpemp_submit-<?php echo $post_id ?>" class="wpemp_submit" type="submit" />
		</p>
		</form></div>

		<p class="wpemp_p">
		<a href="<?php echo $this->wpemp_get_singup_link()?>" id="wpemp_link_register-<?php echo $post_id ?>" title="<?php echo __('Register')?>" target="_blank"><?php echo __('Become a Member')?></a> | 
		<a href="<?php echo trim(get_option('wpemp_url'),'/')?>/index.php?page=login" id="wpemp_link_login-<?php echo $post_id ?>" class="wpemp_link_login" title="<?php echo __('Login')?>"><?php echo __('Login')?></a>
		<a href="<?php echo trim(get_option('wpemp_url'),'/')?>/index.php?page=forgot" id="wpemp_link_forgot-<?php echo $post_id ?>" title="<?php echo __('Forgot Password')?>" style="display:none;" target="_blank"> | <?php echo __('Forgot Password')?></a><br/>
		<?php if(get_option('wpemp_show_powered') == 'yes'){ ?>
			<span class="wpemp_small">Powered By <a href="'.$this->wpemp_get_aff_link().'" ';
			 target="_blank" title="Powered by EasyMemberPro">EasyMemberPro</a></span>
		<?php } ?>
		</p>
		</div>
		<style type="text/css">.postmetadata, .entry-utility {display: none;}</style>
		<?php 
		$html = ob_get_clean();
		
		unset($_SESSION['wmemp_loginError']);
		
		return $html;
	}
	
	function wpemp_get_upgrade_box($post_id){
		
		$levelids = unserialize(get_post_meta($post_id, "_wpemp_levels", true));
		$memberlevels = get_option('wpemp_member_levels');
		//echo var_dump($levelids);
		//echo var_dump($memberlevels);
		//die();
		$a = explode(',',$memberlevels);
		foreach($a as $v){
			$b = explode(':',$v);
			//die(var_dump($b));
			$levelList[$b[0]] = $b[1];
		}
		$html  = '<div class="wpemp_message" id="wpemp_message-'.$post_id.'">';
		$html .= '<p class="wpemp_p">
		<strong>'.__('This content is for '.get_option('wpemp_name').' members with one of the following memberships.').'</strong>'."<br />\n";
		foreach($levelids as $v){
			$html .= $levelList[$v['id']].'<br />';
			
		}
		
		$html .= '</p><div align="right">'.$this->wpemp_get_logout_link().'</div>';
		if(get_option('wpemp_show_powered') == 'yes')
		{
			$html .= '<span class="wpemp_small">Powered By <a href="'.$this->wpemp_get_aff_link().'" ';
			$html .= ' target="_blank" title="Powered by EasyMemberPro">EasyMemberPro</a></span>'."\n";
		}
		$html .= '</p>'."\n";
		$html .= '</div>'."\n";
		$html .= '<style type="text/css">.postmetadata, .entry-utility {display: none;}</style>'."\n";
		return $html;
	}
	
	function wpemp_get_dripfeed_box($notEnoughDays){
		$memberlevels = get_option('wpemp_member_levels');
		$a = explode(',',$memberlevels);
		foreach($a as $v){
			$b = explode(':',$v);
			//die(var_dump($b));
			$levelList[$b[0]] = $b[1];
		}
		rsort($notEnoughDays);
		
		foreach($notEnoughDays as $k=>$v){
			//die(var_dump($v));
			foreach($v as $k2=>$v2){
				
				if($k2 == 'id'){
					$name = $levelList[$v2];
					
				}
				if($k2 == 'by'){$days = $v2;}
				
			}
			$listHtml .= $name.'  members in '.$days.' more days.<br/>';	
		}
		$html  = '<div class="wpemp_message" id="wpemp_message-'.$post_id.'">';
		$html .= '<p class="wpemp_p">
		<strong>'.__('This content will be available to <br />'.$listHtml).'</strong>'."<br />\n";
		
		$html .= '</p><div align="right">'.$this->wpemp_get_logout_link().'</div>';
		if(get_option('wpemp_show_powered') == 'yes')
		{
			$html .= '<span class="wpemp_small">Powered By <a href="'.$this->wpemp_get_aff_link().'" ';
			$html .= ' target="_blank" title="Powered by EasyMemberPro">EasyMemberPro</a></span>'."\n";
		}
		$html .= '</p>'."\n";
		$html .= '</div>'."\n";
		$html .= '<style type="text/css">.postmetadata, .entry-utility {display: none;}</style>'."\n";
		return $html;
	}
	
	function wpemp_get_aff_link(){
		if($link = get_option("wpemp_powered_link"))
			return $link;
		else
			return 'http://www.easymemberpro.com/?utm_source=blog&utm_medium=blog&utm_campaign=blogpoweredby';
	}

	function wpemp_get_singup_link(){
		if($link = get_option("wpemp_signup_link"))
			return $link;
		else
			return trim(get_option('wpemp_url'),'/').'/index.php?page=join';
	}

	
	/*Handles The Login And Out Function*/ 
	function wpemp_login(){
		//die(var_dump($_SESSION));
		if( isset($_GET['wpemp']) && trim($_GET['wpemp']) == 'logout' )
		{
			unset($_SESSION['user']['email']);
			unset($_SESSION['wpemp_levels']);
			wp_redirect(get_option('siteurl'));
			exit;
		}

		if( !( isset($_GET['wpemp']) && trim($_GET['wpemp']) == 'login') )
			return false;

		if(!headers_sent())
		{
			header("Pragma: no-cache");
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: must-revalidate");
		}
	
		if(isset($_REQUEST['_ajax_nonce']))
		{
			if(!defined('DOING_AJAX')) define('DOING_AJAX', 1);
			check_ajax_referer( "wpemp_ajax" );
		}
		else if ( !wp_verify_nonce( trim(strip_tags($_POST['ajax_nonce'])), 'wpemp_ajax' )) {
			wp_die("Please goto the home page and try login in.", get_bloginfo( 'name' ) . " &raquo; EasyMemberPro");
		}
	
		$wpemp_user = (isset($_POST['wpemp_user'])?  	esc_attr(trim(strip_tags($_POST['wpemp_user'])))	: '' );
		$wpemp_pass = (isset($_POST['wpemp_pass'])?  	esc_attr(trim(strip_tags($_POST['wpemp_pass'])))	: '' );
		
		if($wpemp_user == '' || $wpemp_pass == '')
		{
			if(defined('DOING_AJAX'))
				die("Username and Password are required");
			else
				wp_die("Username and Password are required", get_bloginfo( 'name' ) . " &raquo; EasyMemberPro");
		}
	
		ini_set('allow_url_fopen','1');
		$wmemp_auth_url  = trim(get_option('wpemp_url'), '/').'/api/remoteWP.php?username='.$wpemp_user.'&password='.md5($wpemp_pass);

		$wmemp_auth_url .= '&_nonce='.wp_create_nonce( 'wpemp'. substr(time(), -5) ); // force a new value;
		$_SESSION['debug'] = $wmemp_auth_url;
		$auth_res =  file_get_contents($wmemp_auth_url);
		//die(var_dump($auth_res));
		//$_SESSION['debug'] = $wmemp_auth_url;
		//$ch = curl_init();    // initialize curl handle
	//curl_setopt($ch, CURLOPT_URL,$wmemp_auth_url); // set url to post to
	//curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
	//curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
	//curl_setopt($ch, CURLOPT_TIMEOUT, 3); // times out after 4s
	//curl_setopt($ch, CURLOPT_POST, 1); // set POST method
	//curl_setopt($ch, CURLOPT_POSTFIELDS, "url=index%3Dbooks&field-keywords=PHP+MYSQL"); // add POST fields
	//$result = curl_exec($ch); // run the whole process
	//curl_close($ch);
	//setcookie("currentVersion", $result, time()+60*60*24);  
	//return $result;
	//$auth_res = $result;
	
		// Auth Res should be serialized data as of 1.1
		session_start();
		if($auth_res == '0')
		{
			$_SESSION['wmemp_loginError'] = "Username or password incorrect";
			if(defined('DOING_AJAX'))
				die("Username or password incorrect");
			else
				wp_die("Username or password incorrect", get_bloginfo( 'name' ) . " &raquo; EasyMemberPro");
		}
		else
		{
			session_start();
			if($auth_res == '1'){$_SESSION['user']['email'] = $wpemp_user;$_SESSION['wpemp_levels'] = NULL;}
			else{
				$_SESSION['wpemp_userData'] = $auth_res;
				$res = unserialize($auth_res);
				
				$_SESSION['user']['email'] = $wpemp_user;
				$_SESSION['wpemp_levels'] = $res;	
			}
			if(defined('DOING_AJAX'))
				die('1');
		}
		die();
	}
	
	function wpemp_session(){
		session_start();
	}

	function wpemp_comments_template($attr){
		if(!isset( $_SESSION['user']['email'] ) )
			$attr = WPEMP_DIR . "wpemp_comments.php";
		return $attr;
	}
	
	

	function wpemp_save_postdata( $post_id, $post ) {
		if ( !wp_verify_nonce( $_POST['wpemp_nonce'], plugin_basename(__FILE__) )) {
			return $post_id;
		}
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			return $post_id;
	
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ))
		  		return $post_id;
		} else {
			if ( !current_user_can( 'edit_post', $post_id ))
		  		return $post_id;
		}
  		$levelstring = "";
		//die(var_dump($_POST));
		
		$levelSettings = array();
		if(is_array($_POST['wpemp_levels'])){foreach($_POST['wpemp_levels'] as $k=>$v){
			
			$levelSettings[$v]['id'] = $v;
			$levelSettings[$v]['days'] = $_POST["wpemp_levels_days"][$k];
		}}
		
		
		$storeLevelSettings = serialize($levelSettings);
		
		
		if(isset($_POST['wpemp_levels']) && $_POST['wpemp_levels'] !=''){
			if(is_array($_POST['wpemp_levels'])){
			foreach($_POST['wpemp_levels'] as $v){
				$levelstring .= $v.',';
			}
			$levelstring = substr($levelstring,0,-1);
		}
			else{$levelstring = $_POST['wpemp_levels'];}
		}
		$wpemp_levels = $levelstring;
		$wpemp_dropdown = trim(strip_tags(stripslashes($_POST['wpemp_dropdown'])));
		$wpemp_excerpt = trim(strip_tags(stripslashes($_POST['wpemp_excerpt'])));

		update_post_meta( $post_id, '_wpemp_dropdown', $wpemp_dropdown);
		update_post_meta( $post_id, '_wpemp_levels', $storeLevelSettings);
		update_post_meta( $post_id, '_wpemp_excerpt', $wpemp_excerpt);
		return $post_id;
	}
	
	function wpemp_columns_vals($column_name, $post_id){
		global $post;

		if ($column_name == 'wpemp_visibility') {

			$post_option = get_post_meta($post->ID, "_wpemp_dropdown", true);
			$post_option = $post_option?$post_option:'none';
			$t = '';

			// we have nothing, fall back on global options
			if($post_option == 'none')
			{
				$post_type	 = $post->post_type;
				$post_option = get_option('wpemp_show_'.$post_type);
				$post_option = $post_option?$post_option:'no';
				$t = __('Default: ');
			}

			switch($post_option) {
				case 'yes':	$visibility = $t. __('Members');  break;
				case 'no':	$visibility = $t. __('Everyone'); break;
			}
			echo $visibility;
		}
	}

	function wpemp_columns_heads($defaults){
		$defaults['wpemp_visibility'] = __('Visibility');
		return $defaults;
	}
	
	function wpemp_category_levels_form() {
		if(isset($_GET['action']) && $_GET['action']=="edit") {
?>
<div class="icon32" id="icon-edit"><br></div>
<h3><?php echo _e("EMP Membership Requirements"); ?></h2>
<?php $cat_meta = get_option('cat_levels_key_'.$_GET['tag_ID']); //print_r( $cat_meta); ?>
<?php
$html = "";
		$html .="<script type='text/javascript'>
		function showLevels(ele,div){
			if(ele.options[ele.selectedIndex].value == 'yes'){
				document.getElementById('levelsLabel').style.display = 'block';
				document.getElementById(div).style.display = 'block';
			}
			else{document.getElementById(div).style.display = 'none';document.getElementById('levelsLabel').style.display = 'none';}
		}
		</script>";
		$levelstring = get_option('wpemp_member_levels');
		$levelarray = explode(',',$levelstring);
		$levelcount = count($levelarray);
		//die(var_dump($levelstring));
		// get Current Settings For category Levels
		$tig = $_GET['tag_ID'];
		$option = "wpemp_cat_levels_$tig";
		$mysettings = get_option($option);
		$setLevels = $mysettings[1];
		$pos = strpos($setLevels, ',');
		if ($pos === false) {}
		else{$setLevels = explode(',',$setLevels);}
		$vis = $mysettings[0];
		if($vis == 'yes'){$display = 'display:inline';$display2 = 'display:block';}
		else{$display = 'display:none';$display2 = 'display:none';}

		$wpemp_options = array('yes'=>'Visible to Members only', 'no'=>'Visible to Everyone');
		$html .= '<input type="hidden" name="wpemp_nonce" id="wpemp_nonce" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
		$html .= '<table border="0" width="50%" style="text-align:left">';

		$html .= '<tr><td> <label for="wpemp_visibility">' . __("Set Category Visibility with wp-EasyMemberPro") . '</label>
		<div id="levelsLabel" style="'.$display2.'"><label for="wpemp_levels">' . __("Membership Level Required") . '</label></div>
		</td>';
		$html .= '<td><select name="wpemp_dropdown" id="wpemp_dropdown" onChange= "showLevels(this,\'allLevels\')">';
		foreach($wpemp_options as $id => $opt)
		{
			
			$html .= '<option value="'.$id.'" '. selected($id, $vis, false).'>'.$opt.'</option>';
		}
		$html .= '</select>';
		$html .='<div id="allLevels" style="'.$display.'">';
		
		if($levelcount > 0){
			foreach($levelarray as $v){
				$checked = "";
				$level = explode(':',$v);
				if(is_array($setLevels)){
					foreach($setLevels as $v){

						if($v == $level[0]){
							$checked = 'checked=checked';break;
						}
						else{$checked = "";}
					}
				}
				else{
					if($setLevels == $level[0]){
						$checked = 'checked=checked';
					}
					else{$checked = '';}
				}

$html .='<input name="wpemp_levels[]" type="checkbox" '.$checked.' value="'.$level[0].'""/>&nbsp;'.$level[1].'&nbsp;';

	}
}
		$html .='</div>';
		$html .= '</td></tr></table>';
		echo $html;
}
	}
	
	function wpemp_save_category_levels_form(){
		if(isset($_POST['action']) && $_POST['action']=="editedtag" && $_POST['taxonomy']=="category") {
    
	//die(var_dump($_POST));
  		$levelstring = "";
		if(isset($_POST['wpemp_levels']) && $_POST['wpemp_levels'] !=''){
			if(is_array($_POST['wpemp_levels'])){
				foreach($_POST['wpemp_levels'] as $v){
					$levelstring .= $v.',';
			}
			$levelstring = substr($levelstring,0,-1);
		}
			else{$levelstring = $_POST['wpemp_levels'];}
		}
		$wpemp_levels = $levelstring;
		$wpemp_dropdown = trim(strip_tags(stripslashes($_POST['wpemp_dropdown'])));
		//$wpemp_excerpt = trim(strip_tags(stripslashes($_POST['wpemp_excerpt'])));
		$cat_meta_setting[0] = $wpemp_dropdown;
		$cat_meta_setting[1] = $wpemp_levels;
		$tig = $_POST['tag_ID'];
		$option = "wpemp_cat_levels_$tig";
		//die($option);
		update_option($option,$cat_meta_setting);
		$catoptions = get_option('wpemp_cat_options');
		if($catoptions){
			foreach($catoptions as $v){
				if($v == $tig){
					// Catoption already registered
					}
				else{
					$catoptions[] = $tig;
					}
				
				}
		update_option('wpemp_cat_options',$catoptions);
			}
		else{
			$catoptions[] = $tig;
			update_option('wpemp_cat_options',$catoptions);
			}
		
		//return $post_id;
	
	 }
		}
	
	function wpemp_category_filter($thelist,$separator=' ') {
	//echo 'TEST!';
	$cats = get_categories();
	$html = '<ul>';
	//die(var_dump($_SESSION));
	foreach($cats as $obj){
		
		$meta = get_option('wpemp_cat_levels_'.$obj->term_id);
		if($meta[0] == '' || $meta[0] == 'no'){
			// display the link
			$html .= '
	<li class="cat-item cat-item-'.$obj->term_id.'"><a href="'.site_url().'/?cat='.$obj->term_id.'" title="View all posts filed under '.$obj->name.'">'.$obj->name.'</a></li>';
			}
		else{
			// Is member only cat. Lets check permissions
			$allowed = explode(',',$meta[1]);
			if(!$_SESSION["wpemp_levels"]){
				// Not Logged In.
				}
			else{
				$pos = strpos($_SESSION["wpemp_levels"], ',');
				if ($pos === false) {
					// This is a single level
					if(in_array($_SESSION["wpemp_levels"],$allowed)){
						$html .= '
	<li class="cat-item cat-item-'.$obj->term_id.'"><a href="'.site_url().'/?cat='.$obj->term_id.'" title="View all posts filed under '.$obj->name.'">'.$obj->name.'</a></li>';
						}
					}
				else{
					$levels = explode(',',$_SESSION["wpemp_levels"]);
					if(array_intersect($levels,$allowed)){
						$html .= '
	<li class="cat-item cat-item-'.$obj->term_id.'"><a href="'.site_url().'/?cat='.$obj->term_id.'" title="View all posts filed under '.$obj->name.'">'.$obj->name.'</a></li>';
						}
					
					}
				}
			
			}
			
		}
		$html .= '</ul>';
		return $html;
		}
		
		
	/*Handles Short Codes.*/
	function wpemp_firstname(){
		$data = unserialize($_SESSION['wpemp_userData']);
		return $data->profile->sForename;
	}
	function wpemp_lastname(){
		$data = unserialize($_SESSION['wpemp_userData']);
		return $data->profile->sSurname;
	}
	function wpemp_email(){
		$data = unserialize($_SESSION['wpemp_userData']);
		return $data->profile->sEmail;
	}
	function wpemp_addr(){
		$data = unserialize($_SESSION['wpemp_userData']);
		return $data->profile->sAddr1;
	}
	function wpemp_addr2(){
		$data = unserialize($_SESSION['wpemp_userData']);
		return $data->profile->sAddr2;
	}
	function wpemp_addr3(){
		$data = unserialize($_SESSION['wpemp_userData']);
		return $data->profile->sAddr3;
	}
	function wpemp_city(){
		$data = unserialize($_SESSION['wpemp_userData']);
		return $data->profile->sTown;
	}
	function wpemp_state(){
		$data = unserialize($_SESSION['wpemp_userData']);
		return $data->profile->sCounty;
	}
	function wpemp_zipcode(){
		$data = unserialize($_SESSION['wpemp_userData']);
		return $data->profile->sPostcode;
	}
	function wpemp_country(){
		$data = unserialize($_SESSION['wpemp_userData']);
		return $data->profile->sCountry;
	}
	function wpemp_telephone(){
		$data = unserialize($_SESSION['wpemp_userData']);
		return $data->profile->sTelephone;
	}
	function wpemp_mobile(){
		$data = unserialize($_SESSION['wpemp_userData']);
		return $data->profile->sMobile;
	}
	
	}
$wp_EasyMemberPro = & new wp_EasyMemberPro();