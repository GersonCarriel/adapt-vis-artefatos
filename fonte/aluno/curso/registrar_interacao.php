<?php
// Ativar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("../conexao.php");
session_start();

// Verifica se o usuário está logado
$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
    http_response_code(401);
    echo json_encode(["erro" => "Sessão inválida"]);
    exit;
}

// Lê os dados enviados via JSON
$dados = json_decode(file_get_contents("php://input"), true);
$item_id = $dados['item_id'] ?? null;
$material_id = $dados['material_id'] ?? null;
$tempo_utilizado = $dados['tempo_utilizado'] ?? 0;
$concluido = $dados['concluido'] ?? false;

if (!$item_id) {
    http_response_code(400);
    echo json_encode(["erro" => "Item não especificado"]);
    exit;
}

// Verifica se já existe interação registrada
$sqlCheck = "SELECT id FROM interacoes_aluno WHERE usuario_id = ? AND item_id = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("ii", $usuario_id, $item_id);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

if ($resCheck->num_rows > 0) {
    // Atualiza interação existente
    $sqlUpdate = "UPDATE interacoes_aluno
                  SET tempo_utilizado_segundos = tempo_utilizado_segundos + ?, 
                      concluido = ?, 
                      data_conclusao = IF(? = 1, NOW(), data_conclusao), 
                      material_id = ?
                  WHERE usuario_id = ? AND item_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("iiiiii", $tempo_utilizado, $concluido, $concluido, $material_id, $usuario_id, $item_id);
    $stmtUpdate->execute();
} else {
    // Insere nova interação
    $sqlInsert = "INSERT INTO interacoes_aluno 
                  (usuario_id, item_id, material_id, tempo_utilizado_segundos, concluido, data_conclusao)
                  VALUES (?, ?, ?, ?, ?, IF(? = 1, NOW(), NULL))";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("iiiiii", $usuario_id, $item_id, $material_id, $tempo_utilizado, $concluido, $concluido);
    $stmtInsert->execute();
}

echo json_encode(["status" => "ok"]);
