<?php

namespace App\Filament\Widgets;

use App\Models\CashFlow;
use App\Models\Sale;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class FinanceOverviewWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // 📅 Inisialisasi Waktu
        $hariIni = Carbon::today();
        $kemarin = Carbon::yesterday();
        
        $awalMingguIni = Carbon::now()->startOfWeek();
        $awalMingguLalu = Carbon::now()->subWeek()->startOfWeek();
        $akhirMingguLalu = Carbon::now()->subWeek()->endOfWeek();
        
        $awalBulanIni = Carbon::now()->startOfMonth();
        $awalBulanLalu = Carbon::now()->subMonth()->startOfMonth();
        $akhirBulanLalu = Carbon::now()->subMonth()->endOfMonth();

        // 📊 1. HITUNG DATA HARI INI VS KEMARIN
        $dataHariIni = $this->getKalkulasiKeuangan($hariIni, Carbon::now()->endOfDay());
        $dataKemarin = $this->getKalkulasiKeuangan($kemarin, $kemarin->copy()->endOfDay());
        $growthHari = $this->hitungGrowth($dataHariIni['profit'], $dataKemarin['profit']);

        // 📊 2. HITUNG DATA MINGGU INI VS MINGGU LALU
        $dataMingguIni = $this->getKalkulasiKeuangan($awalMingguIni, Carbon::now()->endOfDay());
        $dataMingguLalu = $this->getKalkulasiKeuangan($awalMingguLalu, $akhirMingguLalu);
        $growthMinggu = $this->hitungGrowth($dataMingguIni['profit'], $dataMingguLalu['profit']);

        // 📊 3. HITUNG DATA BULAN INI VS BULAN LALU
        $dataBulanIni = $this->getKalkulasiKeuangan($awalBulanIni, Carbon::now()->endOfDay());
        $dataBulanLalu = $this->getKalkulasiKeuangan($awalBulanLalu, $akhirBulanLalu);
        $growthBulan = $this->hitungGrowth($dataBulanIni['profit'], $dataBulanLalu['profit']);

        return [
            // CARD 1: HARI INI
            Stat::make('Profit Bersih Hari Ini', 'Rp ' . number_format($dataHariIni['profit'], 0, ',', '.'))
                ->description($growthHari['teks'] . " dibanding kemarin")
                ->descriptionIcon($growthHari['icon'])
                ->color($growthHari['color'])
                ->extraAttributes([
                    'title' => "Omzet: Rp " . number_format($dataHariIni['omzet'], 0, ',', '.') . " | Pengeluaran/Loss: Rp " . number_format($dataHariIni['expense'], 0, ',', '.')
                ]),

            // CARD 2: MINGGU INI
            Stat::make('Profit Bersih Minggu Ini', 'Rp ' . number_format($dataMingguIni['profit'], 0, ',', '.'))
                ->description($growthMinggu['teks'] . " dibanding minggu lalu")
                ->descriptionIcon($growthMinggu['icon'])
                ->color($growthMinggu['color'])
                ->extraAttributes([
                    'title' => "Omzet: Rp " . number_format($dataMingguIni['omzet'], 0, ',', '.') . " | Pengeluaran/Loss: Rp " . number_format($dataMingguIni['expense'], 0, ',', '.')
                ]),

            // CARD 3: BULAN INI
            Stat::make('Profit Bersih Bulan Ini', 'Rp ' . number_format($dataBulanIni['profit'], 0, ',', '.'))
                ->description($growthBulan['teks'] . " dibanding bulan lalu")
                ->descriptionIcon($growthBulan['icon'])
                ->color($growthBulan['color'])
                ->extraAttributes([
                    'title' => "Omzet: Rp " . number_format($dataBulanIni['omzet'], 0, ',', '.') . " | Pengeluaran/Loss: Rp " . number_format($dataBulanIni['expense'], 0, ',', '.')
                ]),
        ];
    }

    /**
     * Helper Sakti untuk Menghitung Omzet, Modal FIFO, Expense, dan Profit Bersih
     */
    private function getKalkulasiKeuangan($startDate, $endDate): array
    {
        // A. Omzet Kotor (Murni dari nota penjualan kasir normal)
        $omzet = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->where('customer_name', 'not like', 'SYSTEM_LOSS_%')
            ->sum('final_amount');

        // B. Total Modal Beli FIFO (Menggunakan format dot standar tabel.kolom biar gak dikira JSON)
        $totalModalFifo = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->where('sales.customer_name', 'not like', 'SYSTEM_LOSS_%')
            ->sum(DB::raw('sale_items.qty * sale_items.cost_price'));

        // C. Total Pengeluaran Kas + Kerugian Ikan Mati (Dari tabel CashFlows tipe Expense)
        $expense = CashFlow::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('type', 'Expense')
            ->sum('amount');

        // 🧠 RUMUS INTI LABA RUGI BERSIH MOZZA AQUATIC
        $profit = ($omzet - $totalModalFifo) - $expense;

        return [
            'omzet' => $omzet,
            'expense' => $expense,
            'profit' => $profit,
        ];
    }

    /**
     * Helper untuk membuat Indikator Growth Naik / Turun beserta warnanya
     */
    private function hitungGrowth($sekarang, $lalu): array
    {
        if ($lalu == 0) {
            return [
                'teks' => $sekarang >= 0 ? '+100%' : '-100%',
                'icon' => $sekarang >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
                'color' => $sekarang >= 0 ? 'success' : 'danger',
            ];
        }

        $persen = (($sekarang - $lalu) / abs($lalu)) * 100;
        $tanda = $persen >= 0 ? '+' : '';

        return [
            'teks' => $tanda . number_format($persen, 1) . '%',
            'icon' => $persen >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
            'color' => $persen >= 0 ? 'success' : 'danger',
        ];
    }
}
