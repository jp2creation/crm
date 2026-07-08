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

function crm_out(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function crm_body(): array {
  $body = json_decode((string)file_get_contents('php://input'), true);
  return is_array($body) ? $body : [];
}

function crm_config(): array {
  $files = [
    dirname(__DIR__, 3) . '/crm_private/config.php',
    dirname(__DIR__, 2) . '/crm_private/config.php',
  ];
  $file = null;
  foreach ($files as $candidate) {
    if (is_file($candidate)) {
      $file = $candidate;
      break;
    }
  }
  if (!$file) {
    crm_out(['ok' => false, 'error' => 'Configuration API manquante'], 500);
  }
  $config = require $file;
  if (!is_array($config)) {
    crm_out(['ok' => false, 'error' => 'Configuration API invalide'], 500);
  }
  return $config;
}

function crm_db(): PDO {
  $db = crm_config()['db'] ?? [];
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

function crm_slug(string $value): string {
  $value = strtolower(trim($value));
  if (function_exists('iconv')) {
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
  }
  $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: 'site';
  return trim($value, '-');
}

function crm_column_exists(PDO $pdo, string $table, string $column): bool {
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
  $stmt->execute([$table, $column]);
  return (int)$stmt->fetchColumn() > 0;
}

function crm_time5(?string $value, string $default): string {
  $value = trim((string)$value);
  if (preg_match('/^([0-2][0-9]:[0-5][0-9])/', $value, $match)) return $match[1];
  return $default;
}

function crm_minutes(string $time): int {
  [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));
  return ($hour * 60) + $minute;
}

function crm_site_hours(array $site): array {
  return [
    'morningStart' => crm_time5($site['morning_start'] ?? null, '07:30'),
    'morningEnd' => crm_time5($site['morning_end'] ?? null, '12:00'),
    'afternoonStart' => crm_time5($site['afternoon_start'] ?? null, '13:30'),
    'afternoonEnd' => crm_time5($site['afternoon_end'] ?? null, '17:30'),
  ];
}

function crm_fetch_site_hours(PDO $pdo, int $siteId): array {
  $stmt = $pdo->prepare('SELECT morning_start,morning_end,afternoon_start,afternoon_end FROM crm_sites WHERE id=?');
  $stmt->execute([$siteId]);
  return crm_site_hours($stmt->fetch() ?: []);
}

function crm_datetime_within_site_hours(string $startAt, string $endAt, array $hours): bool {
  $startMinute = crm_minutes(substr($startAt, 11, 5));
  $endMinute = crm_minutes(substr($endAt, 11, 5));
  $morningStart = crm_minutes($hours['morningStart']);
  $morningEnd = crm_minutes($hours['morningEnd']);
  $afternoonStart = crm_minutes($hours['afternoonStart']);
  $afternoonEnd = crm_minutes($hours['afternoonEnd']);
  $startAllowed = ($startMinute >= $morningStart && $startMinute < $morningEnd) || ($startMinute >= $afternoonStart && $startMinute < $afternoonEnd);
  $endAllowed = ($endMinute > $morningStart && $endMinute <= $morningEnd) || ($endMinute > $afternoonStart && $endMinute <= $afternoonEnd);
  return $startAllowed && $endAllowed && $startMinute >= $morningStart && $endMinute <= $afternoonEnd;
}

function crm_save_data_image(?string $dataUrl, string $folder): ?string {
  $dataUrl = trim((string)$dataUrl);
  if ($dataUrl === '') return null;
  if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,/', $dataUrl, $matches)) {
    crm_out(['ok' => false, 'error' => 'Photo invalide'], 400);
  }
  $binary = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
  if ($binary === false || strlen($binary) > 2 * 1024 * 1024) {
    crm_out(['ok' => false, 'error' => 'Photo trop lourde'], 400);
  }
  $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
  $dir = dirname(__DIR__) . '/assets/uploads/' . $folder;
  if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
    crm_out(['ok' => false, 'error' => 'Impossible de stocker la photo'], 500);
  }
  $name = date('YmdHis') . '-' . bin2hex(random_bytes(5)) . '.' . $ext;
  if (file_put_contents($dir . '/' . $name, $binary) === false) {
    crm_out(['ok' => false, 'error' => 'Impossible de stocker la photo'], 500);
  }
  return '/assets/uploads/' . $folder . '/' . $name;
}

function crm_schema(PDO $pdo): void {
  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_sites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    slug VARCHAR(140) NOT NULL UNIQUE,
    active TINYINT(1) NOT NULL DEFAULT 1,
    morning_start TIME NOT NULL DEFAULT "07:30:00",
    morning_end TIME NOT NULL DEFAULT "12:00:00",
    afternoon_start TIME NOT NULL DEFAULT "13:30:00",
    afternoon_end TIME NOT NULL DEFAULT "17:30:00",
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
  $siteHourColumns = [
    'morning_start' => 'ALTER TABLE crm_sites ADD COLUMN morning_start TIME NOT NULL DEFAULT "07:30:00" AFTER active',
    'morning_end' => 'ALTER TABLE crm_sites ADD COLUMN morning_end TIME NOT NULL DEFAULT "12:00:00" AFTER morning_start',
    'afternoon_start' => 'ALTER TABLE crm_sites ADD COLUMN afternoon_start TIME NOT NULL DEFAULT "13:30:00" AFTER morning_end',
    'afternoon_end' => 'ALTER TABLE crm_sites ADD COLUMN afternoon_end TIME NOT NULL DEFAULT "17:30:00" AFTER afternoon_start',
  ];
  foreach ($siteHourColumns as $column => $sql) {
    if (!crm_column_exists($pdo, 'crm_sites', $column)) $pdo->exec($sql);
  }
  if (!crm_column_exists($pdo, 'crm_sites', 'deleted_at')) {
    $pdo->exec('ALTER TABLE crm_sites ADD COLUMN deleted_at DATETIME NULL AFTER updated_at');
  }

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL UNIQUE,
    label VARCHAR(190) NOT NULL,
    group_label VARCHAR(80) NOT NULL,
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT "",
    route_path VARCHAR(160) DEFAULT "",
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL UNIQUE,
    role VARCHAR(40) NOT NULL DEFAULT "user",
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_user_sites (
    site_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    PRIMARY KEY (site_id, user_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_user_permissions (
    permission_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NULL,
    PRIMARY KEY (permission_id, user_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_user_modules (
    module_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NULL,
    PRIMARY KEY (module_id, user_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_vehicles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT "",
    color VARCHAR(20) DEFAULT "#95002e",
    photo_url VARCHAR(255) DEFAULT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX crm_vehicles_site_idx (site_id, active)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
  if (!crm_column_exists($pdo, 'crm_vehicles', 'photo_url')) {
    $pdo->exec('ALTER TABLE crm_vehicles ADD COLUMN photo_url VARCHAR(255) DEFAULT NULL AFTER color');
  }

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_reservations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    user_name VARCHAR(160) NOT NULL,
    title VARCHAR(190) DEFAULT "",
    contact_phone VARCHAR(40) DEFAULT "",
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX crm_reservations_vehicle_range_idx (vehicle_id, start_at, end_at),
    INDEX crm_reservations_site_start_idx (site_id, start_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    user_name VARCHAR(160) NULL,
    action VARCHAR(160) NOT NULL,
    details TEXT NULL,
    created_at DATETIME NOT NULL,
    ip VARCHAR(80) NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

function crm_permissions_seed(): array {
  return [
    ['reservations.view', 'Voir les reservations', 'Module', 10],
    ['reservations.create', 'Creer une reservation', 'Creation', 20],
    ['reservations.update_own', 'Modifier ses reservations', 'Modification', 30],
    ['reservations.update_any', 'Modifier toutes les reservations', 'Modification', 40],
    ['reservations.delete_own', 'Supprimer ses reservations', 'Suppression', 50],
    ['reservations.delete_any', 'Supprimer toutes les reservations', 'Suppression', 60],
    ['reservations.manage_vehicles', 'Gerer les vehicules du site', 'Administration', 70],
    ['equipment_rentals.view', 'Voir les locations materiel', 'Location materiel', 80],
    ['equipment_rentals.create', 'Creer une location materiel', 'Location materiel', 90],
    ['equipment_rentals.update_own', 'Modifier ses locations materiel', 'Location materiel', 100],
    ['equipment_rentals.update_any', 'Modifier toutes les locations materiel', 'Location materiel', 110],
    ['equipment_rentals.delete_own', 'Supprimer ses locations materiel', 'Location materiel', 120],
    ['equipment_rentals.delete_any', 'Supprimer toutes les locations materiel', 'Location materiel', 130],
    ['equipment_rentals.manage_items', 'Gerer le materiel de location', 'Location materiel', 140],
    ['platform.manage_sites', 'Gerer les sites', 'Administration', 150],
    ['platform.manage_users', 'Gerer les utilisateurs', 'Administration', 160],
    ['platform.manage_roles', 'Gerer les roles', 'Administration', 170],
    ['platform.manage_modules', 'Gerer les modules', 'Administration', 180],
  ];
}

function crm_permission_names_for_role(string $role): array {
  if ($role === 'admin') return array_map(fn($permission) => $permission[0], crm_permissions_seed());
  if ($role === 'responsable') {
    return [
      'reservations.view',
      'reservations.create',
      'reservations.update_own',
      'reservations.update_any',
      'reservations.delete_own',
      'reservations.delete_any',
      'reservations.manage_vehicles',
      'equipment_rentals.view',
      'equipment_rentals.create',
      'equipment_rentals.update_own',
      'equipment_rentals.update_any',
      'equipment_rentals.delete_own',
      'equipment_rentals.delete_any',
      'equipment_rentals.manage_items',
    ];
  }
  if ($role === 'blocked') return [];
  return ['reservations.view', 'reservations.create', 'reservations.update_own', 'reservations.delete_own', 'equipment_rentals.view', 'equipment_rentals.create', 'equipment_rentals.update_own', 'equipment_rentals.delete_own'];
}

function crm_ensure_site(PDO $pdo, string $name): int {
  $stmt = $pdo->prepare('SELECT id FROM crm_sites WHERE name=? AND deleted_at IS NULL');
  $stmt->execute([$name]);
  $id = $stmt->fetchColumn();
  if ($id) return (int)$id;

  $stmt = $pdo->prepare('INSERT INTO crm_sites(name,slug,active,created_at) VALUES(?,?,1,NOW())');
  $stmt->execute([$name, crm_slug($name)]);
  return (int)$pdo->lastInsertId();
}

function crm_ensure_permission(PDO $pdo, array $permission): int {
  [$name, $label, $group, $sortOrder] = $permission;
  $stmt = $pdo->prepare('SELECT id FROM crm_permissions WHERE name=?');
  $stmt->execute([$name]);
  $id = $stmt->fetchColumn();
  if ($id) {
    $stmt = $pdo->prepare('UPDATE crm_permissions SET label=?,group_label=?,sort_order=?,updated_at=NOW() WHERE id=?');
    $stmt->execute([$label, $group, $sortOrder, (int)$id]);
    return (int)$id;
  }

  $stmt = $pdo->prepare('INSERT INTO crm_permissions(name,label,group_label,sort_order,created_at) VALUES(?,?,?,?,NOW())');
  $stmt->execute([$name, $label, $group, $sortOrder]);
  return (int)$pdo->lastInsertId();
}

function crm_ensure_module(PDO $pdo, string $name, string $slug, string $description, string $routePath, int $sortOrder): int {
  $stmt = $pdo->prepare('SELECT id FROM crm_modules WHERE slug=?');
  $stmt->execute([$slug]);
  $id = $stmt->fetchColumn();
  if ($id) return (int)$id;

  $stmt = $pdo->prepare('INSERT INTO crm_modules(name,slug,description,route_path,active,sort_order,created_at) VALUES(?,?,?,?,1,?,NOW())');
  $stmt->execute([$name, $slug, $description, $routePath, $sortOrder]);
  return (int)$pdo->lastInsertId();
}

function crm_ensure_user(PDO $pdo, string $name, string $role): int {
  $stmt = $pdo->prepare('SELECT id FROM crm_users WHERE name=?');
  $stmt->execute([$name]);
  $id = $stmt->fetchColumn();
  if ($id) return (int)$id;

  $stmt = $pdo->prepare('INSERT INTO crm_users(name,role,active,created_at) VALUES(?,?,1,NOW())');
  $stmt->execute([$name, $role]);
  return (int)$pdo->lastInsertId();
}

function crm_link_user_site(PDO $pdo, int $userId, int $siteId, bool $isDefault = true): void {
  $stmt = $pdo->prepare('INSERT IGNORE INTO crm_user_sites(site_id,user_id,is_default,created_at) VALUES(?,?,?,NOW())');
  $stmt->execute([$siteId, $userId, $isDefault ? 1 : 0]);
}

function crm_link_user_module(PDO $pdo, int $userId, int $moduleId): void {
  $stmt = $pdo->prepare('INSERT IGNORE INTO crm_user_modules(module_id,user_id,created_at) VALUES(?,?,NOW())');
  $stmt->execute([$moduleId, $userId]);
}

function crm_sync_user_permissions(PDO $pdo, int $userId, string $role, array $permissionIds): void {
  $insert = $pdo->prepare('INSERT IGNORE INTO crm_user_permissions(permission_id,user_id,created_at) VALUES(?,?,NOW())');
  foreach (crm_permission_names_for_role($role) as $name) {
    if (isset($permissionIds[$name])) $insert->execute([(int)$permissionIds[$name], $userId]);
  }
}

function crm_cleanup_legacy_permissions(PDO $pdo): void {
  $stmt = $pdo->prepare('SELECT id FROM crm_permissions WHERE name=?');
  $stmt->execute(['reservations.manage_sites']);
  $legacyId = (int)($stmt->fetchColumn() ?: 0);
  if (!$legacyId) return;

  $pdo->prepare('DELETE FROM crm_user_permissions WHERE permission_id=?')->execute([$legacyId]);
  $pdo->prepare('DELETE FROM crm_permissions WHERE id=?')->execute([$legacyId]);
}

function crm_ensure_vehicle(PDO $pdo, string $name, string $description, string $color, int $siteId): int {
  $stmt = $pdo->prepare('SELECT id FROM crm_vehicles WHERE name=?');
  $stmt->execute([$name]);
  $id = $stmt->fetchColumn();
  if ($id) return (int)$id;

  $stmt = $pdo->prepare('INSERT INTO crm_vehicles(site_id,name,description,color,active,created_at) VALUES(?,?,?,?,1,NOW())');
  $stmt->execute([$siteId, $name, $description, $color]);
  return (int)$pdo->lastInsertId();
}

function crm_seed(PDO $pdo): void {
  $permissionIds = [];
  foreach (crm_permissions_seed() as $permission) {
    $permissionIds[$permission[0]] = crm_ensure_permission($pdo, $permission);
  }
  crm_cleanup_legacy_permissions($pdo);

  $siteIds = [];
  $existingSites = $pdo->query('SELECT id,name FROM crm_sites WHERE deleted_at IS NULL ORDER BY id')->fetchAll();
  if (!$existingSites) {
    foreach (['Palissy', 'Bordeaux', 'Pessac', 'Glotin', 'Pastel'] as $siteName) {
      $siteIds[$siteName] = crm_ensure_site($pdo, $siteName);
    }
  } else {
    foreach ($existingSites as $site) {
      $siteIds[(string)$site['name']] = (int)$site['id'];
    }
  }
  $defaultSiteId = (int)($siteIds['Palissy'] ?? reset($siteIds));

  $moduleIds = [
    'reservations' => crm_ensure_module($pdo, 'Reservations vehicules', 'reservations', 'Planning et reservations des vehicules', '/reservations', 10),
    'locations-materiel' => crm_ensure_module($pdo, 'Location materiel', 'locations-materiel', 'Planning et locations du materiel interne', '/locations-materiel', 15),
    'administration' => crm_ensure_module($pdo, 'Administration', 'administration', 'Gestion des sites, modules, utilisateurs et roles', '/administration', 20),
    'planning' => crm_ensure_module($pdo, 'Planning', 'planning', 'Planning interne par site', '/planning', 30),
    'documents' => crm_ensure_module($pdo, 'Documents internes', 'documents', 'Procedures et documents partages', '/documents', 40),
    'demandes' => crm_ensure_module($pdo, 'Demandes internes', 'demandes', 'Demandes et validations internes', '/demandes', 50),
    'tapis-romus' => crm_ensure_module($pdo, 'Tapis ROMUS', 'tapis-romus', 'Bon de commande et mesures tapis ROMUS', '/tapis-romus', 60),
  ];

  $users = [
    ['J-Philippe', 'admin', array_values($siteIds), array_values($moduleIds)],
    ['Christophe L', 'user', [$defaultSiteId], [$moduleIds['reservations'], $moduleIds['locations-materiel']]],
    ['Remi G', 'user', [$defaultSiteId], [$moduleIds['reservations'], $moduleIds['locations-materiel']]],
    ['Samy I', 'user', [$defaultSiteId], [$moduleIds['reservations'], $moduleIds['locations-materiel']]],
    ['Philippe P', 'responsable', [$defaultSiteId], [$moduleIds['reservations'], $moduleIds['locations-materiel']]],
    ['Jeremy L', 'blocked', [$defaultSiteId], []],
  ];

  foreach ($users as [$name, $role, $sites, $modules]) {
    $userId = crm_ensure_user($pdo, $name, $role);
    foreach ($sites as $index => $siteId) {
      crm_link_user_site($pdo, $userId, (int)$siteId, $index === 0);
    }
    if ($role === 'blocked') {
      $pdo->prepare('DELETE FROM crm_user_modules WHERE user_id=?')->execute([$userId]);
      $pdo->prepare('DELETE FROM crm_user_permissions WHERE user_id=?')->execute([$userId]);
      continue;
    }
    foreach ($modules as $moduleId) {
      crm_link_user_module($pdo, $userId, (int)$moduleId);
    }
    crm_sync_user_permissions($pdo, $userId, $role, $permissionIds);
  }

  $palissyId = (int)($siteIds['Palissy'] ?? $defaultSiteId);
  $bordeauxId = (int)($siteIds['Bordeaux'] ?? $palissyId);
  $sprinterId = crm_ensure_vehicle($pdo, 'Sprinter', 'Vehicule principal Martin Sols', '#f59e0b', $palissyId);
  crm_ensure_vehicle($pdo, 'Boxer chantier', 'Fourgon reserve aux chantiers longs', '#95002e', $palissyId);
  crm_ensure_vehicle($pdo, 'Transit Bordeaux', 'Vehicule livraison Bordeaux', '#2563eb', $bordeauxId);

  if ((int)$pdo->query('SELECT COUNT(*) FROM crm_reservations')->fetchColumn() === 0) {
    $adminId = (int)$pdo->query('SELECT id FROM crm_users WHERE name="J-Philippe"')->fetchColumn();
    $samyId = (int)$pdo->query('SELECT id FROM crm_users WHERE name="Samy I"')->fetchColumn();
    $seed = [
      [7, $palissyId, $sprinterId, $adminId, 'J-Philippe', 'Tournee Palissy', '', '2026-07-22 07:50:00', '2026-07-22 09:00:00', ''],
      [9, $palissyId, $sprinterId, $adminId, 'J-Philippe', 'Preparation depot', '', '2026-07-22 06:50:00', '2026-07-22 07:50:00', ''],
      [8, $palissyId, $sprinterId, $adminId, 'J-Philippe', 'Chargement matin', '', '2026-07-22 06:00:00', '2026-07-22 06:50:00', ''],
      [10, $palissyId, $sprinterId, $adminId, 'J-Philippe', 'Retour chantier', '', '2026-07-03 19:00:00', '2026-07-03 23:00:00', ''],
      [5, $palissyId, $sprinterId, $samyId, 'Samy I', 'Livraison client', '', '2026-07-02 13:00:00', '2026-07-02 14:00:00', ''],
    ];
    $stmt = $pdo->prepare('INSERT INTO crm_reservations(id,site_id,vehicle_id,user_id,user_name,title,contact_phone,start_at,end_at,notes,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,NOW())');
    foreach ($seed as $reservation) $stmt->execute($reservation);
  }
}

function crm_actor_id(array $body = []): int {
  return crm_api_actor_id($body);
}

function crm_user_permissions(PDO $pdo, int $userId): array {
  $stmt = $pdo->prepare('
    SELECT p.name
    FROM crm_permissions p
    JOIN crm_user_permissions up ON up.permission_id=p.id
    WHERE up.user_id=?
    ORDER BY p.sort_order,p.name
  ');
  $stmt->execute([$userId]);
  return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function crm_user_sites(PDO $pdo, int $userId): array {
  $stmt = $pdo->prepare('
    SELECT us.site_id
    FROM crm_user_sites us
    JOIN crm_sites s ON s.id=us.site_id
    WHERE us.user_id=? AND s.deleted_at IS NULL
    ORDER BY us.is_default DESC,us.site_id
  ');
  $stmt->execute([$userId]);
  return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function crm_user_modules(PDO $pdo, int $userId): array {
  $stmt = $pdo->prepare('SELECT module_id FROM crm_user_modules WHERE user_id=? ORDER BY module_id');
  $stmt->execute([$userId]);
  return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function crm_actor(PDO $pdo, array $body = [], bool $allowFallback = false): array {
  $actorId = crm_actor_id($body);
  if ($actorId <= 0 && $allowFallback) {
    $actorId = (int)$pdo->query('SELECT id FROM crm_users WHERE role="admin" AND active=1 ORDER BY id LIMIT 1')->fetchColumn();
  }
  if ($actorId <= 0) crm_out(['ok' => false, 'error' => 'Utilisateur CRM requis'], 401);
  $stmt = $pdo->prepare('SELECT id,name,role,active FROM crm_users WHERE id=? AND active=1');
  $stmt->execute([$actorId]);
  $user = $stmt->fetch();
  if (!$user) crm_out(['ok' => false, 'error' => 'Utilisateur CRM introuvable'], 404);
  $user['id'] = (int)$user['id'];
  $user['siteIds'] = crm_user_sites($pdo, (int)$user['id']);
  if ($user['role'] === 'blocked') {
    $user['moduleIds'] = [];
    $user['permissions'] = [];
    return $user;
  }
  $user['moduleIds'] = crm_user_modules($pdo, (int)$user['id']);
  $user['permissions'] = crm_user_permissions($pdo, (int)$user['id']);
  return $user;
}

function crm_has_permission(array $user, string $permission): bool {
  return in_array($permission, $user['permissions'] ?? [], true);
}

function crm_require_permission(array $user, string $permission): void {
  if (!crm_has_permission($user, $permission)) {
    crm_out(['ok' => false, 'error' => 'Droit insuffisant : ' . $permission], 403);
  }
}

function crm_can_access_site(array $user, int $siteId): bool {
  return in_array($siteId, $user['siteIds'] ?? [], true);
}

function crm_can_access_module(PDO $pdo, array $user, string $slug): bool {
  $stmt = $pdo->prepare('SELECT id FROM crm_modules WHERE slug=? AND active=1');
  $stmt->execute([$slug]);
  $moduleId = (int)($stmt->fetchColumn() ?: 0);
  return $moduleId > 0 && in_array($moduleId, $user['moduleIds'] ?? [], true);
}

function crm_require_module(PDO $pdo, array $user, string $slug): void {
  if (!crm_can_access_module($pdo, $user, $slug)) {
    crm_out(['ok' => false, 'error' => 'Module non autorise : ' . $slug], 403);
  }
}

function crm_to_iso(?string $value): string {
  if (!$value) return '';
  return str_replace(' ', 'T', substr($value, 0, 16));
}

function crm_reservation_row(array $row): array {
  return [
    'id' => (int)$row['id'],
    'siteId' => (int)$row['site_id'],
    'vehicleId' => (int)$row['vehicle_id'],
    'userId' => (int)$row['user_id'],
    'userName' => $row['user_name'],
    'title' => $row['title'] ?? '',
    'contactPhone' => $row['contact_phone'] ?? '',
    'startAt' => crm_to_iso($row['start_at']),
    'endAt' => crm_to_iso($row['end_at']),
    'notes' => $row['notes'] ?? '',
  ];
}

function crm_vehicle_row(array $row): array {
  return [
    'id' => (int)$row['id'],
    'siteId' => (int)$row['site_id'],
    'name' => $row['name'],
    'description' => $row['description'] ?? '',
    'color' => $row['color'] ?? '#95002e',
    'photoUrl' => $row['photo_url'] ?? '',
    'active' => (bool)$row['active'],
  ];
}

function crm_bootstrap(PDO $pdo, array $actor): array {
  $sites = $pdo->query('SELECT id,name,slug,morning_start,morning_end,afternoon_start,afternoon_end FROM crm_sites WHERE active=1 AND deleted_at IS NULL ORDER BY id')->fetchAll();
  $modules = $pdo->query('SELECT id,name,slug,description,active,sort_order FROM crm_modules WHERE active=1 ORDER BY sort_order,name')->fetchAll();
  $vehicles = $pdo->query('SELECT id,site_id,name,description,color,photo_url,active FROM crm_vehicles WHERE active=1 ORDER BY site_id,name')->fetchAll();
  $reservations = $pdo->query('SELECT * FROM crm_reservations ORDER BY start_at,id')->fetchAll();
  $permissions = $pdo->query('SELECT name,label,group_label FROM crm_permissions ORDER BY sort_order,name')->fetchAll();
  $users = $pdo->query('SELECT id,name,role,active FROM crm_users WHERE active=1 ORDER BY id')->fetchAll();

  return [
    'ok' => true,
    'mode' => 'mysql',
    'user' => $actor,
    'sites' => array_map(fn($site) => [
      'id' => (int)$site['id'],
      'name' => $site['name'],
      'slug' => $site['slug'],
      'hours' => crm_site_hours($site),
    ], $sites),
    'modules' => array_map(fn($module) => [
      'id' => (int)$module['id'],
      'name' => $module['name'],
      'slug' => $module['slug'],
      'description' => $module['description'] ?? '',
      'active' => (bool)$module['active'],
      'sortOrder' => (int)$module['sort_order'],
    ], $modules),
    'vehicles' => array_map('crm_vehicle_row', $vehicles),
    'reservations' => array_map('crm_reservation_row', $reservations),
    'permissions' => array_map(fn($permission) => [
      'name' => $permission['name'],
      'label' => $permission['label'],
      'group' => $permission['group_label'],
    ], $permissions),
    'users' => array_map(function($user) use ($pdo) {
      $userId = (int)$user['id'];
      return [
        'id' => $userId,
        'name' => $user['name'],
        'role' => $user['role'],
        'siteIds' => crm_user_sites($pdo, $userId),
        'moduleIds' => crm_user_modules($pdo, $userId),
        'permissions' => crm_user_permissions($pdo, $userId),
      ];
    }, $users),
  ];
}

function crm_log(PDO $pdo, array $user, string $action, string $details = ''): void {
  $stmt = $pdo->prepare('INSERT INTO crm_logs(user_id,user_name,action,details,created_at,ip) VALUES(?,?,?,?,NOW(),?)');
  $stmt->execute([(int)$user['id'], $user['name'], $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
}

function crm_normalize_datetime(string $value): string {
  $value = str_replace('T', ' ', trim($value));
  $time = strtotime($value);
  if ($time === false) crm_out(['ok' => false, 'error' => 'Date invalide'], 400);
  return date('Y-m-d H:i:s', $time);
}

try {
  $pdo = crm_db();
  crm_schema($pdo);
  crm_seed($pdo);

  $action = $_GET['action'] ?? 'bootstrap';
  $body = crm_body();

  if ($action === 'health') {
    crm_out(['ok' => true, 'mode' => 'mysql']);
  }

  if ($action === 'bootstrap') {
    $actor = crm_actor($pdo, $body, false);
    crm_out(crm_bootstrap($pdo, $actor));
  }

  $actor = crm_actor($pdo, $body, false);

  if ($action === 'create_reservation') {
    crm_require_module($pdo, $actor, 'reservations');
    crm_require_permission($actor, 'reservations.create');
    $vehicleId = (int)($body['vehicleId'] ?? $body['vehicle_id'] ?? 0);
    $startAt = crm_normalize_datetime((string)($body['startAt'] ?? $body['start_at'] ?? ''));
    $endAt = crm_normalize_datetime((string)($body['endAt'] ?? $body['end_at'] ?? ''));
    $title = trim((string)($body['title'] ?? ''));
    $contactPhone = trim((string)($body['contactPhone'] ?? $body['contact_phone'] ?? ''));
    $notes = trim((string)($body['notes'] ?? ''));

    if (!$vehicleId || strtotime($endAt) <= strtotime($startAt)) {
      crm_out(['ok' => false, 'error' => 'Creneau invalide'], 400);
    }
    if (strlen($title) > 190) crm_out(['ok' => false, 'error' => 'Titre trop long'], 400);
    if (strlen($contactPhone) > 40) crm_out(['ok' => false, 'error' => 'Telephone trop long'], 400);

    $stmt = $pdo->prepare('SELECT id,site_id FROM crm_vehicles WHERE id=? AND active=1');
    $stmt->execute([$vehicleId]);
    $vehicle = $stmt->fetch();
    if (!$vehicle) crm_out(['ok' => false, 'error' => 'Vehicule introuvable'], 404);

    $siteId = (int)$vehicle['site_id'];
    if (!crm_can_access_site($actor, $siteId)) {
      crm_out(['ok' => false, 'error' => 'Site non autorise'], 403);
    }
    if (!crm_datetime_within_site_hours($startAt, $endAt, crm_fetch_site_hours($pdo, $siteId))) {
      crm_out(['ok' => false, 'error' => 'Creneau hors horaires du site'], 400);
    }

    $conflict = $pdo->prepare('SELECT COUNT(*) FROM crm_reservations WHERE vehicle_id=? AND NOT(end_at<=? OR start_at>=?)');
    $conflict->execute([$vehicleId, $startAt, $endAt]);
    if ((int)$conflict->fetchColumn() > 0) {
      crm_out(['ok' => false, 'error' => 'Le vehicule est deja reserve sur ce creneau'], 409);
    }

    $stmt = $pdo->prepare('INSERT INTO crm_reservations(site_id,vehicle_id,user_id,user_name,title,contact_phone,start_at,end_at,notes,created_at) VALUES(?,?,?,?,?,?,?,?,?,NOW())');
    $stmt->execute([$siteId, $vehicleId, (int)$actor['id'], $actor['name'], $title, $contactPhone, $startAt, $endAt, $notes]);
    $id = (int)$pdo->lastInsertId();
    crm_log($pdo, $actor, 'creation reservation', "Reservation #{$id}");

    $stmt = $pdo->prepare('SELECT * FROM crm_reservations WHERE id=?');
    $stmt->execute([$id]);
    crm_out(['ok' => true, 'reservation' => crm_reservation_row($stmt->fetch())]);
  }

  if ($action === 'update_reservation') {
    crm_require_module($pdo, $actor, 'reservations');
    $id = (int)($body['id'] ?? 0);
    $vehicleId = (int)($body['vehicleId'] ?? $body['vehicle_id'] ?? 0);
    $startAt = crm_normalize_datetime((string)($body['startAt'] ?? $body['start_at'] ?? ''));
    $endAt = crm_normalize_datetime((string)($body['endAt'] ?? $body['end_at'] ?? ''));
    $title = trim((string)($body['title'] ?? ''));
    $contactPhone = trim((string)($body['contactPhone'] ?? $body['contact_phone'] ?? ''));
    $notes = trim((string)($body['notes'] ?? ''));

    if (!$id) crm_out(['ok' => false, 'error' => 'Reservation invalide'], 400);
    if (!$vehicleId || strtotime($endAt) <= strtotime($startAt)) {
      crm_out(['ok' => false, 'error' => 'Creneau invalide'], 400);
    }
    if (strlen($title) > 190) crm_out(['ok' => false, 'error' => 'Titre trop long'], 400);
    if (strlen($contactPhone) > 40) crm_out(['ok' => false, 'error' => 'Telephone trop long'], 400);

    $stmt = $pdo->prepare('SELECT * FROM crm_reservations WHERE id=?');
    $stmt->execute([$id]);
    $reservation = $stmt->fetch();
    if (!$reservation) crm_out(['ok' => false, 'error' => 'Reservation introuvable'], 404);
    if (!crm_can_access_site($actor, (int)$reservation['site_id'])) {
      crm_out(['ok' => false, 'error' => 'Site non autorise'], 403);
    }

    $canUpdateAny = crm_has_permission($actor, 'reservations.update_any');
    $canUpdateOwn = crm_has_permission($actor, 'reservations.update_own') && (int)$reservation['user_id'] === (int)$actor['id'];
    if (!$canUpdateAny && !$canUpdateOwn) {
      crm_out(['ok' => false, 'error' => 'Modification non autorisee'], 403);
    }

    $stmt = $pdo->prepare('SELECT id,site_id FROM crm_vehicles WHERE id=? AND active=1');
    $stmt->execute([$vehicleId]);
    $vehicle = $stmt->fetch();
    if (!$vehicle) crm_out(['ok' => false, 'error' => 'Vehicule introuvable'], 404);

    $siteId = (int)$vehicle['site_id'];
    if (!crm_can_access_site($actor, $siteId)) {
      crm_out(['ok' => false, 'error' => 'Site non autorise'], 403);
    }
    if (!crm_datetime_within_site_hours($startAt, $endAt, crm_fetch_site_hours($pdo, $siteId))) {
      crm_out(['ok' => false, 'error' => 'Creneau hors horaires du site'], 400);
    }

    $conflict = $pdo->prepare('SELECT COUNT(*) FROM crm_reservations WHERE id<>? AND vehicle_id=? AND NOT(end_at<=? OR start_at>=?)');
    $conflict->execute([$id, $vehicleId, $startAt, $endAt]);
    if ((int)$conflict->fetchColumn() > 0) {
      crm_out(['ok' => false, 'error' => 'Le vehicule est deja reserve sur ce creneau'], 409);
    }

    $stmt = $pdo->prepare('UPDATE crm_reservations SET site_id=?,vehicle_id=?,title=?,contact_phone=?,start_at=?,end_at=?,notes=?,updated_at=NOW() WHERE id=?');
    $stmt->execute([$siteId, $vehicleId, $title, $contactPhone, $startAt, $endAt, $notes, $id]);
    crm_log($pdo, $actor, 'modification reservation', "Reservation #{$id}");

    $stmt = $pdo->prepare('SELECT * FROM crm_reservations WHERE id=?');
    $stmt->execute([$id]);
    crm_out(['ok' => true, 'reservation' => crm_reservation_row($stmt->fetch())]);
  }

  if ($action === 'save_vehicle') {
    crm_require_module($pdo, $actor, 'reservations');
    crm_require_permission($actor, 'reservations.manage_vehicles');

    $id = (int)($body['id'] ?? 0);
    $siteId = (int)($body['siteId'] ?? $body['site_id'] ?? 0);
    $name = trim((string)($body['name'] ?? ''));
    $description = trim((string)($body['description'] ?? ''));
    $color = trim((string)($body['color'] ?? '#95002e'));
    $photoDataUrl = (string)($body['photoDataUrl'] ?? $body['photo_data_url'] ?? '');

    if (!$siteId || !crm_can_access_site($actor, $siteId)) {
      crm_out(['ok' => false, 'error' => 'Site non autorise'], 403);
    }
    if ($name === '' || strlen($name) > 160) {
      crm_out(['ok' => false, 'error' => 'Nom de vehicule invalide'], 400);
    }
    if (strlen($description) > 255) {
      crm_out(['ok' => false, 'error' => 'Description trop longue'], 400);
    }
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
      $color = '#95002e';
    }

    $duplicate = $pdo->prepare('SELECT id FROM crm_vehicles WHERE name=? AND id<>? LIMIT 1');
    $duplicate->execute([$name, $id]);
    if ($duplicate->fetchColumn()) {
      crm_out(['ok' => false, 'error' => 'Un vehicule porte deja ce nom'], 409);
    }

    if ($id > 0) {
      $stmt = $pdo->prepare('SELECT * FROM crm_vehicles WHERE id=?');
      $stmt->execute([$id]);
      $vehicle = $stmt->fetch();
      if (!$vehicle) crm_out(['ok' => false, 'error' => 'Vehicule introuvable'], 404);
      if (!crm_can_access_site($actor, (int)$vehicle['site_id'])) {
        crm_out(['ok' => false, 'error' => 'Site non autorise'], 403);
      }
      $photoUrl = $vehicle['photo_url'] ?? '';
      if ($photoDataUrl !== '') $photoUrl = crm_save_data_image($photoDataUrl, 'vehicles') ?: $photoUrl;
      $stmt = $pdo->prepare('UPDATE crm_vehicles SET site_id=?,name=?,description=?,color=?,photo_url=?,active=1,updated_at=NOW() WHERE id=?');
      $stmt->execute([$siteId, $name, $description, $color, $photoUrl ?: null, $id]);
      crm_log($pdo, $actor, 'modification vehicule', "Vehicule #{$id}");
    } else {
      $photoUrl = $photoDataUrl !== '' ? crm_save_data_image($photoDataUrl, 'vehicles') : '';
      $stmt = $pdo->prepare('INSERT INTO crm_vehicles(site_id,name,description,color,photo_url,active,created_at) VALUES(?,?,?,?,?,1,NOW())');
      $stmt->execute([$siteId, $name, $description, $color, $photoUrl ?: null]);
      $id = (int)$pdo->lastInsertId();
      crm_log($pdo, $actor, 'creation vehicule', "Vehicule #{$id}");
    }

    $stmt = $pdo->prepare('SELECT * FROM crm_vehicles WHERE id=?');
    $stmt->execute([$id]);
    crm_out(['ok' => true, 'vehicle' => crm_vehicle_row($stmt->fetch())]);
  }

  if ($action === 'delete_vehicle') {
    crm_require_module($pdo, $actor, 'reservations');
    crm_require_permission($actor, 'reservations.manage_vehicles');

    $id = (int)($body['id'] ?? 0);
    if (!$id) crm_out(['ok' => false, 'error' => 'Vehicule invalide'], 400);

    $stmt = $pdo->prepare('SELECT * FROM crm_vehicles WHERE id=?');
    $stmt->execute([$id]);
    $vehicle = $stmt->fetch();
    if (!$vehicle) crm_out(['ok' => false, 'error' => 'Vehicule introuvable'], 404);
    if (!crm_can_access_site($actor, (int)$vehicle['site_id'])) {
      crm_out(['ok' => false, 'error' => 'Site non autorise'], 403);
    }

    $pdo->prepare('UPDATE crm_vehicles SET active=0,updated_at=NOW() WHERE id=?')->execute([$id]);
    crm_log($pdo, $actor, 'masquage vehicule', "Vehicule #{$id}");
    crm_out(['ok' => true, 'id' => $id]);
  }

  if ($action === 'delete_reservation') {
    crm_require_module($pdo, $actor, 'reservations');
    $id = (int)($body['id'] ?? 0);
    if (!$id) crm_out(['ok' => false, 'error' => 'Reservation invalide'], 400);

    $stmt = $pdo->prepare('SELECT * FROM crm_reservations WHERE id=?');
    $stmt->execute([$id]);
    $reservation = $stmt->fetch();
    if (!$reservation) crm_out(['ok' => false, 'error' => 'Reservation introuvable'], 404);
    if (!crm_can_access_site($actor, (int)$reservation['site_id'])) {
      crm_out(['ok' => false, 'error' => 'Site non autorise'], 403);
    }

    $canDeleteAny = crm_has_permission($actor, 'reservations.delete_any');
    $canDeleteOwn = crm_has_permission($actor, 'reservations.delete_own') && (int)$reservation['user_id'] === (int)$actor['id'];
    if (!$canDeleteAny && !$canDeleteOwn) {
      crm_out(['ok' => false, 'error' => 'Suppression non autorisee'], 403);
    }

    $pdo->prepare('DELETE FROM crm_reservations WHERE id=?')->execute([$id]);
    crm_log($pdo, $actor, 'suppression reservation', "Reservation #{$id}");
    crm_out(['ok' => true]);
  }

  crm_out(['ok' => false, 'error' => 'Action inconnue'], 404);
} catch (Throwable $error) {
  crm_out(crm_api_exception_response($error), 500);
}
