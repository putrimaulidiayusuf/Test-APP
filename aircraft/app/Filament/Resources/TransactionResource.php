<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Models\FlightSeat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;
use App\Models\FlightClass;
use App\Models\PromoCode;
use Filament\Forms\Set;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Penerbangan')
                    ->description('Pilih jadwal dan kelas penerbangan')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->default('TRX-' . strtoupper(uniqid()))
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Forms\Components\Select::make('flight_id')
                            ->relationship('flight', 'flight_number')
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('flight_class_id')
                            ->relationship('class', 'class_type')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($set, $get)),
                    ])->columns(3),

                Forms\Components\Section::make('Informasi Pemesan')
                    ->description('Data kontak orang yang memesan')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Pemesan')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->required(),
                        Forms\Components\TextInput::make('number_of_passengers')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($set, $get)),
                    ])->columns(2),

                Forms\Components\Section::make('Daftar Penumpang')
                    ->description('Isi detail data untuk setiap penumpang')
                    ->schema([
                        Forms\Components\Repeater::make('passenger')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Penumpang')
                                    ->required(),
                                Forms\Components\DatePicker::make('date_of_birth')
                                    ->required(),
                                Forms\Components\TextInput::make('nationality')
                                    ->required(),
                                Forms\Components\Select::make('flight_seat_id')
                                    ->label('Pilih Kursi')
                                    ->options(function (Get $get) {
                                        $flightId = $get('../../flight_id');
                                        if (!$flightId) return [];

                                        return FlightSeat::where('flight_id', $flightId)
                                            ->where('is_available', true)
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable(),
                            ])
                            ->columns(2)
                            ->grid(2)
                            ->minItems(1),
                    ]),

                Forms\Components\Section::make('Pembayaran')
                    ->schema([
                        Forms\Components\Select::make('promo_code_id')
                            ->relationship('promo', 'code')
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::updateTotals($set, $get)),
                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\TextInput::make('subtotal')
                            ->numeric()
                            ->prefix('IDR')
                            ->readOnly()
                            ->helperText('Otomatis: Harga Kelas x Jumlah Penumpang'),
                        Forms\Components\TextInput::make('grandtotal')
                            ->numeric()
                            ->prefix('IDR')
                            ->readOnly()
                            ->helperText('Otomatis: Subtotal - Potongan Promo'),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('flight.flight_number')
                    ->label('Pesawat'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Pemesan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('number_of_passengers')
                    ->label('Pax')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('grandtotal')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = 'Dari: ' . \Carbon\Carbon::parse($data['dari_tanggal'])->format('d M Y');
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = 'Sampai: ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->format('d M Y');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function updateTotals(Set $set, Get $get): void
    {
        $flightClassId = $get('flight_class_id');
        $pax = (int) ($get('number_of_passengers') ?? 1);
        $price = 0;
        if ($flightClassId) {
            $price = FlightClass::find($flightClassId)?->price ?? 0;
        }
        $subtotal = $price * $pax;
        $set('subtotal', $subtotal);
        $promoId = $get('promo_code_id');
        $discount = 0;
        if ($promoId) {
            $promo = PromoCode::find($promoId);
            if ($promo) {
                $discount = $promo->discount_amount;
            }
        }
        $grandtotal = max(0, $subtotal - $discount);
        $set('grandtotal', $grandtotal);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
