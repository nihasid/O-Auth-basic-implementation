<?php

/* *created by Niha Siddiqui 2022-10-05
    * all file uploads defined in this file
*/

namespace App\Helpers;


use App\Models\ExceptionLog;
use Illuminate\Support\Facades\Storage;

class UploadHelper
{
    static function UploadFile( $file, $folder = '', $driver = 'public', $url = false )
    {
        $path = null;

        try {

            if (!$url) {
                $fileContent = $file;
            } else {
                $fileContent = file_get_contents($file);
            }

            $path = Storage::disk($driver)->put($folder, $fileContent, 'public');

        } catch (\Exception $e) {
            ExceptionLog::log( $e, 'file-upload' );
            $path = false;
        } finally {
            return $path;
        }
    }

    static function deleteFile( $fileName, $folder = '', $driver = 'public' )
    {
        try {
            $file = $folder . '/' . $fileName;
            return Storage::disk($driver)->delete( $file );
        } catch (\Exception $e) {
            ExceptionLog::log( $e, 'file-delete' );
            return $file;
        }
    }

}
