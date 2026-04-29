# Theme Switching by Leuchtfeuer Plugin

Allows MJML Theme switching without losing all content by introducing markers. This plugin enhances Mautic's email builder by providing advanced options for changing MJML themes while intelligently preserving your existing content.


## Description

The **Theme Switching by Leuchtfeuer Plugin** for Mautic provides a sophisticated way to change MJML email themes without the common issue of losing all previously crafted content. By utilizing special HTML comment markers (`<!-- LOCKED_START -->` and `<!-- LOCKED_END -->`), users can define sections within their MJML that should be treated specifically during a theme switch. The plugin offers "Smart Merge" and "Translation Mode" options, giving users fine-grained control over how content from the old theme is integrated into the new one.

This plugin is ideal for users who frequently update email designs, want to maintain brand consistency across different templates, or need to apply stylistic variations (e.g., for translations or A/B testing) to existing content without starting from scratch.

## Requirements / Version Support
- Mautic 5.1 
- PHP 8.3
- Core Patch: https://github.com/mautic/mautic/pull/15042
- For the Translation mode you'll need the Leuchtfeuer Translation Plugin: https://github.com/Leuchtfeuer/mautic-Translations-bundle


## Features

-   **Advanced MJML Theme Switching**: Go beyond Mautic's default theme change behavior.
-   **Content Preservation with Markers**: Uses `<!-- LOCKED_START -->` and `<!-- LOCKED_END -->` comments to identify and manage content sections during theme switches.
-   **Smart Merge Mode**: Intelligently combines old content with the new theme. It updates the `<mj-head>` and `<mj-body>` attributes from the new theme, replaces `LOCKED` sections with corresponding new theme `LOCKED` sections, preserves original unlocked content, and appends any new unlocked content from the chosen theme.
-   **Translation Mode**: Similar to Smart Merge, but focuses on applying structural and stylistic changes from the new theme's `LOCKED` sections without appending the new theme's unlocked content. Ideal for restyling or translating existing content blocks.
-   **Interactive Choice Modal**: Presents a clear, user-friendly dialog to choose the desired theme switching method (Smart Merge, Translation Mode, or Mautic Default).
-   **Handles Code Mode Emails**: Automatically falls back to Mautic's default theme switching behavior if an email is in "Code Mode" or not recognized as MJML.
-   **Seamless Integration**: Works within the Mautic email builder interface.
-   **Client-Side Override**: Dynamically modifies Mautic's theme selection JavaScript to inject its custom functionality.


## Installation Instructions

### Step 1: Download the Plugin
1. Download the plugin repository as a ZIP file or clone it from the repository.

### Step 2: Place the Plugin in the Correct Directory
1. Extract the plugin files and move the folder to the `plugins/` directory of your Mautic installation.
2. Rename the folder to `LeuchtfeuerThemeSwitchingBundle`.

### Step 3: Clear the Mautic Cache
Run the following command to clear the cache and ensure Mautic recognizes the new plugin:

```bash
sudo /usr/bin/php /path-to-mautic/bin/console cache:clear
```

### Step 4: Install the Plugin

1. Navigate to the **Plugins** page in the Mautic admin panel.
2. Click the "Install/Upgrade Plugins" button to register the new plugin.

Alternatively, you can install the plugin via command line:

```bash
sudo /usr/bin/php /path-to-mautic/bin/console mautic:plugins:install
```

---

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
        *   **"🔧 Smart Merge"**: Merges content intelligently. Uses the new theme's `<mj-head>` and `<mj-body>` attributes. `LOCKED` sections from the new theme replace corresponding `LOCKED` sections from the old. Original unlocked content is kept. New unlocked content from the selected theme is appended.
        *   **"🌐 Translation Mode"**: Similar to Smart Merge, but does *not* append new unlocked content from the target theme. This is useful for applying stylistic changes or translating content within an existing structure.
        *   **"🧼 Mautic Default"**: Bypasses the plugin's enhanced logic and uses Mautic's standard theme change, which usually replaces all existing content with the new theme's default content.
        *   **"❌ Cancel"**: Closes the modal, and no theme change occurs.

### Processing the Theme Switch (Smart Merge / Translation Mode)
1.  If the user selects "Smart Merge" or "Translation Mode":
    *   The browser is redirected to the email builder URL, but with special query parameters (e.g., `template=newThemeName`, `original=currentEmailId`, `usePluginMerge=true`, `translationMode=true/false`).
2.  When the email builder reloads:
    *   The `BuilderSubscriber` (an event listener part of this plugin) detects these special parameters.
    *   It then invokes the `ThemeSwitchingService`.
    *   The `ThemeSwitchingService` performs the core logic:
        *   It fetches the MJML content of the original email and the new theme.
        *   It merges these two MJML sources based on the chosen mode (Smart Merge or Translation) and the `LOCKED` markers.
        *   The resulting merged MJML is saved to the database for the current email.
        *   The email's assigned theme is updated to the new theme.
3.  The Mautic email builder finishes loading, now displaying the email with the new theme applied and content merged according to the selected strategy.

### Using Markers
-   To make content "editable" or "preserveable" across theme changes (unlocked content), simply place it outside any `LOCKED` blocks.
-   To define sections that are part of the theme's structure and should be replaced by the new theme's corresponding `LOCKED` sections (e.g., headers, footers, specific structural elements), enclose them within `<!-- LOCKED_START -->` and `<!-- LOCKED_END -->`.


mjml
<mjml>
  <mj-head>
    <!-- Head content from new theme will be used -->
  </mj-head>
  <mj-body> <!-- Body attributes from new theme will be used -->

    <!-- LOCKED_START -->
    <mj-section background-color="#efefef">
      <mj-column>
        <mj-text>This is a locked header section. It will be replaced by the new theme's first LOCKED block.</mj-text>
      </mj-column>
    </mj-section>
    <!-- LOCKED_END -->

    <mj-section>
      <mj-column>
        <mj-text>This is unlocked content. It will be preserved.</mj-text>
        <mj-image src="path/to/image.png" />
      </mj-column>
    </mj-section>

    <!-- LOCKED_START -->
    <mj-section background-color="#efefef">
      <mj-column>
        <mj-text>This is a locked footer section. It will be replaced by the new theme's second LOCKED block.</mj-text>
      </mj-column>
    </mj-section>
    <!-- LOCKED_END -->

  </mj-body>
</mjml>

<!-- User Note: Manually replace the '***' lines above and below with triple backticks (```) if your chat display supports standard Markdown code blocks. -->

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
    -   `ThemeSwitchingServiceTest.php`: PHPUnit tests for the `ThemeSwitchingService` to ensure merge logic works as expected.
-   **`LeuchtfeuerThemeSwitchingBundle.php`**: The main bundle class, extending `PluginBundleBase`.
-   **`composer.json`**: Project metadata, dependencies, and autoloading configuration.

---

## Authors

- **Iuri Jorbenadze** - [Email](mailto:jorbenadze2001@gmail.com)
-   **Leuchtfeuer Digital Marketing GmbH** - [Email](mailto:mautic-plugins@Leuchtfeuer.com)

For more information or support, visit [Leuchtfeuer Digital Marketing](https://leuchtfeuer.com).

---

## License

This plugin is licensed under the GPL v3 License.

---
