<?php

namespace App\Filament\Resources\SaleResource\Widgets;

use App\Models\Sale;
use App\Models\Client;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CreateSaleWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    // Vista asociada al widget
    protected static string $view = 'filament.resources.sale-resource.widgets.create-sale-widget';

    // Propiedades del widget
    public ?array $data = [];
    public bool $isQrValid = false;
    public ?Sale $lastSale = null; // Nueva propiedad para almacenar la última venta

    // Configuración del span de columnas
    protected int | string | array $columnSpan = 'full';

    // Método que se ejecuta al montar el widget
    public function mount(): void
    {
        $this->form->fill();
    }

    // Definición del formulario
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Grid para el selector de cliente
                Grid::make()
                    ->schema([
                        Select::make('client_id')
                            ->label('Cliente')
                            ->options(function () {
                                return Client::query()
                                    ->orderBy('nombre_completo')
                                    ->pluck('nombre_completo', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->isQrValid = false;
                            })
                            ->columnSpan(2),
                    ])->columns(2),

                // Sección de tipo de servicio
                Section::make('Tipo de Servicio')
                    ->description('Seleccione el tipo de servicio a registrar')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Radio::make('concepto')
                                    ->label('')
                                    ->options([
                                        'Desayuno' => new HtmlString('
                                            <div class="flex items-start gap-4 p-4 concept-card concept-breakfast">
                                                <div class="flex-shrink-0">
                                                    <x-heroicon-o-sun class="w-8 h-8" />
                                                </div>
                                                <div>
                                                    <span class="font-medium text-lg">Desayuno</span>
                                                    <p class="text-sm">Servicio de desayuno completo</p>
                                                </div>
                                            </div>
                                        '),
                                        'Almuerzo' => new HtmlString('
                                            <div class="flex items-start gap-4 p-4 concept-card concept-lunch">
                                                <div class="flex-shrink-0">
                                                    <x-heroicon-o-cake class="w-8 h-8" />
                                                </div>
                                                <div>
                                                    <span class="font-medium text-lg">Almuerzo</span>
                                                    <p class="text-sm">Servicio de almuerzo ejecutivo</p>
                                                </div>
                                            </div>
                                        '),
                                        'Otro' => new HtmlString('
                                            <div class="flex items-start gap-4 p-4 concept-card concept-other">
                                                <div class="flex-shrink-0">
                                                    <x-heroicon-o-sparkles class="w-8 h-8" />
                                                </div>
                                                <div>
                                                    <span class="font-medium text-lg">Otro Servicio</span>
                                                    <p class="text-sm">Otros servicios de alimentación</p>
                                                </div>
                                            </div>
                                        '),
                                    ])
                                    ->required()
                                    ->inline()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $set('concepto_detalle', $state !== 'Otro' ? null : '');
                                    })
                                    ->extraAttributes(['class' => 'concept-cards-container'])
                                    ->columnSpan('full'),
                            ])
                            ->columns(1),

                        // Grid para detalles adicionales
                        Grid::make()
                            ->schema([
                                TextInput::make('concepto_detalle')
                                    ->label('Detalle del Servicio')
                                    ->placeholder('Especifique el tipo de servicio')
                                    ->visible(fn(Get $get): bool => $get('concepto') === 'Otro')
                                    ->required(fn(Get $get): bool => $get('concepto') === 'Otro')
                                    ->columnSpan(2),

                                TextInput::make('precio')
                                    ->label('Precio')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->columnSpan(3),
                            ])
                            ->columns(3),
                    ]),

                // Campo para QR
                TextInput::make('firma_qr')
                    ->label('QR Cliente')
                    ->placeholder('Escanee el QR')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $client = Client::find($get('client_id'));

                        if (!$client) {
                            $this->isQrValid = false;
                            Notification::make()
                                ->danger()
                                ->title('Seleccione un cliente')
                                ->send();
                            return;
                        }

                        $this->isQrValid = ($state === $client->identificacion);

                        if ($this->isQrValid) {
                            Notification::make()
                                ->success()
                                ->title('QR Válido, Puede registrar la venta.')
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('QR Inválido, No coincide con el cliente')
                                ->send();
                        }
                    }),
            ])
            ->statePath('data');
    }

    // Método para crear una nueva venta
    public function create()
    {
        try {
            $data = $this->form->getState();

            DB::beginTransaction();

            $ventaData = [
                'client_id' => $data['client_id'],
                'concepto' => $data['concepto'],
                'concepto_detalle' => $data['concepto_detalle'] ?? null,
                'precio' => $data['precio'],
                'firma_qr' => $data['firma_qr'],
                'firma_validada' => $this->isQrValid,
            ];

            $sale = Sale::create($ventaData);
            $client = Client::find($data['client_id']);

            // Almacenar la última venta con la relación del cliente
            $this->lastSale = Sale::with('client')->find($sale->id);

            DB::commit();

            Notification::make()
                ->success()
                ->title('Venta Registrada')
                ->body("Venta registrada para {$client->nombre_completo}")
                ->send();

            $this->form->fill();
            $this->isQrValid = false;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->lastSale = null; // Resetear última venta en caso de error

            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Error al registrar la venta: ' . $e->getMessage())
                ->send();

            Log::error('Error al registrar venta:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    // Método para determinar si el widget puede ser visto
    public static function canView(): bool
    {
        return true;
    }
}
