# Theme Switching by Leuchtfeuer

## Overview

Allows MJML Theme switching without losing all content by introducing markers. This plugin enhances Mautic's email builder by providing advanced options for changing MJML themes while intelligently preserving your existing content.

## Description

The **Theme Switching by Leuchtfeuer** plugin for Mautic provides a sophisticated way to change MJML email themes without the common issue of losing all previously crafted content. By utilizing special HTML comment markers (`<!-- LOCKED_START -->` and `<!-- LOCKED_END -->`), users can define sections within their MJML that should be treated specifically during a theme switch. The plugin offers "Smart Merge" and "Translation Mode" options, giving users fine-grained control over how content from the old theme is integrated into the new one.

This plugin is ideal for users who frequently update email designs, want to maintain brand consistency across different templates, or need to apply stylistic variations (e.g., for translations or A/B testing) to existing content without starting from scratch.

## Requirements / Version Support

> [!TIP]
> Other releases of this plugin may cover different Mautic versions!
- Mautic 7.x
- PHP 8.2+
- GrapesJS Builder plugin (MJML emails)
- For the Translation mode you'll need the Leuchtfeuer Translation Plugin: https://github.com/Leuchtfeuer/mautic-Translations-bundle

Smart Merge runs entirely inside this plugin (no Mautic core patch required).


## Features

-   **Advanced MJML Theme Switching**: Go beyond Mautic's default theme change behavior.
-   **Content Preservation with Markers**: Uses `<!-- LOCKED_START -->` and `<!-- LOCKED_END -->` comments to identify and manage content sections during theme switches.
-   **Smart Merge Mode**: Intelligently combines old content with the new theme. It updates the `<mj-head>` and `<mj-body>` attributes from the new theme, replaces `LOCKED` sections with corresponding new theme `LOCKED` sections, preserves original unlocked content, and appends any new unlocked content from the chosen theme.
-   **Translation Mode**: Similar to Smart Merge, but focuses on applying structural and stylistic changes from the new theme's `LOCKED` sections without appending the new theme's unlocked content. Ideal for restyling or translating existing content blocks.
-   **Interactive Choice Modal**: Presents a clear, user-friendly dialog to choose the desired theme switching method (Smart Merge, Translation Mode, or Mautic Default).
-   **Handles Code Mode Emails**: Automatically falls back to Mautic's default theme switching behavior if an email is in "Code Mode" or not recognized as MJML.
-   **Seamless Integration**: Works within the Mautic email builder interface.
-   **Client-Side Override**: Dynamically modifies Mautic's theme selection JavaScript to inject its custom functionality.


## Installation

### Composer
This plugin can be installed through composer.

### Manual Installation
Alternatively, it can be installed manually:
- Download the plugin
- Unzip to the Mautic `plugins` directory
- Rename folder to `LeuchtfeuerThemeSwitchingBundle`
- In the Mautic backend, go to the `Plugins` page as an administrator
- Click on the `Install/Upgrade Plugins` button to install the Plugin.

OR

- If you have shell access, execute `php bin/console cache:clear` and `php bin/console mautic:plugins:reload` to install the plugins.


## Configuration

No additional configuration is required after enabling the plugin. The theme switching functionality is automatically available in the MJML email builder once the plugin is installed and enabled.


## User Flow Scenario

### Starting Point: Email Builder
1.  A user is working on an email within the Mautic MJML email builder.
2.  The email content may contain sections marked with `<!-- LOCKED_START -->` and `<!-- LOCKED_END -->` to delineate content blocks.

### Initiating a Theme Change
1.  The user decides to change the theme and clicks on the "Select a new theme" link or directly on a theme thumbnail in the theme selection area.

### Plugin Intervention
1.  The plugin's JavaScript (`theme-switch.js`) intercepts Mautic's default theme selection process.
2.  It first makes a quick background check to see if the current email is using Mautic's "Code Mode".
    *   **If in Code Mode**: The plugin steps aside, and Mautic's standard theme change process proceeds (typically replacing all content).
    *   **If not in Code Mode (MJML Builder)**: A modal dialog appears, presenting the user with three options:
        *   **"Smart Merge"**: Merges content intelligently. Uses the new theme's `<mj-head>` and `<mj-body>` attributes. `LOCKED` sections from the new theme replace corresponding `LOCKED` sections from the old. Original unlocked content is kept. New unlocked content from the selected theme is appended.
        *   **"Translation Mode"**: Similar to Smart Merge, but does *not* append new unlocked content from the target theme. This is useful for applying stylistic changes or translating content within an existing structure.
        *   **"Mautic Default"**: Bypasses the plugin's enhanced logic and uses Mautic's standard theme change, which usually replaces all existing content with the new theme's default content.
        *   **"Cancel"**: Closes the modal, and no theme change occurs.

### Processing the Theme Switch (Smart Merge / Translation Mode)
1.  If the user selects "Smart Merge" or "Translation Mode":
    *   The browser is redirected to the plugin merge endpoint (`/s/plugin/theme-switch/merge/{emailId}`) with the selected theme and mode.
2.  The plugin controller invokes `ThemeSwitchingService`, merges MJML into `bundle_grapesjsbuilder.custom_mjml`, updates the email theme, and redirects back to the email editor.

### Using Markers
-   To make content "editable" or "preserveable" across theme changes (unlocked content), simply place it outside any `LOCKED` blocks.
-   To define sections that are part of the theme's structure and should be replaced by the new theme's corresponding `LOCKED` sections (e.g., headers, footers, specific structural elements), enclose them within `<!-- LOCKED_START -->` and `<!-- LOCKED_END -->`.


---

## Development Details

### Directory Structure

-   **`Assets/`**: Contains static assets.
    -   `img/LeuchtfeuerThemeSwitchingBundle.png`: Plugin icon.
    -   `js/theme-switch.js`: Core client-side JavaScript. Overrides Mautic's default theme selection behavior (`Mautic.initSelectTheme`) to display the custom modal and prepares parameters for the backend merge process.
-   **`Config/`**: Plugin configuration files.
    -   `config.php`: Main plugin definition, including name, description, author, version, routes, and icon.
-   **`Controller/`**: Handles HTTP requests.
    -   `ThemeSwitchingController.php`: Contains actions like `checkEmailTypeAction` (used by `theme-switch.js` to determine if an email is in code mode).
-   **`EventListener/`**: Contains event subscribers.
    -   `BuilderSubscriber.php`: Subscribes to Mautic's `CoreEvents::BUILDER_ON_LOAD`. When the builder loads with specific URL parameters (set by `theme-switch.js` after user interaction), this subscriber triggers the `ThemeSwitchingService` to perform the actual content merge.
-   **`Integration/`**: Defines how the plugin integrates with Mautic.
    -   `ThemeSwitchingIntegration.php`: Registers the plugin with Mautic, provides display name, icon, and settings form modifications (e.g., help text).
-   **`Resources/`**:
    -   `config/services.yaml`: Defines Symfony service configurations for the plugin's classes (e.g., controllers, services, subscribers).
-   **`Service/`**: Contains business logic.
    -   `ThemeSwitchingService.php`: The heart of the plugin. Contains the logic for parsing old and new MJML, identifying `LOCKED` and unlocked sections, and merging them based on the selected mode (Smart Merge or Translation Mode).
-   **`Tests/`**: Contains unit tests.
    -   `Unit/Service/ThemeSwitchingServiceTest.php`: PHPUnit tests for the `ThemeSwitchingService` to ensure merge logic works as expected.
-   **`LeuchtfeuerThemeSwitchingBundle.php`**: The main bundle class, extending `PluginBundleBase`.
-   **`composer.json`**: Project metadata, dependencies, and autoloading configuration.

---

## Troubleshooting

Make sure you have not only installed but also enabled the Plugin.
If things are still funny, please try:
```
php bin/console cache:clear
```

## Change log
- https://github.com/Leuchtfeuer/mautic-ThemeSwitching-bundle/releases

## Sponsoring & Commercial Support
We are continuously improving our plugins. If you are requiring priority support or custom features,
please contact us at mautic-plugins@leuchtfeuer.com.

## Get Involved
Feel free to open issues or submit pull requests on [GitHub](https://github.com/Leuchtfeuer/mautic-ThemeSwitching-bundle).
Follow the contribution guidelines in `CONTRIBUTING.md`.

## Credits
@iuri-jorbenadze
@leuchtfeuer

## Author
Leuchtfeuer Digital Marketing GmbH

Please raise any issues in GitHub.
For all other things, please email mautic-plugins@Leuchtfeuer.com

## License

This plugin is licensed under the GPL v3 License.
