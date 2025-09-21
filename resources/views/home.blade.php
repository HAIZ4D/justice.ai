<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ config('app.name', 'Rakyat Legal') }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    :root{
      --bg:#0b0d13; --glass:rgba(255,255,255,.08); --border:rgba(255,255,255,.18);
      --text:#eef2ff; --muted:#cbd5e1; --accent:#7c5cff; --accent-2:#6be1ff;
    }
    *{box-sizing:border-box} html,body{height:100%}
    body{
      margin:0; color:var(--text); font-family:Poppins,ui-sans-serif,system-ui,Segoe UI,Roboto,Arial;
      background:
        radial-gradient(1200px 1200px at -10% -10%, #1a1442 0%, transparent 60%),
        radial-gradient(1000px 900px at 110% -5%, #042a3f 0%, transparent 55%),
        radial-gradient(800px 800px at 120% 120%, #3a105a 0%, transparent 55%),
        var(--bg);
    }
    .container{ max-width:1100px; margin:42px auto; padding:0 16px; }
    .glass{ background:linear-gradient(135deg,rgba(255,255,255,.12),rgba(255,255,255,.06));
      border:1px solid var(--border); border-radius:22px; box-shadow:0 8px 24px rgba(0,0,0,.35);
      backdrop-filter: blur(16px) saturate(120%); -webkit-backdrop-filter: blur(16px) saturate(120%); }
    header.glass{ padding:18px 22px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; }
    .title{ font-size:22px; font-weight:700; letter-spacing:.2px }
    .pill{ background:linear-gradient(135deg,var(--accent),var(--accent-2)); color:#0b0d13; padding:8px 14px; border-radius:999px; font-weight:700; font-size:12px; }
    .card{ padding:18px; margin:14px 0; }
    .row{ display:grid; grid-template-columns:1fr; gap:16px } @media (min-width:900px){ .row{ grid-template-columns:1fr 1fr; } }
    .btn{ border:0; border-radius:14px; padding:12px 18px; font-weight:700; color:#0b0d13; background:linear-gradient(135deg,var(--accent),var(--accent-2)); box-shadow:0 8px 18px rgba(124,92,255,.35); cursor:pointer; text-decoration:none; display:inline-block; }
    .btn.secondary{ color:var(--text); background:transparent; border:1px solid var(--border); }
    textarea{ width:100%; min-height:200px; border-radius:16px; border:1px solid var(--border); padding:14px; color:#0b0d13; background:rgba(255,255,255,.95); outline:none; }
    .hint{ color:var(--muted); font-size:13px; margin-top:6px }
    h2.section{ margin:8px 0 10px; font-size:18px }
    .list{ margin:8px 0 0 0; padding-left:18px }
    .muted{ color:var(--muted) }
    .tag{ display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; margin:6px 6px 0 0; }
    .tag.red{ background: rgba(255,59,48,.2); color:#ffd2cf; border:1px solid rgba(255,59,48,.35) }
    .tag.yellow{ background: rgba(255,214,10,.25); color:#2b2200; border:1px solid rgba(255,214,10,.5) }
    .pdf-wrap{ position: relative; }
    .pdf-page{ position: relative; margin: 12px auto; border-radius: 14px; overflow: hidden; background:#0c0f19; }
    .hl-box{ position:absolute; border-radius:6px; box-shadow:0 0 0 1px rgba(255,255,255,.25) inset; }
    .hl-red{ background: rgba(255,59,48,.24); border:1px solid rgba(255,59,48,.45); }
    .hl-yellow{ background: rgba(255,214,10,.28); border:1px solid rgba(255,214,10,.55); }
    .pdf-note{ color:#ffd2cf; font-size:12px; margin:6px 0 0 }
    .footer{ color:#9aa3b2; font-size:12px; margin:16px 0; display:flex; gap:6px; align-items:center; }
    .dot{ width:6px; height:6px; background:#ffd60a; border-radius:50%; display:inline-block }
  </style>

  <!-- PDF.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.5.136/pdf.min.mjs" type="module"></script>
</head>
<body>
  <div class="container">
    <header class="glass">
      <div class="title">AI Legal Assistant — Demo</div>
      <span class="pill">Liquid Glass UI</span>
    </header>

    @if($flash_err ?? false)
      <section class="glass card" style="border-color:rgba(255,59,48,.45)">
        <strong>Error:</strong> {{ $flash_err }}
      </section>
    @endif

    <section class="glass card">
      <form id="analyzeForm" method="POST" action="{{ route('analyze') }}">
        @csrf
        <p class="muted">Paste contract text → bilingual summary → Play with Amazon Polly (audio saved to S3 in Malaysia).</p>
        <textarea name="contract_text" placeholder="Paste contract text here...">{{ $input ?? '' }}</textarea>
        @error('contract_text')<p style="color:#ffb4b4">{{ $message }}</p>@enderror
        <div style="margin-top:12px; display:flex; gap:10px; align-items:center">
          <button class="btn" type="submit">Analyze</button>
          <span class="hint">Your last text is remembered after refresh.</span>
        </div>
      </form>
    </section>

    <section class="glass card">
      <form id="uploadForm" method="POST" action="{{ route('upload') }}" enctype="multipart/form-data">
        @csrf
        <h2 class="section">Upload PDF (client-side highlights via PDF.js)</h2>
        <p class="muted">Upload a contract PDF. We parse text locally (no Textract) and draw red/yellow overlays in the browser.</p>
        <input type="file" name="contract_pdf" accept="application/pdf"
               style="margin-top:10px; background:rgba(255,255,255,.95); color:#0b0d13; border:1px solid var(--border); padding:10px; border-radius:12px">
        @error('contract_pdf') <p style="color:#ffb4b4">{{ $message }}</p> @enderror
        <div style="margin-top:12px"><button class="btn" type="submit">Upload & Analyze PDF</button></div>
      </form>
    </section>

    @isset($result)
      <div class="row">
        <section class="glass card">
          <h2 class="section">BM — Skor Risiko: {{ $result['summary']['risk_score'] }}/10</h2>
          <h3 class="section">✅ {{ $result['summary']['bm']['obligations_title'] }}</h3>
          <ul class="list">@foreach($result['summary']['bm']['obligations'] as $o)<li>{{ $o }}</li>@endforeach</ul>
          <h3 class="section">❌ {{ $result['summary']['bm']['risks_title'] }}</h3>
          <ul class="list">@foreach($result['summary']['bm']['risks'] as $r)<li>{{ $r }}</li>@endforeach</ul>
          <h3 class="section">⚖️ {{ $result['summary']['bm']['legal_title'] }}</h3>
          <ul class="list">@foreach($result['summary']['bm']['legal'] as $l)<li>{{ $l }}</li>@endforeach</ul>
          <h3 class="section">Cadangan</h3>
          <ul class="list">@foreach($result['summary']['bm']['suggestions'] as $s)<li>{{ $s }}</li>@endforeach</ul>
          <div style="margin-top:10px"><a class="btn secondary" href="{{ route('tts', ['lang' => 'bm']) }}">Play (BM, Polly)</a></div>
        </section>

        <section class="glass card">
          <h2 class="section">EN — Risk Score: {{ $result['summary']['risk_score'] }}/10</h2>
          <h3 class="section">✅ {{ $result['summary']['en']['obligations_title'] }}</h3>
          <ul class="list">@foreach($result['summary']['en']['obligations'] as $o)<li>{{ $o }}</li>@endforeach</ul>
          <h3 class="section">❌ {{ $result['summary']['en']['risks_title'] }}</h3>
          <ul class="list">@foreach($result['summary']['en']['risks'] as $r)<li>{{ $r }}</li>@endforeach</ul>
          <h3 class="section">⚖️ {{ $result['summary']['en']['legal_title'] }}</h3>
          <ul class="list">@foreach($result['summary']['en']['legal'] as $l)<li>{{ $l }}</li>@endforeach</ul>
          <h3 class="section">Next Steps</h3>
          <ul class="list">@foreach($result['summary']['en']['suggestions'] as $s)<li>{{ $s }}</li>@endforeach</ul>
          <div style="margin-top:10px"><a class="btn secondary" href="{{ route('tts', ['lang' => 'en']) }}">Play (EN, Polly)</a></div>
        </section>
      </div>

      <section class="glass card">
        <h2 class="section">Highlights (text-based)</h2>
        @forelse(($tx_hl ?? []) as $h)
          <span class="tag {{ $h['color']=='red' ? 'red' : 'yellow' }}">{{ strtoupper($h['color']) }} — {{ $h['text'] }}</span>
        @empty
          <p class="muted">No highlights found yet.</p>
        @endforelse
      </section>
    @endisset

    @if(isset($s3_key) && str($s3_key)->endsWith('.pdf'))
      <section class="glass card">
        <h2 class="section">Document Viewer (PDF.js overlays)</h2>
        @if(session('ocr_mode') === 'local')
          <p class="muted">Using local PDF text extraction. Scanned PDFs may not highlight (no text layer).</p>
        @endif
        <div style="margin-bottom:8px">
          <button class="btn secondary" id="toggleRed">Toggle Red</button>
          <button class="btn secondary" id="toggleYellow">Toggle Yellow</button>
        </div>
        <div id="pdfViewer" class="pdf-wrap" data-key="{{ $s3_key }}"></div>
        <div id="pdfMsg" class="pdf-note"></div>
      </section>
    @endif

    @if(session('audio_url') || isset($audio_url))
      <section class="glass card">
        <h2 class="section">Playback</h2>
        <audio controls src="{{ session('audio_url') ?? $audio_url }}"></audio>
        <p class="hint">Link expires in ~15 minutes.</p>
      </section>
    @endif

    <div class="footer">
      <span class="dot"></span>
      <span>Educational demo only. Not legal advice.</span>
    </div>
  </div>

  @if(isset($s3_key) && str($s3_key)->endsWith('.pdf'))
  <script type="module">
    import * as pdfjsLib from "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.5.136/pdf.min.mjs";
    const viewer = document.getElementById('pdfViewer');
    const msg    = document.getElementById('pdfMsg');
    const tRed   = document.getElementById('toggleRed');
    const tYel   = document.getElementById('toggleYellow');
    const key    = viewer ? viewer.dataset.key : null;

    const risky    = @json(($result['summary']['en']['risks'] ?? []));
    const oblig    = @json(($result['summary']['en']['obligations'] ?? []));
    const redNeedles   = risky.filter(Boolean).map(s=>s.toLowerCase());
    const yellowNeedles= oblig.filter(Boolean).map(s=>s.toLowerCase());
    if(redNeedles.length === 0){
      redNeedles.push(...["penalty","termination","indemnity","liability","damages","non-refundable","auto-renew","arbitration","exclusive jurisdiction","interest","late fee","confidential","pdpa","personal data"]);
    }

    if (viewer && key) {
      const presign = await fetch(`{{ route('pdf.url') }}?key=${encodeURIComponent(key)}`, {
        credentials: 'same-origin'
      }).then(r=>r.ok?r.json():null).catch(()=>null);

      if (!presign || !presign.url) {
        msg.textContent = "No recent PDF to preview.";
      } else {
        pdfjsLib.GlobalWorkerOptions.workerSrc = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.5.136/pdf.worker.min.js";
        const pdf = await pdfjsLib.getDocument({ url: presign.url, useSystemFonts: true }).promise;

        let any = false;
        for(let p=1; p<=pdf.numPages; p++){
          const page = await pdf.getPage(p);
          const scale = 1.35;
          const viewport = page.getViewport({ scale });

          const canvas = document.createElement('canvas');
          const ctx = canvas.getContext('2d');
          canvas.width = Math.floor(viewport.width);
          canvas.height = Math.floor(viewport.height);

          const pageDiv = document.createElement('div');
          pageDiv.className = 'pdf-page';
          pageDiv.style.width = canvas.width+'px';
          pageDiv.style.height = canvas.height+'px';
          pageDiv.dataset.page = p;
          pageDiv.appendChild(canvas);
          viewer.appendChild(pageDiv);

          await page.render({ canvasContext: ctx, viewport }).promise;

          const text = await page.getTextContent();
          text.items.forEach(item => {
            const s = (item.str || '').toLowerCase();
            if(!s) return;
            let cls = null;
            if(redNeedles.some(n => s.includes(n))) cls = 'hl-red';
            else if(yellowNeedles.some(n => s.includes(n))) cls = 'hl-yellow';
            if(!cls) return;

            const [a,b,c,d,e,f] = item.transform;
            const x = e, y = f;
            const fontHeight = Math.hypot(b, d);
            const width  = item.width * scale;
            const height = fontHeight * scale;

            const box = document.createElement('div');
            box.className = `hl-box ${cls}`;
            const top = canvas.height - y * scale;
            box.style.left   = (x * scale) + 'px';
            box.style.top    = (top - height) + 'px';
            box.style.width  = width + 'px';
            box.style.height = height + 'px';
            pageDiv.appendChild(box);
            any = true;
          });
        }
        if(!any){ msg.textContent = "No text-layer matches found. If this is a scanned PDF, OCR is needed."; }

        if(tRed){ tRed.addEventListener('click', ()=>document.querySelectorAll('.hl-red').forEach(n=>n.style.display = (n.style.display==='none'?'':'none')) ); }
        if(tYel){ tYel.addEventListener('click', ()=>document.querySelectorAll('.hl-yellow').forEach(n=>n.style.display = (n.style.display==='none'?'':'none')) ); }
      }
    }
  </script>
  @endif
</body>
</html>
