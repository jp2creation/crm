<?php
declare(strict_types=1);

require_once __DIR__ . '/_api_runtime.php';

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  exit;
}

function crm_pages_out(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function crm_pages_permissions(PDO $pdo, int $userId): array {
  $stmt = $pdo->prepare('SELECT p.name FROM crm_permissions p JOIN crm_user_permissions up ON up.permission_id=p.id WHERE up.user_id=? ORDER BY p.sort_order,p.name');
  $stmt->execute([$userId]);
  return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function crm_pages_modules(PDO $pdo, int $userId): array {
  $stmt = $pdo->prepare('SELECT m.slug FROM crm_modules m JOIN crm_user_modules um ON um.module_id=m.id WHERE um.user_id=? AND m.active=1 ORDER BY m.sort_order,m.name');
  $stmt->execute([$userId]);
  return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function crm_pages_actor(PDO $pdo): array {
  $actorId = crm_api_actor_id();
  if ($actorId <= 0) crm_pages_out(['ok' => false, 'error' => 'Utilisateur CRM requis'], 401);

  $stmt = $pdo->prepare('SELECT id,name,role,active FROM crm_users WHERE id=? AND active=1');
  $stmt->execute([$actorId]);
  $actor = $stmt->fetch();
  if (!$actor) crm_pages_out(['ok' => false, 'error' => 'Utilisateur CRM introuvable'], 404);

  $actor['id'] = (int)$actor['id'];
  $actor['permissions'] = crm_pages_permissions($pdo, (int)$actor['id']);
  $actor['moduleSlugs'] = crm_pages_modules($pdo, (int)$actor['id']);
  return $actor;
}

function crm_pages_has_access(array $actor): bool {
  if (($actor['role'] ?? '') === 'admin') return true;
  $permissions = $actor['permissions'] ?? [];
  $modules = $actor['moduleSlugs'] ?? [];
  return in_array('pages-crm', $modules, true)
    && (in_array('pages.view', $permissions, true) || in_array('pages.manage', $permissions, true));
}

function crm_pages_row(array $page, bool $withContent = true): array {
  $row = [
    'id' => (int)$page['id'],
    'title' => $page['title'],
    'slug' => $page['slug'],
    'excerpt' => $page['excerpt'] ?? '',
    'iconKey' => $page['icon_key'] ?? 'article',
    'active' => (bool)$page['active'],
    'showInMenu' => (bool)$page['show_in_menu'],
    'sortOrder' => (int)$page['sort_order'],
    'routePath' => '/pages-crm/' . $page['slug'],
  ];
  if ($withContent) $row['content'] = $page['content'] ?? '';
  return $row;
}

try {
  $pdo = crm_api_db();
  $actor = crm_pages_actor($pdo);
  if (!crm_pages_has_access($actor)) {
    crm_pages_out(['ok' => false, 'error' => 'Module pages non autorise'], 403);
  }

  $action = $_GET['action'] ?? 'bootstrap';
  if ($action === 'health') crm_pages_out(['ok' => true, 'mode' => 'mysql']);

  if ($action === 'bootstrap') {
    $stmt = $pdo->query('SELECT * FROM crm_pages WHERE active=1 ORDER BY sort_order,title');
    $pages = $stmt->fetchAll();
    crm_pages_out([
      'ok' => true,
      'user' => $actor,
      'pages' => array_map(fn($page) => crm_pages_row($page, false), $pages),
    ]);
  }

  if ($action === 'page') {
    $slug = trim((string)($_GET['slug'] ?? ''));
    if ($slug === '') crm_pages_out(['ok' => false, 'error' => 'Page invalide'], 400);
    $stmt = $pdo->prepare('SELECT * FROM crm_pages WHERE slug=? AND active=1 LIMIT 1');
    $stmt->execute([$slug]);
    $page = $stmt->fetch();
    if (!$page) crm_pages_out(['ok' => false, 'error' => 'Page introuvable'], 404);
    crm_pages_out(['ok' => true, 'page' => crm_pages_row($page, true)]);
  }

  crm_pages_out(['ok' => false, 'error' => 'Action inconnue'], 404);
} catch (Throwable $error) {
  crm_pages_out(crm_api_exception_response($error), 500);
}
