<?php

namespace Drupal\uiowa_auth\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class LegacyLoginController extends ControllerBase {
  public function build() {
    $legacy_redirect = $this->config('uiowa_auth.settings')->get('legacy_redirect');

    if ($legacy_redirect) {
      return $this->redirect('samlauth.saml_controller_login');
    }
    else {
      throw new AccessDeniedHttpException();
    }
  }

}
