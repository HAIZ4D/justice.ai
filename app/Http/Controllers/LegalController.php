<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Aws\Polly\PollyClient;
use Aws\S3\S3Client;
use App\Services\SimpleSummarizer;
use App\Services\LocalPdfReader;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;


class LegalController extends Controller
{
    public function index()
    {
        return view('home', [
            'input'     => session('last_input'),
            'result'    => session('last_result'),
            's3_key'    => session('s3_key'),
            'audio_url' => session('audio_url'),
            'tx_lines'  => session('textract_lines'),         // reused name; from local reader now
            'tx_hl'     => session('textract_highlights'),     // reused name; highlights list
            'ocr_mode'  => session('ocr_mode'),                // 'local'
            'flash_err' => session('error'),
        ]);
    }

    public function analyze(Request $req, SimpleSummarizer $sum)
    {
        $req->validate(['contract_text' => 'required|string|min:30']);
        $text = trim($req->input('contract_text'));

        $key = 'contracts/'.now()->format('Ymd_His').'_'.uniqid().'.txt';
        Storage::disk('s3_uploads')->put($key, $text);

        $result = $sum->analyze($text);

        session([
            'last_input'      => $text,
            'last_result'     => $result,
            's3_key'          => $key,
            'last_summary_bm' => $this->flatten($result['summary']['bm']),
            'last_summary_en' => $this->flatten($result['summary']['en']),
        ]);
        return redirect()->route('home');
    }

    public function upload(Request $req, LocalPdfReader $local, SimpleSummarizer $sum)
    {
        $req->validate(['contract_pdf' => 'required|file|mimes:pdf|max:20480']);
        $pdf = $req->file('contract_pdf');

        // store PDF in S3 (MY)
        $key = 'contracts/'.now()->format('Ymd_His').'_'.uniqid().'.pdf';
        Storage::disk('s3_uploads')->put($key, file_get_contents($pdf->getRealPath()));

        // local text extraction
        try {
            $lines = $local->extract($pdf->getRealPath()); // [{page,text,bbox:null}]
            $ocrMode = 'local';
        } catch (\Throwable $e) {
            Log::error('Local PDF parse failed: '.$e->getMessage());
            return back()->with('error', 'PDF parsing failed: '.$e->getMessage());
        }

        // analyze text to produce risks/obligations & score
        $allText = implode("\n", array_column($lines, 'text'));
        $analysis = $sum->analyze($allText);

        // map simple highlights (string contains)
        $riskySet = array_flip($analysis['summary']['en']['risks']);
        $obligSet = array_flip($analysis['summary']['en']['obligations']);
        $hl = [];
        foreach ($lines as $ln) {
            $color = null;
            foreach ($riskySet as $t => $_) {
                if ($t && stripos($ln['text'], $t) !== false) { $color = 'red'; break; }
            }
            if (!$color) {
                foreach ($obligSet as $t => $_) {
                    if ($t && stripos($ln['text'], $t) !== false) { $color = 'yellow'; break; }
                }
            }
            if ($color) {
                $hl[] = ['page' => $ln['page'], 'text' => $ln['text'], 'color' => $color, 'bbox' => $ln['bbox']];
            }
        }

        session([
            'last_input'        => null,
            'last_result'       => $analysis,
            's3_key'            => $key,
            'textract_lines'    => $lines,
            'textract_highlights' => $hl,
            'last_summary_bm'   => $this->flatten($analysis['summary']['bm']),
            'last_summary_en'   => $this->flatten($analysis['summary']['en']),
            'ocr_mode'          => $ocrMode,
        ]);

        return redirect()->route('home');
    }

public function pdfUrl(Request $request)
{
    // Prefer explicit key; fall back to session for backward-compat
    $key = $request->query('key', session('s3_key'));
    if (!$key || !str_ends_with(strtolower($key), '.pdf')) {
        return response()->json(['error' => 'No recent PDF'], 404);
    }

    $s3 = new \Aws\S3\S3Client([
        'version' => 'latest',
        'region'  => env('AWS_DEFAULT_REGION', 'ap-southeast-5'),
    ]);

    $cmd = $s3->getCommand('GetObject', [
        'Bucket' => env('S3_BUCKET_UPLOADS'),
        'Key'    => $key,
    ]);
    $req = $s3->createPresignedRequest($cmd, '+15 minutes');
    return response()->json(['url' => (string) $req->getUri()]);
}


    public function tts(string $lang)
    {
        $text = $lang === 'bm' ? session('last_summary_bm') : session('last_summary_en');
        if (!$text) return back()->with('error','Nothing to read. Analyze first.');

        try {
            $polly = new PollyClient([
                'version' => 'latest',
                'region'  => env('POLLY_REGION', env('AWS_DEFAULT_REGION', 'ap-southeast-5')),
            ]);

            $voiceId = 'Joanna'; // safe demo voice
            $res = $polly->synthesizeSpeech([
                'OutputFormat' => 'mp3',
                'VoiceId'      => $voiceId,
                'Text'         => $text,
            ]);

            $audioKey = 'audio/'.now()->format('Ymd_His').'_'.uniqid().'.mp3';
            Storage::disk('s3_audio')->put($audioKey, $res['AudioStream']);

            $s3 = new S3Client([
                'version' => 'latest',
                'region'  => env('AWS_DEFAULT_REGION', 'ap-southeast-5'),
            ]);
            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => env('S3_BUCKET_AUDIO'),
                'Key'    => $audioKey,
            ]);
            $request = $s3->createPresignedRequest($cmd, '+15 minutes');
            $url = (string) $request->getUri();

            return back()->with('audio_url', $url);

        } catch (\Throwable $e) {
            Log::error('TTS error: '.$e->getMessage());
            return back()->with('error', 'TTS failed: '.$e->getMessage());
        }
    }

    private function flatten(array $card): string
    {
        $parts = [];
        $parts[] = $card['title'];
        $parts[] = $card['obligations_title'];
        $parts = array_merge($parts, $card['obligations']);
        $parts[] = $card['risks_title'];
        $parts = array_merge($parts, $card['risks']);
        $parts[] = $card['legal_title'];
        $parts = array_merge($parts, $card['legal']);
        $parts[] = 'Cadangan / Suggestions:';
        $parts = array_merge($parts, $card['suggestions']);
        return implode(". ", array_map(fn($x)=>trim($x,'. '), array_filter($parts)));
    }


public function stamp(Request $req)
{
    $data = $req->validate([
        'key' => 'required|string',
        'boxes' => 'required|array',
    ]);

    $bucket = env('S3_BUCKET_UPLOADS');
    $key    = $data['key'];

    $tmpIn  = storage_path('app/tmp_in.pdf');
    $tmpOut = storage_path('app/tmp_out.pdf');

    $s3 = new \Aws\S3\S3Client([
        'version'=>'latest',
        'region'=>env('AWS_DEFAULT_REGION','ap-southeast-5'),
    ]);
    $s3->getObject(['Bucket'=>$bucket,'Key'=>$key,'SaveAs'=>$tmpIn]);

    $pdf = new Fpdi();
    $pageCount = $pdf->setSourceFile($tmpIn);

    $byPage = [];
    foreach ($data['boxes'] as $b) {
        $p = max(1, (int)($b['page'] ?? 1));
        $byPage[$p][] = $b;
    }

    for ($p=1; $p<=$pageCount; $p++) {
        $tplId = $pdf->importPage($p);
        $size  = $pdf->getTemplateSize($tplId);
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);

        if (!empty($byPage[$p])) {
            foreach ($byPage[$p] as $b) {
                if (($b['color'] ?? 'red') === 'yellow') {
                    $pdf->SetDrawColor(204, 164, 0);
                    $pdf->SetFillColor(255, 235, 153);
                } else {
                    $pdf->SetDrawColor(170, 40, 35);
                    $pdf->SetFillColor(255, 180, 180);
                }
                $x = $b['left'] * $size['width'];
                $y = $b['top'] * $size['height'];
                $w = $b['width'] * $size['width'];
                $h = $b['height'] * $size['height'];
                $pdf->Rect($x, $y, $w, $h, 'FD');
            }
        }
    }

    $pdf->Output('F', $tmpOut);

    $outKey = 'stamped/'.pathinfo($key, PATHINFO_FILENAME).'_stamped.pdf';
    $s3->putObject([
        'Bucket'=>$bucket,
        'Key'=>$outKey,
        'Body'=>file_get_contents($tmpOut),
        'ContentType'=>'application/pdf',
    ]);

    $cmd = $s3->getCommand('GetObject', ['Bucket'=>$bucket,'Key'=>$outKey]);
    $req = $s3->createPresignedRequest($cmd, '+15 minutes');

    return response()->json(['url'=>(string)$req->getUri()]);
}

public function pdfStream(\Illuminate\Http\Request $request)
{
    $key = $request->query('key');
    if (!$key || !str_ends_with(strtolower($key), '.pdf')) {
        return response('Missing key', 400);
    }

    try {
        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION', 'ap-southeast-5'),
        ]);

        $res = $s3->getObject([
            'Bucket' => env('S3_BUCKET_UPLOADS'),
            'Key'    => $key,
        ]);

        // Stream it back to the browser
        return response($res['Body'], 200, [
            'Content-Type'   => $res['ContentType'] ?? 'application/pdf',
            'Content-Length' => (string)($res['ContentLength'] ?? strlen((string)$res['Body'])),
            'Accept-Ranges'  => 'bytes',
            'Cache-Control'  => 'no-store',
        ]);
    } catch (\Throwable $e) {
        Log::error('pdfStream failed', ['key'=>$key, 'err'=>$e->getMessage()]);
        return response('Stream failed', 500);
    }
}

}
