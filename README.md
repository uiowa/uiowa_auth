# uiowa_auth
Disables Drupal authentication and implements HawkID SSO. 

# Tests
Tests should be run from the root of a Drupal 8 installation against the 
default site.
 
To run test locally:
```
export SIMPLETEST_BASE_URL=http://localhost:8888
export SIMPLETEST_DB=mysql://root@localhost/drupal
export SYMFONY_DEPRECATIONS_HELPER=disabled
./vendor/bin/phpunit -c docroot/core/ docroot/modules/custom/uiowa_auth/tests
```
