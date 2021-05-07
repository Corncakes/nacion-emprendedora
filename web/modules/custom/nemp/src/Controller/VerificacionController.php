<?php

namespace Drupal\nemp\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines VerificacionController class.
 */
class VerificacionController extends ControllerBase {

  /**
   *
   * @return array
   */
  public function content() {
    $client = \Drupal::httpClient();
    $request = $client->get('http://pruebasruesapi.rues.org.co/Token', [
      'form_params' => [
        'Username' => 'INNPULSA_PRUEBAS',
        'grant_type' => 'password',
        'Password' => 'Fn=g45Sc'
      ]
    ]);
    $response = json_decode((string)$request->getBody()->getContents());

    $tempstore = \Drupal::service('user.private_tempstore')->get('nemp');
    $tempstore->set('token_val', $response->access_token);

    $tempstore = \Drupal::service('user.private_tempstore')->get('nemp');
    $token = $tempstore->get('token_val');

    //var_dump($token);

    $build = [
      '#markup' => '<br><br><h2>' . t('Hola.') . '</h2>',
    ];

    $form_class = '\Drupal\nemp\Form\ValidarForm';
    $build['form'] = \Drupal::formBuilder()->getForm($form_class);

    return $build;
  }

  /**
   *
   * @return array
   */
  public function registro() {
    $build = [
      '#markup' => '<br><br><h2>' . t('Hola.') . '</h2>',
    ];

    $form_class = '\Drupal\nemp\Form\RegistroForm';
    $build['form'] = \Drupal::formBuilder()->getForm($form_class);

    return $build;
  }

  /**
   *
   * @return array
   */
  public function noAplica() {
    $build = [
      '#markup' => '<br><br><h2>' . t('No aplica.') . '</h2>',
    ];

    return $build;
  }

  /**
   *
   * @return array
   */
  public function yaExiste() {
    $build = [
      '#markup' => '<br><br><h2>' . t('El NIT o Cédula que ingresaste, ya fue beneficiario de un dominio .co.') . '</h2>',
    ];

    return $build;
  }

  /**
   *
   * @return array
   */
  public function felicitaciones() {
    $build = [
      '#markup' => '<br><br><h2>' . t('Felicitaciones!.') . '</h2>',
    ];

    return $build;
  }
}
