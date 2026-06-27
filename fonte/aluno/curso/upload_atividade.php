<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . "/../conexao.php";

/* --- Diagnóstico controlado: logue erros em vez de exibir na tela --- */
error_reporting(E_ALL);
ini_set('display_errors', '0');            // não exibir para o usuário final
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/upload_atividade_error.log');

function fail(string $msg, int $http = 400) {
    http_response_code($http);
    // Em caso de falha, ainda mostramos a mensagem:
    echo $msg;
    exit;
}

/* --- Entrada --- */
$usuario_id     = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
$material_id    = isset($_POST['material_id'])   ? (int)$_POST['material_id']   : 0;
$texto_resposta = isset($_POST['texto_resposta']) ? trim((string)$_POST['texto_resposta']) : '';
$arquivo        = $_FILES['arquivo'] ?? null;

// URL para onde voltar após processar (fallback: referer ou raiz)
$returnTo = isset($_POST['return_to']) && $_POST['return_to'] !== ''
    ? $_POST['return_to']
    : ($_SERVER['HTTP_REFERER'] ?? '/');

if ($usuario_id <= 0 || $material_id <= 0) {
    fail("Dados incompletos para o envio.", 400);
}

/* --- Verifica existência dos FKs para evitar erro 1452 --- */
// usuarios
if (!$stmt = $conn->prepare("SELECT 1 FROM usuarios WHERE id = ?")) {
    fail("Falha interna (usuarios.prepare): ".$conn->error, 500);
}
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
if (!$stmt->get_result()->num_rows) {
    fail("Usuário inválido.", 400);
}

// materiais_item (é a tabela certa segundo sua FK)
if (!$stmt = $conn->prepare("SELECT tipo_devolutiva FROM materiais_item WHERE id = ?")) {
    fail("Falha interna (materiais_item.prepare): ".$conn->error, 500);
}
$stmt->bind_param("i", $material_id);
$stmt->execute();
$res = $stmt->get_result();
$tipo_devolutiva = '';
if ($row = $res->fetch_assoc()) {
    $tipo_devolutiva = (string)($row['tipo_devolutiva'] ?? '');
} else {
    fail("Material inválido.", 400);
}

/* --- Validação por tipo_devolutiva --- */
$arquivo_ok = ($arquivo && $arquivo['error'] === UPLOAD_ERR_OK);
$texto_ok   = ($texto_resposta !== '');

if (
    ($tipo_devolutiva === 'arquivo' && !$arquivo_ok) ||
    ($tipo_devolutiva === 'texto'   && !$texto_ok)   ||
    ($tipo_devolutiva === 'ambos'   && !$arquivo_ok && !$texto_ok)
) {
    fail("Dados incompletos para o envio.", 400);
}

/* --- Seed da linha (garante data_inicio) --- */
$sqlSeed = "INSERT INTO atividades_enviadas (material_id, usuario_id, status, data_inicio)
            VALUES (?, ?, 'fazendo', NOW())
            ON DUPLICATE KEY UPDATE status = status";
if (!$stmt = $conn->prepare($sqlSeed)) {
    fail("Falha interna (seed.prepare): ".$conn->error, 500);
}
$stmt->bind_param("ii", $material_id, $usuario_id);
if (!$stmt->execute()) {
    fail("Falha ao preparar envio (seed.execute).", 500);
}

/* --- Se houver arquivo, processa upload --- */
$nome_final   = null;
$caminho_final = null;
$mime_type    = null;

if ($arquivo_ok) {
    $baseUploadDir = "/var/uploads_atividades"; // ajuste se necessário
    if (!is_dir($baseUploadDir)) {
        if (!mkdir($baseUploadDir, 0775, true)) {
            fail("Falha ao preparar diretório de upload.", 500);
        }
    }
    if (!is_writable($baseUploadDir)) {
        fail("Diretório de upload sem permissão de escrita.", 500);
    }

    $nome_original = pathinfo($arquivo['name'], PATHINFO_FILENAME);
    $extensao      = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
    $nome_limpo    = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $nome_original);

    // Buscar última versão pra incrementar
    $sqlCheck = "SELECT nome_arquivo FROM atividades_enviadas WHERE material_id = ? AND usuario_id = ?";
    if (!$stmt = $conn->prepare($sqlCheck)) {
        fail("Falha interna (check.prepare): ".$conn->error, 500);
    }
    $stmt->bind_param("ii", $material_id, $usuario_id);
    $stmt->execute();
    $resCk = $stmt->get_result();

    $versao = 1;
    if ($resCk->num_rows > 0) {
        $registro = $resCk->fetch_assoc();
        if (!empty($registro['nome_arquivo']) && preg_match('/_v(\d+)\./', $registro['nome_arquivo'], $m)) {
            $versao = (int)$m[1] + 1;
        } else {
            $versao = 2;
        }
    }

    $dataStamp    = date('Ymd_Hi');
    $material_fmt = str_pad((string)$material_id, 6, '0', STR_PAD_LEFT);
    $usuario_fmt  = str_pad((string)$usuario_id, 8, '0', STR_PAD_LEFT);

    $nome_final    = "M{$material_fmt}U{$usuario_fmt}_{$dataStamp}_{$nome_limpo}_v{$versao}." . $extensao;
    $caminho_final = rtrim($baseUploadDir, '/')."/".$nome_final;

    if (!move_uploaded_file($arquivo['tmp_name'], $caminho_final)) {
        fail("Falha ao salvar o arquivo.", 500);
    }
    $mime_type = @mime_content_type($caminho_final) ?: null;
}

/* --- Atualiza a linha existente com o que foi enviado --- */
$sqlUpdate = "UPDATE atividades_enviadas
              SET
                  nome_arquivo   = ?,
                  caminho_local  = ?,
                  mime_type      = ?,
                  texto_resposta = ?,
                  data_envio     = NOW(),
                  tempo_execucao = TIMESTAMPDIFF(MINUTE, data_inicio, NOW()),
                  status         = 'enviado'
              WHERE material_id = ? AND usuario_id = ?";
if (!$stmt = $conn->prepare($sqlUpdate)) {
    fail("Falha interna (update.prepare): ".$conn->error, 500);
}
$stmt->bind_param(
    "ssssii",
    $nome_final,
    $caminho_final,
    $mime_type,
    $texto_resposta,
    $material_id,
    $usuario_id
);
if (!$stmt->execute()) {
    fail("Falha ao atualizar envio.", 500);
}

/* --- Redireciona de volta para a página anterior (solução simples) --- */
echo "<!DOCTYPE html>
<html lang='pt-br'>
<head>
<meta charset='UTF-8'>
<meta http-equiv='refresh' content='2;url=javascript:history.back()'>
<style>
  body {
    font-family: Arial, sans-serif;
    background-color: #f8f9fa;
    text-align: center;
    padding-top: 100px;
    color: #333;
  }
  .msg {
    display: inline-block;
    padding: 20px 30px;
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    border-radius: 8px;
    font-size: 18px;
    box-shadow: 0 0 6px rgba(0,0,0,0.1);
  }
</style>
</head>
<body>
  <div class='msg'>✅ Atividade enviada com sucesso!<br><small>Você será redirecionado automaticamente...</small></div>
  <script>
    setTimeout(function(){ window.history.back(); }, 2000);
  </script>
</body>
</html>";
exit;
