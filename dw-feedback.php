<?php
/*
Plugin Name: DW Feedback
Plugin URI: http://www.danielwoolnough.com/product/dw-feedback/
Description: Lets WordPress users easily add a modal window feedback form to your WordPress site 
and view user feedback and user info on the WordPress backend.
Version: 1.0.1
Author: Daniel Woolnough
Author URI: http://www.danielwoolnough.com/
*/

global $nd_feedback_vars, $wpdb;

$nd_feedback_vars['table'] = $wpdb->prefix . 'dw_feedback';
$nd_feedback_vars['plugin_path'] = WP_PLUGIN_DIR.'/dw-feedback';
$nd_feedback_vars['plugin_url'] = WP_PLUGIN_URL.'/dw-feedback';

load_plugin_textdomain('dw', $nd_feedback_vars['plugin_url'] . '/langs/', 'dw-feedback/langs/');

# Detect Ajax
if (!function_exists('nd_is_ajax')) {
	function nd_is_ajax() {
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') return true;
		return false;
	}
}
# Init 
function nd_feedback_init_script() {
	global $nd_feedback_vars;
	if (!is_admin()) :
		wp_register_script( 'feedback_js', $nd_feedback_vars['plugin_url'] . '/js/feedback.js' , 'jquery', '1.0', true );
		wp_register_script( 'blockui', $nd_feedback_vars['plugin_url'] . '/js/blockui.js' , 'jquery', '1.0', true );
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'feedback_js' );
		wp_enqueue_script( 'blockui' );
	else :
		nd_feedback_install();
	endif;
}
add_action('init', 'nd_feedback_init_script');

function nd_feedback_init_style() {
	global $nd_feedback_vars;
	$feedbackcss = $nd_feedback_vars['plugin_url'] . '/css/default.css';
	if (file_exists(TEMPLATEPATH . '/default.css')) $feedbackcss = TEMPLATEPATH . '/default.css';
	wp_register_style('default_css', $feedbackcss);
	wp_enqueue_style( 'default_css' );
}
add_action('wp_print_styles', 'nd_feedback_init_style');

function nd_feedback_install() {
	global $wpdb, $nd_feedback_vars;
	if($wpdb->supports_collation()) {
		if(!empty($wpdb->charset)) $collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if(!empty($wpdb->collate)) $collate .= " COLLATE $wpdb->collate";
	} 	
	$sql = "CREATE TABLE IF NOT EXISTS ".$nd_feedback_vars['table']." (				
			`id`        INT UNSIGNED NOT NULL AUTO_INCREMENT, 
			`feedback`  LONGTEXT  NOT NULL ,
			`face` 		VARCHAR (200) NULL ,
			`mood`	 	VARCHAR (200) NULL ,
			`email` 	VARCHAR (200) NULL ,
			`date`  	DATETIME  NOT NULL ,
			`ip` 		VARCHAR (32) NULL ,
			`agent`   	VARCHAR (200) NULL ,
			`referrer`  VARCHAR (200) NULL ,
			PRIMARY KEY ( `id` )) $collate;";
	$wpdb->query($sql);
}
# Menus
function nd_feedback_menus() {
	global $nd_feedback_vars;
	add_menu_page(__('Feedback','dw'), __('Feedback','dw'), 'manage_options' , 'feedback' , 'nd_feedback_admin_panel', $nd_feedback_vars['plugin_url'] . '/img/feedback-icon.png');
	add_submenu_page('feedback', __('View Feedback','dw'),  __('View','dw') , 'manage_options', 'feedback' , 'nd_feedback_admin_panel');
	add_submenu_page('feedback', __('Feedback Settings','dw') , __('Settings','dw') , 'manage_options', 'feedback-settings', 'nd_feedback_admin_settings');
}
add_action('admin_menu', 'nd_feedback_menus');
# Feedback Form / Button / Modal
function nd_feedback_button() {
	global $nd_feedback_vars;
	
	$title = get_option('nd_feedback_form_title');
	$caption = get_option('nd_feedback_form_caption');
	
	?>
	<div class="auto-style1">
	<a href="#nd_feedback" class="feedback_button"><span><?php echo get_option('nd_feedback_button_text'); ?></span></a>
	<div id="nd_feedback" class="auto-style1">
		<h2 class="auto-style1">DW Feedback</h2>
		<p class="auto-style1"><strong>Hi there, Thank you for downloading this plugin. Unfortunatly, I am no longer able to maintain it anymore.
		Please remove this plugin as soon as you have found a replacement as it is/will be no longer compatable
		with the latest versions of WordPress. Please see me blog for more information. Thanks, Daniel.</strong></p>
		<?php echo wpautop(wptexturize($caption)); ?>
		<div class="feedback_messages"></div>
		<form action="#" method="post" id="feedback_form">
			<div class="auto-style1">
			<p class="auto-style1"><label for="nd_email"><?php _e('Email','dw'); ?></label> <input type="text" class="text" id="nd_email" name="nd_email" placeholder="<?php _e('Your email address', 'dw'); if (get_option('nd_feedback_email_require')=='no') _e(' (optional)', 'dw'); ?>" /></p>
			
			<p class="auto-style1"><label for="nd_input_feedback"><?php _e('Feedback','dw'); ?></label> <textarea cols="20" rows="5" id="nd_input_feedback" name="nd_input_feedback" placeholder="<?php _e('Your feedback about the site', 'dw'); ?>"></textarea></p>
			
			
			<?php if (get_option('nd_feedback_mood_field')!=="no") : ?>
				<div class="auto-style1">
				<label for="nd_mood"><?php _e('Mood','dw'); ?></label>
			
				<ul class="faces">
					<li class="auto-style1"><a href="#" rel="Happy"><?php _e('Happy', 'dw'); ?></a></li>
					<li class="auto-style1"><a href="#" rel="Amused"><?php _e('Amused', 'dw'); ?></a></li>
					<li class="auto-style1"><a href="#" rel="Indifferent"><?php _e('Indifferent', 'dw'); ?></a></li>
					<li class="auto-style1"><a href="#" rel="Sad"><?php _e('Sad', 'dw'); ?></a></li>
				</ul>
				
					<div class="auto-style1">
				
				<input type="text" class="text" name="nd_mood" id="nd_mood" placeholder="<?php _e("I'm feeling...", "dw"); ?>" /> 
				
				<input type="hidden" name="nd_face" id="nd_face" value="Indifferent" />
				 
				<span class="suggestions Indifferent"><?php _e("e.g.", "dw"); ?> <a href="#"><?php _e("Indifferent", "dw"); ?></a>, <a href="#"><?php _e("Undecided", "dw"); ?></a>, <a href="#"><?php _e("Unconcerned", "dw"); ?></a></span>
				<span class="suggestions Amused"><?php _e("e.g.", "dw"); ?><a href="#"><?php _e("Kidding", "dw"); ?></a>, <a href="#"><?php _e("Amused", "dw"); ?></a>, <a href="#"><?php _e("Silly", "dw"); ?></a></span>
				<span class="suggestions Happy"><?php _e("e.g.", "dw"); ?> <a href="#"><?php _e("Happy", "dw"); ?></a>, <a href="#"><?php _e("Confident", "dw"); ?></a>, <a href="#"><?php _e("Grateful", "dw"); ?></a></span>
				<span class="suggestions Sad"><?php _e("e.g.", "dw"); ?> <a href="#"><?php _e("Sad", "dw"); ?></a>, <a href="#"><?php _e("Dissapointed", "dw"); ?></a>, <a href="#"><?php _e("Confused", "dw"); ?></a>, <a href="#"><?php _e("Angry", "dw"); ?></a></span>
					</div>
			</div><?php endif; ?>
			
			<p class="auto-style1"><input type="submit" class="submit_feedback" value="<?php _e('Submit Feedback','dw'); ?>" /></p>
			</div>
		</form>
	</div>
	<script type="text/javascript">
	/* <![CDATA[ */
		(function($){
			$(function(){
	
				// Facebox and feedback button
				$('.feedback_button').show().facebox({
					loadingImage:	'<?php echo $nd_feedback_vars['plugin_url']; ?>/img/loading.gif',
					closeImage:		'<?php echo $nd_feedback_vars['plugin_url']; ?>/img/closelabel.png',
					faceboxHtml:'\
					    <div id="facebox" class="feedback_box" style="display:none;"> \
					      <div class="popup"> \
					        <div class="content"> \
					        </div> \
					        <a href="#" class="close"><img src="/facebox/closelabel.png" title="close" class="close_image" /></a> \
					      </div> \
					    </div>'
				});
				// Mood selectors
				$('.suggestions a').live('click', function(){
					$('input#nd_mood').val($(this).text());
					return false;
				});
				// Faces
				$('span.suggestions, input#nd_mood').hide();
				$('ul.faces li a').live('click', function() {
					$('ul.faces li a').removeClass('current');
					$(this).addClass('current');
					$('input#nd_face').val($(this).attr('rel'));
					$('span.suggestions').hide();
					$('input#nd_mood').show();
					$('span.' + $(this).attr('rel')).show();
					return false;
				});
				// Ajax submission
				$('form#feedback_form input[type=submit]').live('click', function() {
					$('.feedback_box').block({ message: null, overlayCSS: { 
				        backgroundColor: '#fff', 
				        opacity:         0.6 } 
				    });
					var form_data = $('form#feedback_form').serialize();
					$.post('<?php echo home_url('/'); ?>', form_data, function(data) {
						if (data=="SUCCESS") {
							$('form#feedback_form').slideUp();
							$('.feedback_box .feedback_messages').html('<div class="feedback_success"><?php echo __('Thanks, your feedback has been recieved.', 'dw'); ?></div>');
						} else {
							$('.feedback_box .feedback_messages').html('<div class="feedback_error">' + data + '</div>');
						}
						$('.feedback_box').unblock();
					});
					return false;
				});
				
			});
		})(jQuery);
	/* ]]> */
	</script>
	<?php
}
if (get_option('nd_feedback_enable_button')=='yes') add_action('wp_footer', 'nd_feedback_button');
#Feedback Form Processing
function nd_feedback_process_form() {
	if (!is_admin() && nd_is_ajax() && isset($_POST['nd_input_feedback'])) :
		
		global $wpdb, $nd_feedback_vars;
		
		$posted = array();
		
		$vars = array(
			'nd_mood',
			'nd_face',
			'nd_email',
			'nd_input_feedback'
		);
		foreach($vars as $var) :
			if (isset($_POST[$var])) $posted[$var] = stripslashes(trim($_POST[$var]));
			else $posted[$var] = '';
		endforeach;
		
		// Validate
		if (get_option('nd_feedback_email_require')=='yes') :
		
			if (empty($posted['nd_email']) || !is_email($posted['nd_email'])) :
				echo __('Please enter a valid email address.', 'dw');
				exit;
			endif;
		
		else :
		
			if (!empty($posted['nd_email']) && !is_email($posted['nd_email'])) :
				echo __('Please enter a valid email address.', 'dw');
				exit;
			endif;
			
		endif;
		
		if (empty($posted['nd_input_feedback'])) :
			echo __('Please enter your feedback.', 'dw');
			exit;
		endif;
		
		// User IP
		$ipAddress = '';
		if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && strtolower($_SERVER['HTTP_X_FORWARDED_FOR'])!='unknown') {
			$ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif(isset($_SERVER['HTTP_X_REAL_IP']) && strtolower($_SERVER['HTTP_X_REAL_IP'])!='unknown') {
			$ipAddress = $_SERVER['HTTP_X_REAL_IP'];
		} else {
			$ipAddress = $_SERVER['REMOTE_ADDR'];
		}
		
		// Insert into DB	
		$wpdb->insert( $nd_feedback_vars['table'], array( 
			'feedback' 	=> $posted['nd_input_feedback'], 
			'mood' 		=> $posted['nd_mood'], 
			'face' 		=> $posted['nd_face'], 
			'email' 	=> $posted['nd_email'], 
			'referrer'	=> wp_get_referer(),
			'agent'		=> $_SERVER['HTTP_USER_AGENT'],
			'ip'		=> $ipAddress,
			'date'		=> date("Y-m-d H:i:s")
			));
		
		// Notification
		$message = __('A user has submitted feedback. To view their thoughts please login to your WordPress admin panel.', 'dw');
		$message .= "\n\n".home_url('/wp-admin/admin.php?page=feedback');
		if (get_option('nd_feedback_notify_admin')!=="no") wp_mail( get_option('admin_email'), __('Feedback Received', 'dw').' ('.get_bloginfo('name').')', $message );
		
		// Success
		echo 'SUCCESS';
		exit;
		
	endif;
}
add_action('init', 'nd_feedback_process_form');
# Admin Panel
function nd_feedback_admin_panel() {
	global $wpdb, $nd_feedback_vars;
	
	// Delete Feedback
	if (isset($_GET['delete']) && $_GET['delete']>0) :
		
		$delete = (int) stripslashes($_GET['delete']);
		$wpdb->query("DELETE FROM ".$nd_feedback_vars['table']." WHERE id='".$delete."' LIMIT 1;");
		
		echo '<div id="message" class="updated fade"><p><strong>'.__('Feedback deleted successfully',"dw").'</strong></p></div>';

	endif;
	?>
	<div class="auto-style1">
		<div class="icon32" id="dw_feedback"></div>
		<h2 class="auto-style1"><?php _e('DW Feedback / Received Feedback', 'dw'); ?></h2>
		<table class="auto-style1">
			<thead>
				<tr>
					<?php if (get_option('nd_feedback_mood_field')!=="no") : ?><th scope="col" class="center"><?php _e('Mood', 'dw'); ?></th><?php endif; ?>
					<th scope="col"><?php _e('Feedback', 'dw'); ?></th>
					<th scope="col"><?php _e('Referrer', 'dw'); ?></th>
					<th scope="col"><?php _e('IP', 'dw'); ?></th>
					<th scope="col"><?php _e('User Agent', 'dw'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$limit = 20;
				
				if(!isset($_REQUEST['p'])) $page = 1; else $page = (int) $_REQUEST['p']; 

				$total_results = $wpdb->get_var("SELECT COUNT(id) FROM ".$nd_feedback_vars['table'].";");
				
				$total_pages = ceil($total_results / $limit);
				
				$from = (($page * $limit) - $limit); 
					
				$results = $wpdb->get_results("SELECT * FROM ".$nd_feedback_vars['table']." ORDER BY `date` DESC LIMIT ".$from.", ".$limit.";");
				if ($results) :
					foreach ($results as $feedback) :
						echo '<tr>';
						if (get_option('nd_feedback_mood_field')!=="no") :
							if ($feedback->face) :
								echo '<td class="mood"><div class="mood '.strtolower($feedback->face).'"></div>'.wptexturize($feedback->mood).'</td>';
							else :
								echo '<td class="mood">'.wptexturize($feedback->mood).'</td>';
							endif;
						endif;
						echo '<td>
							
							<div class="row-actions">
								<span class="delete"><a class="feedback_delete" href="admin.php?page=feedback&amp;p='.$page.'&amp;delete='.$feedback->id.'">'.__('Delete','dw').'</a></span>
							</div>
							
							<p class="poster">'.__('Feedback left ', 'dw');
						
						if (!empty($feedback->email)) echo __('by ', 'dw').'<strong><a href="mailto:'.$feedback->email.'">'.$feedback->email.'</a></strong>';
						
						echo __('on ', 'dw').date('jS M Y', strtotime($feedback->date)).'</p>'.wpautop(wptexturize($feedback->feedback)).'
						
						</td>';
						echo '<td>'.str_replace(home_url(), '', $feedback->referrer).'</td>';
						echo '<td>'.$feedback->ip.'</td>';
						echo '<td>'.$feedback->agent.'</td>';
						echo '</tr>';
					endforeach;
				else :
					echo '<tr><td colspan="8">'.__('No feedback submitted yet!', 'dw').'</td></tr>';
				endif;
				?>
			</tbody>
		</table>
		<div class="tablenav">
			<div class="auto-style1">
				<?php
					if ($total_pages>1) :
						echo paginate_links( array(
							'base' => 'admin.php?page=feedback&amp;p=%#%',
							'prev_text' => __('&laquo; Previous'),
							'next_text' => __('Next &raquo;'),
							'total' => $total_pages,
							'current' => $page,
							'end_size' => 1,
							'mid_size' => 5,
						));
					endif;				
				?>
				<div class="clear"></div>
			</div>
		</div>
		<script type="text/javascript">
		/* <![CDATA[ */
			jQuery('.feedback_delete').click(function(){
				if ( confirm('<?php echo js_escape(__("You are about to delete this feedback.\n  'Cancel' to stop, 'OK' to delete.", 'dw')); ?>') ) return true;
				return false;			
			});
		/* ]]> */
		</script>
	</div>
	<?php
}

# Admin Settings

global $nd_feedback_options;

$nd_feedback_options = (
	array( 
		array('General Settings', array(
			array('nd_feedback_enable_button', 'yes', __('Enable Feedback button?', 'dw') ,'', 'yesno'),
			array('nd_feedback_notify_admin', 'yes', __('Notify admin of new feedback?', 'dw') ,'', 'yesno'),
			array('nd_feedback_mood_field', 'yes', __('Show "mood" fields?', 'dw') ,'', 'yesno'),
			array('nd_feedback_notify_admin', 'yes', __('Notify admin of new feedback?', 'dw') ,'', 'yesno'),
			array('nd_feedback_button_text', __('Feedback', 'dw'), __('Feedback button text', 'dw'), __('Text shown in the feedback button.', 'dw'), ''),
			)
		),
		array('Feedback Form Settings', array(
			array('nd_feedback_form_title', get_bloginfo('name') . __(' Feedback', 'dw'), 'Form title','Heading shown on the feedback form.', ''),
			array('nd_feedback_form_caption', __('We appreciate any and all feedback about our site; praise, ideas, bug reports you name it!', 'dw'), 'Form caption','Text shown beneath the feedback form title.','textarea'),
			array('nd_feedback_email_require', 'yes', __('Email Address required?', 'dw') ,'', 'yesno'),
			)
		)
	)
);
	
foreach($nd_feedback_options as $section) {
	foreach($section[1] as $option) {
		add_option($option[0], $option[1]);
	}
}

function nd_feedback_admin_css() {
	global $nd_feedback_vars;
	?><style type="text/css">
		div#dw_feedback {
			background: url(<?php echo $nd_feedback_vars['plugin_url']; ?>/img/feedback-icon-big.png) no-repeat;
		}
		#dw_feedback_form h3 {
			background: #E3E3E3;
			padding: 12px;
			margin-bottom: 0 !important
		}
		#dw_feedback_form .dw_feedback_section {
			border: 1px solid #E3E3E3;
			padding: 0 6px
		}
		#dw_feedback_form table {				
			margin-top: 0 !important;
			border-collapse: collapse;
			border-bottom: 2px solid #F9F9F9;
		}
		#dw_feedback_form table td, #dw_feedback_form table th {
			padding: 12px 6px;
			border-bottom: 1px solid #E3E3E3
		}
		.dw_feedback_table td {
			vertical-align: top;
		}
		.dw_feedback_table th.center {
			text-align: center;
		}
		.dw_feedback_table td.mood {
			text-align: center;
			color: #999;
		}
		.dw_feedback_table .poster {
			color: #999
		}
		.dw_feedback_table .row-actions {
			float: right;
		}
		div.mood {
			height: 0;
			width: 42px;
			display: block;
			margin: 2px auto;
			padding: 42px 0 0 0;
			overflow: hidden;
			zoom: 1;
			background: #ccc url(<?php echo $nd_feedback_vars['plugin_url']; ?>/img/coolfaces.png) no-repeat center top;
		}
		div.happy { background-position: 0 0; background-color: #ffdb4c; }
		div.indifferent { background-position: 0 -42px; background-color: #ccc; }
		div.amused { background-position: 0 -84px; background-color: #41b6db; }
		div.sad { background-position: 0 -126px; background-color: #ff4c4c; }
	.auto-style1 {
	text-align: center;
}
	</style><?php
}
add_action('admin_head', 'nd_feedback_admin_css');
	
function nd_feedback_admin_settings() {

	global $nd_feedback_options;

	if ($_POST['save_dw_feedback_options']) {
		foreach($nd_feedback_options as $section) {
			foreach($section[1] as $option) {
				update_option($option[0],stripslashes($_POST[$option[0]]));
			}
		}
		echo '<div id="message" class="updated fade"><p><strong>'.__('Options Saved', 'dw').'</strong></p></div>';
	}
	?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"></div>
		<h2 class="auto-style1"><?php _e('DW Feedback / Options', 'dw'); ?></h2>
		<form method="post" action="admin.php?page=feedback-settings" id="dw_feedback_form">
			<div class="auto-style1">
			<?php	
			foreach($nd_feedback_options as $section) {
				echo '<h3>'.$section[0].'</h3><div class="dw_feedback_section"><table cellspacing="0" cellpadding="0" class="form-table">';
				foreach($section[1] as $option) {
					echo '<tr valign="top">';
					
					echo '<th><label for="'.$option[0].'">'.$option[2].'</label></th><td>';
					
					if ($option[4]=='yesno') {
						$yes = '';
						$no = '';
						if (get_option($option[0])=='yes') $yes='selected="selected"'; else $no='selected="selected"';
						echo '<select name="'.$option[0].'">
							<option value="yes" '.$yes.'>'.__('Yes', 'dw').'</option>
							<option value="no" '.$no.'>'.__('No', 'dw').'</option>
						</select>';
					} elseif ($option[4]=='textarea') {
						echo '<textarea id="'.$option[0].'" name="'.$option[0].'" cols="50" rows="6">'.get_option($option[0]).'</textarea>';
					} else {
						echo '<input type="text" id="'.$option[0].'" name="'.$option[0].'" size="25" value="'.get_option($option[0]).'" />';
					}
					
					if ($option[3]) echo '<br/><span class="setting-description">'.$option[3].'</span>';
					
					echo '</td></tr>';
				}
				echo '</table></div><br class="clear" />';
			}
			?>
			<p class="auto-style1"><input type="submit" class="button-primary" value="<?php _e('Save Changes', 'dw'); ?>" name="save_dw_feedback_options" /></p>
			</div>
		</form>
	</div>
	</div>

	<?php
}