<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\CashFlow;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\DB;

class FinancialReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Laporan Keuangan';
    protected static ?string $title = '📊 Laporan Laba Rugi Detail';

    protected string $view = 'filament.pages.financial-report';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Kita kembalikan ke filter tanggal basic yang aman sentosa
                DatePicker::make('dari_tanggal')->label('Dari Tanggal')->live(),
                DatePicker::make('sampai_tanggal')->label('Sampai Tanggal')->live(),
            ])
            ->columns(2)
            ->statePath('data');
    }

    // Fungsi Helper biar codingan query gak diulang-ulang
    private function getKalkulasiPeriode($start, $end)
    {
        $omzet = Sale::whereBetween('created_at', [$start, $end])->where('customer_name', 'not like', 'SYSTEM_LOSS_%')->sum('final_amount');
        $hpp = DB::table('sale_items')->join('sales', 'sale_items.sale_id', '=', 'sales.id')->whereBetween('sales.created_at', [$start, $end])->where('sales.customer_name', 'not like', 'SYSTEM_LOSS_%')->sum(DB::raw('sale_items.qty * sale_items.cost_price'));
        $expense = CashFlow::whereBetween('transaction_date', [$start, $end])->where('type', 'Expense')->sum('amount');

        if ($omzet == 0 && $hpp == 0 && $expense == 0) return null; // Skip kalau kosong

        return [
            'omzet' => $omzet,
            'hpp' => $hpp,
            'expense' => $expense,
            'laba' => $omzet - $hpp - $expense,
        ];
    }

    protected function getViewData(): array
    {
        // 🌟 1. RIWAYAT MINGGUAN (8 Minggu Terakhir)
        $riwayatMingguan = [];
        for ($i = 0; $i < 8; $i++) {
            $date = Carbon::now()->subWeeks($i);
            $start = $date->copy()->startOfWeek();
            $end = $date->copy()->endOfWeek();
            $data = $this->getKalkulasiPeriode($start, $end);
            if ($data) {
                $data['periode'] = $start->format('d M') . ' - ' . $end->format('d M Y');
                $riwayatMingguan[] = $data;
            }
        }

        // 🌟 2. RIWAYAT BULANAN (12 Bulan Terakhir)
        $riwayatBulanan = [];
        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::now()->subMonths($i);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();
            $data = $this->getKalkulasiPeriode($start, $end);
            if ($data) {
                $data['periode'] = $date->translatedFormat('F Y');
                $riwayatBulanan[] = $data;
            }
        }

        // 🌟 3. RIWAYAT TAHUNAN (3 Tahun Terakhir)
        $riwayatTahunan = [];
        for ($i = 0; $i < 3; $i++) {
            $date = Carbon::now()->subYears($i);
            $start = $date->copy()->startOfYear();
            $end = $date->copy()->endOfYear();
            $data = $this->getKalkulasiPeriode($start, $end);
            if ($data) {
                $data['periode'] = $date->format('Y');
                $riwayatTahunan[] = $data;
            }
        }

        // 🌟 4. PENCARIAN KUSTOM
        $kustom = null;
        if (!empty($this->data['dari_tanggal']) && !empty($this->data['sampai_tanggal'])) {
            $startKustom = Carbon::parse($this->data['dari_tanggal'])->startOfDay();
            $endKustom = Carbon::parse($this->data['sampai_tanggal'])->endOfDay();
            $kustom = $this->getKalkulasiPeriode($startKustom, $endKustom);
        }

        return [
            'riwayatMingguan' => $riwayatMingguan,
            'riwayatBulanan' => $riwayatBulanan,
            'riwayatTahunan' => $riwayatTahunan,
            'kustom' => $kustom,
        ];
    }
}