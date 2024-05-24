# Update `wp-release` branch

We need to have a version that can be submit to WordPress.org plugin hub which includes all dependencies, assets.

- Use the following commands
  - Remove vendors
  ```
  rm -rf vendor public-assets resources src src-deps languages
  ```
  - Update needed files from the main branches
  ```
  gco master -- public-assets resources src src-deps languages composer.* package* tamara* webpack* yarn* readme* tsconfig.json readme.txt icon-* banner-*
  ```
  - Compile css and js (Yarn 20)
  ```
  yarn install
  yarn build
  ```
  - Install the dependencies
  ```
  composer install --no-dev --ignore-platform-reqs
  ```
  - Force to add vendor and assets
  ```
  git add --force vendor public-assets/dist
  ```
  - Remove `require` and `require-dev` stuff in composer.json
  - The add and commit everything
