# Changelog - api-key-wildcard

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

## [1.0.1] - 2025-10-24

### Changed
- Add NGINX configuration instructions
## [1.0.0] - 2025-10-22

### Added
- Add .releaseinclude for tab-swap and api-key-wildcard
- Add .releaseignore for release packaging
- Mark plugin as singleton
- Add automatic update detection
- Add automatic installation via enable() hook
- Add API Key Wildcard Plugin for osTicket

### Changed
- Reset all plugins to v0.9.0 for first official release
- doc: added Support Development to Readme's
- Standardize README.md structure and add LICENSE files
- doc: added License to README.md and fixed README.md
- Clean up CHANGELOG
- Add complete GPL v2 license files
- Add English/German documentation and two installation methods
- Change to separate endpoint architecture (no core patches)

### Fixed
- Use correct osTicket API for singleton instance creation
- Auto-create instance for singleton plugins on enable
- Prevent duplicate RewriteCond lines in .htaccess
- Apply robust .htaccess update to enable() hook
- Make .htaccess update robust against indentation
- Fix getInfo() undefined method error
- Correct URL pattern in wildcard.php
- Complete rewrite to avoid class redeclaration
- Prevent class redeclaration error in wildcard endpoint
