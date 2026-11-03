<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** Regression coverage for template inclusion. */
class Test_Template_Inclusion extends Noptin_Regression_Test_Case {

	public function test_template_attributes_cannot_override_include_paths() {
		$assert = array( $this, 'assertTrue' );
		$directory = sys_get_temp_dir() . '/noptin-template-' . wp_generate_password( 12, false );
		mkdir( $directory );
		$safe   = $directory . '/safe.php';
		$target = $directory . '/target.php';
		$text   = $directory . '/marker.txt';
		file_put_contents( $safe, '<?php echo "SAFE_TEMPLATE:" . $label;' );
		file_put_contents( $target, '<?php $GLOBALS["noptin_include_executed"] = true; echo "UNTRUSTED_INCLUDE_MARKER";' );
		file_put_contents( $text, 'UNTRUSTED_FILE_MARKER' );
		$GLOBALS['noptin_include_executed'] = false;
		$tags = noptin()->emails->tags;
		$original_tag = $tags->tags['subscriber.first_name'] ?? null;
		$post_id = 0;
		$buffer_level = ob_get_level();

		try {
			// Normal template variables and trusted template selection must still work.
			ob_start();
			get_noptin_template( 'safe.php', array( 'label' => 'kept', 'the_template_path' => $target ), 'noptin', $directory );
			$assert( 'SAFE_TEMPLATE:kept' === ob_get_clean(), 'Attributes must not overwrite the resolved template path.' );
			$assert( 'SAFE_TEMPLATE:kept' === noptin_ob_get_clean( $safe, array( 'label' => 'kept', 'file' => $target ) ), 'Buffered includes must protect their file argument.' );
			$assert( false === $GLOBALS['noptin_include_executed'], 'An injected PHP path must never execute.' );

			// Use a persisted post so WordPress can prime its metadata and term caches.
			$post_id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Safe digest fixture', 'post_content' => 'Fixture excerpt.' ) );
			$this->assertGreaterThan( 0, $post_id );
			$post = get_post( $post_id );
			$query = static function () use ( $post ) { return array( $post ); };
			add_filter( 'posts_pre_query', $query );
			foreach ( array( $text, $target, 'php://filter/convert.base64-encode/resource=' . $text ) as $path ) {
				$payload = '[[posts the_template_path="' . str_replace( '\\', '/', $path ) . '" title="Allowed title" description="Allowed description"]]';
				$direct  = $tags->replace_in_body( 'START ' . $payload . ' END' );
				$assert( ! str_contains( $direct, 'UNTRUSTED_' ) && ! str_contains( $direct, base64_encode( 'UNTRUSTED_FILE_MARKER' ) ), 'Posts tags must not read injected paths or stream wrappers.' );
				$assert( str_contains( $direct, 'Allowed title' ) && str_contains( $direct, 'Allowed description' ), 'Supported digest attributes must remain available.' );

				// This is the value returned by the subscriber first-name field, not authored email markup.
				$tags->add_tag( 'subscriber.first_name', array( 'replacement' => $payload ) );
				$nested = noptin_parse_email_content_tags( '<div>START [[subscriber.first_name]] END</div>' );
				$assert( ! str_contains( $nested, 'UNTRUSTED_' ) && ! str_contains( $nested, base64_encode( 'UNTRUSTED_FILE_MARKER' ) ), 'Nested subscriber values must not cause file inclusion.' );
				$assert( str_contains( $nested, 'Allowed title' ), 'The nested replacement path must have actually executed the posts tag.' );
			}
			$assert( false === $GLOBALS['noptin_include_executed'], 'Nested injected PHP paths must never execute.' );
			$grid = Hizzle\Noptin\Emails\Generate\Merge_Tags::get_posts_html( array( 'style' => 'grid', 'title' => 'Grid title' ), array( $post ) );
			$assert( str_contains( $grid, 'Grid title' ) && str_contains( $grid, 'digest-grid' ), 'Grid digest rendering must remain functional.' );

		} finally {
			remove_filter( 'posts_pre_query', $query ?? '__return_null' );
			if ( $post_id ) {
				wp_delete_post( $post_id, true );
			}
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}
			if ( null === $original_tag ) {
				$tags->remove_tag( 'subscriber.first_name' );
			} else {
				$tags->add_tag( 'subscriber.first_name', $original_tag );
			}
			foreach ( array( $safe, $target, $text ) as $file ) {
				unlink( $file );
			}
			rmdir( $directory );
		}
	}
}
