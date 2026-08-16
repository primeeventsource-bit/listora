<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ListingWizardController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/browse', [ListingController::class, 'index'])->name('listings.index');
Route::get('/listing/{listing}', [ListingController::class, 'show'])->name('listings.show');
Route::post('/listing/{listing}/inquire', [InquiryController::class, 'store'])->name('inquiries.store');

Route::get('/list-your-property', [ListingWizardController::class, 'create'])->name('list.create');
Route::post('/list-your-property', [ListingWizardController::class, 'store'])->name('list.store');

Route::get('/how-it-works', [PageController::class, 'how'])->name('how');
Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
Route::get('/apps', [PageController::class, 'apps'])->name('apps');
Route::get('/about', [PageController::class, 'about'])->name('about');
