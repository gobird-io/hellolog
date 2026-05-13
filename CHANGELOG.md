# Changelog

## [0.2.0] - 2026-05-13

### Added
- Dedicated `core-failed-login` sensor, off by default. Failed
  WordPress login attempts no longer travel with the everyday auth
  events — operators flip it on from **Settings → Filters** when
  they're investigating an incident.
- Per-sensor descriptions on the Filters tab so the on/off decision
  doesn't depend on guessing what the slug means.
- "Remove API key" disconnect button on the Connection tab, and a
  client-side format check before saving the key.
- Backend domain pinning: every request carries an `X-Site-Domain`
  header; tokens issued for one site won't authenticate from another.
- PHP-version guard in the plugin bootstrap. On PHP < 8.0 the plugin
  stops loading at the first `require` and shows a single admin
  notice instead of fataling the whole site.

### Changed
- Admin UI consolidated into a single Tools-level page (`Tools →
  helloLOG`) with a sticky top bar, sidebar sub-tabs and shadcn-vue
  components. The old Settings-menu entry is retired.
- "Token" relabelled to "API key" everywhere user-facing.
- README, plugin-header description and composer description no longer
  mention the backend stack — those internals stay in the private
  workspace docs.

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
