<style>
.wpalchemy-meta input[type="text"] {
	width:100%;
}
.wpalchemy-field label {
	font-weight:bold;
}
.wpalchemy-field label span {
	color:#999;
	font-weight:normal;
}
.wpalchemy-field p {
	margin-top:0;
	color:#999;
	font-size:0.9em;
}
</style>

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
