<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Status</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Подключаем Bootstrap для стилей -->
    <link rel="stylesheet" href="{{ asset('resources/css/bootstrap.min.css') }}">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-center">Download Status</h2>
                    </div>
                    <div class="card-body">
                        <div id="status">
                            <p class="text-center">Current status: <span id="status-text" class="fw-bold">{{ $download->status }}</span></p>
                            
                            <div id="progress" class="text-center mt-4" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Processing your download... This may take several minutes.</p>
                            </div>
                            
                            <div id="completed" class="text-center mt-4" style="display: none;">
                                <div class="alert alert-success" role="alert">
                                    <h4 class="alert-heading">Your download is ready!</h4>
                                    <p>Click the button below to download your file.</p>
                                    <hr>
                                    <button id="download-btn" class="btn btn-primary btn-lg">
                                        Download Now
                                    </button>
                                </div>
                                
                                <!-- Кнопка "Вернуться на главную" -->
                                <div id="return-section" class="mt-3" style="display: none;">
                                    <p>Your download should start automatically. If it doesn't, click the download button above.</p>
                                    <a href="{{ route('index') }}" class="btn btn-outline-secondary">
                                        ← Return to Home Page
                                    </a>
                                </div>
                            </div>
                            
                            <div id="failed" class="text-center mt-4" style="display: none;">
                                <div class="alert alert-danger" role="alert">
                                    <h4 class="alert-heading">Download Failed</h4>
                                    <p>Sorry, we couldn't process your download request. Please try again.</p>
                                </div>
                                <a href="{{ route('index') }}" class="btn btn-primary">
                                    ← Return to Home Page
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Функция для начала скачивания
            function startDownload() {
                const downloadUrl = $('#download-btn').data('url');
                
                // Открываем скачивание в новом окне/вкладке
                window.open(downloadUrl, '_blank');
                
                // Показываем кнопку "Вернуться"
                $('#return-section').show();
                $('#download-btn').text('Download Again');
                
                // Автоматический редирект через 10 секунд
                setTimeout(function() {
                    window.location.href = "{{ route('index') }}";
                }, 10000);
            }
            
            // Обработчик клика по кнопке скачивания
            $('#download-btn').on('click', startDownload);
            
            function checkStatus() {
                $.get('{{ route('download.check', ['id' => $download->id]) }}', function(data) {
                    $('#status-text').text(data.status);
                    
                    if (data.status === 'processing') {
                        $('#progress').show();
                    } else if (data.status === 'completed') {
                        $('#progress').hide();
                        $('#completed').show();
                        $('#download-btn').data('url', data.file_url);
                        
                        // Автоматически начинаем скачивание через 1 секунду
                        setTimeout(startDownload, 1000);
                    } else if (data.status === 'failed') {
                        $('#progress').hide();
                        $('#failed').show();
                        clearInterval(interval);
                    }
                }).fail(function() {
                    console.error('Error checking download status');
                });
            }
            
            // Проверяем статус каждые 5 секунд
            var interval = setInterval(checkStatus, 5000);
            checkStatus(); // Первоначальная проверка
            
            // Останавливаем проверку через 10 минут на всякий случай
            setTimeout(function() {
                clearInterval(interval);
            }, 600000);
        });
    </script>
    
    <!-- Подключаем Bootstrap JS -->
    <script >

    @vite('resources/js/bootstrap.bundle.min.js')
    
    </script>
</body>
</html>