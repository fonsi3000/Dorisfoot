<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\Client;
use Carbon\Carbon;
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
use Filament\Navigation\NavigationItem;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $modelLabel = 'Venta';
    protected static ?string $pluralModelLabel = 'Ventas';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withTrashed(); // Esto es crucial para que se muestren las ventas anuladas
    }

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

                // Agregar esta columna para el estado
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Estado')
                    ->formatStateUsing(fn($state): string => $state === null ? 'ACTIVA' : 'ANULADA')
                    ->badge()
                    ->color(fn($state): string => $state === null ? 'success' : 'danger'),

                Tables\Columns\IconColumn::make('firma_validada')
                    ->label('Firma')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y h:i A')
                    ->timezone('America/Bogota')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('concepto')
                    ->options([
                        'Desayuno' => 'Desayuno',
                        'Almuerzo' => 'Almuerzo',
                        'Otro' => 'Otro',
                    ]),
                Tables\Filters\TrashedFilter::make()
                    ->label('Estado de Ventas')
                    ->trueLabel('Mostrar solo anuladas')
                    ->falseLabel('Mostrar solo activas')
                    ->placeholder('Mostrar todas')
                    ->default(null),
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
            ->actions([
                // Ver detalles de la venta
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalles')
                    ->modalHeading('Detalles de la Venta')
                    ->form([
                        Forms\Components\Section::make('Información de la Venta')
                            ->schema([
                                Forms\Components\TextInput::make('concepto')
                                    ->label('Concepto')
                                    ->disabled(),
                                Forms\Components\TextInput::make('concepto_detalle')
                                    ->label('Detalle')
                                    ->visible(fn($state): bool => !is_null($state) && $state !== '')
                                    ->disabled(),
                                Forms\Components\TextInput::make('precio')
                                    ->label('Precio')
                                    ->prefix('$')
                                    ->disabled(),
                                Forms\Components\TextInput::make('created_at')
                                    ->label('Fecha de Creación')
                                    ->formatStateUsing(function ($state) {
                                        return now()->parse($state)->setTimezone('America/Bogota')->format('d/m/Y h:i A');
                                    })
                                    ->disabled(),
                                Forms\Components\TextInput::make('deleted_at')
                                    ->label('Fecha de Anulación')
                                    ->formatStateUsing(function ($state) {
                                        return $state ? now()->parse($state)->setTimezone('America/Bogota')->format('d/m/Y h:i A') : '';
                                    })
                                    ->disabled()
                                    ->visible(fn($state) => $state !== null),
                            ])
                            ->columns(2),
                    ])
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                // Anular venta
                Tables\Actions\Action::make('anular')
                    ->label('Anular Venta')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Anular Venta')
                    ->modalDescription('¿Está seguro que desea anular esta venta? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, anular venta')
                    ->modalCancelActionLabel('No, cancelar')
                    ->action(function (Sale $record) {
                        if ($record->trashed()) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Esta venta ya se encuentra anulada')
                                ->send();
                            return;
                        }

                        $record->delete();

                        Notification::make()
                            ->success()
                            ->title('Venta Anulada')
                            ->body('La venta ha sido anulada correctamente')
                            ->send();
                    })
                    ->visible(fn(Sale $record): bool => !$record->trashed()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Anular Seleccionadas')
                        ->modalHeading('Anular Ventas Seleccionadas')
                        ->modalDescription('¿Está seguro que desea anular las ventas seleccionadas? Esta acción no se puede deshacer.')
                        ->modalSubmitActionLabel('Sí, anular ventas')
                        ->modalCancelActionLabel('No, cancelar'),
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
                                                "Compró {$concepto}" .
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
                            'created_at' => now(), // Esto asegura que se use la fecha del servidor
                            'fecha_venta' => now(), // Esto también usará la fecha del servidor
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
            'informe' => Pages\Informe::route('/informe'),
            'tienda-informe' => Pages\TiendaInforme::route('/tienda-informe'),
        ];
    }
    public static function getNavigationItems(): array
    {
        return [
            ...parent::getNavigationItems(),
            NavigationItem::make('Informe')
                ->icon('heroicon-o-document-chart-bar')
                ->url(Pages\Informe::getUrl())
                ->sort(3),
            NavigationItem::make('Informe de Tienda')
                ->icon('heroicon-o-chart-bar')
                ->url(Pages\TiendaInforme::getUrl())
                ->sort(4),
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
