<?php

namespace App\Http\Services;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File as Files;
use Illuminate\Support\Facades\File;
use App\Http\Models\Setting;

class CommonService
{   

    public function uploadImage_old($width = 0, $height = 0, $file, $folder, $prefix)
    {
        $originalPath = public_path($folder.'original/');
        $thumbnailPath = public_path($folder.'thumb/');

        if (!file_exists($originalPath)){
            $oldmask = umask(0);
            mkdir($originalPath,  0777, true);
            umask($oldmask);
        }
        if (!file_exists($thumbnailPath)){
            $oldmask = umask(0);
            mkdir($thumbnailPath,  0777, true);
            umask($oldmask);
        }

        if ($file != '') {
            $extension = $file->getClientOriginalExtension();
            $imageName = $prefix . '_' . time() . rand(0000, 9999)  . '.' . $file->getClientOriginalExtension();
            
            if ($height && $width) {
                $img = Image::make($file);
                $img->resize($height, $width)->save($thumbnailPath.'/'.$imageName);
                
            }
            $file->move($originalPath, $imageName);

            return $imageName;
        }
    }

    public function uploadImage($image,$folder) {
        $fileContent = file_get_contents($image);
        $createfilename = $folder.'_'.time().rand(0000, 9999);
        $extension = $image->getClientOriginalExtension();
        $filename  = $createfilename.'.'.$extension;


        if(!File::exists(public_path('media/'.$folder.'//original/'))){
            $path = base_path('media/' . $folder . '/original/');
            File::makeDirectory($path, 0777, true, true);
        }

        $image->move(public_path('media/'.$folder.'//original/'), $filename);
        $path = public_path("media/Temp/original/".$filename);

        // $img = Image::make($image);
        // $img->resize(300, 300)->save($thumbnailPath.'/'.$imageName);

        return $filename;  
    }

    public function uploadImageInLocal($image, $folder, $filename = "")
    {
        $fileContent = file_get_contents($image);
        $createfilename = $folder . '_' . time() . rand(0000, 9999);
        $extension = $image->getClientOriginalExtension();
        $filename = $createfilename . '.' . $extension;
        $image->move(public_path("media/Temp/original"), $filename);

        return $filename;
    }

    public function getSecureCode()
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars), 0, 20);
    }

    public function removeImage($path, $image)
    {
        $image_path = public_path($path.'original/'.$image);
        $image_thum_path = public_path($path.'thumb/'.$image);
        
        if(File::exists($image_path) || File::exists($image_thum_path)) 
        { 
            File::delete($image_path);
            File::delete($image_thum_path);
            return true;
        }
        return false;
    }

    public function deleteImage($filename, $folder, $is_original = '')
    {
        $filepath = $folder . '/original/' . $filename;
        $thum_path = $folder . '/thumb/' . $filename;

        if ($is_original == true) {
            if(File::exists($filepath) || File::exists($thum_path)) 
            { 
                File::delete($filepath);
                File::delete($thum_path);
                return true;
            }
        } else {
            if (File::exists(public_path("media/{$folder}/original/{$filename}"))) {
                return File::delete(public_path("media/{$folder}/original/{$filename}"));
            }
        }
        return false;
    }

    public function getMovedFile($file,$path,$folder) {
        $filename = $this->getFileName($file,$folder);
        $tmp_path = public_path('media/Temp/original/'.$file);
       
        info('File::exists',[File::exists($tmp_path)]);
        if(File::exists($tmp_path)){
            if(!File::exists(public_path($path))){
                File::makeDirectory(public_path($path),0777,true);
            }
            $filemoved = File::move($tmp_path,public_path($path.$filename));

            info($filemoved,[File::exists($tmp_path)]);
        }

        return $filename;
    }

    public function getFileName($file, $folder)
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $createfilename = str_replace(' ', '_', $folder) . '_' . time() . rand(0000, 9999);
        $filename = $createfilename . '.' . $extension;
        return $filename;
    }

    public function getSettingValue($entity){
        $get_value = Setting::where('name',$entity)->first();
        if(!blank($get_value)){
            return $get_value->updated_value;
        }
    }
}