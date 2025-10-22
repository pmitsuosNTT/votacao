<?php

namespace Drupal\votacao\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Vote Answer entity.
 *
 * @ContentEntityType(
 *   id = "vote_answer",
 *   label = @Translation("Vote Answer"),
 *   label_collection = @Translation("Vote Answers"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
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
 *   base_table = "vote_answer",
 *   admin_permission = "administer voting",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "status" = "status"
 *   },
 *   links = {
 *     "canonical" = "/admin/content/votacao/answer/{vote_answer}",
 *     "add-form" = "/admin/content/votacao/answer/add",
 *     "edit-form" = "/admin/content/votacao/answer/{vote_answer}/edit",
 *     "delete-form" = "/admin/content/votacao/answer/{vote_answer}/delete",
 *     "collection" = "/admin/content/votacao/answers"
 *   }
 * )
 */
class VoteAnswer extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['question'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Question'))
    ->setRequired(TRUE)
    ->setSetting('target_type', 'vote_question')
    ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 0])
    ->setDisplayConfigurable('form', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
    ->setLabel(t('Title'))
    ->setRequired(TRUE)
    ->setSettings(['max_length' => 255])
    ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 10])
    ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
    ->setLabel(t('Description'))
    ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 20])
    ->setDisplayConfigurable('form', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('Weight'))
    ->setDefaultValue(0)
    ->setDisplayOptions('form', ['type' => 'number', 'weight' => 30])
    ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
    ->setLabel(t('Published'))
    ->setDefaultValue(TRUE)
    ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 40])
    ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
