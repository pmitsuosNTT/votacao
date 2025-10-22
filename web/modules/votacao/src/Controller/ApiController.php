<?php

namespace Drupal\votacao\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends ControllerBase {

  public function __construct(protected EntityTypeManagerInterface $etm) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('entity_type.manager'));
  }

  private function jsonError(string $msg, int $code): JsonResponse {
    return new JsonResponse(['error' => $msg], $code);
  }

  private function guard(Request $request): ?JsonResponse {
    // Auth
    $sent = (string) $request->headers->get('X-API-Key');
    $expected = (string) \Drupal::config('votacao.settings')->get('api_key');
    if (!$expected || !hash_equals($expected, $sent)) {
      return $this->jsonError('Unauthorized', 401);
    }
    // Global enable/disable
    $enabled = \Drupal::config('votacao.settings')->get('voting_enabled');
    if ($enabled !== NULL && !(bool) $enabled) {
      return $this->jsonError('Voting is disabled', 503);
    }
    return NULL;
  }

  public function listQuestions(Request $request): JsonResponse {
    if ($e = $this->guard($request)) return $e;

    $storage = $this->etm->getStorage('vote_question');
    $ids = $storage->getQuery()
      ->condition('status', 1)
      ->condition('is_open', 1)
      ->accessCheck(FALSE)
      ->sort('id', 'DESC')
      ->execute();

    $out = [];
    if ($ids) {
      foreach ($storage->loadMultiple($ids) as $q) {
        $out[] = [
          'id'    => (int) $q->id(),
          'uuid'  => $q->uuid(),
          'label' => $q->label(),
        ];
      }
    }
    return new JsonResponse($out);
  }

  public function showQuestion(string $uuid, Request $request): JsonResponse {
    if ($e = $this->guard($request)) return $e;

    $q = NULL;
    $qs = $this->etm->getStorage('vote_question')->loadByProperties(['uuid' => $uuid]);
    if ($qs) $q = reset($qs);
    if (!$q || !$q->get('status')->value) {
      return $this->jsonError('Question not found', 404);
    }

    $a_storage = $this->etm->getStorage('vote_answer');
    $a_ids = $a_storage->getQuery()
      ->condition('question', (int) $q->id())
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->sort('weight', 'ASC')
      ->execute();

    $answers = [];
    if ($a_ids) {
      foreach ($a_storage->loadMultiple($a_ids) as $a) {
        $answers[] = ['id' => (int) $a->id(), 'title' => $a->label()];
      }
    }

    return new JsonResponse([
      'id'           => (int) $q->id(),
      'uuid'         => $q->uuid(),
      'label'        => $q->label(),
      'is_open'      => (bool) $q->get('is_open')->value,
      'show_results' => (bool) $q->get('show_results')->value,
      'answers'      => $answers,
    ]);
  }

  public function castVote(string $uuid, Request $request): JsonResponse {
    if ($e = $this->guard($request)) return $e;

    $q = NULL;
    $qs = $this->etm->getStorage('vote_question')->loadByProperties(['uuid' => $uuid]);
    if ($qs) $q = reset($qs);
    if (!$q || !$q->get('status')->value) {
      return $this->jsonError('Question not found', 404);
    }
    if (!(bool) $q->get('is_open')->value) {
      return $this->jsonError('Voting is closed for this question', 403);
    }

    $data = json_decode($request->getContent() ?: '[]', TRUE);
    $answer_id = (int) ($data['answer_id'] ?? 0);
    if ($answer_id <= 0) {
      return $this->jsonError('answer_id is required', 400);
    }

    // Validate answer
    $a_valid = $this->etm->getStorage('vote_answer')->getQuery()
      ->condition('id', $answer_id)
      ->condition('question', (int) $q->id())
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    if (!$a_valid) {
      return $this->jsonError('Invalid answer for this question', 400);
    }

    // Identify user (anon = 0). NOTE: anon uniqueness is global uid=0.
    $uid = (int) $this->currentUser()->id();

    //duplicate check.
    $exists = $this->etm->getStorage('vote_vote')->getQuery()
      ->condition('question', (int) $q->id())
      ->condition('uid', $uid)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    if ($exists) {
      return $this->jsonError('You have already voted for this question', 409);
    }

    $vote = $this->etm->getStorage('vote_vote')->create([
      'question' => (int) $q->id(),
      'answer'   => $answer_id,
      'uid'      => $uid,
    ]);
    $vote->save();

    return new JsonResponse(['success' => TRUE], 201);
  }

  public function results(string $uuid, Request $request): JsonResponse {
    if ($e = $this->guard($request)) return $e;

    $q = NULL;
    $qs = $this->etm->getStorage('vote_question')->loadByProperties(['uuid' => $uuid]);
    if ($qs) $q = reset($qs);
    if (!$q || !$q->get('status')->value) {
      return $this->jsonError('Question not found', 404);
    }
    if (!(bool) $q->get('show_results')->value) {
      return $this->jsonError('Results are hidden for this question', 403);
    }

    $a_storage = $this->etm->getStorage('vote_answer');
    $a_ids = $a_storage->getQuery()
      ->condition('question', (int) $q->id())
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->sort('weight', 'ASC')
      ->execute();

    $answers = $a_ids ? $a_storage->loadMultiple($a_ids) : [];
    $counts = array_fill_keys(array_map(fn($a) => (int) $a->id(), $answers), 0);

    $v_storage = $this->etm->getStorage('vote_vote');
    $v_ids = $v_storage->getQuery()
      ->condition('question', (int) $q->id())
      ->accessCheck(FALSE)
      ->execute();
    if ($v_ids) {
      foreach ($v_storage->loadMultiple($v_ids) as $v) {
        $aid = (int) $v->get('answer')->target_id;
        if (isset($counts[$aid])) $counts[$aid]++;
      }
    }

    $out = [];
    foreach ($answers as $a) {
      $aid = (int) $a->id();
      $out[] = ['answer_id' => $aid, 'title' => $a->label(), 'votes' => (int) ($counts[$aid] ?? 0)];
    }

    return new JsonResponse([
      'id'      => (int) $q->id(),
      'uuid'    => $q->uuid(),
      'label'   => $q->label(),
      'results' => $out,
    ]);
  }
}
