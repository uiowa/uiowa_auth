<?php

namespace Drupal\uiowa_auth\ConfigOverride;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Psr\Log\LoggerInterface;

/**
 * Samlauth configuration overrides.
 *
 * @see: samlauth.authentication.yml for static configuration.
 */
class SamlauthOverride implements ConfigFactoryOverrideInterface {

  protected $appRoot;
  protected $logger;

  /**
   * Constructor to inject dependencies.
   *
   * @param string $root
   *   The app.root service (SplString).
   * @param Psr\Log\LoggerInterface $logger
   *   The uiowa_auth logger channel.
   */
  public function __construct($root, LoggerInterface $logger) {
    $this->appRoot = $root;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (in_array('samlauth.authentication', $names)) {
      $env = getenv('AH_SITE_ENVIRONMENT');
      $env = ($env == FALSE) ? 'dev' : $env;

      // Note that 'dev' is the default static configuration.
      // @see: samlauth.authentication.yml
      switch ($env) {
        case 'lando':
        case 'dev':
        case 'test':
          $sp = "https://saml.stage.drupal.uiowa.edu";
          $idp = 'iowafed-test:idp:uiowa.edu';
          $sso = 'https://idp-test.uiowa.edu/idp/profile/SAML2/Redirect/SSO';
          $slo = 'https://idp-test.uiowa.edu/idp/profile/SAML2/Redirect/SLO';
          break;

        case 'prod':
          $sp = 'https://saml.prod.drupal.uiowa.edu';
          $idp = 'urn:mace:incommon:uiowa.edu';
          $sso = 'https://idp.uiowa.edu/idp/profile/SAML2/Redirect/SSO';
          $slo = 'https://idp.uiowa.edu/idp/profile/SAML2/Redirect/SLO';
          break;
      }

      // The samlauth module does not allow for file configuration like the SP.
      // This value will not be displayed on the configuration form since it is
      // an override.
      $file = $this->appRoot . '/../vendor/onelogin/php-saml/certs/idp.crt';

      if (file_exists($file)) {
        $idp_cert = file_get_contents($file);
      }
      else {
        $idp_cert = '';
        $this->logger->warning('Unable to load IDP certificate from vendor directory.');
      }

      $overrides['samlauth.authentication']['sp_entity_id'] = $sp;
      $overrides['samlauth.authentication']['idp_entity_id'] = $idp;
      $overrides['samlauth.authentication']['idp_x509_certificate'] = $idp_cert;
      $overrides['samlauth.authentication']['idp_single_sign_on_service'] = $sso;
      $overrides['samlauth.authentication']['idp_single_log_out_service'] = $slo;
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SamlauthOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
