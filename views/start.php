<div class="no-key config-wrap"><?php
	if ( $akismet_user ) :
		if ( $akismet_user->status == 'active' ) : // ask do they want to use akismet account found using jetpack wpcom connection ?>
<p><?php esc_html_e('Akismet eliminates the comment and trackback spam you get on your site. To setup Akismet, select one of the options below.', 'akismet'); ?></p>
<div class="activate-highlight activate-option">
	<div class="option-description">
		<strong class="small-heading"><?php esc_html_e('Connected via Jetpack', 'akismet'); ?></strong>
		<?php echo esc_attr( $akismet_user->user_email ); ?>
	</div>
	<form name="akismet_use_wpcom_key" action="<?php echo esc_url( Akismet_Admin::get_page_url() ); ?>" method="post" id="akismet-activate" class="right">
		<input type="hidden" name="key" value="<?php echo esc_attr( $akismet_user->api_key );?>"/>
		<input type="hidden" name="action" value="enter-key">
		<?php wp_nonce_field( Akismet_Admin::NONCE ) ?>
		<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Use this Akismet account' , 'akismet'); ?>"/>
	</form>
</div>
<div class="activate-highlight secondary activate-option">
	<div class="option-description">
		<strong><?php esc_html_e('Create a new API key with a different email address', 'akismet'); ?></strong>
		<p><?php esc_html_e('Use this option if you want to setup a new Akismet account.', 'akismet'); ?></p>
	</div>
	<?php Akismet::view( 'get', array( 'text' => __( 'Register a different email address' , 'akismet'), 'classes' => array( 'right', 'button', 'button-secondary' ) ) ); ?>
</div>
<div class="activate-highlight secondary activate-option">
	<div class="option-description">
		<strong><?php esc_html_e('Manually enter an API key', 'akismet'); ?></strong>
		<p><?php esc_html_e('If you have another API key you want to use.', 'akismet'); ?></p>
	</div>
	<form action="<?php echo esc_url( Akismet_Admin::get_page_url() ); ?>" method="post" id="akismet-enter-api-key" class="right">
		<input id="key" name="key" type="text" size="15" maxlength="12" value="" class="regular-text code">
		<input type="hidden" name="action" value="enter-key">
		<?php wp_nonce_field( Akismet_Admin::NONCE ) ?>
		<input type="submit" name="submit" id="submit" class="button button-secondary" value="<?php esc_attr_e('Use this key', 'akismet');?>">
	</form>
</div>
<?php elseif ( $akismet_user->status == 'no-account' ) : //no akismet account, ask do they want to use jetpack wpcom account to create one, then redirect to plans page?>
<p><?php esc_html_e('Akismet eliminates the comment and trackback spam you get on your site. Register your email address below to get started.', 'akismet'); ?></p>
<div class="activate-highlight activate-option">
	<div class="option-description">
		<strong class="small-heading"><?php esc_html_e('Connected via Jetpack', 'akismet'); ?></strong>
		<?php echo esc_attr( $akismet_user->user_email ); ?>
	</div>
	<form name="akismet_activate" id="akismet_activate" action="https://akismet.com/get/" method="post" class="right">
		<input type="hidden" name="passback_url" value="<?php echo esc_attr( Akismet_Admin::get_page_url() ); ?>"/>
		<input type="hidden" name="auto-connect" value="<?php echo $akismet_user->ID;?>"/>
		<input type="hidden" name="redirect" value="plugin-signup"/>
		<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Register Akismet' , 'akismet'); ?>"/>
	</form>
</div>
<div class="activate-highlight secondary activate-option">
	<div class="option-description">
		<strong><?php esc_html_e('Create a new API key with a different email address', 'akismet'); ?></strong>
		<p><?php esc_html_e('Use this option if you want to setup a new Akismet account.', 'akismet'); ?></p>
	</div>
	<?php Akismet::view( 'get', array( 'text' => __( 'Register a different email address' , 'akismet'), 'classes' => array( 'right', 'button', 'button-secondary' ) ) ); ?>
</div>
<div class="activate-highlight secondary activate-option">
	<div class="option-description">
		<strong><?php esc_html_e('Manually enter an API key', 'akismet'); ?></strong>
		<p><?php esc_html_e('If you have another API key you want to use.', 'akismet'); ?></p>
	</div>
	<form action="<?php echo esc_url( Akismet_Admin::get_page_url() ); ?>" method="post" id="akismet-enter-api-key" class="right">
		<input id="key" name="key" type="text" size="15" maxlength="12" value="" class="regular-text code">
		<input type="hidden" name="action" value="enter-key">
		<?php wp_nonce_field( Akismet_Admin::NONCE ) ?>
		<input type="submit" name="submit" id="submit" class="button button-secondary" value="<?php esc_attr_e('Use this key', 'akismet');?>">
	</form>
</div>
<?php elseif ( $akismet_user->status == 'suspended' ) : //has an akismet account but the key is suspended, need to upgrade their account to unsuspend key ?>
<p><?php esc_html_e('Akismet eliminates the comment and trackback spam you get on your site.', 'akismet'); ?></p>
<div class="activate-highlight centered activate-option">
	<strong class="small-heading"><?php esc_html_e( 'Connected via Jetpack' , 'akismet'); ?></strong>
	<h3 class="alert-text"><?php printf( esc_html__( 'Your subscription for %s is suspended' , 'akismet'), $akismet_user->user_email ); ?></h3>
	<p><?php esc_html_e('No worries! Get in touch and we&#8217;ll help sort this out.', 'akismet'); ?></p>
	<a href="https://akismet.com/contact" class="button button-primary"><?php esc_html_e( 'Contact Akismet support' , 'akismet'); ?></a>

</div><?php
		endif;
	else :?>
<p><?php esc_html_e('Akismet eliminates the comment and trackback spam you get on your site. To setup Akismet, select one of the options below.', 'akismet'); ?></p>
<div class="activate-highlight activate-option">
	<div class="option-description">
		<strong><?php esc_html_e( 'New to Akismet?' , 'akismet');?></strong>
		<p><?php esc_html_e('Get started now to squash the comment and trackback spam you get.', 'akismet'); ?></p>
	</div>
	<?php Akismet::view( 'get', array( 'text' => __( 'Get an Akismet account' , 'akismet'), 'classes' => array( 'right', 'button', 'button-primary' ) ) ); ?>
</div>
<div class="activate-highlight secondary activate-option">
	<div class="option-description">
		<strong><?php esc_html_e('Manually enter an API key', 'akismet'); ?></strong>
		<p><?php esc_html_e('If you have another API key you want to use.', 'akismet'); ?></p>
	</div>
	<form action="<?php echo esc_url( Akismet_Admin::get_page_url() ); ?>" method="post" id="akismet-enter-api-key" class="right">
		<input id="key" name="key" type="text" size="15" maxlength="12" value="" class="regular-text code">
		<input type="hidden" name="action" value="enter-key">
		<?php wp_nonce_field( Akismet_Admin::NONCE ); ?>
		<input type="submit" name="submit" id="submit" class="button button-secondary" value="<?php esc_attr_e('Use this key', 'akismet');?>">
	</form>
</div><?php
	endif;?>
</div>