# Bbioon Login Notification

A WordPress plugin that sends email notifications when users log in to your site, with customizable settings for notifications and user roles.

## Features

- Email notifications on user login
- Customizable user roles for notifications
- Exclude specific users from notifications
- Customizable email subject and content with dynamic tags
- Test email functionality to verify settings
- Comprehensive logging of notifications (last 100)
- Multilingual support (translation-ready)
- Session-based notification to prevent duplicate emails

## Installation

1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and click "Install Now"
4. Activate the plugin
5. Configure settings at Settings → Login Notification

## Configuration

After activation, configure the plugin through the WordPress admin panel:

1. **Notification Email**: Set the email address for notifications (defaults to admin email)
2. **User Roles**: Select user roles to trigger notifications (default: Administrator, Editor)
3. **Excluded Users**: Enter comma-separated user IDs to exclude
4. **Email Subject**: Customize with dynamic tags
5. **Email Content**: Customize with HTML and dynamic tags

## Available Tags

- `[username]`: User's login name
- `[first_name]`: User's first name
- `[last_name]`: User's last name
- `[time]`: Login time

## Test Email

Send a test email from the settings page to verify configuration using your current user information.

## Notification Logs

View the last 100 notifications, including:
- Login time
- User details (ID, username, name, email)
- Notification status (success/failure)
- Error messages

Logs can be cleared manually from the settings page.

## Frequently Asked Questions

### Why am I not receiving notifications?

1. Verify email settings
2. Check selected user roles
3. Ensure user isn't excluded
4. Check spam folder
5. Use test email to verify

### Can I send notifications to multiple email addresses?

Use an email alias or forwarding rule for multiple recipients (single email supported).

### How do I customize email content?

Edit the "Email Content" field in settings with HTML and dynamic tags.

## Changelog

### 1.0.1
* Added session-based notification check to prevent duplicate emails
* Improved code documentation with detailed comments

### 1.0.0
* Initial release with core functionality:
    * Email notifications on login
    * Role-based filtering
    * User exclusion
    * Customizable email templates
    * Notification logs

## Contributing

Fork the repository and submit a pull request.

## License

GPL-2.0+