# website toolbox community Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 2.0.9 - 2026-10-09
### Added
- Backoff for setauthtoken failures: transient forum errors are retried after 60 seconds, permanent errors (invalid API key, closed registrations, account conflicts) are not retried until the apikey or user changes (12-hour safety window).
- Connect (3s) and response (10s) timeouts on forum API requests so a slow forum cannot stall page requests.
### Changed
- Forum address updates now arrive only through the signed webhook endpoint instead of a validateAPIKey request on every page view; the webhook route is registered whether or not the community is embedded.
- The embed URL is sent with the checkPluginLogin settings save, and the separate modifySSOURLs request only fires when the embed URL actually changed.
- The SSO group permission check is memoized per request and cached per user for 15 minutes instead of querying user groups on every check.
### Fixed
- After-login handler is registered on craft\web\User instead of every component.

## 2.0.8 - 2026-07-10
### Added
- AUto update plugin settings on related detail change by forum admin.

## 2.0.7 - 2026-06-20
### Added
- Enable the forum embed by default for users when they log into the plugin for the first time.

## 2.0.6 - 2024-09-03
### Added
- Revise the plugin settings page to enhance user-friendliness.

## 2.0.5 - 2024-08-02
### Added
- Compatibility with CraftCMS version 5.

## 2.0.4 - 2024-03-20
### Added
- Fixed header already sent issue.

## 2.0.3 - 2023-10-25
### Added
- Introduce a single sign-on option that enables the choice to enable single sign-on for either all user groups or specific user groups.

## 2.0.2 - 2023-08-21
### Added
- Fixed too many request error

## 2.0.1 - 2023-06-12
### Added
- Added functionality to update domain automatically.

## 2.0.0 - 2023-04-28
### Added
- Added support for CraftCMS 4.
 
## 1.3.0 - 2021-12-27
### Added
- Website Toolbox Community Settings issue resolved.  

## 1.0.0 - 2020-06-30
### Added
- Initial release