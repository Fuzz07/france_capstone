<?php
/**
 * Laravel Deployment Diagnostic Tool for Hostinger
 * Put this file in your public directory (or public_html if you moved public contents there).
 * Access it via your browser: yourdomain.com/diagnose.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mera's Store - Deployment Diagnostic Tool</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; max-width: 900px; margin: 0 auto; padding: 20px; background: #f8fafc; color: #334155; }
        h1 { color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        h2 { color: #334155; margin-top: 30px; }
        .card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; border: 1px solid #e2e8f0; }
        .status { display: inline-block; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 14px; }
        .success { background: #dcfce7; color: #15803d; }
        .danger { background: #fee2e2; color: #b91c1c; }
        .warning { background: #fef9c3; color: #a16207; }
        .info { background: #e0f2fe; color: #0369a1; }
        pre { background: #1e293b; color: #f8fafc; padding: 15px; border-radius: 6px; overflow-x: auto; font-family: Consolas, Monaco, "Andale Mono", monospace; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #e2e8f0; }
        th { background: #f1f5f9; }
        .suggestion { background: #f0fdf4; border-left: 4px solid #16a34a; padding: 15px; border-radius: 0 4px 4px 0; margin-top: 20px; }
        .suggestion-danger { background: #fef2f2; border-left: 4px solid #dc2626; }
    </style>
</head>
<body>

<h1>Laravel Deployment Diagnostics</h1>
<p>This script helps diagnose "500 Internal Server Error" issues when deploying to Hostinger shared hosting.</p>

<div class="card">
    <h2>1. PHP Environment Check</h2>
    <table>
        <tr>
            <th>Parameter</th>
            <th>Required</th>
            <th>Your Server</th>
            <th>Status</th>
        </tr>
        <tr>
            <td>PHP Version</td>
            <td>&gt;= 8.1</td>
            <td><?php echo PHP_VERSION; ?></td>
            <td>
                <?php if (version_compare(PHP_VERSION, '8.1.0', '>=')): ?>
                    <span class="status success">OK</span>
                <?php else: ?>
                    <span class="status danger">Upgrade Needed</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>PDO Extension</td>
            <td>Enabled</td>
            <td><?php echo extension_loaded('pdo') ? 'Enabled' : 'Disabled'; ?></td>
            <td>
                <?php if (extension_loaded('pdo')): ?>
                    <span class="status success">OK</span>
                <?php else: ?>
                    <span class="status danger">Missing</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>PDO MySQL Extension</td>
            <td>Enabled</td>
            <td><?php echo extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled'; ?></td>
            <td>
                <?php if (extension_loaded('pdo_mysql')): ?>
                    <span class="status success">OK</span>
                <?php else: ?>
                    <span class="status danger">Missing</span>
                <?php endif; ?>
            </td>
        </tr>
    </table>
</div>

<div class="card">
    <h2>2. Relative Directory Structure & File Presence</h2>
    <?php
    $current_dir = __DIR__;
    $possible_roots = [
        __DIR__ . '/..', // Standard structure, we are inside 'public'
        __DIR__,        // We are in the root directory
    ];
    
    $root_dir = null;
    $is_split_deployment = false;
    
    foreach ($possible_roots as $p) {
        if (file_exists($p . '/bootstrap/app.php') && file_exists($p . '/artisan')) {
            $root_dir = realpath($p);
            break;
        }
    }
    
    if (!$root_dir) {
        $index_content = @file_get_contents(__DIR__ . '/index.php');
        if ($index_content) {
            if (preg_match('/require\s+__DIR__\s*\.\s*\'([^\']+)\/bootstrap\/app\.php\'/', $index_content, $matches) ||
                preg_match('/require_once\s+__DIR__\s*\.\s*\'([^\']+)\/bootstrap\/app\.php\'/', $index_content, $matches)) {
                $rel_path = $matches[1];
                $root_dir = realpath(__DIR__ . $rel_path);
                $is_split_deployment = true;
            }
        }
    }
    
    if (!$root_dir) {
        $root_dir = realpath(__DIR__ . '/..'); // default to parent
    }
    
    echo "<ul>";
    echo "<li><strong>Current Directory:</strong> <code>" . htmlspecialchars($current_dir) . "</code></li>";
    echo "<li><strong>Detected Laravel Root:</strong> <code>" . htmlspecialchars($root_dir ? $root_dir : 'Not found') . "</code></li>";
    echo "<li><strong>Split Deployment Detected:</strong> " . ($is_split_deployment ? '<span class="status info">Yes</span>' : 'No') . "</li>";
    echo "</ul>";
    ?>

    <table>
        <tr>
            <th>File/Folder</th>
            <th>Expected Path</th>
            <th>Exists?</th>
            <th>Permissions</th>
            <th>Status</th>
        </tr>
        <?php
        $checks = [
            '.env' => $root_dir . '/.env',
            'vendor/autoload.php' => $root_dir . '/vendor/autoload.php',
            'bootstrap/app.php' => $root_dir . '/bootstrap/app.php',
            'bootstrap/cache' => $root_dir . '/bootstrap/cache',
            'storage' => $root_dir . '/storage',
            'storage/logs' => $root_dir . '/storage/logs',
            'storage/framework/views' => $root_dir . '/storage/framework/views',
            'storage/framework/sessions' => $root_dir . '/storage/framework/sessions',
        ];

        foreach ($checks as $name => $path) {
            $exists = file_exists($path);
            $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
            $is_writable = $exists ? is_writable($path) : false;
            
            $status = '<span class="status danger">Missing</span>';
            if ($exists) {
                if ($name === '.env' || strpos($name, 'storage') !== false || $name === 'bootstrap/cache') {
                    if ($is_writable) {
                        $status = '<span class="status success">OK (Writable)</span>';
                    } else {
                        $status = '<span class="status warning">Exists (Not Writable)</span>';
                    }
                } else {
                    $status = '<span class="status success">OK</span>';
                }
            }
            
            echo "<tr>";
            echo "<td><code>" . htmlspecialchars($name) . "</code></td>";
            echo "<td><code>" . htmlspecialchars($path) . "</code></td>";
            echo "<td>" . ($exists ? 'Yes' : 'No') . "</td>";
            echo "<td><code>" . htmlspecialchars($perms) . "</code></td>";
            echo "<td>" . $status . "</td>";
            echo "</tr>";
        }
        ?>
    </table>
</div>

<div class="card">
    <h2>3. Local Cache Leak Check</h2>
    <p>If you ran <code>config:cache</code> or <code>route:cache</code> locally and uploaded the cache files, Laravel will fail in production because of hardcoded paths.</p>
    <table>
        <tr>
            <th>Cache File</th>
            <th>Path</th>
            <th>Status</th>
        </tr>
        <?php
        $cache_files = [
            'Config Cache' => $root_dir . '/bootstrap/cache/config.php',
            'Route Cache' => $root_dir . '/bootstrap/cache/routes-v7.php',
            'Events Cache' => $root_dir . '/bootstrap/cache/events.php',
        ];
        foreach ($cache_files as $name => $path) {
            $exists = file_exists($path);
            echo "<tr>";
            echo "<td>" . htmlspecialchars($name) . "</td>";
            echo "<td><code>" . htmlspecialchars($path) . "</code></td>";
            if ($exists) {
                echo '<td><span class="status danger">CRITICAL WARNING: Exists! Please Delete this file on Hostinger!</span></td>';
            } else {
                echo '<td><span class="status success">OK (Clean)</span></td>';
            }
            echo "</tr>";
        }
        ?>
    </table>
</div>

<div class="card">
    <h2>4. Environment & Database Connection Check</h2>
    <?php
    $env_path = $root_dir . '/.env';
    $env_loaded = false;
    $db_config = [
        'DB_CONNECTION' => '',
        'DB_HOST' => '',
        'DB_PORT' => '',
        'DB_DATABASE' => '',
        'DB_USERNAME' => '',
        'DB_PASSWORD' => '',
        'APP_DEBUG' => '',
    ];

    if (file_exists($env_path)) {
        $env_loaded = true;
        $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim($parts[1]);
                $val = trim($val, '"\' ');
                if (strpos($val, '#') !== false) {
                    $subparts = explode('#', $val, 2);
                    $val = trim($subparts[0]);
                }
                if (array_key_exists($key, $db_config)) {
                    $db_config[$key] = $val;
                }
            }
        }
    }
    
    if (!$env_loaded): ?>
        <p class="status danger">Could not read .env file at <?php echo htmlspecialchars($env_path); ?>!</p>
    <?php else: ?>
        <p class="status success">Successfully read .env file!</p>
        <table>
            <tr>
                <th>Variable</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>DB_CONNECTION</td>
                <td><code><?php echo htmlspecialchars($db_config['DB_CONNECTION']); ?></code></td>
            </tr>
            <tr>
                <td>DB_HOST</td>
                <td><code><?php echo htmlspecialchars($db_config['DB_HOST']); ?></code></td>
            </tr>
            <tr>
                <td>DB_PORT</td>
                <td><code><?php echo htmlspecialchars($db_config['DB_PORT']); ?></code></td>
            </tr>
            <tr>
                <td>DB_DATABASE</td>
                <td><code><?php echo htmlspecialchars($db_config['DB_DATABASE']); ?></code></td>
            </tr>
            <tr>
                <td>DB_USERNAME</td>
                <td><code><?php echo htmlspecialchars($db_config['DB_USERNAME']); ?></code></td>
            </tr>
            <tr>
                <td>DB_PASSWORD</td>
                <td><code><?php echo $db_config['DB_PASSWORD'] ? '******** (Length: ' . strlen($db_config['DB_PASSWORD']) . ')' : '(Empty)'; ?></code></td>
            </tr>
            <tr>
                <td>APP_DEBUG</td>
                <td><code><?php echo htmlspecialchars($db_config['APP_DEBUG']); ?></code></td>
            </tr>
        </table>

        <h3>Testing PDO MySQL Connection...</h3>
        <?php
        if ($db_config['DB_CONNECTION'] !== 'mysql') {
            echo '<p class="status warning">Skipping test: DB_CONNECTION is not set to mysql.</p>';
        } else {
            try {
                $dsn = "mysql:host=" . $db_config['DB_HOST'] . ";port=" . $db_config['DB_PORT'] . ";dbname=" . $db_config['DB_DATABASE'] . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5, // 5 seconds timeout
                ];
                $pdo = new PDO($dsn, $db_config['DB_USERNAME'], $db_config['DB_PASSWORD'], $options);
                echo '<p class="status success">SUCCESS! Connected to the Hostinger database successfully!</p>';
                
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo '<p>Found <strong>' . count($tables) . '</strong> tables in database.</p>';
                if (count($tables) === 0) {
                    echo '<p class="status danger">Your database has 0 tables! You need to run migrations!</p>';
                } else {
                    echo '<ul>';
                    foreach (array_slice($tables, 0, 5) as $t) {
                        echo '<li><code>' . htmlspecialchars($t) . '</code></li>';
                    }
                    if (count($tables) > 5) {
                        echo '<li>... and ' . (count($tables) - 5) . ' more.</li>';
                    }
                    echo '</ul>';
                }
            } catch (PDOException $e) {
                echo '<p class="status danger">CONNECTION FAILED!</p>';
                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                echo '<div class="suggestion suggestion-danger">';
                echo '<h4>Troubleshooting Hostinger Database Connection:</h4>';
                echo '<ul>';
                echo '<li>Verify that <strong>DB_HOST</strong> is correct. On Hostinger, the database host is typically <code>localhost</code> or a specific IP shown in hPanel, NOT always <code>mysql.hostinger.com</code>. Try <code>localhost</code> first!</li>';
                echo '<li>Ensure your database user <code>' . htmlspecialchars($db_config['DB_USERNAME']) . '</code> has full privileges to access database <code>' . htmlspecialchars($db_config['DB_DATABASE']) . '</code>.</li>';
                echo '<li>Double check the password. Hostinger database passwords are separate from hPanel passwords.</li>';
                echo '</ul>';
                echo '</div>';
            }
        }
        endif;
    ?>
</div>

<div class="card">
    <h2>5. Common Hostinger Deployment Solutions</h2>
    <div class="suggestion">
        <h4>If you see a blank screen or 500 Error:</h4>
        <ol>
            <li><strong>Fix Storage Permissions:</strong> Make sure <code>storage/</code> and <code>bootstrap/cache/</code> folders are writable (chmod 755 or 775).</li>
            <li><strong>Clear Config Cache:</strong> If you see paths starting with <code>C:\laragon\...</code> in your server errors, you uploaded your local config cache. Delete <code>bootstrap/cache/config.php</code> on Hostinger immediately!</li>
            <li><strong>Check PHP Version:</strong> Go to Hostinger Panel &rarr; Websites &rarr; Manage &rarr; PHP Configuration and make sure it is set to PHP 8.1 or PHP 8.2.</li>
            <li><strong>Change DB Host:</strong> If database connection fails, change <code>DB_HOST</code> from <code>mysql.hostinger.com</code> to <code>localhost</code> or <code>127.0.0.1</code> in your server's <code>.env</code>.</li>
            <li><strong>Run Migrations:</strong> If you have SSH access, run <code>php artisan migrate --force</code>. If not, you can create a temporary route in <code>routes/web.php</code>:
                <pre>Route::get('/run-migrations', function() {
    Artisan::call('migrate', ['--force' => true]);
    return "Migrations run successfully!";
});</pre>
                Then visit <code>yourdomain.com/run-migrations</code> in your browser, and delete the route after!
            </li>
        </ol>
    </div>
</div>

</body>
</html>
