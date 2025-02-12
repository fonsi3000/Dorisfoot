{{-- resources/views/filament/resources/sale-resource/widgets/create-sale-widget.blade.php --}}
<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            {{-- Encabezado con estilo --}}
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold tracking-tight text-gray-950 dark:text-white">
                        Registro de Ventas
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Complete los datos para registrar una nueva venta
                    </p>
                </div>
                
                {{-- Indicador de estado QR --}}
                <div class="flex items-center gap-2">
                    <div @class([
                        'h-3 w-3 rounded-full',
                        'bg-success-500 animate-pulse' => $isQrValid,
                        'bg-danger-500' => !$isQrValid,
                    ])></div>
                    <span class="text-sm" @class([
                        'text-success-500' => $isQrValid,
                        'text-danger-500' => !$isQrValid,
                    ])>
                        {{ $isQrValid ? 'QR Validado Correctamente' : 'QR Pendiente' }}
                    </span>
                </div>
            </div>

            {{-- Formulario con estilo --}}
            <form wire:submit.prevent="create" class="space-y-4">
                {{ $this->form }}

                <div class="flex justify-end gap-3 mt-6">
                    {{-- Botón registrar venta --}}
                    <x-filament::button
                        type="submit"
                        color="success"
                        icon="heroicon-m-shopping-cart"
                        class="relative overflow-hidden"
                        :disabled="!$isQrValid"
                    >
                        <span class="relative z-10">Registrar Venta</span>
                    </x-filament::button>
                </div>
            </form>

            {{-- Sección de última venta --}}
            @if($lastSale)
                <div class="mt-6 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                            Última Venta Registrada
                        </h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $lastSale->created_at->diffForHumans() }}
                        </span>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Cliente:</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $lastSale->client->nombre_completo }}
                            </span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Concepto:</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $lastSale->concepto }}
                                @if($lastSale->concepto_detalle)
                                    - {{ $lastSale->concepto_detalle }}
                                @endif
                            </span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Precio:</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                ${{ number_format($lastSale->precio, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Mensaje de ayuda --}}
            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                <p>
                    <span class="font-medium">Nota:</span> 
                    Para registrar una venta, primero seleccione un cliente y complete todos los campos requeridos. 
                    Luego escanee el QR del cliente para validar la venta.
                </p>
            </div>
        </div>
    </x-filament::section>

    {{-- Estilos para las tarjetas de concepto --}}
    <style>
        /* Contenedor del radio group */
        .concept-cards-container .filament-forms-radio-component {
            @apply grid grid-cols-3 gap-4;
        }

        /* Cada opción del radio */
        .concept-cards-container .filament-forms-radio-component > div {
            @apply block w-full;
        }

        /* Label contenedor de cada card */
        .concept-cards-container .filament-forms-radio-component label {
            @apply block w-full h-full cursor-pointer;
        }

        /* Card básica */
        .concept-card {
            @apply rounded-xl border-2 shadow-sm transition-all duration-300;
        }

        /* Desayuno */
        .concept-breakfast {
            @apply border-amber-200 bg-gradient-to-br from-amber-50 to-amber-100/50 text-amber-700;
        }

        .concept-cards-container input[type="radio"]:checked + label .concept-breakfast {
            @apply border-amber-500 ring-2 ring-amber-500/20 from-amber-100 to-amber-200/70;
        }

        /* Almuerzo */
        .concept-lunch {
            @apply border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-100/50 text-emerald-700;
        }

        .concept-cards-container input[type="radio"]:checked + label .concept-lunch {
            @apply border-emerald-500 ring-2 ring-emerald-500/20 from-emerald-100 to-emerald-200/70;
        }

        /* Otro */
        .concept-other {
            @apply border-purple-200 bg-gradient-to-br from-purple-50 to-purple-100/50 text-purple-700;
        }

        .concept-cards-container input[type="radio"]:checked + label .concept-other {
            @apply border-purple-500 ring-2 ring-purple-500/20 from-purple-100 to-purple-200/70;
        }

        /* Hover y selección */
        .concept-cards-container label:hover .concept-card {
            @apply -translate-y-0.5 shadow-md;
        }

        .concept-cards-container input[type="radio"]:checked + label .concept-card {
            @apply -translate-y-1 shadow-lg;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .concept-cards-container .filament-forms-radio-component {
                @apply grid-cols-1;
            }
        }
    </style>
</x-filament-widgets::widget>