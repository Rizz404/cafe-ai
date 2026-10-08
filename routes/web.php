<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HandoverController;
use App\Http\Controllers\Admin\KnowledgeItemController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\SeatingAreaController;
use App\Http\Controllers\Api\BaristaChatController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CafePageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CafePageController::class, 'index'])->name('home');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('menu-items', MenuItemController::class)->except('show');
        Route::resource('seating-areas', SeatingAreaController::class)->except('show');
        Route::resource('knowledge-items', KnowledgeItemController::class)->except('show');

        Route::get('reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
        Route::patch('reservations/{reservation}/status', [AdminReservationController::class, 'updateStatus'])->name('reservations.status');

        Route::get('handovers', [HandoverController::class, 'index'])->name('handovers.index');
        Route::get('handovers/{handover}', [HandoverController::class, 'show'])->name('handovers.show');
        Route::post('handovers/{handover}/reply', [HandoverController::class, 'reply'])->name('handovers.reply');
        Route::post('handovers/{handover}/resolve', [HandoverController::class, 'resolve'])->name('handovers.resolve');
    });
});

Route::prefix('{cafeSlug}')->middleware('cafe')->group(function () {
    Route::get('/', [CafePageController::class, 'show'])->name('cafe.show');
    Route::get('menu', [CafePageController::class, 'menu'])->name('cafe.menu');
    Route::get('menu/{itemSlug}', [CafePageController::class, 'menuItem'])->name('cafe.menu-item');
    Route::get('seating', [CafePageController::class, 'seating'])->name('cafe.seating');
    Route::get('seating/{areaSlug}', [CafePageController::class, 'seatingArea'])->name('cafe.seating-area');
    Route::get('facilities', [CafePageController::class, 'facilities'])->name('cafe.facilities');
    Route::get('facilities/{facilityId}', [CafePageController::class, 'facility'])->whereNumber('facilityId')->name('cafe.facility');
    Route::get('info', [CafePageController::class, 'info'])->name('cafe.info');
    Route::get('staff', [CafePageController::class, 'staff'])->name('cafe.staff');
    Route::get('reservation', [CafePageController::class, 'reservationScene'])->name('cafe.reservation');

    Route::prefix('reservation')->name('reservation.')->middleware('throttle:20,1')->group(function () {
        Route::post('quote', [ReservationController::class, 'quote'])->name('quote');
        Route::post('/', [ReservationController::class, 'store'])->name('store');
    });

    Route::prefix('barista')->name('barista.')->group(function () {
        Route::post('start', [BaristaChatController::class, 'start'])->middleware('throttle:barista-start')->name('start');
        Route::post('message', [BaristaChatController::class, 'message'])->middleware('throttle:barista-message')->name('message');
        Route::get('history', [BaristaChatController::class, 'history'])->middleware('throttle:barista-history')->name('history');
    });
});
