<?php

namespace Drupal\scholarship_beneficiary_profile\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\scholarship_beneficiary_profile\Service\MigrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Form controller for the Academic Details form.
 *
 * @package Drupal\scholarship_beneficiary_profile\Form
 */
class AcademicDetailForm extends MultiStepFormBase
{
  /**
   * The MigrationService instance.
   *
   * @var \Drupal\scholarship_beneficiary_profile\Service\MigrationService
   */
  protected $migrationService;

  /**
   * Constructs a new AcademicDetailForm.
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
    return 'academic_details';
  }

  /**
   * Builds the form for the Academic Details section.
   *
   * @param array $form
   *   The existing form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uniqueId = null)
  {

    $form_state->set('uniqueId', $uniqueId);
    $current_user = \Drupal::currentUser();
    $id = $uniqueId ?? $current_user->id(); 
    if ($uniqueId && empty(array_intersect(['administrator'],$current_user->getRoles()))) {
        throw new AccessDeniedHttpException();
    }  
    $query = \Drupal::entityQuery('node')->condition('type', $this->getFormId())->condition('user_reference_id', $id)->accessCheck(FALSE);
    $class = 'form-col col-lg-3 col-md-6 col-sm-12';
    // relationship with student
    $fields = $this->migrationService->getallContentTypesFieldsDetails($class, $this->getFormId());
    // Execute the query to get node IDs.
    $nids = $query->execute();
    if (!empty($nids)) {
      $nid = reset($nids);  // Get the first node ID.
      $node = Node::load($nid);  // Load the node entity.
      if ($node) {
        foreach ($fields as $field_key => $field_value) {
          $field_key = $field_value['name'];
          if ($node->hasField($field_key)) {
            if ($field_key == 'field_10th_marksheet' || $field_key == 'field_11th_marksheet' || $field_key == 'field_12th_marksheet' || $field_key == 'field_cet' || $field_key == 'field_degree_marksheet' || $field_key == 'field_diploma_marksheet' || $field_key == 'field_jee' || $field_key == 'field_neet' || $field_key == 'field_school_leaving_certificate' || $field_key == 'field_course_applied_for') {
              $node->get($field_key)->value = $node->get($field_key)->target_id;
              if ($field_key == 'field_stream' || $field_key == 'field_course_applied_for') {
                $node->get($field_key)->value = $node->get($field_key)->target_id;
                $fields_map = ['field_stream' => 'streamID', 'field_course_applied_for' => 'courseID'];
                foreach ($fields_map as $field_key_name => $var_name) {
                  if ($node->hasField($field_key_name) && !$node->get($field_key)->isEmpty()) {
                    $$var_name = $node->get($field_key_name)->target_id;
                  }
                }
              }
            }
            $form_state->setValue($field_key, $node->get($field_key)->value);
          }
        }
      }
    }

    $is_disabled = FALSE;
    if ($node && $node->hasField('unique_id') && !$node->get('unique_id')->isEmpty()) {
      $is_disabled = TRUE;
      if($uniqueId){
        $is_disabled = FALSE;
      }  
    }

    $steps = $this->migrationService->getcontentTypeList();
    $step = $steps[$this->getFormId()];
    // Generate the form fields dynamically.
    $form = $this->formDisplayConfigurationBycck($fields, $form_state, $step, $uniqueId);
    $courses =  $this->getTerms('course');

    $form['field_course_applied_for'] = [
      '#type' => 'select',
      '#title' => $this->t('Course'),
      '#options' =>  $courses ?? ($form_state->getUserInput()['field_course_applied_for'] ?? NULL),
      '#default_value' => $courseID,
      '#required' => TRUE,
      '#empty_option' => '- Select -',
      '#ajax' => [
        'callback' => '::updateStream',
        'wrapper' => 'stream-wrapper-2',
      ],
      '#wrapper_attributes' => ['class' => 'form-col col-lg-3 col-md-3 col-sm-12'],
    ];

    if ($courseID) {
      $options = $this->getTaxonomyOptions($courseID, 'field_reference_course', $form, $form_state, 'field_course_applied_for', 'stream');
    } else {
      $options = $this->getTaxonomyOptions('', 'field_reference_course', $form, $form_state, 'field_course_applied_for', 'stream');
    }

    $form['field_stream'] = [
      '#type' => 'select',
      '#title' => $this->t('Stream'),
      '#id' => 'edit-field-stream',
      '#options' => $options,
      '#empty_option' => $this->t('- Select -'),
      '#required' => TRUE,
      '#prefix' => '<div id="stream-wrapper-2" class="form-col col-lg-3 col-md-3 col-sm-12">',
      '#suffix' => '</div>',
      '#default_value' => $streamID,
      '#validated' => TRUE,
      '#wrapper_attributes' => ['class' => 'col-sm-12 col-lg-12 col-md-12'],
      '#attributes' => ['class' => ['academic_details-field']],
    ];

    $wrapperfields = [
      'field_10th_marksheet',
      'field_11th_marksheet',
      'field_12th_marksheet',
      'field_diploma_marksheet',
      'field_neet',
      'field_jee',
      'field_cet',
      'field_degree_marksheet',
      'field_school_leaving_certificate'
    ];

    foreach ($wrapperfields as $field_name) {
      if ($field_name == 'field_10th_marksheet' || $field_name == 'field_10th_marksheet' || $field_name == 'field_10th_marksheet' || $field_name == 'field_10th_marksheet' || $field_name == 'field_10th_marksheet' || $field_name == 'field_10th_marksheet' || $field_name == 'field_10th_marksheet') {
        $class = 'col-lg-9 col-md-9 col-sm-12';
      }

      $form[$field_name]['#wrapper_attributes'] = ['class' => $class];
    }

    // Prefix and suffix for specific fields
    $form['field_course_applied_for']['#prefix'] = '<h5 class="form-error-message_note"><div class="academic-note"><p class="title">Notes:</p><br><div class="wrapper-note"><p class="description">1. Fields marked in * are required.</p><p class="dubble_estrick_icon_note" style="">2. ** Any of “11th percentage, 11th passing year and 12th percentage, 12th passing year” or “Diploma percentage, Diploma passing year” must be filled.</p></div></div></h5><div class="row g-3">';
    $form['name_of_college']['#suffix'] = '</div>';
    $form['field_college_name']['#prefix'] = '<div class="row g-3">';
    $form['field_college_name']['#suffix'] = '</div>';
    $form['tenth_percentage']['#prefix'] = '<div class="row g-3">';
    $form['eleventh_passing_year']['#suffix'] = '</div>';
    $form['twelth_percentage']['#prefix'] = '<div class="row g-3">';
    $form['diploma_passing_year']['#suffix'] = '</div>';
    $form['field_graduation_percentage']['#prefix'] = '<div class="row g-3">';
    $form['field_graduation_passing_year']['#suffix'] = '</div>';
    $form['total_marks_in_cet']['#prefix'] = '<div class="row g-3">';
    $form['field_jee_passing_year']['#suffix'] = '</div>';
    $form['field_neet_percentage']['#prefix'] = '<div class="row g-3">';
    $form['field_neet_passing_year']['#suffix'] = '</div>';
    $form['field_any_other_course_name']['#prefix'] = '<div class="row g-3">';
    $form['field_other_course_passing_year']['#suffix'] = '</div>';
    $form['fee_pattern']['#suffix'] = '<h5 class="mb-3">Documents:</h5><div class="finance-document-section academic-document-section">';
    $form['actions']['#type'] = 'actions';
   

    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#attributes' => ['class' => ['outline-purple', 'mt-4', 'academic_save_custom']],
      '#prefix' => '</div>'
    ];

     $form['actions']['action_next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#attributes' => ['class' => ['outline-purple', 'mt-4', 'academic_save'],'style' => 'display:none;'],
    ];
    

    unset($form['unique_id']);
    unset($form['user_reference_id']);
    if ($is_disabled) {
      foreach ($form as $key => &$element) {
        if (is_array($element) && isset($element['#type']) && $element['#type'] !== 'submit') {
          $element['#disabled'] = TRUE;
        }
      }
      // Also disable the submit button to prevent form submission
      $form['field_course_applied_for']['#disabled'] = TRUE;
      $form['field_course_applied_for']['#attributes']['disabled'] = 'disabled';
      unset($form['field_course_applied_for']['#ajax']);
    }
    return $form;
  }

  /**
   * Validates the form data for the personal details form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $field_validation = $this->allinonejson();
    $form_fields = $form_state->getValues();
    $validation_fields_array = [
        'tenth_percentage',
        'tenth_passing_year',
        'eleventh_percentage',
        'eleventh_passing_year',
        'twelth_percentage',
        'twelth_passing_year',
        'diploma_percentage',
        'diploma_passing_year',
        'field_graduation_percentage',
        'field_graduation_passing_year',
        'total_marks_in_cet',
        'cet_year',
        'field_jee_percentage',
        'field_jee_passing_year',
        'field_neet_percentage',
        'field_neet_passing_year',
        'alternate_course_name',
        'field_other_course_percentage',
        'field_other_course_passing_year',
        'field_10th_marksheet',
        'field_11th_marksheet',
        'field_12th_marksheet',
        'field_diploma_marksheet',
        'field_cet',
        'field_jee',
        'field_neet',
        'field_degree_marksheet'
    ];

    if ($this->getTermsById($form_fields['field_course_applied_for']) == "Diploma") {
        $data[] = 'tenth_percentage, tenth_passing_year, field_10th_marksheet';
        $merged = [];
        foreach ($data as $item) {
            $fields = array_map('trim', explode(',', $item));
            $merged = array_merge($merged, $fields);
        }
        $diploma = array_unique($merged);
        foreach ($validation_fields_array as $field_machine_name) {
            if (!in_array($field_machine_name, $diploma)) {
                unset($form_fields[$field_machine_name]);
            }
        }
    }

    if ($this->getTermsById($form_fields['field_course_applied_for']) == "Engineering") {
        $data[] = 'tenth_percentage, tenth_passing_year, field_10th_marksheet';
        if (!empty($form_fields['eleventh_percentage']) || !empty($form_fields['twelth_percentage'])) {
            $data[] = 'eleventh_percentage, eleventh_passing_year,field_11th_marksheet, twelth_percentage, twelth_passing_year,field_12th_marksheet';
        } else if (!empty($form_fields['diploma_percentage'])) {
            $data[] = "diploma_percentage, diploma_passing_year, field_diploma_marksheet";
        } else {
            $data[] = 'eleventh_percentage, eleventh_passing_year,field_11th_marksheet, twelth_percentage, twelth_passing_year,field_12th_marksheet,diploma_percentage, diploma_passing_year, field_diploma_marksheet';
        }

        if (empty($form_fields['diploma_percentage'])) {
            if (!empty($form_fields['total_marks_in_cet'])) {
                $data[] = 'total_marks_in_cet, cet_year, field_cet';
            } else if (!empty($form_fields['field_jee_percentage'])) {
                $data[] = "field_jee_percentage, field_jee_passing_year, field_jee";
            } else {
                $data[] = "total_marks_in_cet, cet_year, field_jee_percentage, field_jee_passing_year, field_cet, field_jee";
            }
        }

        $merged = [];
        foreach ($data as $item) {
            $fields = array_map('trim', explode(',', $item));
            $merged = array_merge($merged, $fields);
        }
        $engineering = array_unique($merged);

        foreach ($validation_fields_array as $field_machine_name) {
            if (!in_array($field_machine_name, $engineering)) {
                unset($form_fields[$field_machine_name]);
            }
        }
    }

    if ($this->getTermsById($form_fields['field_course_applied_for']) == "Graduation") {
        $data[] = 'tenth_percentage, tenth_passing_year, field_10th_marksheet, eleventh_percentage, eleventh_passing_year,field_11th_marksheet, twelth_percentage, twelth_passing_year,field_12th_marksheet';
        $merged = [];
        foreach ($data as $item) {
            $fields = array_map('trim', explode(',', $item));
            $merged = array_merge($merged, $fields);
        }
        $graduation = array_unique($merged);
        foreach ($validation_fields_array as $field_machine_name) {
            if (!in_array($field_machine_name, $graduation)) {
                unset($form_fields[$field_machine_name]);
            }
        }
    }

    if ($this->getTermsById($form_fields['field_course_applied_for']) == "Master") {
        $data[] = 'tenth_percentage, tenth_passing_year, field_10th_marksheet, eleventh_percentage, eleventh_passing_year,field_11th_marksheet, twelth_percentage, twelth_passing_year,field_12th_marksheet,field_graduation_percentage,field_graduation_passing_year,field_degree_marksheet';
        $merged = [];
        foreach ($data as $item) {
            $fields = array_map('trim', explode(',', $item));
            $merged = array_merge($merged, $fields);
        }
        $master = array_unique($merged);
        foreach ($validation_fields_array as $field_machine_name) {
            if (!in_array($field_machine_name, $master)) {
                unset($form_fields[$field_machine_name]);
            }
        }
    }

    if ($this->getTermsById($form_fields['field_course_applied_for']) == "Medicine") {
        $data[] = 'tenth_percentage, tenth_passing_year, field_10th_marksheet, eleventh_percentage, eleventh_passing_year, field_11th_marksheet, twelth_percentage, twelth_passing_year, field_12th_marksheet,
        field_neet_percentage, field_neet_passing_year, field_neet';

        if ($this->getTermsById($form_fields['field_stream']) == "Bachelor of Paramedical Technology") {
            if (!empty($form_fields['total_marks_in_cet'])) {
                $data[] = 'total_marks_in_cet, cet_year, field_cet';
            } else if (!empty($form_fields['field_jee_percentage'])) {
                $data[] = "field_jee_percentage, field_jee_passing_year, field_jee";
            } else {
                $data[] = "total_marks_in_cet, cet_year, field_jee_percentage, field_jee_passing_year, field_cet, field_jee";
            }
        }

        $merged = [];
        foreach ($data as $item) {
            $fields = array_map('trim', explode(',', $item));
            $merged = array_merge($merged, $fields);
        }
        $nursing = array_unique($merged);
        foreach ($validation_fields_array as $field_machine_name) {
            if (!in_array($field_machine_name, $nursing)) {
                unset($form_fields[$field_machine_name]);
            }
        }
    }

    if ($this->getTermsById($form_fields['field_course_applied_for']) == "Nursing") {
        $data[] = 'tenth_percentage, tenth_passing_year, field_10th_marksheet';
        if ($this->getTermsById($form_fields['field_stream']) == "Nursing - BSc Nursing") {
            $data[] = 'eleventh_percentage, eleventh_passing_year,field_11th_marksheet, twelth_percentage, twelth_passing_year,field_12th_marksheet';
        } else if ($this->getTermsById($form_fields['field_stream']) == "Nursing - MSc") {
            $data[] = 'eleventh_percentage, eleventh_passing_year,field_11th_marksheet, twelth_percentage, twelth_passing_year,field_12th_marksheet,field_graduation_percentage,field_graduation_passing_year,field_degree_marksheet';
        } else if ($this->getTermsById($form_fields['field_stream']) == "Nursing - PBBSc Nursing") {
            $data[] = "diploma_percentage, diploma_passing_year, field_diploma_marksheet";
        }

        $merged = [];
        foreach ($data as $item) {
            $fields = array_map('trim', explode(',', $item));
            $merged = array_merge($merged, $fields);
        }
        $nursing = array_unique($merged);
        foreach ($validation_fields_array as $field_machine_name) {
            if (!in_array($field_machine_name, $nursing)) {
                unset($form_fields[$field_machine_name]);
            }
        }
    }

    if ($this->getTermsById($form_fields['field_course_applied_for']) == "Other") {
        $data[] = 'tenth_percentage, tenth_passing_year, field_10th_marksheet';
        if ($this->getTermsById($form_fields['field_stream']) == "CDAC") {
            $data[] = 'field_graduation_percentage,field_graduation_passing_year,field_degree_marksheet';
            if (!empty($form_fields['eleventh_percentage']) && !empty($form_fields['twelth_percentage'])) {
                $data[] = 'eleventh_percentage, eleventh_passing_year,field_11th_marksheet, twelth_percentage, twelth_passing_year,field_12th_marksheet';
            } else if (!empty($form_fields['diploma_percentage'])) {
                $data[] = "diploma_percentage, diploma_passing_year, field_diploma_marksheet";
            } else {
                $data[] = 'eleventh_percentage, eleventh_passing_year,field_11th_marksheet, twelth_percentage, twelth_passing_year,field_12th_marksheet,diploma_percentage, diploma_passing_year, field_diploma_marksheet';
            }
        } elseif ($this->getTermsById($form_fields['field_stream']) == 'Chartered Accountant' || $this->getTermsById($form_fields['field_stream']) == 'Company Secretary' || $this->getTermsById($form_fields['field_stream']) == 'Financial Times Stock Exchange') {
            $data[] = 'eleventh_percentage, eleventh_passing_year,field_11th_marksheet, twelth_percentage, twelth_passing_year,field_12th_marksheet';
        } else if ($this->getTermsById($form_fields['field_stream']) == "PGDCA") {
            $data[] = 'eleventh_percentage, eleventh_passing_year,field_11th_marksheet, twelth_percentage, twelth_passing_year,field_12th_marksheet,field_graduation_percentage,field_graduation_passing_year,field_degree_marksheet';
        }

        $merged = [];
        foreach ($data as $item) {
            $fields = array_map('trim', explode(',', $item));
            $merged = array_merge($merged, $fields);
        }
        $otherCourse = array_unique($merged);

        foreach ($validation_fields_array as $field_machine_name) {
            if (!in_array($field_machine_name, $otherCourse)) {
                unset($form_fields[$field_machine_name]);
            }
        }
    }

    foreach ($form_fields as $field_name => $field_value) {
        if (array_key_exists($field_name, $field_validation)) {
            if (!empty($field_validation[$field_name]['required']) && empty($field_value)) {
                $form_state->setErrorByName($field_name, $field_validation[$field_name]['error_message']);
            }

            if (is_array($field_value)) {
                $field_value = $field_value[0];
            }

            if (!empty($field_validation[$field_name]['regex']) && !preg_match($field_validation[$field_name]['regex'], $field_value)) {
                $form_state->setErrorByName($field_name, $field_validation[$field_name]['error_message']);
            }

            if ($field_name == 'name_of_college' && $field_value === 'other') {
                $field_college_name = trim($form_state->getValue('field_college_name'));
                if (empty($field_college_name)) {
                    $form_state->setErrorByName('field_college_name', 'Please enter college name');
                }
            }
        }
    }
  }

  /**
   * Submits the form data and saves it to the Academic Details content type.
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
    $query = \Drupal::entityQuery('node')->condition('type', $this->getFormId())->condition('user_reference_id', $id)->accessCheck(FALSE);
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
    $form_state->setRedirect('scholarship_beneficiary_profile.step3', ['uniqueId' => $uniqueId]);
  }
  
  /**
   * Returns an array of file field machine names.
   *
   * @return array
   *   The file fields.
   *
   * @author Vaibhav Bargal
   */
  function file_fileds_array()
  {
    return [
      'field_10th_marksheet' => 'field_10th_marksheet',
      'field_11th_marksheet' => 'field_11th_marksheet',
      'field_12th_marksheet' => 'field_12th_marksheet',
      'field_cet' => 'field_cet',
      'field_degree_marksheet' => 'field_degree_marksheet',
      'field_diploma_marksheet' => 'field_diploma_marksheet',
      'field_neet' => 'field_neet',
      'field_school_leaving_certificate' => 'field_school_leaving_certificate'
    ];
  }

  /**
   * Loads the term name based on the term ID.
   *
   * @param int $id
   *   The term ID.
   *
   * @return string|null
   *   The term name or NULL.
   *
   * @author Vaibhav Bargal
   */
  public function getTermsById($id)
  {
    $term = Term::load($id);
    if ($term) {
      return $term->getName();
    }
  }
}
