<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\CourtController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SportController;
use App\Http\Controllers\Api\VenueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Version 1 API Routes
Route::prefix('v1')->group(function () {

    // Public routes

    // Sports routes
    Route::get('/sports', [SportController::class, 'index']);
    Route::get('/sports/active', [SportController::class, 'active']);
    Route::get('/sports/popular', [SportController::class, 'popular']);
    Route::get('/sports/with-court-count', [SportController::class, 'withCourtCount']);
    Route::get('/sports/search/by-position', [SportController::class, 'searchByPosition']);
    Route::get('/sports/for-player-count', [SportController::class, 'forPlayerCount']);
    Route::get('/sports/{sport}', [SportController::class, 'show']);
    Route::get('/sports/slug/{slug}', [SportController::class, 'bySlug']);
    Route::get('/sports/{sport}/statistics', [SportController::class, 'statistics']);

    // Venues routes
    Route::get('/venues', [VenueController::class, 'index']);
    Route::get('/venues/{venue}', [VenueController::class, 'show']);
    Route::get('/venues/{venue}/availability', [VenueController::class, 'availability']);

    // Courts routes
    Route::get('/courts', [CourtController::class, 'index']);
    Route::get('/courts/popular', [CourtController::class, 'popular']);
    Route::get('/courts/{court}', [CourtController::class, 'show']);
    Route::get('/courts/{court}/availability', [CourtController::class, 'availability']);
    Route::get('/courts/{court}/availability-range', [CourtController::class, 'availabilityRange']);
    Route::post('/courts/{court}/check-availability', [CourtController::class, 'checkAvailability']);
    Route::get('/venues/{venue}/courts', [CourtController::class, 'byVenue']);

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
            Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
            Route::post('/change-password', [AuthController::class, 'changePassword']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    // Protected routes
    Route::middleware('auth:api')->group(function () {

        // Profile management
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::post('/avatar', [ProfileController::class, 'uploadAvatar']);
        });

        // Notification management (Device tokens)
        Route::prefix('notifications')->group(function () {
            Route::post('/register-token', [NotificationController::class, 'registerToken']);
            Route::post('/remove-token', [NotificationController::class, 'removeToken']);
            Route::post('/push-notice', [NotificationController::class, 'sendTestNotification']);
            Route::get('/my-devices', [NotificationController::class, 'getMyDeviceTokens']);

            // Topic-based messaging (khuyến nghị cho SDK 6.9.6)
            Route::post('/broadcast-topic', [NotificationController::class, 'broadcastViaTopic']);
            Route::post('/send-role-topic', [NotificationController::class, 'sendToRoleViaTopic']);
        });

        // User's own venues
        Route::get('/my-venues', [VenueController::class, 'myVenues']);

        // Owner routes (venues & courts management)
        Route::middleware('role:owner|admin')->group(function () {

            // Venues management
            Route::apiResource('venues', VenueController::class)->except(['index', 'show']);
            Route::patch('/venues/{venue}/toggle-status', [VenueController::class, 'toggleStatus']);
            Route::get('/venues/{venue}/statistics', [VenueController::class, 'statistics']);

            // Courts management
            Route::apiResource('courts', CourtController::class)->except(['index', 'show']);
            Route::patch('/courts/{court}/toggle-status', [CourtController::class, 'toggleStatus']);
            Route::get('/courts/{court}/statistics', [CourtController::class, 'statistics']);
        });

        // Admin routes
        // Route::middleware('role:admin')->group(function () {

        //     // Sports management
        //     Route::apiResource('sports', SportController::class)->except(['index', 'show']);
        //     Route::patch('/sports/{sport}/toggle-status', [SportController::class, 'toggleStatus']);

        //     // Venues approval
        //     Route::patch('/venues/{venue}/approve', [VenueController::class, 'approve']);
        //     Route::patch('/venues/{venue}/reject', [VenueController::class, 'reject']);

        //     // Notification management (Admin only)
        //     Route::prefix('notifications')->group(function () {
        //         Route::post('/send-to-users', [NotificationController::class, 'sendToUsers']);
        //         Route::post('/send-to-all', [NotificationController::class, 'sendToAllUsers']);
        //         Route::post('/send-to-role', [NotificationController::class, 'sendToRole']);
        //         Route::get('/list', [NotificationController::class, 'getNotifications']);
        //         Route::get('/stats', [NotificationController::class, 'getStats']);
        //         Route::get('/{id}', [NotificationController::class, 'getNotification']);
        //     });
        // });
    });
});

// // Legacy route for backward compatibility
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


// API for admin panel (separate from main API)
Route::group(['prefix' => 'admin', 'middleware' => ['set.locale']], function () {
    Route::post('/login', [AuthController::class, 'adminLogin']);
    Route::post('forgot-password', [AuthController::class, 'adminForgotPassword']);
    Route::post('reset-password', [AuthController::class, 'adminResetPassword']);
    Route::post('/change-password', [AuthController::class, 'adminChangePassword']);
    Route::post('/refresh-token', [AuthController::class, 'adminRefreshToken']);
});
Route::group(['prefix' => 'admin', 'middleware' => ['auth:api', 'role:admin|owner']], function () {
    Route::group(['prefix' => 'users', 'middleware' => ['role:admin']], function () {
        Route::get('/', [AuthController::class, 'adminGetAllUsers']);
        Route::post('/', [AuthController::class, 'adminCreateUser']);
        Route::put('/{id}', [AuthController::class, 'adminUpdateUser']);
        Route::delete('/{id}', [AuthController::class, 'adminDeleteUser']);
    });
    Route::group(['prefix' => 'sports'], function () {
        Route::get('/', [SportController::class, 'adminGetAllSports']);
        Route::post('/', [SportController::class, 'adminCreateSport']);
        Route::put('/{id}', [SportController::class, 'adminUpdateSport']);
        Route::delete('/{id}', [SportController::class, 'adminDeleteSport']);
        Route::patch('/{sport}/toggle-status', [SportController::class, 'toggleStatus']);
    });

    Route::group(['prefix' => 'notifications'], function () {
        Route::get('/', [NotificationController::class, 'adminGetAllNotifications']);
        Route::get('/{id}', [NotificationController::class, 'adminGetNotificationById']);
        Route::post('/', [NotificationController::class, 'adminCreateNotification']);
        Route::put('/{id}', [NotificationController::class, 'adminUpdateNotification']);
        Route::delete('/{id}', [NotificationController::class, 'adminDeleteNotification']);
        Route::post('/send-to-users', [NotificationController::class, 'sendToUsers']);
        Route::post('/send-to-all', [NotificationController::class, 'sendToAllUsers']);
        Route::post('/send-to-role', [NotificationController::class, 'sendToRole']);
        // Route::get('/list', [NotificationController::class, 'getNotifications']);
        Route::get('/stats', [NotificationController::class, 'getStats']);
        // Route::get('/{id}', [NotificationController::class, 'getNotification']);
    });
});