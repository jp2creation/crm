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

function equipment_out(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function equipment_body(): array {
  $body = json_decode((string)file_get_contents('php://input'), true);
  return is_array($body) ? $body : [];
}

function equipment_config(): array {
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
  if (!$file) equipment_out(['ok' => false, 'error' => 'Configuration API manquante'], 500);
  $config = require $file;
  if (!is_array($config)) equipment_out(['ok' => false, 'error' => 'Configuration API invalide'], 500);
  return $config;
}

function equipment_db(): PDO {
  $db = equipment_config()['db'] ?? [];
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

function equipment_slug(string $value): string {
  $value = strtolower(trim($value));
  if (function_exists('iconv')) $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
  $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: 'item';
  return trim($value, '-');
}

function equipment_column_exists(PDO $pdo, string $table, string $column): bool {
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
  $stmt->execute([$table, $column]);
  return (int)$stmt->fetchColumn() > 0;
}

function equipment_time5(?string $value, string $default): string {
  $value = trim((string)$value);
  if (preg_match('/^([0-2][0-9]:[0-5][0-9])/', $value, $match)) return $match[1];
  return $default;
}

function equipment_minutes(string $time): int {
  [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));
  return ($hour * 60) + $minute;
}

function equipment_site_hours(array $site): array {
  return [
    'morningStart' => equipment_time5($site['morning_start'] ?? null, '07:30'),
    'morningEnd' => equipment_time5($site['morning_end'] ?? null, '12:00'),
    'afternoonStart' => equipment_time5($site['afternoon_start'] ?? null, '13:30'),
    'afternoonEnd' => equipment_time5($site['afternoon_end'] ?? null, '17:30'),
  ];
}

function equipment_fetch_site_hours(PDO $pdo, int $siteId): array {
  $stmt = $pdo->prepare('SELECT morning_start,morning_end,afternoon_start,afternoon_end FROM crm_sites WHERE id=?');
  $stmt->execute([$siteId]);
  return equipment_site_hours($stmt->fetch() ?: []);
}

function equipment_datetime_within_site_hours(string $startAt, string $endAt, array $hours): bool {
  $startMinute = equipment_minutes(substr($startAt, 11, 5));
  $endMinute = equipment_minutes(substr($endAt, 11, 5));
  $morningStart = equipment_minutes($hours['morningStart']);
  $morningEnd = equipment_minutes($hours['morningEnd']);
  $afternoonStart = equipment_minutes($hours['afternoonStart']);
  $afternoonEnd = equipment_minutes($hours['afternoonEnd']);
  $startAllowed = ($startMinute >= $morningStart && $startMinute < $morningEnd) || ($startMinute >= $afternoonStart && $startMinute < $afternoonEnd);
  $endAllowed = ($endMinute > $morningStart && $endMinute <= $morningEnd) || ($endMinute > $afternoonStart && $endMinute <= $afternoonEnd);
  return $startAllowed && $endAllowed && $startMinute >= $morningStart && $endMinute <= $afternoonEnd;
}

function equipment_save_data_image(?string $dataUrl, string $folder): ?string {
  $dataUrl = trim((string)$dataUrl);
  if ($dataUrl === '') return null;
  if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,/', $dataUrl, $matches)) {
    equipment_out(['ok' => false, 'error' => 'Photo invalide'], 400);
  }
  $binary = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
  if ($binary === false || strlen($binary) > 2 * 1024 * 1024) {
    equipment_out(['ok' => false, 'error' => 'Photo trop lourde'], 400);
  }
  $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
  $dir = dirname(__DIR__) . '/assets/uploads/' . $folder;
  if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
    equipment_out(['ok' => false, 'error' => 'Impossible de stocker la photo'], 500);
  }
  $name = date('YmdHis') . '-' . bin2hex(random_bytes(5)) . '.' . $ext;
  if (file_put_contents($dir . '/' . $name, $binary) === false) {
    equipment_out(['ok' => false, 'error' => 'Impossible de stocker la photo'], 500);
  }
  return '/assets/uploads/' . $folder . '/' . $name;
}

function equipment_schema(PDO $pdo): void {
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
    if (!equipment_column_exists($pdo, 'crm_sites', $column)) $pdo->exec($sql);
  }
  if (!equipment_column_exists($pdo, 'crm_sites', 'deleted_at')) {
    $pdo->exec('ALTER TABLE crm_sites ADD COLUMN deleted_at DATETIME NULL AFTER updated_at');
  }

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT "",
    route_path VARCHAR(160) DEFAULT "",
    menu_badge VARCHAR(40) DEFAULT NULL,
    show_menu_badge TINYINT(1) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
  if (!equipment_column_exists($pdo, 'crm_modules', 'menu_badge')) {
    $pdo->exec('ALTER TABLE crm_modules ADD COLUMN menu_badge VARCHAR(40) DEFAULT NULL AFTER route_path');
  }
  if (!equipment_column_exists($pdo, 'crm_modules', 'show_menu_badge')) {
    $pdo->exec('ALTER TABLE crm_modules ADD COLUMN show_menu_badge TINYINT(1) NOT NULL DEFAULT 0 AFTER menu_badge');
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

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_menu_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_key VARCHAR(120) NOT NULL UNIQUE,
    group_key VARCHAR(80) NOT NULL,
    icon_key VARCHAR(80) DEFAULT "",
    label VARCHAR(160) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX crm_menu_items_group_idx (group_key, sort_order)
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

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_user_modules (
    module_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NULL,
    PRIMARY KEY (module_id, user_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_user_permissions (
    permission_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NULL,
    PRIMARY KEY (permission_id, user_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_equipment_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    slug VARCHAR(140) NOT NULL UNIQUE,
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_equipment_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    inventory_code VARCHAR(80) NULL,
    description VARCHAR(255) DEFAULT "",
    color VARCHAR(20) DEFAULT "#95002e",
    photo_url VARCHAR(255) DEFAULT NULL,
    half_day_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    day_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    deposit_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX crm_equipment_items_site_idx (site_id, active),
    INDEX crm_equipment_items_category_idx (category_id, active)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
  if (!equipment_column_exists($pdo, 'crm_equipment_items', 'photo_url')) {
    $pdo->exec('ALTER TABLE crm_equipment_items ADD COLUMN photo_url VARCHAR(255) DEFAULT NULL AFTER color');
  }

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_equipment_rentals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    equipment_item_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    user_name VARCHAR(160) NOT NULL,
    period_type VARCHAR(20) NOT NULL DEFAULT "half_day",
    slot VARCHAR(20) NOT NULL DEFAULT "morning",
    status VARCHAR(20) NOT NULL DEFAULT "reserved",
    title VARCHAR(190) DEFAULT "",
    contact_phone VARCHAR(40) DEFAULT "",
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX crm_equipment_rentals_item_range_idx (equipment_item_id, start_at, end_at),
    INDEX crm_equipment_rentals_site_start_idx (site_id, start_at),
    INDEX crm_equipment_rentals_status_idx (status, start_at)
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

function equipment_permissions_seed(): array {
  return [
    ['reservations.view', 'Voir les reservations', 'Reservations', 10],
    ['reservations.create', 'Creer une reservation', 'Reservations', 20],
    ['reservations.update_own', 'Modifier ses reservations', 'Reservations', 30],
    ['reservations.update_any', 'Modifier toutes les reservations', 'Reservations', 40],
    ['reservations.delete_own', 'Supprimer ses reservations', 'Reservations', 50],
    ['reservations.delete_any', 'Supprimer toutes les reservations', 'Reservations', 60],
    ['reservations.manage_vehicles', 'Gerer les vehicules du site', 'Reservations', 70],
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

function equipment_permission_names_for_role(string $role): array {
  if ($role === 'admin') return array_map(fn($permission) => $permission[0], equipment_permissions_seed());
  if ($role === 'responsable') {
    return [
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
  return ['equipment_rentals.view', 'equipment_rentals.create', 'equipment_rentals.update_own', 'equipment_rentals.delete_own'];
}

function equipment_ensure_permission(PDO $pdo, array $permission): int {
  [$name, $label, $group, $sortOrder] = $permission;
  $stmt = $pdo->prepare('SELECT id FROM crm_permissions WHERE name=?');
  $stmt->execute([$name]);
  $id = $stmt->fetchColumn();
  if ($id) {
    $pdo->prepare('UPDATE crm_permissions SET label=?,group_label=?,sort_order=?,updated_at=NOW() WHERE id=?')->execute([$label, $group, $sortOrder, (int)$id]);
    return (int)$id;
  }
  $stmt = $pdo->prepare('INSERT INTO crm_permissions(name,label,group_label,sort_order,created_at) VALUES(?,?,?,?,NOW())');
  $stmt->execute([$name, $label, $group, $sortOrder]);
  return (int)$pdo->lastInsertId();
}

function equipment_ensure_site(PDO $pdo, string $name): int {
  $stmt = $pdo->prepare('SELECT id FROM crm_sites WHERE name=? AND deleted_at IS NULL');
  $stmt->execute([$name]);
  $id = $stmt->fetchColumn();
  if ($id) return (int)$id;
  $stmt = $pdo->prepare('INSERT INTO crm_sites(name,slug,active,created_at) VALUES(?,?,1,NOW())');
  $stmt->execute([$name, equipment_slug($name)]);
  return (int)$pdo->lastInsertId();
}

function equipment_ensure_module(PDO $pdo, string $name, string $slug, string $description, string $routePath, int $sortOrder): int {
  $stmt = $pdo->prepare('SELECT id FROM crm_modules WHERE slug=?');
  $stmt->execute([$slug]);
  $id = $stmt->fetchColumn();
  if ($id) {
    $pdo->prepare('UPDATE crm_modules SET name=?,description=?,route_path=?,active=1,sort_order=?,updated_at=NOW() WHERE id=?')->execute([$name, $description, $routePath, $sortOrder, (int)$id]);
    return (int)$id;
  }
  $stmt = $pdo->prepare('INSERT INTO crm_modules(name,slug,description,route_path,active,sort_order,created_at) VALUES(?,?,?,?,1,?,NOW())');
  $stmt->execute([$name, $slug, $description, $routePath, $sortOrder]);
  return (int)$pdo->lastInsertId();
}

function equipment_ensure_menu_item(PDO $pdo, int $sortOrder): void {
  $stmt = $pdo->prepare('SELECT id FROM crm_menu_items WHERE item_key=?');
  $stmt->execute(['module:locations-materiel']);
  $id = $stmt->fetchColumn();
  if ($id) {
    $pdo->prepare('UPDATE crm_menu_items SET label=?,icon_key=IF(icon_key IS NULL OR icon_key="",?,icon_key),active=1,sort_order=?,updated_at=NOW() WHERE id=?')->execute(['Location materiel', 'package', $sortOrder, (int)$id]);
    return;
  }
  $stmt = $pdo->prepare('INSERT INTO crm_menu_items(item_key,group_key,icon_key,label,active,sort_order,created_at) VALUES(?,?,?,?,1,?,NOW())');
  $stmt->execute(['module:locations-materiel', 'internal', 'package', 'Location materiel', $sortOrder]);
}

function equipment_ensure_user(PDO $pdo, string $name, string $role): int {
  $stmt = $pdo->prepare('SELECT id FROM crm_users WHERE name=?');
  $stmt->execute([$name]);
  $id = $stmt->fetchColumn();
  if ($id) return (int)$id;
  $stmt = $pdo->prepare('INSERT INTO crm_users(name,role,active,created_at) VALUES(?,?,1,NOW())');
  $stmt->execute([$name, $role]);
  return (int)$pdo->lastInsertId();
}

function equipment_link_user_site(PDO $pdo, int $userId, int $siteId, bool $isDefault = true): void {
  $stmt = $pdo->prepare('INSERT IGNORE INTO crm_user_sites(site_id,user_id,is_default,created_at) VALUES(?,?,?,NOW())');
  $stmt->execute([$siteId, $userId, $isDefault ? 1 : 0]);
}

function equipment_link_user_module(PDO $pdo, int $userId, int $moduleId): void {
  $stmt = $pdo->prepare('INSERT IGNORE INTO crm_user_modules(module_id,user_id,created_at) VALUES(?,?,NOW())');
  $stmt->execute([$moduleId, $userId]);
}

function equipment_sync_user_permissions(PDO $pdo, int $userId, string $role, array $permissionIds): void {
  $insert = $pdo->prepare('INSERT IGNORE INTO crm_user_permissions(permission_id,user_id,created_at) VALUES(?,?,NOW())');
  foreach (equipment_permission_names_for_role($role) as $name) {
    if (isset($permissionIds[$name])) $insert->execute([(int)$permissionIds[$name], $userId]);
  }
}

function equipment_ensure_category(PDO $pdo, string $name, int $sortOrder): int {
  $slug = equipment_slug($name);
  $stmt = $pdo->prepare('SELECT id FROM crm_equipment_categories WHERE slug=?');
  $stmt->execute([$slug]);
  $id = $stmt->fetchColumn();
  if ($id) return (int)$id;
  $stmt = $pdo->prepare('INSERT INTO crm_equipment_categories(name,slug,active,sort_order,created_at) VALUES(?,?,1,?,NOW())');
  $stmt->execute([$name, $slug, $sortOrder]);
  return (int)$pdo->lastInsertId();
}

function equipment_ensure_item(PDO $pdo, int $siteId, int $categoryId, string $name, string $code, string $description, string $color, float $halfDayPrice, float $dayPrice, float $deposit, int $sortOrder): int {
  $stmt = $pdo->prepare('SELECT id FROM crm_equipment_items WHERE inventory_code=?');
  $stmt->execute([$code]);
  $id = $stmt->fetchColumn();
  if ($id) return (int)$id;
  $stmt = $pdo->prepare('INSERT INTO crm_equipment_items(site_id,category_id,name,inventory_code,description,color,half_day_price,day_price,deposit_amount,active,sort_order,created_at) VALUES(?,?,?,?,?,?,?,?,?,1,?,NOW())');
  $stmt->execute([$siteId, $categoryId, $name, $code, $description, $color, $halfDayPrice, $dayPrice, $deposit, $sortOrder]);
  return (int)$pdo->lastInsertId();
}

function equipment_seed(PDO $pdo): void {
  $permissionIds = [];
  foreach (equipment_permissions_seed() as $permission) {
    $permissionIds[$permission[0]] = equipment_ensure_permission($pdo, $permission);
  }

  $siteIds = [];
  $existingSites = $pdo->query('SELECT id,name FROM crm_sites WHERE deleted_at IS NULL ORDER BY id')->fetchAll();
  if (!$existingSites) {
    foreach (['Palissy', 'Bordeaux', 'Pessac', 'Glotin', 'Pastel'] as $siteName) {
      $siteIds[$siteName] = equipment_ensure_site($pdo, $siteName);
    }
  } else {
    foreach ($existingSites as $site) {
      $siteIds[(string)$site['name']] = (int)$site['id'];
    }
  }
  $defaultSiteId = (int)($siteIds['Palissy'] ?? reset($siteIds));

  $moduleIds = [
    'reservations' => equipment_ensure_module($pdo, 'Reservations vehicules', 'reservations', 'Planning et reservations des vehicules', '/reservations', 10),
    'locations-materiel' => equipment_ensure_module($pdo, 'Location materiel', 'locations-materiel', 'Planning et locations du materiel interne', '/locations-materiel', 15),
    'administration' => equipment_ensure_module($pdo, 'Administration', 'administration', 'Gestion des sites, modules, utilisateurs et roles', '/administration', 20),
  ];
  equipment_ensure_menu_item($pdo, 15);

  $users = [
    ['J-Philippe', 'admin', array_values($siteIds), array_values($moduleIds)],
    ['Christophe L', 'user', [$defaultSiteId], [$moduleIds['reservations'], $moduleIds['locations-materiel']]],
    ['Remi G', 'user', [$defaultSiteId], [$moduleIds['reservations'], $moduleIds['locations-materiel']]],
    ['Samy I', 'user', [$defaultSiteId], [$moduleIds['reservations'], $moduleIds['locations-materiel']]],
    ['Philippe P', 'responsable', [$defaultSiteId], [$moduleIds['reservations'], $moduleIds['locations-materiel']]],
    ['Jeremy L', 'blocked', [$defaultSiteId], []],
  ];

  foreach ($users as [$name, $role, $sites, $modules]) {
    $userId = equipment_ensure_user($pdo, $name, $role);
    foreach ($sites as $index => $siteId) {
      equipment_link_user_site($pdo, $userId, (int)$siteId, $index === 0);
    }
    if ($role === 'blocked') continue;
    foreach ($modules as $moduleId) {
      equipment_link_user_module($pdo, $userId, (int)$moduleId);
    }
    equipment_sync_user_permissions($pdo, $userId, $role, $permissionIds);
  }

  $categoryId = equipment_ensure_category($pdo, 'Poncage', 10);
  $palissyId = (int)($siteIds['Palissy'] ?? $defaultSiteId);
  equipment_ensure_item($pdo, $palissyId, $categoryId, 'Ponceuse parquet', 'PON-PARQUET', 'Ponceuse principale pour parquet', '#95002e', 45, 80, 300, 10);
  equipment_ensure_item($pdo, $palissyId, $categoryId, 'Bordureuse', 'BOR-001', 'Bordureuse pour finitions et plinthes', '#f59e0b', 35, 60, 200, 20);
  equipment_ensure_item($pdo, $palissyId, $categoryId, 'Aspirateur chantier', 'ASP-001', 'Aspirateur poussiere pour poncage', '#1d354f', 25, 40, 150, 30);
}

function equipment_actor_id(array $body = []): int {
  return crm_api_actor_id($body);
}

function equipment_user_permissions(PDO $pdo, int $userId): array {
  $stmt = $pdo->prepare('SELECT p.name FROM crm_permissions p JOIN crm_user_permissions up ON up.permission_id=p.id WHERE up.user_id=? ORDER BY p.sort_order,p.name');
  $stmt->execute([$userId]);
  return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function equipment_user_sites(PDO $pdo, int $userId): array {
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

function equipment_user_modules(PDO $pdo, int $userId): array {
  $stmt = $pdo->prepare('SELECT module_id FROM crm_user_modules WHERE user_id=? ORDER BY module_id');
  $stmt->execute([$userId]);
  return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function equipment_actor(PDO $pdo, array $body = [], bool $allowFallback = false): array {
  $actorId = equipment_actor_id($body);
  if ($actorId <= 0 && $allowFallback) {
    $actorId = (int)$pdo->query('SELECT id FROM crm_users WHERE role="admin" AND active=1 ORDER BY id LIMIT 1')->fetchColumn();
  }
  if ($actorId <= 0) equipment_out(['ok' => false, 'error' => 'Utilisateur CRM requis'], 401);
  $stmt = $pdo->prepare('SELECT id,name,role,active FROM crm_users WHERE id=? AND active=1');
  $stmt->execute([$actorId]);
  $user = $stmt->fetch();
  if (!$user) equipment_out(['ok' => false, 'error' => 'Utilisateur CRM introuvable'], 404);
  $user['id'] = (int)$user['id'];
  $user['siteIds'] = equipment_user_sites($pdo, (int)$user['id']);
  if ($user['role'] === 'blocked') {
    $user['moduleIds'] = [];
    $user['permissions'] = [];
    return $user;
  }
  $user['moduleIds'] = equipment_user_modules($pdo, (int)$user['id']);
  $user['permissions'] = equipment_user_permissions($pdo, (int)$user['id']);
  return $user;
}

function equipment_has_permission(array $user, string $permission): bool {
  return in_array($permission, $user['permissions'] ?? [], true);
}

function equipment_require_permission(array $user, string $permission): void {
  if (!equipment_has_permission($user, $permission)) {
    equipment_out(['ok' => false, 'error' => 'Droit insuffisant : ' . $permission], 403);
  }
}

function equipment_can_access_site(array $user, int $siteId): bool {
  return in_array($siteId, $user['siteIds'] ?? [], true);
}

function equipment_can_access_module(PDO $pdo, array $user): bool {
  $stmt = $pdo->prepare('SELECT id FROM crm_modules WHERE slug=? AND active=1');
  $stmt->execute(['locations-materiel']);
  $moduleId = (int)($stmt->fetchColumn() ?: 0);
  return $moduleId > 0 && in_array($moduleId, $user['moduleIds'] ?? [], true);
}

function equipment_require_module(PDO $pdo, array $user): void {
  if (!equipment_can_access_module($pdo, $user)) {
    equipment_out(['ok' => false, 'error' => 'Module non autorise : locations-materiel'], 403);
  }
}

function equipment_to_iso(?string $value): string {
  if (!$value) return '';
  return str_replace(' ', 'T', substr($value, 0, 16));
}

function equipment_rental_row(array $row): array {
  return [
    'id' => (int)$row['id'],
    'siteId' => (int)$row['site_id'],
    'equipmentItemId' => (int)$row['equipment_item_id'],
    'userId' => (int)$row['user_id'],
    'userName' => $row['user_name'],
    'periodType' => $row['period_type'] ?? 'half_day',
    'slot' => $row['slot'] ?? 'morning',
    'status' => $row['status'] ?? 'reserved',
    'title' => $row['title'] ?? '',
    'contactPhone' => $row['contact_phone'] ?? '',
    'startAt' => equipment_to_iso($row['start_at']),
    'endAt' => equipment_to_iso($row['end_at']),
    'notes' => $row['notes'] ?? '',
  ];
}

function equipment_item_row(array $row): array {
  return [
    'id' => (int)$row['id'],
    'siteId' => (int)$row['site_id'],
    'categoryId' => $row['category_id'] ? (int)$row['category_id'] : null,
    'name' => $row['name'],
    'inventoryCode' => $row['inventory_code'] ?? '',
    'description' => $row['description'] ?? '',
    'color' => $row['color'] ?? '#95002e',
    'photoUrl' => $row['photo_url'] ?? '',
    'halfDayPrice' => (float)$row['half_day_price'],
    'dayPrice' => (float)$row['day_price'],
    'depositAmount' => (float)$row['deposit_amount'],
    'active' => (bool)$row['active'],
    'sortOrder' => (int)$row['sort_order'],
  ];
}

function equipment_category_row(array $row): array {
  return [
    'id' => (int)$row['id'],
    'name' => $row['name'],
    'slug' => $row['slug'],
    'active' => (bool)$row['active'],
    'sortOrder' => (int)$row['sort_order'],
  ];
}

function equipment_bootstrap(PDO $pdo, array $actor): array {
  $sites = $pdo->query('SELECT id,name,slug,morning_start,morning_end,afternoon_start,afternoon_end FROM crm_sites WHERE active=1 AND deleted_at IS NULL ORDER BY id')->fetchAll();
  $modules = $pdo->query('SELECT id,name,slug,description,active,sort_order FROM crm_modules WHERE active=1 ORDER BY sort_order,name')->fetchAll();
  $categories = $pdo->query('SELECT id,name,slug,active,sort_order FROM crm_equipment_categories WHERE active=1 ORDER BY sort_order,name')->fetchAll();
  $items = $pdo->query('SELECT * FROM crm_equipment_items WHERE active=1 ORDER BY site_id,sort_order,name')->fetchAll();
  $rentals = $pdo->query('SELECT * FROM crm_equipment_rentals ORDER BY start_at,id')->fetchAll();
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
      'hours' => equipment_site_hours($site),
    ], $sites),
    'modules' => array_map(fn($module) => [
      'id' => (int)$module['id'],
      'name' => $module['name'],
      'slug' => $module['slug'],
      'description' => $module['description'] ?? '',
      'active' => (bool)$module['active'],
      'sortOrder' => (int)$module['sort_order'],
    ], $modules),
    'equipmentCategories' => array_map('equipment_category_row', $categories),
    'equipmentItems' => array_map('equipment_item_row', $items),
    'equipmentRentals' => array_map('equipment_rental_row', $rentals),
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
        'siteIds' => equipment_user_sites($pdo, $userId),
        'moduleIds' => equipment_user_modules($pdo, $userId),
        'permissions' => equipment_user_permissions($pdo, $userId),
      ];
    }, $users),
  ];
}

function equipment_log(PDO $pdo, array $user, string $action, string $details = ''): void {
  $stmt = $pdo->prepare('INSERT INTO crm_logs(user_id,user_name,action,details,created_at,ip) VALUES(?,?,?,?,NOW(),?)');
  $stmt->execute([(int)$user['id'], $user['name'], $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
}

function equipment_normalize_datetime(string $value): string {
  $value = str_replace('T', ' ', trim($value));
  $time = strtotime($value);
  if ($time === false) equipment_out(['ok' => false, 'error' => 'Date invalide'], 400);
  return date('Y-m-d H:i:s', $time);
}

function equipment_slot_range(array $body, array $hours): array {
  $periodType = in_array(($body['periodType'] ?? $body['period_type'] ?? ''), ['half_day', 'day'], true) ? (string)($body['periodType'] ?? $body['period_type']) : 'half_day';
  $slot = in_array(($body['slot'] ?? ''), ['morning', 'afternoon', 'full_day'], true) ? (string)$body['slot'] : ($periodType === 'day' ? 'full_day' : 'morning');

  $startRaw = (string)($body['startAt'] ?? $body['start_at'] ?? '');
  $endRaw = (string)($body['endAt'] ?? $body['end_at'] ?? '');
  if ($startRaw !== '' && $endRaw !== '') {
    return [$periodType, $slot, equipment_normalize_datetime($startRaw), equipment_normalize_datetime($endRaw)];
  }

  $date = trim((string)($body['date'] ?? ''));
  if ($date === '' || strtotime($date) === false) equipment_out(['ok' => false, 'error' => 'Date de location invalide'], 400);
  $day = date('Y-m-d', strtotime($date));

  if ($slot === 'afternoon') return [$periodType, $slot, "{$day} {$hours['afternoonStart']}:00", "{$day} {$hours['afternoonEnd']}:00"];
  if ($slot === 'full_day') return ['day', 'full_day', "{$day} {$hours['morningStart']}:00", "{$day} {$hours['afternoonEnd']}:00"];
  return [$periodType, 'morning', "{$day} {$hours['morningStart']}:00", "{$day} {$hours['morningEnd']}:00"];
}

function equipment_fetch_item(PDO $pdo, int $itemId): array {
  $stmt = $pdo->prepare('SELECT id,site_id FROM crm_equipment_items WHERE id=? AND active=1');
  $stmt->execute([$itemId]);
  $item = $stmt->fetch();
  if (!$item) equipment_out(['ok' => false, 'error' => 'Materiel introuvable'], 404);
  return $item;
}

function equipment_validate_rental_payload(PDO $pdo, array $body): array {
  $itemId = (int)($body['equipmentItemId'] ?? $body['equipment_item_id'] ?? 0);
  if (!$itemId) equipment_out(['ok' => false, 'error' => 'Materiel invalide'], 400);
  $item = equipment_fetch_item($pdo, $itemId);
  $hours = equipment_fetch_site_hours($pdo, (int)$item['site_id']);
  [$periodType, $slot, $startAt, $endAt] = equipment_slot_range($body, $hours);
  if (strtotime($endAt) <= strtotime($startAt)) equipment_out(['ok' => false, 'error' => 'Creneau invalide'], 400);
  if (!equipment_datetime_within_site_hours($startAt, $endAt, $hours)) equipment_out(['ok' => false, 'error' => 'Creneau hors horaires du site'], 400);

  $title = trim((string)($body['title'] ?? ''));
  $contactPhone = trim((string)($body['contactPhone'] ?? $body['contact_phone'] ?? ''));
  $notes = trim((string)($body['notes'] ?? ''));
  $status = trim((string)($body['status'] ?? 'reserved'));
  if (!in_array($status, ['reserved', 'picked_up', 'returned', 'cancelled'], true)) $status = 'reserved';
  if (strlen($title) > 190) equipment_out(['ok' => false, 'error' => 'Titre trop long'], 400);
  if (strlen($contactPhone) > 40) equipment_out(['ok' => false, 'error' => 'Telephone trop long'], 400);

  return [(int)$item['id'], (int)$item['site_id'], $periodType, $slot, $status, $title, $contactPhone, $startAt, $endAt, $notes];
}

try {
  $pdo = equipment_db();
  equipment_schema($pdo);
  equipment_seed($pdo);

  $action = $_GET['action'] ?? 'bootstrap';
  $body = equipment_body();

  if ($action === 'health') equipment_out(['ok' => true, 'mode' => 'mysql']);

  if ($action === 'bootstrap') {
    $actor = equipment_actor($pdo, $body, false);
    equipment_out(equipment_bootstrap($pdo, $actor));
  }

  $actor = equipment_actor($pdo, $body, false);

  if ($action === 'create_rental') {
    equipment_require_module($pdo, $actor);
    equipment_require_permission($actor, 'equipment_rentals.create');
    [$itemId, $siteId, $periodType, $slot, $status, $title, $contactPhone, $startAt, $endAt, $notes] = equipment_validate_rental_payload($pdo, $body);

    if (!equipment_can_access_site($actor, $siteId)) equipment_out(['ok' => false, 'error' => 'Site non autorise'], 403);

    if ($status !== 'cancelled') {
      $conflict = $pdo->prepare('SELECT COUNT(*) FROM crm_equipment_rentals WHERE equipment_item_id=? AND status<>"cancelled" AND NOT(end_at<=? OR start_at>=?)');
      $conflict->execute([$itemId, $startAt, $endAt]);
      if ((int)$conflict->fetchColumn() > 0) equipment_out(['ok' => false, 'error' => 'Ce materiel est deja loue sur ce creneau'], 409);
    }

    $stmt = $pdo->prepare('INSERT INTO crm_equipment_rentals(site_id,equipment_item_id,user_id,user_name,period_type,slot,status,title,contact_phone,start_at,end_at,notes,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
    $stmt->execute([$siteId, $itemId, (int)$actor['id'], $actor['name'], $periodType, $slot, $status, $title, $contactPhone, $startAt, $endAt, $notes]);
    $id = (int)$pdo->lastInsertId();
    equipment_log($pdo, $actor, 'creation location materiel', "Location materiel #{$id}");

    $stmt = $pdo->prepare('SELECT * FROM crm_equipment_rentals WHERE id=?');
    $stmt->execute([$id]);
    equipment_out(['ok' => true, 'equipmentRental' => equipment_rental_row($stmt->fetch())]);
  }

  if ($action === 'update_rental') {
    equipment_require_module($pdo, $actor);
    $id = (int)($body['id'] ?? 0);
    if (!$id) equipment_out(['ok' => false, 'error' => 'Location invalide'], 400);

    $stmt = $pdo->prepare('SELECT * FROM crm_equipment_rentals WHERE id=?');
    $stmt->execute([$id]);
    $rental = $stmt->fetch();
    if (!$rental) equipment_out(['ok' => false, 'error' => 'Location introuvable'], 404);
    if (!equipment_can_access_site($actor, (int)$rental['site_id'])) equipment_out(['ok' => false, 'error' => 'Site non autorise'], 403);

    $canUpdateAny = equipment_has_permission($actor, 'equipment_rentals.update_any');
    $canUpdateOwn = equipment_has_permission($actor, 'equipment_rentals.update_own') && (int)$rental['user_id'] === (int)$actor['id'];
    if (!$canUpdateAny && !$canUpdateOwn) equipment_out(['ok' => false, 'error' => 'Modification non autorisee'], 403);

    [$itemId, $siteId, $periodType, $slot, $status, $title, $contactPhone, $startAt, $endAt, $notes] = equipment_validate_rental_payload($pdo, $body);
    if (!equipment_can_access_site($actor, $siteId)) equipment_out(['ok' => false, 'error' => 'Site non autorise'], 403);

    if ($status !== 'cancelled') {
      $conflict = $pdo->prepare('SELECT COUNT(*) FROM crm_equipment_rentals WHERE id<>? AND equipment_item_id=? AND status<>"cancelled" AND NOT(end_at<=? OR start_at>=?)');
      $conflict->execute([$id, $itemId, $startAt, $endAt]);
      if ((int)$conflict->fetchColumn() > 0) equipment_out(['ok' => false, 'error' => 'Ce materiel est deja loue sur ce creneau'], 409);
    }

    $stmt = $pdo->prepare('UPDATE crm_equipment_rentals SET site_id=?,equipment_item_id=?,period_type=?,slot=?,status=?,title=?,contact_phone=?,start_at=?,end_at=?,notes=?,updated_at=NOW() WHERE id=?');
    $stmt->execute([$siteId, $itemId, $periodType, $slot, $status, $title, $contactPhone, $startAt, $endAt, $notes, $id]);
    equipment_log($pdo, $actor, 'modification location materiel', "Location materiel #{$id}");

    $stmt = $pdo->prepare('SELECT * FROM crm_equipment_rentals WHERE id=?');
    $stmt->execute([$id]);
    equipment_out(['ok' => true, 'equipmentRental' => equipment_rental_row($stmt->fetch())]);
  }

  if ($action === 'save_equipment_item') {
    equipment_require_module($pdo, $actor);
    equipment_require_permission($actor, 'equipment_rentals.manage_items');

    $id = (int)($body['id'] ?? 0);
    $siteId = (int)($body['siteId'] ?? $body['site_id'] ?? 0);
    $categoryId = (int)($body['categoryId'] ?? $body['category_id'] ?? 0);
    $categoryName = trim((string)($body['categoryName'] ?? $body['category_name'] ?? ''));
    $name = trim((string)($body['name'] ?? ''));
    $inventoryCode = trim((string)($body['inventoryCode'] ?? $body['inventory_code'] ?? ''));
    $description = trim((string)($body['description'] ?? ''));
    $color = trim((string)($body['color'] ?? '#95002e'));
    $photoDataUrl = (string)($body['photoDataUrl'] ?? $body['photo_data_url'] ?? '');
    $halfDayPrice = (float)str_replace(',', '.', (string)($body['halfDayPrice'] ?? $body['half_day_price'] ?? 0));
    $dayPrice = (float)str_replace(',', '.', (string)($body['dayPrice'] ?? $body['day_price'] ?? 0));
    $depositAmount = (float)str_replace(',', '.', (string)($body['depositAmount'] ?? $body['deposit_amount'] ?? 0));
    $sortOrder = (int)($body['sortOrder'] ?? $body['sort_order'] ?? 100);

    if (!$siteId || !equipment_can_access_site($actor, $siteId)) {
      equipment_out(['ok' => false, 'error' => 'Site non autorise'], 403);
    }
    if ($name === '' || strlen($name) > 160) {
      equipment_out(['ok' => false, 'error' => 'Nom du materiel invalide'], 400);
    }
    if (strlen($inventoryCode) > 80) equipment_out(['ok' => false, 'error' => 'Code inventaire trop long'], 400);
    if (strlen($description) > 255) equipment_out(['ok' => false, 'error' => 'Description trop longue'], 400);
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#95002e';
    if ($halfDayPrice < 0 || $dayPrice < 0 || $depositAmount < 0) {
      equipment_out(['ok' => false, 'error' => 'Les montants ne peuvent pas etre negatifs'], 400);
    }

    $savedCategory = null;
    if ($categoryName !== '') {
      if (strlen($categoryName) > 120) equipment_out(['ok' => false, 'error' => 'Categorie trop longue'], 400);
      $slug = equipment_slug($categoryName);
      $stmt = $pdo->prepare('SELECT * FROM crm_equipment_categories WHERE slug=? OR name=? LIMIT 1');
      $stmt->execute([$slug, $categoryName]);
      $category = $stmt->fetch();
      if ($category) {
        $categoryId = (int)$category['id'];
        $pdo->prepare('UPDATE crm_equipment_categories SET name=?,slug=?,active=1,updated_at=NOW() WHERE id=?')->execute([$categoryName, $slug, $categoryId]);
      } else {
        $stmt = $pdo->prepare('INSERT INTO crm_equipment_categories(name,slug,active,sort_order,created_at) VALUES(?,?,1,?,NOW())');
        $stmt->execute([$categoryName, $slug, 100]);
        $categoryId = (int)$pdo->lastInsertId();
      }
      $stmt = $pdo->prepare('SELECT * FROM crm_equipment_categories WHERE id=?');
      $stmt->execute([$categoryId]);
      $savedCategory = equipment_category_row($stmt->fetch());
    } elseif ($categoryId > 0) {
      $stmt = $pdo->prepare('SELECT * FROM crm_equipment_categories WHERE id=? AND active=1');
      $stmt->execute([$categoryId]);
      $category = $stmt->fetch();
      if (!$category) equipment_out(['ok' => false, 'error' => 'Categorie introuvable'], 404);
      $savedCategory = equipment_category_row($category);
    } else {
      $categoryId = null;
    }

    if ($inventoryCode !== '') {
      $duplicate = $pdo->prepare('SELECT id FROM crm_equipment_items WHERE inventory_code=? AND id<>? LIMIT 1');
      $duplicate->execute([$inventoryCode, $id]);
      if ($duplicate->fetchColumn()) equipment_out(['ok' => false, 'error' => 'Ce code inventaire existe deja'], 409);
    }

    if ($id > 0) {
      $stmt = $pdo->prepare('SELECT * FROM crm_equipment_items WHERE id=?');
      $stmt->execute([$id]);
      $item = $stmt->fetch();
      if (!$item) equipment_out(['ok' => false, 'error' => 'Materiel introuvable'], 404);
      if (!equipment_can_access_site($actor, (int)$item['site_id'])) {
        equipment_out(['ok' => false, 'error' => 'Site non autorise'], 403);
      }
      $photoUrl = $item['photo_url'] ?? '';
      if ($photoDataUrl !== '') $photoUrl = equipment_save_data_image($photoDataUrl, 'equipment') ?: $photoUrl;
      $stmt = $pdo->prepare('UPDATE crm_equipment_items SET site_id=?,category_id=?,name=?,inventory_code=?,description=?,color=?,photo_url=?,half_day_price=?,day_price=?,deposit_amount=?,active=1,sort_order=?,updated_at=NOW() WHERE id=?');
      $stmt->execute([$siteId, $categoryId, $name, $inventoryCode ?: null, $description, $color, $photoUrl ?: null, $halfDayPrice, $dayPrice, $depositAmount, $sortOrder, $id]);
      equipment_log($pdo, $actor, 'modification materiel', "Materiel #{$id}");
    } else {
      $photoUrl = $photoDataUrl !== '' ? equipment_save_data_image($photoDataUrl, 'equipment') : '';
      $stmt = $pdo->prepare('INSERT INTO crm_equipment_items(site_id,category_id,name,inventory_code,description,color,photo_url,half_day_price,day_price,deposit_amount,active,sort_order,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,1,?,NOW())');
      $stmt->execute([$siteId, $categoryId, $name, $inventoryCode ?: null, $description, $color, $photoUrl ?: null, $halfDayPrice, $dayPrice, $depositAmount, $sortOrder]);
      $id = (int)$pdo->lastInsertId();
      equipment_log($pdo, $actor, 'creation materiel', "Materiel #{$id}");
    }

    $stmt = $pdo->prepare('SELECT * FROM crm_equipment_items WHERE id=?');
    $stmt->execute([$id]);
    equipment_out(['ok' => true, 'equipmentItem' => equipment_item_row($stmt->fetch()), 'equipmentCategory' => $savedCategory]);
  }

  if ($action === 'delete_equipment_item') {
    equipment_require_module($pdo, $actor);
    equipment_require_permission($actor, 'equipment_rentals.manage_items');

    $id = (int)($body['id'] ?? 0);
    if (!$id) equipment_out(['ok' => false, 'error' => 'Materiel invalide'], 400);

    $stmt = $pdo->prepare('SELECT * FROM crm_equipment_items WHERE id=?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) equipment_out(['ok' => false, 'error' => 'Materiel introuvable'], 404);
    if (!equipment_can_access_site($actor, (int)$item['site_id'])) {
      equipment_out(['ok' => false, 'error' => 'Site non autorise'], 403);
    }

    $pdo->prepare('UPDATE crm_equipment_items SET active=0,updated_at=NOW() WHERE id=?')->execute([$id]);
    equipment_log($pdo, $actor, 'masquage materiel', "Materiel #{$id}");
    equipment_out(['ok' => true, 'id' => $id]);
  }

  if ($action === 'delete_rental') {
    equipment_require_module($pdo, $actor);
    $id = (int)($body['id'] ?? 0);
    if (!$id) equipment_out(['ok' => false, 'error' => 'Location invalide'], 400);

    $stmt = $pdo->prepare('SELECT * FROM crm_equipment_rentals WHERE id=?');
    $stmt->execute([$id]);
    $rental = $stmt->fetch();
    if (!$rental) equipment_out(['ok' => false, 'error' => 'Location introuvable'], 404);
    if (!equipment_can_access_site($actor, (int)$rental['site_id'])) equipment_out(['ok' => false, 'error' => 'Site non autorise'], 403);

    $canDeleteAny = equipment_has_permission($actor, 'equipment_rentals.delete_any');
    $canDeleteOwn = equipment_has_permission($actor, 'equipment_rentals.delete_own') && (int)$rental['user_id'] === (int)$actor['id'];
    if (!$canDeleteAny && !$canDeleteOwn) equipment_out(['ok' => false, 'error' => 'Suppression non autorisee'], 403);

    $pdo->prepare('DELETE FROM crm_equipment_rentals WHERE id=?')->execute([$id]);
    equipment_log($pdo, $actor, 'suppression location materiel', "Location materiel #{$id}");
    equipment_out(['ok' => true]);
  }

  equipment_out(['ok' => false, 'error' => 'Action inconnue'], 404);
} catch (Throwable $error) {
  equipment_out(crm_api_exception_response($error), 500);
}
