<?php

namespace App\Services;

class ScraperService
{

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
                    //&& $format['filesize'] != null
                    $tmp = [
                        'id_a' => $format['format_id'] ?? null,
                        'ext' => $format['ext']  ?? null,
                        'lang' => trim(strstr($format['format_note'], '-', true)).$format['language']  ?? null,
                        //'lang' => $format['language']  ?? null,
                        'filesize' => $format['filesize']?? null,
                        'codec' => $format['acodec']?? null,
                        'abr' => $format['abr']  ?? null,
                        'tbr' => $format['tbr']  ?? null,
                        'asr' => $format['asr']  ?? null
                    ];
                    $audio[] = $tmp;
                } else if (!isset($format["language"])  && isset($format['format_note'])) {
                    //&& $format["format_note"] != null
                    //video
                    $tmp = [
                        'id_v' => $format['format_id']?? null,
                        'ext' => $format['ext']?? null,
                        'filesize' => $format['filesize']?? null,
                        'codec' => $format['vcodec']?? null,
                        'fps' => $format['fps']?? null,
                        'tbr' => $format['tbr']?? null,
                        'vbr' => $format['vbr']?? null,
                        'dynamic_range' => $format['dynamic_range']?? null,
                        'resolution' => $format['resolution']?? null,
                        'format_note' => $format['format_note']?? null
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

    private static function strSubsScraper(string $str): array
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
                $automatic_captions[] = ScraperService::strSubsScraper($arrOfSub[$i]);
            }
            if (str_contains($arrOfSub[$i], "Language") && str_contains($arrOfSub[$i], "Name") && str_contains($arrOfSub[$i], "Formats") && !$firstFound && !$secondFound) {
                $firstFound = true;
            }

            //subtitles
            if ($secondFound && !$firstFound) {
                if ($i + 1 == count($arrOfSub)) {
                    break;
                }
                $subtitles[] = ScraperService::strSubsScraper($arrOfSub[$i + 1]);
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
}
