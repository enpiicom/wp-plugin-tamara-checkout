### Update `wp-release` branch
- Use the following commands
  - Remove vendors
  ```
  rm -rf vendor
  ```
  - Update needed files from the main branches
  ```
  gco develop -- public-assets resources src src-deps composer.* package* tamara* webpack* yarn* phpcs*
  ```
  - Compile css and js
  ```
  npm install
  npm run build
  ```
  - Force to add the compiled css and js to the repo
  ```
  git add --force public-assets/dist
  ```
  - Install the dependencies
  ```
  composer install --no-dev --ignore-platform-reqs
  ```
  - Force to add vendor
  ```
  git add --force vendor
  ```
  - Remove require stuff in composer.json
  - The add and commit everything

### Codestyling (PHPCS)
- Fix all possible phpcs issues
```
php74 ./vendor/bin/phpcbf
```
- Fix possible phpcs issues on a specified folder
```
php74 ./vendor/bin/phpcbf <path/to/the/folder>
```
- Find all the phpcs issues
```
php74 ./vendor/bin/phpcs
```
- Suppress one or multible phpcs rules for the next below line
```
// phpcs:ignore <rule1>(, <rule2>...)
```
or at same line
```
$foo = "bar"; // phpcs:ignore
```
- Disable phpcs for a block of code
```
// phpcs:disable
/*
$foo = 'bar';
*/
// phpcs:enable

### Running Unit Test
We must run the composer and codecept run test using PHP 7.4
- Set up
```
php74 ./vendor/bin/codecept build
```
- Run Unit Test with Codeception on a specific file (for development purposes)
```
php74 ./vendor/bin/codecept run -vvv unit tests/unit/App/Support/Tamara_Checkout_Helper_Test.php
```
- Run Unit Test with PhpUnit on a specific file (for development purposes)
```
php74 ./vendor/bin/phpunit --verbose tests/unit/App/Support/Tamara_Checkout_Helper_Test.php
```
- Run Unit Test with Codeception (for the whole unit suite)
```
php74 ./vendor/bin/codecept run unit
```
#### Using Coverage report
- Run Unit Test with Codeception (with coverage report)
```
XDEBUG_MODE=coverage php74 ./vendor/bin/codecept run --coverage --coverage-xml --coverage-html unit
```
- Run Unit Test with PhpUnit (with coverage report)
```
XDEBUG_MODE=coverage php74 ./vendor/bin/phpunit --coverage-text -vvv tests/unit
```
