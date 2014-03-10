<div class="wrap">
	
	<h2><?php _e( 'Akismet' );?></h2>
	
	<div class="have-key">	
			
		<?php if ( $stat_totals && isset( $stat_totals['all'] ) && (int) $stat_totals['all']->spam > 0 ) : ?>		
				
			<div class="new-snapshot stats">
			
				<span style="float:right;margin:10px 15px -5px 0px">
					<a href="<?php echo Akismet_Admin::get_stats_page_url();?>" class=""><?php _e( 'Summaries' );?></a>
				</span>				
							
				<iframe allowtransparency="true" scrolling="no" frameborder="0" style="width: 100%; height: 215px; overflow: hidden;" src="<?php printf( 'http://akismet.com/web/1.0/snapshot.php?blog=%s&api_key=%s&height=180', $blog, $api_key );?>"></iframe>
				<ul>
					<li>
						<h3><?php _e( 'Past six months' );?></h3>
						<span><?php echo number_format( $stat_totals['6-months']->spam );?></span>
						<?php _e( 'Spam blocked' );?>
					</li>					
					<li>
						<h3><?php _e( 'All time' );?></h3>
						<span><?php echo number_format( $stat_totals['all']->spam );?></span>
						<?php _e( 'Spam blocked' );?>
					</li>
					<li>
						<h3><?php _e( 'Accuracy' );?></h3>
						<span><?php echo $stat_totals['all']->accuracy; ?>%</span>
						<?php printf( _n( '%s missed spam, %s false positive', '%s missed spam, %s false positives', $stat_totals['all']->false_positives ), number_format( $stat_totals['all']->missed_spam ), number_format( $stat_totals['all']->false_positives ) );?>
					</li>
				</ul>
				<div class="clearfix"></div>
			</div>
		<?php endif;?>
		
		<?php if ( $akismet_user ):?>
			
			<div id="wpcom-stats-meta-box-container" class="metabox-holder"><?php
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
				?>
				<script type="text/javascript">
				jQuery(document).ready( function($) {
					jQuery('.if-js-closed').removeClass('if-js-closed').addClass('closed');
					if(typeof postboxes !== 'undefined')
						postboxes.add_postbox_toggles( 'plugins_page_akismet-key-config' );
				});
				</script>
				<div class="postbox-container" style="width:59%;">
					<div id="normal-sortables" class="meta-box-sortables ui-sortable">
						<div id="referrers" class="postbox ">
							<div class="handlediv" title="Click to toggle"><br></div>
							<h3 class="hndle"><span><?php _e( 'Settings' );?></span></h3>
							<form name="akismet_conf" id="akismet-conf" action="<?php echo Akismet_Admin::get_configuration_page_url();?>" method="POST"> 
								<div class="inside">
									<table cellspacing="0">
										<tbody>	
											<?php if ( !defined( 'WPCOM_API_KEY' ) ):?>	
											<tr>
												<th scope="row" align="left" width="10%"><?php _e('API Key');?></th>
												<td width="5%"/>
												<td align="left">
													<span><input id="key" name="key" type="text" size="15" maxlength="12" value="<?php echo esc_attr( get_option('wordpress_api_key') ); ?>" class="regular-text code <?php echo $akismet_user->status;?>"></span>
												</td>
											</tr>
											<?php endif; ?>	
											<tr>
												<th width="10%"></th>
												<td></td>
												<td>
													<p>
														<label for="akismet_discard_month" title="<?php esc_attr_e( 'Auto-detete old spam' ); ?>"><input name="akismet_discard_month" id="akismet_discard_month" value="true" type="checkbox" <?php echo get_option('akismet_discard_month') == 'true' ? 'checked="checked"':''; ?>> <?php _e('Delete spam on posts more than a month old'); ?></label>
													</p>
													<p>
														<label for="akismet_show_user_comments_approved" title="<?php esc_attr_e( 'Show approved comments' ); ?>"><input name="akismet_show_user_comments_approved" id="akismet_show_user_comments_approved" value="true" type="checkbox" <?php echo get_option('akismet_show_user_comments_approved') == 'true' ? 'checked="checked"':''; ?>> <?php _e('Show the number of approved comments beside each comment author'); ?></label>
													</p>
												</td>
											</tr>
										</tbody>
									</table>
								</div>
								<div id="major-publishing-actions">
									<?php if ( !defined( 'WPCOM_API_KEY' ) ):?>	
									<div id="delete-action">
										<a class="submitdelete deletion" href="<?php echo Akismet_Admin::get_delete_key_url();?>"><?php _e('Disconnect this account'); ?></a>
									</div>
									<?php endif; ?>
									<?php wp_nonce_field(Akismet_Admin::NONCE) ?>
									<div id="publishing-action">
											<input type="hidden" name="action" value="enter-key">
											<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Changes');?>">
										
									</div>
									<div class="clear"></div>
								</div>
							</form>
						</div>
					</div>
				</div>
				<div class="postbox-container" style="width:39%;float: right;">
					<div id="normal-sortables" class="meta-box-sortables ui-sortable">
						<div id="referrers" class="postbox ">
							<div class="handlediv" title="Click to toggle"><br></div>
							<h3 class="hndle"><span><?php _e( 'Account' );?></span></h3>
							<div class="inside">
								<table cellspacing="0">
									<tbody>		
										<tr>
											<th scope="row" align="left"><?php _e( 'Subscription Type' );?></th>
											<td width="5%"/>
											<td align="left">
												<span><?php echo $akismet_user->account_name; ?></span>
											</td>
										</tr>	
										<tr>
											<th scope="row" align="left"><?php _e( 'Status' );?></th>
											<td width="5%"/>
											<td align="left">
												<span><?php echo ucwords( $akismet_user->status ); ?></span>
											</td>
										</tr>
										<?php if ( $akismet_user->next_billing_date ) : ?>
										<tr>
											<th scope="row" align="left"><?php _e( 'Next Billing Date' );?></th>
											<td width="5%"/>
											<td align="left">
												<span><?php echo date( 'F j, Y', $akismet_user->next_billing_date ); ?></span>
											</td>
										</tr>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
							<div id="major-publishing-actions">
								<div id="publishing-action">
									<?php Akismet::view( 'get', array( 'text' => ( $akismet_user->account_type == 'free-api-key' ? __( 'Upgrade' ) : __( 'Change' ) ), 'redirect' => 'upgrade' ) ); ?>
								</div>
								<div class="clear"></div>
							</div>
						</div>
					</div>
				</div>			
			</div>
							
		<?php endif;?>

	</div>
</div>