<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Accessory;
use App\Models\Transaction;
use App\Models\AccessorySale;
use App\Models\Debt;
use App\Models\DailyOpeningBalance;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->role === 'directeur';

        $todayTransactions = Transaction::whereDate('created_at', today())
            ->where('status', 'validee')
            ->get();

        $todayTransactionsCount = $todayTransactions->count();

        $todayProfitMoncash = $todayTransactions->where('service', 'moncash')->sum('fee_net');
        $todayProfitNatcash = $todayTransactions->where('service', 'natcash')->sum('fee_net');
        $todayProfitUsd = $todayTransactions->where('service', 'zelle')->sum('fee_net');

        $todayAccessorySales = AccessorySale::whereDate('created_at', today())->get();
        $todayAccessoryCount = $todayAccessorySales->count();
        $todayAccessoryRevenue = $todayAccessorySales->sum('total_amount');

        $lowStockItems = Accessory::where('status', 'actif')
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0)
            ->get();

        // CDC : l'employé ne consulte que SES transactions — le widget du
        // dashboard doit respecter la même règle que la page Transactions.
        $recentTransactionsQuery = Transaction::with('creator')->orderBy('created_at', 'desc');
        if (!$isAdmin) {
            $recentTransactionsQuery->where('created_by', $user->id);
        }
        $recentTransactions = $recentTransactionsQuery->limit(7)->get();

        $activeDebts = null;
        $activeDebtsTotal = null;
        $activeDebtsTotalUsd = null;
        $openingBalances = [];
        $theoreticalBalances = [];
        $canEditOpening = [];

        if ($isAdmin) {
            $activeDebts = Debt::where('status', 'active')->count();
            $activeDebtsTotal = Debt::where('status', 'active')
                ->where('currency', 'HTG')
                ->sum('remaining_balance');
            $activeDebtsTotalUsd = Debt::where('status', 'active')
                ->where('currency', 'USD')
                ->sum('remaining_balance');

            foreach (['moncash', 'natcash'] as $service) {
                $opening = DailyOpeningBalance::where('service', $service)
                    ->whereDate('balance_date', today())
                    ->first();

                $openingBalances[$service] = $opening;
                $canEditOpening[$service] = !$opening || $opening->isEditable();

                if ($opening) {
                    $retraits = $todayTransactions->where('service', $service)
                        ->where('movement_type', 'retrait')
                        ->sum('amount');

                    $sorties = $todayTransactions->where('service', $service)
                        ->whereIn('movement_type', Transaction::BALANCE_DECREASE_MOVEMENTS)
                        ->sum('amount');

                    $theoreticalBalances[$service] = (float) $opening->opening_balance + $retraits - $sorties;
                }
            }
        }

        return view('admin.dashboard', compact(
            'todayTransactionsCount',
            'todayProfitMoncash',
            'todayProfitNatcash',
            'todayProfitUsd',
            'todayAccessoryCount',
            'todayAccessoryRevenue',
            'lowStockItems',
            'recentTransactions',
            'activeDebts',
            'activeDebtsTotal',
            'activeDebtsTotalUsd',
            'openingBalances',
            'theoreticalBalances',
            'canEditOpening',
            'isAdmin'
        ));
    }
}
