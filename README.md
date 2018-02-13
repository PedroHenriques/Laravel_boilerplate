[![Build Status](https://travis-ci.org/PedroHenriques/Laravel_boilerplate.svg?branch=5.5)](https://travis-ci.org/PedroHenriques/Laravel_boilerplate)

# Laravel 5.5.* Boilerplate

## Setup

1. Download this repository as a ZIP file and unzip its contents into an empty directory (your project's root directory).
2. Rename the file `rename.gitignore` to `.gitignore`
3. `cd` into the project's directory
4. Run `composer install`
5. Do all the Laravel specific configuration (ex: app key, database credentials, etc.)
6. Run the database migrations using `php artisan migrate`
7. Change the views to your application's needs

### Test Environment Setup

1. Update the project's test database and webserver information in the `phpunit.xml` file under

```xml
<php>
    <var name="DB_DSN" value="mysql:host=localhost;dbname=phpunit_test" />
    <var name="DB_USER" value="root" />
    <var name="DB_PASSWD" value="" />
    <var name="DB_DBNAME" value="phpunit_test" />

    <var name="DOMAIN_NAME" value="localhost" />
    <var name="DOMAIN_PORT" value="8000" />
    ...
</php>
```

2. Update the project's test database information in the `.env.testing` file

3. Create the project's test database, matching the name inserted in step 1

4. Run the database migrations on your test database using `php artisan migrate --env=testing`

**NOTE:** The database fixtures used in the integration and end-to-end tests expect the `APP_KEY` value in use for the **testing** environment to be the one defined by this boilerplate.
If you change its value, all the passwords and tokens used in the fixtures will have to be rehashed using the new `APP_KEY`.

## What this boilerplate contains

- **User authentication via username or email:**

  A user can login using either email + password or username + password.
  To customize the unique identifiers that can be used to authenticate a user, add the necessary checks to the `credentials` method in `LoginController`.

- **Activation email:**

  After a new account is registered, by submitting the registration form in `/register`, the account will be created in an **inactive** state - represented by a value of zero in the `is_active` column of the `users` table.
  An activation token will be generated, stored in the `account_activations` table and an email will be sent to the email address provided in the registration form.
  **NOTE:** The duration of these token can be configured in the `auth.php` config file.
  Once the activation link is visited the account will be activated and the user will be allowed to login.
  A new activation can be requested through the route `resendActivation`.
  All actions related to this feature are handled by the `ActivationController`.

  **Middleware:**
  The middleware `App\Http\Middleware\RedirectIfInactive` (alias `activeAccount`) will only let a request through if the authenticated user's account is active.
  There is also a middleware group named `auth.full` that combine Laravel's `auth` middleware with `activeAccount`.

- **Roles:**

  There is often a need to restrict a user's access based on a more layered system, which is beyond what can be done with simply checking if a user is authenticated or not.
  To solve this need a role system is available, where each user will be assigned a role, stored in the `roles` table.
  The role that will be assigned to a new user is defined in the `roles.php` config file.
  Besides assigning a role to each user, the system also supports a **hierarchy** to the roles, also defined in the `roles.php` config file. This hierarchy will grant the permissions of any roles lower in the chain as well as the user's role specific permissions.

  **Role manager:**
  To facilitate checking if a user has the permissions granted by a certain role or to change a user's role, the `App\Services\RoleManager` class is available.

  **Middleware:**
  The middleware `App\Http\Middleware\CheckRole` (alias `withRole`), which expects a role id, will only let a request through if the authenticated user has the permissions granted by that role.

- **Validation:**

  To facilitate grouping validation rule sets and adding extra layers of validation, the `App\Validators\BaseValidator` abstract class provides validation functionality that can be extended to specific validation classes where the rule sets are stored in an easier to maintain format.

**NOTE:** the Laravel provided authentication controllers were kept as intact as possible, with the minimal changes made to allow the extra features provided by this boilerplate.

## Testing this boilerplate

This boilerplate comes with **unit**, **integration** and **end-to-end** tests, located in the `tests` directory.
There is a script in composer.json named `test` that facilitates the execution of the tests.

#### Unit tests

To execute these tests run `composer test -- tests/unit` from the project's root directory.

#### Integration tests

- **Requirements**: a running database server + [a running mailcatcher server](https://mailcatcher.me/ "Mailcatcher's Homepage")

The test classes can extend `tests/integration/BaseIntegrationCase.php` which provides integration with dbunit, allowing the fixtures to be inserted into the test database before each individual test.
This class uses `Tests\CreatesApplication` giving access to Laravel app instance if needed.

To execute these tests run `composer test -- tests/integration` from the project's root directory.

#### End-to-end tests

- **Requirements**: a running database server + a running webserver + [a running mailcatcher server](https://mailcatcher.me/ "Mailcatcher's Homepage")

The test classes can extend `tests/e2e/MinkTestCase.php` which provides integration with dbunit and Mink, allowing the fixtures to be inserted into the test database before each individual test and the use of web browsers to interact with the front-end of the website.

To execute these tests run `composer test -- tests/e2e` from the project's root directory.