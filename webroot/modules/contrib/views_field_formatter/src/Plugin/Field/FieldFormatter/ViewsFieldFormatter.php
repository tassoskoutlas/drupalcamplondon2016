<?php /**
 * @file
 * Contains \Drupal\views_field_formatter\Plugin\Field\FieldFormatter\ViewsFieldFormatter.
 */

namespace Drupal\views_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldFormatter(
 *  id = "views_field_formatter",
 *  label = @Translation("View"),
 *  field_types = {"comment", "datetime", "file", "image", "list_float", "list_integer", "list_string", "path", "taxonomy_term_reference", "text", "text_long", "text_with_summary", "boolean", "changed", "created", "decimal", "email", "entity_reference", "float", "integer", "language", "map", "string", "string_long", "timestamp", "uri", "uuid"}
 * )
 */
class ViewsFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'view' => '',
      'multiple' => FALSE,
      'implode_character' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = array();

    $views =  \Drupal\views\Views::getAllViews();
    $options = array();
    foreach ($views as $view) {
      foreach ($view->get('display') as $display) {
        $options[$view->get('label')][$view->get('id') . '::' . $display['id']] = $display['display_title'];
      }
    }

    if (!empty($options)) {
      $element['view'] = array(
        '#title' => t('View'),
        '#description' => t('Select the view that will be used to get the value of the field.'),
        '#type' => 'select',
        '#default_value' => $this->getSetting('view'),
        '#options' => $options,
      );
      $element['multiple'] = array(
        '#title' => t('Multiple'),
        '#description' => t('If the field is configured as multiple, should we display a view per item ? If selected, there will be one view per item. The arguments passed to that view are in this order: the field item value, the entity id and the item delta.'),
        '#type' => 'checkbox',
        '#default_value' => boolval($this->getSetting('multiple')),
      );
      $element['implode_character'] = array(
        '#title' => t('Implode with this character'),
        '#description' => t('If it is set, all field values are imploded with this character and sent as one views argument. Empty to disable.'),
        '#type' => 'textfield',
        '#default_value' => $this->getSetting('implode_character'),
        '#states' => array(
          'visible' => array(
            ':input[name="fields[body][settings_edit_form][settings][multiple]"]' => array('checked' => TRUE),
          ),
        ),
      );
    } else {
      $element['help'] = array(
        '#markup' => t('<p>No available Views were found.</p>'),
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $settings = $this->getSettings();
    list($view, $view_display) = explode('::', $settings['view']);
    $multiple = ((bool) $settings['multiple'] === TRUE) ? 'Enabled' : 'Disabled';

    if (isset($view)) {
      $summary[] = t('View: @view', array('@view' => $view));
      $summary[] = t('Display: @display', array('@display' => $view_display));
      $summary[] = t('Multiple: @multiple', array('@multiple' => t($multiple)));
    }

    if ($multiple == 'Enabled') {
      if (!empty($settings['implode_character'])) {
        $summary[] = t('Implode character: @character', array('@character' => $settings['implode_character']));
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $settings = $this->getSettings();
    list($view, $view_display) = explode('::', $settings['view'], 2);

    $columns = array_keys($items->getFieldDefinition()->getFieldStorageDefinition()->getSchema()['columns']);
    $column = array_shift($columns);

    $id = $items->getParent()->getValue()->id();
    $cardinality = $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();

    if ( ((bool) $settings['multiple'] === TRUE) && ($cardinality != 1)) {
      if (!empty($settings['implode_character'])) {
        $values = array();
        foreach ($items as $item) {
          $values[] = isset($item->getValue()[$column]) ? $item->getValue()[$column] : NULL;
        }
        $value = implode($settings['implode_character'], array_filter($values));
        $elements[0] = [
          '#type' => 'view',
          '#name' => $view,
          '#display_id' => $view_display,
          '#arguments' => [$value, $id, 0],
        ];
      } else {
        foreach ($items as $delta => $item) {
          $value = isset($item->getValue()[$column]) ? $item->getValue()[$column] : NULL;
          $elements[$delta] = [
            '#type' => 'view',
            '#name' => $view,
            '#display_id' => $view_display,
            '#arguments' => [$value, $id, $delta],
          ];
        }
      }
    } else {
      $item = $items[0];
      $delta = 0;
      $value = isset($item->getValue()[$column]) ? $item->getValue()[$column] : NULL;
      $elements[$delta] = [
        '#type' => 'view',
        '#name' => $view,
        '#display_id' => $view_display,
        '#arguments' => [$value, $id, $delta],
      ];
    }

    return $elements;
  }
}
