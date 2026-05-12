# Changelog

## [0.1.0] - 2026-05-11

### Added
- Plugin scaffold: header, Composer + PSR-4 autoloading under
  `HelloLog\`, activation / deactivation lifecycle, queue
  table (`{$wpdb->prefix}hellolog_queue`), clean uninstall.
- Event catalog covering 100+ codes across WP core (auth, users, content,
  comments, taxonomies, plugins, themes, settings, system, files,
  database, menus, widgets, multisite, request, 2FA, app passwords) and
  integrations (WooCommerce, Gravity Forms, WPForms, CF7, Fluent Forms,
  Yoast SEO, RankMath, ACF, bbPress, LearnDash, MemberPress, PMP, EDD,
  TablePress, Redirection, MainWP, Termly, plus the LW family).
- Sensors register lazily — integration sensors only attach their hooks
  when the target plugin is active.
- Outgoing queue (`QueueRepository` + `QueueEventDispatcher`).
- Transport (`ApiClient` with gzip + Bearer + retry policy,
  `PayloadBuilder`, `QueueFlusher`) and Action Scheduler bridge that
  flushes every 30 s.
- Tabbed Settings page: **Connection** (endpoint + token + Test
  Connection), **Filters** (sensor on/off + IP anonymization),
  **Diagnostics** (queue counts).
- **Tools → Activity Log** admin page with filter bar, AJAX paging, and
  read API call to the backend.
- PHPUnit, PHPCS (WordPress + PHPCompatibility), and GitHub Actions
  workflows for CI + release.
