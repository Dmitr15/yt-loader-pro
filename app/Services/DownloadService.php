<?php

namespace App\Services;

use ZipArchive;

class DownloadService
{
    private $ytdlpPath = 'C:\\ffmpeg\\yt-dlp.exe';
    private $ffmpegPath = 'C:\\ffmpeg\\ffmpeg.exe';
    private $outputDir = '"C:\\xampp\\htdocs\\ytLoaderPro\\downloads\\download_%(title)s\\';

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

    private function executeCommandPreview(string $command): array
    {
        $output = [];
        $res = "";
        $status = 0;
        try {
            exec("$command 2>&1", $output, $status);
            $res = ScraperService::previewParser($output, true);
            $newname = ScraperService::renameData($res);
            rename($res, $newname);
        } catch (\Exception $e) {
            die("Error: " . $e->getMessage());
        }

        return [
            'success' => $status === 0,
            'status' => $status,
            'output' => $newname,
        ];
    }

    private function executeSimpleCommand(string $command): array
    {
        $output = [];
        $status = 0;

        try {
            exec("$command 2>&1", $output, $status);
        } catch (\Exception $e) {
            die("Error: " . $e->getMessage());
        }

        return $output;
    }

    //download video+audio+subs
    public function download(string $url, array $options = [], string $sub_lang, string $sub_format): array
    {
        $safeUrl = escapeshellarg($url);
        $delPath = "C:\\xampp\\htdocs\\ytLoaderPro\\downloadsMedia\\media__" . md5($url . "\\");
        $command = "$this->ytdlpPath -P " . $delPath . " -f ";
        $lang = "";

        if (!empty($sub_lang)) {
            $lang = substr($sub_lang, 0, -2);
        }

        if (count($options) == 0) {
            $command = $command . " best ";
        } else {
            for ($i = 0; $i < count($options); $i++) {
                $command = $command . "$options[$i]";
                if ($i + 1 < count($options)) {
                    $command = $command . "+";
                }
            }
        }

        $caption_subs_format = "";
        if (substr($sub_lang, -1) == '0') {
            $caption_subs_format = $caption_subs_format . "--write-sub --sub-langs $lang";
        } elseif (substr($sub_lang, -1) == '1') {
            $caption_subs_format = $caption_subs_format . "--write-auto-sub --sub-langs $lang";
        }

        if (!empty($sub_format)) {
            $caption_subs_format = $caption_subs_format . " --sub-format $sub_format";
        }

        $command = $command . " $caption_subs_format" . " $safeUrl";

        $response = $this->executeCommandDownload($command);
        //$include = glob($delPath . '/*');

        $this->download_zip($delPath);
        $this->delDir($delPath);

        return $response;
    }

    private function getName(string $file, string $delPath): string
    {
        $trans = array($delPath => "");
        $with_no_path = strtr($file, $trans);

        $pos_last_point = strripos($with_no_path, ".");
        $name = substr($with_no_path, 0, $pos_last_point) . ".zip";

        //$pos_pre_last_point = strripos($name, ".");
        //$name1 = substr($file, 0, $pos_pre_last_point) . ".zip";

        return $name;
    }

    public function download_zip(string $delPath)
    {
        $files = glob($delPath . '/*');
        $filename = $this->getName($files[0], $delPath);
        $zip = new ZipArchive();
        $zip_file = 'C:\\xampp\\htdocs\\ytLoaderPro\\downloadsMedia\\download.zip';

        if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
            foreach ($files as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();

            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename=' . $filename);
            header('Content-Length: ' . filesize($zip_file));
            readfile($zip_file);

            // Удаляем временный zip-файл после отправки
            unlink($zip_file);
        }
    }


    public function download_zip_2(string $delPath)
    {
        $zip = new ZipArchive();
        $files = glob($delPath . '/*');
        $filePath = public_path("new.zip");
        if ($zip->open($filePath, ZipArchive::CREATE) === TRUE) {
            foreach ($files as $file => $v) {
                $zip->addFile($v, basename($v));
            }
            $zip->close();
        }
        return response()->download($filePath);
    }


    private function DownloadInBrowser(string $file)
    {
        $filename = basename($file);

        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
        } else {
            echo "Файл не найден.";
        }
    }

    public function downloadSeparately(string $url, array $options = []): array
    {
        $safeUrl = escapeshellarg($url);
        $command = "";

        if (key($options) == "video") {
            $delPath = "C:\\xampp\\htdocs\\ytLoaderPro\\downloadsMedia\\media__video__" . md5($_POST['url'] . "\\");
            $command = "$this->ytdlpPath -P " . $delPath . " -f " . $options['video'] . " $safeUrl";
        } elseif (key($options) == "audio") {
            $delPath = "C:\\xampp\\htdocs\\ytLoaderPro\\downloadsMedia\\media__audio__" . md5($_POST['url'] . "\\");
            $command = "$this->ytdlpPath -P " . $delPath . " -f " . $options['audio'] . " $safeUrl";
        } elseif (key($options) == "subs") {
            if (substr($options['subs']['lang'], -1) == '0') {
                $delPath = "C:\\xampp\\htdocs\\ytLoaderPro\\downloadsMedia\\media__subs__" . md5($_POST['url'] . "\\");
                $lang = substr($options['subs']['lang'], 0, -2);
                $command = "$this->ytdlpPath -P $delPath --write-sub --sub-langs $lang --sub-format {$options['subs']['format']} --skip-download $safeUrl";
            } elseif (substr($options['subs']['lang'], -1) == '1') {
                $delPath = "C:\\xampp\\htdocs\\ytLoaderPro\\downloadsMedia\\media__captions__" . md5($_POST['url'] . "\\");
                $lang = substr($options['subs']['lang'], 0, -2);
                $command = "$this->ytdlpPath -P $delPath --write-auto-sub --sub-langs $lang --sub-format {$options['subs']['format']} --skip-download $safeUrl";
            }
        }

        $response = $this->executeCommandDownload($command);
        $include = glob($delPath . '/*');

        $this->DownloadInBrowser($include[0]);
        $this->delDir($delPath);

        return $response;
    }

    /////////////////////////////////
    public function updateYTdlp(): array
    {
        $command = "$this->ytdlpPath -U";
        $response = $this->executeSimpleCommand($command);

        return $response;
    }
    ///////////////////////////////////////

    public function preview(string $url): array
    {
        $previewData = [];

        $safeUrl = escapeshellarg($url);
        $command = "$this->ytdlpPath -o " . "C:\\xampp\\htdocs\\ytLoaderPro\\downloads\\info__" . md5($url) . "\\" . md5($url) . " --write-info-json --skip-download $safeUrl";
        //$deletePath = "C:\\xampp\\htdocs\\ytLoaderPro\\downloads\\info__" . md5($url);
        $response = $this->executeCommandPreview($command);

        $parsFormts = ScraperService::formats_scraper($response['output']);
        $previewData['mediaData'] = $parsFormts;
        $subs = $this->viewSubs($safeUrl);
        $previewData['subs'] = ScraperService::subs_scraper($subs);

        //$this->delDir($deletePath);

        return $previewData;
    }

    public function viewSubs(string $url): array
    {
        $safeUrl = escapeshellarg($url);
        $command = "$this->ytdlpPath --list-subs " . $safeUrl;

        $response = $this->executeSimpleCommand($command);

        return $response;
    }


    public function delDir(string $path): void
    {
        $includes = glob($path . '/*');

        foreach ($includes as $include) {

            if (is_dir($include)) {

                $this->delDir($include);
            } else {

                unlink($include);
            }
        }
        rmdir($path);
    }
}
