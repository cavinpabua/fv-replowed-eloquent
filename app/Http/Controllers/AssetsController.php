<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

class AssetsController extends Controller
{

    public function checkAssets(){
        return is_dir(public_path('farmville/assets/hashed/assets'));
    }

    public function downloadAssets(Request $request){        

        $postData = $request->post();

        $url = $postData['url'];
        

        $directory = public_path("tmp");
        $filename = basename(parse_url($url, PHP_URL_PATH));
        $filePath = $directory . '/' . $filename;
        
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0777, true, true);
        }

        if (!isset($postData['totalSize'])) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($ch);
            $fileSize = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            curl_close($ch);

            return response()->json(['totalSize' => $fileSize]);
        }

        $start = $postData['start'];
        $end = $postData['end'];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Range: bytes=$start-$end"
        ]);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 206 || $httpCode == 200) {
            file_put_contents($filePath, $data, FILE_APPEND);
            $downloadedSize = file_exists($filePath) ? filesize($filePath) : 0;
            $progress = ($downloadedSize / $request->totalSize) * 100;

            return response()->json(["status" => "success", "progress" => round($progress, 2)]);

        } else {
            http_response_code(500);
            return response()->json(["error" => "Failed to fetch data"]);
        }
    
    }

    public function getProgress()
    {
        return response()->json(['file_num' => Session::get('file_progress', 0), 'progress' => Session::get('download_progress', 0)]);
    }


    public function extractAssets(Request $request){

        $postData = $request->post();

        $batchSize = $postData['batchSize'];
        $offset = $postData['offset'];

        $files = glob(public_path("tmp") . '/*');

        foreach($files as $key => $file) {

            $warcProcess = 0;
            $totalCount = 0;
            $warc_reader = new WarcController($file);
            while(($record = $warc_reader->nextRecord()) != FALSE){
                $headers = $record['header'];
                $payload = $record['content'];
                
                if (!empty($payload) && array_key_exists('WARC-Target-URI', $headers)) {
                    $totalCount++;
                    if ($totalCount < $offset) continue;
                    if (!str_contains($headers['WARC-Target-URI'], "assets/hashed/assets")) continue;

                    $filename = parse_url($headers['WARC-Target-URI'], PHP_URL_PATH);
                    $filename = ltrim($filename, '/');
                    
                    if (!$filename) {
                        $filename = uniqid('extracted_', true);
                    }

                    $outputDir = public_path();
                    $dirName = dirname($filename);
                    
                    if (!File::exists("$outputDir/$dirName")) {
                        File::makeDirectory("$outputDir/$dirName", 0777, true, true);
                    }
                    
                    if (file_exists("$outputDir/$filename")) continue;
                    file_put_contents("$outputDir/$filename", $payload);
                    $warcProcess++;
                    if ($warcProcess >= $batchSize){
                        return response()->json(["status" => "success", "progress" => $totalCount, "done" => false]);
                    }
                    Session::put('warc_file_progress', $warcProcess);

                }
            }
        }

        return response()->json(["status" => "success", "done" => true]);
    }

    public function extractProgress()
    {
        return response()->json(['file_num' => Session::get('file_progress', 0), 'progress' => Session::get('warc_file_progress', 0), 'finished' => Session::get('warc_file_finished', 0)]);
    }

}