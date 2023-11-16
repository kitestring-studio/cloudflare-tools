# Cloudflare Tools

![Plugin Version](https://img.shields.io/badge/version-1.0.0-green)
![License](https://img.shields.io/badge/license-GPL--2.0+-blue)

Cloudflare Tools is an extension to the official Cloudflare plugin, allowing users to mark specific pages and posts to be always purged from the cache when the plugin initiates a selective cache purge.

## Features

- **Always Purge Pages and Posts**: Mark specific pages and posts to be always purged from Cloudflare's cache.
- Purge pages and posts from the edit table screen.

## Changelog
### 1.0.1
- Add "Purge" button for pages & posts on the edit table screen
### 1.0.0
- Initial release.

## TODO
- **Additional URLs**: Add custom URLs that will be purged from cache, for example, URLs that are not pages or posts.

## Requirements

- PHP 7.4 or higher
- Official Cloudflare plugin for WordPress

## Installation

1. Download the plugin zip file.
2. Go to **Plugins > Add New** in your WordPress admin.
3. Click **Upload Plugin** and select the zip file.
4. Click **Install Now** and activate the plugin.

## Usage

### Page and Post Meta Box

In the edit screen of a page or post, find the **Always Purge This Page** meta box to enable or disable always purge for that specific page or post.

### Settings Page

Navigate to **Settings > Cloudflare Tools** in your WordPress admin to access the plugin settings. Here, you can:

- View the list of pages and posts marked "always purge".
- Delete the setting without opening the pages.


## Support

For support, feature requests, or bug reports, please visit [GitHub](https://github.com/kitestring-studio/cloudflare-tools).

## License

This plugin is licensed under the GNU General Public License v2 or later. See the [LICENSE](LICENSE) file for more details.
