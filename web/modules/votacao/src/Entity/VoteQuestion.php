<?php

namespace Drupal\votacao\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Vote Question entity.
 *
 * @ContentEntityType(
 *   id = "vote_question",
 *   label = @Translation("Vote Question"),
 *   label_collection = @Translation("Vote Questions"),
 *   handlers = {
 *     "list_builder" = "Drupal\votacao\VoteQuestionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "vote_question",
 *   admin_permission = "administer voting",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   links = {
 *     "canonical" = "/admin/content/votacao/question/{vote_question}",
 *     "add-form" = "/admin/content/votacao/question/add",
 *     "edit-form" = "/admin/content/votacao/question/{vote_question}/edit",
 *     "delete-form" = "/admin/content/votacao/question/{vote_question}/delete",
 *     "collection" = "/admin/content/votacao/questions"
 *   }
 * )
 */
class VoteQuestion extends ContentEntityBase {

  /**
   * Base field definitions.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
    ->setLabel(t('Question'))
    ->setRequired(TRUE)
    ->setSettings(['max_length' => 255])
    ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 0])
    ->setDisplayConfigurable('form', TRUE);

    $fields['is_open'] = BaseFieldDefinition::create('boolean')
    ->setLabel(t('Open for voting'))
    ->setDefaultValue(TRUE)
    ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 10])
    ->setDisplayConfigurable('form', TRUE);

    $fields['show_results'] = BaseFieldDefinition::create('boolean')
    ->setLabel(t('Show results after vote'))
    ->setDefaultValue(FALSE)
    ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 20])
    ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
    ->setLabel(t('Published'))
    ->setDefaultValue(TRUE)
    ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 30])
    ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
