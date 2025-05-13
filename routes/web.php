<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VersionController;
use App\Http\Controllers\Admin\ChargerController as AdminChargerController;
use App\Http\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Admin\ChargerTypeController as AdminChargerTypeController;
use App\Http\Controllers\Admin\BatterySizeController as AdminBatterySizeController;
use App\Http\Controllers\WebHookController;
use App\Http\Controllers\PhonePayController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/page/{id}/{slug}', [PageController::class, 'conditionPage']);

// Webhook Route
Route::any('/get-booking-status', [WebHookController::class, 'getWebHookBookingStatus']);

// Export Routes
Route::get('/download-users-data', [UserController::class, 'userExport']);
Route::get('/download-content-page-data', [PageController::class, 'contentPageExport']);
Route::get('/download-versions-data', [VersionController::class, 'versionExport']);
Route::get('/download-public-charger-data', [AdminChargerController::class, 'publicChargerExport']);
Route::get('/download-sonik-charger-data', [AdminChargerController::class, 'sonikChargerExport']);
Route::get('/download-private-charger-data', [AdminChargerController::class, 'privateChargerExport']);
Route::get('/download-vehicle-make-data', [AdminVehicleController::class, 'vehicleMakeExport']);
Route::get('/download-charger-type-data', [AdminChargerTypeController::class, 'chargerTypeExport']);
Route::get('/download-battery-size-data', [AdminBatterySizeController::class, 'batterySizeExport']);
Route::get('/download-vehicle-model-data', [AdminVehicleController::class, 'vehcileModelExport']);
Route::get('/download-under-maintenance-charger-data', [AdminChargerController::class, 'underMaintenanceChargerExport']);
Route::get('/download-unavailable-charger-data', [AdminChargerController::class, 'unAvailableChargerExport']);
Route::get('/download-available-charger-data', [AdminChargerController::class, 'availableChargerExport']);
Route::get('/download-busy-charger-data', [AdminChargerController::class, 'busyChargerExport']);


Route::get('/phone-pay',[PhonePayController::class, 'getWebView']);
Route::get('/phonepay-process',[PhonePayController::class,'phonePayProcess'])->name('payment-process');
Route::any('/phonepay-response',[PhonePayController::class,'phonePayResponse'])->name('phonepay.response');
Route::any('/phonepay-success/{transaction_id}',[PhonePayController::class,'phonePaySuccess'])->name('phonepay.success');
Route::any('/phonepay-failure',[PhonePayController::class,'phonePayFailure'])->name('phonepay.failure');
Route::any('/phonepay/refund',[PhonePayController::class,'phonePayRefund'])->name('phonepay.refund');