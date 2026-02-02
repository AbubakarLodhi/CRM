<?php

use App\Http\Controllers\Invoice\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// routes/web.php



Route::get('/invoices/{type}/{id}', [InvoiceController::class, 'show'])
    ->name('invoices.show');

