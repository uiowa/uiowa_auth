# uiowa_auth
Disables Drupal authentication and implements HawkID SSO. Requires samlauth
configuration for user name and email attributes. Includes a dynamic
configuration override for samlauth.authentication.idp_x509_certificate.


# Tests
Tests should be run from the root of a Drupal 8 installation against the 
default site, not a multisite.
 
To run test locally:
```
export SIMPLETEST_BASE_URL=http://localhost:8888
export SIMPLETEST_DB=mysql://root@localhost/drupal
export SYMFONY_DEPRECATIONS_HELPER=disabled
./vendor/bin/phpunit -c docroot/core/ docroot/modules/custom/uiowa_auth/tests
```
