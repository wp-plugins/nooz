<style>
#wpalchemy-content-apt {
	margin-top:20px;
}
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
		<?php $mb->the_field('subheadline'); ?>
		<label for="<?php $mb->the_name(); ?>">Subheadline</label>
		<input type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>">
		<p style="margin:0;">An optional subheadline for this press release.</p>
	</div>
</div>
