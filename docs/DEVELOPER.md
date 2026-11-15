# Swansea Harriers website: developer handoff

A WordPress theme and a companion plugin that reproduce the 2026 website design exactly, with fixtures, results, training groups, volunteers and records managed through simple admin forms. League results import automatically from the Gwent and West Glamorgan league websites.

| Folder | Goes in | What it does |
|---|---|---|
| `swansea-harriers/` | `wp-content/themes/` | Presentation only: one shared stylesheet, templates, homepage logic, small vanilla JS file. |
| `harriers-core/` | `wp-content/plugins/` | Content types, admin forms, results importer, starter content. Kept separate so content survives any future theme change. |

No other plugins, page builders or build steps are needed. Tested on WordPress 6.8 and PHP 8.3.

## Install

1. **Back up the current site** (files and database). Then try this on a staging copy first.
2. Copy both folders into place.
3. Activate the **Harriers Core** plugin, then the **Swansea Harriers** theme.
4. Go to **Settings → Permalinks** and click Save. This refreshes the `/fixtures/` address.
5. Optional, on a fresh or staging site: **Leagues & results → Import results → Load starter content**. It runs as six small steps on the same page (so it works on slow hosts and WordPress Playground); if a step fails, clicking again carries on. This creates all the club data from the design, plus the pages and menus. It is safe to re-run, because it skips anything that already exists, and it only trashes WordPress's untouched "Hello world" post and "Sample Page".
6. On the live site, instead of step 5, map the existing pages:
   - Results page → Page template **Results**
   - Training → **Training groups**
   - Volunteers → **Volunteers**
   - Records → **Club records**
   - **Settings → Reading:** set a static homepage and a "News" posts page.
   - **Appearance → Menus:** assign the six menu locations (see Menus below).
7. **Appearance → Customise → Homepage / Club details:** check the banner, spotlight, venue, club facts and contact details.

## Reliable automatic imports

The importer runs on WP-Cron every 6 hours. WP-Cron only fires when someone visits the site, so on a quiet site add a real server cron:

```
*/30 * * * * curl -s https://www.swanseaharriers.co.uk/wp-cron.php?doing_wp_cron > /dev/null
```

Then set `define( 'DISABLE_WP_CRON', true );` in `wp-config.php`.

## How the data is modelled

| Admin menu | Post type | Notes |
|---|---|---|
| Fixtures | `hs_fixture` (public, archive `/fixtures/`) | Meta: `hs_date` (Y-m-d), `hs_end_date`, `hs_time`, `hs_venue`, `hs_details`, `hs_discipline` (on/off), `hs_entry_url`, `hs_entry_platform`, `hs_info_url`. Taxonomy `hs_fixture_type`. |
| Leagues & results | `hs_league` | One per league per season. `hs_current` = show on Results page (untick → Archive tab). `hs_season_type` winter/summer. `hs_standings` = lines of `Label \| Value \| Detail`. `hs_source` manual/gwent/westglam, plus source settings. |
| Leagues & results → Rounds | `hs_round` | Child of its league (`post_parent`, kept in step with the `hs_league` meta). `hs_round_no`, `hs_date`, `hs_venue`, `hs_cancelled`, `hs_auto`, `hs_status` provisional/final, `hs_source_url` (West Glam venue page), `hs_rows` (lines of `Race \| Position \| Out of \| Score \| First Harrier`), `hs_last_import`. |
| Training groups | `hs_training` | Taxonomy `hs_training_tab` sets the tab; term meta `hs_order` orders tabs; `menu_order` orders groups. |
| Volunteers | `hs_volunteer` | Taxonomy `hs_volunteer_role` (Coaches, Team managers, Officials, Officers & trustees) and `hs_event_group` (coach filter). `hs_list` xc/tf splits team managers. Featured image = optional photo. |
| Club records | `hs_record` | Title = athlete. Meta `hs_event`, `hs_age_group`, `hs_mark`, `hs_year`. Taxonomy `hs_event_group`. |
| Posts | `post` | News. Categories Announcements, Results, Fixtures, Weekly updates drive the coloured tags and filter chips. A sticky post becomes the large feature on the News page. |

All fields are native meta boxes (`harriers-core/includes/fields.php`), not ACF. Everything is `show_in_rest`, so it is available over the REST API.

Pipe-separated text fields (results rows, standings) are deliberate. A non-technical editor can fix a typo in a results line without a complex UI, and the importer writes the same format.

## Homepage logic (`front-page.php`)

- **Next fixture strip:** `hs_get_next_fixture_group()` takes the soonest upcoming fixture. If that falls on a Saturday or Sunday, it returns every fixture that weekend; otherwise, every fixture on that day. One fixture shows as a headline; several show as a list.
- **Latest result:** `hs_get_latest_result()` is the most recent round with results. It names race winners (rows whose "First Harrier" ends `· 1st`), or team wins, or falls back to "Full results and tables".
- **Coming up:** the next 5 fixtures. **Latest news:** the latest 3 posts.
- **Tiles:** the *Homepage tiles* menu. Each item uses its title, its description (enable *Description* in Screen Options) and a CSS class `icon-<name>` (`trophy calendar news run join star chart people shield pin shirt flag`), plus `is-dark` for a dark icon box.
- **Banner, spotlight, venue, stats, join band:** Customiser (`inc/customizer.php`). Defaults point at the images bundled in `assets/images/`.

## Results importer (`harriers-core/includes/importer/`)

- `HS_Importer::due_rounds()` returns rounds dated in the last 21 days whose league is Gwent or West Glam, with auto import on, not cancelled and not final.
- Every 6 hours, `run_due()` imports each due round. Admins can also run one round, or all due rounds, from **Leagues & results → Import results**, which also shows a log.
- Results are never wiped. If the league site is down or our team isn't listed yet, the round is left alone.

### Gwent (`class-hs-gwent-parser.php`)

Files follow `{base}/{folder}/{race}{yy}{match}{t|i|d}.htm`. For example, `/2026/sm262t.htm` is senior men, 2026/27, match 2, team results.

- **Juniors (`u12g` … `u20b`)** are preformatted text. **After match 1 the team list is in overall league order**, with two numbers per line (match score, cumulative score). The match position is therefore computed by ranking match scores; reading the line order would give wrong placings.
- **Seniors and masters (`sl`, `sm`, `sl24` … `sm65`)** are HTML tables: `Pos | Team | Pts | Cum Pts | Cum Pos`. Senior men and women also read the `…d.htm` divisions page for their division position and cumulative standing.
- **"FINAL RESULTS"** on the senior pages marks the round Final. Gwent issues a first version on race weekend, accepts corrections until the Tuesday, then publishes final results. This happened for Match 2 on 12 Nov 2026, when the senior figures changed. The importer keeps re-checking until Final.
- **First Harrier** is the best-placed athlete whose club starts with the league's "Our club name" (default `Swansea Harriers`). Our A team is that name without a `"B"`/`'B'` suffix.
- **Season settings** live on the league: folder `2026`, code `26`. For 2027/28 these become `2027` / `27`.

### West Glamorgan (`class-hs-westglam-parser.php`)

- Each venue page (set per round in `hs_source_url`) has one table per race: `Position | Time | Bib | FirstName | Surname | Gender | Club | AgeCat | Points`.
- The team tables (`/team-tables-male/`, `/team-tables-female/`) have `Club | MP1 | MP2 … | LP1 | LP2 … | MatchPoint | LeaguePoints | Division`. The round position is `teams in division + 1 − LPn`. The standing ranks by league points, then match points.
- West Glam doesn't label results final, so a round is marked Final 7 days after the race.

### Tests

```
wp eval-file wp-content/plugins/harriers-core/tests/run-tests.php
```

There are 31 checks against saved copies of real league pages in `tests/fixtures/`. They cover the junior re-ranking, B-team exclusion, division lookup, the final flag, West Glam position maths, never wiping results on fetch failure, which rounds are due, and the weekend fixture grouping. If either league changes its page layout, save a new copy of the page into `tests/fixtures/` and update the parser until the tests pass.

The **summer leagues** (National Athletics League on OpenTrack, YDL as PDFs) are set to "Typed in by hand": their tables load via JavaScript or come as PDFs, so they aren't worth scraping. The standings panel and rounds are edited in the league and round forms.

## Theme structure

```
swansea-harriers/
  style.css                 theme header only
  assets/css/main.css       the one stylesheet (tokens at the top: colours, fonts, radii)
  assets/js/site.js         tabs, filters, round switcher, mobile menu, copy link (~150 lines, no deps)
  assets/images/            crest, hero, spotlight, venue defaults
  functions.php             setup, menus, enqueue, block styles, patterns
  inc/customizer.php        homepage + club detail settings
  inc/template-tags.php     shared render helpers (page hero, fixture row, news card)
  inc/icons.php             inline SVG icons
  header.php / footer.php
  front-page.php            homepage
  index.php                 News + category archives + search
  single.php                news post
  archive-hs_fixture.php    /fixtures/
  single-hs_fixture.php     one fixture
  page.php                  editable pages (Join, Club, Safeguarding, Contact, Support)
  page-templates/           results.php, training.php, volunteers.php, records.php
  patterns-src/             block patterns (CTA band, card grid, steps, FAQ, document list)
```

- **No inline CSS for styling**, apart from a few one-off layout values in templates. Colours, fonts and spacing come from `:root` tokens in `main.css`. To restyle, change the tokens.
- **Editable pages** use core blocks with these classes: `hs-lead`, `hs-cards` (`-2`, `-3`, `-4`), `hs-card` (`is-dark`, `is-bright`, `hs-price`), `hs-ticks`, `hs-steps`, `hs-faq` (Details block), `hs-cta`, `hs-callout`, `hs-stats`, `hs-timeline`, `hs-people-list`, `hs-docs`. They are styled in section 13 of `main.css` and also loaded in the editor. Button block styles are "Dark green" and "White".
- **Fonts:** Barlow Condensed (headings) and Inter (body) from Google Fonts. To self-host for GDPR, replace `hs_fonts_url()` and add `@font-face` rules.
- **Accessibility:** real buttons and links, ARIA tabs with arrow-key support, 44px touch targets, visible focus, skip link, reduced-motion respected, no horizontal scroll at 390px wide.

## Menus

| Location | Seeded menu |
|---|---|
| `primary` | Results, Fixtures, News, Training, Club, Volunteers, Contact (the "Join the club" button is built in) |
| `tools` | Power of 10 (give it class `is-highlight`), OpenTrack, Fabian4, ResultsBase, Race Timing Solutions |
| `explore` | the 12 homepage tiles |
| `footer-1`, `footer-2`, `footer-3` | footer columns. The column heading is the menu name with "Footer · " removed. |

## Still to do on the live site

- **Contact form:** the Contact page has a placeholder paragraph for the club's form plugin shortcode. Use a "What's it about?" dropdown that routes to the right volunteer.
- **Real document links:** complaints procedure, constitution and so on (currently `#`), on the Safeguarding page.
- **Photo permissions:** the Hannah Brier spotlight photo looks like a championship press photo. Confirm the club may use it, or swap it in Customise → Homepage.
- **Values the club still needs to supply:** faster endurance group times and meeting point, kit prices, parking, Hall of Fame photos.
- **Redirects** from old URLs if any page addresses change (e.g. `/annual-club-fixtures-off-track/` → `/fixtures/`).

## Security and accessibility

Scanned November 2026. Details of what was checked and changed:

**Code scan** (PHP_CodeSniffer + WordPress Coding Standards security rules): 0 errors, 0 warnings.
- All theme output is escaped where it is printed (`esc_html`, `esc_url`, `wp_kses` with `hs_allowed_html()`).
- Admin forms use nonces and capability checks; import needs `edit_others_posts` (Editors and Admins).
- Admin result messages are stored per user in a transient, not passed in the URL.
- The results importer only fetches from `gwent-league.org.uk` and `westglamleague.co.uk` (filter `hs_importer_allowed_hosts`) and uses `wp_safe_remote_get`.

**Site hardening** (`harriers-core/includes/security.php`, each item has an off-switch filter):
- REST API: only Fixtures (and Fixture types) are public; leagues, rounds, volunteers, records and training groups are not.
- `/wp-json/wp/v2/users`, `?author=N` and `/author/…` don't reveal login names to visitors.
- XML-RPC off (`hs_disable_xmlrpc` → false if Jetpack needs it).
- WordPress version tag removed; generic login error.
- Headers: `X-Content-Type-Options`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`.

**For the live host to do** (outside the theme/plugin): HTTPS with HSTS, a Content-Security-Policy, automatic core/plugin updates, two-factor login for admins (e.g. the Two Factor plugin), login rate limiting, and daily off-site backups.

**Accessibility** (axe-core 4.10, WCAG 2.2 AA + best practice, 14 pages desktop + mobile): no colour-contrast, label, alt-text, link-name or keyboard issues. Fixed: all header content now sits inside a `<header>` landmark, and heading levels no longer skip (training tabs, Join, Contact, Support). The only remaining notices come from WordPress's own admin bar, which visitors never see.
Tabs use `role="tablist"`/`tab`/`tabpanel` with arrow-key support; form fields in the editor have linked help text and marked required fields.
