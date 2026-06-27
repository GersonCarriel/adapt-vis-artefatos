<?php
// -------------------------------
// LTI-SIM: versão robusta p/ debug
// -------------------------------
declare(strict_types=1);

// Mostra erros só se estiver no ambiente de DEV
$DEV = true; // mude para false em produção
if ($DEV) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Inclui a conexão usando caminho absoluto
$cxPath = __DIR__ . '/conexao.php';
if (!is_file($cxPath)) {
    http_response_code(500);
    echo "<h3>Erro: conexao.php não encontrado em " . htmlspecialchars($cxPath) . "</h3>";
    exit;
}
require_once $cxPath;

// Valida objeto de conexão ($conn)
if (!isset($conn)) {
    http_response_code(500);
    echo "<h3>Erro: variável \$conn não definida em conexao.php</h3>";
    exit;
}

// Se for mysqli, forçamos exceptions (facilita achar erros de SQL)
if ($conn instanceof mysqli) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn->set_charset('utf8mb4');
}

// Teste simples de conexão
try {
    // Ajuste o nome da tabela/colunas conforme o seu dump
    $sql = "SELECT id, nome, lti_sub, moodle_user 
            FROM usuarios 
            WHERE lti_issuer = 'simulador-lti'
            ORDER BY id ASC";
    $result = $conn->query($sql);
} catch (Throwable $e) {
    http_response_code(500);
    if ($DEV) {
        echo "<h3>Erro de banco:</h3><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    } else {
        echo "<h3>Erro interno ao consultar usuários.</h3>";
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Simulador LTI</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    h2 { color: #333; }
    ul { list-style: none; padding: 0; }
    li { margin-bottom: 10px; }
    form { display: inline; }
    input[type="submit"] {
      background-color: #0066cc; color: white; border: none;
      padding: 6px 12px; cursor: pointer; border-radius: 4px;
    }
    input[type="submit"]:hover { background-color: #004999; }
    .muted { color: #666; font-size: 0.95em; }
    .err   { color: #b30000; }
  </style>
</head>
<body>
  <h2>🔐 Simulador de Acesso LTI</h2>
  <p class="muted">Escolha um usuário para simular o acesso.</p>

  <?php if ($result instanceof mysqli_result && $result->num_rows > 0): ?>
    <ul>
      <?php while ($row = $result->fetch_assoc()): ?>
        <li>
          👤 <?php echo htmlspecialchars($row['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
          (<?php echo htmlspecialchars($row['moodle_user'] ?? '', ENT_QUOTES, 'UTF-8'); ?>)
          <form method="post" action="redirecionar.php">
            <input type="hidden" name="sub" value="<?php echo htmlspecialchars($row['lti_sub'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <input type="submit" value="Entrar">
          </form>
        </li>
      <?php endwhile; ?>
    </ul>
  <?php else: ?>
    <p class="err">Nenhum usuário com <code>lti_issuer = 'simulador-lti'</code> encontrado.</p>
    <p class="muted">Verifique se você populou a tabela <code>usuarios</code> (ex.: com o seed que geramos).</p>
  <?php endif; ?>
</body>
</html>
