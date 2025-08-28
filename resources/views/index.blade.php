<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="{{ asset('resources/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('resources/css/styles.css') }}">
  <link rel="shortcut icon" href="{{ asset('images/ico.svg') }}" type="image/x-icon">
  <title>Yt Loader Pro</title>

  @vite('resources/css/bootstrap.min.css')
  @vite('resources/css/styles.css')
</head>

<body class="bg-light text-dark">

  <header class="py-4 bg-white shadow-sm">
    <div class="container text-center">
      <h1 class="fw-bold text-primary">YT Loader Pro</h1>
      <p class="text-muted mb-0">Загружай видео, аудио и субтитры в один клик</p>
    </div>
  </header>

  <main class="container py-5">
    <form class="p-4 bg-white shadow rounded-4" action="{{ route('process.form') }}" method="POST" novalidate >
      @csrf

      <!-- URL input -->
      <div class="row g-3 align-items-center mb-4">
        <div class="col-md-9">
          @if (session()->has('previewData.url'))          
              <input type="text" id="url" name="url" class="form-control form-control-lg" placeholder="Вставьте ссылку" value="{{ session('previewData')['url'] }}">
          @else
              <input type="text" id="url" name="url" class="form-control form-control-lg" placeholder="Вставьте ссылку" value="">
          @endif
          
        </div>
        <div class="col-md-3 d-grid">
          <button type="submit" name="preview" class="btn btn-primary btn-lg">
            🔍 Preview
          </button>
        </div>
      </div>

      <!-- Preview -->
      <div class="row g-4">
        <div class="col-md-3 text-center">
          @if (session()->has('previewData.thumbnail'))
              <img src="{{ session('previewData')['thumbnail'] }}" class="img-fluid rounded shadow-sm" alt="thumbnail">
          @else
              <img src="{{ asset('images/PlaceIMG.svg') }}" class="img-fluid rounded shadow-sm" alt="thumbnail">
          @endif
        </div>
        
        <div class="col-md-9">
          <h3 class="mb-3">
            @if (session()->has('previewData.title'))
                {{ session('previewData')['title'] }}
            @else
                <span class="text-muted">Предпросмотр видео появится здесь</span>
            @endif
          </h3>

          <!-- Video formats -->
          <div class="d-flex align-items-center mb-3">
            <select class="form-select me-2" name="video-formats">
              <option value="">Видео форматы</option>
              @if(session()->has('previewData.videoFormats'))
                @foreach (session('previewData')['videoFormats'] as $format)
                  <option value="{{ $format['format_id'] }}">
                    {{ $format['ext'] ?? 'N/A'}} · {{ $format['filesize'] ?? 'N/A'}} · {{ $format['codec'] ?? 'N/A'}} · {{ $format['fps'] ?? 'N/A'}} · {{ $format['tbr'] ?? 'N/A'}} · {{ $format['vbr'] ?? 'N/A'}} · {{ $format['asr'] ?? 'N/A'}} · {{ $format['dynamic_range'] ?? 'N/A'}} · {{ $format['resolution'] ?? 'N/A'}} · {{ $format['format_note'] ?? 'N/A'}}
                  </option>
                @endforeach
              @else
                <option disabled>Нет доступных форматов</option>
              @endif
            </select>
            <button type="submit" name="download-video" class="btn btn-outline-primary">
              ⬇️
            </button>
          </div>

          <!-- Audio formats -->
          <div class="d-flex align-items-center mb-3">
            <select class="form-select me-2" name="audio-formats">
              <option value="">Аудио форматы</option>
              @if(session()->has('previewData.audioFormats'))
                @foreach(session('previewData')['audioFormats'] as $format)
                  <option value="{{ $format['format_id'] }}">
                    {{ $format['ext'] ?? 'N/A'}} · {{ $format['filesize'] ?? 'N/A'}} · {{ $format['lang'] ?? 'N/A'}} · {{ $format['codec'] ?? 'N/A'}} · {{ $format['abr'] ?? 'N/A'}} · {{ $format['tbr'] ?? 'N/A'}} · {{ $format['asr'] ?? 'N/A'}}
                  </option>
                @endforeach
              @else
                <option disabled>Нет доступных форматов</option>
              @endif
            </select>
            <button type="submit" name="download-audio" class="btn btn-outline-primary">
              🎵
            </button>
          </div>

          <!-- Subtitles -->
          <div class="row g-2 align-items-center mb-3">
            <div class="col-md-6">
              <select class="form-select" name="subs-lang">
                <option value="">Субтитры</option>
                @if (session()->has('previewData.subtitles'))
                  @foreach (session('previewData')["subtitles"] as $subs)
                    
                    <option value="{{ $subs['lang_code'] }}">{{ $subs['lang_name']?? 'N/A' }}</option>
                  @endforeach
                @else
                  <option disabled>Нет субтитров</option>
                @endif
              </select>
            </div>
            <div class="col-md-4">
              <select class="form-select" name="subs-formats">
                <option value="">Формат</option>
                <option value="vtt">vtt</option>
                <option value="srt">srt</option>
                <option value="ttml">ttml</option>
              </select>
            </div>
            <div class="col-md-2 d-grid">
              <button type="submit" name="download-subs" class="btn btn-outline-primary">
                💬
              </button>
            </div>
          </div>

          <!-- Download All -->
          <div class="text-center mt-4">
            <button type="submit" name="download" class="btn btn-success btn-lg px-5 rounded-pill shadow">
              ⬇️ Скачать всё
            </button>
          </div>
        </div>
      </div>
    </form>
  </main>

  <footer class="text-center py-3 text-muted small">
    © 2025 YT Loader Pro. Все права защищены.
  </footer>

  <script>
    @vite('resources/js/bootstrap.bundle.min.js')
    @vite('resources/js/script.js')
  </script>
</body>
</html>
