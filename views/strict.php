<tr valign="top">
	<th scope="row"><?php _e('Akismet anti-spam strictness'); ?></th>
	<td><fieldset><legend class="screen-reader-text"><span><?php _e('Akismet anti-spam strictness'); ?></span></legend>
	<p><label for="akismet_strictness_1"><input type="radio" name="akismet_strictness" id="akismet_strictness_1" value="1" <?php checked('1', get_option('akismet_strictness')); ?> /> <?php _e('Strict: silently discard the worst and most pervasive spam.'); ?></label></p>
	<p><label for="akismet_strictness_0"><input type="radio" name="akismet_strictness" id="akismet_strictness_0" value="0" <?php checked('0', get_option('akismet_strictness')); ?> /> <?php _e('Safe: always put spam in the Spam folder for review.'); ?></label></p>
	</fieldset></td>
</tr>