<?php

namespace Drupal\votacao\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Vote (a user's choice for a question).
 *
 * @ContentEntityType(
 *   id = "vote_vote",
 *   label = @Translation("Vote"),
 *   label_collection = @Translation("Votes"),
 *   handlers = {
 *     "list_builder" = "Drupal\votacao\VoteVoteListBuilder",
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
 *   base_table = "vote_vote",
 *   admin_permission = "administer voting",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/content/votacao/vote/{vote_vote}",
 *     "add-form" = "/admin/content/votacao/vote/add",
 *     "edit-form" = "/admin/content/votacao/vote/{vote_vote}/edit",
 *     "delete-form" = "/admin/content/votacao/vote/{vote_vote}/delete",
 *     "collection" = "/admin/content/votacao/votes"
 *   }
 * )
 */
class VoteVote extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['question'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Question'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'vote_question');

    $fields['answer'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Answer'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'vote_answer');

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }

}
