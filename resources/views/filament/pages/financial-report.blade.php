<x-filament-panels::page>

    <x-filament::section icon="heroicon-o-table-cells" heading="📊 Riwayat Laba Rugi Bulanan (Overview Utama)">
        @if(count($riwayatBulanan) > 0)
            <div style="overflow-x: auto; margin-top: -10px;">
                <table style="width: 100%; border-collapse: collapse; white-space: nowrap; font-size: 0.875rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(156,163,175,0.2);">
                            <th style="padding: 14px 16px; text-align: left; opacity: 0.7; font-weight: 600;">Periode Bulan</th>
                            <th style="padding: 14px 16px; text-align: right; opacity: 0.7; font-weight: 600;">Omzet Kotor</th>
                            <th style="padding: 14px 16px; text-align: right; opacity: 0.7; font-weight: 600;">Modal Lapak (FIFO)</th>
                            <th style="padding: 14px 16px; text-align: right; opacity: 0.7; font-weight: 600;">Beban / Loss</th>
                            <th style="padding: 14px 16px; text-align: right; opacity: 0.7; font-weight: 600;">Laba Bersih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($riwayatBulanan as $row)
                            <tr style="border-bottom: 1px solid rgba(156,163,175,0.1);">
                                <td style="padding: 14px 16px; font-weight: bold;">{{ $row['periode'] }}</td>
                                <td style="padding: 14px 16px; text-align: right; color: #10b981;">Rp {{ number_format($row['omzet'], 0, ',', '.') }}</td>
                                <td style="padding: 14px 16px; text-align: right; color: #ef4444;">- Rp {{ number_format($row['hpp'], 0, ',', '.') }}</td>
                                <td style="padding: 14px 16px; text-align: right; color: #f97316;">- Rp {{ number_format($row['expense'], 0, ',', '.') }}</td>
                                <td style="padding: 14px 16px; text-align: right; font-weight: 900; color: {{ $row['laba'] >= 0 ? '#10b981' : '#ef4444' }};">
                                    Rp {{ number_format($row['laba'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="text-align: center; padding: 24px 0; opacity: 0.6; font-style: italic;">Belum ada data transaksi bulanan.</div>
        @endif
    </x-filament::section>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; margin-top: 8px;">
        
        <x-filament::section icon="heroicon-o-calendar" heading="📅 Riwayat Taktis Mingguan">
            @if(count($riwayatMingguan) > 0)
                <div style="overflow-x: auto; margin-top: -10px;">
                    <table style="width: 100%; border-collapse: collapse; white-space: nowrap; font-size: 0.85rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(156,163,175,0.2);">
                                <th style="padding: 12px; text-align: left; opacity: 0.7; font-weight: 600;">Minggu</th>
                                <th style="padding: 12px; text-align: right; opacity: 0.7; font-weight: 600;">Omzet</th>
                                <th style="padding: 12px; text-align: right; opacity: 0.7; font-weight: 600;">Laba Bersih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($riwayatMingguan as $row)
                                <tr style="border-bottom: 1px solid rgba(156,163,175,0.1);">
                                    <td style="padding: 12px; font-weight: bold; font-size: 0.8rem;">{{ $row['periode'] }}</td>
                                    <td style="padding: 12px; text-align: right; color: #10b981;">Rp {{ number_format($row['omzet'], 0, ',', '.') }}</td>
                                    <td style="padding: 12px; text-align: right; font-weight: 800; color: {{ $row['laba'] >= 0 ? '#10b981' : '#ef4444' }};">
                                        Rp {{ number_format($row['laba'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align: center; padding: 24px 0; opacity: 0.6; font-style: italic;">Belum ada data mingguan.</div>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-presentation-chart-bar" heading="📈 Riwayat Makro Tahunan">
            @if(count($riwayatTahunan) > 0)
                <div style="overflow-x: auto; margin-top: -10px;">
                    <table style="width: 100%; border-collapse: collapse; white-space: nowrap; font-size: 0.85rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(156,163,175,0.2);">
                                <th style="padding: 12px; text-align: left; opacity: 0.7; font-weight: 600;">Tahun</th>
                                <th style="padding: 12px; text-align: right; opacity: 0.7; font-weight: 600;">Total Omzet</th>
                                <th style="padding: 12px; text-align: right; opacity: 0.7; font-weight: 600;">Laba Bersih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($riwayatTahunan as $row)
                                <tr style="border-bottom: 1px solid rgba(156,163,175,0.1);">
                                    <td style="padding: 12px; font-weight: bold; font-size: 0.9rem;">{{ $row['periode'] }}</td>
                                    <td style="padding: 12px; text-align: right; color: #10b981;">Rp {{ number_format($row['omzet'], 0, ',', '.') }}</td>
                                    <td style="padding: 12px; text-align: right; font-weight: 800; color: {{ $row['laba'] >= 0 ? '#10b981' : '#ef4444' }};">
                                        Rp {{ number_format($row['laba'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align: center; padding: 24px 0; opacity: 0.6; font-style: italic;">Belum ada data tahunan.</div>
            @endif
        </x-filament::section>

    </div>

    <x-filament::section icon="heroicon-o-magnifying-glass" heading="Cari Rentang Waktu Spesifik" collapsed>
        <form wire:submit.prevent="submit" style="margin-bottom: 16px;">
            {{ $this->form }}
        </form>

        @if($kustom)
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 24px;">
                <div style="padding: 16px; border-radius: 12px; border: 1px solid rgba(156,163,175,0.2);">
                    <p style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold; opacity: 0.6;">Total Omzet</p>
                    <p style="font-size: 1.25rem; font-weight: bold; margin-top: 8px;">Rp {{ number_format($kustom['omzet'], 0, ',', '.') }}</p>
                </div>
                <div style="padding: 16px; border-radius: 12px; border: 1px solid rgba(156,163,175,0.2);">
                    <p style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold; opacity: 0.6;">Modal Barang (FIFO)</p>
                    <p style="font-size: 1.25rem; font-weight: bold; margin-top: 8px; color: #ef4444;">Rp {{ number_format($kustom['hpp'], 0, ',', '.') }}</p>
                </div>
                <div style="padding: 16px; border-radius: 12px; border: 1px solid rgba(156,163,175,0.2);">
                    <p style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold; opacity: 0.6;">Beban Operasional</p>
                    <p style="font-size: 1.25rem; font-weight: bold; margin-top: 8px; color: #f97316;">Rp {{ number_format($kustom['expense'], 0, ',', '.') }}</p>
                </div>
                <div style="padding: 16px; border-radius: 12px; border: 2px solid {{ $kustom['laba'] >= 0 ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)' }};">
                    <p style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold; color: {{ $kustom['laba'] >= 0 ? '#10b981' : '#ef4444' }};">Laba Bersih Akhir</p>
                    <p style="font-size: 1.5rem; font-weight: 900; margin-top: 8px; color: {{ $kustom['laba'] >= 0 ? '#10b981' : '#ef4444' }};">
                        Rp {{ number_format($kustom['laba'], 0, ',', '.') }}
                    </p>
                </div>
            </div>
        @endif
    </x-filament::section>

</x-filament-panels::page>