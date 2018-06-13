# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [3.0.1] - 2018-06-13
### Fixed
- Fixed incorrect reset password email subject, in the E2E tests for the ForgotPasswordController, due to changes in Laravel's default controller.

## [3.0.0] - 2018-02-13
### Added
- Switched from Laravel `5.5.*` to Laravel `5.6.*`.

### Changed
- The readme.md file to include information about the branches supporting previous versions of Laravel.

### Fixed
- Fixed bug with "BaseJob.php" where the class was named "BaseJobs" instead of "BaseJob".

## [2.1.0] - 2018-01-25
### Added
- "getRules()" and "getMessages()" to BaseValidator.
- Unit tests for the rules and messages of "ActivationValidator".
- Changelog file.

## [2.0.0] - 2018-01-23
### Changed
- Renamed the "roles" method in "User" model to "role".

## [1.0.1] - 2018-01-23
### Changed
- Updated the "Auth.full" web middleware group to use its individual middleware alias, instead of their FQNs.
- Updated the "test" Composer script to not stop on failure.
- Improved the Doc Blocks for the "Role" model's "users" relationship method.

## [1.0.0] - 2017-11-22
### Added
- Initial version of this boilerplate.