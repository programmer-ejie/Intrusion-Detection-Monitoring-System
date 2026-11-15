<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class DemoAttackService
{
    public function catalog(): array
    {
        return json_decode(file_get_contents(resource_path('data/demo-attacks.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public function predict(array $payload): array
    {
        // Do not retry POSTs: the remote service may already have saved the flow.
        $response = Http::acceptJson()->connectTimeout(15)->timeout(180)
            ->post(config('services.ids.analyze_url'), $payload);

        if (!$response->successful()) {
            throw new RuntimeException('The prediction service rejected the flow (HTTP '.$response->status().').');
        }

        $result = $response->json();
        if (!is_array($result)
            || !in_array($result['risk_level'] ?? null, ['attack', 'benign'], true)
            || !is_numeric($result['prob_attack'] ?? null)
            || !is_finite((float) $result['prob_attack'])
            || $result['prob_attack'] < 0 || $result['prob_attack'] > 1
            || ($result['risk_level'] === 'attack' && (!is_string($result['attack_type'] ?? null) || trim($result['attack_type']) === ''))
            || ($result['missing_expected_features'] ?? 0) > 0) {
            throw new RuntimeException('The prediction service returned an incomplete or invalid prediction.');
        }

        return $result;
    }
}
