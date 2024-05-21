# Development Guides

## Basic guides
- Generate Block theme blocks (e.g. Payment options block...)
```
yarn build-blocks
```

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
docker run --rm --interactive --tty -e XDEBUG_MODE=off -e COMPOSER=composer-dev73.json -v $PWD:/app -v ~/.composer:/root/.composer npbtrac/php73_cli composer install
```
- Start the docker
```
docker-compose up -d wordpress73
```
- Check the website at http://127.0.0.1:${HTTP_EXPOSING_PORT_PREFIX}73

### Using PHP 8.1 docker
- Copy the environments and adjust the values to match your local
```
cp .env.example .env
```
- Install need dev stuff for PHP 8.1
```
XDEBUG_MODE=off COMPOSER=composer-dev81.json composer81 install
```
or if you don't have PHP 8.0 locally
```
docker run --rm --interactive --tty -e XDEBUG_MODE=off -e COMPOSER=composer-dev81.json -v $PWD:/app -v ~/.composer:/root/.composer npbtrac/php81_cli composer install
```
- Start the docker
```
docker-compose up -d wordpress81
```
- Check the website at http://127.0.0.1:${HTTP_EXPOSING_PORT_PREFIX}81

### Troubleshooting
- If you see the errors, try to do:
```
docker compose exec --user=webuser wordpress81 wp enpii-base enpii-base prepare
docker compose exec --user=webuser wordpress81 wp enpii-base artisan wp-app:setup
```

## Update `wp-release` branch
- Use the following commands
  - Remove vendors
  ```
  rm -rf vendor public-assets resources src src-deps languages
  ```
  - Update needed files from the main branches
  ```
  gco develop -- public-assets resources src src-deps languages composer.* package* tamara* webpack* yarn* readme* tsconfig.json readme.txt icon-* banner-*
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
- We use PHPUnit with the directory structure from CodeCeption.

Install/update dependencies (you should use PHP 8.0+)
```
composer install
```

We must run the composer and codecept run test using PHP 8.0+ (considering `php80` is the alias to your PHP 8.0 executable file)

If you don't have PHP 8.0 locally, you can use the docker:
```
docker pull npbtrac/php80_cli
```
and whenever you want to run unit test, you can do something like this:
```
docker run --rm --interactive --tty -v $PWD:/app npbtrac/php80_cli ./vendor/bin/phpunit
```
- Run Unit Test with Codeception (for the whole unit suite)
```
php80 ./vendor/bin/phpunit
```

- Create a Unit Test file
```
docker-compose exec --user=webuser --workdir=/var/www/html/public/wp-content/plugins/tamara-checkout wordpress81 wp enpii-base artisan wp-app:make:phpunit <relative/path/without/.php>
```
e.g.
```
docker-compose exec --user=webuser --workdir=/var/www/html/public/wp-content/plugins/tamara-checkout wordpress81 wp enpii-base artisan wp-app:make:phpunit tests/Unit/App/Support/Tmp
```

### Using Coverage report
- Run Unit Test with PHPUnit (with coverage report)
```
docker-compose exec --workdir=/var/www/html/public/wp-content/plugins/tamara-checkout wordpress81 yarn phpunit:coverage
```

- Run Unit Test with PhpUnit (with coverage report)
```
docker-compose exec --workdir=/var/www/html/public/wp-content/plugins/tamara-checkout wordpress81 yarn phpunit:coverage-single --whitelist=<path/to/folder/to/perform/the/coverage> <path/to/test/folder>
```
e.g.
```
docker-compose exec --workdir=/var/www/html/public/wp-content/plugins/tamara-checkout wordpress81 yarn phpunit:coverage-single --whitelist=src/App/Support/Tamara_Checkout_Helper.php tests/Unit/App/Support/Tamara_Checkout_Helper_Test.php
```
