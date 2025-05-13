<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\Http\Models\Version;
use App\Http\Services\VersionService;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CentralExport;
use App\Http\Services\ExportService;

class VersionController extends Controller
{

    public function __construct(VersionService $versionService, ExportService $exportService)
    {
        $this->versionService = $versionService;
        $this->exportService = $exportService;
    }

    public function getVersion(Request $request)
    {
        $item = (object) [];
        $item = $this->versionService->getVersion(['status' => 1]);
        $code = config('constant.SUCCESS');
        $msg = "Available version";
        return response(array('code' => $code, 'msg' => $msg, 'result' => $item));
    }

    public function getVersionList(Request $request)
    {
        $user = auth()->user();
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPage ? $request->perPage : "5";
        $version_listing =  $this->versionService->versionList($request->search, $sort_by, $order_by, $per_page);
        if ($version_listing) {
            return response(array('code' => config('constant.SUCCESS'), 'msg' => 'Version', 'result' => $version_listing));
        } else {
            return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'Not found', 'result' => $version_listing));
        }
    }

    public function addVersion(Request $request)
    {
        $code = config('constant.UNSUCCESS');
        $validator = Validator::make($request->all(), [
            'ios_version' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (Version::where('ios_version', $value)->count() > 0) {
                        $fail('This Ios Version is already exists.');
                    }
                },
            ],
            'android_version' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (Version::where('android_version', $value)->count() > 0) {
                        $fail('This Android Version is already exists.');
                    }
                },
            ],
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            \DB::beginTransaction();
            try {
                $status = $request->status ? $request->status : "0";
                $data = $request->only('ios_version', 'android_version') + ['status' => $status];
                $version_add =  $this->versionService->storeVersion($data);
                \DB::commit();
                if ($version_add) {
                    $code = config('constant.SUCCESS');
                    return response(array('code' => $code, 'msg' => 'Version added successfully', 'result' => $version_add));
                }
            } catch (\Exception $e) {
                \DB::rollBack();
                return response()->json(['code' => $code, 'msg' => $e->getMessage()]);
            }
        }
    }

    public function editVersion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ios_version' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    if (Version::where('id', '!=', $request->id)->where('ios_version', $value)->count() > 0) {
                        $fail('The Ios Version is already exists.');
                    }
                },
            ],
            'android_version' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    if (Version::where('id', '!=', $request->id)->where('android_version', $value)->count() > 0) {
                        $fail('The Android Version is already exists.');
                    }
                },
            ],
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            \DB::beginTransaction();
            try {
                $status = $request->status ? $request->status : "0";
                $data = $request->only('ios_version', 'android_version') + ['status' => $status];
                $version_updtae =  $this->versionService->updateVersion($request->id, $data);

                \DB::commit();
                if ($version_updtae) {
                    return response(array('code' => config('constant.SUCCESS'), 'msg' => 'Version Updated Successfully', 'result' => $version_updtae));
                }
            } catch (\Exception $e) {
                \DB::rollBack();
                return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $e->getMessage()]);
            }
        }
    }

    public function getVersionById(Request $request)
    {
        $version = $this->versionService->getVersion(['id' => base64_decode($request->id)])->first();
        if ($version) {
            return response(array('code' => config('constant.SUCCESS'), 'msg' => 'Page detail', 'result' => $version));
        } else {
            return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'Something went wrong', 'result' => $version));
        }
    }

    public function deleteVersion(Request $request)
    {
        $version = $this->versionService->getVersion(['id' => base64_decode($request->id)])->first();
        if ($version) {
            $delete = $this->versionService->deleteVersion(['id' => base64_decode($request->id)]);
            if ($delete) {
                return response(array('code' => config('constant.SUCCESS'), 'msg' => 'Version deleted successfully', 'result' => $delete));
            }
            return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'Something went wrong', 'result' => $delete));
        }
    }

    public function versionExport()
    {
        $export_data = $this->exportService->versionsExport();
        return Excel::download(new CentralExport($export_data['data'], $export_data['header']), 'Versions.csv');
    }
}
