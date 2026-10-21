<?php
/**
 * Kommentit.
 *
 * @package sami-elmeranta
 */

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="entry-content prose prose-lg max-w-none">
	<?php if ( have_comments() ) : ?>
		<h2><?php echo esc_html( sprintf( 'Kommentit (%d)', get_comments_number() ) ); ?></h2>
		<ol class="comment-list">
			<?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 0 ) ); ?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php
	comment_form( array(
		'title_reply'   => 'Jätä kommentti',
		'label_submit'  => 'Lähetä kommentti',
		'class_submit'  => 'btn-primary',
		'comment_notes_before' => '<p class="text-sm text-muted">Sähköpostiosoitettasi ei julkaista.</p>',
	) );
	?>
</section>
