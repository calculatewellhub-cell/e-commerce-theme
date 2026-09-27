=== Aurelia Commerce ===
Contributors: aureliacommerce
Tags: woocommerce, whatsapp, upi, ai, wishlist
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.1
Requires Plugins: woocommerce
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WhatsApp ordering, UPI QR payments, an AI shopping concierge, 3D viewer, video studio, social auto-posting, analytics and SEO for WooCommerce.

== Description ==

Aurelia Commerce adds the store features that the Aurelia block theme is designed around. It works with any block theme and with the WooCommerce Cart and Checkout blocks, and it is compatible with High-Performance Order Storage (HPOS).

* **WhatsApp ordering**: "Order on WhatsApp" on product pages, cart and side cart, with prices checked on the server. Optionally creates a WooCommerce order ("Pending – WhatsApp") and adds a pay link to the message. Also adds enquiry, video-call and floating chat buttons.
* **UPI payments**: a payment method for the Checkout block that shows an amount-specific UPI QR code and a "Pay with UPI app" button (PhonePe, Google Pay, Paytm, BHIM), then collects the UTR reference. Works alongside Cash on Delivery and any gateway plugin (Razorpay, PhonePe PG, Cashfree…).
* **AI shopping concierge**: answers from your live catalogue using the Claude API, called from your server. The key never reaches the browser, requests are rate-limited per visitor, and a rule-based assistant takes over when no key is set.
* **3D viewer**: a "View in 3D" button with a built-in ring model or your own GLB/GLTF file, and an animated 3D hero. three.js loads only when needed.
* **Image → video studio**: turn product photos into Reels, Shorts and feed videos in the browser and save them to the Media Library.
* **Social auto-posting**: schedule posts to Facebook and Instagram (Meta Graph API) or any network through a Make, Zapier or n8n webhook. Each platform gets its own tracked short link with UTM tags.
* **Analytics**: a cookie-less, anonymous dashboard of views, add-to-carts, WhatsApp orders, concierge chats and social clicks, stored in your own database with automatic retention.
* **SEO and AI search (AIO)**: Product, Store, WebSite, Breadcrumb and FAQ structured data. It extends Yoast, Rank Math or AIOSEO when one of them is active rather than duplicating them. It also publishes `/llms.txt` and `/llms-full.txt`, sets AI crawler rules in robots.txt, and serves a product feed at `/product-feed.xml`.
* **Shop features**:
  * Live search, quick view, wishlist, compare and colour/image/button swatches.
  * Grid/list toggle and load more or infinite scroll.
  * Sticky add-to-cart bar, delivery estimate, size guide and review photos.
  * Recently viewed products, "Frequently bought together" and a free-shipping progress bar.
  * Sale countdowns, a newsletter popup (off by default) and a newsletter subscriber list.
* **Setup wizard and one-click demo import**, which can be removed again in one click.

== Installation ==

1. Plugins → Add New → Upload Plugin, choose `aurelia-commerce.zip`, then Install and Activate. WooCommerce must be active.
2. The setup wizard opens automatically. You can reopen it at any time from Aurelia → Setup wizard.
3. Adjust everything later in Aurelia → Settings.

For reliable social scheduling, set up a real server cron job. See the README or Aurelia → Settings → Social posting.

== External services ==

This plugin sends data to third-party services only when you enable the related feature.

* **Anthropic Claude API** (`api.anthropic.com`): when the AI concierge is enabled and an API key is saved, each visitor message and a summary of your public catalogue are sent to generate a reply. See the [terms](https://www.anthropic.com/legal/commercial-terms) and [privacy policy](https://www.anthropic.com/legal/privacy).
* **Meta Graph API** (`graph.facebook.com`): when you connect Facebook or Instagram, scheduled posts (caption, link and media URL) are sent to publish them. See the [terms](https://www.facebook.com/legal/terms) and [privacy policy](https://www.facebook.com/privacy/policy/).
* **Your webhook URLs** (Make, Zapier, n8n, Mailchimp…): the post or sign-up data is sent to the URL you enter.
* **WhatsApp** (`wa.me`): order and enquiry buttons open WhatsApp with a pre-filled message. Nothing is sent until the shopper presses send.

== Frequently Asked Questions ==

= Does it work without the Aurelia theme? =

Yes. The features use their own styles and follow your theme's colours where possible.

= Does it store personal data? =

Analytics store no IP addresses, cookies or personal identifiers. Newsletter emails are stored in your database and included in WordPress's personal data export and erase tools.

== Changelog ==

= 1.0.0 =
* First release.
