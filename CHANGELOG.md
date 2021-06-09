# Change Log

All notable changes to this project will be documented in this file. This project adheres
to [Semantic Versioning] (http://semver.org/). For change log format,
use [Keep a Changelog] (http://keepachangelog.com/).

## [2.0.0-beta2] - In progress

### Changed

- Change `Event::getEvent()` for `Event::getName()`
- Debug event get name of event for CustomEvent

### Fixed

- Add subscribers from config
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