<?php
/**
 * Register Settings
 *
 * @package   ng-commentlove
 * @copyright Copyright (c) 2016, Nose Graze Ltd.
 * @license   GPL2+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get an Option
 *
 * Looks to see if the specified setting exists, returns the default if not.
 *
 * @param string $key     Key to retrieve
 * @param mixed  $default Default option
 *
 * @global       $ng_comment_love_options
 *
 * @since 1.1.0
 * @return mixed
 */
function ng_comment_love_get_option( $key = '', $default = false ) {
	global $ng_comment_love_options;

	$value = ( array_key_exists( $key, $ng_comment_love_options ) && ! empty( $ng_comment_love_options[ $key ] ) ) ? $ng_comment_love_options[ $key ] : $default;
	$value = apply_filters( 'ng-comment-love/options/get', $value, $key, $default );

	return apply_filters( 'ng-comment-love/options/get/' . $key, $value, $key, $default );
}

/**
 * Update an Option
 *
 * Updates an existing setting value in both the DB and the global variable.
 * Passing in an empty, false, or null string value will remove the key from the ng_comment_love_settings array.
 *
 * @param string $key   Key to update
 * @param mixed  $value The value to set the key to
 *
 * @global       $ng_comment_love_options
 *
 * @since 1.1.0
 * @return bool True if updated, false if not
 */
function ng_comment_love_update_option( $key = '', $value = false ) {
	// If no key, exit
	if ( empty( $key ) ) {
		return false;
	}

	if ( empty( $value ) ) {
		$remove_option = ng_comment_love_delete_option( $key );

		return $remove_option;
	}

	// First let's grab the current settings
	$options = get_option( 'ng_comment_love_settings' );

	// Let's let devs alter that value coming in
	$value = apply_filters( 'ng-comment-love/options/update', $value, $key );

	// Next let's try to update the value
	$options[ $key ] = $value;
	$did_update      = update_option( 'ng_comment_love_settings', $options );

	// If it updated, let's update the global variable
	if ( $did_update ) {
		global $ng_comment_love_options;
		$ng_comment_love_options[ $key ] = $value;
	}

	return $did_update;
}

/**
 * Remove an Option
 *
 * Removes an setting value in both the DB and the global variable.
 *
 * @param string $key The key to delete.
 *
 * @global       $ng_comment_love_options
 *
 * @since 1.1.0
 * @return boolean True if updated, false if not.
 */
function ng_comment_love_delete_option( $key = '' ) {
	// If no key, exit
	if ( empty( $key ) ) {
		return false;
	}

	// First let's grab the current settings
	$options = get_option( 'ng_comment_love_settings' );

	// Next let's try to update the value
	if ( isset( $options[ $key ] ) ) {
		unset( $options[ $key ] );
	}

	$did_update = update_option( 'ng_comment_love_settings', $options );

	// If it updated, let's update the global variable
	if ( $did_update ) {
		global $ng_comment_love_options;
		$ng_comment_love_options = $options;
	}

	return $did_update;
}

/**
 * Get Settings
 *
 * Retrieves all plugin settings
 *
 * @since 1.0
 * @return array NG Comment Love settings
 */
function ng_comment_love_get_settings() {
	$settings = get_option( 'ng_comment_love_settings', array() );

	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	return apply_filters( 'ng-comment-love/get-settings', $settings );
}

/**
 * Add all settings sections and fields.
 *
 * @since 1.1.0
 * @return void
 */
function ng_comment_love_register_settings() {

	if ( false == get_option( 'ng_comment_love_settings' ) ) {
		add_option( 'ng_comment_love_settings' );
	}

	foreach ( ng_comment_love_get_registered_settings() as $tab => $sections ) {
		foreach ( $sections as $section => $settings ) {
			add_settings_section(
				'ng_comment_love_settings_' . $tab . '_' . $section,
				__return_null(),
				'__return_false',
				'ng_comment_love_settings_' . $tab . '_' . $section
			);

			foreach ( $settings as $option ) {
				// For backwards compatibility
				if ( empty( $option['id'] ) ) {
					continue;
				}

				$name = isset( $option['name'] ) ? $option['name'] : '';

				add_settings_field(
					'ng_comment_love_settings[' . $option['id'] . ']',
					$name,
					function_exists( 'ng_comment_love_' . $option['type'] . '_callback' ) ? 'ng_comment_love_' . $option['type'] . '_callback' : 'ng_comment_love_missing_callback',
					'ng_comment_love_settings_' . $tab . '_' . $section,
					'ng_comment_love_settings_' . $tab . '_' . $section,
					array(
						'section'     => $section,
						'id'          => isset( $option['id'] ) ? $option['id'] : null,
						'desc'        => ! empty( $option['desc'] ) ? $option['desc'] : '',
						'name'        => isset( $option['name'] ) ? $option['name'] : null,
						'size'        => isset( $option['size'] ) ? $option['size'] : null,
						'options'     => isset( $option['options'] ) ? $option['options'] : '',
						'std'         => isset( $option['std'] ) ? $option['std'] : '',
						'min'         => isset( $option['min'] ) ? $option['min'] : null,
						'max'         => isset( $option['max'] ) ? $option['max'] : null,
						'step'        => isset( $option['step'] ) ? $option['step'] : null,
						'chosen'      => isset( $option['chosen'] ) ? $option['chosen'] : null,
						'placeholder' => isset( $option['placeholder'] ) ? $option['placeholder'] : null,
						'input-type'  => isset( $option['input-type'] ) ? $option['input-type'] : 'text'
					)
				);
			}
		}
	}

	// Creates our settings in the options table
	register_setting( 'ng_comment_love_settings', 'ng_comment_love_settings', 'ng_comment_love_settings_sanitize' );

}

add_action( 'admin_init', 'ng_comment_love_register_settings' );

/**
 * Registered Settings
 *
 * Sets and returns the array of all plugin settings.
 * Developers can use the following filters to add their own settings or
 * modify existing ones:
 *
 *  + ng-comment-love/settings/{key} - Where {key} is a specific tab. Used to modify a single tab/section.
 *  + ng-comment-love/settings/registered-settings - Includes the entire array of all settings.
 *
 * @since 1.1.0
 * @return array
 */
function ng_comment_love_get_registered_settings() {

	$ng_comment_love_settings = array(
		'general' => apply_filters( 'ng-comment-love/settings/general', array(
			'main' => array(
				'link_type'          => array(
					'id'      => 'link_type',
					'name'    => __( 'Link Type', 'ng-comment-love' ),
					'desc'    => __( 'Choosing "dofollow" will give the commenters some SEO juice for their links. "nofollow" will tell Google not to follow their link, and thus not pass along any SEO juice to the commenter.', 'ng-comment-love' ),
					'type'    => 'select',
					'options' => array(
						'nofollow' => esc_html__( 'nofollow', 'ng-comment-love' ),
						'dofollow' => esc_html__( 'dofollow', 'ng-comment-love' )
					),
					'std'     => 'nofollow'
				),
				'show_for_logged_in' => array(
					'id'      => 'show_for_logged_in',
					'name'    => __( 'Logged In Users', 'ng-comment-love' ),
					'desc'    => __( 'Whether or not logged in users can add love to their comments.', 'ng-comment-love' ),
					'type'    => 'select',
					'options' => array(
						'yes' => esc_html__( 'Yes', 'ng-comment-love' ),
						'no'  => esc_html__( 'No', 'ng-comment-love' )
					),
					'std'     => 'yes'
				),
				'numb_blog_posts'    => array(
					'id'         => 'numb_blog_posts',
					'name'       => __( 'Number of Blog Posts', 'ng-comment-love' ),
					'desc'       => __( 'This is the number of blog posts the plugin will fetch from their site. They\'ll be able to choose one of this many.', 'ng-comment-love' ),
					'type'       => 'text',
					'std'        => '10',
					'input-type' => 'number'
				),
			)
		) ),
		'text'    => apply_filters( 'ng-comment-love/settings/text', array(
			'main' => array(
				'text_comment_form'      => array(
					'id'   => 'text_comment_form',
					'name' => esc_html__( 'Comment Form Text', 'ng-comment-love' ),
					'desc' => sprintf( __( 'Text to appear below the comment form. Must contain an anchor tag with the ID %s', 'ng-comment-love' ), '<code>comment-love-get-posts</code>' ),
					'type' => 'textarea',
					'std'  => __( '(Enter your URL then <a href="#" id="comment-love-get-posts">click here</a> to include a link to one of your blog posts.)', 'ng-comment-love' )
				),
				'text_button'            => array(
					'id'   => 'text_button',
					'name' => esc_html__( 'Button Text', 'ng-comment-love' ),
					'desc' => __( 'Text inside the button for fetching a blog post.', 'ng-comment-love' ),
					'type' => 'text',
					'std'  => __( 'Find a Post', 'ng-comment-love' )
				),
				'text_recently_posted'   => array(
					'id'   => 'text_recently_posted',
					'name' => esc_html__( 'Recently Posted', 'ng-comment-love' ),
					'desc' => sprintf( __( 'Displayed below the comment text after a comment with love is submitted. Use %1$s as a placeholder for the commenter\'s name and %2$s as a placeholder for the blog post link.', 'ng-comment-love' ), '[name]', '[post]' ),
					'type' => 'textarea',
					'std'  => sprintf( __( '%1$s recently posted: %2$s', 'ng-comment-love' ), '[name]', '[post]' )
				),
				'text_error_website_url' => array(
					'id'   => 'text_error_website_url',
					'name' => esc_html__( 'Error Text: Website URL', 'ng-comment-love' ),
					'desc' => __( 'Error message that appears after pressing the button but if no website URL is entered.', 'ng-comment-love' ),
					'type' => 'textarea',
					'std'  => __( 'Please enter your website URL first!', 'ng-comment-love' )
				),
				'text_error_no_posts'    => array(
					'id'   => 'text_error_no_posts',
					'name' => esc_html__( 'Error Text: No Posts', 'ng-comment-love' ),
					'desc' => __( 'Error message that appears after pressing the button but if no posts can be found (or if there\'s an issue communicating with the site).', 'ng-comment-love' ),
					'type' => 'textarea',
					'std'  => __( 'No posts found', 'ng-comment-love' )
				),
			)
		) )
	);

	return apply_filters( 'ng-comment-love/settings/registered-settings', $ng_comment_love_settings );

}

/**
 * Sanitize Settings
 *
 * Adds a settings error for the updated message.
 *
 * @param array  $input                   The value inputted in the field
 *
 * @global array $ng_comment_love_options Array of all the NG Comment Love options
 *
 * @since 1.1.0
 * @return array New, sanitized settings.
 */
function ng_comment_love_settings_sanitize( $input = array() ) {

	global $ng_comment_love_options;

	if ( empty( $_POST['_wp_http_referer'] ) ) {
		return $input;
	}

	parse_str( $_POST['_wp_http_referer'], $referrer );

	$settings = ng_comment_love_get_registered_settings();
	$tab      = isset( $referrer['tab'] ) ? $referrer['tab'] : 'general';
	$section  = isset( $referrer['section'] ) ? $referrer['section'] : 'main';

	$input = $input ? $input : array();
	$input = apply_filters( 'ng-comment-love/settings/sanitize/' . $tab . '/' . $section, $input );

	// Loop through each setting being saved and pass it through a sanitization filter
	foreach ( $input as $key => $value ) {
		// Get the setting type (checkbox, select, etc)
		$type = isset( $settings[ $tab ][ $section ][ $key ]['type'] ) ? $settings[ $tab ][ $section ][ $key ]['type'] : false;
		if ( $type ) {
			// Field type specific filter
			$input[ $key ] = apply_filters( 'ng-comment-love/settings/sanitize/' . $type, $value, $key );
		}
		// General filter
		$input[ $key ] = apply_filters( 'ng-comment-love/settings/sanitize', $input[ $key ], $key );
	}

	// Loop through the whitelist and unset any that are empty for the tab being saved
	$main_settings    = $section == 'main' ? $settings[ $tab ] : array(); // Check for extensions that aren't using new sections
	$section_settings = ! empty( $settings[ $tab ][ $section ] ) ? $settings[ $tab ][ $section ] : array();
	$found_settings   = array_merge( $main_settings, $section_settings );

	if ( ! empty( $found_settings ) ) {
		foreach ( $found_settings as $key => $value ) {
			if ( empty( $input[ $key ] ) && array_key_exists( $key, $ng_comment_love_options ) ) {
				unset( $ng_comment_love_options[ $key ] );
			}
		}
	}

	// Merge our new settings with the existing
	$output = array_merge( $ng_comment_love_options, $input );

	add_settings_error( 'ng-comment-love-notices', '', __( 'Settings updated.', 'ng-comment-love' ), 'updated' );

	return $output;

}

/**
 * Retrieve settings tabs
 *
 * @since 1.1.0
 * @return array $tabs
 */
function ng_comment_love_get_settings_tabs() {
	$tabs            = array();
	$tabs['general'] = __( 'General', 'ng-comment-love' );
	$tabs['text']    = __( 'Text', 'ng-comment-love' );

	return apply_filters( 'ng-comment-love/settings/tabs', $tabs );
}


/**
 * Retrieve settings tabs
 *
 * @since 1.1.0
 * @return array $section
 */
function ng_comment_love_get_settings_tab_sections( $tab = false ) {
	$tabs     = false;
	$sections = ng_comment_love_get_registered_settings_sections();

	if ( $tab && ! empty( $sections[ $tab ] ) ) {
		$tabs = $sections[ $tab ];
	} else if ( $tab ) {
		$tabs = false;
	}

	return $tabs;
}

/**
 * Get the settings sections for each tab
 * Uses a static to avoid running the filters on every request to this function
 *
 * @since  1.1.0
 * @return array Array of tabs and sections
 */
function ng_comment_love_get_registered_settings_sections() {
	static $sections = false;

	if ( false !== $sections ) {
		return $sections;
	}

	$sections = array(
		'general' => apply_filters( 'ng-comment-love/settings/sections/general', array(
			'main' => __( 'General', 'ng-comment-love' )
		) ),
		'text'    => apply_filters( 'ng-comment-love/settings/sections/text', array(
			'main' => __( 'Text', 'ng-comment-love' )
		) )
	);

	$sections = apply_filters( 'ng-comment-love/settings/sections', $sections );

	return $sections;
}

/**
 * Sanitizes a string key for NG Comment Love Settings
 *
 * Keys are used as internal identifiers. Alphanumeric characters, dashes, underscores, stops, colons and slashes are
 * allowed
 *
 * @param  string $key String key
 *
 * @since 1.1.0
 * @return string Sanitized key
 */
function ng_comment_love_sanitize_key( $key ) {
	$raw_key = $key;
	$key     = preg_replace( '/[^a-zA-Z0-9_\-\.\:\/]/', '', $key );

	return apply_filters( 'ng-comment-love/sanitize-key', $key, $raw_key );
}

/**
 * Missing Callback
 *
 * If a function is missing for settings callbacks alert the user.
 *
 * @param array $args Arguments passed by the setting
 *
 * @since 1.1.0
 * @return void
 */
function ng_comment_love_missing_callback( $args ) {
	printf(
		__( 'The callback function used for the %s setting is missing.', 'ng-comment-love' ),
		'<strong>' . $args['id'] . '</strong>'
	);
}

/**
 * Text Callback
 *
 * Renders text fields.
 *
 * @param array  $args                    Arguments passed by the setting
 *
 * @global array $ng_comment_love_options Array of all the NG Comment Love settings
 *
 * @since 1.1.0
 * @return void
 */
function ng_comment_love_text_callback( $args ) {
	global $ng_comment_love_options;

	if ( isset( $ng_comment_love_options[ $args['id'] ] ) ) {
		$value = $ng_comment_love_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$args['readonly'] = true;
		$value            = isset( $args['std'] ) ? $args['std'] : '';
		$name             = '';
	} else {
		$name = 'name="ng_comment_love_settings[' . esc_attr( $args['id'] ) . ']"';
	}

	$type = array_key_exists( 'input-type', $args ) ? $args['input-type'] : 'text';

	$readonly = ( array_key_exists( 'readonly', $args ) && $args['readonly'] ) === true ? ' readonly="readonly"' : '';
	$size     = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	?>
	<input type="<?php echo esc_attr( $type ); ?>" class="<?php echo sanitize_html_class( $size ); ?>-text" id="ng_comment_love_settings[<?php echo ng_comment_love_sanitize_key( $args['id'] ); ?>]" <?php echo $name; ?> value="<?php echo esc_attr( stripslashes( $value ) ); ?>"<?php echo $readonly; ?>>
	<label for="ng_comment_love_settings[<?php echo ng_comment_love_sanitize_key( $args['id'] ); ?>]" class="desc"><?php echo wp_kses_post( $args['desc'] ); ?></label>
	<?php
}

/**
 * Header Callback
 *
 * Simply renders a title and description.
 *
 * @param array $args Arguments passed by the setting
 *
 * @since 1.1.0
 * @return void
 */
function ng_comment_love_header_callback( $args ) {
	if ( array_key_exists( 'desc', $args ) ) {
		echo '<div class="desc">' . wp_kses_post( $args['desc'] ) . '</div>';
	}
}

/**
 * Textarea Callback
 *
 * Renders textarea fields.
 *
 * @param array  $args                    Arguments passed by the setting
 *
 * @global array $ng_comment_love_options Array of all the NG Comment Love settings
 *
 * @since 1.1.0
 * @return void
 */
function ng_comment_love_textarea_callback( $args ) {
	global $ng_comment_love_options;

	if ( isset( $ng_comment_love_options[ $args['id'] ] ) ) {
		$value = $ng_comment_love_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}
	?>
	<textarea class="large-text" id="ng_comment_love_settings[<?php echo ng_comment_love_sanitize_key( $args['id'] ); ?>]" name="ng_comment_love_settings[<?php echo esc_attr( $args['id'] ); ?>]" rows="10" cols="50"><?php echo esc_textarea( $value ); ?></textarea>
	<label for="ng_comment_love_settings[<?php echo ng_comment_love_sanitize_key( $args['id'] ); ?>]" class="desc"><?php echo wp_kses_post( $args['desc'] ); ?></label>
	<?php
}

/**
 * Color picker Callback
 *
 * Renders color picker fields.
 *
 * @param array  $args                    Arguments passed by the setting
 *
 * @global array $ng_comment_love_options Array of all the EDD Options
 *
 * @since 1.1.0
 * @return void
 */
function ng_comment_love_color_callback( $args ) {
	global $ng_comment_love_options;

	if ( isset( $ng_comment_love_options[ $args['id'] ] ) ) {
		$value = $ng_comment_love_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$default = isset( $args['std'] ) ? $args['std'] : '';
	?>
	<input type="text" class="ama-color-picker" id="ng_comment_love_settings[<?php echo ng_comment_love_sanitize_key( $args['id'] ); ?>" name="ng_comment_love_settings[<?php echo esc_attr( $args['id'] ); ?>]" value="<?php echo esc_attr( $value ); ?>" data-default-color="<?php echo esc_attr( $default ); ?>">
	<label for="ng_comment_love_settings[<?php echo ng_comment_love_sanitize_key( $args['id'] ); ?>" class="desc"><?php echo wp_kses_post( $args['desc'] ); ?></label>
	<?php
}

/**
 * Checkbox Callback
 *
 * Renders a checkbox field.
 *
 * @param array  $args                    Arguments passed by the setting
 *
 * @global array $ng_comment_love_options Array of all the NG Comment Love settings
 *
 * @since 1.1.0
 * @return void
 */
function ng_comment_love_checkbox_callback( $args ) {
	global $ng_comment_love_options;

	$checked = isset( $ng_comment_love_options[ $args['id'] ] ) ? checked( 1, $ng_comment_love_options[ $args['id'] ], false ) : '';
	?>
	<input type="checkbox" id="ng_comment_love_settings[<?php echo ng_comment_love_sanitize_key( $args['id'] ); ?>]" name="ng_comment_love_settings[<?php echo ng_comment_love_sanitize_key( $args['id'] ); ?>]" value="1" <?php echo $checked; ?>>
	<label for="ng_comment_love_settings[<?php echo ng_comment_love_sanitize_key( $args['id'] ); ?>]" class="desc"><?php echo wp_kses_post( $args['desc'] ); ?></label>
	<?php
}

/**
 * Select Callback
 *
 * Renders select fields.
 *
 * @param array  $args                    Arguments passed by the setting
 *
 * @global array $ng_comment_love_options Array of all the NG Comment Love Options
 *
 * @since 1.1.0
 * @return void
 */
function ng_comment_love_select_callback( $args ) {
	global $ng_comment_love_options;

	if ( isset( $ng_comment_love_options[ $args['id'] ] ) ) {
		$value = $ng_comment_love_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( isset( $args['placeholder'] ) ) {
		$placeholder = $args['placeholder'];
	} else {
		$placeholder = '';
	}

	if ( isset( $args['chosen'] ) ) {
		$chosen = 'class="ng-comment-love-chosen"';
	} else {
		$chosen = '';
	}

	$html = '<select id="ng_comment_love_settings[' . ng_comment_love_sanitize_key( $args['id'] ) . ']" name="ng_comment_love_settings[' . esc_attr( $args['id'] ) . ']" ' . $chosen . 'data-placeholder="' . esc_html( $placeholder ) . '">';

	foreach ( $args['options'] as $option => $name ) {
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
	}

	$html .= '</select>';
	$html .= '<label for="ng_comment_love_settings[' . ng_comment_love_sanitize_key( $args['id'] ) . ']" class="desc"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

/**
 * TinyMCE Callback
 *
 * Renders a rich text editor.
 *
 * @param array  $args                    Arguments passed by the setting
 *
 * @global array $ng_comment_love_options Array of all the NG Comment Love Options
 *
 * @since 1.1.0
 * @return void
 */
function ng_comment_love_tinymce_callback( $args ) {
	global $ng_comment_love_options;

	if ( isset( $ng_comment_love_options[ $args['id'] ] ) ) {
		$value = $ng_comment_love_options[ $args['id'] ];

		if ( empty( $args['allow_blank'] ) && empty( $value ) ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$rows = isset( $args['size'] ) ? $args['size'] : 20;

	wp_editor( stripslashes( $value ), 'ng_comment_love_settings' . esc_attr( $args['id'] ), array(
		'textarea_name' => 'ng_comment_love_settings[' . esc_attr( $args['id'] ) . ']',
		'textarea_rows' => absint( $rows )
	) );
	?>
	<br>
	<label for="ng_comment_love_settings[<?php echo ng_comment_love_sanitize_key( $args['id'] ); ?>]" class="desc">
		<?php echo wp_kses_post( $args['desc'] ); ?>
	</label>
	<?php
}