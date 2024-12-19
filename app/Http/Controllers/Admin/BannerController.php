<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Models\Banner;
use Illuminate\Support\Facades\File;
use Validator;
use App\Http\Services\CommonService;

class BannerController extends Controller
{
	public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

	public function getWebBanner(Request $request){
		$code = config('constant.SUCCESS');
        $msg = "Get Banner Successfully";
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;
        $result = Banner::where(function($q) use ($search){
                if ($search != '') {
                    $q->where('name','LIKE', "%{$search}%");
                }
            })->orderBy($sort_by, $order_by)->paginate($per_page);
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
	}

	public function showBanner(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Show Banner Data";
        $result = Banner::where('id', $request->id)->first();
        if ($result) {
            return response(array('code' => $code, 'msg' => $msg, 'result' => $result));
        } else {
            return response(array('code' => $code, 'msg' => $msg, 'result' => $result));
        }
    }

    public function storeOrUpdateBannerWeb(Request $request){
    	$validator = Validator::make($request->all(), [
            'name' => 'required',
            'status' => 'required|boolean'
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
        	if (!blank($request->image) && $request->image != '' && isset($request->image)) {
                $file = $this->commonService->getMovedFile($request->image, 'media/Banner/original/', 'Banner');
            } else {
                $file = null;
            }
        	if (!blank($request->id)) {
        		$code = config('constant.SUCCESS');
                $msg = 'Banner Updated Successfully';
                $check_banner = Banner::where('id', '!=', $request->id)->where('name', $request->name)->first();
                if(!blank($check_banner)){
                	return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'Name Already Exist', 'result' => []));
                }else{
                	$data = [
		        		'name' => $request->name,
		        		'status' => $request->status,
		        		'image' => $file,
		        	];
		        	$update_banner = Banner::where('id',$request->id)->update($data);
		        	return response(array('code' => $code, 'msg' => $msg, 'result' => $update_banner));
                }
        	}else{
        		$code = config('constant.SUCCESS');
	            $msg = 'Banner Created Successfully';
	        	$data = [
	        		'name' => $request->name,
	        		'status' => $request->status,
	        		'image' => $file
	        	];
	        	$create_banner = Banner::create($data);
	        	return response(array('code' => $code, 'msg' => $msg, 'result' => $create_banner));
        	}
        	
        }
    }

    public function deleteBannerImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $this->commonService->deleteImage($request->file, 'Banner');
            $banner = Banner::where('id',$request->id)->first();
            if ($banner) {
                $banner->image = '';
                $banner->save();
            }
            return response()->json(['code' => config('constant.SUCCESS'), 'msg' => 'Banner image deleted successfully', 'deleted' => true]);
        }
    }

    public function deleteBanner(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:banners,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $banner = Banner::where('id',$request->id)->delete();
            return response(array('code' => config('constant.SUCCESS'), 'msg' => 'Banner Deleted Successfully'));
        }
    }
}