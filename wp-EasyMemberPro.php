<?php
/*
Plugin Name: wp-EasyMemberPro!
Plugin URI: http://www.easymemberpro.com
Description: An Extension to Easy Member Pro, Allowing the Viewing of Pages and Posts Based on Membership Levels.
Version: 1.0.2
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
		define("WPEMP_VER"		, "1.0.1" );

		register_activation_hook   ( WPEMP_FILE	, array( &$this, 'wpemp_activate'		)); 
		register_deactivation_hook ( WPEMP_FILE	, array( &$this, 'wpemp_deactivate'		)); // not required //

		$this->message 			= array();
		$this->err	 			= array();

		add_action( 'init'				, array( &$this, 'wpemp_session'		), 0);
		add_action( 'init'				, array( &$this, 'wpemp_login'		), 1);
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
	}

	function wpemp_activate(){
		update_option ("wp_wpemp_ver", WPEMP_VER);
		update_option ("wpemp_show_powered", "yes");

		// check if fopen is disabled //
		ini_set('allow_url_fopen','1');
		$old_ua = @ ini_get('user_agent');
		@ ini_set('user_agent','Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3');
		$f = @ fopen('http://www.msn.com', "r");
		if( ! $f )
			update_option ("wp_wpemp_fopen", 'off');
		else
		{
			@fclose($f);
			update_option ("wp_wpemp_fopen", 'on');
		} 
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

	function wpemp_actions($links, $file){
		if( strpos( $file, basename(__FILE__)) !== false )
		{
			$link = '<a href="options-general.php?page=wpemp_options">'.__('Settings').'</a>';
			array_unshift( $links, $link );
		}
		return $links;
	}
	
	function wpemp_content($content = ''){
		if(get_option('wpemp_url') == '' && (get_option('wpemp_show_post') == 'yes' || get_option('wpemp_show_page') == 'yes'))
			return $content . '<div id="wpemp_message"><strong>'.__('EasyMemberPro URL Required').'</strong><br/>'.__('Please goto settings page and set the URL to EasyMemberPro').'</div>';

		global $post;
		$post_option = get_post_meta($post->ID, "_wpemp_dropdown", true);
		$post_level = get_post_meta($post->ID, "_wpemp_levels", true);
		$post_option = $post_option?$post_option:'none';
		$post_excerpt = get_post_meta($post->ID, "_wpemp_excerpt", true);
		
		
		$userlevels = $_SESSION['wpemp_levels'];
		//die(var_dump($_SESSION));
		$pos = strpos($userlevels, ',');
		if ($pos === false) {}
		else{$userlevels = explode(',',$userlevels);}
		
		$pos = strpos($post_level, ',');
		if ($pos === false) {}
		else{$post_level = explode(',',$post_level);}
		$approved = 0;
		if(is_array($post_level)){
			foreach($post_level as $v){
				if(is_array($userlevels)){
					if(in_array($v,$userlevels)){
						$approved = 1;
						break;
						}
					else{$approved = 0;}
				}
				else{
					if($v == $userlevels){$approved = 1;break;}
					else{$approved = 0;}
				}
			}
		}
		else{
			if(is_array($userlevels)){
				foreach($userlevels as $v){
					if($v == $post_level){$approved = 1;break;}
					else{$approved = 0;}
					}
				}
			else{
				if($userlevels == $post_level){$approved = 1;}
				else{$approved = 0;}	
			}	
		}
		
		
		

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
		else if( $post_option == 'yes' && !isset( $_SESSION['sUsername'] ) )
		{
			$scontent = '';
			if($post_excerpt == 'yes')
			{
				remove_filter('the_content', array( &$this, 'wpemp_content'), 100);
				$scontent = get_the_excerpt();
				add_filter('the_content', array(&$this, 'wpemp_content'), 100);
			}
			//die($scontent. $this->wpemp_get_login_box($post->ID));
			return $scontent. $this->wpemp_get_login_box($post->ID);
		}
		else if( $post_option == 'yes' && isset( $_SESSION['sUsername'] ) )
			if($approved == 0){
				$scontent = '';
			if($post_excerpt == 'yes')
			{
				remove_filter('the_content', array( &$this, 'wpemp_content'), 100);
				$scontent = get_the_excerpt();
				add_filter('the_content', array(&$this, 'wpemp_content'), 100);
			}
			
			return $scontent. $this->wpemp_get_upgrade_box($post->ID);
				}
			else{return $content. '<div id="logout" align="right">'.$this->wpemp_get_logout_link().'</div>';}

		else
			return $content;
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
		if( get_option ("wp_wpemp_fopen") == 'off' )
			return $this->wpemp_get_login_link(true);
		

		// we have fopen, yay .... now we show them a form.//
		$html  = '<div class="wpemp_message" id="wpemp_message-'.$post_id.'">';
		$html .= '<p class="wpemp_p"><strong>'.__('This content is for '.get_option('wpemp_name').' members only.').'</strong></p>'."\n";

		$html .= '<div id="wpemp_login_div-'.$post_id.'" style="display:none">'."\n";
		$html .= '<form id="wpemp_login_form-'.$post_id.'" class="wpemp_login_form" name="wpemp_login_form" action="'.get_option("siteurl").'/?wpemp=login" method="post">'."\n";
		$html .= '<input type="hidden" name="ajax_nonce" id="ajax_nonce-'.$post_id.'" value="'.wp_create_nonce( 'wpemp_ajax' ).'"/>'."\n";
		$html .= '<p class="wpemp_p">'."\n";
		$html .= '	<label for="wpemp_user">'.__('Username').': </label><br/>'."\n";
		$html .= '	<input name="wpemp_user" id="wpemp_user-'.$post_id.'" class="wpemp_user" value="" title="User Name"/>'."\n";
		$html .= '</p>'."\n";
		$html .= '<p class="wpemp_p">'."\n";
		$html .= '	<label for="wpemp_pass">'.__('Password').': </label><br/>'."\n";
		$html .= '	<input name="wpemp_pass" id="wpemp_pass-'.$post_id.'" class="wpemp_pass" value="" type="password" title="'.__('Password').'"/>'."\n";
		$html .= '</p>'."\n";
		$html .= '<p class="wpemp_pl">'."\n";
		$html .= '	<span id="wpemp_login_note-'.$post_id.'" class="wpemp_login_note">&nbsp;</span>'."\n";
		$html .= '</p>'."\n";
		$html .= '<p class="wpemp_pr">'."\n";
		$html .= '	<img src="' . WPEMP_URL . 'css/loader.gif" id="wpemp_loader-'.$post_id.'" class="wpemp_loader" alt="Loading..." style="display:none"/>'."\n";
		$html .= '	<input value="Login" name="wpemp_submit" id="wpemp_submit-'.$post_id.'" class="wpemp_submit" type="submit" />'."\n";
		$html .= '</p>'."\n";
		$html .= '</form></div>'."\n";

		$html .= '<p class="wpemp_p">'."\n";
		$html .= '<a href="'.$this->wpemp_get_singup_link().'" id="wpemp_link_register-'.$post_id.'" title="'.__('Register').'" target="_blank">'.__('Become a Member').'</a> | '."\n";
		$html .= '<a href="'.trim(get_option('wpemp_url'),'/').'/index.php?page=login" id="wpemp_link_login-'.$post_id.'" class="wpemp_link_login" title="'.__('Login').'">'.__('Login').'</a>'."\n";
		$html .= '<a href="'.trim(get_option('wpemp_url'),'/').'/index.php?page=forgot" id="wpemp_link_forgot-'.$post_id.'" title="'.__('Forgot Password').'" style="display:none;" target="_blank"> | '.__('Forgot Password').'</a><br/>'."\n";
		if(get_option('wpemp_show_powered') == 'yes')
		{
			$html .= '<span class="wpemp_small">Powered By <a href="'.$this->wpemp_get_aff_link().'" ';
			$html .= ' target="_blank" title="Powered by EasyMemberPro">EasyMemberPro</a></span>'."\n";
		}
		$html .= '</p>'."\n";
		$html .= '</div>'."\n";
		$html .= '<style type="text/css">.postmetadata, .entry-utility {display: none;}</style>'."\n";
		//die($html);
		return $html;
	}
	
	function wpemp_get_upgrade_box($post_id){
		
		$levelids = get_post_meta($post_id, "_wpemp_levels", true);
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
			
		$pos = strpos($levelids, ',');
		if ($pos === false) {}
		else{$setLevels = explode(',',$setLevels);}
		
		
		$html  = '<div class="wpemp_message" id="wpemp_message-'.$post_id.'">';
		$html .= '<p class="wpemp_p">
		<strong>'.__('This content is for '.get_option('wpemp_name').' members with the following memberships only.').'</strong>'."<br />\n";
		$pos = strpos($levelids, ',');
		if ($pos === false) {$html .=$levelList[$levelids];}
		else{
			$ids = explode(',',$levelids);
			foreach($ids as $v){
				$html .= $levelList[$v].'<br />';
				}
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
	
	function wpemp_login(){
		if( isset($_GET['wpemp']) && trim($_GET['wpemp']) == 'logout' )
		{
			unset($_SESSION['sUsername']);
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
		if($auth_res == '0')
		{
			if(defined('DOING_AJAX'))
				die("Username or password incorrect");
			else
				wp_die("Username or password incorrect", get_bloginfo( 'name' ) . " &raquo; EasyMemberPro");
		}
		else
		{
			session_start();
			$_SESSION['sUsername'] = $wpemp_user;
			$_SESSION['wpemp_levels'] = $auth_res;
			
			if(defined('DOING_AJAX'))
				die('1');
		}
		die();
	}
	
	function wpemp_session(){
		session_start();
	}

	function wpemp_comments_template($attr){
		if(!isset( $_SESSION['sUsername'] ) )
			$attr = WPEMP_DIR . "wpemp_comments.php";
		return $attr;
	}

	/*function wpemp_page_custom_box($post) {
		$wpemp_options = array('none'=> 'Default [Global Settings]', 'yes'=>'Visible to Members only', 'no'=>'Visible to Everyone');

		$html = '<input type="hidden" name="wpemp_nonce" id="wpemp_nonce" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
		$html .= '<table border="0" width="100%" style="text-align:left">';

		if(get_option('wpemp_show_page') == 'yes')
			$html .= '<tr><th colspan="2">'.__('By default, pages are currently visible only to test members').'</th></tr>';
		else
			$html .= '<tr><th colspan="2">'.__('By default, pages are currently visible to everybody.').'</th></tr>';

		$html .= '<tr><td> &raquo; <label for="wpemp_visibility">' . __("Set Pages visibility with wp-EasyMemberPro") . '</label></td>';
		$html .= '<td><select name="wpemp_dropdown" id="wpemp_dropdown">';
		foreach($wpemp_options as $id => $opt)
		{
			$html .= '<option value="'.$id.'" '. selected($id, get_post_meta($post->ID, '_wpemp_dropdown', true)).'>'.$opt.'</option>';
		}
		$html .= '</select>';
		$html .= '</td></tr>';
		$levelstring = get_option('wpemp_member_levels');
		$levelarray = explode(',',$levelstring);
		$levelcount = count($levelarray);
		//die(var_dump($levelcount));
if($levelcount > 0){
	foreach($levelarray as $v){
	$level = explode(':',$v);

$html .='<input name="levelIds[]" type="checkbox" value="'.$level[0].'" />'.$level[1].'&nbsp;';

	}
}
		$html .= '<tr><td> &raquo; <label for="wpemp_excerpt">' . __("Show Post Excerpts") . '</label></td>';
		$html .= '<td><input type="checkbox" name="wpemp_excerpt" id="wpemp_excerpt" value="yes" '. checked(get_post_meta($post->ID, '_wpemp_excerpt', true), 'yes').'/> </td></tr></table>';
		echo $html;
	}*/
	
	function wpemp_page_custom_box($post) {
		
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
		$setLevels = get_post_meta($post->ID, '_wpemp_levels', true);
		//die($setLevels);
		$pos = strpos($setLevels, ',');
		if ($pos === false) {}
		else{$setLevels = explode(',',$setLevels);}
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

$html .='<input name="wpemp_levels[]" type="checkbox" '.$checked.' value="'.$level[0].'" />&nbsp;'.$level[1].'&nbsp;';

	}
}
		$html .='</div>';
		$html .= '</td></tr>';
		$html .= '<tr><td width="35%"> &raquo; <label for="wpemp_excerpt">' . __("Show Post Excerpts") . '</label></td>';
		$html .= '<td width="65%"><input type="checkbox" name="wpemp_excerpt" id="wpemp_excerpt" value="yes" '. checked(get_post_meta($post->ID, '_wpemp_excerpt', true), 'yes', false).'/></td></tr></table>';
		echo $html;
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
		$levelcount = count($levelarray);
		$setLevels = get_post_meta($post->ID, '_wpemp_levels', true);
		//die($setLevels);
		$pos = strpos($setLevels, ',');
		if ($pos === false) {}
		else{$setLevels = explode(',',$setLevels);}
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
		$html .='<div id="allLevels">';
		
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

$html .='<input name="wpemp_levels[]" type="checkbox" '.$checked.' value="'.$level[0].'" style="'.$display.'"/>&nbsp;'.$level[1].'&nbsp;';

	}
}
		$html .='</div>';
		$html .= '</td></tr>';
		$html .= '<tr><td width="35%"> &raquo; <label for="wpemp_excerpt">' . __("Show Post Excerpts") . '</label></td>';
		$html .= '<td width="65%"><input type="checkbox" name="wpemp_excerpt" id="wpemp_excerpt" value="yes" '. checked(get_post_meta($post->ID, '_wpemp_excerpt', true), 'yes', false).'/></td></tr></table>';
		echo $html;
	}

	function wpemp_add_custom_box() {
		if( function_exists( 'add_meta_box' )) {
			foreach (array('post','page') as $type) 
				add_meta_box( 'wpemp_section', __( 'wp-EasyMemberPro Options'), array( &$this, 'wpemp_'.$type.'_custom_box'), $type, 'normal', 'high' );
		}
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
		update_post_meta( $post_id, '_wpemp_levels', $wpemp_levels);
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
	
	}
$wp_EasyMemberPro = & new wp_EasyMemberPro();