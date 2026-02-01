<?php
/*
  共有ユーティリティ関数群
  - このファイルは common.php の早い段階で読み込まれる想定
*/

/**
 * 指定パスの更新日時をバージョン文字列（YmdHis）で返します。
 * ファイルが存在しない／取得失敗時は空文字を返します。
 */
function mtime_ver(string $path): string
{
    // キャッシュ付きラッパに委譲（挙動は同じ）
    return fs_mtime_str_cached($path);
}

// ベースパス配下の相対パスについて、更新バージョン文字列（YmdHis）を返す
function mtime_ver_at_base(string $fileDirectory, string $relativePath): string
{
    $base = resolve_base_path($fileDirectory);
    $relativePath = ltrim($relativePath, '/');
    return mtime_ver($base . $relativePath);
}

/**
 * 表示用ファイルのベースパスを返す。
 * 実運用: /virtual/usr/public_html/{fileDirectory}/
 * ローカル: ./
 */
function resolve_base_path(string $fileDirectory): string
{
    $virtual = '/virtual/usr/public_html/' . $fileDirectory;
    if (is_dir($virtual)) {
        return rtrim($virtual, '/') . '/';
    }
    return './';
}

// 軽量FSキャッシュ（1リクエスト内でのみ有効）
function fs_is_file_cached(string $path): bool
{
    static $cache = [];
    if (!array_key_exists($path, $cache)) {
        $cache[$path] = is_file($path);
    }
    return $cache[$path];
}

function fs_mtime_str_cached(string $path): string
{
    static $cache = [];
    if (!array_key_exists($path, $cache)) {
        if (!fs_is_file_cached($path)) return $cache[$path] = '';
        $t = filemtime($path);
        $cache[$path] = ($t !== false) ? date('YmdHis', $t) : '';
    }
    return $cache[$path];
}

function fs_getimagesize_cached(string $path)
{
    static $cache = [];
    if (!array_key_exists($path, $cache)) {
        if (!fs_is_file_cached($path)) return $cache[$path] = [0,0,0,''];
        $size = @getimagesize($path);
        $cache[$path] = (is_array($size) && count($size) >= 4) ? $size : [0,0,0,''];
    }
    return $cache[$path];
}

// UNIXタイムを返すmtime（キャッシュ）
function fs_mtime_cached(string $path): int
{
    static $cache = [];
    if (!array_key_exists($path, $cache)) {
        if (!fs_is_file_cached($path)) return $cache[$path] = 0;
        $t = @filemtime($path);
        $cache[$path] = ($t !== false) ? (int)$t : 0;
    }
    return $cache[$path];
}

// ファイルサイズ（バイト）を返す（キャッシュ）
function fs_filesize_cached(string $path): int
{
    static $cache = [];
    if (!array_key_exists($path, $cache)) {
        if (!fs_is_file_cached($path)) return $cache[$path] = 0;
        $s = @filesize($path);
        $cache[$path] = ($s !== false) ? (int)$s : 0;
    }
    return $cache[$path];
}

// テキスト読み込み（失敗時は既定値）
function read_text(string $path, string $default = ''): string
{
    $buf = @file_get_contents($path);
    return ($buf === false) ? $default : $buf;
}

// UTF-8のBOMを先頭から除去
function strip_utf8_bom(string $s): string
{
    return preg_replace('/^\xEF\xBB\xBF/', '', $s);
}

// テキスト読み込み（BOM除去版）
function read_text_no_bom(string $path, string $default = ''): string
{
    $buf = read_text($path, $default);
    return ($buf === '') ? $buf : strip_utf8_bom($buf);
}

/**
 * JSON-LD用のimage断片を生成（主画像が存在する場合のみ）
 * 例: "image": "https://example.com/page-id.png",
 */
function ldjson_image_fragment_for_page(string $globalMainImagePath, string $punyAddress, string $urlParamPage): string
{
    if (fs_is_file_cached($globalMainImagePath)) {
        // 呼び出し側で末尾にカンマが必要なフォーマットのため付与を維持
        return '"image": "https://' . $punyAddress . '/' . $urlParamPage . '.png",';
    }
    return '';
}

// ルートURL生成（末尾スラッシュ付）
function build_site_root_url(string $punyAddress): string
{
    return 'https://' . $punyAddress . '/';
}

// 現在ページのフルURL（クエリ形式）を返す
function build_current_view_url(string $punyAddress, string $urlParamPage): string
{
    return 'https://' . $punyAddress . '/?page=' . $urlParamPage;
}

// 現在ページの相対HREF（UI用）を返す
function build_current_view_href(string $urlParamPage): string
{
    return '?page=' . $urlParamPage;
}

// 任意パスを絶対URLへ
function abs_url(string $punyAddress, string $path): string
{
    $path = ltrim($path, '/');
    return 'https://' . $punyAddress . '/' . $path;
}

// 文字列を '-' で区切った最後の要素を返す（該当なしは空文字）
function last_part_after_dash(string $s): string
{
    $parts = explode('-', $s);
    return count($parts) ? end($parts) : '';
}

// deep-sengokuドメイン判定
function is_deep_sengoku(string $punyAddress): bool
{
    return $punyAddress === 'deep-sengoku.net';
}

// コンテンツ定義に対象ページが存在するか
function has_content_for_page($content_hash, string $urlParamPage): bool
{
    return is_array($content_hash ?? null)
        && isset($content_hash[$urlParamPage])
        && isset($content_hash[$urlParamPage]['html']);
}

// 外部URL判定（http/https）
function is_external_url(string $s): bool
{
    return (bool)preg_match('#^https?://#i', $s);
}

// JSONエンコード（表記揺れ抑止用の共通ヘルパ）
// 既定: Unicode/スラッシュをエスケープしない。必要に応じて追加フラグをOR指定。
function json_stringify($value, int $extraFlags = 0)
{
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | $extraFlags;
    $json = json_encode($value, $flags);
    return ($json === false) ? '""' : $json;
}

// 文字列正規化（現行仕様を維持）
// - CR(\r)除去
// - LF(\n)をスペース化
// - &nbsp; を除去
// - 連続空白を1つに
// - 前後trim
function normalize_text(string $s): string
{
    $s = str_replace("\r", '', $s);
    $s = str_replace("\n", ' ', $s);
    $s = str_replace('&nbsp;', '', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

// DOM要素から可視テキストを抽出し、上の正規化を適用（現行仕様に合わせてnodeValueを使用）
function normalize_dom_text($node): string
{
    $raw = strip_tags($node->nodeValue);
    return normalize_text($raw);
}

// 空白整形のみ（既存箇所の挙動を変えないための軽量版）
// - CR/LFをスペース化
// - 連続空白を1つに
// - trim
function normalize_spaces_only(string $s): string
{
    $s = str_replace(["\r", "\n"], ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

// 先頭座標からJSON-LD用geo断片を生成（存在しない場合は空文字）
function ldjson_geo_fragment_from_cached_positions(string $urlParamPage): string
{
    global $pageMapPositions;
    if (is_array($pageMapPositions)
        && isset($pageMapPositions[$urlParamPage])
        && isset($pageMapPositions[$urlParamPage][0])
        && is_array($pageMapPositions[$urlParamPage][0])
        && count($pageMapPositions[$urlParamPage][0]) >= 2) {
        $lat = (float)$pageMapPositions[$urlParamPage][0][0];
        $lng = (float)$pageMapPositions[$urlParamPage][0][1];
        return "    \"geo\": {\n        \"@type\": \"GeoCoordinates\",\n        \"latitude\": $lat,\n        \"longitude\": $lng\n    }";
    }
    return '';
}

// 先頭座標からJSON-LD用geo+hasMap断片を生成（存在しない場合は空文字）
// hasMap形式は短縮版 Google Maps 直URL
// 例: "hasMap": "https://www.google.com/maps/@35.732222,139.664722,8z"
function ldjson_geo_hasmap_fragment_from_cached_positions(string $urlParamPage, int $zoom = 8): string
{
    global $pageMapPositions;
    if (is_array($pageMapPositions)
        && isset($pageMapPositions[$urlParamPage])
        && isset($pageMapPositions[$urlParamPage][0])
        && is_array($pageMapPositions[$urlParamPage][0])
        && count($pageMapPositions[$urlParamPage][0]) >= 2) {
        $lat = (float)$pageMapPositions[$urlParamPage][0][0];
        $lng = (float)$pageMapPositions[$urlParamPage][0][1];
        $mapUrl = 'https://www.google.com/maps/@' . $lat . ',' . $lng . ',' . $zoom . 'z';
        return "\"geo\": {\n        \"@type\": \"GeoCoordinates\",\n        \"latitude\": $lat,\n        \"longitude\": $lng\n    },\n    \"hasMap\": \"$mapUrl\"";
    }
    return '';
}

?>
