<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestDirectorySearch extends Command
{
    protected $signature = 'directory:test {term : The search term to send}';
    protected $description = 'Diagnose the external employee directory endpoint';

    public function handle(): int
    {
        $url     = config('directory.url');
        $method  = config('directory.method', 'POST');
        $field   = config('directory.query_field', 'search');
        $timeout = config('directory.timeout', 10);
        $token   = config('directory.token');
        $key     = config('directory.results_key');
        $verify  = config('directory.verify', true);
        $term    = $this->argument('term');

        $this->newLine();
        $this->info('── Directory configuration ──────────────────────────────');
        $this->line("URL          : " . ($url ?: '(empty — local DB search only!)'));
        $this->line("Method       : {$method}");
        $this->line("Query field  : {$field}");
        $this->line("Results key  : " . ($key ?: '(none — response is the array)'));
        $this->line("Token        : " . ($token ? '(set)' : '(none)'));
        $this->line("SSL verify   : " . ($verify ? 'true' : 'false (insecure)'));
        $this->line("Timeout      : {$timeout}s");
        $this->newLine();

        if (empty($url)) {
            $this->error('EMPLOYEE_SEARCH_URL is empty. Set it in .env then run: php artisan config:clear');
            return self::FAILURE;
        }

        $this->info("── Calling endpoint with {$field}=\"{$term}\" ──────────────");

        try {
            $request = Http::timeout($timeout)->acceptJson();
            if (!$verify) {
                $request = $request->withoutVerifying();
            }
            if (!empty($token)) {
                $request = $request->withToken($token);
            }

            $payload  = [$field => $term];
            $response = $method === 'GET'
                ? $request->get($url, $payload)
                : $request->post($url, $payload);

            $this->line("HTTP status  : " . $response->status());
            $this->line("Content-Type : " . $response->header('Content-Type'));
            $this->newLine();

            $bodyRaw = $response->body();
            $this->info('── Raw response body (first 2000 chars) ─────────────────');
            $this->line(substr($bodyRaw, 0, 2000));
            $this->newLine();

            $json = $response->json();

            if ($json === null) {
                $this->error('Response is NOT valid JSON. The endpoint must return JSON.');
                return self::FAILURE;
            }

            // Determine the array
            $array = null;
            if (!empty($key) && isset($json[$key])) {
                $array = $json[$key];
                $this->line("Using configured results_key '{$key}'.");
            } else {
                foreach (['data', 'results', 'employees', 'items'] as $c) {
                    if (isset($json[$c]) && is_array($json[$c])) {
                        $array = $json[$c];
                        $this->warn("Auto-detected wrapper key '{$c}'. Consider setting EMPLOYEE_SEARCH_RESULTS_KEY={$c}");
                        break;
                    }
                }
                if ($array === null) {
                    $array = is_array($json) && array_is_list($json) ? $json : null;
                }
            }

            if (!is_array($array)) {
                $this->error('Could not find an array of employees in the response.');
                $this->line('Top-level keys: ' . implode(', ', array_keys((array) $json)));
                $this->line('→ Set EMPLOYEE_SEARCH_RESULTS_KEY to the key that holds the array.');
                return self::FAILURE;
            }

            $this->info('── Found ' . count($array) . ' employee(s) ─────────────────────────────');

            if (count($array) === 0) {
                $this->warn('The endpoint returned an EMPTY array for this term.');
                return self::SUCCESS;
            }

            // Show the keys of the first entry so we can verify field mapping
            $first = $array[0];
            $this->newLine();
            $this->info('Keys present on the first employee object:');
            $this->line('  ' . implode(', ', array_keys((array) $first)));
            $this->newLine();

            // Run the real mapper
            $service = app(\App\Services\EmployeeDirectoryService::class);
            $mapped  = (new \ReflectionMethod($service, 'mapEntry'))
                ->invoke($service, (array) $first);

            $this->info('Mapped result for the first employee:');
            foreach ($mapped as $k => $v) {
                $flag = ($k === 'username' && empty($v)) ? '  ❌ MISSING (this row will be skipped!)' : '';
                $this->line("  {$k} = " . ($v ?? 'null') . $flag);
            }

            $this->newLine();
            if (empty($mapped['username'])) {
                $this->error('username could not be mapped → all rows are filtered out → no results.');
                $this->line('Fix: add your username field to config/directory.php → fields.username[]');
            } else {
                $this->info('Mapping looks good. Search should work in the app now.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Request failed: ' . $e->getMessage());
            $this->line('Check the URL is reachable from this server and the method (GET/POST) is correct.');
            return self::FAILURE;
        }
    }
}
