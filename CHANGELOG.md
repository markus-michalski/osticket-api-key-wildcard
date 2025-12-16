# Changelog

All notable changes to this plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Nothing yet

### Changed
- Nothing yet

### Fixed
- Nothing yet

## [2.0.0] - 2025-12-16

### Changed
- added zip-files to gitignore, deleted zip files
- refactor!: modular architecture with security fixes
- Auto-sync from Markus-PC
- Auto-sync from RTY-9618531788

## [1.1.0] - 2025-12-16

### Security
- Fixed XSS vulnerability in wildcard access logging (sanitize remote IP with Format::sanitize + Format::htmlchars)

### Added
- New PluginInstaller class for file operations (SRP)
- New HtaccessManager class for .htaccess modifications (SRP)
- New PluginConstants class to eliminate magic strings

### Changed
- Refactored main plugin class into modular components
- Eliminated ~100 lines of code duplication between enable() and performUpdate()
- Use __DIR__ constant instead of hardcoded plugin paths
- Config option log_wildcard_access is now properly respected
- Version bump to 1.1.0

### Fixed
- disable() now properly removes ALL .htaccess modifications (including MultiViews directive)
- Error handling improved (no more @ error suppression operator)

## [1.0.2] - 2025-11-08

### Changed
- added CC project instructions

### Fixed
- replace VisibilityConstraint with disabled field for installed_version
- Remove README-de.md check from CI

## [1.0.1] - 2025-11-05

### Changed
- Add CI and License badges to README
- Add GitHub Actions CI workflow
- Add release configuration files
## [1.0.0] - 2025-11-05

### Added
- Initial release of API Key Wildcard Plugin

### Changed
- Reset CHANGELOG for fresh 1.0.0 release
- Revise support and contributing sections in README
- Update links for documentation in README.md
