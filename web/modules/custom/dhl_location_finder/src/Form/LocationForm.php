<?php

namespace Drupal\dhl_location_finder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class LocationForm extends FormBase {

  public function getFormId() {
    return 'dhl_location_finder_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => $this->getCountryOptions(),
      '#required' => TRUE,
    ];

    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#required' => TRUE,
    ];

    $form['postal_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal Code'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Find Locations'),
    ];

    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $country = $form_state->getValue('country');
    $city = $form_state->getValue('city');
    $postal_code = $form_state->getValue('postal_code');


    $form_state->setRedirect('dhl_location_finder.results', [
      'country' => $country,
      'city' => $city,
      'postalCode' => $postal_code,
    ]);
  }
  private function getCountryOptions() {
    return [
      'IN' => 'India',
      'DE' => 'Germany',
      'US' => 'United States',
      'FR' => 'France'
    ];
  }

}
