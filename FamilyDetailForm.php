/**
 * Returns an array of supported file field machine names.
 *
 * @return array
 *   An associative array where the keys and values are field machine names.
 */
function file_fileds_array() {
  return [
    'field_aadhaar_card' => 'field_aadhaar_card',
    'field_photo_id' => 'field_photo_id',
    'field_caste_certificate' => 'field_caste_certificate',
    'field_ration_card' => 'field_ration_card',
  ];
}

/**
 * Allows modifications to the form display related to file fields.
 *
 * This method currently does nothing but returns the original form array.
 * You can extend it to dynamically alter file-related display properties.
 *
 * @param array $form
 *   The form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state object.
 *
 * @return array
 *   The (possibly altered) form array.
 */
public function updateFileDisplay(array &$form, FormStateInterface $form_state) {
  return $form;
}

/**
 * Generates preview markup for a file field after an AJAX callback.
 *
 * This function checks the file uploaded to a given field, loads the file,
 * and returns appropriate markup based on the file type (image, PDF, etc).
 *
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state object containing field values.
 * @param string $filed
 *   The machine name of the file field (though this argument is currently unused).
 *
 * @return string
 *   HTML markup for file preview or download link.
 */
public function showfile_after_ajax(FormStateInterface $form_state, $filed) {
  $field = 'field_aadhar_card'; // This could be dynamic based on $filed

  $preview_markup = '';

  if (!empty($form_state->getValue($field))) {
    $file = File::load($form_state->getValue($field)[0]);

    if ($f
