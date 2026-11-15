# Swansea Harriers website preview

A proposed new website for Swansea Harriers Athletic Club, built on WordPress.

## Try it

**[Open the live preview](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/cleverly87/swanseaharriers-preview-update/main/blueprint.json)**

The preview runs a complete WordPress site in your browser using [WordPress Playground](https://wordpress.org/playground/). Nothing is installed and nothing is saved: everyone who opens the link gets their own fresh copy, so feel free to click around and change things. It takes around 20–30 seconds to load the first time.

- **The public site** opens first: homepage, results, fixtures, news, training, volunteers, records and the club pages.
- **The admin side:** click **Swansea Harriers → Dashboard** in the black bar at the top (you're already logged in). Try **Fixtures → Add fixture**, **Posts → Add New Post**, or **Appearance → Customise → Homepage**, then view the site to see the change.

The automatic import of Gwent League and West Glamorgan results can't run inside the browser preview. On a real web host it fetches results after each fixture by itself. The results already loaded show how they will look.

## What's in this repository

| Folder | What it is |
|---|---|
| `swansea-harriers/` | The WordPress theme: the look of the site, one shared stylesheet |
| `harriers-core/` | The WordPress plugin: fixtures, league results (with automatic import), training groups, volunteers, records |
| `dist/` | Ready-to-upload zips of the theme and plugin |
| `docs/DEVELOPER.md` | Install guide, data model and importer details for a developer |
| `docs/EDITOR-GUIDE.pdf` | Plain-English guide for whoever updates the site |
| `blueprint.json` | The setup the preview link uses |

## Installing on a real WordPress site

1. **Plugins → Add New Plugin → Upload Plugin:** upload `dist/harriers-core-plugin.zip` and activate it.
2. **Appearance → Themes → Add New Theme → Upload Theme:** upload `dist/swansea-harriers-theme.zip` and activate it.
3. **Settings → Permalinks:** choose "Post name" and save.
4. **Leagues & results → Import results:** click **Load starter content** (on a fresh or staging site).

Full details are in [docs/DEVELOPER.md](docs/DEVELOPER.md).
