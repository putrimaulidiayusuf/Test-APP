<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 1. Tombol Tambah Data
            Actions\CreateAction::make(),

            // 2. Tombol Export Excel
            ExportAction::make()
                ->label('Download Excel')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('Laporan_Transaksi_' . date('Y-m-d'))
                        ->withColumns([
                            Column::make('code')->heading('Kode Transaksi'),
                            Column::make('flight.flight_number')->heading('No. Penerbangan'),
                            Column::make('name')->heading('Nama Pemesan'),
                            Column::make('grandtotal')->heading('Total Bayar'),
                            Column::make('payment_status')->heading('Status'),
                            Column::make('created_at')->heading('Tanggal'),
                        ]),
                ]),
        ];
    }
}
