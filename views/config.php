<div class="wrap">

	<h2><?php esc_html_e( 'Akismet' , 'akismet');?></h2>

	<div class="have-key">

		<?php if ( $stat_totals && isset( $stat_totals['all'] ) && (int) $stat_totals['all']->spam > 0 ) : ?>

			<div class="new-snapshot stats">

				<span style="float:right;margin:10px 15px -5px 0px">
					<a href="<?php echo esc_url( Akismet_Admin::get_page_url( 'stats' ) ); ?>" class=""><?php esc_html_e( 'Summaries' , 'akismet');?></a>
				</span>

				<iframe allowtransparency="true" scrolling="no" frameborder="0" style="width: 100%; height: 215px; overflow: hidden;" src="<?php printf( 'http://akismet.com/web/1.0/snapshot.php?blog=%s&api_key=%s&height=180', $blog, $api_key );?>"></iframe>
				<ul>
					<li>
						<h3><?php esc_html_e( 'Past six months' , 'akismet');?></h3>
						<span><?php echo number_format( $stat_totals['6-months']->spam );?></span>
						<?php esc_html_e( 'Spam blocked' , 'akismet');?>
					</li>
					<li>
						<h3><?php esc_html_e( 'All time' , 'akismet');?></h3>
						<span><?php echo number_format( $stat_totals['all']->spam );?></span>
						<?php esc_html_e( 'Spam blocked' , 'akismet');?>
					</li>
					<li>
						<h3><?php esc_html_e( 'Accuracy' , 'akismet');?></h3>
						<span><?php echo $stat_totals['all']->accuracy; ?>%</span>
						<?php printf(
							esc_html(
								_n( '%s missed spam, %s false positive', '%s missed spam, %s false positives', $stat_totals['all']->false_positives , 'akismet')
							),
							number_format( $stat_totals['all']->missed_spam ),
							number_format( $stat_totals['all']->false_positives )
						); ?>
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
							<h3 class="hndle"><span><?php esc_html_e( 'Settings' , 'akismet');?></span></h3>
							<form name="akismet_conf" id="akismet-conf" action="<?php echo esc_url( Akismet_Admin::get_page_url() ); ?>" method="POST">
								<div class="inside">
									<table cellspacing="0" class="akismet-settings">
										<tbody>
											<?php if ( !defined( 'WPCOM_API_KEY' ) ):?>
											<tr>
												<th scope="row" align="left" width="10%"><?php esc_html_e('API Key', 'akismet');?></th>
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
														<label for="akismet_show_user_comments_approved" title="<?php esc_attr_e( 'Show approved comments' , 'akismet'); ?>"><input name="akismet_show_user_comments_approved" id="akismet_show_user_comments_approved" value="true" type="checkbox" <?php echo get_option('akismet_show_user_comments_approved') == 'true' ? 'checked="checked"':''; ?>> <?php esc_html_e('Show the number of approved comments beside each comment author', 'akismet'); ?></label>
													</p>
													<p>
														<label for="akismet_discard_month" title="<?php esc_attr_e( 'Auto-detete spam from old posts' , 'akismet'); ?>"><input name="akismet_discard_month" id="akismet_discard_month" value="true" type="checkbox" <?php echo get_option('akismet_discard_month') == 'true' ? 'checked="checked"':''; ?>> <?php esc_html_e('Automatically delete spam from posts older than 30 days', 'akismet'); ?></label><span class="note"><strong><?php esc_html_e('Note:', 'akismet');?></strong> <?php printf( __( 'Spam in the <a href="%s">spam folder</a> older than 15 days is deleted automatically.' , 'akismet'), admin_url( 'edit-comments.php?type=spam' ) );?></span><div class="clear"></div>
													</p>
												</td>
											</tr>
										</tbody>
									</table>
								</div>
								<div id="major-publishing-actions">
									<?php if ( !defined( 'WPCOM_API_KEY' ) ):?>
									<div id="delete-action">
										<a class="submitdelete deletion" href="<?php echo esc_url( Akismet_Admin::get_page_url( 'delete_key' ) ); ?>"><?php esc_html_e('Disconnect this account', 'akismet'); ?></a>
									</div>
									<?php endif; ?>
									<?php wp_nonce_field(Akismet_Admin::NONCE) ?>
									<div id="publishing-action">
											<input type="hidden" name="action" value="enter-key">
											<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'akismet');?>">

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
							<h3 class="hndle"><span><?php esc_html_e( 'Account' , 'akismet');?></span></h3>
							<div class="inside">
								<table cellspacing="0">
									<tbody>
										<tr>
											<th scope="row" align="left"><?php esc_html_e( 'Subscription Type' , 'akismet');?></th>
											<td width="5%"/>
											<td align="left">
												<span><?php echo $akismet_user->account_name; ?></span>
											</td>
										</tr>
										<tr>
											<th scope="row" align="left"><?php esc_html_e( 'Status' , 'akismet');?></th>
											<td width="5%"/>
											<td align="left">
												<span><?php echo ucwords( $akismet_user->status ); ?></span>
											</td>
										</tr>
										<?php if ( $akismet_user->next_billing_date ) : ?>
										<tr>
											<th scope="row" align="left"><?php esc_html_e( 'Next Billing Date' , 'akismet');?></th>
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
									<?php Akismet::view( 'get', array( 'text' => ( $akismet_user->account_type == 'free-api-key' ? __( 'Upgrade' , 'akismet') : __( 'Change' , 'akismet') ), 'redirect' => 'upgrade' ) ); ?>
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