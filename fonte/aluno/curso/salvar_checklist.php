<?php
// para ligar mensagem de erro na tela, para teste.
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../conexao.php';
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario_id']) || !is_numeric($_SESSION['usuario_id'])) {
  http_response_code(401); // Unauthorized
  echo 'DEBUG: Usuário não autenticado';
  exit;
}

$usuario_id = intval($_SESSION['usuario_id']);
$material_id = isset($_POST['material_id']) ? intval($_POST['material_id']) : 0;
$checklist_item_id = isset($_POST['checklist_item_id']) ? intval($_POST['checklist_item_id']) : 0;
$checked = isset($_POST['checked']) && $_POST['checked'] === 'true' ? 1 : 0;

// DEBUG: Exibir dados recebidos
echo "DEBUG: Recebido material_id=$material_id, checklist_item_id=$checklist_item_id, checked=$checked, usuario_id=$usuario_id\n";

// Validação básica dos dados recebidos
if ($material_id <= 0 || $checklist_item_id <= 0) {
  http_response_code(400); // Bad Request
  echo 'DEBUG: Dados inválidos';
  exit;
}


echo "<script>alert('material--===---==---==>'.material_id);</script>";

// Prepara e executa a query
$sql = "INSERT INTO checklist_status_aluno (material_id, checklist_item_id, usuario_id, checked, marcado_em)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE checked = VALUES(checked), marcado_em = NOW()";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500); // Internal Server Error
  echo 'DEBUG: Erro ao preparar a query';
  exit;
}

$stmt->bind_param("iiii", $material_id, $checklist_item_id, $usuario_id, $checked);
if (!$stmt->execute()) {
  http_response_code(500);
  echo 'DEBUG: Erro ao executar a query';
  exit;
}

echo 'DEBUG: Dados salvos com sucesso';
