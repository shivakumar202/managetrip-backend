<?php

use App\Http\Controllers\Admin\Activity\ActivityPricingUploadController;
use App\Http\Controllers\Admin\Ferry\FerryController;
use App\Http\Controllers\Admin\Ferry\FerryPricesController;
use App\Http\Controllers\Admin\Hotel\HotelPricesController;
use App\Http\Controllers\Admin\Transport\TransportController;
use App\Http\Controllers\Admin\TravelActivitiesController;
use App\Http\Controllers\Admin\Updates\UpdateController;
use App\Http\Controllers\FlightsController;
use App\Http\Controllers\QutationController;
use App\Http\Controllers\SpecialServicesController;
use App\Http\Controllers\Trips\TripsController;
use App\Http\Controllers\Quotes\QuoteSectionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HotelsController;
use App\Http\Controllers\QueryController;
use App\Http\Controllers\TripController;
use Symfony\Component\Mailer\Transport;

/**
 * =====================================================
 * Public Auth Routes (No authentication required)
 * =====================================================
 */
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

/**
 * =====================================================
 * Public Query Routes (No authentication required for creating queries)
 * =====================================================
 */



/**
 * =====================================================
 * Protected Routes (Requires Sanctum authentication)
 * =====================================================
 */
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth endpoints
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    // Protected query routes
    Route::put('/queries/{id}', [QueryController::class, 'update']);
    Route::post('/queries', [QueryController::class, 'store']);
    Route::get('/qutations', [QutationController::class, 'index']);


    Route::get('/transport-service-prices', [TransportController::class, 'index']);
    Route::post('/transport-service-prices', [TransportController::class, 'store']);

    Route::get('/transport-services', [TransportController::class, 'transportServices']);


    Route::get('/travel-activities', [TravelActivitiesController::class, 'index']);

    Route::get('/travel-activity-prices', [ActivityPricingUploadController::class, 'index']);
    Route::post('/travel-activity-prices', [ActivityPricingUploadController::class, 'store']);


    Route::get('/ferry-services', [FerryController::class, 'index']);
    Route::get('/ferry-services-prices', [FerryPricesController::class, 'index']);
    Route::post('/ferry-service-prices', [FerryPricesController::class, 'store']);


    // travel hotel routes
    Route::get('/hotels', [HotelsController::class, 'index']);
    Route::get('/hotel-groups', [HotelsController::class, 'hotelGroups']);
    Route::get('/hotel-meal-plans', [HotelsController::class, 'hotelMealPlans']);
    Route::get('/hotel-room-types', [HotelsController::class, 'hotelRoomTypes']);
    Route::get('/hotel-prices', [HotelPricesController::class, 'index']);
    Route::post('/hotel-prices', [HotelPricesController::class, 'store']);
    Route::get('/hotel-prices/download', [HotelPricesController::class, 'downloadPricing']);

    // special services routes
    Route::get('/special-services', [SpecialServicesController::class, 'index']);
    Route::post('/special-services', [SpecialServicesController::class, 'store']);


    // Trips Controller routes
    Route::get('/trips', [TripsController::class, 'index']);
    Route::get('/trip/hotel', [TripsController::class, 'getHotel']);
    Route::get('/trip/{id}', [TripsController::class, 'show']);
    Route::post('/trip/{id}/save-quote', [TripsController::class, 'saveQuote']);
    Route::post('/quotes', [TripsController::class, 'saveQuoteFromWizard']);
    Route::get('/trip/hotel/{id}', [TripsController::class, 'getHotelDetails']);
    Route::post('/hotel-price', [TripsController::class, 'getHotelPrices']);
    Route::get('/trip-service-location', [TripsController::class, 'getServiceLocation']);
    Route::get('/trip-services', [TripsController::class, 'getService']);
    Route::get('/trip-vehicle-types', [TripsController::class, 'getVehicleTypes']);
    Route::get('/trip-service-pricing', [TripsController::class, 'getActivityPrice']);
    Route::get('/trip-activities-locations', [TripsController::class, 'getTripActivityLocations']);
    Route::get('/trip-activities', [TripsController::class, 'getTripActivities']);
    Route::get('/trip-ferry-routes', [TripsController::class, 'getTripFerryRoutes']);
    Route::get('/trip-ferry-services', [TripsController::class, 'getTripFerryServices']);
    Route::get('/trip-ferry-pricing', [TripsController::class, 'getTripFerryPricing']);
    Route::get('/trip-activity-pricing', [TripsController::class, 'getTripActivityPricing']);
    Route::get('/trip-special-services', [TripsController::class, 'getTripSpecialServices']);




    // Modular Quote Workflow Routes
    Route::post('/quotes/draft/{tripId}', [QuoteSectionController::class, 'createDraftQuote']);
    Route::get('/quotes/{quoteId}', [QuoteSectionController::class, 'getQuote']);
    Route::post('/quotes/{quoteId}/hotels', [QuoteSectionController::class, 'saveHotels']);
    Route::post('/quotes/{quoteId}/transports', [QuoteSectionController::class, 'saveTransportActivities']);
    Route::post('/quotes/{quoteId}/flights', [QuoteSectionController::class, 'saveFlights']);
    Route::post('/quotes/{quoteId}/special-services', [QuoteSectionController::class, 'saveSpecialServices']);
    Route::post('/quotes/{quoteId}/pricing', [QuoteSectionController::class, 'savePricing']);
    Route::post('/quotes/{quoteId}/finalize', [QuoteSectionController::class, 'finalizeQuote']);
    Route::get('/quotes/{quoteId}/progress', [QuoteSectionController::class, 'getQuoteProgress']);
    Route::get('/quotes/{quoteId}/summary', [QuoteSectionController::class, 'getQuoteSummary']);

    Route::get('/quote/{tripId}/latest', [QuoteSectionController::class, 'getLatestQuote']);
    Route::get('/quote/{tripId}/quotes/{quotecode?}', [QuoteSectionController::class, 'getAllQuotesForTrip']);
    Route::get('/quote/{tripId}/quote/{quotecode}/accommodations', [QuoteSectionController::class, 'getAllQuotesForTrip']);
    Route::get('/quote/{tripId}/quote-suggestions/{quotecode?}', [QuoteSectionController::class, 'getQuoteSuggestions']);
    Route::get('/quote/{quoteCode}', [QuoteSectionController::class, 'draftgetQuote']);


    Route::get('/quote/{tripId}/hotels/{quotecode}', [TripController::class, 'getQuoteHotels']);
    Route::get('/quote/{tripId}/accounting/{quotecode}', [TripController::class, 'getQuoteAccounting']);
    Route::get('/quote/{tripId}/activities/{quotecode}', [TripController::class, 'getQuoteActivities']);
    Route::get('/quote/{tripId}/transports/{quotecode}', [TripController::class, 'getQuoteTransports']);
    Route::get('/quote/{tripId}/ferries/{quotecode}', [TripController::class, 'getQuoteFerries']);

    // Add more protected routes here

    Route::get('/updates', [UpdateController::class, 'index']);
    Route::post('/update/new', [UpdateController::class, 'store']);
    Route::post('/updates/{id}/comment', [UpdateController::class, 'addComment']);

    // Admin user/role/permission management
    Route::get('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'index']);
    Route::get('/users/{id}', [\App\Http\Controllers\Admin\UserManagementController::class, 'show']);
    Route::post('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'store']);
    Route::put('/users/{id}', [\App\Http\Controllers\Admin\UserManagementController::class, 'update']);
    Route::delete('/users/{id}', [\App\Http\Controllers\Admin\UserManagementController::class, 'destroy']);
    Route::post('/users/{id}/roles', [\App\Http\Controllers\Admin\UserManagementController::class, 'assignRoles']);
    Route::delete('/users/{id}/roles/{roleId}', [\App\Http\Controllers\Admin\UserManagementController::class, 'removeRole']);

    Route::get('/roles', [\App\Http\Controllers\Admin\RoleManagementController::class, 'index']);
    Route::get('/roles/{id}', [\App\Http\Controllers\Admin\RoleManagementController::class, 'show']);
    Route::post('/roles', [\App\Http\Controllers\Admin\RoleManagementController::class, 'store']);
    Route::put('/roles/{id}', [\App\Http\Controllers\Admin\RoleManagementController::class, 'update']);
    Route::delete('/roles/{id}', [\App\Http\Controllers\Admin\RoleManagementController::class, 'destroy']);
    Route::post('/roles/{id}/permissions', [\App\Http\Controllers\Admin\RoleManagementController::class, 'assignPermissions']);
    Route::delete('/roles/{id}/permissions/{permissionId}', [\App\Http\Controllers\Admin\RoleManagementController::class, 'removePermission']);

    Route::get('/permissions', [\App\Http\Controllers\Admin\PermissionController::class, 'index']);
    Route::post('/permissions', [\App\Http\Controllers\Admin\PermissionController::class, 'store']);
    Route::put('/permissions/{id}', [\App\Http\Controllers\Admin\PermissionController::class, 'update']);
    Route::delete('/permissions/{id}', [\App\Http\Controllers\Admin\PermissionController::class, 'destroy']);



    Route::prefix('flights')->group(function () {
        Route::get('/search', [FlightsController::class, 'search']);
        Route::get('/airports', [FlightsController::class, 'airports']);
        Route::get('/calendar', [FlightsController::class, 'calendar']);
        Route::get('/details', [FlightsController::class, 'details']);
    });
});
