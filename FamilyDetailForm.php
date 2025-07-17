<?php

namespace Drupal\scholarship_beneficiary_profile\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\scholarship_beneficiary_profile\Service\MigrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides the form for the Family Details section of the scholarship beneficiary profile.
 *
 * @package Drupal\scholarship_beneficiary_profile\Form
 */
class FamilyDetailForm extends MultiStepFormBase
{
  /**
   * The MigrationService instance.
   *
   * @var \Drupal\scholarship_beneficiary_profile\Service\MigrationService
   */
  protected $migrationService;

  /**
   * Constructs a new FamilyDetailForm.
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
    return 'family_details';
  }

  /**
   * Builds the form for the Family Details section.
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
    $nids = 0;
    $form_state->set('uniqueId', $uniqueId);
    $current_user = \Drupal::currentUser();
    $id = $uniqueId ?? $current_user->id();

    if ($uniqueId && empty(array_intersect(['administrator'], $current_user->getRoles()))) {
      throw new AccessDeniedHttpException();
    }

    $form['#attached']['library'][] = 'scholarship_beneficiary_profile/family_details';
    $form['progress_bar'] = $this->buildProgressBar(5, 7, $uniqueId);

    $user = \Drupal\user\Entity\User::load($id);

    // Check if the user has a unique_id
    $has_unique_id = FALSE;
    if ($user && !$user->get('field_unique_id')->isEmpty()) {
      $has_unique_id = TRUE;
      if ($uniqueId) {
        $has_unique_id = FALSE;
      }
    }

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'family_details')
      ->condition('field_user_reference_id', $id)
      ->accessCheck(FALSE);

    // Execute the query to get node IDs.
    $nids = $query->execute();

    $form['nid'] = [
      '#type' => 'hidden',
      '#default_value' => $nids,
      '#attributes' => [
        'id' => 'nid',
      ],
    ];

    $form['pid'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'pid',
      ],
    ];

    // Add form fields for family details.
    $form['name_of_family_member'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of the family member'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['family_details-field']],
    ];

    $form['year_of_birth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Year of birth'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['family_details-field']],
    ];

    $form['relationship_with_student'] = [
      '#type' => 'select',
      '#title' => $this->t('Relationship with student'),
      '#options' => [
        'father' => $this->t('Father'),
        'mother' => $this->t('Mother'),
        'brother' => $this->t('Brother'),
        'sister' => $this->t('Sister'),
        'grandfather' => $this->t('Grandfather'),
        'grandmother' => $this->t('Grandmother'),
        'uncle' => $this->t('Uncle'),
        'aunty' => $this->t('Aunty'),
        'cousin' => $this->t('Cousin'),
        'guardian' => $this->t('Guardian'),
        'husband' => $this->t('Husband'),
        'wife' => $this->t('Wife'),
      ],
      '#required' => TRUE,
      '#attributes' => ['class' => ['family_details-field']],
    ];

    $form['educatiional_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Educational status'),
      '#options' => [
        'inprogress' => $this->t('Inprogress'),
        'illiterate' => $this->t('Illiterate'),
        'non-matric' => $this->t('Non-Matric'),
        'matric' => $this->t('Matric'),
        'twelfth' => $this->t('Twelfth'),
        'graduate' => $this->t('Graduate'),
        'post-graduate' => $this->t('Post-graduate'),
      ],
      '#required' => TRUE,
      '#attributes' => ['class' => ['family_details-field']],
    ];

    $form['occupation'] = [
      '#type' => 'select',
      '#title' => $this->t('Occupation'),
      '#options' => [
        'service' => $this->t('Service'),
        'business' => $this->t('Business'),
        'farming' => $this->t('Farming'),
        'daily_Wages' => $this->t('Daily Wages'),
        'student' => $this->t('Student'),
        'other' => $this->t('Other'),
      ],
      '#required' => TRUE,
      '#attributes' => ['class' => ['family_details-field']],
    ];

    $form['occupation_details'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Occupation details'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['family_details-field']],
    ];

    $form['monthly_income'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Monthly income'),
      '#required' => TRUE,
      '#min' => 0,
      '#max' => 5000000,
      '#description' => $this->t('Please enter a number between 0 and 50,00,000.'),
      '#attributes' => [
        'class' => ['family_details-field'],
        'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "");',
        'minlength' => 1,
        'maxlength' => 7,
      ],
    ];

    $form['health_problem'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Any illness / health problem details'),
      '#attributes' => ['class' => ['family_details-field']],
    ];

    $form['medical_expenses'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Medical expenses'),
      '#min' => 0,
      '#max' => 500000,
      '#description' => $this->t('Please enter a number between 0 and 5,00,000.'),
      '#attributes' => [
        'class' => ['family_details-field'],
        'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "");',
        'minlength' => 1,
        'maxlength' => 6,
      ],
    ];

    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add member'),
      '#attributes' => ['class' => ['outline-purple', 'mt-4']],
    ];

    $form['cancel_btn'] = [
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#attributes' => [
        'class' => ['outline-purple', 'mt-4'],
        'style' => 'display : none',
      ],
    ];

    if ($has_unique_id) {
      foreach ($form as $key => &$element) {
        if (is_array($element) && isset($element['#type']) && $element['#type'] !== 'hidden') {
          $element['#disabled'] = TRUE;
        }
      }
      if (isset($form['actions']['next'])) {
        $form['actions']['next']['#disabled'] = TRUE;
      }
    }

    return $form;
  }

  /**
   * Validates the form data for the Family Details form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $data['name_of_family_member'] = $form_state->getValue('name_of_family_member');
    $data['occupation_details'] = $form_state->getValue('occupation_details');
    $data['health_problem'] = $form_state->getValue('health_problem');
    $year = $form_state->getValue('year_of_birth');

    foreach ($data as $field_name => $field_value) {
      if (preg_match('/\d/', $field_value)) {
        $form_state->setErrorByName($field_name, $this->t('Numbers are not allowed.'));
      }
    }

    if (!is_numeric($year) || strlen($year) !== 4 || (int) $year < 1900 || (int) $year > (int) date('Y')) {
      $form_state->setErrorByName('year_of_birth', $this->t('Please enter a year between 1900 & the current year.'));
    }
  }

  /**
   * Submits the Family Details form data.
   *
   * Saves or updates the family details node and its associated paragraphs.
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

    if (!empty($data['nid'])) {
      $node = Node::load($data['nid']);
      if ($node) {
        if (!empty($data['pid'])) {
          $paragraph = Paragraph::load($data['pid']);
          if (isset($paragraph) && !empty($paragraph)) {
            $paragraph->set('field_name_of_the_family_member', $data['name_of_family_member']);
            $paragraph->set('field_year_of_birth', $data['year_of_birth']);
            $paragraph->set('field_relationship_with_student', $data['relationship_with_student']);
            $paragraph->set('field_educational_status', $data['educatiional_status']);
            $paragraph->set('field_occupation', $data['occupation']);
            $paragraph->set('field_occupation_details', $data['occupation_details']);
            $paragraph->set('field_monthly_income', $data['monthly_income']);
            $paragraph->set('field_any_illness_health_problem', $data['health_problem']);
            $paragraph->set('field_medical_expenses', $data['medical_expenses']);
            $paragraph->save();
            \Drupal::messenger()->addMessage($this->t('Paragraph updated successfully.'));
          } else {
            \Drupal::messenger()->addMessage($this->t('Paragraph not found.'), 'error');
          }
        } else {
          $paragraph = $this->paragraph_create($data);
          $node->get('field_family_members')->appendItem($paragraph);
          $node->setOwnerId($id);
          $node->save();
          \Drupal::messenger()->addMessage($this->t('Family members saved successfully.'));
        }
      }
    } else {
      $paragraph = $this->paragraph_create($data);
      $node = Node::create([
        'type' => 'family_details',
        'title' => 'Family member details',
        'field_family_members' => $paragraph,
        'field_user_reference_id' => $id,
      ]);
      $node->setOwnerId($id);
      $node->save();
      \Drupal::messenger()->addMessage($this->t('Your data has been saved successfully.'));
    }
  }

  /**
   * Creates a new paragraph for family member details.
   *
   * @param array $data
   *   The data for the paragraph fields.
   *
   * @return \Drupal\paragraphs\Entity\Paragraph
   *   The created paragraph entity.
   */
  public function paragraph_create($data)
  {
    $paragraph = Paragraph::create([
      'type' => 'family_members',
      'field_name_of_the_family_member' => $data['name_of_family_member'],
      'field_year_of_birth' => $data['year_of_birth'],
      'field_relationship_with_student' => $data['relationship_with_student'],
      'field_educational_status' => $data['educatiional_status'],
      'field_occupation' => $data['occupation'],
      'field_occupation_details' => $data['occupation_details'],
      'field_monthly_income' => $data['monthly_income'],
      'field_any_illness_health_problem' => $data['health_problem'],
      'field_medical_expenses' => $data['medical_expenses'],
    ]);
    $paragraph->save();
    return $paragraph;
  }
}

