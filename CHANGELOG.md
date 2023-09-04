# Change Log

All notable changes to this project will be documented in this file. This project adheres
to [Semantic Versioning] (http://semver.org/). For change log format,
use [Keep a Changelog] (http://keepachangelog.com/).

## [2.3.0] - 2023-09-04

### Changed

- Load composer packages from development too ; to allow usage of commands in dev mode

## [2.2.0] - 2022-01-25

### Added

- Compatibility with library `league/flysystem` ^3.0

### Changed

- `EntryPoints::get()` accept an array of entry name

## [2.1.0] - 2022-01-13

### Changed

- Assets are now loaded on demand and not systematically
- Bump package `berlioz/service-container` to 2.1

### Fixed

- Bad path to get environment from config
- Application services provides class list
- List of provides class not added to service container

## [2.0.1] - 2021-09-23

### Fixed

- Services missing from the list of services provided; tests added to verify in the future

## [2.0.0] - 2021-09-08

### Fixed

- Signature of `PhpErrorSet::count(): int`

## [2.0.0-beta2] - 2021-07-07

### Added

- New `Filesystem` class to mount only necessaries filesystems

### Changed

- Change `Event::getEvent()` for `Event::getName()`
- Debug event get name of event for CustomEvent

### Fixed

- Bad config path 'berlioz.env' instead of 'berlioz.environment'
- Retrieve of config modification time
- Add events subscribers from config
- Add services providers from config
- Missing start event activity for debug
- Fix array keys of filtered activities to get memory usage

## [2.0.0-beta1] - 2021-06-07

### Added

- New event manager `berlioz/event-manager`
- New filesystem `league/flysystem`

### Changed

- Refactoring
- Bump minimal compatibility to PHP 8
- Upgrade `berlioz/config` to 2.x
- Upgrade `berlioz/service-container` to 2.x
- Container services config path changed for "container.services"

### Removed

- Dependency to `psr/log`

## [1.1.0] - 2020-09-09

### Added

- PHP 8 support in `composer.json` file
- New method `Composer::getVersion(): ?string` to get version of composer project

## [1.0.1] - 2020-07-09

### Changed

- Handle multiple ip in HTTP headers for debug

## [1.0.0] - 2020-05-29

First version
