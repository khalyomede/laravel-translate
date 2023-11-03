# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Ability to specify multiple columns to translate for a single model ([#55](https://github.com/khalyomede/laravel-translate/issues/55)).

### Breaking

- The order of the translations is not the same when translating a single model column ([#55](https://github.com/khalyomede/laravel-translate/issues/55)).

## [0.1.2] - 2023-09-19

### Fixed

- Keys containing a colon will now be correctly detected ([#53](https://github.com/khalyomede/laravel-translate/issues/53)).
- The command will now detect keys aproximatively 40 times faster ([#52](https://github.com/khalyomede/laravel-translate/issues/52)).

## [0.1.1] - 2023-09-19

### Fixed

- The command will not hang a few seconds without showing progress anymore ([#46](https://github.com/khalyomede/laravel-translate/issues/46)).
- Texts starting with "New" will now be correctly translated ([#49](https://github.com/khalyomede/laravel-translate/issues/49)).

## [0.1.0] - 2023-03-25

### Added

- First working version.
