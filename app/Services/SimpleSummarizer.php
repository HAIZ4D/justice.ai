<?php

namespace App\Services;

class SimpleSummarizer
{
    private array $riskKw = [
        'penalty','termination','indemnity','liability','damages',
        'non-refundable','auto-renew','arbitration','exclusive jurisdiction',
        'interest','late fee','confidential','data','pdpa','personal data'
    ];

    public function analyze(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text));
        $lines = array_values(array_filter($lines, fn($l)=>strlen(trim($l))>0));

        $obligations = array_values(array_filter($lines, fn($l)=>
            preg_match('/\b(shall|must|agree|responsible)\b/i', $l)
        ));

        $risks = [];
        foreach ($lines as $l) {
            foreach ($this->riskKw as $kw) {
                if (stripos($l, $kw) !== false) { $risks[] = $l; break; }
            }
        }
        $risks = array_values(array_unique($risks));
        $riskScore = min(10, round(count($risks)*1.5, 1));

        $bm = [
            'title' => 'Ringkasan Kontrak',
            'obligations_title' => '✅ Kewajipan Anda',
            'obligations' => array_slice($obligations, 0, 5),
            'risks_title' => '❌ Risiko Kepada Anda',
            'risks' => array_slice($risks, 0, 5),
            'legal_title' => '⚖️ Konteks Undang-Undang',
            'legal' => [
                'Semak klausa penalti & pemprosesan data di bawah PDPA 2010.',
            ],
            'suggestions' => [
                'Cuba runding pengurangan denda (cth. 3 bulan → 1 bulan).',
                'Tambah tempoh bertenang (cooling-off) atau hak batal tanpa penalti.',
            ],
        ];

        $en = [
            'title' => 'Contract Summary',
            'obligations_title' => '✅ Your Obligations',
            'obligations' => array_slice($obligations, 0, 5),
            'risks_title' => '❌ Risks to You',
            'risks' => array_slice($risks, 0, 5),
            'legal_title' => '⚖️ Legal Context',
            'legal' => [
                'Review penalty enforceability and PDPA 2010 data terms.',
            ],
            'suggestions' => [
                'Negotiate lower penalties (e.g., 3 months → 1 month).',
                'Add a cooling-off period or no-penalty cancellation right.',
            ],
        ];

        $highlights = [];
        foreach ($lines as $i => $l) {
            $color = in_array($l, $risks, true) ? 'red'
                   : (in_array($l, $obligations, true) ? 'yellow' : null);
            if ($color) $highlights[] = ['line'=>$i+1,'text'=>$l,'color'=>$color];
        }

        return ['summary'=>['bm'=>$bm,'en'=>$en,'risk_score'=>$riskScore],'highlights'=>$highlights];
    }
}
