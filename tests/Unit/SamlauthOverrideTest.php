<?php

namespace Drupal\Tests\rusty\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\uiowa_auth\ConfigOverride\SamlauthOverride;

/**
 * Test description.
 *
 * @group unit
 */
class SamlauthOverrideTest extends UnitTestCase {

  /**
   * Test config overrides for different environment variables.
   *
   * @dataProvider providerConfigByEnv
   */
  public function testConfigByEnv($env, $sp, $idp, $sso, $slo) {
    $sut = new SamlauthOverride($this->root);
    putenv('AH_SITE_ENVIRONMENT=' . $env);
    file_put_contents($this->root . '/../vendor/onelogin/php-saml/certs/idp.crt', 'foo');

    $overrides = $sut->loadOverrides(['samlauth.authentication']);
    $this->assertEquals($overrides['samlauth.authentication']['idp_x509_certificate'], 'foo');
    $this->assertEquals($overrides['samlauth.authentication']['sp_entity_id'], $sp);
    $this->assertEquals($overrides['samlauth.authentication']['idp_entity_id'], $idp);
    $this->assertEquals($overrides['samlauth.authentication']['idp_single_sign_on_service'], $sso);
    $this->assertEquals($overrides['samlauth.authentication']['idp_single_log_out_service'], $slo);
  }

  /**
   * DataProvider for testConfigByEnv().
   */
  public function providerConfigByEnv() {
    return [
      [
        'dev',
        'https://saml.stage.drupal.uiowa.edu',
        'iowafed-test:idp:uiowa.edu',
        'https://idp-test.uiowa.edu/idp/profile/SAML2/Redirect/SSO',
        'https://idp-test.uiowa.edu/idp/profile/SAML2/Redirect/SLO',
      ],
      [
        'test',
        'https://saml.stage.drupal.uiowa.edu',
        'iowafed-test:idp:uiowa.edu',
        'https://idp-test.uiowa.edu/idp/profile/SAML2/Redirect/SSO',
        'https://idp-test.uiowa.edu/idp/profile/SAML2/Redirect/SLO',
      ],
      [
        'prod',
        'https://saml.prod.drupal.uiowa.edu',
        'urn:mace:incommon:uiowa.edu',
        'https://idp.uiowa.edu/idp/profile/SAML2/Redirect/SSO',
        'https://idp.uiowa.edu/idp/profile/SAML2/Redirect/SLO',
      ],
    ];
  }

}
