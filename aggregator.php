<?php

$url = "https://raw.githubusercontent.com/nyeinkokoaung404/V2ray-Configs/refs/heads/main/Splitted-By-Protocol/trojan.txt";
$raw = file_get_contents($url);
$content = base64_decode($raw);
$trojan = explode("\n", $content);

$trojan = array_filter($trojan, fn($line) => strpos($line, "trojan://") === 0);
$trojan = array_values($trojan);

foreach ($trojan as $i => $link) {
  $u = @parse_url($link);
  if (!$u || !isset($u['scheme']) || strtolower($u['scheme']) !== 'trojan') {
    continue;
  }

  parse_str($u['query'] ?? "", $query);

  $name      = 'TROJAN-' . ($i + 1);
  $password  = $u['user'] ?? '';
  $server    = "quiz.vidio.com";
  $port      = isset($u['port']) ? (int)$u['port'] : 443;
  $type      = strtolower($query['type'] ?? '');
  $sni       = $query['sni']  ?? null;
  $hostHdr   = $query['host'] ?? null;
  $path      = isset($query['path']) ? urldecode($query['path']) : '/';
  $fp        = $query['fp']   ?? null;

  // YAML string escape
  $yq = fn(string $s): string => '"' . str_replace(['\\', '"'], ['\\\\', '\"'], $s) . '"';

  // 4) Build proxy entry
  $entry   = [];
  $entry[] = "- name: " . $yq($name);
  $entry[] = "  type: trojan";
  $entry[] = "  server: " . $yq($server);
  $entry[] = "  port: $port";
  $entry[] = "  password: " . $yq($password);
  if ($sni) $entry[] = "  sni: " . $yq($sni);
  $entry[] = "  skip-cert-verify: false";
  $entry[] = "  udp: true";
  if ($fp) $entry[] = "  client-fingerprint: " . $yq($fp);

  if ($type === 'ws' || $hostHdr || $path) {
    $entry[] = "  network: ws";
    $entry[] = "  ws-opts:";
    $entry[] = "    path: " . $yq($path ?: '/');
    if ($hostHdr || $sni) {
      $entry[] = "    headers:";
      $entry[] = "      Host: " . $yq($hostHdr ?: $sni);
    }
  }

  $proxies[] = implode("\n", $entry);
}

// 5) Print YAML
echo "proxies:\n";
foreach ($proxies as $p) {
  echo "" . str_replace("\n", "\n", $p) . "\n";
}
