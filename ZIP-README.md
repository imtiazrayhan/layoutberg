# LayoutBerg Plugin Packaging

This document explains how to create a WordPress plugin zip file for distribution.

## Available Scripts

### Basic Zip Creation

```bash
npm run zip
```

Creates a zip file of the current plugin state. This will include all files except those specified in `.distignore`.

### Build and Package

```bash
npm run package
```

Builds the plugin assets first, then creates the zip file. This ensures all JavaScript and CSS files are compiled and optimized.

### Full Release Process

```bash
npm run release
```

Runs the complete release process:

1. Lints JavaScript and CSS files
2. Runs tests
3. Builds assets
4. Creates the zip file

## Files Included in Zip

The zip file includes all necessary files for the plugin to function:

### Core Plugin Files

-   `layoutberg.php` - Main plugin file
-   `readme.txt` - WordPress.org readme
-   `uninstall.php` - Cleanup script
-   `LICENSE` - License file

### PHP Classes

-   `includes/` - All PHP class files
-   `admin/` - Admin interface files
-   `public/` - Public-facing files

### Built Assets

-   `build/` - Compiled JavaScript and CSS files
-   `build/admin/` - Admin interface assets
-   `build/editor/` - Gutenberg editor assets
-   `build/blocks/` - Block assets

### Assets

-   `assets/` - Static assets (images, icons)
-   `languages/` - Translation files
-   `templates/` - Template files

## Files Excluded

The following files are excluded from the zip (see `.distignore`):

-   Development files (`node_modules/`, `.git/`, etc.)
-   Source files (`src/`)
-   Build artifacts (`.map` files, `.asset.php` files)
-   Documentation files (`README.md`, `CLAUDE.md`)
-   Test files (`tests/`, `phpunit.xml`)
-   Configuration files (`package.json`, `composer.json`, etc.)

## Output

The zip file is created as `layoutberg.zip` in the plugin root directory and is ready for:

-   WordPress.org plugin repository submission
-   Direct installation on WordPress sites
-   Distribution to customers

## Best Practices

1. **Always run `npm run package`** before creating a release to ensure all assets are built
2. **Test the zip file** by installing it on a fresh WordPress installation
3. **Verify file permissions** - all files should be readable
4. **Check file size** - the zip should be reasonable in size (typically under 1MB for most plugins)

## Troubleshooting

If the zip command fails:

1. Ensure all dependencies are installed: `npm install`
2. Check that the build process completes successfully: `npm run build`
3. Verify that `.distignore` is properly configured
4. Make sure you have write permissions in the plugin directory
