<?php

declare(strict_types=1);

if (!function_exists('financialReceiptSanitizePhone')) {
    function financialReceiptSanitizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string)$phone);
        return $digits !== '' ? $digits : 'sem_telefone';
    }
}

if (!function_exists('financialReceiptBuildHtml')) {
    function financialReceiptBuildHtml(array $receiptData): string
    {
        $valor = (int)($receiptData['valor_total'] ?? 0);
        $impostoRetido = (int)($receiptData['imposto_retido'] ?? 0);
        $valorLiquido = (int)($receiptData['valor_liquido_medium'] ?? max(0, $valor - $impostoRetido));
        $dataRecibo = (string)($receiptData['data_pagamento'] ?? $receiptData['data_realizacao'] ?? date('Y-m-d'));
        $destinatario = (string)($receiptData['destinatario'] ?? '________________________________');
        $descricaoJp = (string)($receiptData['descricao_jp'] ?? '宗教儀式提供料として');
        $descricaoPt = (string)($receiptData['descricao_pt'] ?? 'Referente a serviços de cerimônia religiosa');
        $npoNome = (string)($receiptData['npo_nome'] ?? 'CRM Terreiro');
        $npoEndereco = (string)($receiptData['npo_endereco'] ?? 'Tsu, Mie, Japão');
        $mediumNome = (string)($receiptData['medium_name'] ?? '—');
        $tataNome = (string)($receiptData['tata_name'] ?? '—');
        $numero = (string)($receiptData['receipt_no'] ?? '—');
        $mostrarSelo = $valor > 50000;

        $h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');

        return '<!doctype html>'
            . '<html lang="pt-BR"><head><meta charset="UTF-8"><title>領収書</title>'
            . '<style>'
            . 'body{font-family:DejaVu Sans, sans-serif;background:#fff;color:#111;margin:0;padding:24px;}'
            . '.receipt-shell{border:2px solid #111;border-radius:24px;padding:32px;max-width:920px;margin:0 auto;}'
            . '.top{display:flex;justify-content:space-between;gap:16px;border-bottom:1px solid #cbd5e1;padding-bottom:16px;margin-bottom:24px;}'
            . '.muted{color:#475569;font-size:12px;}.title{font-size:32px;font-weight:800;margin:0;}.sub{font-size:13px;letter-spacing:0.25em;color:#64748b;margin:0 0 6px 0;}'
            . '.grid{display:grid;gap:24px;}.grid2{grid-template-columns:1fr 260px;}.grid-half{grid-template-columns:1fr 1fr;}'
            . '.label{font-size:12px;color:#64748b;margin-bottom:6px;}.line{border-bottom:1px solid #111;min-height:42px;padding-top:8px;font-size:18px;font-weight:700;}'
            . '.amount{border-bottom:1px solid #111;padding:8px 0;text-align:center;font-size:38px;font-weight:800;}'
            . '.box{border:1px solid #cbd5e1;border-radius:16px;padding:16px;background:#f8fafc;}.box p{margin:0 0 8px 0;}.hanko{height:120px;border:2px solid #111;border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;text-align:center;}'
            . '.footer{border-top:1px dashed #cbd5e1;padding-top:16px;margin-top:24px;font-size:11px;line-height:1.6;color:#475569;}'
            . '.alert{margin-bottom:16px;border:1px solid #f59e0b;background:#fffbeb;color:#92400e;border-radius:16px;padding:12px 16px;font-size:12px;}'
            . '</style></head><body>'
            . ($mostrarSelo ? '<div class="alert"><strong>Lembrete fiscal:</strong> se este recibo for impresso e o valor exceder ¥50.000, colar selo de imposto de ¥200 (Shunyu Inshi).</div>' : '')
            . '<section class="receipt-shell">'
            . '<div class="top"><div><p class="sub">領収書</p><h1 class="title">RECIBO</h1></div>'
            . '<div style="text-align:right;font-size:12px;"><p><strong>Data / 日付:</strong> ' . $h(date('Y/m/d', strtotime($dataRecibo))) . '</p><p><strong>No.:</strong> ' . $h($numero) . '</p></div></div>'
            . '<div class="grid grid2" style="margin-bottom:24px;">'
            . '<div><div class="label">様 / Sr(a).</div><div class="line">' . $h($destinatario) . '</div></div>'
            . '<div><div class="label">金額 / Valor</div><div class="amount">¥ ' . number_format($valor, 0, ',', ',') . '-</div></div>'
            . '</div>'
            . '<div class="box" style="margin-bottom:24px;"><p class="label" style="margin-bottom:8px;">但し書き / Referente a</p><p style="font-size:18px;font-weight:700;">' . $h($descricaoJp) . '</p><p style="font-size:13px;color:#475569;">' . $h($descricaoPt) . '</p></div>'
            . '<div class="grid grid-half" style="margin-bottom:24px;">'
            . '<div class="box"><p style="font-weight:700;">日本語</p><p>上記金額を正に領収いたしました。</p><p>源泉徴収税額: ¥ ' . number_format($impostoRetido, 0, ',', ',') . '</p><p>支払予定額: ¥ ' . number_format($valorLiquido, 0, ',', ',') . '</p></div>'
            . '<div class="box"><p style="font-weight:700;">Português</p><p>Recebemos o valor acima referente ao serviço religioso descrito.</p><p>Imposto retido na fonte: ¥ ' . number_format($impostoRetido, 0, ',', ',') . '</p><p>Valor líquido do médium: ¥ ' . number_format($valorLiquido, 0, ',', ',') . '</p></div>'
            . '</div>'
            . '<div class="grid" style="grid-template-columns:1fr 120px;align-items:end;">'
            . '<div><p style="font-size:18px;font-weight:700;margin:0 0 6px 0;">' . $h($npoNome) . '</p><p class="muted">' . $h($npoEndereco) . '</p><p class="muted" style="margin-top:8px;">Medium / Executor: ' . $h($mediumNome) . '</p><p class="muted">Tata: ' . $h($tataNome) . '</p></div>'
            . '<div class="hanko">印<br>HANKO</div>'
            . '</div>'
            . '<div class="footer"><p>JP: 本書類には、日本の税法に基づく源泉徴収（10.21%）の控除額が明記されています。控除額は税務署納付用として留保されます。</p><p>PT: Este recibo informa a retenção de imposto de renda na fonte (Gensen Choshu - 10,21%) conforme a legislação tributária japonesa. O valor retido permanece reservado para recolhimento ao Zeimusho.</p></div>'
            . '</section></body></html>';
    }
}

if (!function_exists('financialReceiptSavePdf')) {
    function financialReceiptSavePdf(array $receiptData, string $basePath): array
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            $autoload = $basePath . '/vendor/autoload.php';
            if (file_exists($autoload)) {
                require_once $autoload;
            }
        }

        if (!class_exists(\Dompdf\Dompdf::class)) {
            throw new RuntimeException('Dompdf não está disponível para gerar o recibo.');
        }

        $receiptDate = (string)($receiptData['data_pagamento'] ?? $receiptData['data_realizacao'] ?? date('Y-m-d'));
        $year = date('Y', strtotime($receiptDate));
        $month = date('m', strtotime($receiptDate));
        $dirRelative = 'recibos/' . $year . '/' . $month;
        $dirAbsolute = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dirRelative);
        if (!is_dir($dirAbsolute) && !mkdir($dirAbsolute, 0775, true) && !is_dir($dirAbsolute)) {
            throw new RuntimeException('Não foi possível criar a pasta do recibo.');
        }

        $phoneBase = financialReceiptSanitizePhone((string)($receiptData['cliente_telefone'] ?? ''));
        $seq = 1;
        do {
            $filename = $phoneBase . '_' . $seq . '.pdf';
            $absolutePath = $dirAbsolute . DIRECTORY_SEPARATOR . $filename;
            $seq++;
        } while (file_exists($absolutePath));

        $html = financialReceiptBuildHtml($receiptData);
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $output = $dompdf->output();

        if (file_put_contents($absolutePath, $output) === false) {
            throw new RuntimeException('Não foi possível salvar o PDF do recibo.');
        }

        return [
            'relative_path' => $dirRelative . '/' . $filename,
            'absolute_path' => $absolutePath,
            'filename' => $filename,
            'html' => $html,
        ];
    }
}
