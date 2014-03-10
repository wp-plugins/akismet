<?php

class Akismet_Admin {

	var $notices = array();

	const NONCE = 'akismet-update-key';
	
	/**
	 * Holds the singleton instance of this class
	 * @var Akismet
	 */
	static $instance = false;

	/**
	 * Singleton
	 * @static
	 */
	public static function init() {
		if ( ! self::$instance ) {
			self::$instance = new Akismet_Admin;
		}

		return self::$instance;
	}
	
	/**
	 * Constructor.  Initializes WordPress hooks
	 */
	private function Akismet_Admin() {			
		$this->init_hooks();
		
		if ( isset( $_POST['action'] ) && $_POST['action'] == 'enter-key' ) {
			$this->enter_api_key();			
		}
	}
	
	public function init_hooks() {
		// The standalone stats page was removed in 3.0 for an all-in-one config and stats page.
		// Redirect any links that might have been bookmarked or in browser history.
		if ( isset( $_GET['page'] ) && 'akismet-stats-display' == $_GET['page'] ) {
			wp_safe_redirect( admin_url( 'admin.php?page=akismet-key-config&view=stats' ), 301 );
			die;
		}
		
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 5 ); # Priority 5, so it's called before Jetpack's admin_menu.
		add_action( 'admin_notices', array( $this, 'display_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_resources' ) );		
		add_action( 'activity_box_end', array( $this, 'dashboard_stats' ) );		
		add_action( 'rightnow_end', array( $this, 'rightnow_stats' ) );		
		add_action( 'manage_comments_nav', array( $this, 'check_for_spam_button' ) );
		add_action( 'transition_comment_status', array( $this, 'transition_comment_status' ), 10, 3 );		
		add_action( 'admin_action_akismet_recheck_queue', array( $this, 'recheck_queue' ) );
		add_action( 'wp_ajax_akismet_recheck_queue', array( $this, 'recheck_queue' ) );
		add_action( 'wp_ajax_comment_author_deurl', array( $this, 'remove_comment_author_url' ) ); 
		add_action( 'wp_ajax_comment_author_reurl', array( $this, 'add_comment_author_url' ) ); 
		
		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
		add_filter( 'comment_row_actions', array( $this, 'comment_row_action' ), 10, 2 );
		add_filter( 'comment_text', array( $this, 'text_add_link_class' ) );
	}
	
	function admin_init() {
		add_meta_box( 'akismet-status', __('Comment History'), array( $this, 'comment_status_meta_box' ), 'comment', 'normal' );
	}
	
	function admin_menu() {
		if ( class_exists( 'Jetpack' ) )
			add_action( 'jetpack_admin_menu', array( $this, 'load_menu' ) );
		else
			$this->load_menu();
	}

	function admin_head() {
		if ( !current_user_can( 'manage_options' ) )
			return;
	}
	
	function load_menu() {
		if ( class_exists( 'Jetpack' ) )
			$hook = add_submenu_page( 'jetpack', __( 'Akismet' ), __( 'Akismet' ), 'manage_options', 'akismet-key-config', array( $this, 'display_page' ) );
		else 
			$hook = add_options_page( __('Akismet'), __('Akismet'), 'manage_options', 'akismet-key-config', array( $this, 'display_page' ) );		
		
		if ( version_compare( $GLOBALS['wp_version'], '3.3', '>=' ) ) {
			add_action( "load-$hook", array( $this, 'admin_help' ) );
		}
	}
	
	public function load_resources() {
		global $hook_suffix;
	
		if ( in_array( $hook_suffix, array( 
			'index.php', # dashboard
			'edit-comments.php',
			'comment.php',
			'post.php',
			'settings_page_akismet-key-config',
			'jetpack_page_akismet-key-config',
		) ) ) {
			wp_register_style( 'akismet.css', AKISMET__PLUGIN_URL . '_inc/akismet.css', array(), AKISMET_VERSION );
			wp_enqueue_style( 'akismet.css');
		
			wp_register_script( 'akismet.js', AKISMET__PLUGIN_URL . '_inc/akismet.js', array('jquery','postbox'), AKISMET_VERSION );
			wp_enqueue_script( 'akismet.js' );
			wp_localize_script( 'akismet.js', 'WPAkismet', array(
				'comment_author_url_nonce' => wp_create_nonce( 'comment_author_url_nonce' )
			) );
		}
	}
	
	/**
	 * Add help to the Akismet page
	 *
	 * @return false if not the Akismet page
	 */
	function admin_help() {
		$current_screen = get_current_screen();

		// Screen Content
		if ( current_user_can( 'manage_options' ) ) {
			if ( !Akismet::get_api_key() || ( isset( $_GET['view'] ) && $_GET['view'] == 'start' ) ) {
				//setup page
				$current_screen->add_help_tab(
					array(
						'id'		=> 'overview',
						'title'		=> __( 'Overview' ),
						'content'	=>
							'<p><strong>' . __( 'Akismet Setup' ) . '</strong></p>' .
							'<p>' . __( 'Akismet filters out your comment and track-back spam for you, so you can focus on more important things.' ) . '</p>' .
							'<p>' . __( 'On this page, you are able to setup the Akismet plugin.' ) . '</p>',
					)
				);
				
				$current_screen->add_help_tab(
					array(
						'id'		=> 'setup-signup',
						'title'		=> __( 'New to Akismet' ),
						'content'	=>
							'<p><strong>' . __( 'Akismet Setup' ) . '</strong></p>' .
							'<p>' . __( 'You need to enter an API key to activate the Akismet service on your site.' ) . '</p>' .
							'<p>' . sprintf( __( 'Signup for an account on <a href="%s" target="%s">Akismet.com</a> to get an API Key.' ), 'https://akismet.com/plugin-signup/', '_blank' ) . '</p>',
					)
				);
				
				$current_screen->add_help_tab(
					array(
						'id'		=> 'setup-manual',
						'title'		=> __( 'Enter an API Key' ),
						'content'	=>
							'<p><strong>' . __( 'Akismet Setup' ) . '</strong></p>' .
							'<p>' . __( 'If you already have an API key' ) . '</p>' .
							'<ol>' .
								'<li>' . __( 'Copy and paste the API key into the text field.' ) . '</li>' .
								'<li>' . __( 'Click the Use this Key button.' ) . '</li>' .
							'</ol>',
					)
				);
			}
			elseif ( ( isset( $_GET['view'] ) && $_GET['view'] == 'stats' ) || ( isset( $_GET['page'] ) && $_GET['page'] == 'akismet-stats-display' ) ) {
				//stats page
				$current_screen->add_help_tab(
					array(
						'id'		=> 'overview',
						'title'		=> __( 'Overview' ),
						'content'	=>
							'<p><strong>' . __( 'Akismet Stats' ) . '</strong></p>' .
							'<p>' . __( 'Akismet filters out your comment and track-back spam for you, so you can focus on more important things.' ) . '</p>' .
							'<p>' . __( 'On this page, you are able to view stats on spam filtered on your site.' ) . '</p>',
					)
				);
			}
			else {
				//configuration page
				$current_screen->add_help_tab(
					array(
						'id'		=> 'overview',
						'title'		=> __( 'Overview' ),
						'content'	=>
							'<p><strong>' . __( 'Akismet Configuration' ) . '</strong></p>' .
							'<p>' . __( 'Akismet filters out your comment and track-back spam for you, so you can focus on more important things.' ) . '</p>' .
							'<p>' . __( 'On this page, you are able to enter/remove an API key, view account information and view spam stats.' ) . '</p>',
					)
				);
				
				$current_screen->add_help_tab(
					array(
						'id'		=> 'settings',
						'title'		=> __( 'Settings' ),
						'content'	=>
							'<p><strong>' . __( 'Akismet Configuration' ) . '</strong></p>' .
							'<p><strong>' . __( 'API Key' ) . '</strong> - ' . __( 'Enter/remove an API key.' ) . '</p>' .
							'<p><strong>' . __( 'Delete spam on posts more than a month old' ) . '</strong> - ' . __( 'Automatically delete spam comments on posts that are older than a month old.' ) . '</p>' .
							'<p><strong>' . __( 'Show the number of approved comments beside each comment author' ) . '</strong> - ' . __( 'Show the number of approved comments beside each comment author in the comments list page.' ) . '</p>',
					)
				);
				
				$current_screen->add_help_tab(
					array(
						'id'		=> 'account',
						'title'		=> __( 'Account' ),
						'content'	=>
							'<p><strong>' . __( 'Akismet Configuration' ) . '</strong></p>' .
							'<p><strong>' . __( 'Subscription Type' ) . '</strong> - ' . __( 'The Akismet subscription plan' ) . '</p>' .
							'<p><strong>' . __( 'Status' ) . '</strong> - ' . __( 'The subscription status - active, cancelled or suspended' ) . '</p>',
					)
				);
			}
		}

		// Help Sidebar
		$current_screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p><a href="https://akismet.com/faq/" target="_blank">'     . __( 'Akismet FAQ' ) . '</a></p>' .
			'<p><a href="https://akismet.com/support/" target="_blank">' . __( 'Akismet Support' ) . '</a></p>'
		);
	}
	
	public function enter_api_key() {		
		if ( function_exists('current_user_can') && !current_user_can('manage_options') )
			die(__('Cheatin&#8217; uh?'));
			
		if ( !wp_verify_nonce( $_POST['_wpnonce'], self::NONCE ) )
			return false;		
		
		foreach( array( 'akismet_discard_month', 'akismet_show_user_comments_approved' ) as $option ) {
			update_option( $option, isset( $_POST[$option] ) ? 'true' : 'false' );
		}
			
		if ( defined( 'WPCOM_API_KEY' ) )
			return false; //shouldn't have option to save key if already defined		
			
		$new_key = preg_replace( '/[^a-h0-9]/i', '', $_POST['key'] );
		$old_key = Akismet::get_api_key();
			
		if ( empty( $new_key ) ) {
			if ( !empty( $old_key ) ) {
				delete_option( 'wordpress_api_key' );		
				$this->notices[] = 'new-key-empty';
			}
		}  
		elseif ( $new_key != $old_key ) {
			$this->save_key( $new_key );
		}
		
		return true;
	}
	
	public function save_key( $api_key ) {
		$key_status = Akismet::verify_key( $api_key );
			
		if ( $key_status == 'valid' ) {
			update_option( 'wordpress_api_key', $api_key );
			$this->notices[] = 'new-key-valid';
		}
		elseif ( in_array( $key_status, array( 'invalid', 'failed' ) ) )
			$this->notices[] = 'new-key-'.$key_status;
	}
	
	public function dashboard_stats() {
		if ( !function_exists('did_action') || did_action( 'rightnow_end' ) ) 
			return; // We already displayed this info in the "Right Now" section
			
		if ( !$count = get_option('akismet_spam_count') )
			return;
		
		global $submenu;
		
		echo '<h3>' . _x( 'Spam', 'comments' ) . '</h3>';		
		
		$link = isset( $submenu['edit-comments.php'] ) ? 'edit-comments.php' : 'edit.php';
		
		echo '<p>'.sprintf( _n( 
				'<a href="%1$s">Akismet</a> has protected your site from <a href="%2$s">%3$s spam comments</a>.', 
				'<a href="%1$s">Akismet</a> has protected your site from <a href="%2$s">%3$s spam comments</a>.', 
				$count 
			), 'http://akismet.com/?return=true', clean_url("$link?page=akismet-admin"), number_format_i18n($count) ).'</p>';
	}
	
	// WP 2.5+
	public function rightnow_stats() {
		global $submenu, $wp_db_version;
	
		if ( 8645 < $wp_db_version  ) // 2.7
			$link = 'edit-comments.php?comment_status=spam';
		elseif ( isset( $submenu['edit-comments.php'] ) )
			$link = 'edit-comments.php?page=akismet-admin';
		else
			$link = 'edit.php?page=akismet-admin';
	
		if ( $count = get_option('akismet_spam_count') ) {
			$intro = sprintf( _n(
				'<a href="%1$s">Akismet</a> has protected your site from %2$s spam comment already. ',
				'<a href="%1$s">Akismet</a> has protected your site from %2$s spam comments already. ',
				$count
			), 'http://akismet.com/?return=true', number_format_i18n( $count ) );
		} else {
			$intro = sprintf( __('<a href="%1$s">Akismet</a> blocks spam from getting to your blog. '), 'http://akismet.com/?return=true' );
		}
	
		$link = function_exists( 'esc_url' ) ? esc_url( $link ) : clean_url( $link );
		if ( $queue_count = Akismet_Admin::get_spam_count() ) {
			$queue_text = sprintf( _n(
				'There\'s <a href="%2$s">%1$s comment</a> in your spam queue right now.',
				'There are <a href="%2$s">%1$s comments</a> in your spam queue right now.',
				$queue_count
			), number_format_i18n( $queue_count ), $link );
		} else {
			$queue_text = sprintf( __( "There's nothing in your <a href='%1\$s'>spam queue</a> at the moment." ), $link );
		}
	
		$text = $intro . '<br />' . $queue_text;
		echo "<p class='akismet-right-now'>$text</p>\n";
	}
	
	public function check_for_spam_button( $comment_status ) {
		if ( 'approved' == $comment_status )
			return;
			
		if ( function_exists('plugins_url') )
			$link = 'admin.php?action=akismet_recheck_queue';
		else
			$link = 'edit-comments.php?page=akismet-admin&recheckqueue=true&noheader=true';
			
		echo '</div><div class="alignleft"><a class="button-secondary checkforspam" href="' . $link . '">' . __('Check for Spam') . '</a>';
		echo '<img src="' . esc_url( admin_url( 'images/wpspin_light.gif' ) ) . '" class="checkforspam-spinner" />';
	}
	
	public function transition_comment_status( $new_status, $old_status, $comment ) {
		if ( $new_status == $old_status )
			return;
	
		# we don't need to record a history item for deleted comments
		if ( $new_status == 'delete' )
			return;
			
		if ( !is_admin() )
			return;
			
		if ( !current_user_can( 'edit_post', $comment->comment_post_ID ) && !current_user_can( 'moderate_comments' ) )
			return;
	
		if ( defined('WP_IMPORTING') && WP_IMPORTING == true )
			return;
	
		// if this is present, it means the status has been changed by a re-check, not an explicit user action
		if ( get_comment_meta( $comment->comment_ID, 'akismet_rechecking' ) )
			return;
			
		global $current_user;
		$reporter = '';
		if ( is_object( $current_user ) )
			$reporter = $current_user->user_login;
		
		// Assumption alert:
		// We want to submit comments to Akismet only when a moderator explicitly spams or approves it - not if the status
		// is changed automatically by another plugin.  Unfortunately WordPress doesn't provide an unambiguous way to
		// determine why the transition_comment_status action was triggered.  And there are several different ways by which
		// to spam and unspam comments: bulk actions, ajax, links in moderation emails, the dashboard, and perhaps others.
		// We'll assume that this is an explicit user action if POST or GET has an 'action' key.
		if ( isset($_POST['action']) || isset($_GET['action']) ) {
			if ( $new_status == 'spam' && ( $old_status == 'approved' || $old_status == 'unapproved' || !$old_status ) ) {
				return Akismet_Admin::submit_spam_comment( $comment->comment_ID );
			} elseif ( $old_status == 'spam' && ( $new_status == 'approved' || $new_status == 'unapproved' ) ) {
				return Akismet_Admin::submit_nonspam_comment( $comment->comment_ID );
			}
		}
		
		Akismet::update_comment_history( $comment->comment_ID, sprintf( __('%s changed the comment status to %s'), $reporter, $new_status ), 'status-' . $new_status );
	}
	
	public function recheck_queue() {
		global $wpdb;
	
		Akismet::fix_scheduled_recheck();
	
		if ( ! ( isset( $_GET['recheckqueue'] ) || ( isset( $_REQUEST['action'] ) && 'akismet_recheck_queue' == $_REQUEST['action'] ) ) )
			return;
			
		$paginate = ''; 
		if ( isset( $_POST['limit'] ) && isset( $_POST['offset'] ) ) { 
			$paginate = $wpdb->prepare( " LIMIT %d OFFSET %d", array( $_POST['limit'], $_POST['offset'] ) ); 
	 	} 
	 	$moderation = $wpdb->get_results( "SELECT * FROM {$wpdb->comments} WHERE comment_approved = '0'{$paginate}", ARRAY_A );
	 	
		foreach ( (array) $moderation as $c ) {
			$c['user_ip']      = $c['comment_author_IP'];
			$c['user_agent']   = $c['comment_agent'];
			$c['referrer']     = '';
			$c['blog']         = get_bloginfo('url');
			$c['blog_lang']    = get_locale();
			$c['blog_charset'] = get_option('blog_charset');
			$c['permalink']    = get_permalink($c['comment_post_ID']);
	
			$c['user_role'] = '';
			if ( isset( $c['user_ID'] ) )
				$c['user_role'] = Akismet::get_user_roles($c['user_ID']);
	
			if ( Akismet::is_test_mode() )
				$c['is_test'] = 'true';
	
			add_comment_meta( $c['comment_ID'], 'akismet_rechecking', true );
			
			$response = Akismet::http_post( http_build_query( $c ), 'comment-check' );
			if ( 'true' == $response[1] ) {
				wp_set_comment_status( $c['comment_ID'], 'spam' );
				update_comment_meta( $c['comment_ID'], 'akismet_result', 'true' );
				delete_comment_meta( $c['comment_ID'], 'akismet_error' );
				Akismet::update_comment_history( $c['comment_ID'], __('Akismet re-checked and caught this comment as spam'), 'check-spam' );
			
			} elseif ( 'false' == $response[1] ) {
				update_comment_meta( $c['comment_ID'], 'akismet_result', 'false' );
				delete_comment_meta( $c['comment_ID'], 'akismet_error' );
				Akismet::update_comment_history( $c['comment_ID'], __('Akismet re-checked and cleared this comment'), 'check-ham' );
			// abnormal result: error
			} else {
				update_comment_meta( $c['comment_ID'], 'akismet_result', 'error' );
				Akismet::update_comment_history( $c['comment_ID'], sprintf( __('Akismet was unable to re-check this comment (response: %s)'), substr($response[1], 0, 50)), 'check-error' );
			}
	
			delete_comment_meta( $c['comment_ID'], 'akismet_rechecking' );
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { 
	 		wp_send_json( array( 
	 			'processed' => count((array) $moderation), 
	 		)); 
	 	} 
	 	else { 
	 		$redirect_to = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : admin_url( 'edit-comments.php' ); 
	 		wp_safe_redirect( $redirect_to ); 
	 		exit; 
	 	}
	}
	
	// Adds an 'x' link next to author URLs, clicking will remove the author URL and show an undo link
	public function remove_comment_author_url() {
	    if ( !empty( $_POST['id'] ) && check_admin_referer( 'comment_author_url_nonce' ) ) {
	        $comment = get_comment( intval( $_POST['id'] ), ARRAY_A );
	        if ( $comment && current_user_can( 'edit_comment', $comment['comment_ID'] ) ) {
	            $comment['comment_author_url'] = '';
	            do_action( 'comment_remove_author_url' );
	            print( wp_update_comment( $comment ) );
	            die();
	        }
	    }
	}
		
	public function add_comment_author_url() {
	    if ( !empty( $_POST['id'] ) && !empty( $_POST['url'] ) && check_admin_referer( 'comment_author_url_nonce' ) ) {
	        $comment = get_comment( intval( $_POST['id'] ), ARRAY_A );
	        if ( $comment && current_user_can( 'edit_comment', $comment['comment_ID'] ) ) {
	            $comment['comment_author_url'] = esc_url( $_POST['url'] );
	            do_action( 'comment_add_author_url' );
	            print( wp_update_comment( $comment ) );
	            die();
	        }
	    }
	}
	
	public function comment_row_action( $a, $comment ) {
	
		// failsafe for old WP versions
		if ( !function_exists('add_comment_meta') )
			return $a;
	
		$akismet_result = get_comment_meta( $comment->comment_ID, 'akismet_result', true );
		$akismet_error  = get_comment_meta( $comment->comment_ID, 'akismet_error', true );
		$user_result    = get_comment_meta( $comment->comment_ID, 'akismet_user_result', true);
		$comment_status = wp_get_comment_status( $comment->comment_ID );
		$desc = null;
		if ( $akismet_error ) {
			$desc = __( 'Awaiting spam check' );
		} elseif ( !$user_result || $user_result == $akismet_result ) {
			// Show the original Akismet result if the user hasn't overridden it, or if their decision was the same
			if ( $akismet_result == 'true' && $comment_status != 'spam' && $comment_status != 'trash' )
				$desc = __( 'Flagged as spam by Akismet' );
			elseif ( $akismet_result == 'false' && $comment_status == 'spam' )
				$desc = __( 'Cleared by Akismet' );
		} else {
			$who = get_comment_meta( $comment->comment_ID, 'akismet_user', true );
			if ( $user_result == 'true' )
				$desc = sprintf( __('Flagged as spam by %s'), $who );
			else
				$desc = sprintf( __('Un-spammed by %s'), $who );
		}
	
		// add a History item to the hover links, just after Edit
		if ( $akismet_result ) {
			$b = array();
			foreach ( $a as $k => $item ) {
				$b[ $k ] = $item;
				if (
					$k == 'edit'
					|| ( $k == 'unspam' && $GLOBALS['wp_version'] >= 3.4 )
				) {
					$b['history'] = '<a href="comment.php?action=editcomment&amp;c='.$comment->comment_ID.'#akismet-status" title="'. esc_attr__( 'View comment history' ) . '"> '. __('History') . '</a>';
				}
			}
			
			$a = $b;
		}
			
		if ( $desc )
			echo '<span class="akismet-status" commentid="'.$comment->comment_ID.'"><a href="comment.php?action=editcomment&amp;c='.$comment->comment_ID.'#akismet-status" title="' . esc_attr__( 'View comment history' ) . '">'.esc_html( $desc ).'</a></span>';
			
		if ( apply_filters( 'akismet_show_user_comments_approved', get_option('akismet_show_user_comments_approved') ) == 'true' ) {
			$comment_count = Akismet::get_user_comments_approved( $comment->user_id, $comment->comment_author_email, $comment->comment_author, $comment->comment_author_url );
			$comment_count = intval( $comment_count );
			echo '<span class="akismet-user-comment-count" commentid="'.$comment->comment_ID.'" style="display:none;"><br><span class="akismet-user-comment-counts">'.sprintf( _n( '%s approved', '%s approved', $comment_count ), number_format_i18n( $comment_count ) ) . '</span></span>';
		}
		
		return $a;
	}
	
	public function comment_status_meta_box( $comment ) {
		$history = Akismet::get_comment_history( $comment->comment_ID );
	
		if ( $history ) {
			echo '<div class="akismet-history" style="margin: 13px;">';
			foreach ( $history as $row ) {
				$time = date( 'D d M Y @ h:i:m a', $row['time'] ) . ' GMT';
				echo '<div style="margin-bottom: 13px;"><span style="color: #999;" alt="' . $time . '" title="' . $time . '">' . sprintf( __('%s ago'), human_time_diff( $row['time'] ) ) . '</span> - ';
				echo esc_html( $row['message'] ) . '</div>';
			}			
			echo '</div>';	
		}
	}
	
	public function plugin_action_links( $links, $file ) {
		if ( $file == plugin_basename( AKISMET__PLUGIN_URL . '/akismet.php' ) ) {
			$links[] = '<a href="' . Akismet::get_configuration_page_url() . '">'.__( 'Settings' ).'</a>';
		}

		return $links;
	}
	
	public function text_add_link_callback( $m ) {	
		// bare link?
		if ( $m[4] == $m[2] )
			return '<a '.$m[1].' href="'.$m[2].'" '.$m[3].' class="comment-link">'.$m[4].'</a>';
		else
		    return '<span title="'.$m[2].'" class="comment-link"><a '.$m[1].' href="'.$m[2].'" '.$m[3].' class="comment-link">'.$m[4].'</a></span>';
	}
	
	public function text_add_link_class( $comment_text ) {
		return preg_replace_callback( '#<a ([^>]*)href="([^"]+)"([^>]*)>(.*?)</a>#i', array( $this, 'text_add_link_callback' ), $comment_text );
	}
	
	public static function submit_spam_comment( $comment_id ) {
		global $wpdb, $current_user, $current_site;
		
		$comment_id = (int) $comment_id;
	
		$comment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->comments} WHERE comment_ID = %d", $comment_id ) );
		
		if ( !$comment ) // it was deleted
			return;
			
		if ( 'spam' != $comment->comment_approved )
			return;
		
		// use the original version stored in comment_meta if available	
		$as_submitted = get_comment_meta( $comment_id, 'akismet_as_submitted', true);
		
		if ( $as_submitted && is_array( $as_submitted ) && isset( $as_submitted['comment_content'] ) )
			$comment = (object) array_merge( (array)$comment, $as_submitted );
		
		$comment->blog         = get_bloginfo('url');
		$comment->blog_lang    = get_locale();
		$comment->blog_charset = get_option('blog_charset');
		$comment->permalink    = get_permalink($comment->comment_post_ID);
		
		if ( is_object($current_user) )
		    $comment->reporter = $current_user->user_login;

		if ( is_object($current_site) )
			$comment->site_domain = $current_site->domain;
	
		$comment->user_role = '';
		if ( isset( $comment->user_ID ) )
			$comment->user_role = Akismet::get_user_roles( $comment->user_ID );
	
		if ( Akismet::is_test_mode() )
			$comment->is_test = 'true';
	
		$post = get_post( $comment->comment_post_ID );
		$comment->comment_post_modified_gmt = $post->post_modified_gmt;
		
		$response = Akismet::http_post( http_build_query( $comment ), 'submit-spam' );
		if ( $comment->reporter ) {
			Akismet::update_comment_history( $comment_id, sprintf( __('%s reported this comment as spam'), $comment->reporter ), 'report-spam' );
			update_comment_meta( $comment_id, 'akismet_user_result', 'true' );
			update_comment_meta( $comment_id, 'akismet_user', $comment->reporter );
		}
		
		do_action('akismet_submit_spam_comment', $comment_id, $response[1]);
	}
	
	public static function submit_nonspam_comment( $comment_id ) {
		global $wpdb, $current_user, $current_site;
		
		$comment_id = (int) $comment_id;
	
		$comment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->comments} WHERE comment_ID = %d", $comment_id ) );
		if ( !$comment ) // it was deleted
			return;
			
		// use the original version stored in comment_meta if available	
		$as_submitted = get_comment_meta( $comment_id, 'akismet_as_submitted', true);
		
		if ( $as_submitted && is_array($as_submitted) && isset($as_submitted['comment_content']) )
			$comment = (object) array_merge( (array)$comment, $as_submitted );
		
		$comment->blog         = get_bloginfo('url');
		$comment->blog_lang    = get_locale();
		$comment->blog_charset = get_option('blog_charset');
		$comment->permalink    = get_permalink( $comment->comment_post_ID );
		$comment->user_role    = '';
		
		if ( is_object($current_user) )
		    $comment->reporter = $current_user->user_login;
		
		if ( is_object($current_site) )
			$comment->site_domain = $current_site->domain;
	
		if ( isset( $comment->user_ID ) )
			$comment->user_role = Akismet::get_user_roles($comment->user_ID);
	
		if ( Akismet::is_test_mode() )
			$comment->is_test = 'true';
	
		$post = get_post( $comment->comment_post_ID );
		$comment->comment_post_modified_gmt = $post->post_modified_gmt;
	
		$response = Akismet::http_post( http_build_query( $comment ), 'submit-ham' );
		if ( $comment->reporter ) {
			Akismet::update_comment_history( $comment_id, sprintf( __('%s reported this comment as not spam'), $comment->reporter ), 'report-ham' );
			update_comment_meta( $comment_id, 'akismet_user_result', 'false' );
			update_comment_meta( $comment_id, 'akismet_user', $comment->reporter );
		}
		
		do_action('akismet_submit_nonspam_comment', $comment_id, $response[1]);
	}

	// Total spam in queue
	// get_option( 'akismet_spam_count' ) is the total caught ever
	public static function get_spam_count( $type = false ) {
		global $wpdb;
	
		if ( !$type ) { // total
			$count = wp_cache_get( 'akismet_spam_count', 'widget' );
			if ( false === $count ) {
				if ( function_exists('wp_count_comments') ) {
					$count = wp_count_comments();
					$count = $count->spam;
				} else {
					$count = (int) $wpdb->get_var("SELECT COUNT(comment_ID) FROM {$wpdb->comments} WHERE comment_approved = 'spam'");
				}
				wp_cache_set( 'akismet_spam_count', $count, 'widget', 3600 );
			}
			return $count;
		} elseif ( 'comments' == $type || 'comment' == $type ) { // comments
			$type = '';
		} else { // pingback, trackback, ...
			$type  = $wpdb->escape( $type );
		}
	
		return (int) $wpdb->get_var("SELECT COUNT(comment_ID) FROM {$wpdb->comments} WHERE comment_approved = 'spam' AND comment_type='$type'");
	}
	
	// Check connectivity between the WordPress blog and Akismet's servers.
	// Returns an associative array of server IP addresses, where the key is the IP address, and value is true (available) or false (unable to connect).
	public static function check_server_connectivity() {	
		$test_host = 'rest.akismet.com';
		
		// Some web hosts may disable one or both functions
		if ( !function_exists('fsockopen') || !function_exists('gethostbynamel') )
			return array();
		
		$ips = gethostbynamel( $test_host );
		if ( !$ips || !is_array($ips) || !count($ips) )
			return array();
			
		$api_key = Akismet::get_api_key();
			
		$servers = array();
		foreach ( $ips as $ip ) {
			$response = Akismet::verify_key( $api_key, $ip );
			// even if the key is invalid, at least we know we have connectivity
			if ( $response == 'valid' || $response == 'invalid' )
				$servers[$ip] = true;
			else
				$servers[$ip] = false;
		}	
		return $servers;
	}
	
	// Check the server connectivity and store the results in an option.
	// Cached results will be used if not older than the specified timeout in seconds; use $cache_timeout = 0 to force an update.
	// Returns the same associative array as check_server_connectivity()
	public static function get_server_connectivity( $cache_timeout = 86400 ) {
		$servers = get_option('akismet_available_servers');
		if ( (time() - get_option('akismet_connectivity_time') < $cache_timeout) && $servers !== false )
			return $servers;
		
		// There's a race condition here but the effect is harmless.
		$servers = Akismet_Admin::check_server_connectivity();
		update_option('akismet_available_servers', $servers);
		update_option('akismet_connectivity_time', time());
		return $servers;
	}

	// Returns true if server connectivity was OK at the last check, false if there was a problem that needs to be fixed.
	public static function is_server_connectivity_ok() {
		$servers = Akismet_Admin::get_server_connectivity();
		return !( empty($servers) || !count($servers) || count( array_filter($servers) ) < count($servers) );
	}
	
	public static function get_number_spam_waiting() {
		global $wpdb;			
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->commentmeta} WHERE meta_key = 'akismet_error'" );
	}
	
	public static function get_configuration_page_url() {
		return esc_url( add_query_arg( array( 'page' => 'akismet-key-config' ), class_exists( 'Jetpack' ) ? admin_url( 'admin.php' ) : admin_url( 'options-general.php' ) ) );
	}
	
	public static function get_stats_page_url() {
		return esc_url( add_query_arg( array( 'page' => 'akismet-key-config', 'view' => 'stats' ), class_exists( 'Jetpack' ) ? admin_url( 'admin.php' ) : admin_url( 'options-general.php' ) ) );
	}
	
	public static function get_delete_key_url() {
		return esc_url( add_query_arg( array( 'page' => 'akismet-key-config', 'view' => 'start', 'action' => 'delete-key', '_wpnonce' => wp_create_nonce( self::NONCE ) ), class_exists( 'Jetpack' ) ? admin_url( 'admin.php' ) : admin_url( 'options-general.php' ) ) );
	}
	
	public function display_alert() {
		Akismet::view( 'notice', array( 
			'type' => 'alert',
			'code' => (int) get_option( 'akismet_alert_code' ),
			'msg'  => get_option( 'akismet_aledrt_msg' ) 
		) );
	}	
	
	public function display_spam_check_warning() {		
		Akismet::fix_scheduled_recheck();
		
		if ( Akismet_Admin::get_number_spam_waiting() > 0 && wp_next_scheduled('akismet_schedule_cron_recheck') > time() )
			Akismet::view( 'notice', array( 'type' => 'spam-check' ) );	
	}
	
	public function display_invalid_version() {
        Akismet::view( 'notice', array( 'type' => 'version' ) ); 
	}
	
	public function display_api_key_warning() {
		Akismet::view( 'notice', array( 'type' => 'plugin' ) ); 
	}

	public function display_page() {				
		if ( !Akismet::get_api_key() )
			$this->display_start_page();
		elseif ( isset( $_GET['view'] ) && $_GET['view'] == 'start' )			
			$this->display_start_page();
		elseif ( isset( $_GET['view'] ) && $_GET['view'] == 'stats' )			
			$this->display_stats_page();
		elseif ( isset( $_GET['page'] ) && $_GET['page'] == 'akismet-stats-display' )			
			$this->display_stats_page();
		else
			$this->display_configuration_page();
	}
	
	public function display_start_page() {
		if ( isset( $_GET['action'] ) ) {
			if ( $_GET['action'] == 'delete-key' ) {
				if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], self::NONCE ) )
					delete_option( 'wordpress_api_key' );
			}
		}
		
		if ( $api_key = Akismet::get_api_key() ) {
			$this->display_configuration_page();
			return;		
		}
		
		$akismet_user = false;				
		
		if ( class_exists( 'Jetpack' ) ) {				
			if ( $jetpack_user = Akismet_Admin::get_jetpack_user() ) {
												
				$akismet_user = Akismet::http_post( http_build_query( array( 
					'user_id'          => $jetpack_user['user_id'],
					'api_key'          => $jetpack_user['api_key'], 
					'get_account_type' => 'true' 
				) ), 'verify-wpcom-key' );
				
				if ( count( $akismet_user ) > 1 )
					$akismet_user = json_decode( $akismet_user[1] );
				
				Akismet::log( compact( 'akismet_user' ) );
			}
			
			if ( isset( $_GET['action'] ) ) {
				if ( $_GET['action'] == 'save-key' ) {
					//auto save jetpack user if the correct wp user id is passed back from akismet done page
					if ( isset( $_GET['id'] ) && (int) $_GET['id'] == $akismet_user->ID ) {
						$this->save_key( $akismet_user->api_key );	
						$this->display_notice();						
						$this->display_configuration_page();
						return;					
					}
				}
			}
		}	
		
		echo '<h2 class="ak-header">'.__('Akismet').'</h2>';
		
		$this->display_status();
				
		Akismet::view( 'start', compact( 'akismet_user' ) );
	}

	public function display_stats_page() {
		Akismet::view( 'stats' );
	}
	
	public function display_configuration_page() {
		$api_key      = Akismet::get_api_key();
		$akismet_user = Akismet::http_post( http_build_query( array( 'key' => $api_key ) ), 'get-subscription' );		
		
		if ( count( $akismet_user ) > 1 )
			$akismet_user = json_decode( $akismet_user[1] );
		else
			$akismet_user = false;
			
		$blog = parse_url( get_option('home'), PHP_URL_HOST );
			
		foreach( array( '6-months', 'all' ) as $interval ) {
			$response = Akismet::http_post( http_build_query( array( 'blog' => urlencode( $blog ), 'key' => $api_key, 'from' => $interval ) ), 'get-stats' );
			
			if ( count( $response ) > 1 )
				$stat_totals[$interval] = json_decode( $response[1] );
		}
		
		if ( empty( $this->notices ) ) {
			//show status
			if ( $akismet_user->status == 'active' && $akismet_user->account_type == 'free-api-key' ) {
				
				$time_saved = false;
				
				if ( $stat_totals['all']->time_saved > 1800 ) {
					$total_in_minutes = round( $stat_totals['all']->time_saved / 60 );
					$total_in_hours   = round( $total_in_minutes / 60 );
					$total_in_days    = round( $total_in_hours / 8 );					
					$cleaning_up      = __( 'Cleaning up spam takes time.' );
					
					if ( $total_in_days > 1 )
						$time_saved = $cleaning_up . ' ' . sprintf( __( 'Since you joined us, Akismet has saved you %s days!' ), number_format_i18n( $total_in_days ) );
					elseif ( $total_in_hours > 1 )
						$time_saved = $cleaning_up . ' ' . sprintf( __( 'Since you joined us, Akismet has saved you %d hours!' ), $total_in_hours );
					elseif ( $total_in_minutes >= 30 )
						$time_saved = $cleaning_up . ' ' . sprintf( __( 'Since you joined us, Akismet has saved you %d minutes!' ), $total_in_minutes );
				}		

				Akismet::view( 'notice', array( 'type' => 'active-notice', 'time_saved' => $time_saved ) );
			}
			elseif ( in_array( $akismet_user->status, array( 'cancelled', 'suspended' ) ) )
				Akismet::view( 'notice', array( 'type' => $akismet_user->status ) );			
		}			
		
		Akismet::log( compact( 'stat_totals', 'akismet_user' ) );			
		Akismet::view( 'config', compact( 'api_key', 'blog', 'akismet_user', 'stat_totals' ) );
	}
	
	public function display_notice() {
		global $hook_suffix;
	
		if ( in_array( $hook_suffix, array( 'jetpack_page_akismet-key-config', 'settings_page_akismet-key-config', 'edit-comments.php' ) ) && (int) get_option( 'akismet_alert_code' ) > 0 ) { 	
			$this->display_alert();
		}
		elseif ( $hook_suffix == 'plugins.php' && !Akismet::get_api_key() ) {
			$this->display_api_key_warning();
		}
		elseif ( $hook_suffix == 'edit-comments.php' && wp_next_scheduled( 'akismet_schedule_cron_recheck' ) ) {
			$this->display_spam_check_warning();
		}
		elseif ( in_array( $hook_suffix, array( 'jetpack_page_akismet-key-config', 'settings_page_akismet-key-config' ) ) && Akismet::get_api_key() ) {
			$this->display_status();
		}
	}
	
	public function display_status() {
		$servers    = Akismet_Admin::get_server_connectivity();
		$fail_count = count( $servers ) - count( array_filter( $servers ) );
		$type       = '';
		
		if ( empty( $servers ) || $fail_count > 0 )
			$type = 'servers-be-down';
			
		if ( !function_exists('fsockopen') || !function_exists('gethostbynamel') )
			$type = 'missing-functions';
			
		if ( !empty( $type ) )
			Akismet::view( 'notice', compact( 'type' ) );
		elseif ( !empty( $this->notices ) ) {
			foreach ( $this->notices as $type )
				Akismet::view( 'notice', compact( 'type' ) );
		}
	}

	private function get_jetpack_user() {
		if ( !class_exists('Jetpack') )
			return false;
			
		Jetpack::load_xml_rpc_client();
		$xml = new Jetpack_IXR_ClientMulticall( array( 'user_id' => get_current_user_id() ) );
	
		$xml->addCall( 'wpcom.getUserID' );
		$xml->addCall( 'akismet.getAPIKey' );	
		$xml->query();
		
		Akismet::log( compact( 'xml' ) );
		
		if ( !$xml->isError() ) {
			$responses = $xml->getResponse();			
			if ( count( $responses ) > 1 ) {
				$api_key = array_shift( $responses[0] );
				$user_id = (int) array_shift( $responses[1] );
				return compact( 'api_key', 'user_id' );
			} 
		}		
		return false;
	}
}
?>