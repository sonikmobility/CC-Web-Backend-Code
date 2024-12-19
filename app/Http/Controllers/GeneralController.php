<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\CommonService;

class GeneralController extends Controller
{
	public function __construct(CommonService $commonService)
	{
		$this->commonService = $commonService;
	}

	public function addGeneralData(Request $request)
	{
		if ($request->image != '' && isset($request->image)) {
			$file = $this->commonService->getMovedFile($request->image, 'media/General/original/', 'General');
			$data = $request->only('email', 'name', 'password', 'single_select', 'multi_select') + ['image' => $file];
			return $data;
		}
	}
}
