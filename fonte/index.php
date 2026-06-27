<?php
declare(strict_types=1);

// ---------------------------
// Bootstrap (autoload e .env)
// ---------------------------
require __DIR__ . '/../vendor/autoload.php';
if (class_exists(Dotenv\Dotenv::class)) {
    Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

// Sessão
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---------------------------
// Base / Roteamento
// ---------------------------
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$envBase    = $_ENV['APP_BASE'] ?? ($_ENV['APP_BASE_PATH'] ?? null);
$basePrefix = rtrim($envBase ?: dirname($scriptName), '/'); // típico: /adaptativa

$rawUri  = $_GET['__u'] ?? ($_SERVER['REQUEST_URI'] ?? '/');
$uriPath = parse_url($rawUri, PHP_URL_PATH) ?? '/';

$path = $uriPath;
if ($basePrefix !== '' && $basePrefix !== '/' && str_starts_with($uriPath, $basePrefix)) {
    $path = (string)substr($uriPath, strlen($basePrefix));
}
if ($path === '' || $path === false) { $path = '/'; }

$envIsProd     = (($_ENV['APP_ENV'] ?? '') === 'prod');
$clientIsLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'], true);

// ---------------------------
// Helpers
// ---------------------------
function json_out($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function redirect(string $location, int $status = 302): void {
    http_response_code($status);
    header('Location: ' . $location);
    exit;
}
if (!function_exists('log_event')) {
    function log_event(string $event, array $data = []): void {
        $line = json_encode([
            'ts'     => date('c'),
            'pid'    => getmypid(),
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'path'   => $_SERVER['REQUEST_URI'] ?? null,
            'event'  => $event,
            'data'   => $data
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        @file_put_contents('/var/log/adaptativa/app.log', $line . PHP_EOL, FILE_APPEND);
    }
}

// ---------------------------
// Utils: base64url + JWT payload (sem verificar assinatura)
// ---------------------------
function b64url_decode_str(string $data): string {
    $remainder = strlen($data) % 4;
    if ($remainder) { $data .= str_repeat('=', 4 - $remainder); }
    return (string)base64_decode(strtr($data, '-_', '+/'));
}
function jwt_decode_payload_unsafe(string $jwt): ?array {
    $parts = explode('.', $jwt);
    if (count($parts) < 2) return null;
    $payloadJson = b64url_decode_str($parts[1]);
    $payload = json_decode($payloadJson, true);
    return is_array($payload) ? $payload : null;
}

// ---------------------------
/* LTI helpers */
// ---------------------------
function lti_payload_claim(array $p, string $claim, $default = null) {
    return $p[$claim] ?? $default;
}

/** Extração ROBUSTA de roles do id_token */
function lti_roles_from_idtoken(?string $idToken): array {
    if (!$idToken) return [];
    $payload = jwt_decode_payload_unsafe($idToken);
    if (!$payload) return [];

    $roles = [];

    // 1) Claim padrão IMS LTI 1.3
    $c1 = 'https://purl.imsglobal.org/spec/lti/claim/roles';
    if (array_key_exists($c1, $payload)) {
        $v = $payload[$c1];
        if (is_array($v)) $roles = array_merge($roles, $v);
        elseif (is_string($v) && $v !== '') $roles[] = $v;
    }

    // 2) Alguns provedores colocam em "roles"
    if (array_key_exists('roles', $payload)) {
        $v = $payload['roles'];
        if (is_array($v)) $roles = array_merge($roles, $v);
        elseif (is_string($v) && $v !== '') $roles[] = $v;
    }

    // 3) Edge: roles dentro de "context"
    $c2 = 'https://purl.imsglobal.org/spec/lti/claim/context';
    if (isset($payload[$c2]) && is_array($payload[$c2]) && isset($payload[$c2]['roles']) && is_array($payload[$c2]['roles'])) {
        $roles = array_merge($roles, $payload[$c2]['roles']);
    }

    // Normaliza
    $roles = array_values(array_filter(array_map('strval', $roles)));

    // Log auxiliar para garantir que esta função foi chamada
    log_event('id_token_claims_seen', [
        'keys'  => array_slice(array_keys($payload), 0, 30),
        'roles' => $roles ?: null
    ]);

    return $roles;
}

/** Decide alvo pelas roles (funciona com URIs IMS) */
function decide_target_from_roles(array $roles): ?string {
    $isAluno = false;
    foreach ($roles as $r) {
        $R = strtolower((string)$r);

        if (strpos($R, 'administrator') !== false) {
            return 'admin';
        }
        if (
            strpos($R, 'instructor') !== false ||
            strpos($R, 'teacher') !== false ||
            strpos($R, 'faculty') !== false ||
            strpos($R, 'teachingassistant') !== false
        ) {
            return 'professor';
        }
        if (strpos($R, 'learner') !== false || strpos($R, 'student') !== false) {
            $isAluno = true;
        }
    }
    return $isAluno ? 'aluno' : null;
}

/** Heurística por e-mail (config via .env) */
function decide_target_from_email(?string $email): ?string {
    if (!$email) return null;

    $inList = static function(string $env, string $needle): bool {
        $list = array_filter(array_map('trim', explode(',', $_ENV[$env] ?? '')));
        foreach ($list as $e) {
            if (strcasecmp($e, $needle) === 0) return true;
        }
        return false;
    };

    if ($inList('ROLE_ADMIN_EMAILS', $email)) return 'admin';
    if ($inList('ROLE_PROF_EMAILS',  $email)) return 'professor';
    return null;
}

// ---------------------------
// Debug rápido (somente local e não-prod)
// ---------------------------
if (($_GET['__debug'] ?? '') === '1' && !$envIsProd && $clientIsLocal) {
    json_out([
        'REQUEST_URI'       => $_SERVER['REQUEST_URI'] ?? null,
        'SCRIPT_NAME'       => $scriptName,
        'APP_BASE(.env)'    => $envBase,
        'basePrefix'        => $basePrefix,
        '__u(from Nginx)'   => $_GET['__u'] ?? null,
        'uriPath'           => $uriPath,
        'path'              => $path,
        'APP_PUBLIC_ORIGIN' => $_ENV['APP_PUBLIC_ORIGIN'] ?? null,
        'SESSION'           => $_SESSION['adapt_user'] ?? null,
    ]);
}

// ---------------------------
// Health & Ping
// ---------------------------
if ($path === '/' || $path === '/health' || $path === '/health.txt') {
    echo "OK-adaptativa";
    exit;
}
if ($path === '/lti/ping') {
    json_out(['ok' => true, 'ts' => time(), 'path' => $path]);
}

// ---------------------------
// LTI 1.0/1.1 (simples)
// ---------------------------
if ($path === '/lti/launch' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $role = $_POST['role'] ?? 'Learner';
    if (in_array($role, ['Admin','Administrator'], true)) {
        redirect(($basePrefix ?: '') . '/admin', 302);
    }
    if (in_array($role, ['Instructor','Professor','Teacher'], true)) {
        redirect(($basePrefix ?: '') . '/professor', 302);
    }
    redirect(($basePrefix ?: '') . '/aluno', 302);
}

// ---------------------------
// LTI 1.3 — LOGIN INIT (OIDC)
// ---------------------------
if ($path === '/lti/login') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
    $publicOrigin = $_ENV['APP_PUBLIC_ORIGIN'] ?? ($scheme . '://' . $host);
    $redirectUri  = $publicOrigin . ($basePrefix ?: '') . '/lti/oidc';

    $param = static function(string $key, ?string $default = ''): string {
        if (isset($_POST[$key])) return (string)$_POST[$key];
        if (isset($_GET[$key]))  return (string)$_GET[$key];
        return (string)$default;
    };
    $profile        = $param('profile', 'dev');
    $targetIncoming = $param('target', '');
    $loginHint      = $param('login_hint', '');
    $messageHint    = $param('lti_message_hint', '');
    $targetLinkUri  = $param('target_link_uri', $publicOrigin . ($basePrefix ?: '') . '/lti/launch');

    $platforms = [];
    $cfgFile   = dirname(__DIR__) . '/config/lti-platforms.php';
    if (is_file($cfgFile)) {
        /** @var array $platforms */
        $platforms = require $cfgFile;
    }
    $plat = $platforms[$profile] ?? [
        'issuer'         => $_ENV['LTI13_PLATFORM_ISS']      ?? 'http://18.225.92.145',
        'client_id'      => $_ENV['LTI13_CLIENT_ID']         ?? '',
        'deployment_id'  => $_ENV['LTI13_DEPLOYMENT_ID']     ?? '',
        'auth_login_url' => $_ENV['LTI13_AUTH_LOGIN_URL']    ?? 'http://18.225.92.145/mod/lti/auth.php',
        'token_url'      => $_ENV['LTI13_AUTH_TOKEN_URL']    ?? 'http://18.225.92.145/mod/lti/token.php',
        'jwks_url'       => $_ENV['LTI13_KEYSET_URL']        ?? 'http://18.225.92.145/mod/lti/certs.php',
    ];
    $authUrl  = $plat['auth_login_url'];
    $clientId = $plat['client_id'];

    $state = bin2hex(random_bytes(16));
    $nonce = bin2hex(random_bytes(16));
    setcookie('lti13_state', $state, [
        'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    ]);
    setcookie('lti13_nonce', $nonce, [
        'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    ]);

    log_event('oidc_login_redirect', [
        'computed_redirect_uri' => $redirectUri,
        'platform_client_id'    => $clientId,
        'incoming_client_id'    => $param('client_id', null),
        'profile'               => $profile,
        'target_incoming'       => ($targetIncoming === '' ? null : $targetIncoming),
        'target_link_uri'       => $targetLinkUri
    ]);

    $qs = http_build_query([
        'scope'            => 'openid',
        'response_type'    => 'id_token',
        'response_mode'    => 'form_post',
        'prompt'           => 'none',
        'client_id'        => $clientId,
        'redirect_uri'     => $redirectUri,
        'login_hint'       => $loginHint,
        'lti_message_hint' => $messageHint,
        'state'            => $state,
        'nonce'            => $nonce,
        'target_link_uri'  => $targetLinkUri,
    ]);
    redirect($authUrl . '?' . $qs, 302);
}

// ---------------------------
// LTI 1.3 — OIDC Redirect (POST do Moodle)
// ---------------------------
if ($path === '/lti/oidc' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {

    log_event('oidc_entry', [
        'method'       => 'POST',
        'has_id_token' => isset($_POST['id_token']) && $_POST['id_token'] !== '',
        'cookie_state' => isset($_COOKIE['lti13_state']) ? 'present' : 'absent',
        'post_state'   => isset($_POST['state']) ? 'present' : 'absent',
    ]);

    $idToken = $_POST['id_token'] ?? '';
    $payload = $idToken ? (jwt_decode_payload_unsafe($idToken) ?: []) : [];

    // 1) target explícito?
    $target = $_POST['target'] ?? ($_GET['target'] ?? '');
    $source = 'query_or_post.target';

    // 2) via roles do id_token
    $rolesIdToken = [];
    if ($target === '') {
        $rolesIdToken = lti_roles_from_idtoken($idToken);
        $fromRoles = decide_target_from_roles($rolesIdToken);
        if ($fromRoles !== null) {
            $target = $fromRoles;
            $source = 'id_token.roles';
        }
    }

    // 3) via cookie lti_role (se existir e ainda não decidido)
    $rolesCookie = [];
    if ($target === '') {
        if (!empty($_COOKIE['lti_role'])) {
            // aceita CSV simples ou única string
            $rolesCookie = array_filter(array_map('trim', explode(',', (string)$_COOKIE['lti_role'])));
            $fromCookie = decide_target_from_roles($rolesCookie);
            if ($fromCookie !== null) {
                $target = $fromCookie;
                $source = 'cookie.lti_role';
            }
        }
    }

    // 4) heurística por e-mail (se ainda não decidido)
    $iss   = (string)($payload['iss'] ?? '');
    $sub   = (string)($payload['sub'] ?? '');
    $name  = (string)($payload['name'] ?? (($payload['given_name'] ?? '') . ' ' . ($payload['family_name'] ?? '')));
    $email = (string)($payload['email'] ?? '');

    $emailTarget = null;
    if ($target === '') {
        $emailTarget = decide_target_from_email($email);
        if ($emailTarget !== null) {
            $target = $emailTarget;
            $source = 'email.heuristic';
        }
    }

    // 5) fallback final
    if ($target === '') {
        $target = 'aluno';
        $source = 'fallback';
    }

    $_SESSION['adapt_user'] = [
        'role_decided' => $target,
        'name'         => trim($name) ?: null,
        'email'        => $email ?: null,
        'sub'          => $sub ?: null,
        'iss'          => $iss ?: null,
    ];

    log_event('resolve_target', [
        'decided'        => $target,
        'source'         => $source,
        'roles_idtoken'  => $rolesIdToken ?: null,
        'roles_cookie'   => $rolesCookie ?: null,
        'email_target'   => $emailTarget,
        'query'          => $_GET,
        'post'           => array_merge($_POST, ['id_token' => '***', 'state' => '***']),
        'session'        => $_SESSION['adapt_user'],
    ]);

    // Redireciona para a tela
    if ($target === 'professor') { redirect(($basePrefix ?: '') . '/professor', 302); }
    if ($target === 'admin')     { redirect(($basePrefix ?: '') . '/admin', 302); }
    redirect(($basePrefix ?: '') . '/aluno', 302);
}

// ---------------------------
// Telas
// ---------------------------
if ($path === '/aluno')     { require __DIR__ . '/aluno/index.php';     exit; }
if ($path === '/professor') { require __DIR__ . '/professor/index.php'; exit; }
if ($path === '/admin')     { require __DIR__ . '/admin/index.php';     exit; }

// ---------------------------
// 404
// ---------------------------
http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo "Not found: " . htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
