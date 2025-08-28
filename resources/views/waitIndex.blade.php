<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="{{ asset('resources/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('resources/css/styles.css') }}">
  <link rel="shortcut icon" href="{{ asset('images/ico.svg') }}" type="image/x-icon">
  <title>Yt Loader Pro - Processing</title>

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
    <div class="p-4 bg-white shadow rounded-4">
      <!-- URL input (disabled) -->
      <div class="row g-3 align-items-center mb-4">
        <div class="col-md-9">
          <input type="text" class="form-control form-control-lg" value="{{ $previewData->url }}" disabled>
        </div>
        <div class="col-md-3 d-grid">
          <button class="btn btn-secondary btn-lg" disabled>
            ⏳ Processing...
          </button>
        </div>
      </div>

      <!-- Preview (disabled) -->
      <div class="row g-4">
        <div class="col-md-3 text-center">
          <img src="{{ asset('images/PlaceIMG.svg') }}" class="img-fluid rounded shadow-sm opacity-50" alt="thumbnail">
        </div>
        
        <div class="col-md-9">
          <h3 class="mb-3 text-muted">
            Обработка preview...
          </h3>

          <!-- Video formats (disabled) -->
          <div class="d-flex align-items-center mb-3">
            <select class="form-select me-2" disabled>
              <option value="">Видео форматы</option>
            </select>
            <button class="btn btn-outline-secondary" disabled>
              ⬇️
            </button>
          </div>

          <!-- Audio formats (disabled) -->
          <div class="d-flex align-items-center mb-3">
            <select class="form-select me-2" disabled>
              <option value="">Аудио форматы</option>
            </select>
            <button class="btn btn-outline-secondary" disabled>
              🎵
            </button>
          </div>

          <!-- Subtitles (disabled) -->
          <div class="row g-2 align-items-center mb-3">
            <div class="col-md-6">
              <select class="form-select" disabled>
                <option value="">Субтитры</option>
              </select>
            </div>
            <div class="col-md-4">
              <select class="form-select" disabled>
                <option value="">Формат</option>
              </select>
            </div>
            <div class="col-md-2 d-grid">
              <button class="btn btn-outline-secondary" disabled>
                💬
              </button>
            </div>
          </div>

          <!-- Download All (disabled) -->
          <div class="text-center mt-4">
            <button class="btn btn-secondary btn-lg px-5 rounded-pill shadow" disabled>
              ⬇️ Скачать всё
            </button>
          </div>
        </div>
      </div>

      <!-- Progress indicator -->
      <div class="text-center mt-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Обрабатываем preview, пожалуйста подождите...</p>
        
        <!-- Debug info (можно удалить после тестирования) -->
        <div class="mt-3 small text-muted">
          <p>ID: {{ $previewData->id }}</p>
          <p>Status: {{ $previewData->status }}</p>
          <p>URL: {{ $previewData->url }}</p>
        </div>
      </div>
    </div>
  </main>

  <footer class="text-center py-3 text-muted small">
    © 2025 YT Loader Pro. Все права защищены.
  </footer>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(document).ready(function() {
      function checkStatus() {
        $.get('{{ route('preview.check', ['id' => $previewData->id]) }}', function(data) {
          if (data.status === 'completed') {
            // Перенаправляем на главную страницу
            window.location.href = '{{ route('preview.return',['id' => $previewData->id]) }}';
          } else if (data.status === 'failed') {
            // Показываем ошибку
            alert('Ошибка при обработке preview');
            window.location.href = '{{ route('preview.return',['id' => $previewData->id]) }}';
          } else {
            // Продолжаем проверять статус
            setTimeout(checkStatus, 2000);
          }
        }).fail(function() {
          // В случае ошибки запроса, продолжаем проверять
          setTimeout(checkStatus, 2000);
        });
      }

      // Начинаем проверку статуса
      setTimeout(checkStatus, 2000);
    });
  </script>
</body>
</html>