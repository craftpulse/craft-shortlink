# craft-shortlink Changelog

All notable changes to this project will be documented in this file.

## 4.0.0-beta.15 - 2022-09-19

### Added
- Added the preview of the shortlink urls in the sidebar

## 4.0.0-beta.14 - 2022-09-12

### Fixed
- Fixed bug when a draft was created and save after, the shortlink wouldn't update

## 4.0.0-beta.13 - 2022-09-12

### Fixed
- Fixed bug when shortlink save didn't happen after entry save

## 4.0.0-beta.12 - 2022-09-11

### Fixed
- Fixed bug when shortlink regenerated when revision put back in place

## 4.0.0-beta.11 - 2022-09-07

### Fixed
- Fixed a new issue where the shortlink wouldn't regenerate on a duplicated entry
- Fixed the regenerate when a revision was created

## 4.0.0-beta.10 - 2022-09-07

### Fixed
- Fixed an issue where the shortlink wouldn't regenerate on a duplicated entry

## 4.0.0-beta.9 - 2022-09-07

### Fixed
- Fixed an issue where the homepage redirect wouldn't resolve correctly

## 4.0.0-beta.8 - 2022-09-07

### Added
- Added check to see if shortlinkUrl is set in settings, if not, do nothing.

## 4.0.0-beta.6 - 2022-09-06

### Changed
- improved fallback for shortlink ID

## 4.0.0-beta.5 - 2022-09-06

### Fixed
- fix regeneration on revision changes

## 4.0.0-beta.4 - 2022-09-06

### Fixed
- null check

## 4.0.0-beta.3 - 2022-09-06

### Added
- Eslint Config

### Fixed
- Issue that could occur when there is no cpEditUrl
- Linting issues

## 4.0.0-beta.2 - 2022-09-06

### Added
- Shortlink redirect caching
- Static redirects can be deleted
- Added incrementing hitCount
- Added when shortlink is last used
- Added dashboard shortlink overview

### Changed
- Improved redirecting

### Fixed
- Element registration
- findAll / findOne functionality
- Content settings in tailwind config

## 4.0.0-beta.1 - 2022-09-02

### Added
- Initial release
