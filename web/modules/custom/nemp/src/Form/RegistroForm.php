<?php

namespace Drupal\nemp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements a form.
 */
class RegistroForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'registro_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['registro']['ciiu'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cod CIIU'),
      '#default_value' => '',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    );

    $form['registro']['sector'] = [
      '#type' => 'select',
      '#title' => $this->t('Select element'),
      '#options' => [
        '1' => $this->t('One'),
        '2' => $this->t('Two'),
        '3' => $this->t('Three'),
        '4' => $this->t('Four'),
        '5' => $this->t('Five'),
      ],
    ];

    $form['registro']['tiene_dominio'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Tiene página web con dominio .co, .com.co?'),
      '#default_value' => 1,
      '#options' => array(
        0 => $this->t('Si'),
        1 => $this->t('No'),
      ),
    );

    $form['registro']['dominio'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Nombre del dominio que quisiera activar'),
      '#default_value' => '',
      '#size' => 255,
      '#maxlength' => 128,
      '#required' => TRUE,
    );

    $form['registro']['autorizo'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Si, autorizo'),
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Continuar'),
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
    $connection = \Drupal::service('database');

    $tempstore = \Drupal::service('user.private_tempstore')->get('nemp');

    $nit = $token = $tempstore->get('nit');
    $codigo_estado_matricula = $tempstore->get('codigo_estado_matricula');
    $email = $tempstore->get('email');
    $ciiu = $form_state->getValue('ciiu');
    $sector = $form_state->getValue('sector');
    $dominio = $form_state->getValue('dominio');
    $tiene_dominio = $form_state->getValue('tiene_dominio');
    $autorizo = $form_state->getValue('autorizo');

    $registro = $connection->insert('co_registro')
      ->fields(['nit', 'codigo_estado_matricula', 'email', 'ciiu', 'sector', 'has_domain', 'domain', 'authorize'])
      ->values([
        'nit' => $nit,
        'codigo_estado_matricula' => $codigo_estado_matricula,
        'email' => $email,
        'ciiu' => $ciiu,
        'sector' => $sector,
        'has_domain' => $tiene_dominio,
        'domain' => $dominio,
        'authorize' => $autorizo,
      ])
      ->execute();

    $serialNumber = substr(md5(uniqid(rand(), true)), 0, 10);

    $userPin = $connection->insert('co_user_pin')
      ->fields(['pin', 'cid'])
      ->values([
        'pin' => $serialNumber,
        'cid' => $registro,
      ])
      ->execute();

    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'nemp';
    $key = 'general_mail';
    $to = "corncakes@gmail.com";
    $params['message'] = "This is the message";
    $params['subject'] = "Mail subject";
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($result['result'] !== true) {
      \Drupal::messenger()->addMessage(t('There was a problem sending your message and it was not sent.'), 'error');
    }
    else {
      \Drupal::messenger()->addMessage(t('Your message has been sent.'));
    }

    $url = \Drupal\Core\Url::fromRoute('nemp.nacionemprendedora_co_felicitaciones');
    $form_state->setRedirectUrl($url);
  }
}
