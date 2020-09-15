# Changelog

# 1.3.3 - 2020-09-15
- WooCommerce lifetime value automation trigger - ADDED
- Integration with Contact Forms 7 - ADDED
- Integration with Gravity Forms - ADDED
- WP RSS Aggregator appends the RSS title to new post notification excerpts - FIXED
- Ability to edit the double opt-in confirmation email - ADDED
- HTML entities in email subjects are not encoded properly - FIXED
- Do not sync pending (e.g if BuddyPress is installed) or spam (for multisite) users - FIXED
- Nonce verification can now be toggled on via a hook or constant - ADDED
- Ability to stop a sending campaign - ADDED
- New large fields email subscription template - ADDED
- New post notifications test email not working - FIXED
- Users and subscribers are now synced separately - CHANGED
- Ability to limit subscription forms to logged-in users, logged out users or specific user roles - ADDED
- Subscription form builder now supports a dark mode - ADDED
- Ability to change the sizes and styles of subscription form headings and texts - ADDED
[View full release notes](https://noptin.com/blog/noptin-1-3-3-release-notes/)

# 1.3.2 - 2020-06-23
- Ability to delete a subscriber after they unsubscribe - ADDED
- Subscribers marked as in-active when they resubscribe - FIXED
- Subscription confirmation not working in Merriweather template - FIXED
- "CLoseBtnPos not defined" error when editing popups - FIXED
- Newsletters and Forms not deleted when the plugin is uninstalled - FIXED
- Integration with Elementor forms - ADDED

# 1.3.1 - 2020-06-16
- General improvements
- "Plain" newsletter template - ADDED
- Unsubscription automation rule trigger and action - ADDED

## 1.3.0 - 2020-05-25
- Ability to filter the newletter double opt-in confirmation email - ADDED
- Ability to filter the function used to send newsletters - ADDED
- Improve the newsletter subscription error messages - CHANGED
- Email template plugins cause broken newsletter emails - FIXED
- Sometimes background emails do not send - FIXED
- Newletter page not working for URLs that use 'index.php' in their permalinks - FIXED
- Ability to search newsletter subscribers - ADDED
- Ability to bulk activate subscribers - ADDED
- Ability to bulk de-activate subscribers - ADDED

## 1.2.9 - 2020-05-05
- Newsletter actions page nolonger uses a normal WordPress page - CHANGED
- Ability to edit the newsletter unsubscription confirmation message - ADDED
- Ability to edit the newsletter subscription confirmation message - ADDED
- Danish translation thanks to [Hans Miguel Børsting](http://skibhuscentret.dk/wp)
- Partially translated to German thanks to [Frank Jermann](https://frank-jermann.de)

# 1.2.8 - 2020-04-26
- Newsletter subscribers page not showing a title - FIXED
- "Minimal" newsletter optin template - ADDED
- "Content Upgrades" newsletter optin template - ADDED
- "Discount" newsletter optin template - ADDED
- Hide block and quick-form widget from existing newsletter subscribers - CHANGED
- Option to set the logo shown on newsletters - RE-ADDED
- Ability to set a custom newsletter footer text - ADDED
- Ability to set a custom newsletter permission text - ADDED
- Ability to set a newsletter's "reply-to" email - ADDED
- Newsletter confirmation page displays the incorrect feedback - FIXED
- Automatic new post notification newsletters are sent when an existing post is updated - FIXED

## 1.2.7 - 2020-04-08
- Admininstrators can now edit email subscribers - ADDED
- Ability to resend the subscription confirmation double opt-in email - ADDED
- GeoLocation not showing the correct subscriber location - FIXED
- Improved subscriber interface - CHANGED
- Noptin_Subscriber and Noptin_Subscriber_Query classes for dealing with subscribers - ADDED
- Ability to set custom confirmation pages for double opt-in and newsletter unscrubscription - ADDED

## 1.2.6 - 2020-03-28
- Ability to import and export newsletter subscription forms - ADDED
- Ability to set an alternative subscriber's cookie - ADDED
- Manually add new newsletter subscribers in your WordPress admin dashboard - ADDED
- Ninja Forms integration (Use Ninja Forms to collect newsletter subscribers) - ADDED
- WPForms integration (Use your WPForms forms to collect newsletter subscribers) - ADDED
- Collect subscribers from your Ultimate Member registration forms - ADDED
- Collect subscribers from your UsersWP registration forms - ADDED
- Collect subscribers from your WooCommerce registration forms and checkout pages - ADDED
- Collect subscribers from your Easy Digital Downloads (EDD) checkout pages - ADDED
- Auto-update your subscriber's name whenever they update their WordPress user profile - ADDED
- WordPress 5.4 Compatibility - ADDED
- Add a `noptin-mark-as-existing-subscriber` class to any link/button and it will set the subscription cookie when someone clicks on it - ADDED
- Update babel, vue-loader, npm, webpack, autoprefixer, and grunt - ADDED

## 1.2.5 - 2020-03-16
- Update npm, webpack and babel
- Newsletter Opt-in forms now support adding hidden fields - ADDED
- Newsletter Opt-in forms now support adding checkbox fields - ADDED
- Save a subscriber's conversion page - ADDED
- Support for subscriber double opt-in - ADDED
- Ability to hide opt-in fields - ADDED
- Ability to create sliding newsletter subscription forms - ADDED
- Ability to add shortcodes in opt-in form titles, descriptions and notes - ADDED
- Professional opt-in template - ADDED
- Multi-select input fields cause pages to hang - FIXED
- Select boxes appear longer than other fields - FIXED

## 1.2.4 - 2020-03-03
- Update NPM dependancies.
- Form display rules now support absolute URL's in addition to post ids - ADDED
- Tool pages now display the name of the tool - CHANGED
- Export specific email subscriber fields - ADDED
- Export email subscribers as JSON, XML or CSV - ADDED
- Subscriber import now supports importing thousands of subscribers without crashing - FIXED
- Button to delete all email subscribers - ADDED
- Display popup opt-in forms once per week instead of once per session - CHANGED
- GDPR consent checkbox - ADDED

## 1.2.3 - 2020-02-10
- Debug log viewer - ADDED
- System status viewer - ADDED
- Ability to sync your WordPress users with your newsletter subscribers - ADDED
- Tooltips not showing - FIXED
- Subscriber GeoLocation - ADDED
- Google Analytics integration - ADDED

## 1.2.2 - 2020-01-20
- Ability to preview newsletter campaigns - ADDED
- Ability to import newsletter subscribers - ADDED
- Template loader - ADDED

## 1.2.1 - 2020-01-06
- Do not inject shortcode subscription forms on post previews - CHANGED
- check if class DOMDocument exists before emogrifying newsletter emails - ADDED
- New filter to change email templates - ADDED
