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
                    {{-- Botón limpiar
                    <x-filament::button
                        type="button"
                        color="gray"
                        wire:click="$refresh"
                        icon="heroicon-m-arrow-path"
                    >
                        Limpiar
                    </x-filament::button> --}}

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
</x-filament-widgets::widget>