<?php

/**
 * Logs media uploads
 */
class SimpleMediaLogger extends SimpleLogger
{

	public $slug = "SimpleMediaLogger";

	public function __construct() {
		
		add_action("admin_init", array($this, "on_admin_init"));

	}

	function on_admin_init() {

		add_action("add_attachment", array($this, "on_add_attachment"));
		add_action("edit_attachment", array($this, "on_edit_attachment"));
		add_action("delete_attachment", array($this, "on_delete_attachment"));

		add_action("admin_head", array($this, "output_styles"));
		
	}

	function output_styles() {
		
		?>
		<style>
			.simple-history-logitem--logger-SimpleMediaLogger--attachment-icon,
			.simple-history-logitem--logger-SimpleMediaLogger--attachment-thumb {
				display: inline-block;
				margin: .5em 0 0 0;
			}

			.simple-history-logitem--logger-SimpleMediaLogger--attachment-icon {
				max-width: 40px;
				max-height: 32px;
			}

			.simple-history-logitem--logger-SimpleMediaLogger--attachment-thumb {
				padding: 5px;
				border: 1px solid #ddd;
				-webkit-border-radius: 2px;
				-moz-border-radius: 2px;
				border-radius: 2px;
			}

			.simple-history-logitem--logger-SimpleMediaLogger--attachment-thumb img {
				/*
				photoshop-like background that represents tranpsarency
				so user can see that an image have transparency
				*/
				display: block;
				background-image: url('data:image/gif;base64,R0lGODlhEAAQAIAAAP///8zMzCH5BAAAAAAALAAAAAAQABAAAAIfjG+gq4jM3IFLJgpswNly/XkcBpIiVaInlLJr9FZWAQA7');
				max-width: 100%;
				max-height: 300px;
				height: auto;
			}

			.simple-history-logitem--logger-SimpleMediaLogger--attachment-meta-size,
			.simple-history-logitem--logger-SimpleMediaLogger--attachment-open {
				margin: .5em 0 0 0;
			}
		</style>
		<?php

	}

	/**
	 * Get output
	 */
	function getLogRowDetailsOutput($row) {

		/*
		[post_type] => attachment
		[attachment_title] => placebeppe
		[attachment_filename] => placebeppe.jpg
		[attachment_mime] => image/jpeg
		[attachment_filesize] => 85946
		*/

		$context = $row->context;

		$attachment_id = $context["attachment_id"];
		$is_image = wp_attachment_is_image( $attachment_id );
		$filetype = wp_check_filetype( $context["attachment_filename"] );
		$file_url = wp_get_attachment_url( $attachment_id );
		$message = "";

		$is_video = strpos($filetype["type"], "video/") !== false;
		$is_audio = strpos($filetype["type"], "audio/") !== false;

		if ( $is_image ) {

			//$thumb_src = wp_get_attachment_thumb_url( $context["attachment_id"] );
			$thumb_src = wp_get_attachment_image_src($attachment_id, array(350,500));
			$context["attachment_thumb"] = sprintf('<div class="simple-history-logitem--logger-SimpleMediaLogger--attachment-thumb"><img src="%1$s"></div>', $thumb_src[0] );


		} else if ($is_audio) {

			$content = sprintf('[audio src="%1$s"]', $file_url);
			$context["attachment_thumb"] .= do_shortcode( $content );

		} else if ($is_video) {

			$content = sprintf('[video src="%1$s"]', $file_url);
			$context["attachment_thumb"] .= do_shortcode( $content );

		}

		// plain icon
		//$thumb_src = wp_mime_type_icon( $context["attachment_mime"] );
		//$context["attachment_thumb"] = sprintf('<div class="simple-history-logitem--logger-SimpleMediaLogger--attachment-icon"><img src="%1$s"></div>', $thumb_src );
		
		$context["attachment_size_format"] = size_format( $row->context["attachment_filesize"] );
		$context["filetype"] = $filetype["ext"];

		$message .= "<p class='simple-history-logitem--logger-SimpleMediaLogger--attachment-meta-size'>" . __('{attachment_size_format}') . "</p>";

		if ( ! empty( $context["attachment_thumb"] ) ) {
			$message .= "" . __('{attachment_thumb}') . "";
		}

		$message .= "<p class='simple-history-logitem--logger-SimpleMediaLogger--attachment-open'>" . __('<a href="#">Open original</a> | <a href="#">Edit attachment</a> ') . "</p>";

		$output = $this->interpolate($message, $context);

		return $output;

	}

	function on_add_attachment($attachment_id) {

		$attachment_post = get_post( $attachment_id );
		$filename = esc_html( wp_basename( $attachment_post->guid ) );
		$mime = get_post_mime_type( $attachment_post );
		$file  = get_attached_file( $attachment_id );
		$file_size = false;

		if ( file_exists( $file ) ) {
			$file_size = filesize( $file );
		}

		$this->info(
			'Uploaded {post_type} "{attachment_filename}"',
			array(
				"post_type" => get_post_type($attachment_post),
				"attachment_id" => $attachment_id,
				"attachment_title" => get_the_title($attachment_post),
				"attachment_filename" => $filename,
				"attachment_mime" => $mime,
				"attachment_filesize" => $file_size
			)
		);

	}
	/*
	function on_edit_attachment($attachment_id) {
		// is this only being called if the title of the attachment is changed?!
		$post = get_post($attachment_id);
		$post_title = urlencode(get_the_title($post->ID));
		add("action=updated&object_type=attachment&object_id=$attachment_id&object_name=$post_title");
	}
	function on_delete_attachment($attachment_id) {
		$post = get_post($attachment_id);
		$post_title = urlencode(get_the_title($post->ID));
		add("action=deleted&object_type=attachment&object_id=$attachment_id&object_name=$post_title");
	}*/

}