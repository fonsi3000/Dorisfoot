<?php

namespace App\Filament\Resources\SaleResource\Widgets;

use App\Models\Sale;
use App\Models\Client;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CreateSaleWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.resources.sale-resource.widgets.create-sale-widget';

    public ?array $data = [];
    public bool $isQrValid = false;

    protected int | string | array $columnSpan = 'full';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
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
                    }),

                Select::make('concepto')
                    ->label('Concepto')
                    ->required()
                    ->options([
                        'Desayuno' => 'Desayuno',
                        'Almuerzo' => 'Almuerzo',
                        'Otro' => 'Otro',
                    ])
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        $set('concepto_detalle', $state !== 'Otro' ? null : '');
                    }),

                TextInput::make('concepto_detalle')
                    ->label('Detalle')
                    ->visible(fn(Get $get): bool => $get('concepto') === 'Otro')
                    ->required(fn(Get $get): bool => $get('concepto') === 'Otro'),

                TextInput::make('precio')
                    ->label('Precio')
                    ->required()
                    ->numeric()
                    ->prefix('$'),

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
                    })
            ])
            ->columns(4)
            ->statePath('data');  // Agregamos esto para manejar el estado del formulario

    }

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

            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Error al registrar la venta: ' . $e->getMessage())
                ->send();
        }
    }

    public static function canView(): bool
    {
        return true;
    }
}
