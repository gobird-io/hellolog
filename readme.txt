=== helloLOG ===
Contributors:      gobird
Tags:              activity log, audit log, security, monitoring, postgres
Requires at least: 6.4
Tested up to:      6.7
Requires PHP:      8.0
Stable tag:        0.1.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Activity audit log for WordPress that ships events to an external PostgreSQL + TimescaleDB backend.

== Description ==

Tracks user, content, plugin, theme, system, and integration activity on your
WordPress site and forwards events to an external API
(`https://api.gobird.io/v1/wordpress-activity-audit-log` by default). Long-term
storage, search, retention, and cross-site aggregation happen on the backend —
your WordPress database keeps only a small outgoing queue that drains
continuously.

= Why an external backend? =

Other activity log plugins write every event into a dedicated local table
(often using EAV-style metadata rows). On busy sites those tables grow into
millions of rows and start to dominate admin UI latency, backups, and replication.

helloLOG moves storage off the WordPress database entirely. The
backend runs on PostgreSQL + TimescaleDB hypertables with 7-day chunks and
compression — designed for write-heavy audit workloads.

= Logged activity =

* Authentication: login/logout, failed logins, password reset, 2FA, app passwords
* Users: create / delete / role change / profile updates
* Content: posts, pages, CPTs, taxonomies, comments, menus, widgets, custom fields
* Plugins & themes: install / activate / deactivate / update / delete / file changes
* Settings & system: site URL, permalinks, discussion, WordPress core updates
* Files: wp-config.php, .htaccess, robots.txt, plugin/theme file modifications
* Multisite: sites, super admin grants, network settings
* Integrations: WooCommerce, Gravity Forms, WPForms, Yoast SEO, RankMath, ACF,
  bbPress, LearnDash, MemberPress, Paid Memberships Pro, TablePress,
  Redirection, MainWP, and more

Sensors lazy-load — an integration's hooks only register when the integration
is actually active on the site.

== Installation ==

1. Upload the plugin to `wp-content/plugins/hellolog/`.
2. Activate it through the **Plugins** menu in WordPress.
3. Go to **Settings → Activity Log**, paste the API endpoint URL and the
   site token issued by your backend admin, and click **Test Connection**.
4. Optionally toggle sensors and IP anonymization in the **Filters** tab.

== Frequently Asked Questions ==

= Do my events stay on the same server as my WordPress site? =

No — by design. The plugin only buffers them locally for a few seconds before
pushing them to the configured backend. The backend is where retention,
search, and cross-site aggregation happen.

= What if the backend is unreachable? =

The local queue keeps events until delivery succeeds, with exponential
backoff. After repeated failures an entry moves to a dead-letter status,
visible in the **Diagnostics** tab.

= Where do I get a site token? =

Run `POST /admin/sites` on the backend with `mint_token: true` (admin
bearer required); the response includes the site token, shown once. Copy
it into **Settings → Activity Log → Connection**.

== Changelog ==

= 0.1.0 =
* New: Initial release — plugin scaffold, activation/deactivation lifecycle,
  outgoing queue table.
