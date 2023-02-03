# Changelog

# 1.11.1
* Fix: Concurrent newsletter campaigns from different sites not sent on a multisite network.
* Allow setting custom dates for sending post digest newsletters.
* Fix: Monthly digest newsletter always reschedule to the following month when edited.

# 1.11.0
* Include percentages in Newsletter campaign stats.
* Edit email based automation rules using the newsletter editor.

# 1.10.3
* Add EDD newsletter automation rule triggers.
* Add support for EDD newsletter template.
* Add support for WS Form.

# 1.10.2
* Fix popup newsletter subscription forms not working.

# 1.10.1
* Ability to send an email to individual newsletter subscribers.

# 1.10.0
* Paid Memberships Pro - Membership level changed automation trigger.
* Paid Memberships Pro - Change membership level changed automation action.
* Escape formulas in CSV exports.

# 1.9.5
* Daily post digests now send content published in the previous day instead of the previous 24 hours.
* Add [[order.coupon_code]] WooCommerce newsletter merge tag.

# 1.9.4
* Fix: Noptin newsletter subscribers overview page blank when certain plugins are active. [https://github.com/lasssim](@lasssim)

# 1.9.3
* Fix: WooCommerce product purchase automation trigger reverts to intially selected values when saved.

# 1.9.2
* Add subscribe to newsletter automation rule action.
* Improve WooCommerce product purchased automation rule trigger.

# 1.9.1
* Added more WooCommerce conditional logic filters.
* Newsletter perfomance improvements.

# 1.9.0
* GeoDirectory newsletter integration.
* Add conditional logic to all automation rule triggers.

# 1.8.2
* Fixed: A CRON conflict causing post digests to create multiple newsletter campaigns.

# 1.8.1
* Fix unsaved custom newsletter fields disappearing

# 1.8.0
* Add language custom field.
* Add ability to send multi-lingual newsletters.
* Add newsletter-subscriber based conditional logic to automation rule triggers.

# 1.7.8
* Fix automation rules send empty email body.

# 1.7.7
* Ensure PHP < 7.3 compatibility

# 1.7.6
* Newsletter subscribers export file is empty.

# 1.7.5
* Subscription checkbox breaks the registration form.
* Add BuddyPress registration form compatibility.

# 1.7.4 =
* Add ability to set newsletter subscription checkboxes as checked by default
* Add ability to set a field as required
* Add support for WooCommerce checkout block
* Default to the classic editor for non-woocommerce sites

# 1.7.3
* Update plugin name

# 1.7.2
* Fix incorrect logo link in email newsletters

# 1.7.1
* Double opt-in confirmation links displays empty box on some sites - FIXED

# 1.7.0
* Ability to duplicate and preview automated email campaigns - ADDED
* Ability to send a test email for any automated email campaign - ADDED
* Filter newsletter recipients by custom fields.
* View unsubscribes for each email newsletter and automated email.
* Ability to order newsletter subscribers by custom field.
* Subscriber delete button not working - FIXED.
* Fix conflict with WPForms pro

# 1.6.6
* Action page links not working - FIXED

# 1.6.5
* Now uses normal WordPress new-post pages to for the newsletter form editor - FIXED
* Now adds and verifies a key before redirecting newsletter links - CHANGED

# 1.6.4
* All imported newsletter subscribers marked as unconfirmed - FIXED

# 1.6.3
* Update newsletter form editor JS - CHANGED

# 1.6.2
* Multi-lingual newsletter improvements - ADDED

# 1.6.1
* Double newsletter subscription fields on new installs - FIXED

# 1.6.0
* Add [noptin] newsletter form shortcode

# 1.5.6
* Import newsletter subscribers page is blank - FIXED

# 1.5.5 - 2021-08-09
* Better management of custom fields - ADDED

# 1.5.0 - 2021-05-16
* Ability to connect your site to Noptin.com for faster support and updates - ADDED
* Ability to set multiple admin notification recipients - ADDED
* Ability to view stats for sent new post notification newsletters - ADDED
* Ability to automatically delete sent campaigns after X days - ADDED

# 1.4.6 - 2021-04-02
* WPML Noptin settings translations not loaded - FIXED
* Confirmation URLs show 404 error when using Polylang - FIXED

# 1.4.5 - 2021-04-02
* Fix automatic "new post notifications" do not work if the new post is a duplicate of an earlier post.

# 1.4.4 - 2021-03-14
* Fix checkbox always shows no (Ville Viljanen).
* Fix featured image not showing in new post notification newsletter.
* Add a [[resubscribe_url]] tag usable in emails and unsubscribe message.
* Add the `[noptin-show-if-subscriber]` and `[noptin-show-if-non-subscriber]` shortcodes.
* Add the `[noptin-subscriber-count]` shortcode.

# 1.4.3 - 2021-02-17
* Fix undefined variable notice in new hook.

# 1.4.2 - 2021-02-15
* WooCommerce integration displays an error when the Woo Product Feed addon is displayed.

# 1.4.1 - 2021-02-13
- Allow your customers to subscribe to your newsletter when checking out via GetPaid.
- Add a GetPaid new invoice automation rule.
- Add a GetPaid lifetime value automation rule.
- Add a GetPaid item purchased automation rule.
- Fix Contact Form 7 submissions not added to the newsletter if the acceptance checkbox is not set.
- The ".noptin-mark-as-existing-subscriber" CSS class can now be used to close sliding newsletter sign-up forms.
- Ability to limit the maximum number of emails that can be sent per hour.

# 1.4.0 - 2020-12-22
- Add a conditional newsletter subscription checkbox to Contact Form 7.
- Add a conditional newsletter subscription checkbox to Ninja Forms.
- Add a conditional newsletter subscription checkbox to WPForms.

# 1.3.9 - 2020-12-07
- Fix WooCommerce checkout not adding new customers to the newsletter.
- Add a nofollow attribute to newsletter unsubscribe links (@shawnkhall).
- Add newsletter unsubscribe email headers (@shawnkhall).

# 1.3.8 - 2020-11-20
- Anyone with a "manage_noptin" capability can now manage newsletters and subscribers.

# 1.3.7 - 2020-11-15
- Enable featured images in new post notification newsletters.

# 1.3.6 - 2020-11-07
- Update newsletter packages.

# 1.3.5 - 2020-10-06
- Ability to use templates generated by newsletter template plugins instead of the default Noptin newsletter templates - ADDED
- Ability to order newsletter subscribers by their subscription status - ADDED
- Clicking on the back button when viewing a newsletter subscriber returns you to the first page instead of the page that you were initially viewing - FIXED
- Ability to set the number of newsletter subscribers visible per page - ADDED ([Oleg Dmitriev](https://www.independent.wine/))
- Ability to duplicate a newsletter email campaign - ADDED
- If an existing newsletter subscriber tries to sign up, they [get an error showing their subscriber id](https://wordpress.org/support/topic/strange-erorr-code-with-existing-email/) - FIXED

# 1.3.4 - 2020-09-16
- Newsletter settings page throws an error because of a call to an undefined function (thanks @mb299) - FIXED

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
