<?php

namespace App\Http\Controllers;

use App\Jobs\DownloadJob;
use App\Jobs\PreviewJob;
use App\Models\Download;
use App\Models\PreviewData;
use Illuminate\Http\Request;
use App\Services\DownloadService;

class DownloadController extends Controller
{
    public $previewData = [];
    public function handleRequest(Request $request, DownloadService $download_service)
    {
        $url = $request->input('url');

        try {
            //preview downloading
            if ($request->has('preview')) {
                // Обработка превью

                if (!empty($url)) {
                    try {
                        //creating a new entry in the database
                        $preview = PreviewData::create(['url' => $url, 'status' => 'pending']);

                        //go to queue
                        PreviewJob::dispatch($preview->id, $url);

                        return redirect()->route('preview.status', ['id' => $preview->id]);
                    } catch (\Exception $e) {
                        return redirect()->route('index')->withErrors(['error' => 'Ошибка при получении данных: ' . $e->getMessage()]);
                    }
                } else {
                    return redirect()->route('index')->withErrors(['url' => 'Paste the url!']);
                }

                //only video downloading
            } elseif ($request->has('download-video')) {
                //code...

                //only audio downloading
            } elseif ($request->has('download-audio')) {
                //code...

                //only subs downloading
            } elseif ($request->has('download-subs')) {
                //code...

                //download all
            } elseif ($request->has('download')) {
                $params = [];
                if (!empty($url)) {
                    try {
                        //creating a new entry in the database
                        $download = Download::create(['url' => $url, 'status' => 'pending']);

                        if (!empty($request->get('video-formats'))) {
                            $params[] = $request->get('video-formats');
                        }
                        if (!empty($request->get('audio-formats'))) {
                            $params[] = $request->get('audio-formats');
                        }

                        $delPath = storage_path('app/public/media__' . md5($url . time()) . "\\");

                        //go to queue
                        DownloadJob::dispatch($download->id, $url, $params, $delPath, $request->get('subs-lang') ?? '', $request->get('subs-formats') ?? '');

                        return redirect()->route('download.status', ['id' => $download->id]);
                    } catch (\Exception $e) {
                        return redirect()->route('index')->withErrors(['error' => 'Ошибка при получении данных: ' . $e->getMessage()]);
                    }
                } else {
                    return redirect()->route('index')->withErrors(['url' => 'Paste the url!']);
                }
            }
        } catch (\Exception $e) {
            return redirect()->route('index')->withErrors(['error' => 'Error: ' . $e->getMessage()])->withInput();
        }
        return redirect()->route('index');
    }


    //return view for checking status of download
    public function downloadStatus($id)
    {
        $download = Download::findOrFail($id);
        return view('download-status', compact('download'));
    }


    //download file if status in 'completed'
    public function downloadFile($id)
    {
        $download = Download::findOrFail($id);

        if ($download->status !== 'completed') {
            abort(404, 'File not ready');
        }

        $filePath = storage_path('app/' . $download->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath)->deleteFileAfterSend(true);
    }


    //Checking download status in 5 sec
    public function checkStatus($id)
    {
        $download = Download::findOrFail($id);
        return response()->json([
            'status' => $download->status,
            'file_url' => $download->status === 'completed' ? route('download.file', ['id' => $download->id]) : null
        ]);
    }


    //redirect to download preview page
    public function previewStatus($id)
    {
        $previewData = PreviewData::findOrFail($id);
        return view('waitIndex', compact('previewData'));
    }


    //check preview downloading status
    public function checkPreviewStatus($id)
    {
        $previewRequest = PreviewData::findOrFail($id);
        return response()->json([
            'status' => $previewRequest->status,
            'preview_data' => $previewRequest->status === 'completed' ? $previewRequest->preview_data : null
        ]);
    }


    //parse and return data to index page
    public function returnPreview($id)
    {
        $previewData = PreviewData::with(['videoFormats', 'audioFormats', 'subtitles'])->find($id);
        if (!$previewData) {
            return redirect()->route('index')->withErrors('Preview data not found');
        }

        // Преобразуем в массив для избежания проблем с сериализацией
        $previewDataForSession = [
            'id' => $previewData->id,
            'url' => $previewData->url,
            'status' => $previewData->status,
            'title' => $previewData->title,
            'thumbnail' => $previewData->thumbnail,
            'videoFormats' => $previewData->videoFormats->toArray(),
            'audioFormats' => $previewData->audioFormats->toArray(),
            'subtitles' => $previewData->subtitles->toArray(),
            'created_at' => $previewData->created_at,
            'updated_at' => $previewData->updated_at,
        ];

        return redirect()->route('index')->with('previewData', $previewDataForSession);
    }
}
