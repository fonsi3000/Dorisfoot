<x-filament-panels::page>
    <div>
        {{ $this->form }}
        
        <div class="mt-4 flex justify-end">
            <x-filament::button wire:click="filtrarDatos" color="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Aplicar filtros</span>
                <span wire:loading>Procesando...</span>
            </x-filament::button>
        </div>
    </div>

    @if($this->cantidadVentas > 0)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Ventas Totales</h2>
                        <p class="text-sm text-gray-500">En el período seleccionado</p>
                    </div>
                    <div class="text-3xl font-bold text-primary-600">${{ number_format($this->totalVentas, 0, ',', '.') }}</div>
                </div>
            </x-filament::section>
            
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Cantidad de Ventas</h2>
                        <p class="text-sm text-gray-500">Transacciones realizadas</p>
                    </div>
                    <div class="text-3xl font-bold text-primary-600">{{ $this->cantidadVentas }}</div>
                </div>
            </x-filament::section>
            
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Promedio por Venta</h2>
                        <p class="text-sm text-gray-500">Valor promedio</p>
                    </div>
                    <div class="text-3xl font-bold text-primary-600">
                        ${{ $this->cantidadVentas > 0 ? number_format($this->totalVentas / $this->cantidadVentas, 0, ',', '.') : 0 }}
                    </div>
                </div>
            </x-filament::section>
        </div>

        <div class="mt-6">
            <x-filament::section>
                <h2 class="text-xl font-bold text-gray-900 mb-3">Evolución de Ventas</h2>
                <div class="w-full" style="height: 350px;" x-data="{
                    init() {
                        const datos = {{ Js::from($this->ventasPorFecha) }};
                        
                        if (datos.length === 0) return;
                        
                        const labels = datos.map(item => item.label);
                        const totales = datos.map(item => item.total);
                        
                        const ctx = document.getElementById('ventas-chart-{{ md5(json_encode($this->ventasPorFecha)) }}').getContext('2d');
                        
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Ventas Totales',
                                    data: totales,
                                    borderColor: '#16a34a',
                                    backgroundColor: 'rgba(22, 163, 74, 0.1)',
                                    borderWidth: 2,
                                    tension: 0.2,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'top',
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return `$ ${new Intl.NumberFormat('es-CO').format(context.raw)}`;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return `${new Intl.NumberFormat('es-CO').format(value)}`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                }">
                    <canvas id="ventas-chart-{{ md5(json_encode($this->ventasPorFecha)) }}"></canvas>
                </div>
            </x-filament::section>
        </div>
        
        <x-filament::section class="mt-6">
            <h2 class="text-xl font-bold text-gray-900 mb-3">Detalle de Ventas por {{ $this->data['tipo_grafico'] === 'diario' ? 'Día' : ($this->data['tipo_grafico'] === 'semanal' ? 'Semana' : 'Mes') }}</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                        <tr>
                            <th scope="col" class="px-6 py-3">{{ $this->data['tipo_grafico'] === 'diario' ? 'Fecha' : ($this->data['tipo_grafico'] === 'semanal' ? 'Semana' : 'Mes') }}</th>
                            <th scope="col" class="px-6 py-3 text-right">Cantidad</th>
                            <th scope="col" class="px-6 py-3 text-right">Valor Total</th>
                            <th scope="col" class="px-6 py-3 text-right">Promedio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->ventasPorFecha as $item)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4">{{ $item['label'] }}</td>
                                <td class="px-6 py-4 text-right">{{ $item['cantidad'] }}</td>
                                <td class="px-6 py-4 text-right">${{ number_format($item['total'], 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right">${{ number_format($item['total'] / $item['cantidad'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr class="bg-white border-b">
                                <td colspan="4" class="px-6 py-4 text-center">No hay datos disponibles</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 font-medium">
                            <td class="px-6 py-3">Total</td>
                            <td class="px-6 py-3 text-right">{{ $this->cantidadVentas }}</td>
                            <td class="px-6 py-3 text-right">${{ number_format($this->totalVentas, 0, ',', '.') }}</td>
                            <td class="px-6 py-3 text-right">
                                ${{ $this->cantidadVentas > 0 ? number_format($this->totalVentas / $this->cantidadVentas, 0, ',', '.') : 0 }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>
    @else
        <div class="mt-6">
            <x-filament::section>
                <div class="text-center py-8">
                    <div class="text-gray-400 mb-3">
                        <svg class="inline-block w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">No hay datos disponibles</h3>
                    <p class="mt-1 text-sm text-gray-500">No se encontraron ventas en el período seleccionado.</p>
                </div>
            </x-filament::section>
        </div>
    @endif

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endpush
</x-filament-panels::page>