<?php

namespace Drupal\votacao;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class VoteVoteListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    return [
      'id'       => $this->t('ID'),
      'question' => $this->t('Question'),
      'answer'   => $this->t('Answer'),
      'user'     => $this->t('User'),
      'created'  => $this->t('Created'),
    ] + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\votacao\Entity\VoteVote $entity */
    $q = $entity->get('question')->entity;
    $a = $entity->get('answer')->entity;
    $u = $entity->get('uid')->entity;

    $created = (int) ($entity->get('created')->value ?? 0);

    return [
      'id'       => $entity->id(),
      'question' => $q ? $q->label() : $this->t('-'),
      'answer'   => $a ? $a->label() : $this->t('-'),
      'user'     => $u ? $u->getDisplayName() : $this->t('Anonymous'),
      'created'  => $created ? \Drupal::service('date.formatter')->format($created, 'short') : $this->t('-'),
    ] + parent::buildRow($entity);
  }
}
