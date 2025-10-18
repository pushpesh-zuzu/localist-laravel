<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Helpers\CustomHelper;


class OctoparseService
{
    protected string $base = 'https://dataapi.octoparse.com';

    public function __construct()
    {
        
    }

    protected function getToken(): string
    {
        // Cache token until it expires
        return Cache::remember('octo_access_token', 60 * 60, function () {
            $resp = Http::asForm()->post("{$this->base}/token", [
                'username' => CustomHelper::setting_value('octoparse_username', 'username@domain.com'),
                'password' => CustomHelper::setting_value('octoparse_password', 'your_default_password'),
                'grant_type' => 'password',
            ]);

            if (! $resp->ok()) {
                throw new \Exception('Octoparse auth failed: ' . $resp->body());
            }

            $json = $resp->json();
            // Save refresh token too
            if (!empty($json['refresh_token'])) {
                Cache::put('octo_refresh_token', $json['refresh_token'], 24 * 60);
            }
            // expiry in seconds
            $expiresIn = $json['expires_in'] ?? 3600;
            // override cache TTL with actual expiry minus a buffer
            Cache::put('octo_access_token', $json['access_token'], max(30, $expiresIn - 30));

            return $json['access_token'];
        });
    }

    protected function withAuth()
    {
        $token = $this->getToken();
        return Http::withToken($token);
    }

    /**
     * Get tasks in a group (optional helper)
     */
    public function listTasks(int $taskGroupId)
    {
        $r = $this->withAuth()->get("{$this->base}/api/Task", [
            'taskGroupId' => $taskGroupId,
        ]);
        return $r->json();
    }

    /**
     * Pull up to $size rows of non-exported data for a task
     */
    public function getTaskData(string $taskId, int $size = 1000)
    {
        if ($size < 1) $size = 1;
        if ($size > 1000) $size = 1000;

        $r = $this->withAuth()->get("{$this->base}/api/notexportdata/gettop", [
            'taskId' => $taskId,
            'size' => $size,
        ]);

        if ($r->status() === 429) {
            // Rate-limited
            throw new \Exception('Octoparse rate limit hit (429). Backoff and retry.');
        }

        if (! $r->ok()) {
            throw new \Exception('Octoparse get data error: ' . $r->body());
        }

        return $r->json(); // returns structure: { data: { total,currentTotal,dataList: [...] }, error: "success" }
    }
}
