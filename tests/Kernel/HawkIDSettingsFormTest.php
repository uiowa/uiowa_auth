<?php

namespace Drupal\Tests\uiowa_auth\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\uiowa_auth\Form\HawkIDSettingsForm;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Class HawkIDSettingsFormTest.
 *
 * @group kernel
 */
class HawkIDSettingsFormTest extends EntityKernelTestBase {
  protected $strictConfigSchema = FALSE;

  /**
   * Test form submits with valid values.
   */
  public function testValidHawkIdForm() {
    $factory = $this->container->get('config.factory');
    $hawkid_settings_form = new HawkIDSettingsForm($factory);
    $form_state = new FormState();
    $form = [];

    $form_state->setValues([
      'role_mappings' => 'admin|CN=foo,OU=bar,OU=baz' . PHP_EOL . 'webmaster|CN=foo,OU=bar' . PHP_EOL . 'webmaster|CN=baz,OU=qux',
      'member_of_attribute' => 'baz',
    ]);

    $hawkid_settings_form->submitForm($form, $form_state);
    $this->assertEquals(FALSE, $form_state->hasAnyErrors());

    $this->assertEquals([
      'admin' => 'CN=foo,OU=bar,OU=baz',
      'webmaster' => 'CN=baz,OU=qux',
      'webmaster' => 'CN=foo,OU=bar',
    ], $factory->get('uiowa_auth.settings')->get('role_mappings'));

    $this->assertEquals('baz', $factory->get('uiowa_auth.settings')->get('member_of_attribute'));
  }

  /**
   * Test form fails with invalid values.
   *
   * @dataProvider invalidValues
   */
  public function testInvalidHawkIdForm($mapping, $message) {
    $factory = $this->container->get('config.factory');
    $hawkid_settings_form = new HawkIDSettingsForm($factory);
    $form_state = new FormState();
    $form = [];

    $form_state->setValues([
      'role_mappings' => $mapping,
      'member_of_attribute' => 'baz',
    ]);

    $hawkid_settings_form->validateForm($form, $form_state);
    $this->assertArrayHasKey('role_mappings', $form_state->getErrors());
    $this->assertEquals($message, $form_state->getErrors()['role_mappings']->render());
  }

  /**
   * Data provider for invalid values.
   *
   * @see testInvalidHawkIdForm()
   */
  public function invalidValues() {
    return [
      [
        'CN=foo,OU=bar,OU=baz',
        'Mapping CN=foo,OU=bar,OU=baz does not contain a pipe character (|).',
      ],
      [
        'CN=foo|OU=bar|OU=baz',
        'Mapping CN=foo|OU=bar|OU=baz contains more than one pipe character (|). Separate multiple mappings with a return.',
      ],
    ];
  }

}
