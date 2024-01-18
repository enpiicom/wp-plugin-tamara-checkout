### Update `wp-release` branch
- Use the following commands
  - Remove vendors
  ```
  rm -rf vendor
  ```
  - Update needed files from the main branchs  
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
