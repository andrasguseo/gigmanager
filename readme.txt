=== GigManager ===
Contributors: aguseo
Donate link: https://paypal.me/guseo
Tags: gig management, concert listings, tour dates, music events, event calendar
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage and display live shows, tour dates, artists, and venues. A gig management plugin for musicians, bands, and performers.

== Description ==

**GigManager** is a WordPress plugin for managing and displaying live shows. Whether you are a musician, a band, a DJ, a comedian, a speaker... or any kind of performer, GigManager helps you manage and display your live shows using simple shortcodes.

The plugin uses native WordPress custom post types for Shows, Artists, Venues, and Tours, giving you a familiar editing experience with full support for revisions, trash, and bulk actions.

= Features =

* **Show management** — Create shows with date, time, artist, venue, tour, ticket URL, external link, notes, and status (sold out or cancelled).
* **Artists, Venues, and Tours** — Manage reusable artist, venue, and tour records linked to your shows.
* **Shortcode** — Display upcoming, past, or all shows anywhere on your site with the `[gigmanager_shows]` shortcode.
* **Three display templates** — Choose from list (card-style), table, or classic (two-row) layouts.
* **Sidebar widget** — Show a compact list of upcoming shows in any widget area.
* **Customize to your liking** — You can override any of the templates. Paste them into your theme's `gigmanager/` directory to customize the output.
* **Customizable labels** — Rename "Show", "Artist", "Tour", and more from the settings page with ease.
* **CSV import and export** — Bulk import artists, venues, tours, and shows via CSV. Export your data at any time.
* **Sticky defaults** — Optionally pre-fill artist, venue, and tour fields based on your last entry.
* **Onboarding** — A dashboard widget guides you through initial setup.

= Shortcode Usage =

`[gigmanager_shows]`

Parameters:

* `scope` — `upcoming` (default), `past`, `today`, or `all`
* `artist` — Filter by artist slug or ID
* `limit` — Maximum number of shows to display
* `sort` — `ASC` (default for upcoming) or `DESC`
* `template` — `list` (default), `table`, or `classic`

Examples:

Show the last 10 events with the table template:
`[gigmanager_shows scope="past" limit="10" template="table"]`

Show upcoming shows for the artist with the slug "the-foo-bars":
`[gigmanager_shows artist="the-foo-bars" scope="upcoming"]`


== Installation ==

1. Upload the plugin zip file via the *Plugins ‣ Add New* screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **GigManager → Settings** and select a page where your shows will be displayed.
4. Add the `[gigmanager_shows]` shortcode to that page.
5. Start adding artists, venues, and shows.

== Frequently Asked Questions ==

= Can I customize the front-end templates? =

Yes. Copy any template file from `plugins/gigmanager/templates/` into your theme at `your-theme/gigmanager/` and modify it as needed.

= What timezone does the plugin use? =

GigManager uses your site's timezone as configured in **Settings → General → Timezone**.

= Can I use this for multiple artists? =

Yes. You can create as many artists as you need and filter shows by artist using the shortcode's `artist` parameter.

= Can I move my data between sites? =

Yes. Use the built-in CSV export on one site and the CSV import on another to move your artists, venues, tours, and shows.

== Screenshots ==

1. Front-end display using the list template.
2. Front-end display using the table template.
3. Front-end display using the classic template.
4. Show list in the admin.
5. Adding a new show.
6. Settings page.
7. CSV import screen.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release of GigManager.
