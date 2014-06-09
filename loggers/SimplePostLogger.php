<?php

/**
 * Logs changes to posts and pages, including custom post types
 */
class SimplePostLogger extends SimpleLogger
{

	public $slug = "SimplePostLogger";

	public function __construct() {
		
		add_action("admin_init", array($this, "on_admin_init"));

	}

	function on_admin_init() {

		add_action("transition_post_status", array($this, "on_transition_post_status"), 10, 3);
		add_action("delete_post", array($this, "on_delete_post"));
		add_action("untrash_post", array($this, "on_untrash_post"));
		
	}

	/**
	 * Called when a post is restored from the trash
	 */
	function on_untrash_post($post_id) {

		$post = get_post($post_id);

		$this->info(
			'Restored {post_type} "{post_title}" from trash',
			array(
				"post_id" => $post_id,
				"post_type" => get_post_type($post),
				"post_title" => get_the_title($post)
			)
		);

	}

	/**
	 * Called when a post is deleted from the trash
	 */
	function on_delete_post($post_id) {
		
		$post = get_post($post_id);

		if ( wp_is_post_revision($post_id) ) {
			return;
		}

		if ( $post->post_status === "auto-draft" || $post->post_status === "inherit" ) {
			return;
		}

		$this->info(
			'Deleted {post_type} "{post_title}"',
			array(
				"post_id" => $post_id,
				"post_type" => get_post_type($post),
				"post_title" => get_the_title($post)
			)
		);

	}


	/**
	  * Fired when a post has changed status
	  */
	function on_transition_post_status($new_status, $old_status, $post) {

		// Don't log revisions
		if ( wp_is_post_revision( $post ) ) {
			return;
		}

		/*
		From new to auto-draft <- ignore
		From new to inherit <- ignore
		From auto-draft to draft <- page/post created
		From draft to draft
		From draft to pending
		From pending to publish
		From pending to trash
		if not from & to = same, then user has changed something
		*/

		$context = array(
			"post_id" => $post->ID,
			"post_type" => get_post_type($post),
			"post_title" => get_the_title($post)
		);

		if ($old_status == "auto-draft" && ($new_status != "auto-draft" && $new_status != "inherit")) {

			// Post created
			$this->info(
				'Created {post_type} "{post_title}"',
				$context
			);		

		} elseif ($new_status == "auto-draft" || ($old_status == "new" && $new_status == "inherit")) {

			// Hm... Not sure.
			return;

		} elseif ($new_status == "trash") {

			// Post trashed
			$this->info(
				'Moved {post_type} "{post_title}" to the trash',
				$context
			);		

		} else {

			// Post updated
			$this->info(
				'Updated {post_type} "{post_title}"',
				$context
			);		

		}

	}	

	/**
	 * Modify plain output to inlcude link to post
	 */
	public function getLogRowPlainTextOutput($row) {
	
		$message = __('Updated {post_type} <a href="{edit_link}">"{post_title}"</a>');

		$context = $row->context;
		$context["post_type"] = esc_html( $context["post_type"] );
		$context["post_title"] = esc_html( $context["post_title"] );
		$context["edit_link"] = get_edit_post_link( $context["post_id"] );

		return $this->interpolate($message, $context);

	}


}
