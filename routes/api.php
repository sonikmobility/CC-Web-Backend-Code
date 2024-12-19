<?php


use App\Http\Controllers\HydraController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\VersionController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\ChargerController;
use App\Http\Controllers\User\ChargerController as ApiChargerController;
use App\Http\Controllers\MakeModelController;
// Booking Controller
use App\Http\Controllers\User\BookingController as UserBookingController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\BannerController as AdminBannerController;
// Charging History Controller
use App\Http\Controllers\User\ChargingHistoryController as UserChargingHistoryController;
use App\Http\Controllers\Admin\ChargingHistoryController as AdminChargingHistoryController;
use App\Http\Controllers\User\UserController as ApiUserController;
use App\Http\Controllers\User\VehicleController as ApiVehicleController;
use App\Http\Controllers\User\BatterySizeController as ApiBatterySizeController;
use App\Http\Controllers\User\ChargerTypeController as ApiChargerTypeController;
use App\Http\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Admin\ChargerController as AdminChargerController;
use App\Http\Controllers\Admin\ChargerTypeController as AdminChargerTypeController;
use App\Http\Controllers\Admin\BatterySizeController as AdminBatterySizeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\User\BannerController;
use App\Http\Controllers\User\UserReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ChargerWalletController;
use App\Http\Controllers\WalletWithdrawalController;
use App\Http\Controllers\User\AnalyticsController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WebHookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//use the middleware 'hydra.log' with any request to get the detailed headers, request parameters and response logged in logs/laravel.log

Route::get('info1', function () {
    phpinfo();
});

//Webhook Routes:


Route::get('hydra', [HydraController::class, 'hydra']);
Route::get('hydra/version', [HydraController::class, 'version']);

Route::post('users', [UserController::class, 'store']);
Route::post('login', [UserController::class, 'login']);
// Route::post('login-mobile-number', [UserController::class, 'loginWithMobileNumber']);
Route::post('login-mobile-number', [ApiUserController::class, 'loginWithMobileNumber']);
Route::post('update-user-info', [ApiUserController::class, 'updateUserInfo']);

Route::post('social/login', [UserController::class, 'socialLogin']);
// //Google
// Route::get('/login/google', [App\Http\Controllers\Auth\LoginController::class, 'redirectToGoogle'])->name('login.google');
// Route::get('/login/google/callback', [App\Http\Controllers\Auth\LoginController::class, 'handleGoogleCallback']);
// //Facebook
// Route::get('/login/facebook', [App\Http\Controllers\Auth\LoginController::class, 'redirectToFacebook'])->name('login.facebook');
// Route::get('/login/facebook/callback', [App\Http\Controllers\Auth\LoginController::class, 'handleFacebookCallback']);

Route::get('version', [VersionController::class, 'getVersion']);
Route::post('forgot_password', [ResetPasswordController::class, 'forgotPassword']);
Route::any('resetPassword/{access_code}', [ResetPasswordController::class, 'resetPassword']);

Route::post('removeTmpImage', [UserController::class, 'removeTmpImage']);
Route::any('uploadTmpImage', [UserController::class, 'uploadTmpImage']);

Route::post('store-general-data', [GeneralController::class, 'addGeneralData']);
Route::post('logout', [UserController::class, 'logout']);
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('me', [UserController::class, 'me']);

    Route::post('change_password', [UserController::class, 'changePassword']);
});

Route::group(['middleware' => 'auth:sanctum', 'ability:admin,super-admin,user'], function () {
    Route::post('add', [UserController::class, 'add']);
    Route::post('update', [UserController::class, 'update']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::patch('users/{user}', [UserController::class, 'update']);
    Route::apiResource('roles', RoleController::class)->except(['create', 'edit']);
});

// Setting Route : User    
Route::post('get-setting-for-user', [SettingController::class, 'getSettingForUser']);

Route::group(['middleware' => 'auth:sanctum', 'ability:admin,super-admin'], function () {

    Route::post('get-user-details', [ApiUserController::class, 'getUserDetails']);

    Route::post('getUserList', [UserController::class, 'getUserList']);
    Route::post('getAllUser', [UserController::class, 'getAllUser']);
    Route::post('getUser', [UserController::class, 'getUser']);
    Route::post('delete-user', [UserController::class, 'deleteUser']);
    Route::post('delete-user-image', [UserController::class, 'deleteUserImage']);
    Route::post('delete-admin-image', [UserController::class, 'deleteAdminImage']);
    Route::post('block-user-by-id', [UserController::class, 'blockUserById']);
    Route::post('unblock-user-by-id', [UserController::class, 'unblockUserById']);

    Route::post('get-content-page', [PageController::class, 'getContentPage']);
    Route::post('add-content-page', [PageController::class, 'addContentPage']);
    Route::post('delete-content-page', [PageController::class, 'deleteContentPage']);
    Route::post('get-content-page-by-id', [PageController::class, 'getContentPageById']);
    Route::post('edit-content-page', [PageController::class, 'editContentPage']);

    Route::post('get-version-list', [VersionController::class, 'getVersionList']);
    Route::post('add-version', [VersionController::class, 'addVersion']);
    Route::post('delete-version', [VersionController::class, 'deleteVersion']);
    Route::post('get-version-by-id', [VersionController::class, 'getVersionById']);
    Route::post('edit-version', [VersionController::class, 'editVersion']);

    // Charger Route
    Route::post('add-charger', [ApiChargerController::class, 'addCharger']);
    Route::post('get-api-charger-id', [ChargerController::class, 'getChargerById']);
    Route::post('get-charger-by-id', [ChargerController::class, 'getCharger']);
    Route::post('get-near-by-charger', [ChargerController::class, 'getNearByCharger']);
    Route::get('get-all-charger', [ChargerController::class, 'getAllCharger']);

    // Use In Web
    Route::post('get-charger-by-user-id', [ChargerController::class, 'getChargerByUserId']);
    // Use In Api
    Route::post('my-charger', [ApiChargerController::class, 'getChargerByUserId']);
    Route::post('manage-my-charger', [ApiChargerController::class, 'manageMyCharger']);
    Route::post('get-charger-detail', [ApiChargerController::class, 'getChargerDetail']);

    Route::post('update-charger', [ChargerController::class, 'updateCharger']);
    Route::post('delete-charger', [ChargerController::class, 'deleteCharger']);
    Route::post('delete-qr-code-image', [ChargerController::class, 'deleteQrCodeImage']);

    // Vehicle Route
    Route::post('add-vehicle', [MakeModelController::class, 'addVehicle']);
    Route::post('show-user-vehicle', [MakeModelController::class, 'showUserVehicle']);
    // Also send make_id to perticuler make & model data Or with make_id to get All Data.
    Route::post('get-model-make-data', [MakeModelController::class, 'getModelMakeData']);

    // Create Make Vehicle
    Route::post('add-make-vehicle', [ApiVehicleController::class, 'addMakeVehicle']);
    Route::get('get-make-vehicle', [ApiVehicleController::class, 'getMakeVehicle']);

    // Create Model Vehicle
    Route::post('add-model-vehicle', [ApiVehicleController::class, 'addModelVehicle']);
    Route::post('get-model-vehicle', [ApiVehicleController::class, 'getModelVehicle']);
    Route::post('my-vehicle', [ApiVehicleController::class, 'myVehicle']);
    Route::post('filter-my-vehicle-list', [ApiVehicleController::class, 'filterMyVehicleList']);
    Route::post('get-all-model-vehicle', [ApiVehicleController::class, 'getAllModelVehicle']);

    // Add User Vehicle
    Route::post('add-user-vehicle', [ApiVehicleController::class, 'AddUserVehicle']);

    // Admin - Vehicle Route
    Route::post('get-user-vehicle', [AdminVehicleController::class, 'getUserVehicle']);
    Route::post('get-all-vehicle-make', [AdminVehicleController::class, 'getAllVehicleMake']);
    Route::post('get-all-vehicle-model', [AdminVehicleController::class, 'getAllVehicleModel']);
    Route::post('show-vehicle-make', [AdminVehicleController::class, 'showVehicleMake']);
    Route::post('add-vehicle-make', [AdminVehicleController::class, 'addVehicleMake']);
    Route::post('update-vehicle-make', [AdminVehicleController::class, 'updateVehicleMake']);
    Route::post('delete-vehicle-make', [AdminVehicleController::class, 'deleteVehicleMake']);
    Route::post('get-make-vehicles', [AdminVehicleController::class, 'getMake']);
    Route::post('get-charger-types', [AdminVehicleController::class, 'getTypes']);
    Route::post('get-battery-sizes', [AdminVehicleController::class, 'getSizes']);
    Route::post('show-vehicle-model', [AdminVehicleController::class, 'showVehicleModel']);
    Route::post('delete-vehicle-model', [AdminVehicleController::class, 'deleteVehicleModel']);
    Route::post('update-vehicle-model', [AdminVehicleController::class, 'updateVehicleModel']);
    Route::post('add-vehicle-model', [AdminVehicleController::class, 'addVehicleModel']);
    Route::post('add-or-update-user-vehicle', [AdminVehicleController::class, 'AddOrUpdateUserVehicle']);
    Route::post('show-user-vehicle', [AdminVehicleController::class, 'showUserVechile']);
    Route::post('delete-user-vehicle', [AdminVehicleController::class, 'deleteUserVehicle']);

    // Charger Management Route - Admin
    Route::post('get-users', [AdminChargerController::class, 'getUsers']);
    Route::post('get-all-public-charger', [AdminChargerController::class, 'getAllPublicCharger']);
    Route::post('get-all-private-charger', [AdminChargerController::class, 'getAllPrivateCharger']);
    Route::post('show-public-charger', [AdminChargerController::class, 'showPublicCharger']);
    Route::post('delete-admin-charger', [AdminChargerController::class, 'deleteCharger']);
    Route::post('add-or-update-charger', [AdminChargerController::class, 'addOrUpdateCharger']);
    Route::post('get-all-chargers-data', [AdminChargerController::class, 'getAllChargersData']);

    // Booking Route - User
    Route::post('store-booking', [UserBookingController::class, 'storeBooking'])->name('store.booking');
    Route::post('booking-availability', [UserBookingController::class, 'bookingAvailability']);
    Route::post('get-my-booking', [UserBookingController::class, 'getMyBookings']);
    Route::post('varified-booking', [UserBookingController::class, 'varifiedBooking']);
    Route::post('my-booking-history', [UserBookingController::class, 'myBookingHistory']);

    // Skipped Route
    Route::post('is-skipped', [ApiVehicleController::class, 'isSkipped']);

    // Booking Route - Admin
    Route::post('get-booking', [AdminBookingController::class, 'getBooking']);
    Route::post('get-upcoming-booking', [AdminBookingController::class, 'getUpcomingBooking']);
    Route::post('get-completed-booking', [AdminBookingController::class, 'getCompletedBooking']);
    Route::post('get-cancel-booking', [AdminBookingController::class, 'getCancelBooking']);
    Route::post('my-bookings', [AdminBookingController::class, 'myBookings']);
    //Route::post('update-upcoming-booking-status', [AdminBookingController::class, 'updateUpcomingBookingStatus']);
    Route::post('update-upcoming-booking-status', [AdminBookingController::class, 'updateUpcomingBookingStatusClone']);
    Route::post('get-booking-info', [AdminBookingController::class, 'getBookingInfo']);

    // Charging History Route - Admin
    Route::post('get-charging-history', [AdminChargingHistoryController::class, 'getChargingHistory']);
    Route::get('get-history-all-charger', [AdminChargingHistoryController::class, 'getHistoryAllCharger']);

    // Charging History Route - User
    Route::post('store-start-charging-history', [UserChargingHistoryController::class, 'storeChargingStartHistory']);
    Route::post('update-stop-charging-history', [UserChargingHistoryController::class, 'storeChargingStopHistory']);
    //Route::post('my-charging-history', [UserChargingHistoryController::class, 'myChargingHistory']);

    // Charger Status Route
    Route::post('get-busy-charger', [ChargerController::class, 'getBusyCharger']);
    Route::post('get-available-charger', [ChargerController::class, 'getAvailableCharger']);
    Route::post('get-unavailable-charger', [ChargerController::class, 'getUnAvailableCharger']);
    Route::post('get-under-maintence-charger', [ChargerController::class, 'getUnderMaintenanceCharger']);

    // Payment Route - User
    Route::post('generate-final-order-id', [UserBookingController::class, 'generateFinalOrderId']);
    //Route::post('payment-success', [UserBookingController::class, 'paymentSuccess']);
    Route::post('payment-success', [UserBookingController::class, 'paymentSuccessNew'])->name('payment.success');
    Route::post('refund-user-amount', [UserBookingController::class, 'refundUserAmount']);
    //Route::post('update-pre-payment', [UserBookingController::class, 'updatePrePaymentStatus']);
    Route::post('update-pre-payment', [UserBookingController::class, 'updatePrePaymentStatusNew'])->name('update.pre.payment.status');

    // Wallet Routes
    Route::post('add-wallet-amount', [WalletWithdrawalController::class, 'addWalletAmount'])->name('add.wallet.amount');

    // Payment Status Route - Admin
    Route::post('get-pre-auth-payment', [AdminBookingController::class, 'getPreAuthPayment']);
    Route::post('get-completed-payment', [AdminBookingController::class, 'getCompletedPayment']);

    // Reports - Admin
    Route::post('get-charger-report', [ReportController::class, 'getChargerReport']);
    Route::post('get-month-wise-booking', [ReportController::class, 'getMonthwiseBooking']);
    Route::post('get-month-wise-earning', [ReportController::class, 'getMonthwiseEarning']);
    Route::post('get-user-report', [ReportController::class, 'getUserReport']);
    Route::post('get-month-wise-user-booking', [ReportController::class, 'getMonthwiseUserBooking']);
    Route::post('get-month-wise-user-earning', [ReportController::class, 'getMonthwiseUserEarning']);

    // Reports - User
    Route::post('my-analytics', [AnalyticsController::class, 'myAnalytics']);
    Route::post('my-charger-analytics', [AnalyticsController::class, 'myChargerAnalytics']);

    // Dashboard - Admin
    Route::post('get-charger-count', [DashboardController::class, 'getChargerCount']);
    Route::post('get-data-count', [DashboardController::class, 'getDataCount']);
    Route::post('get-booking-count', [DashboardController::class, 'getBookingCount']);
    Route::post('get-user-count', [DashboardController::class, 'getUserCount']);

    // Charger Wallet Route - User
    Route::post('get-wallet', [ChargerWalletController::class, 'getWallet']);

    // Wallet Withdrawal Request Route - User
    Route::post('add-wallet-withdrawal-request',[WalletWithdrawalController::class, 'addWalletWithdrawalRequest']);
    Route::post('get-user-withdrawal-request',[WalletWithdrawalController::class, 'addUserWithdrawalRequest']);

    // Wallet Withdrawal Request Route - Admin
    Route::post('get-pending-withdrawal-request',[WalletWithdrawalController::class, 'getPendingRequest']);
    Route::post('withdrawal-status-change', [WalletWithdrawalController::class, 'withdrawalStatusChange']);
    Route::post('get-approved-withdrawal-request',[WalletWithdrawalController::class, 'getApprovedRequest']);
    Route::post('get-cancelled-withdrawal-request',[WalletWithdrawalController::class, 'getDeclinedRequest']);
    
    Route::get('get-wallet-transactions',[WalletWithdrawalController::class, 'getAllTransactions']);

    // Settings Api Route
    Route::post('get-settings', [SettingController::class, 'getSettings']);

    
    // User Report Route
    Route::post('store-user-report', [UserReportController::class, 'store']);
    Route::post('get-all-user-report', [UserReportController::class, 'index']);
    Route::post('get-all-report-title', [UserReportController::class, 'getTitle']);
    Route::post('get-all-report-reason', [UserReportController::class, 'getReason']);
    Route::post('add-report-title', [UserReportController::class, 'addReportTitle']);
    Route::post('show-report-title', [UserReportController::class, 'showReportTitle']);
    Route::post('update-report-title', [UserReportController::class, 'updateReportTitle']);
    Route::post('delete-report-title', [UserReportController::class, 'deleteReportTitle']);

    // Banner Route
    Route::post('get-banner', [BannerController::class, 'getBanner']);
    Route::post('get-web-banner', [AdminBannerController::class, 'getWebBanner']);
    Route::post('show-banner', [AdminBannerController::class, 'showBanner']);
    Route::post('store-or-update-benner-web', [AdminBannerController::class, 'storeOrUpdateBannerWeb']);
    Route::post('delete-banner-image', [AdminBannerController::class, 'deleteBannerImage']);
    Route::post('delete-banner', [AdminBannerController::class, 'deleteBanner']);
    Route::post('store-banner',[BannerController::class, 'addBanner']);

    // Delete Account Route
    Route::post('delete-account', [ApiUserController::class, 'deleteAccount']);

    // Charger Type Route : Admin
    Route::post('get-charger-type', [AdminChargerTypeController::class, 'getAllChargerType']);
    Route::post('show-charger-type', [AdminChargerTypeController::class, 'showChargerType']);
    Route::post('add-charger-type', [AdminChargerTypeController::class, 'addChargerType']);
    Route::post('update-charger-type', [AdminChargerTypeController::class, 'updateChargerType']);
    Route::post('delete-charger-type', [AdminChargerTypeController::class, 'deleteChargerType']);
    Route::post('get-charger-type-list', [AdminChargerTypeController::class, 'getChargerTypeList']);

    // Charging Type Route : API
    Route::post('get-charging-type-data', [ApiChargerTypeController::class, 'getCharingType']);

    // Battery Size Route : Admin
    Route::post('get-battery-size', [AdminBatterySizeController::class, 'getAllBatterySize']);
    Route::post('show-battery-size', [AdminBatterySizeController::class, 'showBatterySize']);
    Route::post('add-battery-size', [AdminBatterySizeController::class, 'addBatterySize']);
    Route::post('update-battery-size', [AdminBatterySizeController::class, 'updateBatterySize']);
    Route::post('delete-battery-size', [AdminBatterySizeController::class, 'deleteBatterySize']);

    // Battery Size Route : API
    Route::post('get-battery-size-data', [ApiBatterySizeController::class, 'getBatterySize']);

    Route::apiResource('users', UserController::class)->except(['edit', 'create', 'store', 'update'])->middleware(['auth:sanctum', 'ability:admin,super-admin']);
    Route::apiResource('users.roles', UserRoleController::class)->except(['create', 'edit', 'show', 'update'])->middleware(['auth:sanctum', 'ability:admin,super-admin']);

    Route::post('phonepe/intent-details', [TransactionController::class, 'getIntentDetails']);
    Route::post('phonepe/check-transaction-status', [TransactionController::class, 'checkTransactionStatus']);
});

Route::post('phonepe/handle-callback', [TransactionController::class, 'phonePeCallBack']);

//Route::apiResource('users', UserController::class)->except(['edit', 'create', 'store', 'update'])->middleware(['auth:sanctum', 'ability:admin,super-admin']);
//Route::put('users/{user}', [UserController::class, 'update'])->middleware(['auth:sanctum', 'ability:admin,super-admin,user']);
//Route::post('update', [UserController::class, 'update'])->middleware(['auth:sanctum', 'ability:admin,super-admin,user']);
//Route::patch('users/{user}', [UserController::class, 'update'])->middleware(['auth:sanctum', 'ability:admin,super-admin,user']);

//Route::apiResource('roles', RoleController::class)->except(['create', 'edit'])->middleware(['auth:sanctum', 'ability:admin,super-admin,user']);
//Route::apiResource('users.roles', UserRoleController::class)->except(['create', 'edit', 'show', 'update'])->middleware(['auth:sanctum', 'ability:admin,super-admin']);
