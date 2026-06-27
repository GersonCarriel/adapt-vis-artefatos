<?php
// funcoes_liberacao.php

function verificarItensFinalizados($usuario_id, $aula_id) {
  global $db;
  $total = $db->query("
    SELECT COUNT(*) FROM itens_aula
    WHERE aula_id = {$aula_id}
  ")->fetchColumn();

  $finalizados = $db->query("
    SELECT COUNT(*) FROM itens_aluno ia
    JOIN itens_aula i ON i.id = ia.item_id
    WHERE i.aula_id = {$aula_id} AND ia.usuario_id = {$usuario_id} AND ia.finalizado_em IS NOT NULL
  ")->fetchColumn();

  return $total > 0 && $total == $finalizados;
}

function verificarQuizFinalizado($usuario_id, $aula_id) {
  global $db;
  $quiz_id = $db->query("
    SELECT id FROM quizzes
    WHERE aula_id = {$aula_id}
  ")->fetchColumn();

  if (!$quiz_id) return false;

  $finalizado = $db->query("
    SELECT finalizado_em FROM quiz_tentativas
    WHERE quiz_id = {$quiz_id} AND aluno_id = {$usuario_id}
  ")->fetchColumn();

  return !empty($finalizado);
}

function podeLiberarProximaAula($usuario_id, $aula_id) {
  return verificarItensFinalizados($usuario_id, $aula_id)
      && verificarQuizFinalizado($usuario_id, $aula_id);
}

function liberarAula($usuario_id, $aula_id) {
  global $db;
  $existe = $db->query("
    SELECT id FROM aulas_aluno
    WHERE aula_id = {$aula_id} AND usuario_id = {$usuario_id}
  ")->fetchColumn();

  if (!$existe) {
    $db->query("
      INSERT INTO aulas_aluno (aula_id, usuario_id)
      VALUES ({$aula_id}, {$usuario_id})
    ");
  }
}
