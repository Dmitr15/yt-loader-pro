<?php

namespace App\Jobs;

use App\Models\PreviewData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class PreviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $scrapId;
    protected $safeUrl;
    protected $delPath;
    protected $ytdlpPath = 'C:\\ffmpeg\\yt-dlp.exe';
    protected $ffmpegPath = 'C:\\ffmpeg\\ffmpeg.exe';

    /**
     * Create a new job instance.
     */
    public function __construct(int $scrapId, string $url)
    {
        $this->safeUrl = escapeshellarg($url);
        $this->delPath = "C:\\xampp\\htdocs\\ytLoaderPro\\downloads\\info__" . md5($url . time());
        $this->scrapId = $scrapId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //find the id of the required record in table
        $preview = PreviewData::find($this->scrapId);
        if (!$preview) {
            Log::error('Preview record not found', ['download_id' => $this->scrapId]);
            return;
        }

        try {
            //update status
            $preview->update(['status' => 'processing']);

            $result = $this->preview($this->safeUrl);

            // if (!isset($result['success'])) {
            //     Log::error('No success', ['video_url' => $this->safeUrl, 'result' => $result, 'preview_id' => $this->scrapId]);
            //     throw new \Exception('Invalid response structure from executeCommandPreview');
            // }

            if ($result['success']) {

                //fill in data in mySQL tables
                $this->fillIn($result, $preview);

                //delete temp directory
                $this->delDir($this->delPath);

                //change status on 'completed'
                $preview->update([
                    'status' => 'completed',
                    'processed_at' => now(),
                ]);
                Log::info('Preview data downloaded successfully', ['video_url' => $this->safeUrl, 'result' => $result]);
            } else {
                Log::error('Preview data downloaded failed', ['video_url' => $this->safeUrl, 'result' => $result, 'preview_id' => $this->scrapId]);
            }
        } catch (\Exception $e) {
            Log::error('Preview downloaded failed', ['video_url' => $this->safeUrl, 'error' => $e->getMessage()]);
            $this->fail($e);
        }
    }


    //fill in db with data
    public function fillIn(array $previewInfo, $preview)
    {
        try {
            $preview->update([
                'title' => $previewInfo['mediaData']['title'] ?? null,
                'thumbnail' => $previewInfo['mediaData']['thumbnail'] ?? null
            ]);

            // Сохраняем видео форматы
            if (!empty($previewInfo['mediaData']['video'])) {
                foreach ($previewInfo['mediaData']['video'] as $videoFormat) {
                    $preview->videoFormats()->create([
                        'format_id' => $videoFormat['id_v'] ?? null,
                        'ext' => $videoFormat['ext'] ?? null,
                        'filesize' => $videoFormat['filesize'] ?? null,
                        'codec' => $videoFormat['codec'] ?? null,
                        'fps' => $videoFormat['fps'] ?? null,
                        'tbr' => $videoFormat['tbr'] ?? null,
                        'vbr' => $videoFormat['vbr'] ?? null,
                        'asr' => $videoFormat['asr'] ?? null,
                        'dynamic_range' => $videoFormat['dynamic_range'] ?? null,
                        'resolution' => $videoFormat['resolution'] ?? null,
                        'format_note' => $videoFormat['format_note'] ?? null,
                    ]);
                }
            }

            // Сохраняем аудио форматы (аналогично видео форматам)
            if (!empty($previewInfo['mediaData']['audio'])) {
                foreach ($previewInfo['mediaData']['audio'] as $audioFormat) {
                    $preview->audioFormats()->create([
                        'format_id' => $audioFormat['id_a'] ?? null,
                        'ext' => $audioFormat['ext'] ?? null,
                        'filesize' => $audioFormat['filesize'] ?? null,
                        'lang' => $audioFormat['lang'] ?? null,
                        'codec' => $audioFormat['codec'] ?? null,
                        'abr' => $audioFormat['abr'] ?? null,
                        'tbr' => $audioFormat['tbr'] ?? null,
                        'asr' => $audioFormat['asr'] ?? null,
                    ]);
                }
            }

            // Сохраняем субтитры
            if (!empty($previewInfo['subs']['subs'])) {
                foreach ($previewInfo['subs']['subs'] as $subs) {
                    $key = array_key_first($subs);
                    $preview->subtitles()->create([
                        'type' => 'subs',
                        'lang_code' => $key,
                        'lang_name' => $subs[$key],
                    ]);
                }
            }

            if (!empty($previewInfo['subs']['captions'])) {
                foreach ($previewInfo['subs']['captions'] as $captions) {
                    $key = array_key_first($captions);
                    $preview->subtitles()->create([
                        'type' => 'captions',
                        'lang_code' => $key,
                        'lang_name' => $captions[$key],
                    ]);
                }
            }

        } catch (\Exception $e) {
            if (isset($preview)) {
                $preview->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }


    public function preview(string $url): array
    {
        $previewData = [];
        $command = "$this->ytdlpPath -o " . $this->delPath . "\\" . md5($url) . " --write-info-json --skip-download $this->safeUrl";
        //execute the download command
        $response = $this->executeCommandPreview($command);

        //parsing data after downloading
        $parsFormts = $this->formats_scraper($response['output']);
        $previewData['mediaData'] = $parsFormts;

        //download subs
        $subs = $this->viewSubs();

        //parse required data from download output
        $previewData['subs'] = $this->subs_scraper($subs);
        $previewData['success'] = $response['success'];

        return $previewData;
    }


    private function executeCommandPreview(string $command): array
    {
        $output = [];
        $res = "";
        $status = 0;
        try {
            exec("$command 2>&1", $output, $status);
            Log::info('After download command', ['output' => $output, 'command' => $command, 'status' => $status]);
            $res = $this->previewParser($output, true);
            $newname = $this->renameData($res);
            rename($res, $newname);
            Log::info('After parsing command', ['success' => $status === 0, 'status' => $status, 'output' => $newname ?? $res]);

            return [
                'success' => $status === 0,
                'status' => $status,
                'output' => $newname ?? $res, // Используем $res, если $newname не определена
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 1,
                'output' => '',
            ];
        }
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

    public function viewSubs(): array
    {
        $command = "$this->ytdlpPath --list-subs " . $this->safeUrl;
        $response = $this->executeSimpleCommand($command);
        return $response;
    }

    public static function formats_scraper(string $pass): array
    {
        $full = [];
        $video = [];
        $audio = [];

        $file_str = file_get_contents($pass);
        $file_json = json_decode($file_str, true);

        $formats = $file_json['formats'];
        $full['title'] = $file_json['title'];
        $full['thumbnail'] = $file_json['thumbnail'];
        $full['duration'] = $file_json['duration_string'];

        foreach ($formats as $format) {
            if ($format["ext"] != "mhtml") {
                //audio
                if ($format["resolution"] == "audio only" && isset($format['filesize'])) {

                    $tmp = [
                        'id_a' => $format['format_id'] ?? null,
                        'ext' => $format['ext'] ?? null,
                        'lang' => trim(strstr($format['format_note'], '-', true)) . $format['language'] ?? null,
                        'filesize' => $format['filesize'] ?? null,
                        'codec' => $format['acodec'] ?? null,
                        'abr' => $format['abr'] ?? null,
                        'tbr' => $format['tbr'] ?? null,
                        'asr' => $format['asr'] ?? null
                    ];
                    $audio[] = $tmp;
                } else if (!isset($format["language"]) && isset($format['format_note'])) {
                    //video
                    $tmp = [
                        'id_v' => $format['format_id'] ?? null,
                        'ext' => $format['ext'] ?? null,
                        'filesize' => $format['filesize'] ?? null,
                        'codec' => $format['vcodec'] ?? null,
                        'fps' => $format['fps'] ?? null,
                        'tbr' => $format['tbr'] ?? null,
                        'vbr' => $format['vbr'] ?? null,
                        'dynamic_range' => $format['dynamic_range'] ?? null,
                        'resolution' => $format['resolution'] ?? null,
                        'format_note' => $format['format_note'] ?? null
                    ];
                    $video[] = $tmp;
                }
            }
        }
        unset($formats);

        $full['video'] = $video;
        unset($video);

        $full['audio'] = $audio;
        unset($audio);

        return $full;
    }

    public static function previewParser(array $output, bool $isDownload): string
    {
        $path = "";
        if ($isDownload) {
            foreach ($output as $str) {
                if (str_contains($str, 'C:\\')) {
                    $path = strstr($str, 'C:\\');
                    break;
                }
            }
        } else {
            foreach ($output as $str) {
                if (str_contains($str, '[Merger] Merging formats into ')) {
                    $path = strstr($str, 'C:\\');
                    break;
                }
            }
        }
        return $path;
    }

    public static function renameData(string $path): string
    {
        $name = strstr($path, "info__");
        $firstPart = stristr($path, $name, true);
        $replaceSymbols = [" " => "_", ")" => "", "(" => "", ".info" => "", "!" => "", "?" => "", "@" => "", "|" => ""];
        $renamed = strtr($name, $replaceSymbols);

        return $firstPart . $renamed;
    }

    public static function strSubsScraper(string $str): array
    {
        $lang_code = "";
        $lang_full = "";

        $code_ended = false;
        $foundSplit = false;
        $full_ended = false;

        $arr = [];
        $res = [];
        $return_arr = [];
        $arr = str_split($str);

        for ($i = 0; $i < count($arr); $i++) {
            if ($arr[$i] != " " && !$code_ended) {
                $lang_code = $lang_code . $arr[$i];
            } else {
                if (!$code_ended) {
                    $code_ended = true;
                }
                if ($foundSplit && !$full_ended) {
                    if (!$full_ended && $arr[$i] == 'v' && $arr[$i + 1] == 't' && $arr[$i + 2] == 't' && $arr[$i + 3] == ',') {
                        $full_ended = true;
                        break;
                    }
                    $res[] = $arr[$i];
                }
                if ($arr[$i] == ' ' && !$foundSplit) {
                    $foundSplit = true;
                }
            }
        }

        unset($arr);
        $lang_full = $lang_full . trim(implode($res));
        unset($res);
        $return_arr["$lang_code"] = $lang_full;

        return $return_arr;
    }

    public static function subs_scraper(array $arrOfSub): array
    {
        $result = [];
        $automatic_captions = [];
        $subtitles = [];
        $firstFound = false;
        $secondFound = false;

        for ($i = 0; $i < count($arrOfSub); $i++) {
            //automatic captions
            if ($firstFound && !str_contains($arrOfSub[$i], "has no subtitles") && !str_contains($arrOfSub[$i], "Available subtitles for") && !$secondFound) {
                $automatic_captions[] = PreviewJob::strSubsScraper($arrOfSub[$i]);
            }
            if (str_contains($arrOfSub[$i], "Language") && str_contains($arrOfSub[$i], "Name") && str_contains($arrOfSub[$i], "Formats") && !$firstFound && !$secondFound) {
                $firstFound = true;
            }

            //subtitles
            if ($secondFound && !$firstFound) {
                if ($i + 1 == count($arrOfSub)) {
                    break;
                }
                $subtitles[] = PreviewJob::strSubsScraper($arrOfSub[$i + 1]);
            }
            if (!$secondFound && str_contains($arrOfSub[$i], "Available subtitles for") && str_contains($arrOfSub[$i + 1], "Language") && str_contains($arrOfSub[$i + 1], "Name") && str_contains($arrOfSub[$i + 1], "Formats")) {
                $secondFound = true;
                $firstFound = false;
            }
        }

        $result['captions'] = $automatic_captions;
        unset($automatic_captions);

        $result['subs'] = $subtitles;
        unset($subtitles);

        return $result;
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
