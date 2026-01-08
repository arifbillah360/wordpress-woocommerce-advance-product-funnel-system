=== Gym Multi-Participant Booking ===
Contributors: yourusername
Tags: woocommerce, booking, gym, fitness, multi-participant, email, participant management
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allow customers to book multiple gym slots for different participants and automatically send confirmation emails to each person.

== Description ==

**Gym Multi-Participant Booking** is a WooCommerce extension that enables product-specific multi-participant bookings with individual email confirmations.

Perfect for:

* Gyms and fitness centers
* Yoga studios
* Sports facilities
* Group classes and workshops
* Personal training sessions
* Any booking scenario with multiple participants

= Key Features =

* **Product-Specific**: Enable participant booking only for selected products
* **Dynamic Forms**: Participant fields appear automatically based on quantity
* **Individual Emails**: Each participant receives their own confirmation email
* **Email Management**: Resend emails from admin panel
* **Customizable**: Fully customize email templates and form labels
* **Admin Dashboard**: View and manage all participants from order page
* **Mobile Responsive**: Works perfectly on all devices
* **Translation Ready**: Fully translatable (i18n ready)

= How It Works =

1. Enable participant booking for specific products
2. Customer selects quantity (e.g., 5 slots)
3. Form appears asking for each participant's details (name, email, phone)
4. Customer completes checkout
5. Each participant automatically receives confirmation email
6. Admin can view all participants and resend emails if needed

= Developer Friendly =

* Clean, well-documented code
* WordPress and WooCommerce coding standards
* Hooks and filters for customization
* Extensible architecture
* No jQuery dependency on frontend

== Installation ==

= Automatic Installation =

1. Log in to your WordPress dashboard
2. Navigate to Plugins > Add New
3. Search for "Gym Multi-Participant Booking"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Log in to WordPress dashboard
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the downloaded zip file and click "Install Now"
5. Activate the plugin

= Configuration =

1. Go to WooCommerce > Participant Booking
2. Configure your gym details and email settings
3. Edit any product and check "Enable Participant Booking"
4. Save the product
5. Test by adding product to cart with quantity > 1

== Frequently Asked Questions ==

= Does this work with all WooCommerce products? =

No, you must explicitly enable participant booking for each product. This gives you full control over which products require participant details.

= Can I customize the email template? =

Yes! The email template is located in `templates/email/participant-confirmation.php`. You can copy it to your theme folder at `your-theme/gym-participant-booking/email/participant-confirmation.php` to customize it without losing changes on plugin updates.

= Can I customize the participant form? =

Yes, through the settings page (WooCommerce > Participant Booking) you can customize field labels, form title, and description. For advanced customization, you can override the template files in your theme.

= What happens if email sending fails? =

The order will still be completed successfully. Failed emails are logged in order notes, and you can manually resend them from the order page in admin. The admin also receives a notification about failed emails.

= Can I require phone numbers? =

Yes, there's a setting to make the phone number field mandatory or optional. By default, phone number is required.

= Does this support variable products? =

Yes, participant booking works with both simple and variable products. Each variation can have participant booking enabled independently.

= Is it GDPR compliant? =

The plugin stores participant data as part of the order, which is standard WooCommerce behavior. You should add appropriate privacy policy notices on your site and obtain consent as required by GDPR. The plugin includes data retention settings to help with compliance.

= Can participants cancel their individual bookings? =

Currently, the plugin doesn't include individual participant cancellation functionality. Cancellations must be handled at the order level through WooCommerce's standard refund/cancellation process.

= Will this slow down my site? =

No, the plugin is lightweight and only loads resources on product pages where participant booking is enabled. Email sending happens asynchronously after checkout, so it doesn't affect page load times.

= Can I export participant lists? =

Yes, from the order edit page in admin, you can export all participants for that order to CSV format. This is useful for attendance tracking and record-keeping.

= Does it work with other booking plugins? =

The plugin is standalone and doesn't require other booking plugins. It may work alongside them, but compatibility isn't guaranteed. We recommend testing in a staging environment first.

= What email clients are supported? =

The email template is designed to work with all major email clients including Gmail, Outlook 2007-2021, Apple Mail, Yahoo Mail, and mobile email apps. It uses table-based layout with inline CSS for maximum compatibility.

= Can I send emails in different languages? =

Yes, the plugin is fully translation-ready. You can translate all strings using WordPress translation tools, and emails will be sent in the site's configured language.

= How many participants can I add per order? =

There's no hard limit, but it's determined by the product quantity. If a customer orders 100 slots, they'll need to provide details for 100 participants. For practical reasons, we recommend setting reasonable maximum quantities on your products.

= Can I bulk resend emails? =

Yes, from the order edit page, you can select multiple participants and resend their confirmation emails in bulk. The admin interface includes progress tracking for bulk operations.

== Screenshots ==

1. Product page with participant form (quantity = 3)
2. Admin settings page - General settings
3. Admin settings page - Email settings
4. Order page showing all participants with email status
5. Product edit page - Enable booking checkbox
6. Sample confirmation email received by participant
7. Resend email functionality in order admin
8. Mobile responsive participant form

== Changelog ==

= 1.0.0 - 2026-01-09 =

* Initial release
* Product-specific participant booking
* Dynamic participant forms based on quantity
* Automated email sending to each participant
* Admin management interface
* Customizable email templates
* Resend email functionality (single and bulk)
* CSV export of participant data
* Mobile responsive design
* Translation ready (i18n)
* GDPR-friendly data handling
* WordPress 6.7 compatibility
* WooCommerce 9.x compatibility

== Upgrade Notice ==

= 1.0.0 =
Initial release. Welcome to Gym Multi-Participant Booking! Please backup your site before installing any new plugin.

== Additional Info ==

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 5.0 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher

= Support =

For support inquiries, please:

* Visit our support forum at WordPress.org
* Check the documentation at [your-documentation-url]
* Submit issues on GitHub at [your-github-repo]
* Contact us at support@yoursite.com

= Documentation =

Full documentation is available at [your-documentation-url] including:

* Setup guide
* Template customization
* Hook reference
* API documentation
* Troubleshooting guide

= Feature Requests =

Have an idea to make this plugin better? Submit feature requests on our GitHub repository or through the WordPress.org support forum.

= Roadmap =

Upcoming features we're working on:

* **QR code generation** for gym check-in
* **SMS notifications** to participants
* **Calendar sync** (Google Calendar, Outlook, iCal)
* **Participant self-service portal** for managing bookings
* **Attendance tracking** and check-in system
* **Reminder emails** before scheduled sessions
* **Waitlist management** for sold-out sessions
* **Custom fields** for additional participant information
* **Analytics dashboard** for booking insights
* **Integration with popular booking plugins**

= Privacy Policy =

This plugin stores the following data:

* Participant names, email addresses, and phone numbers (stored with order data)
* Email send status and timestamps
* Plugin settings and configuration

All data is stored in your WordPress database and follows WooCommerce's data retention policies. When you uninstall the plugin, you can choose whether to delete participant data or retain it for compliance purposes.

= Contributing =

This plugin is open source! Contributions are welcome:

* Report bugs on GitHub
* Submit pull requests
* Translate into your language
* Suggest improvements

= Credits =

* **Developed by**: Md Arif Billah
* **Email Template Design**: Optimized for 40+ email clients
* **Icons**: WordPress Dashicons
* **Testing**: Compatible with WordPress 6.7 and WooCommerce 9.x

= License =

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA

== Third-Party Services ==

This plugin does not connect to any third-party services or external APIs. All functionality runs entirely on your WordPress/WooCommerce installation.

== Translations ==

* English (en_US) - Default
* Translations welcome! Visit [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/gym-multi-participant-booking)

Help us translate this plugin into your language!

== Developer Documentation ==

= Hooks & Filters =

**Actions:**

* `gmpb_before_participant_fields` - Before participant fields render
* `gmpb_after_participant_fields` - After participant fields render
* `gmpb_email_sent` - After email is sent to participant
* `gmpb_email_failed` - When email sending fails

**Filters:**

* `gmpb_participant_fields` - Modify participant field definitions
* `gmpb_email_template_data` - Modify email template variables
* `gmpb_form_validation_rules` - Customize validation rules
* `gmpb_email_subject` - Customize email subject line

= Template Override =

Copy template files from:
`/wp-content/plugins/gym-participant-booking/templates/`

To your theme:
`/wp-content/themes/your-theme/gym-participant-booking/`

Then customize as needed.

= Code Example =

`
// Modify email subject
add_filter( 'gmpb_email_subject', 'custom_email_subject', 10, 2 );
function custom_email_subject( $subject, $participant ) {
    return 'Welcome to Our Gym - ' . $participant['name'];
}

// Add custom participant field
add_filter( 'gmpb_participant_fields', 'add_custom_field' );
function add_custom_field( $fields ) {
    $fields['emergency_contact'] = array(
        'label' => 'Emergency Contact',
        'required' => false,
        'type' => 'text'
    );
    return $fields;
}
`

For more examples, visit our documentation.
