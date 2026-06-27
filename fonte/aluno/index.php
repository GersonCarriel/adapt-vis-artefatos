<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Papel via sessão (definido pelo OIDC no front controller) ou pelo path (fallback)
$role = $_SESSION['adapt_user']['role_decided'] ?? null;
if (!$role) {
    $u = $_SERVER['REQUEST_URI'] ?? '';
    $role = (str_contains($u, '/professor') ? 'professor'
           : (str_contains($u, '/admin')     ? 'admin'
           : 'aluno'));
}

$userName  = $_SESSION['adapt_user']['name']  ?? null;
$userEmail = $_SESSION['adapt_user']['email'] ?? null;

// NOVO: pega o sub vindo do LTI (ajuste a chave se estiver diferente)
$subFromLti = $_SESSION['adapt_user']['sub'] ?? null;

// Flag opcional para você conseguir usar o index "normal" sem redirecionar
$debug = isset($_GET['debug']);

// Se for ALUNO, tiver sub na sessão e NÃO estiver em modo debug,
// reaproveita a lógica do redirecionar.php e segue para curso.php.
if ($role === 'aluno' && $subFromLti && !$debug) {
    // Emula o POST que o lti-sim.php faz
    $_POST['sub'] = $subFromLti;

    // index.php e redirecionar.php estão na mesma pasta (/public/aluno),
    // por isso o caminho relativo funciona:
    require __DIR__ . '/redirecionar.php';
    exit; // garante que nada mais do index.php rode
}

// Define classes de badge para exibir o papel do usuário na interface
$badgeClass = 'badge';
switch ($role) {
    case 'professor':
        $badgeClass .= ' badge-professor';
        break;
    case 'admin':
        $badgeClass .= ' badge-admin';
        break;
    default:
        $badgeClass .= ' badge-aluno';
        $role = 'aluno';
        break;
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Adapt-Vis – Área do Aluno</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    :root {
      --bg-page: #f4f6fb;
      --bg-card: #ffffff;
      --border-soft: #dde2ee;
      --text-main: #1f2933;
      --text-muted: #6b7280;
      --accent: #2563eb;
      --accent-soft: #e0edff;
      --danger: #b91c1c;
      --shadow-soft: 0 10px 30px rgba(15, 23, 42, 0.08);
      --radius-lg: 18px;
      --radius-pill: 999px;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    body {
      background: radial-gradient(circle at top left, #e0edff, #f4f6fb 40%, #ffffff);
      min-height: 100vh;
      color: var(--text-main);
      padding: 20px;
    }

    main {
      max-width: 1080px;
      margin: 0 auto;
    }

    .shell {
      background: rgba(255, 255, 255, 0.8);
      border-radius: 24px;
      box-shadow: var(--shadow-soft);
      padding: 20px;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(226, 232, 240, 0.8);
    }

    .header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 12px 16px;
      border-radius: var(--radius-lg);
      background: linear-gradient(135deg, #eef2ff, #eff6ff);
      border: 1px solid var(--border-soft);
    }

    .header-left {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: var(--accent-soft);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      color: var(--accent);
      font-size: 18px;
    }

    .user-info {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .user-name {
      font-weight: 600;
      font-size: 15px;
    }

    .user-email {
      font-size: 13px;
      color: var(--text-muted);
    }

    .badge {
      padding: 6px 14px;
      border-radius: var(--radius-pill);
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      border: 1px solid transparent;
    }
    .badge-aluno {
      background: #dcfce7;
      color: #166534;
      border-color: #bbf7d0;
    }
    .badge-professor {
      background: #fee2e2;
      color: #b91c1c;
      border-color: #fecaca;
    }
    .badge-admin {
      background: #e0f2fe;
      color: #075985;
      border-color: #bae6fd;
    }

    .toolbar {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 12px;
      flex-wrap: wrap;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 8px 14px;
      font-size: 13px;
      border-radius: var(--radius-pill);
      border: 1px solid var(--border-soft);
      background: #ffffff;
      color: var(--text-main);
      text-decoration: none;
      cursor: pointer;
      transition: all 0.18s ease;
    }
    .btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12);
      border-color: #cbd5f5;
    }
    .btn-primary {
      background: var(--accent);
      color: #ffffff;
      border-color: #1d4ed8;
    }
    .btn-primary:hover {
      background: #1d4ed8;
    }

    .btn-ghost {
      background: transparent;
    }

    .btn-icon {
      width: 18px;
      height: 18px;
      border-radius: 999px;
      border: 1px solid rgba(148, 163, 184, 0.7);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
    }

    .content-wrapper {
      margin-top: 18px;
      background: #ffffff;
      border-radius: 20px;
      border: 1px solid var(--border-soft);
      padding: 18px;
      min-height: 320px;
    }

    .content-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 8px;
      gap: 10px;
    }

    .content-title {
      font-size: 16px;
      font-weight: 600;
    }

    .content-subtitle {
      font-size: 13px;
      color: var(--text-muted);
    }

    .debug-hint {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 4px;
    }

    .alert {
      padding: 8px 12px;
      border-radius: 10px;
      font-size: 13px;
      margin-top: 10px;
    }
    .alert-info {
      background: #eff6ff;
      color: #1d4ed8;
      border: 1px solid #bfdbfe;
    }

    @media (max-width: 640px) {
      .header {
        flex-direction: column;
        align-items: flex-start;
      }
      .content-wrapper {
        padding: 14px;
      }
    }
  </style>
</head>
<body>
<main>
  <div class="shell">
    <header class="header">
      <div class="header-left">
        <div class="user-avatar">
          <?php
          $initials = '';
          if (!empty($userName)) {
              $parts = explode(' ', trim($userName));
              $initials = strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'));
              if (count($parts) > 1) {
                  $initials .= strtoupper(mb_substr(end($parts), 0, 1, 'UTF-8'));
              }
          } else {
              $initials = 'AD';
          }
          echo htmlspecialchars($initials);
          ?>
        </div>
        <div class="user-info">
          <div class="user-name">
            <?php echo htmlspecialchars($userName ?? 'Usuário não identificado'); ?>
          </div>
          <div class="user-email">
            <?php echo htmlspecialchars($userEmail ?? 'sem e-mail na sessão'); ?>
          </div>
        </div>
      </div>

      <div>
        <span class="<?php echo $badgeClass; ?>">
          <?php echo strtoupper($role); ?>
        </span>
      </div>
    </header>

    <div class="toolbar">
      <a href="lti-sim.php" class="btn">
        <span class="btn-icon">⚙</span>
        <span>Simulador LTI (teste manual)</span>
      </a>

      <a href="?debug=1" class="btn btn-ghost">
        <span class="btn-icon">🐞</span>
        <span>Ver esta tela sem redirecionar</span>
      </a>

      <a href="/curso/curso.php" class="btn btn-primary">
        <span class="btn-icon">▶</span>
        <span>Ir para curso.php (acesso direto)</span>
      </a>
    </div>

    <div class="content-wrapper">
      <div class="content-header">
        <div>
          <div class="content-title">Painel da Ferramenta Adapt-Vis</div>
          <div class="content-subtitle">
            Quando acessado via Moodle como <strong>aluno</strong>, este index.php redireciona automaticamente para o curso.php,
            reaproveitando a lógica do redirecionar.php.
          </div>
          <div class="debug-hint">
            Use <code>?debug=1</code> na URL para abrir esta tela mesmo sendo aluno, sem redirecionar.
          </div>
        </div>
      </div>

      <div class="alert alert-info">
        <strong>Observação:</strong> o redirecionamento automático é feito apenas se:
        <ul style="margin: 6px 0 0 18px; padding-left: 0;">
          <li>o papel for <code>aluno</code>;</li>
          <li>existir um <code>sub</code> em <code>$_SESSION['adapt_user']['sub']</code>;</li>
          <li>você não estiver usando <code>?debug=1</code>.</li>
        </ul>
      </div>

      <?php
      // Renderiza um HTML estático adicional, se existir (ex.: instruções, cards, etc.)
      $htmlPath = __DIR__ . '/main_content.html';
      if (is_file($htmlPath)) {
          readfile($htmlPath);
      }
      ?>
    </div>
  </div>
</main>
</body>
</html>
