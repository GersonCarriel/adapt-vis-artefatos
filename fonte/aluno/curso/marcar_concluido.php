<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//session_start();
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


include("../conexao.php");

$usuario_id = $_SESSION['usuario_id'] ?? null;
$item_id = $_POST['item_id'] ?? null;

if (!$usuario_id || !$item_id) {
  http_response_code(400);
  echo "Usuário ou item não especificado.";
  exit;
}

// Verifica se já existe interação
$sql = "SELECT concluido FROM interacoes_aluno WHERE usuario_id = ? AND item_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $usuario_id, $item_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
  // Atualiza o estado (alternância)
  $row = $res->fetch_assoc();
  $novoEstado = $row['concluido'] ? 0 : 1;

  $sqlUpdate = "UPDATE interacoes_aluno SET concluido = ?, data_conclusao = NOW() WHERE usuario_id = ? AND item_id = ?";
  $stmtUpdate = $conn->prepare($sqlUpdate);
  $stmtUpdate->bind_param("iii", $novoEstado, $usuario_id, $item_id);

  if (!$stmtUpdate->execute()) {
    error_log("Erro ao atualizar: " . $stmtUpdate->error);
    http_response_code(500);
    echo "Erro ao atualizar";
    exit;
  }

  echo $novoEstado ? 'concluido' : 'desfeito';
} else {
  // Insere como concluído
  $sqlInsert = "INSERT INTO interacoes_aluno (usuario_id, item_id, concluido, data_conclusao) VALUES (?, ?, 1, NOW())";
  $stmtInsert = $conn->prepare($sqlInsert);
  $stmtInsert->bind_param("ii", $usuario_id, $item_id);

  if (!$stmtInsert->execute()) {
    error_log("Erro ao inserir: " . $stmtInsert->error);
    http_response_code(500);
    echo "Erro ao inserir";
    exit;
  }

  echo 'concluido';
}
