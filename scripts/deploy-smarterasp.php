<?php

/**
 * Redeploy the committed HEAD to SmarterASP.NET (Windows/IIS) over FTPS.
 *
 *   php scripts/deploy-smarterasp.php [--dry-run] [--no-migrate] [--keep-build]
 *
 * Steps:
 *   1. git archive HEAD into a temp build dir (uncommitted changes are NOT shipped)
 *      and copy DEPLOY_ENV_FILE in as the server .env
 *   2. composer install --no-dev, npm ci + npm run build, prune dev-only files
 *   3. php artisan migrate --force against the production database (before any
 *      file changes, so a failing migration leaves the live site untouched)
 *   4. upload web.config first (keeps the archive unreachable), then the build
 *      as one zip plus a one-time extractor: per-file FTPS to IIS is far too slow
 *   5. call the extractor over HTTPS; it unpacks, clears compiled views, and
 *      deletes itself and the zip
 *   6. smoke-test DEPLOY_URL/login
 *
 * --dry-run stops after the zip is built (no migrations, nothing uploaded).
 *
 * Configuration: copy .env.deploy.example to .env.deploy (gitignored).
 * Environment variables with the same names override the file.
 * Requires PHP 8.3+ with the curl and zip extensions, git, Composer and npm.
 */

const REQUIRED = ['DEPLOY_FTP_HOST', 'DEPLOY_FTP_USER', 'DEPLOY_FTP_PASSWORD', 'DEPLOY_FTP_PATH', 'DEPLOY_URL', 'DEPLOY_ENV_FILE'];

// Dev-only paths removed from the build before zipping.
const PRUNE = [
    'tests', 'node_modules', 'scripts', '.editorconfig', '.gitattributes', '.gitignore', '.npmrc',
    '.env.example', '.env.deploy.example', 'package.json', 'package-lock.json', 'vite.config.js',
    'phpunit.xml', 'README.md', 'AGENTS.md', '_extend_tree.php', '_tree_state.php',
];

// Runtime folders whose contents belong to the server and must never be overwritten.
const RUNTIME_DIRS = [
    'storage/logs', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views',
    'storage/app/private', 'storage/app/public',
];

$root = dirname(__DIR__);
$flags = array_slice($argv, 1);
$dryRun = in_array('--dry-run', $flags, true);
$migrate = ! in_array('--no-migrate', $flags, true);
$keepBuild = in_array('--keep-build', $flags, true) || $dryRun;

try {
    foreach (['curl', 'zip'] as $extension) {
        if (! extension_loaded($extension)) {
            throw new RuntimeException("PHP extension '{$extension}' is required.");
        }
    }

    $config = loadConfig($root.'/.env.deploy');
    $envFile = resolvePath($root, $config['DEPLOY_ENV_FILE']);
    if (! is_file($envFile)) {
        throw new RuntimeException("Production env file not found: {$envFile}");
    }
    $siteUrl = rtrim($config['DEPLOY_URL'], '/');
    $ftpPath = '/'.trim($config['DEPLOY_FTP_PATH'], '/');

    $commit = trim(capture('git rev-parse --short HEAD', $root));
    $branch = trim(capture('git rev-parse --abbrev-ref HEAD', $root));
    step("Deploying {$branch}@{$commit} to {$siteUrl}");
    if (trim(capture('git status --porcelain', $root)) !== '') {
        warn('Working tree has uncommitted changes; only the committed HEAD is deployed.');
    }

    $work = sys_get_temp_dir().DIRECTORY_SEPARATOR.'holyhealth-deploy-'.date('Ymd-His');
    $build = $work.DIRECTORY_SEPARATOR.'build';
    $archive = $work.DIRECTORY_SEPARATOR.'deploy.zip';
    mkdir($build, 0700, true);

    step('Exporting HEAD');
    $source = $work.DIRECTORY_SEPARATOR.'source.zip';
    run('git archive --format=zip -o '.escapeshellarg($source).' HEAD', $root);
    extractZip($source, $build);
    unlink($source);
    copy($envFile, $build.'/.env');

    step('Installing PHP dependencies');
    run('composer install --no-dev --optimize-autoloader --no-interaction --no-progress', $build);

    step('Building front-end assets');
    run('npm ci --no-audit --no-fund', $build);
    run('npm run build', $build);

    foreach (PRUNE as $path) {
        removePath($build.'/'.$path);
    }
    foreach (glob($build.'/database/*.sql') as $dump) {
        unlink($dump);
    }

    if ($migrate && ! $dryRun) {
        step('Running migrations against the production database');
        run(escapeshellarg(PHP_BINARY).' artisan migrate --force', $build);
    }

    foreach (RUNTIME_DIRS as $dir) {
        clearRuntimeDir($build.'/'.$dir);
    }

    step('Creating archive');
    $count = zipDirectory($build, $archive);
    chmod($archive, 0600);
    echo sprintf("  %d files, %.1f MB\n", $count, filesize($archive) / 1048576);

    if ($dryRun) {
        step("Dry run: nothing uploaded. Build kept at {$work}");
        exit(0);
    }

    $ftp = new Ftps($config['DEPLOY_FTP_HOST'], $config['DEPLOY_FTP_USER'], $config['DEPLOY_FTP_PASSWORD']);

    step('Uploading web.config');
    $ftp->upload($build.'/web.config', $ftpPath.'/web.config');

    step('Uploading archive');
    $ftp->upload($archive, $ftpPath.'/_deploy.zip');

    $token = bin2hex(random_bytes(24));
    $extractorName = '_deploy_'.bin2hex(random_bytes(8)).'.php';
    $extractorFile = $work.DIRECTORY_SEPARATOR.$extractorName;
    file_put_contents($extractorFile, extractorSource($token));
    $ftp->upload($extractorFile, $ftpPath.'/public/'.$extractorName);

    step('Extracting on the server');
    [$status, $body] = httpGet($siteUrl.'/'.$extractorName.'?t='.$token, 900);
    echo preg_replace('/^/m', '  ', trim($body))."\n";
    if ($status !== 200 || ! str_contains($body, 'EXTRACT_OK')) {
        $ftp->deleteQuietly($ftpPath.'/public/'.$extractorName);
        $ftp->deleteQuietly($ftpPath.'/_deploy.zip');
        throw new RuntimeException("Extraction failed (HTTP {$status}); the extractor and archive were removed.");
    }

    step('Smoke test');
    [$status] = httpGet($siteUrl.'/login', 60);
    if ($status !== 200) {
        throw new RuntimeException("GET {$siteUrl}/login returned HTTP {$status}. Check storage/logs on the server.");
    }
    echo "  GET /login -> 200\n";

    if (! $keepBuild) {
        removePath($work);
    }

    step("Done: {$branch}@{$commit} is live at {$siteUrl}");
} catch (Throwable $e) {
    fwrite(STDERR, "\n[deploy] FAILED: {$e->getMessage()}\n");
    if (isset($work) && is_dir($work)) {
        fwrite(STDERR, "[deploy] Build left at {$work} (contains the production .env; delete it when done).\n");
    }
    exit(1);
}

final class Ftps
{
    public function __construct(
        private string $host,
        private string $user,
        private string $password,
    ) {}

    public function upload(string $local, string $remote, int $attempts = 3): void
    {
        $size = filesize($local);

        for ($try = 1; $try <= $attempts; $try++) {
            $handle = fopen($local, 'rb');
            try {
                $this->request($remote, [
                    CURLOPT_UPLOAD => true,
                    CURLOPT_INFILE => $handle,
                    CURLOPT_INFILESIZE => $size,
                ]);

                // IIS FTPS can silently drop the tail of a transfer; verify before trusting it.
                $info = $this->request($remote, [CURLOPT_NOBODY => true]);
                if ((int) $info['download_content_length'] === $size) {
                    return;
                }
                warn("Size mismatch uploading {$remote} (attempt {$try}/{$attempts}), retrying.");
            } catch (RuntimeException $e) {
                // Dropped TLS sessions are transient on this host; a fresh connection usually succeeds.
                warn("{$e->getMessage()} (attempt {$try}/{$attempts}), retrying.");
            } finally {
                fclose($handle);
            }
            if ($try < $attempts) {
                sleep(5 * $try);
            }
        }

        throw new RuntimeException("Upload of {$remote} did not arrive intact.");
    }

    public function deleteQuietly(string $remote): void
    {
        try {
            $this->request('/', [CURLOPT_NOBODY => true, CURLOPT_QUOTE => ['DELE '.$remote]]);
        } catch (Throwable) {
            // Best effort cleanup.
        }
    }

    private function request(string $remote, array $options): array
    {
        $segments = array_map('rawurlencode', explode('/', ltrim($remote, '/')));
        $curl = curl_init('ftp://'.$this->host.'/'.implode('/', $segments));
        curl_setopt_array($curl, $options + [
            CURLOPT_USERPWD => $this->user.':'.$this->password,
            CURLOPT_USE_SSL => CURLUSESSL_ALL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 900,
        ]);
        curl_exec($curl);
        $error = curl_error($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        if ($error !== '') {
            throw new RuntimeException("FTPS {$remote}: {$error}");
        }

        return $info;
    }
}

function extractorSource(string $token): string
{
    return <<<PHP
<?php
// One-time deploy extractor generated by scripts/deploy-smarterasp.php. Deletes itself.
if (! hash_equals('{$token}', (string) (\$_GET['t'] ?? ''))) { http_response_code(404); exit; }
@set_time_limit(900);
header('Content-Type: text/plain');
\$root = dirname(__DIR__);
\$zipFile = \$root.DIRECTORY_SEPARATOR.'_deploy.zip';
\$zip = new ZipArchive();
\$opened = \$zip->open(\$zipFile);
if (\$opened !== true) { echo "OPEN_FAIL {\$opened}\\n"; @unlink(__FILE__); exit; }
\$count = \$zip->numFiles;
\$ok = \$zip->extractTo(\$root);
\$zip->close();
echo \$ok ? "EXTRACT_OK {\$count} files\\n" : "EXTRACT_FAIL\\n";
foreach (glob(\$root.'/storage/framework/views/*.php') ?: [] as \$view) { @unlink(\$view); }
foreach (['config.php', 'routes-v7.php', 'events.php'] as \$cached) { @unlink(\$root.'/bootstrap/cache/'.\$cached); }
@unlink(\$zipFile);
foreach (['storage/logs', 'storage/framework/sessions', 'storage/framework/views', 'bootstrap/cache', 'storage/app/public'] as \$dir) {
    if (! is_writable(\$root.'/'.\$dir)) { echo "WARNING not writable: {\$dir}\\n"; }
}
@unlink(__FILE__);
echo 'cleanup='.(file_exists(\$zipFile) || file_exists(__FILE__) ? 'incomplete' : 'ok')."\\n";
PHP;
}

function loadConfig(string $file): array
{
    $config = [];
    if (is_file($file)) {
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
                $value = substr($value, 1, -1);
            }
            $config[$key] = $value;
        }
    }

    foreach (REQUIRED as $key) {
        $fromEnv = getenv($key);
        if ($fromEnv !== false && $fromEnv !== '') {
            $config[$key] = $fromEnv;
        }
    }

    $missing = array_filter(REQUIRED, fn ($key) => ($config[$key] ?? '') === '');
    if ($missing !== []) {
        throw new RuntimeException('Missing settings: '.implode(', ', $missing).'. Copy .env.deploy.example to .env.deploy and fill it in.');
    }

    return $config;
}

function resolvePath(string $root, string $path): string
{
    return preg_match('#^([A-Za-z]:)?[\\\\/]#', $path) ? $path : $root.DIRECTORY_SEPARATOR.$path;
}

function run(string $command, string $cwd): void
{
    echo "  $ {$command}\n";
    $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $cwd);
    if (! is_resource($process) || proc_close($process) !== 0) {
        throw new RuntimeException("Command failed: {$command}");
    }
}

function capture(string $command, string $cwd): string
{
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException("Command failed: {$command}");
    }

    return $output;
}

function httpGet(string $url, int $timeout): array
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_FOLLOWLOCATION => false]);
    $body = curl_exec($curl);
    $error = curl_error($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if ($error !== '') {
        throw new RuntimeException("GET {$url}: {$error}");
    }

    return [$status, (string) $body];
}

function extractZip(string $file, string $destination): void
{
    $zip = new ZipArchive();
    if ($zip->open($file) !== true || ! $zip->extractTo($destination)) {
        throw new RuntimeException("Could not extract {$file}");
    }
    $zip->close();
}

function zipDirectory(string $source, string $destination): int
{
    $zip = new ZipArchive();
    if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException("Could not create {$destination}");
    }

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS));
    $prefix = strlen(rtrim($source, '/\\')) + 1;
    $count = 0;
    foreach ($files as $file) {
        if ($file->isFile()) {
            $zip->addFile($file->getPathname(), str_replace('\\', '/', substr($file->getPathname(), $prefix)));
            $count++;
        }
    }
    $zip->close();

    return $count;
}

function clearRuntimeDir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }
    foreach (new DirectoryIterator($dir) as $entry) {
        if ($entry->isDot() || in_array($entry->getFilename(), ['.gitignore', 'web.config'], true)) {
            continue;
        }
        removePath($entry->getPathname());
    }
}

function removePath(string $path): void
{
    if (is_link($path) || is_file($path)) {
        unlink($path);

        return;
    }
    if (! is_dir($path)) {
        return;
    }
    foreach (new DirectoryIterator($path) as $entry) {
        if (! $entry->isDot()) {
            removePath($entry->getPathname());
        }
    }
    rmdir($path);
}

function step(string $message): void
{
    echo "\n[deploy] {$message}\n";
}

function warn(string $message): void
{
    fwrite(STDERR, "[deploy] WARNING: {$message}\n");
}
