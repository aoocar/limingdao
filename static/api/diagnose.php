<?php
/**
 * GitHub API 连接诊断脚本
 * 用完后请立即删除此文件！
 */
header('Content-Type: text/plain; charset=utf-8');

echo "===== Limingdao 提交接口诊断 =====\n\n";

// 1. 检查配置文件
$configPath = __DIR__ . '/.submit_config.json';
echo "[1] 配置文件路径: {$configPath}\n";
echo "[1] 配置文件存在: " . (file_exists($configPath) ? '✅ 是' : '❌ 否') . "\n";

if (!file_exists($configPath)) {
    echo "\n❌ 配置文件不存在，请先创建！\n";
    exit;
}

$config = json_decode(file_get_contents($configPath), true);
if (!$config) {
    echo "❌ 配置文件 JSON 解析失败，请检查格式\n";
    echo "文件内容: " . file_get_contents($configPath) . "\n";
    exit;
}

$token = $config['github_token'] ?? '';
$owner = $config['repo_owner'] ?? 'aoocar';
$repo  = $config['repo_name'] ?? 'limingdao';

echo "[1] Token 长度: " . strlen($token) . " 字符\n";
echo "[1] Token 前缀: " . substr($token, 0, 6) . "***\n";
echo "[1] Repo: {$owner}/{$repo}\n\n";

// 2. 检查 cURL
echo "[2] cURL 扩展: " . (function_exists('curl_init') ? '✅ 已安装' : '❌ 未安装') . "\n";
echo "[2] cURL 版本: " . (curl_version()['version'] ?? '未知') . "\n";
echo "[2] SSL 版本: " . (curl_version()['ssl_version'] ?? '未知') . "\n\n";

// 3. 测试 DNS 解析
echo "[3] DNS 解析 api.github.com: ";
$ip = gethostbyname('api.github.com');
if ($ip === 'api.github.com') {
    echo "❌ 解析失败\n";
} else {
    echo "✅ {$ip}\n";
}

// 4. 测试 GitHub API 连接（不需要 Token）
echo "\n[4] 测试 GitHub API 基础连接...\n";
$ch = curl_init('https://api.github.com/');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HTTPHEADER => ['User-Agent: Limingdao-Test'],
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
$errno = curl_errno($ch);
curl_close($ch);

if ($err) {
    echo "❌ 连接失败: [{$errno}] {$err}\n";
} else {
    echo "✅ HTTP {$code}\n";
}

// 5. 测试带 Token 的 API 调用
echo "\n[5] 测试 Token 有效性（获取用户信息）...\n";
$ch = curl_init('https://api.github.com/user');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: token {$token}",
        'Accept: application/vnd.github.v3+json',
        'User-Agent: Limingdao-Test',
    ],
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo "❌ 请求失败: {$err}\n";
} else {
    echo "HTTP {$code}: ";
    $data = json_decode($res, true);
    if ($code === 200) {
        echo "✅ Token 有效，用户: " . ($data['login'] ?? '未知') . "\n";
    } elseif ($code === 401) {
        echo "❌ Token 无效或已过期！请重新生成\n";
        echo "响应: {$res}\n";
    } else {
        echo "⚠️ 异常响应\n";
        echo "响应: {$res}\n";
    }
}

// 6. 测试仓库访问
echo "\n[6] 测试仓库访问 ({$owner}/{$repo})...\n";
$ch = curl_init("https://api.github.com/repos/{$owner}/{$repo}/git/ref/heads/main");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: token {$token}",
        'Accept: application/vnd.github.v3+json',
        'User-Agent: Limingdao-Test',
    ],
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo "❌ 请求失败: {$err}\n";
} else {
    echo "HTTP {$code}: ";
    if ($code === 200) {
        $data = json_decode($res, true);
        echo "✅ 仓库可访问，main SHA: " . substr($data['object']['sha'] ?? '', 0, 8) . "\n";
    } elseif ($code === 404) {
        echo "❌ 仓库不存在或 Token 无权访问\n";
    } else {
        echo "⚠️ 异常\n";
        echo "响应: " . substr($res, 0, 300) . "\n";
    }
}

echo "\n===== 诊断完成 =====\n";
echo "⚠️ 请用完后立即删除此文件！\n";
