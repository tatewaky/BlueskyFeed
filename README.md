# Bluesky Plugin for WordPress

This WordPress plugin allows users to configure and display a feed from the Bluesky platform using an administrative settings page and a shortcode.

## Features
- **Admin Settings Page**: Configure your Bluesky account credentials (username and password) via a settings page in the WordPress admin dashboard.
- **Shortcode**: Use the `[bluesky_feed]` shortcode to display a feed of posts from your Bluesky account.
- **Cache Management**: Efficient caching of feed data with an option to clear the cache manually.

## Installation
1. **Upload the Plugin**:
  - Copy the PHP file to your WordPress plugin directory (`wp-content/plugins/`).
  - Ensure the file is named appropriately (e.g., `bluesky-plugin.php`).

2. **Activate the Plugin**:
  - Navigate to the WordPress admin dashboard.
  - Go to `Plugins > Installed Plugins` and activate the Bluesky plugin.

## Configuration
1. **Navigate to the Settings Page**:
  - In the WordPress admin dashboard, go to `Bluesky Config` from the sidebar menu.

2. **Enter Credentials**:
  - Provide your Bluesky account username and password in the respective fields.
  - Save the credentials by clicking the "Save Account" button.

3. **Clear Cache (Optional)**:
  - Use the "Force Delete Cache" button to manually clear cached feed data if necessary.

## Usage
### Shortcode
The plugin provides a shortcode to display the feed on any page or post:

```html
[bluesky_feed count="5"]
```

- `count` (optional): Number of posts to display. Defaults to `1`.

### Example
```html
[bluesky_feed count="3"]
```
This will display the latest 3 posts from your Bluesky account.

## Technical Details
- **Caching**:
  - Cached feed data is stored as a transient and expires daily.
  - Cache can be cleared manually from the settings page.

- **API Integration**:
  - The plugin interacts with the Bluesky API for authentication and fetching the feed.
  - API endpoints used:
    - Authentication: `/com.atproto.server.createSession`
    - Fetch Feed: `/app.bsky.feed.getAuthorFeed`

## Error Handling
If the plugin encounters an error (e.g., authentication failure or API issues), it will display an appropriate error message in place of the feed.

## Customization
Developers can extend or modify the plugin by:
- **Adding Hooks**: Modify the plugin behavior by adding custom hooks.
- **Editing Code**: Update the `displayFeed` method to customize the appearance of the feed.

## Support
For issues or feature requests, feel free to contact the plugin developer or open a support ticket on the WordPress plugin repository (if applicable).

---

This plugin is provided as-is without warranty. Always back up your WordPress site before installing new plugins.

