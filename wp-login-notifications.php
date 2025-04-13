<?php
/**
 * Plugin Name: Bbioon Login Notification
 * Description: Sends email notification when users login with customizable settings
 * Version: 1.0.0
 * Author: Ahmad Wael
 * License: GPL-2.0+
 * Text Domain: bbioon-login-notification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Prevent direct access to the file
}

/**
 * Class Bbioon_Login_Notification
 * Main class for handling login notifications and plugin settings
 */
class Bbioon_Login_Notification {
	/**
	 * Singleton instance of the class
	 *
	 * @var Bbioon_Login_Notification
	 */
	private static $instance;

	/**
	 * Plugin settings stored in the database
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Get the singleton instance of the class
	 *
	 * @return Bbioon_Login_Notification
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor to initialize hooks and settings
	 */
	private function __construct() {
		// Load default settings or existing ones from the database
		$this->settings = get_option( 'bbioon_login_notification_settings', [
			'email'          => get_option( 'admin_email' ),
			'roles'          => [ 'administrator', 'editor' ],
			'excluded_users' => '',
			'subject'        => 'User [username] Logged In',
			'content'        => "User [username] ([first_name] [last_name]) logged in at [time].",
		] );

		// Register WordPress hooks
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'wp_login', [ $this, 'send_notification' ], 10, 2 );
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'admin_post_bbioon_login_notification_test_email', [ $this, 'send_test_email' ] );
		add_action( 'admin_post_bbioon_login_notification_clear_logs', [ $this, 'clear_logs' ] );
	}

	/**
	 * Load plugin text domain for translations
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'bbioon-login-notification', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Add settings page to WordPress admin menu
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Login Notification Settings', 'bbioon-login-notification' ),
			__( 'Login Notification', 'bbioon-login-notification' ),
			'manage_options',
			'bbioon-login-notification',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Register plugin settings with WordPress
	 */
	public function register_settings() {
		register_setting(
			'bbioon_login_notification_group',
			'bbioon_login_notification_settings',
			[ $this, 'sanitize_settings' ]
		);
	}

	/**
	 * Sanitize settings input before saving
	 *
	 * @param array $input Raw input from settings form
	 *
	 * @return array Sanitized settings
	 */
	public function sanitize_settings( $input ) {
		$sanitized                   = [];
		$sanitized['email']          = sanitize_email( $input['email'] ?? '' );
		$sanitized['roles']          = array_map( 'sanitize_text_field', (array) ( $input['roles'] ?? [] ) );
		$sanitized['excluded_users'] = sanitize_text_field( $input['excluded_users'] ?? '' );
		$sanitized['subject']        = sanitize_text_field( $input['subject'] ?? '' );
		$sanitized['content']        = wp_kses_post( $input['content'] ?? '' );

		return $sanitized;
	}

	/**
	 * Log notification details to the database
	 *
	 * @param int     $user_id User ID
	 * @param string  $status  Status (success/failure)
	 * @param string  $message Log message
	 * @param WP_User $user    User object
	 */
	private function log_notification( $user_id, $status, $message, $user ) {
		$log_entry = [
			'time'       => current_time( 'mysql' ),
			'user_id'    => $user_id,
			'username'   => $user->user_login,
			'first_name' => $user->first_name,
			'last_name'  => $user->last_name,
			'email'      => $user->user_email,
			'status'     => $status,
			'message'    => $message,
		];

		// Retrieve existing logs and append new entry
		$logs   = get_option( 'bbioon_login_notification_logs', [] );
		$logs[] = $log_entry;
		// Limit to 100 logs to prevent excessive storage
		if ( count( $logs ) > 100 ) {
			$logs = array_slice( $logs, - 100 );
		}
		update_option( 'bbioon_login_notification_logs', $logs );
	}

	/**
	 * Clear all notification logs
	 */
	public function clear_logs() {
		// Verify nonce for security
		check_admin_referer( 'bbioon_login_notification_clear_logs' );
		// Reset logs option to empty array
		update_option( 'bbioon_login_notification_logs', [] );
		// Redirect with success message
		wp_safe_redirect( add_query_arg( [
			'page'    => 'bbioon-login-notification',
			'message' => 'logs_cleared',
		], admin_url( 'options-general.php' ) ) );
		exit;
	}

	/**
	 * Render the settings page in WordPress admin
	 */
	public function render_settings_page() {
		$logs = get_option( 'bbioon_login_notification_logs', [] );
		?>
        <div class="wrap">
            <h1><?php
				esc_html_e( 'Login Notification Settings', 'bbioon-login-notification' ); ?></h1>
            <!-- Settings form -->
            <form method="post" action="options.php">
				<?php
				settings_fields( 'bbioon_login_notification_group' );
				do_settings_sections( 'bbioon_login_notification_group' );
				?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="bbioon_login_notification_settings[email]">
								<?php
								esc_html_e( 'Notification Email', 'bbioon-login-notification' ); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                    type="email"
                                    name="bbioon_login_notification_settings[email]"
                                    id="bbioon_login_notification_settings[email]"
                                    value="<?php
									echo esc_attr( $this->settings['email'] ); ?>"
                                    class="regular-text"
                            />
                            <p class="description">
								<?php
								esc_html_e( 'The email address that will receive login notifications. Defaults to the WordPress admin email.', 'bbioon-login-notification' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php
							esc_html_e( 'User Roles', 'bbioon-login-notification' ); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php
	                                    esc_html_e( 'User Roles', 'bbioon-login-notification' ); ?></span>
                                </legend>
                                <p class="description">
									<?php
									esc_html_e( 'Select which user roles should trigger notifications when they log in. By default, only Administrator logins are tracked.',
										'bbioon-login-notification' ); ?>
                                </p>
								<?php
								// Display all editable roles as checkboxes
								foreach ( get_editable_roles() as $role_name => $role_info ):
									?>
                                    <label>
                                        <input
                                                type="checkbox"
                                                name="bbioon_login_notification_settings[roles][]"
                                                value="<?php
												echo esc_attr( $role_name ); ?>"
											<?php
											checked( in_array( $role_name, $this->settings['roles'], true ) ); ?>
                                        />
										<?php
										echo esc_html( $role_info['name'] ); ?>
                                    </label><br/>
								<?php
								endforeach; ?>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bbioon_login_notification_settings[excluded_users]">
								<?php
								esc_html_e( 'Excluded User IDs', 'bbioon-login-notification' ); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                    type="text"
                                    name="bbioon_login_notification_settings[excluded_users]"
                                    id="bbioon_login_notification_settings[excluded_users]"
                                    value="<?php
									echo esc_attr( $this->settings['excluded_users'] ); ?>"
                                    class="regular-text"
                            />
                            <p class="description">
								<?php
								esc_html_e( 'Comma-separated list of user IDs to exclude from notifications. These users will not trigger notifications when they log in.',
									'bbioon-login-notification' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bbioon_login_notification_settings[subject]">
								<?php
								esc_html_e( 'Email Subject', 'bbioon-login-notification' ); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                    type="text"
                                    name="bbioon_login_notification_settings[subject]"
                                    id="bbioon_login_notification_settings[subject]"
                                    value="<?php
									echo esc_attr( $this->settings['subject'] ); ?>"
                                    class="regular-text"
                            />
                            <p class="description">
								<?php
								esc_html_e( 'Subject line for the notification email. Available tags: [username], [first_name], [last_name], [time] - these will be replaced with actual user data.',
									'bbioon-login-notification' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bbioon_login_notification_settings[content]">
								<?php
								esc_html_e( 'Email Content', 'bbioon-login-notification' ); ?>
                            </label>
                        </th>
                        <td>
                            <textarea
                                    name="bbioon_login_notification_settings[content]"
                                    id="bbioon_login_notification_settings[content]"
                                    class="large-text"
                                    rows="6"
                            ><?php
	                            echo esc_textarea( $this->settings['content'] ); ?></textarea>
                            <p class="description">
								<?php
								esc_html_e( 'Content of the notification email. HTML is allowed. Available tags: [username], [first_name], [last_name], [time] - these will be replaced with actual user data.',
									'bbioon-login-notification' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
				<?php
				submit_button(); ?>
            </form>

            <!-- Test email form -->
            <h2><?php
				esc_html_e( 'Test Notification', 'bbioon-login-notification' ); ?></h2>
            <p class="description">
				<?php
				esc_html_e( 'Send a test email to verify your notification settings are working correctly. The test will use your current user information.',
					'bbioon-login-notification' ); ?>
            </p>
            <form action="<?php
			echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
                <input type="hidden" name="action" value="bbioon_login_notification_test_email">
				<?php
				wp_nonce_field( 'bbioon_login_notification_test_email' ); ?>
                <p>
                    <input type="submit" class="button button-secondary" value="<?php
					esc_attr_e( 'Send Test Email', 'bbioon-login-notification' ); ?>">
                </p>
            </form>

            <!-- Logs section -->
            <h2><?php
				esc_html_e( 'Notification Logs', 'bbioon-login-notification' ); ?></h2>
            <p class="description">
				<?php
				esc_html_e( 'View the last 100 login notifications sent by the system. Logs are automatically trimmed to prevent excessive database usage.',
					'bbioon-login-notification' ); ?>
            </p>
            <form action="<?php
			echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
                <input type="hidden" name="action" value="bbioon_login_notification_clear_logs">
				<?php
				wp_nonce_field( 'bbioon_login_notification_clear_logs' ); ?>
                <p>
                    <input type="submit" class="button button-secondary" value="<?php
					esc_attr_e( 'Clear Logs', 'bbioon-login-notification' ); ?>">
                </p>
            </form>
			<?php
			if ( empty( $logs ) ): ?>
                <p><?php
					esc_html_e( 'No notifications logged yet.', 'bbioon-login-notification' ); ?></p>
			<?php
			else: ?>
                <table class="widefat fixed">
                    <thead>
                    <tr>
                        <th><?php
							esc_html_e( 'Time', 'bbioon-login-notification' ); ?></th>
                        <th><?php
							esc_html_e( 'User ID', 'bbioon-login-notification' ); ?></th>
                        <th><?php
							esc_html_e( 'Username', 'bbioon-login-notification' ); ?></th>
                        <th><?php
							esc_html_e( 'First Name', 'bbioon-login-notification' ); ?></th>
                        <th><?php
							esc_html_e( 'Last Name', 'bbioon-login-notification' ); ?></th>
                        <th><?php
							esc_html_e( 'Email', 'bbioon-login-notification' ); ?></th>
                        <th><?php
							esc_html_e( 'Status', 'bbioon-login-notification' ); ?></th>
                        <th><?php
							esc_html_e( 'Message', 'bbioon-login-notification' ); ?></th>
                    </tr>
                    </thead>
                    <tbody>
					<?php
					foreach ( array_reverse( $logs ) as $log ): ?>
                        <tr>
                            <td><?php
								echo esc_html( $log['time'] ); ?></td>
                            <td><?php
								echo esc_html( $log['user_id'] ); ?></td>
                            <td><?php
								echo esc_html( $log['username'] ); ?></td>
                            <td><?php
								echo esc_html( $log['first_name'] ); ?></td>
                            <td><?php
								echo esc_html( $log['last_name'] ); ?></td>
                            <td><?php
								echo esc_html( $log['email'] ); ?></td>
                            <td><?php
								echo esc_html( $log['status'] ); ?></td>
                            <td><?php
								echo esc_html( $log['message'] ); ?></td>
                        </tr>
					<?php
					endforeach; ?>
                    </tbody>
                </table>
			<?php
			endif; ?>
        </div>
		<?php
	}

	/**
	 * Send a test notification email
	 */
	public function send_test_email() {
		// Verify nonce for security
		check_admin_referer( 'bbioon_login_notification_test_email' );

		// Check if email is configured
		if ( empty( $this->settings['email'] ) ) {
			wp_safe_redirect( add_query_arg( [
				'page'  => 'bbioon-login-notification',
				'error' => 'no_email',
			], admin_url( 'options-general.php' ) ) );
			exit;
		}

		// Get current user for test email
		$user         = wp_get_current_user();
		$replacements = [
			'[username]'   => $user->user_login,
			'[first_name]' => $user->first_name,
			'[last_name]'  => $user->last_name,
			'[time]'       => current_time( 'mysql' ),
		];

		// Prepare email subject and content
		$subject = str_replace(
			array_keys( $replacements ),
			array_values( $replacements ),
			$this->settings['subject']
		);

		$content = str_replace(
			array_keys( $replacements ),
			array_values( $replacements ),
			$this->settings['content']
		);

		// Send email
		$sent = wp_mail(
			$this->settings['email'],
			$subject,
			$content,
			[ 'Content-Type: text/html; charset=UTF-8' ]
		);

		// Log the attempt
		$this->log_notification(
			$user->ID,
			$sent ? 'success' : 'failure',
			$sent ? 'Test email sent' : 'Failed to send test email',
			$user
		);

		// Redirect with result
		wp_safe_redirect( add_query_arg( [
			'page'    => 'bbioon-login-notification',
			'message' => $sent ? 'test_sent' : 'test_failed',
		], admin_url( 'options-general.php' ) ) );
		exit;
	}

	/**
	 * Send notification email on user login
	 *
	 * @param string  $user_login Username
	 * @param WP_User $user       User object
	 */
	public function send_notification( $user_login, $user ) {
		// Exit if no email is configured
		if ( empty( $this->settings['email'] ) ) {
			return;
		}

		// Check if user is excluded
		$excluded = array_map( 'trim', explode( ',', $this->settings['excluded_users'] ) );
		if ( in_array( $user->ID, $excluded, true ) ) {
			return;
		}

		// Check if user role is selected
		$user_roles = (array) $user->roles;
		if ( ! empty( $this->settings['roles'] ) && ! array_intersect( $user_roles, $this->settings['roles'] ) ) {
			return;
		}

		// Prepare replacement tags
		$replacements = [
			'[username]'   => $user->user_login,
			'[first_name]' => $user->first_name,
			'[last_name]'  => $user->last_name,
			'[time]'       => current_time( 'mysql' ),
		];

		// Prepare email subject and content
		$subject = str_replace(
			array_keys( $replacements ),
			array_values( $replacements ),
			$this->settings['subject']
		);

		$content = str_replace(
			array_keys( $replacements ),
			array_values( $replacements ),
			$this->settings['content']
		);

		// Send email
		$sent = wp_mail(
			$this->settings['email'],
			$subject,
			$content,
			[ 'Content-Type: text/html; charset=UTF-8' ]
		);

		// Log the attempt
		$this->log_notification(
			$user->ID,
			$sent ? 'success' : 'failure',
			$sent ? 'Notification sent' : 'Failed to send notification',
			$user
		);
	}
}

// Initialize the plugin
Bbioon_Login_Notification::get_instance();