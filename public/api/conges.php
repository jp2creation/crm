<?php
declare(strict_types=1);

require_once __DIR__ . '/_api_runtime.php';

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Headers: Content-Type, X-CRM-User-Id');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  exit;
}

function leaves_out(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function leaves_body(): array {
  $body = json_decode((string)file_get_contents('php://input'), true);
  return is_array($body) ? $body : [];
}

function leaves_config(): array {
  $files = [
    dirname(__DIR__, 3) . '/crm_private/config.php',
    dirname(__DIR__, 2) . '/crm_private/config.php',
  ];
  foreach ($files as $file) {
    if (is_file($file)) {
      $config = require $file;
      if (is_array($config)) return $config;
    }
  }
  leaves_out(['ok' => false, 'error' => 'Configuration API manquante'], 500);
}

function leaves_db(): PDO {
  $db = leaves_config()['db'] ?? [];
  $host = $db['host'] ?? 'localhost';
  $port = (int)($db['port'] ?? 3306);
  $name = $db['database'] ?? '';
  $charset = $db['charset'] ?? 'utf8mb4';
  $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
  return new PDO($dsn, $db['username'] ?? '', $db['password'] ?? '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
}

function leaves_slug(string $value): string {
  $value = strtolower(trim($value));
  if (function_exists('iconv')) $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
  $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: 'salarie';
  return trim($value, '-');
}

function leaves_schema(PDO $pdo): void {
  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_leave_employees (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crm_user_id BIGINT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    color VARCHAR(20) NOT NULL DEFAULT "#f59e0b",
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 100,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX crm_leave_employees_user_idx (crm_user_id),
    INDEX crm_leave_employees_active_idx (active, sort_order)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_leave_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    type VARCHAR(40) NOT NULL DEFAULT "conge",
    period VARCHAR(20) NOT NULL DEFAULT "full",
    status VARCHAR(30) NOT NULL DEFAULT "approved",
    notes TEXT NULL,
    source VARCHAR(80) NOT NULL DEFAULT "crm",
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX crm_leave_entries_employee_idx (employee_id, start_date, end_date),
    INDEX crm_leave_entries_dates_idx (start_date, end_date),
    INDEX crm_leave_entries_status_idx (status, type)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

function leaves_actor_id(array $body = []): int {
  return crm_api_actor_id($body);
}

function leaves_user_permissions(PDO $pdo, int $userId): array {
  $stmt = $pdo->prepare('SELECT p.name FROM crm_permissions p JOIN crm_user_permissions up ON up.permission_id=p.id WHERE up.user_id=? ORDER BY p.sort_order,p.name');
  $stmt->execute([$userId]);
  return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function leaves_user_modules(PDO $pdo, int $userId): array {
  $stmt = $pdo->prepare('SELECT module_id FROM crm_user_modules WHERE user_id=? ORDER BY module_id');
  $stmt->execute([$userId]);
  return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function leaves_actor(PDO $pdo, array $body = []): array {
  $actorId = leaves_actor_id($body);
  if ($actorId <= 0) leaves_out(['ok' => false, 'error' => 'Utilisateur CRM requis'], 401);

  $stmt = $pdo->prepare('SELECT id,name,role,active FROM crm_users WHERE id=? AND active=1');
  $stmt->execute([$actorId]);
  $actor = $stmt->fetch();
  if (!$actor) leaves_out(['ok' => false, 'error' => 'Utilisateur CRM introuvable'], 404);

  $actor['id'] = (int)$actor['id'];
  $actor['moduleIds'] = leaves_user_modules($pdo, (int)$actor['id']);
  $actor['permissions'] = leaves_user_permissions($pdo, (int)$actor['id']);
  return $actor;
}

function leaves_has_permission(array $actor, string $permission): bool {
  return in_array($permission, $actor['permissions'] ?? [], true);
}

function leaves_require_permission(array $actor, string $permission): void {
  if (!leaves_has_permission($actor, $permission)) {
    leaves_out(['ok' => false, 'error' => 'Droit insuffisant : ' . $permission], 403);
  }
}

function leaves_require_module(PDO $pdo, array $actor): void {
  $stmt = $pdo->prepare('SELECT id FROM crm_modules WHERE slug="conges" AND active=1');
  $stmt->execute();
  $moduleId = (int)($stmt->fetchColumn() ?: 0);
  if ($moduleId <= 0 || !in_array($moduleId, $actor['moduleIds'] ?? [], true)) {
    leaves_out(['ok' => false, 'error' => 'Module non autorise : conges'], 403);
  }
}

function leaves_bool(mixed $value, bool $default = true): bool {
  if ($value === null) return $default;
  if (is_bool($value)) return $value;
  return in_array(strtolower((string)$value), ['1', 'true', 'yes', 'on'], true);
}

function leaves_date(string $value, string $field): string {
  $value = trim($value);
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
    leaves_out(['ok' => false, 'error' => 'Date invalide : ' . $field], 400);
  }
  [$year, $month, $day] = array_map('intval', explode('-', $value));
  if (!checkdate($month, $day, $year)) {
    leaves_out(['ok' => false, 'error' => 'Date invalide : ' . $field], 400);
  }
  return $value;
}

function leaves_color(string $value): string {
  $value = trim($value);
  if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) return strtolower($value);
  return '#f59e0b';
}

function leaves_employee_row(array $row): array {
  return [
    'id' => (int)$row['id'],
    'crmUserId' => $row['crm_user_id'] !== null ? (int)$row['crm_user_id'] : null,
    'name' => $row['name'],
    'slug' => $row['slug'],
    'color' => $row['color'] ?: '#f59e0b',
    'active' => (bool)$row['active'],
    'sortOrder' => (int)$row['sort_order'],
  ];
}

function leaves_entry_row(array $row): array {
  return [
    'id' => (int)$row['id'],
    'employeeId' => (int)$row['employee_id'],
    'employeeName' => $row['employee_name'] ?? '',
    'employeeColor' => $row['employee_color'] ?? '#f59e0b',
    'startDate' => $row['start_date'],
    'endDate' => $row['end_date'],
    'type' => $row['type'] ?: 'conge',
    'period' => $row['period'] ?: 'full',
    'status' => $row['status'] ?: 'approved',
    'notes' => $row['notes'] ?? '',
    'source' => $row['source'] ?? 'crm',
  ];
}

function leaves_fetch_entry(PDO $pdo, int $id): array {
  $stmt = $pdo->prepare('
    SELECT l.*, e.name employee_name, e.color employee_color
    FROM crm_leave_entries l
    JOIN crm_leave_employees e ON e.id=l.employee_id
    WHERE l.id=?
  ');
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  if (!$row) leaves_out(['ok' => false, 'error' => 'Conge introuvable'], 404);
  return leaves_entry_row($row);
}

function leaves_bootstrap(PDO $pdo, array $actor): array {
  leaves_require_module($pdo, $actor);
  leaves_require_permission($actor, 'conges.view');

  $employees = $pdo->query('SELECT * FROM crm_leave_employees ORDER BY active DESC,sort_order,name')->fetchAll();
  $leaves = $pdo->query('
    SELECT l.*, e.name employee_name, e.color employee_color
    FROM crm_leave_entries l
    JOIN crm_leave_employees e ON e.id=l.employee_id
    ORDER BY l.start_date,l.end_date,e.sort_order,e.name
  ')->fetchAll();

  return [
    'ok' => true,
    'user' => [
      'id' => (int)$actor['id'],
      'name' => $actor['name'],
      'role' => $actor['role'],
      'permissions' => $actor['permissions'] ?? [],
      'canManage' => leaves_has_permission($actor, 'conges.manage'),
    ],
    'employees' => array_map('leaves_employee_row', $employees),
    'leaves' => array_map('leaves_entry_row', $leaves),
    'types' => [
      ['value' => 'conge', 'label' => 'Conge', 'color' => '#facc15'],
      ['value' => 'rtt', 'label' => 'RTT', 'color' => '#38bdf8'],
      ['value' => 'absence', 'label' => 'Absence', 'color' => '#fb7185'],
      ['value' => 'formation', 'label' => 'Formation', 'color' => '#a78bfa'],
      ['value' => 'maladie', 'label' => 'Maladie', 'color' => '#94a3b8'],
    ],
    'periods' => [
      ['value' => 'full', 'label' => 'Journee'],
      ['value' => 'morning', 'label' => 'Matin'],
      ['value' => 'afternoon', 'label' => 'Apres-midi'],
    ],
  ];
}

function leaves_save_employee(PDO $pdo, array $actor, array $body): array {
  leaves_require_module($pdo, $actor);
  leaves_require_permission($actor, 'conges.manage');

  $id = max(0, (int)($body['id'] ?? 0));
  $name = trim((string)($body['name'] ?? ''));
  if ($name === '') leaves_out(['ok' => false, 'error' => 'Nom salarie requis'], 400);

  $baseSlug = leaves_slug((string)($body['slug'] ?? $name));
  $slug = $baseSlug;
  $suffix = 2;
  while (true) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM crm_leave_employees WHERE slug=? AND id<>?');
    $stmt->execute([$slug, $id]);
    if ((int)$stmt->fetchColumn() === 0) break;
    $slug = $baseSlug . '-' . $suffix;
    $suffix++;
  }

  $crmUserId = (int)($body['crmUserId'] ?? $body['crm_user_id'] ?? 0);
  if ($crmUserId <= 0) $crmUserId = null;
  $color = leaves_color((string)($body['color'] ?? '#f59e0b'));
  $active = leaves_bool($body['active'] ?? null, true) ? 1 : 0;
  $sortOrder = max(0, min(999, (int)($body['sortOrder'] ?? $body['sort_order'] ?? 100)));

  if ($id > 0) {
    $stmt = $pdo->prepare('UPDATE crm_leave_employees SET crm_user_id=?,name=?,slug=?,color=?,active=?,sort_order=?,updated_at=NOW() WHERE id=?');
    $stmt->execute([$crmUserId, $name, $slug, $color, $active, $sortOrder, $id]);
  } else {
    $stmt = $pdo->prepare('INSERT INTO crm_leave_employees(crm_user_id,name,slug,color,active,sort_order,created_at,updated_at) VALUES(?,?,?,?,?,?,NOW(),NOW())');
    $stmt->execute([$crmUserId, $name, $slug, $color, $active, $sortOrder]);
    $id = (int)$pdo->lastInsertId();
  }

  $stmt = $pdo->prepare('SELECT * FROM crm_leave_employees WHERE id=?');
  $stmt->execute([$id]);
  return ['ok' => true, 'employee' => leaves_employee_row($stmt->fetch())];
}

function leaves_delete_employee(PDO $pdo, array $actor, array $body): array {
  leaves_require_module($pdo, $actor);
  leaves_require_permission($actor, 'conges.manage');

  $id = (int)($body['id'] ?? 0);
  if ($id <= 0) leaves_out(['ok' => false, 'error' => 'Salarie requis'], 400);

  $stmt = $pdo->prepare('SELECT COUNT(*) FROM crm_leave_entries WHERE employee_id=?');
  $stmt->execute([$id]);
  if ((int)$stmt->fetchColumn() > 0) {
    $pdo->prepare('UPDATE crm_leave_employees SET active=0,updated_at=NOW() WHERE id=?')->execute([$id]);
    return ['ok' => true, 'archived' => true];
  }

  $pdo->prepare('DELETE FROM crm_leave_employees WHERE id=?')->execute([$id]);
  return ['ok' => true, 'deleted' => true];
}

function leaves_save_leave(PDO $pdo, array $actor, array $body): array {
  leaves_require_module($pdo, $actor);
  leaves_require_permission($actor, 'conges.manage');

  $id = max(0, (int)($body['id'] ?? 0));
  $employeeId = (int)($body['employeeId'] ?? $body['employee_id'] ?? 0);
  if ($employeeId <= 0) leaves_out(['ok' => false, 'error' => 'Salarie requis'], 400);

  $startDate = leaves_date((string)($body['startDate'] ?? $body['start_date'] ?? ''), 'debut');
  $endDate = leaves_date((string)($body['endDate'] ?? $body['end_date'] ?? $startDate), 'fin');
  if ($endDate < $startDate) leaves_out(['ok' => false, 'error' => 'La date de fin doit etre apres le debut'], 400);

  $span = (new DateTimeImmutable($startDate))->diff(new DateTimeImmutable($endDate))->days;
  if ($span > 370) leaves_out(['ok' => false, 'error' => 'Periode trop longue'], 400);

  $stmt = $pdo->prepare('SELECT id FROM crm_leave_employees WHERE id=? AND active=1');
  $stmt->execute([$employeeId]);
  if (!$stmt->fetchColumn()) leaves_out(['ok' => false, 'error' => 'Salarie introuvable'], 404);

  $types = ['conge', 'rtt', 'absence', 'formation', 'maladie'];
  $periods = ['full', 'morning', 'afternoon'];
  $statuses = ['approved', 'planned', 'pending', 'refused'];
  $type = in_array((string)($body['type'] ?? 'conge'), $types, true) ? (string)$body['type'] : 'conge';
  $period = in_array((string)($body['period'] ?? 'full'), $periods, true) ? (string)$body['period'] : 'full';
  $status = in_array((string)($body['status'] ?? 'approved'), $statuses, true) ? (string)$body['status'] : 'approved';
  $notes = trim((string)($body['notes'] ?? ''));

  $stmt = $pdo->prepare('
    SELECT id FROM crm_leave_entries
    WHERE employee_id=? AND id<>? AND NOT(end_date < ? OR start_date > ?)
    LIMIT 1
  ');
  $stmt->execute([$employeeId, $id, $startDate, $endDate]);
  if ($stmt->fetchColumn()) leaves_out(['ok' => false, 'error' => 'Un conge existe deja sur cette periode'], 409);

  if ($id > 0) {
    $stmt = $pdo->prepare('UPDATE crm_leave_entries SET employee_id=?,start_date=?,end_date=?,type=?,period=?,status=?,notes=?,updated_by=?,updated_at=NOW() WHERE id=?');
    $stmt->execute([$employeeId, $startDate, $endDate, $type, $period, $status, $notes, (int)$actor['id'], $id]);
  } else {
    $stmt = $pdo->prepare('INSERT INTO crm_leave_entries(employee_id,start_date,end_date,type,period,status,notes,source,created_by,updated_by,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
    $stmt->execute([$employeeId, $startDate, $endDate, $type, $period, $status, $notes, 'crm', (int)$actor['id'], (int)$actor['id']]);
    $id = (int)$pdo->lastInsertId();
  }

  return ['ok' => true, 'leave' => leaves_fetch_entry($pdo, $id)];
}

function leaves_delete_leave(PDO $pdo, array $actor, array $body): array {
  leaves_require_module($pdo, $actor);
  leaves_require_permission($actor, 'conges.manage');

  $id = (int)($body['id'] ?? 0);
  if ($id <= 0) leaves_out(['ok' => false, 'error' => 'Conge requis'], 400);
  $pdo->prepare('DELETE FROM crm_leave_entries WHERE id=?')->execute([$id]);
  return ['ok' => true, 'deleted' => true];
}

try {
  $pdo = leaves_db();
  leaves_schema($pdo);
  $body = leaves_body();
  $actor = leaves_actor($pdo, $body);
  $action = (string)($_GET['action'] ?? 'bootstrap');

  if ($action === 'bootstrap') leaves_out(leaves_bootstrap($pdo, $actor));
  if ($action === 'save_employee') leaves_out(leaves_save_employee($pdo, $actor, $body));
  if ($action === 'delete_employee') leaves_out(leaves_delete_employee($pdo, $actor, $body));
  if ($action === 'save_leave') leaves_out(leaves_save_leave($pdo, $actor, $body));
  if ($action === 'delete_leave') leaves_out(leaves_delete_leave($pdo, $actor, $body));

  leaves_out(['ok' => false, 'error' => 'Action inconnue'], 404);
} catch (Throwable $error) {
  error_log('[crm-leaves] ' . $error->getMessage());
  leaves_out([
    'ok' => false,
    'error' => crm_api_debug() ? $error->getMessage() : 'Erreur API conges',
  ], 500);
}
