<?php

namespace Drupal\votacao\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\votacao\Entity\VoteQuestion;
use Symfony\Component\DependencyInjection\ContainerInterface;

class VoteCastForm extends FormBase {

  protected EntityTypeManagerInterface $etm;

  public function __construct(EntityTypeManagerInterface $etm) {
    $this->etm = $etm;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  public function getFormId(): string {
    return 'votacao_vote_cast_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, VoteQuestion $vote_question = NULL): array {
    // check cofnig
    if (!\Drupal::config('votacao.settings')->get('voting_enabled')) {
      $form['message'] = ['#markup' => $this->t('Voting is disabled.')];
      return $form;
    }

    if (!$vote_question) {
      $this->messenger()->addError($this->t('Question not found.'));
      return $form;
    }

    if (!$vote_question->get('status')->value) {
      $form['message'] = ['#markup' => $this->t('This question is unpublished.')];
      return $form;
    }

    $uid = (int) $this->currentUser()->id();
    // dd('$uid')
    $voted = !empty($this->etm->getStorage('vote_vote')
      ->getQuery()
      ->condition('question', $vote_question->id())
      ->condition('uid', $uid)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute());

    // Load published answers
    $answer_ids = $this->etm->getStorage('vote_answer')
      ->getQuery()
      ->condition('question', $vote_question->id())
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->sort('weight', 'ASC')
      ->execute();

    if (empty($answer_ids)) {
      $form['message'] = ['#markup' => $this->t('No answers available for this question.')];
      return $form;
    }

    $answers = $this->etm->getStorage('vote_answer')->loadMultiple($answer_ids);
    $options = [];
    foreach ($answers as $a) {
      $options[$a->id()] = $a->label();
    }

    $form['question'] = [
      '#type' => 'item',
      '#title' => $this->t('Question'),
      '#markup' => $vote_question->label(),
    ];

    if ($vote_question->get('is_open')->value && !$voted) {
      $form['answer'] = [
        '#type' => 'radios',
        '#title' => $this->t('Choose one'),
        '#options' => $options,
        '#required' => TRUE,
      ];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Vote'),
      ];
    }
    else {
      $form['message'] = [
        '#markup' => $voted ? $this->t('You have already voted.') : $this->t('Voting is closed.'),
      ];
    }

    if ($vote_question->get('show_results')->value && $voted) {
      $form['results'] = [
        '#type' => 'details',
        '#title' => $this->t('Results'),
        '#open' => TRUE,
        'items' => [
          '#theme' => 'item_list',
          '#items' => $this->buildResultsList($vote_question->id(), $answers),
        ],
        '#weight' => 100,
      ];
    }

    return $form;
  }

public function submitForm(array &$form, FormStateInterface $form_state): void {
  $qid = (int) $form_state->getBuildInfo()['args'][0]->id();
  $answer_id = (int) $form_state->getValue('answer');
  $uid = (int) $this->currentUser()->id();

  // Create and save the vote.
  $vote = $this->etm->getStorage('vote_vote')->create([
    'question' => $qid,
    'answer' => $answer_id,
    'uid' => $uid,
  ]);
  $vote->save();

  $this->messenger()->addStatus($this->t('Your vote has been recorded.'));
  $form_state->setRedirect('votacao.vote_form', ['vote_question' => $qid]);
}


protected function buildResultsList(int $qid, array $answers): array {
  $storage = $this->etm->getStorage('vote_vote');

  // Count votes per visible (published) answer.
  $counts = array_fill_keys(array_map(fn($a) => (int) $a->id(), $answers), 0);

  $ids = $storage->getQuery()
    ->condition('question', $qid)
    ->accessCheck(FALSE)
    ->execute();

  if (!empty($ids)) {
    $votes = $storage->loadMultiple($ids);
    foreach ($votes as $v) {
      $aid = (int) $v->get('answer')->target_id;
      if (isset($counts[$aid])) {
        $counts[$aid]++;
      }
    }
  }

  $total = array_sum($counts);
  $items = [];

  foreach ($answers as $a) {
    $aid   = (int) $a->id();
    $votes = (int) ($counts[$aid] ?? 0);

    //calculate percentage
    $pct = $total > 0 ? round(($votes / $total) * 100, 1) : 0.0;
    $items[] = $a->label() . ' — ' . $votes . ' (' . $pct . '%)';
  }

  return $items;
}

}
