<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $modelLabel = 'Venta';
    protected static ?string $pluralModelLabel = 'Ventas';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('client.nombre_completo')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('concepto')
                    ->label('Concepto')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Desayuno' => 'success',
                        'Almuerzo' => 'info',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('concepto_detalle')
                    ->label('Detalle')
                    ->visible(fn($state): bool => !is_null($state) && $state !== '')
                    ->searchable(),

                Tables\Columns\TextColumn::make('precio')
                    ->label('Precio')
                    ->money('COP')
                    ->sortable(),

                Tables\Columns\IconColumn::make('firma_validada')
                    ->label('Firma')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('concepto')
                    ->options([
                        'Desayuno' => 'Desayuno',
                        'Almuerzo' => 'Almuerzo',
                        'Otro' => 'Otro',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->actions([])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('nueva_venta')
                    ->label('Nueva Venta')
                    ->modalHeading('Crear nueva venta')
                    ->modalWidth('5xl')
                    ->form([
                        Wizard::make([
                            Step::make('Datos de la venta')
                                ->description('Ingrese los datos básicos de la venta')
                                ->schema([
                                    Forms\Components\Select::make('client_id')
                                        ->label('Cliente')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->relationship('client', 'nombre_completo')
                                        ->searchable(['nombre_completo', 'identificacion']),

                                    Forms\Components\Select::make('concepto')
                                        ->label('Concepto')
                                        ->required()
                                        ->options([
                                            'Desayuno' => 'Desayuno',
                                            'Almuerzo' => 'Almuerzo',
                                            'Otro' => 'Otro',
                                        ])
                                        ->live()
                                        ->afterStateUpdated(fn($state, Forms\Set $set) =>
                                        $set('concepto_detalle', $state !== 'Otro' ? null : '')),

                                    Forms\Components\TextInput::make('concepto_detalle')
                                        ->label('Detalle del concepto')
                                        ->visible(fn(Forms\Get $get): bool => $get('concepto') === 'Otro')
                                        ->required(fn(Forms\Get $get): bool => $get('concepto') === 'Otro')
                                        ->maxLength(255)
                                        ->default(null),

                                    Forms\Components\TextInput::make('precio')
                                        ->label('Precio')
                                        ->required()
                                        ->numeric()
                                        ->prefix('$')
                                        ->maxValue(42949672.95),
                                ]),

                            Step::make('Validación QR')
                                ->description('Escanee el código QR del cliente')
                                ->schema([
                                    Forms\Components\TextInput::make('firma_qr')
                                        ->label('Escanear QR del carnet')
                                        ->placeholder('Escanee el QR del cliente')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            $clientId = $get('client_id');
                                            $client = Client::find($clientId);

                                            if (!$client) {
                                                $set('firma_validada', false);
                                                Notification::make()
                                                    ->danger()
                                                    ->title('Error')
                                                    ->body('Debe seleccionar un cliente primero')
                                                    ->send();
                                                return;
                                            }

                                            if ($state !== $client->identificacion) {
                                                $set('firma_validada', false);
                                                Notification::make()
                                                    ->danger()
                                                    ->title('QR Inválido')
                                                    ->body('El código QR no coincide con la identificación del cliente')
                                                    ->send();
                                                return;
                                            }

                                            $set('firma_validada', true);
                                            Notification::make()
                                                ->success()
                                                ->title('QR Validado')
                                                ->body('El código QR ha sido validado correctamente')
                                                ->send();
                                        })
                                        ->dehydrated(false),

                                    Forms\Components\Hidden::make('firma_validada')
                                        ->default(false)
                                        ->required()
                                        ->rules(['required', 'boolean', 'accepted']),
                                ]),

                            Step::make('Confirmación')
                                ->description('Confirme los datos de la venta')
                                ->schema([
                                    Forms\Components\Placeholder::make('resumen')
                                        ->label('Resumen de la venta')
                                        ->content(function (Forms\Get $get): string {
                                            $client = Client::find($get('client_id'));

                                            if (!$client) {
                                                return 'Error: Cliente no encontrado';
                                            }

                                            $concepto = $get('concepto') ?? 'No especificado';
                                            $detalle = $get('concepto_detalle');
                                            $precio = $get('precio') ? number_format($get('precio'), 2, ',', '.') : '0,00';

                                            return "{$client->nombre_completo}\n" .
                                                "Compro  {$concepto}" .
                                                ($detalle ? " - {$detalle}" : '') .
                                                "\nPor: $" . $precio;
                                        }),
                                ]),
                        ])
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['fecha_venta'] = now();
                        if ($data['concepto'] !== 'Otro') {
                            $data['concepto_detalle'] = null;
                        }
                        return $data;
                    })
                    ->action(function (array $data) {
                        if (!$data['firma_validada']) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('La firma QR no ha sido validada')
                                ->send();
                            return;
                        }

                        $client = Client::find($data['client_id']);

                        $ventaData = [
                            'client_id' => $data['client_id'],
                            'concepto' => $data['concepto'],
                            'precio' => $data['precio'],
                            'firma_validada' => true,
                            'fecha_venta' => $data['fecha_venta'],
                        ];

                        if (isset($data['concepto_detalle']) && $data['concepto_detalle'] !== null) {
                            $ventaData['concepto_detalle'] = $data['concepto_detalle'];
                        }

                        $venta = Sale::create($ventaData);

                        Notification::make()
                            ->success()
                            ->title('Venta Registrada')
                            ->body("Venta registrada correctamente a nombre de {$client->nombre_completo} por valor de $" . number_format($data['precio'], 2, ',', '.'))
                            ->persistent()
                            ->send();
                    })
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 0 ? 'success' : 'warning';
    }
}
