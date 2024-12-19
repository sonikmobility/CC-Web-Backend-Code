<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Models\Banner;
use App\Http\Models\Charger;
use Validator;
use Illuminate\Support\Facades\File;


class BannerController extends Controller
{
    public function getBanner(){
        $user_exist = auth('sanctum')->user();
        $get_banner = [];
        $success = true;
        if(!blank($user_exist)){
            $get_banner = Banner::where('status',1)->get();
            $code = config('constant.SUCCESS');
            $msg = "Get banner Successfully";
        }else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg, 'result'=> $get_banner]);
    }

    public function addBanner(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'image' => 'required',
            'status' => 'required|boolean'
        ]);
        $code = config('constant.UNSUCCESS');
        $msg = "Not Found";
        $create_banner = [];
        $success = false;
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $user_exist = auth('sanctum')->user();
            if (!blank($user_exist)) {
                $user_id = auth('sanctum')->user()->id;
                $name = $request->name;
                $status = $request->status ? $request->status : 0;
                if ($request->hasFile('image')) {
                    $success = true;
                    $code = config('constant.SUCCESS');
                    $msg = "Banner Created Successfully";
                    $image = $request->file('image');
                    $file = $this->uploadImage($image, 'Banner');
                    $create_banner = Banner::create(['name'=>$name,'image'=>$file,'status'=>$status]);
                }
            } else {
                $code = config('constant.UNSUCCESS');
                $msg = "User Not Found";
            }
            return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg, 'result'=> $create_banner]);
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

        return $filename;  
    }
}
