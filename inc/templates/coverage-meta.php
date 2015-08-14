<div class="wpalchemy-meta">
	<div class="wpalchemy-field">
		<?php $mb->the_field('link'); ?>
		<label for="<?php $mb->the_name(); ?>">Link:</label>
		<input type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>">
		<p>The Link URL of the news source.</p>
	</div>
	<div class="wpalchemy-field">
		<?php $mb->the_field('source'); ?>
		<label for="<?php $mb->the_name(); ?>">Source:</label>
		<input type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>">
		<p>Optional name of the news source (e.g. Mashable, PR Newswire, CNN).</p>
	</div>
</div>
