<?php

namespace Drupal\scholarship_beneficiary_profile\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\scholarship_beneficiary_profile\Service\MigrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Form controller for the Finance Details form.
 *
 * This form handles the submission, validation, and display of finance-related
 * details for the scholarship beneficiary profile.
 *
 * @package Drupal\scholarship_beneficiary_profile\Form
 * @author Vaibhav
 */
class FinanceDetailForm extends MultiStepFormBase
{
  /**
   * The MigrationService instance.
   *
   * @var \Drupal\scholarship_beneficiary_profile\Service\MigrationService
   */
  protected $migrationService;

  /**
   * Constructs a new FinanceDetailForm.
   *
   * @param \Drupal\scholarship_beneficiary_profile\Service\MigrationService $migrationService
   *   The migration service.
   */
  public function __construct(MigrationService $migrationService)
  {
    $this->migrationService = $migrationService;
  }

  /**
   * Creates an instance of the form from the container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return static
   *   The form instance.
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('scholarship_beneficiary_profile.migration_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'finance_details';
  }

  /**
   * Builds the form for the Finance Details section.
   *
   * Dynamically generates the form fields based on the available fields and
   * any existing values in the database for the current user.
   *
   * @param array $form
   *   The existing form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param string|null $uniqueId
   *   The unique ID of the user (optional).
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uniqueId = null)
  {
    $form_state->set('uniqueId', $uniqueId);
    $current_user = \Drupal::currentUser();
    $id = $uniqueId ?? $current_user->id();

    if ($uniqueId && empty(array_intersect(['administrator'], $current_user->getRoles()))) {
      throw new AccessDeniedHttpException();
    }

    $query = \Drupal::entityQuery('node')
      ->condition('type', $this->getFormId())
      ->condition('user_reference_id', $id)
      ->accessCheck(FALSE);

    $class = 'form-col col-lg-3 col-md-6 col-sm-12';
    $fields = $this->migrationService->getallContentTypesFieldsDetails($class, $this->getFormId());
    $nids = $query->execute();

    if (!empty($nids)) {
      $nid = reset($nids);
      $node = Node::load($nid);

      if ($node) {
        foreach ($fields as $field_key => $field_value) {
          $field_key = $field_value['name'];
          if ($node->hasField($field_key)) {
            if (in_array($field_key, [
              'field_agri_equipment_photo',
              'field_cattle_photo',
              'field_land_document_7_12',
              'field_land_document_8a',
              'field_loan_documents',
              'field_income_certificate',
            ])) {
              $node->get($field_key)->value = $node->get($field_key)->target_id;
            }
            $form_state->setValue($field_key, $node->get($field_key)->value);
          }
        }
      }
    }

    $is_disabled = FALSE;
    if ($node && $node->hasField('unique_id') && !$node->get('unique_id')->isEmpty()) {
      $is_disabled = TRUE;
      if ($uniqueId) {
        $is_disabled = FALSE;
      }
    }

    $steps = $this->migrationService->getcontentTypeList();
    $step = $steps[$this->getFormId()];
    $form = $this->formDisplayConfigurationBycck($fields, $form_state, $step, $uniqueId);

    $wrapperfields = [
      'field_full_name',
      'field_siblings_unique_id',
      'how_many_acres',
      'annual_income_from_land',
      'irrigated_or_non_irigated',
      'crops_they_take',
      'field_land_document_7_12',
      'field_land_document_8a',
      'field_cattle_photo',
      'field_agri_equipment_photo',
      'field_loan_documents',
      'field_income_certificate',
    ];

    foreach ($wrapperfields as $field_name) {
      $class = $this->getFieldClass($field_name);
      if ($field_name == 'irrigated_or_non_irigated') {
        $form[$field_name]['#prefix'] = "<div class ='$class'>";
      } else {
        $form[$field_name]['#wrapper_attributes'] = ['class' => $class];
      }
    }

    $form['field_siblings_unique_id']['#suffix'] = '<div class="col-lg-12 col-sm-12"></div><div class="row g-3"><h5>Agricultural land details</h5>';
    $form['agricultural_equipment_they_have']['#suffix'] = '</div></div><h5 class="mb-3">Documents:</h5><div class="finance-document-section">';
    $form['annual_income']['#suffix'] = '<div class="col-lg-12 col-sm-12"></div>';

    $form['actions']['#type'] = 'actions';
    $form['actions']['#prefix'] = '</div>';
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#attributes' => ['class' => ['outline-purple', 'mt-4']],
      '#prefix' => '</div>',
    ];

    unset($form['unique_id']);
    unset($form['user_reference_id']);

    if ($is_disabled) {
      foreach ($form as $key => &$element) {
        if (is_array($element) && isset($element['#type']) && $element['#type'] !== 'submit') {
          $element['#disabled'] = TRUE;
        }
      }
      $form['actions']['next']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * Validates the form data for the Finance Details form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $validation_rules = $this->allinonejson();
    $form_fields = $form_state->getValues();

    foreach ($form_fields as $field_name => $field_value) {
      if (array_key_exists($field_name, $validation_rules)) {
        if (!empty($validation_rules[$field_name]['required']) && empty($field_value)) {
          $form_state->setErrorByName($field_name, $validation_rules['error_message']);
        }
        if (!empty($validation_rules[$field_name]['regex']) && !preg_match($validation_rules[$field_name]['regex'], $field_value)) {
          $form_state->setErrorByName($field_name, $validation_rules[$field_name]['error_message']);
        }
      }
    }
  }

  /**
   * Submits the form data and saves it to the Finance Details content type.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $data = $form_state->getValues();
    $uniqueId = $form_state->get('uniqueId');
    $current_user = \Drupal::currentUser();
    $id = $uniqueId ?? $current_user->id();

    $query = \Drupal::entityQuery('node')
      ->condition('type', $this->getFormId())
      ->condition('user_reference_id', $id)
      ->accessCheck(FALSE);

    $nids = $query->execute();
    $nid = reset($nids);

    if ($nid) {
      $node = Node::load($nid);
      if (!$node) {
        return;
      }
    } else {
      $node = Node::create(['type' => $this->getFormId()]);
      $node->set('title', $this->getFormId());
    }

    $fields = $this->migrationService->getallContentTypesFieldsDetails('', $this->getFormId());
    foreach ($fields as $field_key => $field_value) {
      $field_key = $field_value['name'];
      if (isset($data[$field_key])) {
        $Getfilefield = $this->file_fileds_array();
        if ($field_key == $Getfilefield[$field_key] && !empty($data[$Getfilefield[$field_key]])) {
          $document = File::load($data[$field_key][0]);
          $document->setPermanent();
          $document->save();
          $node->set($field_key, $data[$field_key]);
        }
        if (is_array($data[$field_key])) {
          $node->set($field_key, $data[$field_key]);
        } else {
          $node->set($field_key, trim($data[$field_key]));
        }
      }
    }

    $node->setOwnerId($id);
    $node->set('user_reference_id', $id);
    $node->save();

    \Drupal::messenger()->addMessage($this->t('Your data has been saved successfully.'));
    $form_state->setRedirect('scholarship_beneficiary_profile.step4', ['uniqueId' => $uniqueId]);
  }

  /**
   * Returns an array of file fields.
   *
   * @return array
   *   An array of file field names.
   */
  function file_fileds_array()
  {
    return [
      'field_agri_equipment_photo' => 'field_agri_equipment_photo',
      'field_cattle_photo' => 'field_cattle_photo',
      'field_land_document_7_12' => 'field_land_document_7_12',
      'field_land_document_8a' => 'field_land_document_8a',
      'field_loan_documents' => 'field_loan_documents',
      'field_income_certificate' => 'field_income_certificate',
    ];
  }

  /**
   * Determines the CSS class for a given field.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return string
   *   The CSS class for the field.
   */
  private function getFieldClass($field_name)
  {
    if (in_array($field_name, ['field_full_name', 'field_siblings_unique_id'])) {
      return 'form-col col-lg-3 col-md-6 col-sm-12 scholarship_avail_siblings';
    } elseif (in_array($field_name, ['how_many_acres', 'annual_income_from_land', 'crops_they_take'])) {
      return 'form-col col-lg-3 col-md-6 col-sm-12 own_agricultural_land';
    } elseif (in_array($field_name, [
      'field_land_document_7_12',
      'field_land_document_8a',
      'field_cattle_photo',
      'field_agri_equipment_photo',
      'field_loan_documents',
      'field_income_certificate',
    ])) {
      return 'col-lg-9 col-md-9 col-sm-12';
    } elseif ($field_name == 'irrigated_or_non_irigated') {
      return 'form-col col-lg-3 col-md-6 col-sm-12 own_agricultural_land';
    } else {
      return 'form-col col-lg-4 col-md-4 col-sm-12';
    }
  }
}
