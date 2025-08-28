<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ZipArchive;
use Illuminate\Support\Facades\Log;
use App\Models\Download;

class DownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $downloadId;
    protected $safeUrl;
    protected $delPath;
    protected $command;
    protected $subs_lang;
    protected $subs_formats;
    protected array $params;
    protected $ytdlpPath = 'C:\\ffmpeg\\yt-dlp.exe';
    protected $ffmpegPath = 'C:\\ffmpeg\\ffmpeg.exe';
    protected $outputDir = '"C:\\xampp\\htdocs\\ytLoaderPro\\downloads\\download_%(title)s\\';


    /**
     * Create a new job instance.
     */
    public function __construct(int $downloadId, string $url, array $params, string $delPath, string $subs_lang, string $subs_formats)
    {
        $this->downloadId = $downloadId;
        $this->safeUrl = escapeshellarg($url);
        $this->delPath = $delPath;
        $this->command = "$this->ytdlpPath -P " . $this->delPath . " -f ";
        $this->subs_lang = $subs_lang ?? null;
        $this->subs_formats = $subs_formats ?? null;
        $this->params = $params ?? null;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //find the id of the required record in table
        $download = Download::find($this->downloadId);
        if (!$download) {
            Log::error('Download record not found', ['download_id' => $this->downloadId]);
            return;
        }

        try {
            //update status
            $download->update(['status' => 'processing']);

            //download video files
            $result = $this->download($this->params);

            if ($result['success']) {

                //create zip archive
                $zipPath = $this->createZip();

                //change status on 'completed'
                $download->update(['status' => 'completed', 'file_path' => $zipPath]);

                Log::info('Video downloaded successfully', ['video_url' => $this->safeUrl, 'result' => $result]);
            } else {

                Log::error('Video downloaded failed', ['video_url' => $this->safeUrl, 'result' => $result, 'download_id' => $this->downloadId]);
                $download->update(['status' => 'failed', 'file_path' => '']);
            }
        } catch (\Exception $e) {

            Log::error('Video downloaded failed', ['video_url' => $this->safeUrl, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->fail($e);
        }
    }


    public function executeCommandDownload(string $command)
    {
        $output = [];
        $status = 0;

        try {
            exec("$command 2>&1", $output, $status);
        } catch (\Exception $e) {
            die("Error: " . $e->getMessage());
        }

        return [
            'success' => $status === 0,
            'status' => $status
        ];
    }


    public function download(array $options): array
    {
        $lang = "";

        if (!empty($this->subs_lang)) {
            $lang = substr($this->subs_lang, 0, -2);
        }

        if (count($options) == 0) {
            $this->command = $this->command . " best ";
        } else {
            for ($i = 0; $i < count($options); $i++) {
                $this->command = $this->command . "$options[$i]";
                if ($i + 1 < count($options)) {
                    $this->command = $this->command . "+";
                }
            }
        }

        $caption_subs_format = "";
        if (substr($this->subs_lang, -1) == '0') {
            $caption_subs_format = $caption_subs_format . "--write-sub --sub-langs $lang";
        } elseif (substr($this->subs_lang, -1) == '1') {
            $caption_subs_format = $caption_subs_format . "--write-auto-sub --sub-langs $lang";
        }

        if (!empty($this->subs_formats)) {
            $caption_subs_format = $caption_subs_format . " --sub-format $this->subs_formats";
        }

        $this->command = $this->command . " $caption_subs_format" . " $this->safeUrl";

        //execute the download command
        $response = $this->executeCommandDownload($this->command);

        return $response;
    }


    protected function createZip(): string
    {
        $zip = new ZipArchive();
        $zipFileName = 'download_' . time() . '.zip';
        $zipPath = storage_path('app/public/' . $zipFileName);

        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            $files = glob($this->delPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $zip->addFile($file, basename($file));
                }
            }
            $zip->close();
        }

        // Очистка временной директории
        $this->delDir($this->delPath);

        return 'public/' . $zipFileName;
    }


    protected function delDir(string $path): void
    {
        if (!file_exists($path))
            return;

        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->delDir($file) : unlink($file);
        }
        rmdir($path);
    }

}
