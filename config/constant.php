<?php
return [
    'UNSUCCESS' => 101,
	'SUCCESS' => 200,
	'ALREADY_REGISTER' => 102,
	'NOTFOUND' => 404,
	'Base_V' => 'http://127.0.0.1:8000/',
	'icon' => 'public/img/',
	'storage_path'  =>  env('APP_IMAGE_URL'),
	'temp_img_path' =>  env('APP_IMAGE_URL') . 'Temp/original',
	'RAZORPAY_API_KEY' => env('RAZORPAY_API_KEY',"rzp_test_xefZhgKfU24o7b"),
	'RAZORPAY_API_SECRET_KEY' => env('RAZORPAY_API_SECRET_KEY', "Ht1kRUVkdl2Py9aup7tkaZQn"),
	'APP_URL' => env('APP_URL') ? env('APP_URL') : 'https://api.sonikmobility.com/',
];