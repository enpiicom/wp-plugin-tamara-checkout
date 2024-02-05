# Development Guides

## Use the docker to deploy the project locally

### Using PHP 7.3 docker
- Copy the environments and adjust the values to match your local
```
cp .env.example .env
```
- Install need dev stuff for PHP 7.3
```
XDEBUG_MODE=off COMPOSER=composer-dev73.json composer73 install
```
or if you don't have PHP 7.3 locally
```
docker run --rm --interactive --tty -e XDEBUG_MODE=off -e COMPOSER=composer-dev73.json -v $PWD:/app npbtrac/php73_cli composer install
```
- Start the docker
```
docker-compose up -d wordpress73
```
- Check the website at http://127.0.0.1:${HTTP_EXPOSING_PORT_PREFIX}73

### Using PHP 8.0 docker
- Copy the environments and adjust the values to match your local
```
cp .env.example .env
```
- Install need dev stuff for PHP 8.0
```
XDEBUG_MODE=off COMPOSER=composer-dev80.json composer80 install
```
or if you don't have PHP 8.0 locally
```
docker run --rm --interactive --tty -e XDEBUG_MODE=off -e COMPOSER=composer-dev80.json -v $PWD:/app npbtrac/php80_cli composer install
```
- Start the docker
```
docker-compose up -d wordpress80
```
- Check the website at http://127.0.0.1:${HTTP_EXPOSING_PORT_PREFIX}73

### Troubleshooting
- If you see the errors, try to do:
```
docker compose exec wordpress wp --allow-root enpii-base prepare
docker compose exec wordpress wp --allow-root enpii-base artisan wp-app:setup
```

## Update `wp-release` branch
- Use the following commands
  - Remove vendors
  ```
  rm -rf vendor public-assets resources src src-deps
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

## Codestyling (PHPCS)
Install/update dependencies (you should use PHP 8.0+)
```
composer install
```

- Fix all possible phpcs issues
```
php ./vendor/bin/phpcbf
```
- Fix possible phpcs issues on a specified folder
```
php ./vendor/bin/phpcbf <path/to/the/folder>
```
- Find all the phpcs issues
```
php ./vendor/bin/phpcs
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
```

## Running Unit Test
Install/update dependencies (you should use PHP 8.0+)
```
composer install
```

We must run the composer and codecept run test using PHP 8.0+ (considering `php80` is the alias to your PHP 8.0 executable file)

If you don't have PHP 8.0 locally, you can use the docker:
```
docker pull serversideup/php:8.0-cli
```
and whenever you want to rin something, you can do something like this:
```
docker run --rm --interactive --tty -v $PWD:/var/www/html serversideup/php:8.0-cli ./vendor/bin/codecept build
```
- Set up
```
php80 ./vendor/bin/codecept build
```
- Run Unit Test with Codeception on a specific file (for development purposes)
```
php80 ./vendor/bin/codecept run -vvv unit tests/unit/App/Support/Tamara_Checkout_Helper_Test.php
```
- Run Unit Test with PhpUnit on a specific file (for development purposes)
```
php80 ./vendor/bin/phpunit --verbose tests/unit/App/Support/Tamara_Checkout_Helper_Test.php
```
- Run Unit Test with Codeception (for the whole unit suite)
```
php80 ./vendor/bin/codecept run unit
```

### Using Coverage report
- Run Unit Test with Codeception (with coverage report)
```
XDEBUG_MODE=coverage php80 ./vendor/bin/codecept run --coverage --coverage-xml --coverage-html unit
```
- Run Unit Test with PhpUnit (with coverage report)
```
XDEBUG_MODE=coverage php80 ./vendor/bin/phpunit --coverage-text -vvv tests/unit
```
