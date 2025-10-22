<?php

namespace Drupal\votacao;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class VoteQuestionListBuilder extends EntityListBuilder {

  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Question');
    $header['uuid'] = $this->t('UUID');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\votacao\Entity\VoteQuestion $entity */
    $row['id'] = $entity->id();
    $row['label'] = $entity->label();
    $row['uuid'] = $entity->uuid();
    return $row + parent::buildRow($entity);
  }

}
