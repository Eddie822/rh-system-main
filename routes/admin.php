<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CoverController;
use App\Http\Controllers\Admin\FamilyController;
use App\Http\Controllers\Admin\MaterialOrderController;
use App\Http\Controllers\Admin\OrdersController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductionController;
use App\Http\Controllers\Admin\RawMaterialController;
use App\Http\Controllers\Admin\RecipeController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('admin.dashboard');
})->name('dashboard');

Route::resource('families', FamilyController::class);
Route::resource('categories', CategoryController::class);
Route::resource('subcategories', SubcategoryController::class);
Route::resource('products', ProductController::class);
Route::resource('covers', CoverController::class);

Route::post('orders/{order}/status', [OrdersController::class, 'updateStatus'])->name('orders.updateStatus');
Route::resource('orders', OrdersController::class)->only(['index', 'show', 'update', 'destroy']);
Route::resource('users', UserController::class);

// GESTIÓN DE SUPPLY CHAIN (NUEVOS MÓDULOS)
// 1. Proveedores
Route::resource('suppliers', SupplierController::class);

// 2. Almacén / Materia Prima
Route::resource('raw_materials', RawMaterialController::class);

// 3. Pedidos de Materia Prima
Route::resource('material_orders', MaterialOrderController::class);

// AGREGADO: Ruta POST personalizada para la recepción manual del pedido
Route::post('material_orders/{order}/receive', [MaterialOrderController::class, 'receive'])
    ->name('material_orders.receive');


// Producción
Route::resource('productions', ProductionController::class);

Route::post('productions/{production}/complete', [ProductionController::class, 'complete'])
    ->name('productions.complete');
// Recetas
Route::resource('recipes', RecipeController::class);
