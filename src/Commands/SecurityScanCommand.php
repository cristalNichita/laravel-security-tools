<?php

namespace Fragly\SecurityTools\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SecurityScanCommand extends Command
{
    protected $signature = 'security:scan
                            {--format=table : Output format: table|md}
                            {--output= : Custom output path for Markdown (overrides config)}
                            {--strict : Fail on warnings (for CI/CD)}';

    protected $description = 'Scan .env and config for common security issues.';

    public function handle(): int
    {
        $issues = [];
        $this->info('🔒 Laravel Security Tools — scanning...');
        $this->line('');

        $issues = array_merge($issues, $this->scanEnv());
        $issues = array_merge($issues, $this->scanConfig());

        $this->renderResult($issues);

        if ($this->option('format') === 'md') {
            $this->writeMarkdown($issues);
        }

        $hasProblems = collect($issues)->contains(fn($i) =>
            $i['level'] === 'error' ||
            ($this->option('strict') && $i['level'] === 'warning')
        );

        return $hasProblems ? Command::FAILURE : Command::SUCCESS;
    }

    // ---------------------------------------------------------------------
    // ENVIRONMENT (.env) SCAN
    // ---------------------------------------------------------------------
    protected function scanEnv(): array
    {
        $results = [];
        $path = base_path('.env');

        if (!file_exists($path)) {
            return [[
                'area' => 'env',
                'key' => '.env',
                'message' => 'Missing .env file',
                'level' => 'error',
                'hint' => 'Create .env from .env.example and configure required keys.',
            ]];
        }

        $env = $this->parseDotEnv(file_get_contents($path));
        $isProd = config('app.env') === 'production';

        // Required keys
        foreach (config('security-tools.env.required_non_empty', []) as $key) {
            if (!array_key_exists($key, $env) || trim((string)($env[$key] ?? '')) === '') {
                $results[] = $this->issue('env', $key, 'Missing or empty value', 'error', "Set {$key} in .env.");
            }
        }

        // Dangerous values
        foreach (config('security-tools.env.dangerous_values', []) as $key => $badValues) {
            $val = strtolower((string)Arr::get($env, $key, ''));
            if ($val !== '' && in_array($val, array_map('strtolower', $badValues), true)) {
                $results[] = $this->issue('env', $key, "Dangerous value: {$val}", 'warning', "Set {$key}=false in production.");
            }
        }

        // Forbidden in production
        if ($isProd) {
            foreach (config('security-tools.env.production_forbidden', []) as $key => $forbiddenValues) {
                $val = strtolower((string)Arr::get($env, $key, ''));
                if (in_array($val, array_map('strtolower', $forbiddenValues), true)) {
                    $results[] = $this->issue('env', $key, "Forbidden value in production: {$val}", 'error', "Change {$key} for production.");
                }
            }
        }

        // Regex checks
        foreach (config('security-tools.env.regex', []) as $key => $pattern) {
            if (isset($env[$key]) && !preg_match($pattern, $env[$key])) {
                $results[] = $this->issue('env', $key, 'Invalid format', 'warning', "Value does not match pattern {$pattern}.");
            }
        }

        // HTTPS checks
        if ($isProd) {
            foreach (config('security-tools.env.url_must_be_https', []) as $key) {
                if (isset($env[$key]) && Str::startsWith($env[$key], 'http://')) {
                    $results[] = $this->issue('env', $key, 'Non-HTTPS URL in production', 'warning', "Use https:// for {$key}.");
                }
            }
        }

        // Substring disallow
        if ($isProd) {
            foreach (config('security-tools.env.production_disallow_substrings', []) as $key => $substrings) {
                $val = strtolower((string)($env[$key] ?? ''));
                foreach ($substrings as $bad) {
                    if (Str::contains($val, strtolower($bad))) {
                        $results[] = $this->issue('env', $key, "Contains '{$bad}' (not allowed in production)", 'warning', "Set correct domain for {$key}.");
                    }
                }
            }
        }

        // APP_KEY validation
        if (!empty($env['APP_KEY'])) {
            $key = (string)$env['APP_KEY'];
            $ok = Str::startsWith($key, 'base64:') && strlen(base64_decode(substr($key, 7), true) ?: '') === 32;
            if (!$ok) {
                $results[] = $this->issue('env', 'APP_KEY', 'Invalid APP_KEY format', 'warning', 'Run: php artisan key:generate');
            }
        }

        return $results;
    }

    // ---------------------------------------------------------------------
    // CONFIG() RUNTIME CHECKS
    // ---------------------------------------------------------------------
    protected function scanConfig(): array
    {
        $results = [];
        $isProd = config('app.env') === 'production';

        // app.debug
        if ($isProd && config('app.debug')) {
            $results[] = $this->issue('config', 'app.debug', 'APP_DEBUG=true in production', 'error', 'Set APP_DEBUG=false in production.');
        }

        // HTTPS cookies
        if ($isProd && !config('session.secure')) {
            $results[] = $this->issue('config', 'session.secure', 'Secure cookies disabled', 'warning', 'Set session.secure=true to enforce HTTPS cookies.');
        }

        if (!config('session.http_only')) {
            $results[] = $this->issue('config', 'session.http_only', 'HttpOnly disabled', 'warning', 'Set session.http_only=true.');
        }

        // SameSite
        if ($isProd && config('session.same_site') === 'none' && !config('session.secure')) {
            $results[] = $this->issue('config', 'session.same_site', 'SameSite=None without HTTPS', 'warning', 'Use SameSite=None only with HTTPS.');
        }

        // CORS
        $origins = (array)(config('cors.allowed_origins', []));
        if ($isProd && in_array('*', $origins, true)) {
            $results[] = $this->issue('config', 'cors.allowed_origins', 'CORS allows all origins (*)', 'warning', 'Avoid "*" in production.');
        }

        // Trusted proxy
        if ($isProd && config('trustedproxy.proxies') === '*') {
            $results[] = $this->issue('config', 'trustedproxy.proxies', 'TrustedProxy wildcard (*)', 'warning', 'Specify proxy IPs instead of "*".');
        }

        // Cache / Queue / Mail
        if ($isProd && config('cache.default') === 'array') {
            $results[] = $this->issue('config', 'cache.default', 'CACHE_DRIVER=array in production', 'warning', 'Use Redis or Memcached instead.');
        }

        if ($isProd && config('queue.default') === 'sync') {
            $results[] = $this->issue('config', 'queue.default', 'QUEUE_CONNECTION=sync in production', 'warning', 'Use database or Redis queues.');
        }

        if ($isProd && config('mail.default') === 'log') {
            $results[] = $this->issue('config', 'mail.default', 'MAIL_MAILER=log in production', 'warning', 'Configure real SMTP mailer.');
        }

        if ($isProd && config('log.level') === 'debug') {
            $results[] = $this->issue('config', 'log.level', 'LOG_LEVEL=debug in production', 'warning', 'Use info/warning/error in production.');
        }

        // HTTPS URL
        if ($isProd && Str::startsWith(config('app.url'), 'http://')) {
            $results[] = $this->issue('config', 'app.url', 'Non-HTTPS APP_URL', 'warning', 'Set app.url to use HTTPS.');
        }

        return $results;
    }

    // ---------------------------------------------------------------------
    // UTILITIES
    // ---------------------------------------------------------------------
    protected function renderResult(array $issues): void
    {
        if (empty($issues)) {
            $this->info('✅ No issues found. Good job!');
            return;
        }

        $rows = array_map(fn($i) => [
            strtoupper($i['level']),
            $i['area'],
            $i['key'],
            $i['message'],
            $i['hint'],
        ], $issues);

        $this->table(['Level', 'Area', 'Key', 'Message', 'Hint'], $rows);
    }

    protected function writeMarkdown(array $issues): void
    {
        $path = $this->option('output') ?: base_path(config('security-tools.report.markdown_path', 'storage/logs/security-report.md'));

        $lines = [
            '# Laravel Security Tools Report',
            '',
            '- Generated at: ' . now()->toDateTimeString(),
            '',
        ];

        if (empty($issues)) {
            $lines[] = '✅ No issues found.';
        } else {
            $lines[] = '| Level | Area | Key | Message | Hint |';
            $lines[] = '|---|---|---|---|---|';
            foreach ($issues as $i) {
                $lines[] = sprintf(
                    '| %s | %s | `%s` | %s | %s |',
                    strtoupper($i['level']),
                    $i['area'],
                    $i['key'],
                    $i['message'],
                    str_replace('|', '\|', $i['hint'])
                );
            }
        }

        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, implode("\n", $lines) . "\n");
        $this->info('📝 Markdown report saved: ' . $path);
    }

    protected function parseDotEnv(string $content): array
    {
        $env = [];
        foreach (preg_split('/\R/', $content) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
            $k = trim($k);
            $v = trim($v);

            if ((Str::startsWith($v, '"') && Str::endsWith($v, '"')) ||
                (Str::startsWith($v, "'") && Str::endsWith($v, "'"))) {
                $v = substr($v, 1, -1);
            }

            $env[$k] = $v;
        }
        return $env;
    }

    protected function issue(string $area, string $key, string $message, string $level, string $hint): array
    {
        return compact('area', 'key', 'message', 'level', 'hint');
    }
}