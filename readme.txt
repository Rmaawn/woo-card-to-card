=== Card to Card Payment for WooCommerce ===
Contributors: Rmaawn
Donate link: https://example.com/donate
Tags: woocommerce, payment, card-to-card, iran, persian
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Card to Card Payment for WooCommerce

Professional card-to-card (person-to-person) bank transfer payment gateway for WooCommerce with receipt upload, anti-fraud detection, and multi-card management.

== Description ==

Card to Card Payment for WooCommerce is a professional and secure payment gateway designed specifically to handle direct bank transfers and card-to-card payments smoothly. This is especially useful for regions where direct card-to-card payments are the norm, fully supporting Farsi/Persian language and Iranian banking structures.

### Key Features
* **Multiple Card Management**: Add multiple bank accounts and cards for users to transfer money to.
* **Receipt Upload**: Customers can upload their payment receipts directly on the checkout or "Thank You" page.
* **Anti-Fraud System**: Smart fraud detection to prevent duplicate receipt uploads.
* **Custom Order Status**: Automatically puts orders "On Hold (Awaiting Receipt)" until the customer uploads the receipt.
* **Admin Dashboard**: Fast and easy admin panel to approve or reject receipts with just one click.
* **Responsive Design**: Works perfectly on mobile and desktop devices.
* **High-Performance Order Storage (HPOS)**: Fully compatible with WooCommerce HPOS.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/woo-card-to-card-pro` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to WooCommerce > Settings > Payments.
4. Enable the "Card to Card (Pro)" gateway and click "Manage" or "Set up".
5. Enter your bank card details, account numbers, and any instructional text.
6. Save changes and you are ready to receive payments!

== Frequently Asked Questions ==

= Does this plugin support WooCommerce High-Performance Order Storage (HPOS)? =
Yes! This plugin is fully compatible with WooCommerce HPOS and custom order tables.

= Can I add multiple bank cards? =
Absolutely. You can add as many bank cards and account details as you need from the payment gateway settings.

= Is it available in Persian/Farsi? =
Yes, the plugin is fully localized for Farsi and built with the Iranian market in mind, though it works in English perfectly as well.

= What happens if a customer uploads a fake receipt? =
Admins can review all uploaded receipts from the WooCommerce order dashboard. If a receipt is fake or invalid, you can easily reject it, which notifies the customer to upload a valid one.

= Does it prevent duplicate receipts? =
Yes, the plugin has an anti-fraud detection mechanism to prevent customers from uploading the same receipt image multiple times.

== Screenshots ==

1. **Checkout Page** - Shows the beautifully designed card details presented to the customer during checkout.
2. **Receipt Upload Form** - The customer interface for easily uploading payment receipts on the Order Received page.
3. **Admin Settings Panel** - WooCommerce settings page to manage multiple bank cards and configuration.
4. **Order Admin View** - The metabox inside the WooCommerce order screen where admins can approve or reject receipts.
5. **Custom Order Statuses** - Showing the "Awaiting Receipt" order status in the WooCommerce orders list.

== Changelog ==

= 1.0.0 =
* Initial public release.
* Added multi-card support.
* Added receipt upload functionality.
* Added smart anti-fraud detection.
* HPOS compatibility included.

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade necessary.
