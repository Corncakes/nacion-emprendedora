<?php

namespace Drupal\nemp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements a form.
 */
class ValidarForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'validar_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['validar']['nit'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Ingresa con tu CC o NIT para realizar verificación de datos'),
      '#default_value' => '',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Quiero ser beneficiario'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate submitted form data.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tempstore = \Drupal::service('user.private_tempstore')->get('nemp');
    $nit = $form_state->getValue('nit');
    $tempstore->set('nit', $nit);

    $connection = \Drupal::service('database');
    $query = $connection->select('co_registro', 'cr');
    $query
      ->condition('cr.nit', $nit, '=')
      ->fields('nit', array('nit'));
    $numRows = $query->countQuery()->execute()->fetchField();

    if ($numRows == 0) {
      $token = $tempstore->get('token_val');
      $headers = [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
      ];
      $client = \Drupal::httpClient();
      try {
        $request = $client->post('http://pruebasruesapi.rues.org.co/api/consultasRUES/ConsultaNIT', [
          'headers' => $headers,
          'query' => [
            'usuario' => 'pruebas',
            'nit' => $nit,
            'dv' => ''
          ]
        ]);
        $response = json_decode((string)$request->getBody()->getContents());
      } catch (RequestException $e) {
        watchdog_exception('nemp', $e->getMessage());
      }

      if ($response->registros[0]->codigo_estado_matricula == '01') {
        $tempstore->set('email', $response->registros[0]->correo_electronico_comercial);
        $tempstore->set('codigo_estado_matricula', $response->registros[0]->codigo_estado_matricula);

        $url = \Drupal\Core\Url::fromRoute('nemp.nacionemprendedora_co_registro');
      } else {
        $url = \Drupal\Core\Url::fromRoute('nemp.nacionemprendedora_co_no_aplica');
      }
    } else {
      $url = \Drupal\Core\Url::fromRoute('nemp.nacionemprendedora_co_ya_existe');
    }
    $form_state->setRedirectUrl($url);
  }
}
