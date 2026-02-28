<?php
/**
 * Limingdao 用户提交网站处理脚本
 * 
 * 运行环境：阿里云宝塔面板 (PHP 7.x+)
 * 功能：接收用户提交的网站信息，调用 GitHub API 创建 PR
 * 
 * 部署后需在服务器上创建配置文件（详见 README）
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '仅支持 POST 请求']);
    exit;
}

// ===== 读取配置 =====
$configPath = __DIR__ . '/.submit_config.json';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['error' => '服务端配置缺失，请联系管理员']);
    error_log('[Limingdao Submit] 配置文件不存在: ' . $configPath);
    exit;
}

$config = json_decode(file_get_contents($configPath), true);
$githubToken = $config['github_token'] ?? '';
$repoOwner   = $config['repo_owner'] ?? 'aoocar';
$repoName    = $config['repo_name'] ?? 'limingdao';

if (empty($githubToken)) {
    http_response_code(500);
    echo json_encode(['error' => '服务端配置错误']);
    exit;
}

// ===== 读取并校验表单数据 =====
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => '无效的请求数据']);
    exit;
}

$title       = trim($input['title'] ?? '');
$sitelink    = trim($input['sitelink'] ?? '');
$description = trim($input['description'] ?? '');
$category    = trim($input['category'] ?? '');
$subCategory = trim($input['subCategory'] ?? '默认');
$logo        = trim($input['logo'] ?? '');

// 必填校验
if (empty($title) || empty($sitelink) || empty($description) || empty($category) || empty($subCategory)) {
    http_response_code(400);
    echo json_encode(['error' => '请填写所有必填字段']);
    exit;
}

// URL 格式校验
if (!filter_var($sitelink, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => '网站链接格式不正确，请包含 https://']);
    exit;
}

// XSS 防护
$title       = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars(mb_substr($description, 0, 200), ENT_QUOTES, 'UTF-8');
$category    = ($category === '其他') ? '未分类' : htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
$subCategory = htmlspecialchars($subCategory, ENT_QUOTES, 'UTF-8');

// ===== 生成 Markdown 内容 =====
$safeTitle = preg_replace('/[<>:"\/\\\\|?*\x00-\x1f]/', '', $title);
$safeTitle = preg_replace('/\s+/', ' ', $safeTitle);
$safeTitle = mb_substr(trim($safeTitle), 0, 80);

$fileName  = "content/bookmarks/{$safeTitle}.md";
$timestamp = date('c');

$logoLine = !empty($logo) ? "logo: \"{$logo}\"" : '';

$mdLines = array_filter([
    '---',
    "title: \"{$title}\"",
    "sitelink: \"{$sitelink}\"",
    "description: \"{$description}\"",
    "categories: \"{$category}\"",
    "sub-category: \"{$subCategory}\"",
    $logoLine,
    'weight: 10',
    'recommend: 0',
    "date: {$timestamp}",
    '---',
    '',
    $description,
    ''
], function($line) { return $line !== ''; });

$mdContent = implode("\n", $mdLines);

// ===== GitHub API 调用 =====

/**
 * 封装 GitHub API 调用（带重试）
 */
function githubApi($url, $token, $method = 'GET', $data = null, $maxRetries = 2) {
    $attempts = 0;
    while ($attempts <= $maxRetries) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: token {$token}",
                'Accept: application/vnd.github.v3+json',
                'Content-Type: application/json',
                'User-Agent: Limingdao-Submit-Bot'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $attempts++;
            error_log("[Limingdao Submit] cURL错误 (尝试 {$attempts}): {$error}");
            if ($attempts <= $maxRetries) {
                sleep(2); // 重试前等待
                continue;
            }
            return ['error' => true, 'message' => '网络连接失败，请稍后重试', 'http_code' => 0];
        }

        return [
            'error'     => ($httpCode >= 400),
            'http_code' => $httpCode,
            'data'      => json_decode($response, true),
            'raw'       => $response
        ];
    }
    return ['error' => true, 'message' => '请求超时', 'http_code' => 0];
}

$apiBase = "https://api.github.com/repos/{$repoOwner}/{$repoName}";

// --- 1. 获取 main 分支 SHA ---
$refResult = githubApi("{$apiBase}/git/ref/heads/main", $githubToken);
if ($refResult['error']) {
    http_response_code(500);
    echo json_encode(['error' => '无法连接到代码仓库，请稍后重试']);
    error_log('[Limingdao Submit] 获取main分支失败: ' . ($refResult['raw'] ?? $refResult['message'] ?? ''));
    exit;
}

$mainSha = $refResult['data']['object']['sha'];

// --- 2. 创建提交分支 ---
$branchName = 'submit/' . time() . '-' . preg_replace('/\s/', '-', mb_substr($safeTitle, 0, 20));

$branchResult = githubApi("{$apiBase}/git/refs", $githubToken, 'POST', [
    'ref' => "refs/heads/{$branchName}",
    'sha' => $mainSha
]);

if ($branchResult['error']) {
    http_response_code(500);
    echo json_encode(['error' => '创建提交分支失败，请稍后重试']);
    error_log('[Limingdao Submit] 创建分支失败: ' . ($branchResult['raw'] ?? ''));
    exit;
}

// --- 3. 在新分支创建文件 ---
$fileResult = githubApi("{$apiBase}/contents/{$fileName}", $githubToken, 'PUT', [
    'message' => "📎 用户提交网站: {$title}",
    'content' => base64_encode($mdContent),
    'branch'  => $branchName
]);

if ($fileResult['error']) {
    if ($fileResult['http_code'] === 422) {
        http_response_code(409);
        echo json_encode(['error' => '该网站名称已被收录，请更换名称或联系管理员']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => '创建书签文件失败']);
    }
    error_log('[Limingdao Submit] 创建文件失败: ' . ($fileResult['raw'] ?? ''));
    exit;
}

// --- 4. 创建 Pull Request ---
$prBody = implode("\n", [
    '## 用户提交的网站收录',
    '',
    '| 字段 | 内容 |',
    '|---|---|',
    "| **网站名称** | {$title} |",
    "| **链接** | {$sitelink} |",
    "| **简介** | " . mb_substr($description, 0, 100) . " |",
    "| **一级分类** | {$category} |",
    "| **二级分类** | {$subCategory} |",
    "| **Logo** | " . ($logo ?: '自动抓取') . " |",
    '',
    "> 提交时间: {$timestamp}",
    '',
    "### 生成的文件",
    "`{$fileName}`",
    '',
    '---',
    '✅ 合并此 PR 即完成网站收录，书签将在下次部署时自动上线。'
]);

$prResult = githubApi("{$apiBase}/pulls", $githubToken, 'POST', [
    'title' => "📎 收录网站: {$title}",
    'head'  => $branchName,
    'base'  => 'main',
    'body'  => $prBody
]);

if ($prResult['error']) {
    http_response_code(500);
    echo json_encode(['error' => '创建审核请求失败']);
    error_log('[Limingdao Submit] 创建PR失败: ' . ($prResult['raw'] ?? ''));
    exit;
}

// ===== 成功 =====
echo json_encode([
    'success' => true,
    'message' => '🎉 提交成功！管理员审核通过后将自动上线。'
]);
