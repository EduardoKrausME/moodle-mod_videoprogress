# Changelog

This changelog documents only the two most recent published GitHub releases. Each entry describes changes verified by comparing its tag with the immediately preceding published release. Changes made later on `master` are excluded.

## 1.4.1 - 2026-10-19

### Changed

- Updated the PHP 8.3 GitHub Actions CI matrix from Moodle 5.2 (`MOODLE_502_STABLE`) to Moodle 5.3 (`MOODLE_503_STABLE`), retaining the Moodle 4.5 test entry.
- Updated the PostgreSQL CI service from version 16 to 17 and the MariaDB CI service from version 10 to 11.4, including revised MariaDB environment and health-check settings.
- Hardened the release workflow against shell injection by passing the commit message through a `RELEASE_MESSAGE` environment variable, and added release concurrency controls using `github.ref`, `cancel-in-progress: false` and `queue: max`.
- Added `pix/icon.png` and reorganized the SVG logo asset as `pix/monologo.svg`.
- Expanded installation documentation to specify that `local_video_bridge` version `2026100505` or later is required, is not bundled with the activity, and must be installed with its dependencies before installing or upgrading Video Progress.
- Updated the release identifier from `1.4.0` to `1.4.1` and the build number from `2026100507` to `2026101900`; the existing Video Bridge dependency version was not changed.

No additional Video Progress activity logic changes were identified in this tag comparison.

[Compare 1.4.0...1.4.1](https://github.com/EduardoKrausME/moodle-mod_videoprogress/compare/1.4.0...1.4.1)

## 1.4.0 - 2026-10-05

### Changed

- Migrated video source ownership to the required `local_video_bridge` plugin, declaring a minimum dependency build of `2026100505`. Video Progress now uses `local_video_bridge\source\manager` for source discovery, configuration, file persistence and player settings instead of its former in-module source manager.
- Removed the bundled `videoprogresssource` subplugin type, its source plugin management interface, and the embedded upload, URL, YouTube, Vimeo and Nextcloud source implementations; those source providers are supplied by Video Bridge.
- Removed the in-module HTML5 and HLS AMD adapters and the previously bundled HLS.js and Vimeo Player runtime assets, updating `thirdpartylibs.xml` accordingly.
- Updated the activity form, player configuration, instance management, plugin-file handling, and associated source/plugin-info tests to use Video Bridge's shared infrastructure.
- Updated backup and restore mappings to include `local_video_bridge/video` files rather than the former activity-owned video area, while retaining a legacy `mod_videoprogress/video` restore mapping for older backups.
- Updated CI installation to check out `moodle-local_video_bridge` as an extra plugin and document the shared-source architecture in `README.md`.
- Updated the release identifier from `1.3.13` to `1.4.0` and the build number from `2026100301` to `2026100507`.

### Migration

- Added an upgrade step for versions older than build `2026100506` that copies stored files from `mod_videoprogress/video` into `local_video_bridge/video` where the destination file does not already exist, then deletes the corresponding legacy file records. The step records a Moodle upgrade savepoint.

**Installation requirement:** This release depends on installing the separate Video Bridge plugin. Upgrading the activity without the required dependency is not supported.

[Compare 1.3.13...1.4.0](https://github.com/EduardoKrausME/moodle-mod_videoprogress/compare/1.3.13...1.4.0)
