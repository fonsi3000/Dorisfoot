<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\TextInput;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function afterCreate(): void
    {
        // Mostrar modal para escanear QR después de crear la venta
        $this->showQrModal();
    }

    protected function showQrModal(): void
    {
        $this->dialog()->open([
            'title' => 'Validar Firma',
            'description' => 'Por favor escanee el código QR del carnet del cliente',
            'form' => [
                TextInput::make('firma_qr')
                    ->label('Código QR')
                    ->required()
                    ->autocomplete(false),
            ],
            'submitAction' => [
                'label' => 'Validar QR',
                'action' => function (array $data) {
                    $venta = $this->record;

                    if ($venta->validarFirma($data['firma_qr'])) {
                        $this->dialog()->success([
                            'title' => 'Firma Validada',
                            'description' => 'La firma ha sido validada correctamente.',
                        ]);
                    } else {
                        $this->dialog()->error([
                            'title' => 'Error de Validación',
                            'description' => 'El código QR no coincide con la identificación del cliente.',
                        ]);
                    }
                },
            ],
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
