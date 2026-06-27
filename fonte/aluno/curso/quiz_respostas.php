<?php
// quiz_respostas.php

require 'conexao.php';
session_start();

$usuario_id = $_SESSION['usuario_id'];
$quiz_id = $_POST['quiz_id'];

// Cria tentativa se não existir
$tentativa = $db->query("
  SELECT id FROM quiz_tentativas
  WHERE quiz_id = {$quiz_id} AND aluno_id = {$usuario_id}
")->fetchColumn();

if (!$tentativa) {
  $db->query("
    INSERT INTO quiz_tentativas (quiz_id, aluno_id)
    VALUES ({$quiz_id}, {$usuario_id})
  ");
  $tentativa = $db->lastInsertId();
}

// Grava respostas
foreach ($_POST as $key => $value) {
  if (strpos($key, 'pergunta_') === 0) {
    $pergunta_id = str_replace('pergunta_', '', $key);
    $opcao_id = $value;

    // Verifica se já respondeu
    $existe = $db->query("
      SELECT id FROM quiz_respostas
      WHERE tentativa_id = {$tentativa} AND pergunta_id = {$pergunta_id}
    ")->fetchColumn();

    if (!$existe) {
      $correta = $db->query("
        SELECT correta FROM quiz_opcoes
        WHERE id = {$opcao_id}
      ")->fetchColumn();

      $pontos = $correta ? 1 : 0;

      $db->query("
        INSERT INTO quiz_respostas (tentativa_id, pergunta_id, opcao_id, correta, pontos)
        VALUES ({$tentativa}, {$pergunta_id}, {$opcao_id}, {$correta}, {$pontos})
      ");
    }
  }
}

// Finaliza tentativa
$db->query("
  UPDATE quiz_tentativas
  SET finalizado_em = NOW()
  WHERE id = {$tentativa}
");

// Verifica liberação
require 'funcoes_liberacao.php';
$aula_id = $db->query("SELECT aula_id FROM quizzes WHERE id = {$quiz_id}")->fetchColumn();
$liberar = podeLiberarProximaAula($usuario_id, $aula_id);

if ($liberar) {
  liberarAula($usuario_id, $aula_id + 1);
}

echo json_encode([
  'mensagem' => 'Respostas registradas com sucesso!',
  'liberar_proxima' => $liberar
]);
