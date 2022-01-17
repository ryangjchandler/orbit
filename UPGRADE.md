# Upgrade Guide

## Upgrade from `v0.x` to `v1.x`

The release of v1.x of Orbit contains a couple of breaking changes and incompatibilities with previous versions. Please see the information below:

### Git integration has been removed

The first-party Git integration has been removed. This functionality will return at some point in the future as an additional package for Orbit. If you were using the integration before, you will be able to implement it yourself in the meantime by looking at the source for v0.9.x of Orbit.

### `md_json` driver has been removed

The `md_json` (`MarkdownJson`) driver wasn't incredibly popular and in order to streamline this package, I've decided to remove it from Orbit. If you wish to continue using this driver in your application, you can look at [this file](https://github.com/ryangjchandler/orbit/blob/v0.9.1/src/Drivers/MarkdownJson.php) and create your own custom driver.
