<?php
require '../conexao.php';
session_start();

$usuario_id = $_SESSION['usuario_id'] ?? null;
$quiz_id = $_POST['quiz_id'] ?? null;
$item_id = $_POST['item_id'] ?? null;

if (!$usuario_id || !$quiz_id || !$item_id) {
  http_response_code(400);
  echo "Dados incompletos.";
  exit;
}

$salvas = 0;

// Grava cada resposta do quiz
foreach ($_POST as $key => $value) {
  if (strpos($key, 'pergunta_') === 0) {
    $pergunta_id = str_replace('pergunta_', '', $key);
    $opcao_id = $value;

    $stmt = $conn->prepare("
      INSERT INTO respostas_quiz (usuario_id, quiz_id, pergunta_id, opcao_id)
      VALUES (?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE
        opcao_id = VALUES(opcao_id),
        data_resposta = CURRENT_TIMESTAMP()
    ");
    $stmt->bind_param("iiii", $usuario_id, $quiz_id, $pergunta_id, $opcao_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
      $salvas++;
    }
  }
}

// Atualiza a conclusão do quiz no interações_aluno
if (isset($_POST['finalizar_quiz']) && $_POST['finalizar_quiz'] == '1') {
  $sql = "UPDATE interacoes_aluno
          SET data_conclusao = NOW(),
              concluido = 1
          WHERE usuario_id = ? AND item_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $usuario_id, $item_id);
  $stmt->execute();
}

echo "ok";
exit;
