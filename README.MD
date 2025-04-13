# Bbioon Login Notification

A WordPress plugin that sends email notifications when users log in to your site, with customizable settings for notifications and user roles.

## Features

- Receive email notifications when users log in
- Customize which user roles trigger notifications
- Exclude specific users from notifications
- Customizable email subject and content with dynamic tags
- Test email functionality to verify settings
- Comprehensive logging of all notifications
- Multilingual support (ready for translation)

## Installation

1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and click "Install Now"
4. Activate the plugin
5. Configure settings at Settings → Login Notification

## Configuration

After activation, configure the plugin through the WordPress admin panel:

1. **Notification Email**: Set the email address that will receive notifications (defaults to admin email)
2. **User Roles**: Select which user roles should trigger notifications (default: Administrator)
3. **Excluded Users**: Enter comma-separated user IDs to exclude from notifications
4. **Email Subject**: Customize the subject line with available tags
5. **Email Content**: Customize the email body content with available tags

## Available Tags

Use these dynamic tags in your email subject and content:

- `[username]`: The user's login name
- `[first_name]`: The user's first name
- `[last_name]`: The user's last name
- `[time]`: The login time

## Test Email

You can send a test email from the settings page to verify your configuration. The test will use your current user information.

## Notification Logs

The plugin maintains logs of the last 100 notifications, showing:
- Time of login
- User information
- Notification status (success/failure)
- Any error messages

Logs can be cleared manually from the settings page.

## Frequently Asked Questions

### Why am I not receiving notifications?

1. Verify your email settings are correct
2. Check that the user role is selected in settings
3. Verify the user isn't in the excluded list
4. Check your spam folder
5. Use the test email function to verify your setup

### Can I send notifications to multiple email addresses?

Currently, notifications are sent to a single email address. For multiple recipients, use an email alias or forwarding rule.

### How do I customize the email content?

Edit the "Email Content" field in the settings. You can use HTML tags and the available dynamic tags.

## Changelog

### 1.0.0
* Initial release with core functionality:
    * Email notifications on login
    * Role-based filtering
    * User exclusion
    * Customizable email templates
    * Notification logs

## Contributing

Contributions are welcome! Please fork the repository and submit a pull request.

## License

This plugin is licensed under the GPL-2.0+ license.