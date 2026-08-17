<?php
/**
 * Script de Auditoria Rápida - Kitchen Manager
 * Acesse via navegador em: http://seu-site.local/wp-content/plugins/kitchen-manager/audit.php
 * OU rode via terminal / comando.
 */

define('KM_ROOT', __DIR__);

function scanDirRecursive($dir) {
    $results = [];
    $files = scandir($dir);
    foreach ($files as $value) {
        if ($value === '.' || $value === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $value;
        if (is_dir($path)) {
            $results = array_merge($results, scanDirRecursive($path));
        } else {
            if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $results[] = $path;
            }
        }
    }
    return $results;
}

$allPhpFiles = scanDirRecursive(KM_ROOT);

echo "<h1>🔍 Auditoria de Arquivos — Kitchen Manager</h1>";
echo "<p>Total de arquivos PHP escaneados: <strong>" . count($allPhpFiles) . "</strong></p>";
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; width:100%; font-family:sans-serif;'>";
echo "<tr style='background:#f0f0f1;'><th>Caminho Relativo</th><th>Tamanho</th><th>Classe / Função Detectada</th><th>Avisos de Código Legado / Suspeito</th></tr>";

foreach ($allPhpFiles as $filePath) {
    $relative = str_replace(KM_ROOT . DIRECTORY_SEPARATOR, '', $filePath);
    $content = file_get_contents($filePath);
    $size = round(filesize($filePath) / 1024, 2) . ' KB';

    $warnings = [];

    // Checagem de redirecionamentos que causam headers already sent
    if (strpos($content, 'wp_redirect') !== false || strpos($content, 'wp_safe_redirect') !== false) {
        if (strpos($relative, 'Admin') !== false || strpos($relative, 'Views') !== false) {
            $warnings[] = "⚠️ Contém <code>wp_redirect</code> em Admin/View (pode causar 'headers already sent')";
        }
    }

    // Checagem de métodos legados conhecidos
    if (strpos($content, 'createRecipe(') !== false) {
        $warnings[] = "❌ Chama método legado <code>createRecipe()</code> (substituído por <code>saveRecipe</code>)";
    }

    // Checagem de referências antigas de tabelas
    if (strpos($content, 'wp_km_') !== false) {
        $warnings[] = "⚠️ Usa prefixo estático 'wp_km_' em vez de dynamic <code>\$wpdb->prefix</code>";
    }

    // Detecta classes
    preg_match('/class\s+([a-zA-Z0-9_]+)/', $content, $classMatches);
    $className = $classMatches[1] ?? '<em>(sem classe / arquivo de view/script)</em>';

    $rowBg = !empty($warnings) ? '#fff3cd' : '#ffffff';

    echo "<tr style='background: {$rowBg};'>";
    echo "<td><code>{$relative}</code></td>";
    echo "<td>{$size}</td>";
    echo "<td><strong>{$className}</strong></td>";
    echo "<td>" . (empty($warnings) ? "✅ OK" : implode('<br>', $warnings)) . "</td>";
    echo "</tr>";
}

echo "</table>";