<?php

declare(strict_types=1);

if (!defined('CRM_GENSEN_RATE')) {
    define('CRM_GENSEN_RATE', 0.1021);
}

if (!defined('CRM_MEDIUM_SPLIT_DEFAULTS')) {
    define('CRM_MEDIUM_SPLIT_DEFAULTS', [
        'pct_espaco' => 20.0,
        'pct_treinamento' => 10.0,
        'pct_material' => 20.0,
        'pct_tata' => 10.0,
        'pct_executor' => 40.0,
    ]);
}

if (!function_exists('normalizarConfigMedium')) {
    function normalizarConfigMedium(array $configMedium = []): array
    {
        $defaults = CRM_MEDIUM_SPLIT_DEFAULTS;
        $config = [];
        foreach ($defaults as $key => $defaultValue) {
            $config[$key] = isset($configMedium[$key]) ? (float)$configMedium[$key] : $defaultValue;
        }

        $percentualTotal = array_sum($config);
        if ($percentualTotal <= 0) {
            $config = $defaults;
            $percentualTotal = array_sum($config);
        }

        $normalizado = [];
        foreach ($config as $key => $value) {
            $normalizado[$key] = round(($value / $percentualTotal) * 100, 4);
        }

        return [
            'informado' => $config,
            'normalizado' => $normalizado,
            'percentual_total' => round(array_sum($normalizado), 4),
        ];
    }
}

if (!function_exists('distribuirPercentuaisInteiros')) {
    function distribuirPercentuaisInteiros(int $valorTotal, array $percentuais): array
    {
        $chaves = array_keys($percentuais);
        $partes = [];
        $acumulado = 0;
        $ultimaChave = end($chaves);

        foreach ($percentuais as $chave => $percentual) {
            if ($chave === $ultimaChave) {
                $partes[$chave] = max(0, $valorTotal - $acumulado);
                continue;
            }

            $valor = (int) round($valorTotal * ((float)$percentual / 100), 0, PHP_ROUND_HALF_UP);
            $partes[$chave] = $valor;
            $acumulado += $valor;
        }

        return $partes;
    }
}

if (!function_exists('calcularSplitTrabalho')) {
    function calcularSplitTrabalho($valorTotal, array $configMedium = []): array
    {
        $valorTotal = max(0, (int) round((float) $valorTotal, 0, PHP_ROUND_HALF_UP));
        $config = normalizarConfigMedium($configMedium);
        $percentuais = $config['normalizado'];
        $brutos = distribuirPercentuaisInteiros($valorTotal, $percentuais);

        $impostoTata = (int) round(($brutos['pct_tata'] ?? 0) * CRM_GENSEN_RATE, 0, PHP_ROUND_HALF_UP);
        $impostoExecutor = (int) round(($brutos['pct_executor'] ?? 0) * CRM_GENSEN_RATE, 0, PHP_ROUND_HALF_UP);

        $liquidoTata = max(0, (int)($brutos['pct_tata'] ?? 0) - $impostoTata);
        $liquidoExecutor = max(0, (int)($brutos['pct_executor'] ?? 0) - $impostoExecutor);
        $custosInternos = (int)($brutos['pct_espaco'] ?? 0) + (int)($brutos['pct_treinamento'] ?? 0) + (int)($brutos['pct_material'] ?? 0);
        $impostoTotal = $impostoTata + $impostoExecutor;

        return [
            'valor_total' => $valorTotal,
            'percentuais' => $config,
            'brutos' => [
                'espaco' => (int)($brutos['pct_espaco'] ?? 0),
                'treinamento' => (int)($brutos['pct_treinamento'] ?? 0),
                'material' => (int)($brutos['pct_material'] ?? 0),
                'tata' => (int)($brutos['pct_tata'] ?? 0),
                'executor' => (int)($brutos['pct_executor'] ?? 0),
            ],
            'impostos' => [
                'aliquota_gensen' => CRM_GENSEN_RATE,
                'tata' => $impostoTata,
                'executor' => $impostoExecutor,
                'total_retido' => $impostoTotal,
            ],
            'liquidos' => [
                'tata' => $liquidoTata,
                'executor' => $liquidoExecutor,
            ],
            'totais' => [
                'custos_internos' => $custosInternos,
                'honorarios_brutos' => (int)($brutos['pct_tata'] ?? 0) + (int)($brutos['pct_executor'] ?? 0),
                'honorarios_liquidos' => $liquidoTata + $liquidoExecutor,
                'valor_zeimusho' => $impostoTotal,
            ],
        ];
    }
}
