<?php

namespace Drupal\uiowa_auth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uiowa_auth\RoleMappings;

/**
 * Configure HawkID settings for this site.
 */
class HawkIDSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uiowa_auth';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['uiowa_auth.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $mappings = $this->config('uiowa_auth.settings')->get('role_mappings');

    $text = RoleMappings::arrayToText($mappings);

    $form['description'] = [
      '#markup' => $this->t('HawkID authenticated users can have a member-of
      attribute set representing group membership. Roles can be mapped
      automatically based on the distinguished names (DN) in that
      attribute. Roles will be re-evaluated upon each login and
      assigned/revoked, accordingly. DNs must match exactly.'),
    ];

    $form['role_mappings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Role mappings'),
      '#description' => $this->t('Enter role mappings in the format rid|dn
       where rid is the machine name of the role and dn is the distinguished
       name to match on the member_of attribute.
       Ex. webmaster|CN=MyGroup,OU=Groups,OU=MyDomain,DC=iowa,DC=uiowa,DC=edu.
       Separate multiple mappings with a return.'),
      '#default_value' => $text,
    ];

    $form['member_of_attribute'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Member-of attribute'),
      '#description' => $this->t('The attribute name to parse for member-of values in the SAML response.'),
      '#default_value' => $this->config('uiowa_auth.settings')->get('member_of_attribute'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $mappings = RoleMappings::stringToArray($form_state->getValue('role_mappings'));

    foreach ($mappings as $mapping) {
      $parts = explode('|', $mapping);

      if (count($parts) == 1) {
        $form_state->setErrorByName('role_mappings', $this->t('Mapping @mapping does not contain a pipe character (|).', ['@mapping' => $mapping]));
      }

      if (count($parts) > 2) {
        $form_state->setErrorByName('role_mappings', $this->t('Mapping @mapping contains more than one pipe character (|). Separate multiple mappings with a return.', ['@mapping' => $mapping]));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mappings = RoleMappings::stringToArray($form_state->getValue('role_mappings'));

    $this->config('uiowa_auth.settings')
      ->set('role_mappings', $mappings)
      ->set('member_of_attribute', $form_state->getValue('member_of_attribute'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
