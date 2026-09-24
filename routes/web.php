<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CashMovementController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InstitutionController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\OperationController;
use App\Http\Controllers\Admin\PayoutRequestController;
use App\Http\Controllers\Admin\PlacementController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SyncController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WhatsappOutboxController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Member\LoginController as MemberLoginController;
use App\Http\Controllers\Member\PortalController;
use App\Http\Controllers\Member\ReportController as MemberReportController;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['fr', 'sw'], true), 404);
    session(['locale' => $locale]);

    if ($user = auth()->user()) {
        $user->update(['locale' => $locale]);
    } elseif ($member = auth('member')->user()) {
        $member->update(['locale' => $locale]);
    }

    return redirect()->back();
})->name('locale.switch');

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('admin.dashboard');
    }
    if (auth('member')->check()) {
        return redirect()->route('member.dashboard');
    }

    return view('home');
});

Route::get('/manifest.webmanifest', function () {
    return response(file_get_contents(public_path('manifest.webmanifest')), 200, [
        'Content-Type' => 'application/manifest+json',
    ]);
})->name('pwa.manifest');

Route::get('/sw.js', function () {
    return response(file_get_contents(public_path('sw.js')), 200, [
        'Content-Type' => 'application/javascript',
        'Service-Worker-Allowed' => '/',
        'Cache-Control' => 'no-cache',
    ]);
})->name('pwa.sw');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth:web')->name('logout');

Route::middleware('guest:member')->prefix('membre')->name('member.')->group(function () {
    Route::get('/login', [MemberLoginController::class, 'create'])->name('login');
    Route::post('/login', [MemberLoginController::class, 'store']);
});

Route::post('/membre/logout', [MemberLoginController::class, 'destroy'])->middleware('auth:member')->name('member.logout');

Route::middleware('auth:member')->prefix('membre')->name('member.')->group(function () {
    Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/profil', [PortalController::class, 'profile'])->name('profile');
    Route::put('/profil', [PortalController::class, 'updateProfile'])->name('profile.update');
    Route::get('/reseau', [PortalController::class, 'network'])->name('network');
    Route::get('/historique', [PortalController::class, 'history'])->name('history');
    Route::get('/achats', [PortalController::class, 'purchases'])->name('purchases');
    Route::get('/alertes', [PortalController::class, 'alerts'])->name('alerts');
    Route::post('/retrait', [PortalController::class, 'requestPayout'])->name('payout.request');
    Route::get('/rapports', [MemberReportController::class, 'index'])->name('reports.index');
    Route::get('/rapports/{type}/print', [MemberReportController::class, 'printA4'])->name('reports.print');
    Route::get('/rapports/{type}', [MemberReportController::class, 'show'])->name('reports.show');
});

Route::middleware('auth:web')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::middleware('admin')->group(function () {
        Route::get('/institution', [InstitutionController::class, 'edit'])->name('institution.edit');
        Route::put('/institution', [InstitutionController::class, 'update'])->name('institution.update');
        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('/branches/create', [BranchController::class, 'create'])->name('branches.create');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::post('/branches/{branch}/codes', [BranchController::class, 'generateCodes'])->name('branches.codes.generate');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::get('/sync', [SyncController::class, 'index'])->name('sync.index');
        Route::post('/sync/run', [SyncController::class, 'run'])->name('sync.run');
        Route::get('/plan', [PlanController::class, 'edit'])->name('plan.edit');
        Route::put('/plan/settings', [PlanController::class, 'updateSettings'])->name('plan.settings');
        Route::post('/plan/tiers', [PlanController::class, 'storeTier'])->name('plan.tiers.store');
        Route::delete('/plan/tiers/{tier}', [PlanController::class, 'destroyTier'])->name('plan.tiers.destroy');
        Route::post('/plan/rules', [PlanController::class, 'storeRule'])->name('plan.rules.store');
        Route::delete('/plan/rules/{rule}', [PlanController::class, 'destroyRule'])->name('plan.rules.destroy');
    });

    // Admin (toutes) + responsable (sa succursale) — autorisation via BranchPolicy
    Route::get('/branches/{branch}', [BranchController::class, 'show'])->name('branches.show');

    Route::middleware('staff.branch')->group(function () {
        Route::get('/members', [MemberController::class, 'index'])->name('members.index');
        Route::get('/members/tree', [MemberController::class, 'tree'])->name('members.tree');
        Route::get('/members/create', [MemberController::class, 'create'])->name('members.create');
        Route::post('/members', [MemberController::class, 'store'])->name('members.store');
        Route::get('/members/{member}', [MemberController::class, 'show'])->name('members.show');
        Route::get('/members/{member}/edit', [MemberController::class, 'edit'])->name('members.edit');
        Route::put('/members/{member}', [MemberController::class, 'update'])->name('members.update');
        Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
        Route::get('/placements', [PlacementController::class, 'index'])->name('placements.index');
        Route::post('/placements/{member}', [PlacementController::class, 'store'])->name('placements.store');
        Route::get('/members/{member}/print', [MemberController::class, 'printCard'])->name('members.print');
        Route::post('/members/{member}/whatsapp', [MemberController::class, 'whatsapp'])->name('members.whatsapp');

        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
        Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
        Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('/sales/{sale}/edit', [SaleController::class, 'edit'])->name('sales.edit');
        Route::put('/sales/{sale}', [SaleController::class, 'update'])->name('sales.update');
        Route::delete('/sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');
        Route::get('/sales/{sale}/print-58', [SaleController::class, 'print58'])->name('sales.print58');
        Route::get('/sales/{sale}/print-80', [SaleController::class, 'print80'])->name('sales.print80');
        Route::get('/sales/{sale}/facture-58', [SaleController::class, 'printInvoice58'])->name('sales.invoice58');
        Route::get('/sales/{sale}/facture-80', [SaleController::class, 'printInvoice80'])->name('sales.invoice80');
        Route::get('/sales/{sale}/recu-58', [SaleController::class, 'printReceipt58'])->name('sales.receipt58');
        Route::get('/sales/{sale}/recu-80', [SaleController::class, 'printReceipt80'])->name('sales.receipt80');
        Route::post('/sales/{sale}/whatsapp', [SaleController::class, 'whatsapp'])->name('sales.whatsapp');

        Route::get('/operations', [OperationController::class, 'index'])->name('operations.index');
        Route::get('/operations/create', [OperationController::class, 'create'])->name('operations.create');
        Route::post('/operations', [OperationController::class, 'store'])->name('operations.store');
        Route::post('/operations/types', [OperationController::class, 'storeType'])->name('operations.types.store');
        Route::get('/operations/{movement}/edit', [OperationController::class, 'edit'])->name('operations.edit');
        Route::put('/operations/{movement}', [OperationController::class, 'update'])->name('operations.update');
        Route::delete('/operations/{movement}', [OperationController::class, 'destroy'])->name('operations.destroy');

        Route::get('/cash', [CashMovementController::class, 'index'])->name('cash.index');
        Route::get('/cash/create', [CashMovementController::class, 'create'])->name('cash.create');
        Route::post('/cash', [CashMovementController::class, 'store'])->name('cash.store');
        Route::get('/cash/{movement}/print-58', [CashMovementController::class, 'print58'])->name('cash.print58');
        Route::get('/cash/{movement}/print-80', [CashMovementController::class, 'print80'])->name('cash.print80');
        Route::get('/cash/{movement}/edit', [CashMovementController::class, 'edit'])->name('cash.edit');
        Route::put('/cash/{movement}', [CashMovementController::class, 'update'])->name('cash.update');
        Route::delete('/cash/{movement}', [CashMovementController::class, 'destroy'])->name('cash.destroy');

        Route::get('/whatsapp', [WhatsappOutboxController::class, 'index'])->name('whatsapp.index');
        Route::post('/whatsapp/{outbox}/open', [WhatsappOutboxController::class, 'open'])->name('whatsapp.open');

        Route::get('/commissions', [CommissionController::class, 'index'])->name('commissions.index');
        Route::post('/commissions/{commission}/pay', [CommissionController::class, 'pay'])->name('commissions.pay');
        Route::delete('/commissions/{commission}', [CommissionController::class, 'destroy'])->name('commissions.destroy');

        Route::get('/payouts', [PayoutRequestController::class, 'index'])->name('payouts.index');
        Route::post('/payouts/{payout}/pay', [PayoutRequestController::class, 'pay'])->name('payouts.pay');
        Route::post('/payouts/{payout}/reject', [PayoutRequestController::class, 'reject'])->name('payouts.reject');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/print', [ReportController::class, 'printLegacy'])->name('reports.print.legacy');
        Route::get('/reports/{type}/print', [ReportController::class, 'printA4'])->name('reports.print');
        Route::get('/reports/{type}', [ReportController::class, 'show'])->name('reports.show');
    });
});
