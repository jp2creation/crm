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

function crm_admin_out(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function crm_admin_body(): array {
  $body = json_decode((string)file_get_contents('php://input'), true);
  return is_array($body) ? $body : [];
}

function crm_admin_config(): array {
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
  if (!$file) crm_admin_out(['ok' => false, 'error' => 'Configuration API manquante'], 500);
  $config = require $file;
  if (!is_array($config)) crm_admin_out(['ok' => false, 'error' => 'Configuration API invalide'], 500);
  return $config;
}

function crm_admin_db(): PDO {
  $db = crm_admin_config()['db'] ?? [];
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

function crm_admin_slug(string $value): string {
  $value = strtolower(trim($value));
  if (function_exists('iconv')) $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
  $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: 'item';
  return trim($value, '-');
}

function crm_admin_column_exists(PDO $pdo, string $table, string $column): bool {
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
  $stmt->execute([$table, $column]);
  return (int)$stmt->fetchColumn() > 0;
}

function crm_admin_time5(?string $value, string $default): string {
  $value = trim((string)$value);
  if (preg_match('/^([0-2][0-9]:[0-5][0-9])/', $value, $match)) return $match[1];
  return $default;
}

function crm_admin_minutes(string $time): int {
  [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));
  return ($hour * 60) + $minute;
}

function crm_admin_normalize_time(array $body, string $camelKey, string $snakeKey, string $default): string {
  $value = trim((string)($body[$camelKey] ?? $body[$snakeKey] ?? ($body['hours'][$camelKey] ?? $default)));
  if (!preg_match('/^([0-2][0-9]):([0-5][0-9])$/', $value, $match)) {
    crm_admin_out(['ok' => false, 'error' => 'Horaire invalide'], 400);
  }
  $hour = (int)$match[1];
  if ($hour > 23) crm_admin_out(['ok' => false, 'error' => 'Horaire invalide'], 400);
  return sprintf('%02d:%02d:00', $hour, (int)$match[2]);
}

function crm_admin_site_hours(array $site): array {
  return [
    'morningStart' => crm_admin_time5($site['morning_start'] ?? null, '07:30'),
    'morningEnd' => crm_admin_time5($site['morning_end'] ?? null, '12:00'),
    'afternoonStart' => crm_admin_time5($site['afternoon_start'] ?? null, '13:30'),
    'afternoonEnd' => crm_admin_time5($site['afternoon_end'] ?? null, '17:30'),
  ];
}

function crm_admin_save_data_image(string $dataUrl, string $folder): string {
  if ($dataUrl === '') return '';
  if (!preg_match('/^data:image\/(png|jpeg|webp);base64,(.+)$/', $dataUrl, $matches)) {
    crm_admin_out(['ok' => false, 'error' => 'Photo invalide'], 400);
  }

  $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
  $binary = base64_decode($matches[2], true);
  if ($binary === false || strlen($binary) > 5 * 1024 * 1024) {
    crm_admin_out(['ok' => false, 'error' => 'Photo invalide ou trop lourde'], 400);
  }

  $safeFolder = trim(preg_replace('/[^a-z0-9_-]+/i', '', $folder) ?: 'profiles', '/');
  $uploadDir = dirname(__DIR__) . '/assets/uploads/' . $safeFolder;
  if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    crm_admin_out(['ok' => false, 'error' => 'Dossier upload indisponible'], 500);
  }

  $filename = uniqid('photo_', true) . '.' . $extension;
  $path = $uploadDir . '/' . $filename;
  if (file_put_contents($path, $binary) === false) {
    crm_admin_out(['ok' => false, 'error' => 'Enregistrement photo impossible'], 500);
  }

  return '/assets/uploads/' . $safeFolder . '/' . $filename;
}

function crm_admin_table_exists(PDO $pdo, string $table): bool {
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
  $stmt->execute([$table]);
  return (int)$stmt->fetchColumn() > 0;
}

function crm_admin_schema(PDO $pdo): void {
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
    if (!crm_admin_column_exists($pdo, 'crm_sites', $column)) $pdo->exec($sql);
  }
  if (!crm_admin_column_exists($pdo, 'crm_sites', 'deleted_at')) {
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
  if (!crm_admin_column_exists($pdo, 'crm_modules', 'menu_badge')) {
    $pdo->exec('ALTER TABLE crm_modules ADD COLUMN menu_badge VARCHAR(40) DEFAULT NULL AFTER route_path');
  }
  if (!crm_admin_column_exists($pdo, 'crm_modules', 'show_menu_badge')) {
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

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_menu_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_key VARCHAR(80) NOT NULL UNIQUE,
    title VARCHAR(120) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
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
  if (!crm_admin_column_exists($pdo, 'crm_menu_items', 'icon_key')) {
    $pdo->exec('ALTER TABLE crm_menu_items ADD COLUMN icon_key VARCHAR(80) DEFAULT "" AFTER group_key');
  }

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL UNIQUE,
    first_name VARCHAR(80) DEFAULT NULL,
    last_name VARCHAR(80) DEFAULT NULL,
    email VARCHAR(190) DEFAULT NULL,
    bio VARCHAR(255) DEFAULT NULL,
    photo_url VARCHAR(255) DEFAULT NULL,
    role VARCHAR(40) NOT NULL DEFAULT "user",
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
  $userProfileColumns = [
    'first_name' => 'ALTER TABLE crm_users ADD COLUMN first_name VARCHAR(80) DEFAULT NULL AFTER name',
    'last_name' => 'ALTER TABLE crm_users ADD COLUMN last_name VARCHAR(80) DEFAULT NULL AFTER first_name',
    'email' => 'ALTER TABLE crm_users ADD COLUMN email VARCHAR(190) DEFAULT NULL AFTER last_name',
    'bio' => 'ALTER TABLE crm_users ADD COLUMN bio VARCHAR(255) DEFAULT NULL AFTER email',
    'photo_url' => 'ALTER TABLE crm_users ADD COLUMN photo_url VARCHAR(255) DEFAULT NULL AFTER bio',
  ];
  foreach ($userProfileColumns as $column => $sql) {
    if (!crm_admin_column_exists($pdo, 'crm_users', $column)) $pdo->exec($sql);
  }

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

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    user_name VARCHAR(160) NULL,
    action VARCHAR(160) NOT NULL,
    details TEXT NULL,
    created_at DATETIME NOT NULL,
    ip VARCHAR(80) NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

  $pdo->exec('CREATE TABLE IF NOT EXISTS crm_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    excerpt VARCHAR(255) DEFAULT "",
    content MEDIUMTEXT NULL,
    icon_key VARCHAR(80) DEFAULT "article",
    active TINYINT(1) NOT NULL DEFAULT 1,
    show_in_menu TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

function crm_admin_permission_seed(): array {
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
    ['pages.view', 'Voir les pages CRM', 'Pages CRM', 190],
    ['pages.manage', 'Gerer les pages CRM', 'Pages CRM', 200],
  ];
}

function crm_admin_role_profiles(): array {
  return [
    [
      'key' => 'user',
      'label' => 'Employe',
      'description' => 'Reservation et location sur les sites rattaches, suppression de ses propres demandes.',
      'permissions' => ['reservations.view', 'reservations.create', 'reservations.update_own', 'reservations.delete_own', 'equipment_rentals.view', 'equipment_rentals.create', 'equipment_rentals.update_own', 'equipment_rentals.delete_own'],
      'moduleSlugs' => ['reservations', 'locations-materiel'],
    ],
    [
      'key' => 'responsable',
      'label' => 'Responsable site',
      'description' => 'Gestion des reservations, vehicules et locations materiel des sites rattaches.',
      'permissions' => ['reservations.view', 'reservations.create', 'reservations.update_own', 'reservations.update_any', 'reservations.delete_own', 'reservations.delete_any', 'reservations.manage_vehicles', 'equipment_rentals.view', 'equipment_rentals.create', 'equipment_rentals.update_own', 'equipment_rentals.update_any', 'equipment_rentals.delete_own', 'equipment_rentals.delete_any', 'equipment_rentals.manage_items'],
      'moduleSlugs' => ['reservations', 'locations-materiel'],
    ],
    [
      'key' => 'admin',
      'label' => 'Administrateur',
      'description' => 'Acces global aux sites, modules, utilisateurs, roles et permissions.',
      'permissions' => array_map(fn($permission) => $permission[0], crm_admin_permission_seed()),
      'moduleSlugs' => ['reservations', 'locations-materiel', 'pages-crm', 'administration', 'planning', 'documents', 'demandes', 'tapis-romus'],
    ],
    [
      'key' => 'blocked',
      'label' => 'Sans acces',
      'description' => 'Aucun module ni action disponible.',
      'permissions' => [],
      'moduleSlugs' => [],
    ],
  ];
}

function crm_admin_module_seed(): array {
  return [
    ['Reservations vehicules', 'reservations', 'Planning et reservations des vehicules', '/reservations', 10],
    ['Location materiel', 'locations-materiel', 'Planning et locations du materiel interne', '/locations-materiel', 15],
    ['Pages CRM', 'pages-crm', 'Pages internes modifiables depuis le CRM', '/pages-crm', 18],
    ['Administration', 'administration', 'Gestion des sites, modules, utilisateurs et roles', '/administration', 20],
    ['Planning', 'planning', 'Planning interne par site', '/planning', 30],
    ['Documents internes', 'documents', 'Procedures et documents partages', '/documents', 40],
    ['Demandes internes', 'demandes', 'Demandes et validations internes', '/demandes', 50],
    ['Tapis ROMUS', 'tapis-romus', 'Bon de commande et mesures tapis ROMUS', '/tapis-romus', 60],
  ];
}

function crm_admin_menu_group_seed(): array {
  return [
    ['dashboards', 'Dashboards', 10],
    ['internal', 'Modules internes', 20],
    ['apps', 'Apps', 30],
    ['authentication', 'Authentication', 40],
    ['pages', 'Pages', 50],
    ['forms', 'From', 60],
    ['tables', 'Table', 70],
    ['charts', 'Charts', 80],
  ];
}

function crm_admin_static_menu_item_seed(): array {
  return [
    ['dashboard:overview', 'dashboards', 'Overview', 'dashboard', 10],
    ['dashboard:analytics', 'dashboards', 'Analytics', 'chartLine', 20],
    ['dashboard:ecommerce', 'dashboards', 'eCommerce', 'shopping', 30],
    ['dashboard:crm', 'dashboards', 'CRM', 'briefcase', 40],
    ['app:email', 'apps', 'Email', 'mail', 10],
    ['app:chat', 'apps', 'Chat', 'message', 20],
    ['app:calendar', 'apps', 'Calendar', 'calendar', 30],
    ['app:contacts', 'apps', 'Contacts', 'contacts', 40],
    ['app:blog', 'apps', 'Blog', 'article', 50],
    ['app:ecommerce', 'apps', 'E-commerce', 'shopping', 60],
    ['app:notes', 'apps', 'Notes', 'note', 70],
    ['app:kanban', 'apps', 'Kanban Board', 'kanban', 80],
    ['feature:rule-engine', 'apps', 'Rule Engine', 'ruleEngine', 90],
    ['feature:query-builder', 'apps', 'Query Builder', 'queryBuilder', 100],
    ['feature:simulation', 'apps', 'Real-Time Simulation', 'simulation', 110],
    ['feature:insights', 'apps', 'Smart Insights', 'insights', 120],
    ['feature:workflow-builder', 'apps', 'Workflow Builder', 'workflowBuilder', 130],
    ['feature:task-scheduler', 'apps', 'Task Scheduler', 'taskScheduler', 140],
    ['auth:login', 'authentication', 'Login', 'lock', 10],
    ['auth:register', 'authentication', 'Register', 'userPlus', 20],
    ['auth:forgot-password', 'authentication', 'Forgot Password', 'key', 30],
    ['page:pricing', 'pages', 'Pricing', 'creditCard', 10],
    ['page:account-settings', 'pages', 'Account Settings', 'settings', 20],
    ['page:gallery', 'pages', 'Gallery', 'photo', 30],
    ['page:faq', 'pages', 'FAQ', 'help', 40],
    ['page:typography', 'pages', 'Typography', 'heading', 50],
    ['form:layout', 'forms', 'Form Layout', 'layoutGrid', 10],
    ['form:validation', 'forms', 'Form Validation', 'checklist', 20],
    ['form:editor', 'forms', 'Editor', 'edit', 30],
    ['table:simple', 'tables', 'Simple Table', 'table', 10],
    ['table:data', 'tables', 'Data Table', 'database', 20],
    ['table:crud', 'tables', 'CRUD Table', 'edit', 30],
    ['chart:line', 'charts', 'Line', 'chartLine', 10],
    ['chart:area', 'charts', 'Area', 'chartArea', 20],
    ['chart:columns', 'charts', 'Columns', 'chartBar', 30],
    ['chart:pie', 'charts', 'Pie & Doughnut', 'chartPie', 40],
    ['chart:radar', 'charts', 'Radar', 'chartRadar', 50],
    ['chart:candlestick', 'charts', 'Candlestick', 'chartCandle', 60],
  ];
}

function crm_admin_ensure_permission(PDO $pdo, array $permission): int {
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

function crm_admin_ensure_module(PDO $pdo, array $module): int {
  [$name, $slug, $description, $routePath, $sortOrder] = $module;
  $stmt = $pdo->prepare('SELECT id FROM crm_modules WHERE slug=?');
  $stmt->execute([$slug]);
  $id = $stmt->fetchColumn();
  if ($id) return (int)$id;
  $stmt = $pdo->prepare('INSERT INTO crm_modules(name,slug,description,route_path,active,sort_order,created_at) VALUES(?,?,?,?,1,?,NOW())');
  $stmt->execute([$name, $slug, $description, $routePath, $sortOrder]);
  return (int)$pdo->lastInsertId();
}

function crm_admin_ensure_menu_group(PDO $pdo, array $group): int {
  [$menuKey, $title, $sortOrder] = $group;
  $stmt = $pdo->prepare('SELECT id FROM crm_menu_groups WHERE menu_key=?');
  $stmt->execute([$menuKey]);
  $id = $stmt->fetchColumn();
  if ($id) return (int)$id;
  $stmt = $pdo->prepare('INSERT INTO crm_menu_groups(menu_key,title,active,sort_order,created_at) VALUES(?,?,1,?,NOW())');
  $stmt->execute([$menuKey, $title, $sortOrder]);
  return (int)$pdo->lastInsertId();
}

function crm_admin_ensure_menu_item(PDO $pdo, array $item): int {
  [$itemKey, $groupKey, $label, $iconKey, $sortOrder] = $item;
  $stmt = $pdo->prepare('SELECT id FROM crm_menu_items WHERE item_key=?');
  $stmt->execute([$itemKey]);
  $id = $stmt->fetchColumn();
  if ($id) {
    $pdo->prepare("UPDATE crm_menu_items SET label=?,icon_key=IF(icon_key IS NULL OR icon_key='',?,icon_key),updated_at=NOW() WHERE id=?")->execute([$label, $iconKey, (int)$id]);
    return (int)$id;
  }
  $stmt = $pdo->prepare('INSERT INTO crm_menu_items(item_key,group_key,icon_key,label,active,sort_order,created_at) VALUES(?,?,?,?,1,?,NOW())');
  $stmt->execute([$itemKey, $groupKey, $iconKey, $label, $sortOrder]);
  return (int)$pdo->lastInsertId();
}

function crm_admin_module_icon_key(string $slug): string {
  return [
    'reservations' => 'truck',
    'administration' => 'settings',
    'planning' => 'calendar',
    'documents' => 'article',
    'demandes' => 'checklist',
    'tapis-romus' => 'table',
    'locations-materiel' => 'package',
    'pages-crm' => 'article',
    'conges' => 'calendar',
  ][$slug] ?? 'category';
}

function crm_admin_unique_page_slug(PDO $pdo, string $value, int $ignoreId = 0): string {
  $base = crm_admin_slug($value) ?: 'page';
  $slug = $base;
  $suffix = 2;
  while (true) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM crm_pages WHERE slug=? AND id<>?');
    $stmt->execute([$slug, $ignoreId]);
    if ((int)$stmt->fetchColumn() === 0) return $slug;
    $slug = $base . '-' . $suffix;
    $suffix++;
  }
}

function crm_admin_page_row(array $page): array {
  return [
    'id' => (int)$page['id'],
    'title' => $page['title'],
    'slug' => $page['slug'],
    'excerpt' => $page['excerpt'] ?? '',
    'content' => $page['content'] ?? '',
    'iconKey' => $page['icon_key'] ?? 'article',
    'active' => (bool)$page['active'],
    'showInMenu' => (bool)$page['show_in_menu'],
    'sortOrder' => (int)$page['sort_order'],
    'routePath' => '/pages-crm/' . $page['slug'],
  ];
}

function crm_admin_sync_page_menu_item(PDO $pdo, array $page, string $oldSlug = ''): void {
  $oldKey = $oldSlug !== '' ? 'cms-page:' . $oldSlug : '';
  $newKey = 'cms-page:' . $page['slug'];

  if ($oldKey !== '' && $oldKey !== $newKey) {
    $pdo->prepare('DELETE FROM crm_menu_items WHERE item_key=?')->execute([$oldKey]);
  }

  if ((int)$page['active'] !== 1 || (int)$page['show_in_menu'] !== 1) {
    $pdo->prepare('UPDATE crm_menu_items SET active=0,updated_at=NOW() WHERE item_key=?')->execute([$newKey]);
    return;
  }

  $stmt = $pdo->prepare('SELECT id FROM crm_menu_items WHERE item_key=?');
  $stmt->execute([$newKey]);
  $id = (int)($stmt->fetchColumn() ?: 0);
  if ($id > 0) {
    $stmt = $pdo->prepare('UPDATE crm_menu_items SET group_key="pages",icon_key=?,label=?,active=1,sort_order=?,updated_at=NOW() WHERE id=?');
    $stmt->execute([$page['icon_key'] ?: 'article', $page['title'], (int)$page['sort_order'], $id]);
    return;
  }

  $stmt = $pdo->prepare('INSERT INTO crm_menu_items(item_key,group_key,icon_key,label,active,sort_order,created_at) VALUES(?,"pages",?,?,1,?,NOW())');
  $stmt->execute([$newKey, $page['icon_key'] ?: 'article', $page['title'], (int)$page['sort_order']]);
}

function crm_admin_ensure_site(PDO $pdo, string $name): int {
  $stmt = $pdo->prepare('SELECT id FROM crm_sites WHERE name=? AND deleted_at IS NULL');
  $stmt->execute([$name]);
  $id = $stmt->fetchColumn();
  if ($id) return (int)$id;
  $stmt = $pdo->prepare('INSERT INTO crm_sites(name,slug,active,created_at) VALUES(?,?,1,NOW())');
  $stmt->execute([$name, crm_admin_slug($name)]);
  return (int)$pdo->lastInsertId();
}

function crm_admin_ensure_user(PDO $pdo, string $name, string $role): int {
  $stmt = $pdo->prepare('SELECT id FROM crm_users WHERE name=?');
  $stmt->execute([$name]);
  $id = $stmt->fetchColumn();
  if ($id) return (int)$id;
  $stmt = $pdo->prepare('INSERT INTO crm_users(name,role,active,created_at) VALUES(?,?,1,NOW())');
  $stmt->execute([$name, $role]);
  return (int)$pdo->lastInsertId();
}

function crm_admin_sync_ids(PDO $pdo, string $table, string $column, int $userId, array $ids): void {
  if (!in_array($table, ['crm_user_sites', 'crm_user_modules', 'crm_user_permissions'], true)) return;
  if (!in_array($column, ['site_id', 'module_id', 'permission_id'], true)) return;
  $pdo->prepare("DELETE FROM {$table} WHERE user_id=?")->execute([$userId]);
  if (!$ids) return;

  if ($table === 'crm_user_sites') {
    $stmt = $pdo->prepare('INSERT IGNORE INTO crm_user_sites(site_id,user_id,is_default,created_at) VALUES(?,?,?,NOW())');
    foreach (array_values($ids) as $index => $id) $stmt->execute([(int)$id, $userId, $index === 0 ? 1 : 0]);
    return;
  }

  $stmt = $pdo->prepare("INSERT IGNORE INTO {$table}({$column},user_id,created_at) VALUES(?,?,NOW())");
  foreach ($ids as $id) $stmt->execute([(int)$id, $userId]);
}

function crm_admin_ids_from_names(PDO $pdo, string $table, string $column, array $names): array {
  if (!$names) return [];
  $names = array_values(array_unique(array_filter(array_map('strval', $names))));
  if (!$names) return [];
  $placeholders = implode(',', array_fill(0, count($names), '?'));
  $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE {$column} IN ({$placeholders})");
  $stmt->execute($names);
  return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function crm_admin_cleanup_legacy_permissions(PDO $pdo): void {
  $stmt = $pdo->prepare('SELECT id FROM crm_permissions WHERE name=?');
  $stmt->execute(['reservations.manage_sites']);
  $legacyId = (int)($stmt->fetchColumn() ?: 0);
  if (!$legacyId) return;

  $pdo->prepare('DELETE FROM crm_user_permissions WHERE permission_id=?')->execute([$legacyId]);
  $pdo->prepare('DELETE FROM crm_permissions WHERE id=?')->execute([$legacyId]);
}

function crm_admin_seed(PDO $pdo): void {
  foreach (crm_admin_permission_seed() as $permission) crm_admin_ensure_permission($pdo, $permission);
  crm_admin_cleanup_legacy_permissions($pdo);
  foreach (crm_admin_module_seed() as $module) crm_admin_ensure_module($pdo, $module);
  $pdo->exec("UPDATE crm_modules SET menu_badge='Martin', show_menu_badge=1 WHERE slug='reservations' AND menu_badge IS NULL");
  foreach (crm_admin_menu_group_seed() as $group) crm_admin_ensure_menu_group($pdo, $group);
  foreach (crm_admin_static_menu_item_seed() as $item) crm_admin_ensure_menu_item($pdo, $item);
  $modules = $pdo->query('SELECT name,slug,sort_order FROM crm_modules ORDER BY sort_order,name')->fetchAll();
  foreach ($modules as $module) {
    crm_admin_ensure_menu_item($pdo, ['module:' . $module['slug'], 'internal', $module['name'], crm_admin_module_icon_key((string)$module['slug']), (int)$module['sort_order']]);
  }

  $siteIds = [];
  $existingSites = $pdo->query('SELECT id,name FROM crm_sites WHERE deleted_at IS NULL ORDER BY id')->fetchAll();
  if (!$existingSites) {
    foreach (['Palissy', 'Bordeaux', 'Pessac', 'Glotin', 'Pastel'] as $siteName) {
      $siteIds[$siteName] = crm_admin_ensure_site($pdo, $siteName);
    }
  } else {
    foreach ($existingSites as $site) {
      $siteIds[(string)$site['name']] = (int)$site['id'];
    }
  }
  $defaultSiteId = (int)($siteIds['Palissy'] ?? reset($siteIds));

  $profiles = [];
  foreach (crm_admin_role_profiles() as $profile) $profiles[$profile['key']] = $profile;

  $users = [
    ['J-Philippe', 'admin', array_values($siteIds)],
    ['Christophe L', 'user', [$defaultSiteId]],
    ['Remi G', 'user', [$defaultSiteId]],
    ['Samy I', 'user', [$defaultSiteId]],
    ['Philippe P', 'responsable', [$defaultSiteId]],
    ['Jeremy L', 'blocked', [$defaultSiteId]],
  ];

  foreach ($users as [$name, $role, $sites]) {
    $userId = crm_admin_ensure_user($pdo, $name, $role);
    $profile = $profiles[$role] ?? $profiles['user'];
    if ($role === 'blocked') {
      crm_admin_sync_ids($pdo, 'crm_user_modules', 'module_id', $userId, []);
      crm_admin_sync_ids($pdo, 'crm_user_permissions', 'permission_id', $userId, []);
      continue;
    }
    $hasSites = (int)$pdo->query('SELECT COUNT(*) FROM crm_user_sites WHERE user_id=' . (int)$userId)->fetchColumn() > 0;
    $hasModules = (int)$pdo->query('SELECT COUNT(*) FROM crm_user_modules WHERE user_id=' . (int)$userId)->fetchColumn() > 0;
    $hasPermissions = (int)$pdo->query('SELECT COUNT(*) FROM crm_user_permissions WHERE user_id=' . (int)$userId)->fetchColumn() > 0;
    $profileModuleIds = crm_admin_ids_from_names($pdo, 'crm_modules', 'slug', $profile['moduleSlugs']);
    if (!$hasSites) crm_admin_sync_ids($pdo, 'crm_user_sites', 'site_id', $userId, $sites);
    if (!$hasModules) {
      crm_admin_sync_ids($pdo, 'crm_user_modules', 'module_id', $userId, $profileModuleIds);
    } elseif ($role === 'admin') {
      $insert = $pdo->prepare('INSERT IGNORE INTO crm_user_modules(module_id,user_id,created_at) VALUES(?,?,NOW())');
      foreach ($profileModuleIds as $moduleId) $insert->execute([(int)$moduleId, $userId]);
    }
    $profilePermissionIds = crm_admin_ids_from_names($pdo, 'crm_permissions', 'name', $profile['permissions']);
    if (!$hasPermissions) {
      crm_admin_sync_ids($pdo, 'crm_user_permissions', 'permission_id', $userId, $profilePermissionIds);
    } elseif ($role === 'admin') {
      $insert = $pdo->prepare('INSERT IGNORE INTO crm_user_permissions(permission_id,user_id,created_at) VALUES(?,?,NOW())');
      foreach ($profilePermissionIds as $permissionId) $insert->execute([(int)$permissionId, $userId]);
    }
  }
}

function crm_admin_actor_id(array $body = []): int {
  return crm_api_actor_id($body);
}

function crm_admin_user_permissions(PDO $pdo, int $userId): array {
  $stmt = $pdo->prepare('SELECT p.name FROM crm_permissions p JOIN crm_user_permissions up ON up.permission_id=p.id WHERE up.user_id=? ORDER BY p.sort_order,p.name');
  $stmt->execute([$userId]);
  return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function crm_admin_user_ids(PDO $pdo, string $table, string $column, int $userId): array {
  $stmt = $pdo->prepare("SELECT {$column} FROM {$table} WHERE user_id=? ORDER BY {$column}");
  $stmt->execute([$userId]);
  return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function crm_admin_actor(PDO $pdo, array $body = [], bool $allowFallback = false): array {
  $actorId = crm_admin_actor_id($body);
  if ($actorId <= 0 && $allowFallback) {
    $actorId = (int)$pdo->query('SELECT id FROM crm_users WHERE role="admin" AND active=1 ORDER BY id LIMIT 1')->fetchColumn();
  }
  if ($actorId <= 0) crm_admin_out(['ok' => false, 'error' => 'Utilisateur CRM requis'], 401);
  $stmt = $pdo->prepare('SELECT id,name,first_name,last_name,email,bio,photo_url,role,active FROM crm_users WHERE id=? AND active=1');
  $stmt->execute([$actorId]);
  $actor = $stmt->fetch();
  if (!$actor) crm_admin_out(['ok' => false, 'error' => 'Utilisateur CRM introuvable'], 404);
  $actor['id'] = (int)$actor['id'];
  $actor['permissions'] = crm_admin_user_permissions($pdo, (int)$actor['id']);
  return $actor;
}

function crm_admin_has_permission(array $actor, string $permission): bool {
  return in_array($permission, $actor['permissions'] ?? [], true);
}

function crm_admin_profile_payload(array $user): array {
  $firstName = trim((string)($user['first_name'] ?? ''));
  $lastName = trim((string)($user['last_name'] ?? ''));
  $rawName = trim((string)($user['name'] ?? ''));

  if ($firstName === '' && $rawName !== '') {
    if ($rawName === 'J-Philippe') {
      $firstName = 'Jean-Philippe';
    } else {
      $parts = preg_split('/\s+/', $rawName, 2);
      $firstName = trim((string)($parts[0] ?? ''));
      if ($lastName === '') $lastName = trim((string)($parts[1] ?? ''));
    }
  }

  $displayName = trim($firstName . ' ' . $lastName);
  if ($displayName === '') $displayName = $rawName !== '' ? $rawName : 'Jean-Philippe';
  $canEditIdentity = ($user['role'] ?? '') === 'admin' || crm_admin_has_permission($user, 'platform.manage_users');

  return [
    'id' => (int)($user['id'] ?? 0),
    'name' => $rawName,
    'displayName' => $displayName,
    'firstName' => $firstName,
    'lastName' => $lastName,
    'email' => trim((string)($user['email'] ?? '')) ?: 'contact@jp2creation.fr',
    'bio' => trim((string)($user['bio'] ?? '')) ?: (($user['role'] ?? '') === 'admin' ? 'Administrateur CRM Martin Sols' : ''),
    'photoUrl' => trim((string)($user['photo_url'] ?? '')) ?: '/assets/logo/logomark.png',
    'role' => $user['role'] ?? 'user',
    'canEditIdentity' => $canEditIdentity,
  ];
}

function crm_admin_require_any(array $actor, array $permissions): void {
  foreach ($permissions as $permission) {
    if (crm_admin_has_permission($actor, $permission)) return;
  }
  crm_admin_out(['ok' => false, 'error' => 'Droit administration insuffisant'], 403);
}

function crm_admin_log(PDO $pdo, array $actor, string $action, string $details = ''): void {
  $stmt = $pdo->prepare('INSERT INTO crm_logs(user_id,user_name,action,details,created_at,ip) VALUES(?,?,?,?,NOW(),?)');
  $stmt->execute([(int)$actor['id'], $actor['name'], $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
}

function crm_admin_unique_slug(PDO $pdo, string $table, string $value, int $ignoreId = 0): string {
  $base = crm_admin_slug($value);
  $slug = $base;
  $suffix = 2;
  while (true) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE slug=? AND id<>?");
    $stmt->execute([$slug, $ignoreId]);
    if ((int)$stmt->fetchColumn() === 0) return $slug;
    $slug = $base . '-' . $suffix;
    $suffix++;
  }
}

function crm_admin_bootstrap(PDO $pdo, array $actor): array {
  $sites = $pdo->query('SELECT id,name,slug,active,morning_start,morning_end,afternoon_start,afternoon_end FROM crm_sites WHERE deleted_at IS NULL ORDER BY active DESC,name')->fetchAll();
  $modules = $pdo->query('SELECT id,name,slug,description,route_path,menu_badge,show_menu_badge,active,sort_order FROM crm_modules ORDER BY active DESC,sort_order,name')->fetchAll();
  $menuGroups = $pdo->query('SELECT id,menu_key,title,active,sort_order FROM crm_menu_groups ORDER BY sort_order,title')->fetchAll();
  $menuItems = $pdo->query('SELECT id,item_key,group_key,icon_key,label,active,sort_order FROM crm_menu_items ORDER BY group_key,sort_order,label')->fetchAll();
  $permissions = $pdo->query('SELECT id,name,label,group_label,sort_order FROM crm_permissions ORDER BY sort_order,name')->fetchAll();
  $users = $pdo->query('SELECT id,name,role,active,created_at FROM crm_users ORDER BY active DESC,name')->fetchAll();
  $pages = $pdo->query('SELECT * FROM crm_pages ORDER BY active DESC,sort_order,title')->fetchAll();

  return [
    'ok' => true,
    'actor' => $actor,
    'roles' => crm_admin_role_profiles(),
    'sites' => array_map(fn($site) => [
      'id' => (int)$site['id'],
      'name' => $site['name'],
      'slug' => $site['slug'],
      'active' => (bool)$site['active'],
      'hours' => crm_admin_site_hours($site),
    ], $sites),
    'modules' => array_map(fn($module) => [
      'id' => (int)$module['id'],
      'name' => $module['name'],
      'slug' => $module['slug'],
      'description' => $module['description'] ?? '',
      'routePath' => $module['route_path'] ?? '',
      'menuBadge' => $module['menu_badge'] ?? '',
      'showMenuBadge' => (bool)$module['show_menu_badge'],
      'active' => (bool)$module['active'],
      'sortOrder' => (int)$module['sort_order'],
    ], $modules),
    'menuGroups' => array_map(fn($group) => [
      'id' => (int)$group['id'],
      'menuKey' => $group['menu_key'],
      'title' => $group['title'],
      'active' => (bool)$group['active'],
      'sortOrder' => (int)$group['sort_order'],
    ], $menuGroups),
    'menuItems' => array_map(fn($item) => [
      'id' => (int)$item['id'],
      'itemKey' => $item['item_key'],
      'groupKey' => $item['group_key'],
      'iconKey' => $item['icon_key'] ?? '',
      'label' => $item['label'],
      'active' => (bool)$item['active'],
      'sortOrder' => (int)$item['sort_order'],
    ], $menuItems),
    'permissions' => array_map(fn($permission) => [
      'id' => (int)$permission['id'],
      'name' => $permission['name'],
      'label' => $permission['label'],
      'group' => $permission['group_label'],
      'sortOrder' => (int)$permission['sort_order'],
    ], $permissions),
    'pages' => array_map('crm_admin_page_row', $pages),
    'users' => array_map(function($user) use ($pdo) {
      $userId = (int)$user['id'];
      return [
        'id' => $userId,
        'name' => $user['name'],
        'role' => $user['role'],
        'active' => (bool)$user['active'],
        'siteIds' => crm_admin_user_ids($pdo, 'crm_user_sites', 'site_id', $userId),
        'moduleIds' => crm_admin_user_ids($pdo, 'crm_user_modules', 'module_id', $userId),
        'permissionIds' => crm_admin_user_ids($pdo, 'crm_user_permissions', 'permission_id', $userId),
        'permissions' => crm_admin_user_permissions($pdo, $userId),
      ];
    }, $users),
  ];
}

try {
  $pdo = crm_admin_db();
  crm_admin_schema($pdo);
  crm_admin_seed($pdo);

  $action = $_GET['action'] ?? 'bootstrap';
  $body = crm_admin_body();

  if ($action === 'health') crm_admin_out(['ok' => true, 'mode' => 'mysql']);

  $actor = crm_admin_actor($pdo, $body);

  if ($action === 'profile') {
    crm_admin_out(['ok' => true, 'profile' => crm_admin_profile_payload($actor)]);
  }

  if ($action === 'save_profile') {
    $profile = crm_admin_profile_payload($actor);
    $canEditIdentity = (bool)$profile['canEditIdentity'];
    $firstName = $canEditIdentity ? trim((string)($body['firstName'] ?? $body['first_name'] ?? $profile['firstName'])) : $profile['firstName'];
    $lastName = $canEditIdentity ? trim((string)($body['lastName'] ?? $body['last_name'] ?? $profile['lastName'])) : $profile['lastName'];
    $email = trim((string)($body['email'] ?? $profile['email']));
    $bio = trim((string)($body['bio'] ?? $profile['bio']));
    $photoUrl = (string)$profile['photoUrl'];
    $photoDataUrl = (string)($body['photoDataUrl'] ?? $body['photo_data_url'] ?? '');

    if ($firstName === '') crm_admin_out(['ok' => false, 'error' => 'Prenom obligatoire'], 400);
    if (strlen($firstName) > 80 || strlen($lastName) > 80) crm_admin_out(['ok' => false, 'error' => 'Prenom ou nom trop long'], 400);
    if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190)) {
      crm_admin_out(['ok' => false, 'error' => 'Adresse e-mail invalide'], 400);
    }
    if (strlen($bio) > 255) crm_admin_out(['ok' => false, 'error' => 'Bio trop longue'], 400);
    if ($photoDataUrl !== '') $photoUrl = crm_admin_save_data_image($photoDataUrl, 'profiles') ?: $photoUrl;

    $stmt = $pdo->prepare('UPDATE crm_users SET first_name=?,last_name=?,email=?,bio=?,photo_url=?,updated_at=NOW() WHERE id=?');
    $stmt->execute([$firstName, $lastName, $email, $bio, $photoUrl, (int)$actor['id']]);

    $stmt = $pdo->prepare('SELECT id,name,first_name,last_name,email,bio,photo_url,role,active FROM crm_users WHERE id=?');
    $stmt->execute([(int)$actor['id']]);
    $updated = $stmt->fetch();
    if (!$updated) crm_admin_out(['ok' => false, 'error' => 'Profil introuvable'], 404);
    $updated['id'] = (int)$updated['id'];
    $updated['permissions'] = crm_admin_user_permissions($pdo, (int)$updated['id']);
    crm_admin_log($pdo, $actor, 'modification profil', $email);
    crm_admin_out(['ok' => true, 'profile' => crm_admin_profile_payload($updated)]);
  }

  if ($action === 'bootstrap') {
    crm_admin_require_any($actor, ['platform.manage_users', 'platform.manage_modules', 'platform.manage_sites', 'platform.manage_roles']);
    crm_admin_out(crm_admin_bootstrap($pdo, $actor));
  }

  if ($action === 'save_menu_settings') {
    crm_admin_require_any($actor, ['platform.manage_modules']);
    $groups = is_array($body['groups'] ?? null) ? $body['groups'] : [];
    $items = is_array($body['items'] ?? null) ? $body['items'] : [];

    $groupKeys = $pdo->query('SELECT menu_key FROM crm_menu_groups')->fetchAll(PDO::FETCH_COLUMN);
    $groupKeySet = array_fill_keys(array_map('strval', $groupKeys), true);
    $itemKeys = $pdo->query('SELECT item_key FROM crm_menu_items')->fetchAll(PDO::FETCH_COLUMN);
    $itemKeySet = array_fill_keys(array_map('strval', $itemKeys), true);

    $pdo->beginTransaction();
    $groupStmt = $pdo->prepare('UPDATE crm_menu_groups SET title=?,active=?,sort_order=?,updated_at=NOW() WHERE menu_key=?');
    foreach ($groups as $group) {
      $menuKey = trim((string)($group['menuKey'] ?? $group['menu_key'] ?? ''));
      if (!isset($groupKeySet[$menuKey])) continue;
      $title = trim((string)($group['title'] ?? ''));
      if ($title === '') {
        $pdo->rollBack();
        crm_admin_out(['ok' => false, 'error' => 'Titre de groupe obligatoire'], 400);
      }
      if (strlen($title) > 120) {
        $pdo->rollBack();
        crm_admin_out(['ok' => false, 'error' => 'Titre de groupe trop long'], 400);
      }
      $active = isset($group['active']) ? (int)(bool)$group['active'] : 1;
      $sortOrder = (int)($group['sortOrder'] ?? $group['sort_order'] ?? 100);
      $groupStmt->execute([$title, $active, $sortOrder, $menuKey]);
    }

    $itemStmt = $pdo->prepare('UPDATE crm_menu_items SET group_key=?,icon_key=?,active=?,sort_order=?,updated_at=NOW() WHERE item_key=?');
    foreach ($items as $item) {
      $itemKey = trim((string)($item['itemKey'] ?? $item['item_key'] ?? ''));
      if (!isset($itemKeySet[$itemKey])) continue;
      $groupKey = trim((string)($item['groupKey'] ?? $item['group_key'] ?? ''));
      if (!isset($groupKeySet[$groupKey])) {
        $pdo->rollBack();
        crm_admin_out(['ok' => false, 'error' => 'Groupe de menu invalide'], 400);
      }
      $iconKey = trim((string)($item['iconKey'] ?? $item['icon_key'] ?? ''));
      if ($iconKey !== '' && !preg_match('/^[a-zA-Z0-9_-]{1,80}$/', $iconKey)) {
        $pdo->rollBack();
        crm_admin_out(['ok' => false, 'error' => 'Icone de menu invalide'], 400);
      }
      $active = isset($item['active']) ? (int)(bool)$item['active'] : 1;
      $sortOrder = (int)($item['sortOrder'] ?? $item['sort_order'] ?? 100);
      $itemStmt->execute([$groupKey, $iconKey, $active, $sortOrder, $itemKey]);
    }
    $pdo->commit();

    crm_admin_log($pdo, $actor, 'modification menu', 'configuration menu lateral');
    crm_admin_out(['ok' => true]);
  }

  if ($action === 'pages_bootstrap') {
    crm_admin_require_any($actor, ['pages.manage', 'platform.manage_modules']);
    $pages = $pdo->query('SELECT * FROM crm_pages ORDER BY active DESC,sort_order,title')->fetchAll();
    crm_admin_out(['ok' => true, 'pages' => array_map('crm_admin_page_row', $pages)]);
  }

  if ($action === 'save_page') {
    crm_admin_require_any($actor, ['pages.manage', 'platform.manage_modules']);
    $id = (int)($body['id'] ?? 0);
    $title = trim((string)($body['title'] ?? ''));
    $slugSource = trim((string)($body['slug'] ?? '')) ?: $title;
    $excerpt = trim((string)($body['excerpt'] ?? ''));
    $content = trim((string)($body['content'] ?? ''));
    $iconKey = trim((string)($body['iconKey'] ?? $body['icon_key'] ?? 'article'));
    $active = isset($body['active']) ? (int)(bool)$body['active'] : 1;
    $showInMenu = isset($body['showInMenu']) ? (int)(bool)$body['showInMenu'] : (isset($body['show_in_menu']) ? (int)(bool)$body['show_in_menu'] : 1);
    $sortOrder = (int)($body['sortOrder'] ?? $body['sort_order'] ?? 100);

    if ($title === '') crm_admin_out(['ok' => false, 'error' => 'Titre de page obligatoire'], 400);
    if (strlen($title) > 160) crm_admin_out(['ok' => false, 'error' => 'Titre de page trop long'], 400);
    if (strlen($excerpt) > 255) crm_admin_out(['ok' => false, 'error' => 'Resume trop long'], 400);
    if ($content === '') crm_admin_out(['ok' => false, 'error' => 'Contenu obligatoire'], 400);
    if (strlen($iconKey) > 80 || !preg_match('/^[a-zA-Z0-9_-]+$/', $iconKey)) $iconKey = 'article';

    $oldSlug = '';
    if ($id > 0) {
      $stmt = $pdo->prepare('SELECT slug FROM crm_pages WHERE id=?');
      $stmt->execute([$id]);
      $oldSlug = (string)($stmt->fetchColumn() ?: '');
      if ($oldSlug === '') crm_admin_out(['ok' => false, 'error' => 'Page introuvable'], 404);
    }

    $slug = crm_admin_unique_page_slug($pdo, $slugSource, $id);
    if ($id > 0) {
      $stmt = $pdo->prepare('UPDATE crm_pages SET title=?,slug=?,excerpt=?,content=?,icon_key=?,active=?,show_in_menu=?,sort_order=?,updated_at=NOW() WHERE id=?');
      $stmt->execute([$title, $slug, $excerpt, $content, $iconKey, $active, $showInMenu, $sortOrder, $id]);
      crm_admin_log($pdo, $actor, 'modification page CRM', $title);
    } else {
      $stmt = $pdo->prepare('INSERT INTO crm_pages(title,slug,excerpt,content,icon_key,active,show_in_menu,sort_order,created_at) VALUES(?,?,?,?,?,?,?,?,NOW())');
      $stmt->execute([$title, $slug, $excerpt, $content, $iconKey, $active, $showInMenu, $sortOrder]);
      $id = (int)$pdo->lastInsertId();
      crm_admin_log($pdo, $actor, 'creation page CRM', $title);
    }

    $stmt = $pdo->prepare('SELECT * FROM crm_pages WHERE id=?');
    $stmt->execute([$id]);
    $page = $stmt->fetch();
    if (!$page) crm_admin_out(['ok' => false, 'error' => 'Page introuvable'], 404);
    crm_admin_sync_page_menu_item($pdo, $page, $oldSlug);
    crm_admin_out(['ok' => true, 'page' => crm_admin_page_row($page)]);
  }

  if ($action === 'delete_page') {
    crm_admin_require_any($actor, ['pages.manage', 'platform.manage_modules']);
    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) crm_admin_out(['ok' => false, 'error' => 'Page invalide'], 400);

    $stmt = $pdo->prepare('SELECT * FROM crm_pages WHERE id=?');
    $stmt->execute([$id]);
    $page = $stmt->fetch();
    if (!$page) crm_admin_out(['ok' => false, 'error' => 'Page introuvable'], 404);

    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM crm_menu_items WHERE item_key=?')->execute(['cms-page:' . $page['slug']]);
    $pdo->prepare('DELETE FROM crm_pages WHERE id=?')->execute([$id]);
    $pdo->commit();
    crm_admin_log($pdo, $actor, 'suppression page CRM', (string)$page['title']);
    crm_admin_out(['ok' => true, 'id' => $id]);
  }

  if ($action === 'save_site') {
    crm_admin_require_any($actor, ['platform.manage_sites', 'platform.manage_modules']);
    $id = (int)($body['id'] ?? 0);
    $name = trim((string)($body['name'] ?? ''));
    $active = isset($body['active']) ? (int)(bool)$body['active'] : 1;
    if ($name === '') crm_admin_out(['ok' => false, 'error' => 'Nom du site obligatoire'], 400);
    if (strlen($name) > 120) crm_admin_out(['ok' => false, 'error' => 'Nom du site trop long'], 400);
    $morningStart = crm_admin_normalize_time($body, 'morningStart', 'morning_start', '07:30');
    $morningEnd = crm_admin_normalize_time($body, 'morningEnd', 'morning_end', '12:00');
    $afternoonStart = crm_admin_normalize_time($body, 'afternoonStart', 'afternoon_start', '13:30');
    $afternoonEnd = crm_admin_normalize_time($body, 'afternoonEnd', 'afternoon_end', '17:30');
    if (
      crm_admin_minutes($morningStart) >= crm_admin_minutes($morningEnd) ||
      crm_admin_minutes($afternoonStart) >= crm_admin_minutes($afternoonEnd) ||
      crm_admin_minutes($morningEnd) > crm_admin_minutes($afternoonStart)
    ) {
      crm_admin_out(['ok' => false, 'error' => 'Plages horaires incoherentes'], 400);
    }
    $slug = crm_admin_unique_slug($pdo, 'crm_sites', $name, $id);
    if ($id > 0) {
      $stmt = $pdo->prepare('UPDATE crm_sites SET name=?,slug=?,active=?,morning_start=?,morning_end=?,afternoon_start=?,afternoon_end=?,updated_at=NOW() WHERE id=?');
      $stmt->execute([$name, $slug, $active, $morningStart, $morningEnd, $afternoonStart, $afternoonEnd, $id]);
      crm_admin_log($pdo, $actor, 'modification site', $name);
    } else {
      $stmt = $pdo->prepare('INSERT INTO crm_sites(name,slug,active,morning_start,morning_end,afternoon_start,afternoon_end,created_at) VALUES(?,?,?,?,?,?,?,NOW())');
      $stmt->execute([$name, $slug, $active, $morningStart, $morningEnd, $afternoonStart, $afternoonEnd]);
      $id = (int)$pdo->lastInsertId();
      crm_admin_log($pdo, $actor, 'creation site', $name);
    }
    crm_admin_out(['ok' => true, 'id' => $id]);
  }

  if ($action === 'delete_site') {
    crm_admin_require_any($actor, ['platform.manage_sites']);
    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) crm_admin_out(['ok' => false, 'error' => 'Site invalide'], 400);

    $stmt = $pdo->prepare('SELECT id,name,slug FROM crm_sites WHERE id=? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    $site = $stmt->fetch();
    if (!$site) crm_admin_out(['ok' => false, 'error' => 'Site introuvable'], 404);

    $vehiclesCount = 0;
    if (crm_admin_table_exists($pdo, 'crm_vehicles')) {
      $stmt = $pdo->prepare('SELECT COUNT(*) FROM crm_vehicles WHERE site_id=?');
      $stmt->execute([$id]);
      $vehiclesCount = (int)$stmt->fetchColumn();
    }

    $reservationsCount = 0;
    if (crm_admin_table_exists($pdo, 'crm_reservations')) {
      $stmt = $pdo->prepare('SELECT COUNT(*) FROM crm_reservations WHERE site_id=?');
      $stmt->execute([$id]);
      $reservationsCount = (int)$stmt->fetchColumn();
    }

    $equipmentCount = 0;
    if (crm_admin_table_exists($pdo, 'crm_equipment_items')) {
      $stmt = $pdo->prepare('SELECT COUNT(*) FROM crm_equipment_items WHERE site_id=?');
      $stmt->execute([$id]);
      $equipmentCount = (int)$stmt->fetchColumn();
    }

    $equipmentRentalsCount = 0;
    if (crm_admin_table_exists($pdo, 'crm_equipment_rentals')) {
      $stmt = $pdo->prepare('SELECT COUNT(*) FROM crm_equipment_rentals WHERE site_id=?');
      $stmt->execute([$id]);
      $equipmentRentalsCount = (int)$stmt->fetchColumn();
    }

    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM crm_user_sites WHERE site_id=?')->execute([$id]);
    $used = $vehiclesCount > 0 || $reservationsCount > 0 || $equipmentCount > 0 || $equipmentRentalsCount > 0;
    if ($used) {
      $archiveName = substr((string)$site['name'], 0, 100) . ' supprime ' . $id;
      $archiveSlug = substr((string)($site['slug'] ?: crm_admin_slug((string)$site['name'])), 0, 110) . '-deleted-' . $id;
      $pdo->prepare('UPDATE crm_sites SET name=?,slug=?,active=0,deleted_at=NOW(),updated_at=NOW() WHERE id=?')->execute([$archiveName, $archiveSlug, $id]);
    } else {
      $pdo->prepare('DELETE FROM crm_sites WHERE id=?')->execute([$id]);
    }
    $pdo->commit();
    crm_admin_log($pdo, $actor, $used ? 'archivage site' : 'suppression site', (string)$site['name']);
    crm_admin_out(['ok' => true, 'id' => $id, 'archived' => $used]);
  }

  if ($action === 'save_module') {
    crm_admin_require_any($actor, ['platform.manage_modules']);
    $id = (int)($body['id'] ?? 0);
    $name = trim((string)($body['name'] ?? ''));
    $slugSource = trim((string)($body['slug'] ?? '')) ?: $name;
    $description = trim((string)($body['description'] ?? ''));
    $routePath = trim((string)($body['routePath'] ?? $body['route_path'] ?? ''));
    $menuBadge = trim((string)($body['menuBadge'] ?? $body['menu_badge'] ?? ''));
    $showMenuBadge = (int)(bool)($body['showMenuBadge'] ?? $body['show_menu_badge'] ?? false);
    $active = isset($body['active']) ? (int)(bool)$body['active'] : 1;
    $sortOrder = (int)($body['sortOrder'] ?? $body['sort_order'] ?? 100);
    if ($name === '') crm_admin_out(['ok' => false, 'error' => 'Nom du module obligatoire'], 400);
    if (strlen($menuBadge) > 40) crm_admin_out(['ok' => false, 'error' => 'Badge menu trop long'], 400);
    if ($routePath === '') $routePath = '/' . crm_admin_slug($slugSource);
    $slug = crm_admin_unique_slug($pdo, 'crm_modules', $slugSource, $id);
    if ($id > 0) {
      $stmt = $pdo->prepare('UPDATE crm_modules SET name=?,slug=?,description=?,route_path=?,menu_badge=?,show_menu_badge=?,active=?,sort_order=?,updated_at=NOW() WHERE id=?');
      $stmt->execute([$name, $slug, $description, $routePath, $menuBadge, $showMenuBadge, $active, $sortOrder, $id]);
      crm_admin_log($pdo, $actor, 'modification module', $name);
    } else {
      $stmt = $pdo->prepare('INSERT INTO crm_modules(name,slug,description,route_path,menu_badge,show_menu_badge,active,sort_order,created_at) VALUES(?,?,?,?,?,?,?,?,NOW())');
      $stmt->execute([$name, $slug, $description, $routePath, $menuBadge, $showMenuBadge, $active, $sortOrder]);
      $id = (int)$pdo->lastInsertId();
      crm_admin_log($pdo, $actor, 'creation module', $name);
    }
    crm_admin_out(['ok' => true, 'id' => $id]);
  }

  if ($action === 'save_user') {
    crm_admin_require_any($actor, ['platform.manage_users', 'platform.manage_roles']);
    $id = (int)($body['id'] ?? 0);
    $name = trim((string)($body['name'] ?? ''));
    $role = trim((string)($body['role'] ?? 'user'));
    $active = isset($body['active']) ? (int)(bool)$body['active'] : 1;
    $siteIds = array_map('intval', is_array($body['siteIds'] ?? null) ? $body['siteIds'] : []);
    $moduleIds = array_map('intval', is_array($body['moduleIds'] ?? null) ? $body['moduleIds'] : []);
    $permissionIds = array_map('intval', is_array($body['permissionIds'] ?? null) ? $body['permissionIds'] : []);
    if ($name === '') crm_admin_out(['ok' => false, 'error' => 'Nom utilisateur obligatoire'], 400);
    if (!in_array($role, ['admin', 'responsable', 'user', 'blocked'], true)) $role = 'user';
    if ($role === 'blocked') {
      $moduleIds = [];
      $permissionIds = [];
    }
    if (!$siteIds) $siteIds = array_map('intval', $pdo->query('SELECT id FROM crm_sites WHERE active=1 AND deleted_at IS NULL ORDER BY id LIMIT 1')->fetchAll(PDO::FETCH_COLUMN));
    if ($id > 0) {
      $stmt = $pdo->prepare('UPDATE crm_users SET name=?,role=?,active=?,updated_at=NOW() WHERE id=?');
      $stmt->execute([$name, $role, $active, $id]);
      crm_admin_log($pdo, $actor, 'modification utilisateur', $name);
    } else {
      $stmt = $pdo->prepare('INSERT INTO crm_users(name,role,active,created_at) VALUES(?,?,?,NOW())');
      $stmt->execute([$name, $role, $active]);
      $id = (int)$pdo->lastInsertId();
      crm_admin_log($pdo, $actor, 'creation utilisateur', $name);
    }
    crm_admin_sync_ids($pdo, 'crm_user_sites', 'site_id', $id, $siteIds);
    crm_admin_sync_ids($pdo, 'crm_user_modules', 'module_id', $id, $moduleIds);
    crm_admin_sync_ids($pdo, 'crm_user_permissions', 'permission_id', $id, $permissionIds);
    $pdo->prepare('UPDATE crm_reservations SET user_name=? WHERE user_id=?')->execute([$name, $id]);
    if (crm_admin_table_exists($pdo, 'crm_equipment_rentals')) {
      $pdo->prepare('UPDATE crm_equipment_rentals SET user_name=? WHERE user_id=?')->execute([$name, $id]);
    }
    crm_admin_out(['ok' => true, 'id' => $id]);
  }

  crm_admin_out(['ok' => false, 'error' => 'Action inconnue'], 404);
} catch (Throwable $error) {
  crm_admin_out(crm_api_exception_response($error), 500);
}
