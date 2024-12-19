<?php

namespace App\Http\Controllers;

use Validator;
use App\Http\Models\Page;
use Illuminate\Http\Request;
use App\Http\Services\PageService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CentralExport;
use App\Http\Services\ExportService;

class PageController extends Controller
{

    public function __construct(PageService $pageService, ExportService $exportService)
    {
        $this->pageService = $pageService;
        $this->exportService = $exportService;
    }

    public function getContentPage(Request $request)
    {
        $user = auth()->user();
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $page_listing =  $this->pageService->pageList($request->search, $sort_by, $order_by, $per_page);
        if ($page_listing) {
            return response(array('code' => config('constant.SUCCESS'), 'msg' => 'Pages', 'result' => $page_listing));
        } else {
            return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'Not found', 'result' => $page_listing));
        }
    }

    public function addContentPage(Request $request)
    {
        $code = config('constant.UNSUCCESS');
        $validator = Validator::make($request->all(), [
            'page_name' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (Page::where('page_name', $value)->count() > 0) {
                        $fail('This page is already exists.');
                    }
                },
            ],
            'page_title' => 'required',
            'page_content' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            \DB::beginTransaction();
            try {
                $page_name = strtolower(str_replace(' ', '_', $request->page_name));
                $data = $request->only('page_name', 'page_title', 'page_content') + ['slug' => $page_name];
                $page_add =  $this->pageService->storeContentPage($data);
                \DB::commit();
                if ($page_add) {
                    $code = config('constant.SUCCESS');
                    return response(array('code' => $code, 'msg' => 'Page added successfully', 'result' => $page_add));
                }
            } catch (\Exception $e) {
                \DB::rollBack();
                return response()->json(['code' => $code, 'msg' => $e->getMessage()]);
            }
        }
    }

    public function deleteContentPage(Request $request)
    {
        $get_page = $this->pageService->getContentPage(['id' => base64_decode($request->id)]);
        if ($get_page) {
            $delete = $this->pageService->deleteContentPage(['id' => base64_decode($request->id)]);
            if ($delete) {
                return response(array('code' => config('constant.SUCCESS'), 'msg' => 'Page deleted successfully', 'result' => $delete));
            }
            return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'Something went wrong', 'result' => $delete));
        }
    }

    public function getContentPageById(Request $request)
    {
        $content = $this->pageService->getContentPage(['id' => base64_decode($request->id)]);
        if ($content) {
            return response(array('code' => config('constant.SUCCESS'), 'msg' => 'Page detail', 'result' => $content));
        } else {
            return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'Something went wrong', 'result' => $content));
        }
    }


    public function editContentPage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_name' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    if (Page::where('id', '!=', $request->id)->where('page_name', $value)->count() > 0) {
                        $fail('The page name is already exists.');
                    }
                },
            ],
            'page_title' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    if (Page::where('id', '!=', $request->id)->where('page_title', $value)->count() > 0) {
                        $fail('The page title is already exists.');
                    }
                },
            ],
            'page_content' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            \DB::beginTransaction();
            try {
                $page_name = strtolower(str_replace(' ', '_', $request->page_name));
                $data = $request->only('page_name', 'page_title', 'page_content') + ['slug' => $page_name];
                $page_update =  $this->pageService->updateContentPage($request->id, $data);

                \DB::commit();
                if ($page_update) {
                    return response(array('code' => config('constant.SUCCESS'), 'msg' => 'Content Page Updated Successfully', 'result' => $page_update));
                }
            } catch (\Exception $e) {
                \DB::rollBack();
                return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $e->getMessage()]);
            }
        }
    }

    public function conditionPage($slug, $id)
    {
        $page_id = base64_decode($id);
        $page_data = Page::whereRaw('LOWER(page_title) = "' . str_replace('-', ' ', $slug) . '"')->where('id', $page_id)->first();
        return view('content-page.view-page', ['page_data' => $page_data]);
    }

    public function contentPageExport()
    {
        $export_data = $this->exportService->contentPagesExport();
        return Excel::download(new CentralExport($export_data['data'], $export_data['header']), 'Pages.csv');
    }
}
