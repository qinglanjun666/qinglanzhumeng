<?php
/**
 * 管理端：导入/更新大学数据（CSV）
 * POST /api/admin/import/universities
 *
 * 支持字段映射：name, province, city, type, mood_slug, keywords, one_line, logo_url, external_id(可选)
 * 认证：Header `X-Admin-Password` 或 form 字段 `password`
 * 参数：match_by = name|external_id（若external_id列不存在则自动回退到name并返回警告）
 * 返回：导入报告（成功、失败、错误原因、插入/更新计数）
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Admin-Password");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit();
}

// 引入必要文件
include_once '../config/database.php';
include_once '../models/University.php';

// 辅助方法：确保标签相关表存在（personality_tags 与 university_personality_tags）
function ensureTagTablesExist(PDO $db): void {
    // personality_tags
    $db->exec("CREATE TABLE IF NOT EXISTS personality_tags (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tag_name VARCHAR(100) NOT NULL,
        description VARCHAR(500) NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_tag_name (tag_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // university_personality_tags（与 universities.id 关联）
    $db->exec("CREATE TABLE IF NOT EXISTS university_personality_tags (
        university_id INT UNSIGNED NOT NULL,
        tag_id INT UNSIGNED NOT NULL,
        PRIMARY KEY (university_id, tag_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function getOrCreateTagId(PDO $db, string $tag_name): int {
    $sel = $db->prepare("SELECT id FROM personality_tags WHERE tag_name = :name LIMIT 1");
    $sel->execute([':name' => $tag_name]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);
    if ($row) return (int)$row['id'];
    $desc = $tag_name; // 简单描述使用同名，后续可在后台完善
    $ins = $db->prepare("INSERT INTO personality_tags (tag_name, description) VALUES (:name, :desc)");
    $ins->execute([':name' => $tag_name, ':desc' => $desc]);
    return (int)$db->lastInsertId();
}

// 兼容旧结构：universities_basic 表及其映射
function ensureBasicAndGetId(PDO $db, string $name, string $province, string $city, string $type, ?string $keywords, ?string $external_id): ?int {
    try {
        $sel = $db->prepare("SELECT id FROM universities_basic WHERE name = :name LIMIT 1");
        $sel->execute([':name' => $name]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null; // 旧表不存在则忽略
    }

    $region = $province . (strlen($city) ? ('-' . $city) : '');
    $nature = '公立';
    $level = null;
    if ($external_id) {
        if (stripos($external_id, '985') === 0) { $level = '双一流/985/211'; }
        elseif (stripos($external_id, '211') === 0) { $level = '211'; }
    }
    if (!$level) { $level = $type ?: '综合'; }

    $majorsJson = null;
    if ($keywords) {
        $parts = preg_split('/\|+|;+|,+/', $keywords);
        $parts = array_values(array_filter(array_map('trim', $parts), fn($x) => $x !== ''));
        $majorsJson = json_encode($parts, JSON_UNESCAPED_UNICODE);
    }

    if (isset($row['id'])) {
        $id = (int)$row['id'];
        $upd = $db->prepare("UPDATE universities_basic SET region=:region, nature=:nature, level=:level, key_majors=:key_majors WHERE id=:id");
        $upd->execute([
            ':region' => $region,
            ':nature' => $nature,
            ':level' => $level,
            ':key_majors' => $majorsJson,
            ':id' => $id
        ]);
        return $id;
    } else {
        try {
            $ins = $db->prepare("INSERT INTO universities_basic (name, region, nature, level, key_majors) VALUES (:name,:region,:nature,:level,:key_majors)");
            $ins->execute([
                ':name' => $name,
                ':region' => $region,
                ':nature' => $nature,
                ':level' => $level,
                ':key_majors' => $majorsJson
            ]);
            return (int)$db->lastInsertId();
        } catch (Exception $e) {
            return null;
        }
    }
}

// 检测 university_personality_tags 表结构（支持旧：university_basic_id/personality_tag_id；新：university_id/tag_id）
function detectUptSchema(PDO $db): array {
    try {
        $colsStmt = $db->prepare("SHOW COLUMNS FROM university_personality_tags");
        $colsStmt->execute();
        $cols = $colsStmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $hasBasic = in_array('university_basic_id', $cols) && in_array('personality_tag_id', $cols);
        $hasUniv = in_array('university_id', $cols) && in_array('tag_id', $cols);
        // 检测 university_id 的外键引用目标（若存在）
        $refTarget = null;
        if ($hasUniv) {
            try {
                $fkStmt = $db->prepare("SELECT REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'university_personality_tags' AND COLUMN_NAME = 'university_id' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1");
                $fkStmt->execute();
                $refTarget = $fkStmt->fetchColumn() ?: null;
            } catch (Exception $e) { /* ignore */ }
        }
        return [ 'has_basic' => $hasBasic, 'has_univ' => $hasUniv, 'univ_ref' => $refTarget ];
    } catch (Exception $e) {
        return [ 'has_basic' => false, 'has_univ' => false, 'univ_ref' => null ];
    }
}

function bindTagToUniversity(PDO $db, int $university_id, int $tag_id, array $uni_basic_data = []): void {
    $schema = detectUptSchema($db);
    if ($schema['has_basic']) {
        // 旧结构：需要 universities_basic.id
        $basic_id = ensureBasicAndGetId(
            $db,
            $uni_basic_data['name'] ?? '',
            $uni_basic_data['province'] ?? '',
            $uni_basic_data['city'] ?? '',
            $uni_basic_data['type'] ?? '',
            $uni_basic_data['keywords'] ?? null,
            $uni_basic_data['external_id'] ?? null
        );
        if (!$basic_id) return;
        $chk = $db->prepare("SELECT 1 FROM university_personality_tags WHERE university_basic_id = :uid AND personality_tag_id = :tid LIMIT 1");
        $chk->execute([':uid' => $basic_id, ':tid' => $tag_id]);
        if (!$chk->fetch()) {
            $ins = $db->prepare("INSERT INTO university_personality_tags (university_basic_id, personality_tag_id) VALUES (:uid, :tid)");
            $ins->execute([':uid' => $basic_id, ':tid' => $tag_id]);
        }
    } elseif ($schema['has_univ']) {
        // 新结构字段名，但需要判断外键指向
        if (isset($schema['univ_ref']) && $schema['univ_ref'] === 'universities_basic') {
            // 字段为 university_id，但外键指向 universities_basic：使用 basic_id 写入
            $basic_id = ensureBasicAndGetId(
                $db,
                $uni_basic_data['name'] ?? '',
                $uni_basic_data['province'] ?? '',
                $uni_basic_data['city'] ?? '',
                $uni_basic_data['type'] ?? '',
                $uni_basic_data['keywords'] ?? null,
                $uni_basic_data['external_id'] ?? null
            );
            if (!$basic_id) return;
            $chk = $db->prepare("SELECT 1 FROM university_personality_tags WHERE university_id = :uid AND tag_id = :tid LIMIT 1");
            $chk->execute([':uid' => $basic_id, ':tid' => $tag_id]);
            if (!$chk->fetch()) {
                $ins = $db->prepare("INSERT INTO university_personality_tags (university_id, tag_id) VALUES (:uid, :tid)");
                $ins->execute([':uid' => $basic_id, ':tid' => $tag_id]);
            }
        } else {
            // 正常：university_id 指向 universities.id
            $chk = $db->prepare("SELECT 1 FROM university_personality_tags WHERE university_id = :uid AND tag_id = :tid LIMIT 1");
            $chk->execute([':uid' => $university_id, ':tid' => $tag_id]);
            if (!$chk->fetch()) {
                $ins = $db->prepare("INSERT INTO university_personality_tags (university_id, tag_id) VALUES (:uid, :tid)");
                $ins->execute([':uid' => $university_id, ':tid' => $tag_id]);
            }
        }
    } else {
        // 表不存在或结构未知：尝试创建新结构并写入
        ensureTagTablesExist($db);
        $chk = $db->prepare("SELECT 1 FROM university_personality_tags WHERE university_id = :uid AND tag_id = :tid LIMIT 1");
        $chk->execute([':uid' => $university_id, ':tid' => $tag_id]);
        if (!$chk->fetch()) {
            $ins = $db->prepare("INSERT INTO university_personality_tags (university_id, tag_id) VALUES (:uid, :tid)");
            $ins->execute([':uid' => $university_id, ':tid' => $tag_id]);
        }
    }
}

// 简单认证：环境变量 HJ_ADMIN_PASSWORD 或默认值
$env_password = getenv('HJ_ADMIN_PASSWORD');
if ($env_password === false || $env_password === '') {
    $env_password = 'zxasqw123456';
}

$provided_password = null;
// 优先使用 Header
if (isset($_SERVER['HTTP_X_ADMIN_PASSWORD'])) {
    $provided_password = $_SERVER['HTTP_X_ADMIN_PASSWORD'];
}
// 其次使用 form 字段
if (!$provided_password && isset($_POST['password'])) {
    $provided_password = $_POST['password'];
}

if (!$provided_password || $provided_password !== $env_password) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized", "error" => "Invalid admin password"]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        http_response_code(500);
        echo json_encode(["message" => "Database connection failed"]);
        exit();
    }

    $university = new University($db);

    // 解析 match_by 参数
    $match_by = isset($_POST['match_by']) ? strtolower(trim($_POST['match_by'])) : 'name';
    if (!in_array($match_by, ['name', 'external_id'])) {
        $match_by = 'name';
    }

    // 检查 external_id 列是否存在
    $externalIdSupported = false;
    if ($match_by === 'external_id') {
        $checkStmt = $db->prepare("SHOW COLUMNS FROM universities LIKE 'external_id'");
        $checkStmt->execute();
        $col = $checkStmt->fetch();
        if ($col) {
            $externalIdSupported = true;
        } else {
            // 回退到 name
            $match_by = 'name';
        }
    }

    // 读取 CSV 数据：优先文件，其次文本
    $csvRows = [];
    $errors = [];
    $warnings = [];

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $_FILES['file']['tmp_name'];
        $handle = fopen($tmpPath, 'r');
        if ($handle === false) {
            http_response_code(400);
            echo json_encode(["message" => "Failed to read uploaded file"]);
            exit();
        }
        while (($data = fgetcsv($handle)) !== false) {
            $csvRows[] = $data;
        }
        fclose($handle);
    } elseif (isset($_POST['csv_text'])) {
        $csv_text = trim($_POST['csv_text']);
        if ($csv_text === '') {
            http_response_code(400);
            echo json_encode(["message" => "csv_text is empty"]);
            exit();
        }
        $lines = preg_split('/\r\n|\n|\r/', $csv_text);
        foreach ($lines as $line) {
            if ($line === '') continue;
            $csvRows[] = str_getcsv($line);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "No CSV data provided. Upload a file or send csv_text."]);
        exit();
    }

    if (empty($csvRows)) {
        http_response_code(400);
        echo json_encode(["message" => "CSV is empty"]);
        exit();
    }

    // 处理表头
    $header = array_map(function($h) { return strtolower(trim($h)); }, $csvRows[0]);
    $rows = array_slice($csvRows, 1);

    $required = ['name', 'province', 'city', 'type', 'mood_slug'];
    foreach ($required as $req) {
        if (!in_array($req, $header)) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required column: $req"]);
            exit();
        }
    }

    $inserted = 0;
    $updated = 0;
    $success = 0;
    $failed = 0;

    // 提示 external_id 支持情况
    if ($match_by === 'name' && in_array('external_id', $header)) {
        $warnings[] = 'external_id column present but not used (fallback to name)';
    }
    if (!$externalIdSupported && isset($_POST['match_by']) && strtolower($_POST['match_by']) === 'external_id') {
        $warnings[] = 'external_id match requested but column not found; fell back to name';
    }

    // 建立 header 映射索引
    $idx = array_flip($header);

    foreach ($rows as $i => $row) {
        // 组装数据
        $name = trim($row[$idx['name']] ?? '');
        $province = trim($row[$idx['province']] ?? '');
        $city = trim($row[$idx['city']] ?? '');
        $type = trim($row[$idx['type']] ?? '');
        $mood_slug = trim($row[$idx['mood_slug']] ?? '');
        $keywords = isset($idx['keywords']) ? trim($row[$idx['keywords']] ?? '') : '';
        $one_line = isset($idx['one_line']) ? trim($row[$idx['one_line']] ?? '') : '';
        $logo_url = isset($idx['logo_url']) ? trim($row[$idx['logo_url']] ?? '') : null;
        $external_id = isset($idx['external_id']) ? trim($row[$idx['external_id']] ?? '') : null;
        // tags 列（可选：tags 或 personality_tags）
        $tags_str = '';
        if (isset($idx['tags'])) { $tags_str = trim($row[$idx['tags']] ?? ''); }
        elseif (isset($idx['personality_tags'])) { $tags_str = trim($row[$idx['personality_tags']] ?? ''); }

        // 基本验证
        if ($name === '' || $province === '' || $city === '' || $type === '' || $mood_slug === '') {
            $failed++;
            $errors[] = [
                'row' => $i + 2, // 加上表头
                'name' => $name,
                'error' => 'Missing required fields'
            ];
            continue;
        }

        // 验证 mood_slug
        if (!$university->isValidMoodSlug($mood_slug)) {
            $failed++;
            $errors[] = [
                'row' => $i + 2,
                'name' => $name,
                'error' => 'Invalid mood_slug: ' . $mood_slug
            ];
            continue;
        }

        // 构造数据数组
        $data = [
            'name' => $name,
            'province' => $province,
            'city' => $city,
            'type' => $type,
            'mood_slug' => $mood_slug,
            'keywords' => $keywords,
            'one_line' => $one_line,
            'logo_url' => $logo_url,
            'external_id' => $external_id
        ];

        // 匹配模式：当前仅支持 name；external_id 存在时也可支持（如果列存在）
        if ($match_by === 'external_id' && $externalIdSupported && $external_id) {
            $result = $university->upsertUniversityByExternalId($data);
        } else {
            $result = $university->upsertUniversityByName($data);
        }

        if ($result && isset($result['action'])) {
            $success++;
            if ($result['action'] === 'inserted') {
                $inserted++;
            } elseif ($result['action'] === 'updated') {
                $updated++;
            }

            // 写入 tags → universities 映射（基于 universities.id）
            if ($tags_str !== '') {
                ensureTagTablesExist($db);
                $univ_id = isset($result['id']) ? (int)$result['id'] : null;
                if ($univ_id) {
                    $tagParts = preg_split('/\|+|;+|,+/', $tags_str);
                    $tagParts = array_values(array_filter(array_map('trim', $tagParts), fn($x) => $x !== ''));
                    foreach ($tagParts as $tname) {
                        $tid = getOrCreateTagId($db, $tname);
                        bindTagToUniversity($db, $univ_id, $tid, [
                            'name' => $name,
                            'province' => $province,
                            'city' => $city,
                            'type' => $type,
                            'keywords' => $keywords,
                            'external_id' => $external_id
                        ]);
                    }
                } else {
                    $warnings[] = 'Row ' . ($i + 2) . ': missing university id for tag binding';
                }
            }
        } else {
            $failed++;
            $errors[] = [
                'row' => $i + 2,
                'name' => $name,
                'error' => 'Unknown error during upsert'
            ];
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'matched_by' => $match_by,
        'inserted_count' => $inserted,
        'updated_count' => $updated,
        'success_count' => $success,
        'failure_count' => $failed,
        'errors' => $errors,
        'warnings' => $warnings
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
?>