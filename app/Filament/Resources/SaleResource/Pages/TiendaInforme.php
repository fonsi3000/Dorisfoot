<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\DB;

class TiendaInforme extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = SaleResource::class;

    protected static string $view = 'filament.resources.sale-resource.pages.tienda-informe';

    protected static ?string $title = 'Informe de Tienda';

    public ?array $data = [];

    // Propiedades para la interfaz
    public $ventasPorFecha = [];
    public $totalVentas = 0;
    public $cantidadVentas = 0;

    public function mount(): void
    {
        $this->form->fill([
            'fecha_inicio' => now()->startOfMonth()->format('Y-m-d'),
            'fecha_fin' => now()->format('Y-m-d'),
            'tipo_grafico' => 'diario',
        ]);

        $this->filtrarDatos();
    }

    public function updated($propertyName): void
    {
        if (str_starts_with($propertyName, 'data.')) {
            $this->filtrarDatos();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filtros')
                    ->schema([
                        Grid::make()
                            ->schema([
                                DatePicker::make('fecha_inicio')
                                    ->label('Fecha Inicio')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->default(now()->startOfMonth())
                                    ->required(),

                                DatePicker::make('fecha_fin')
                                    ->label('Fecha Fin')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->default(now())
                                    ->required(),
                            ])
                            ->columns(2),

                        Select::make('tipo_grafico')
                            ->label('Tipo de Agrupación')
                            ->options([
                                'diario' => 'Ventas Diarias',
                                'semanal' => 'Ventas Semanales',
                                'mensual' => 'Ventas Mensuales',
                            ])
                            ->default('diario')
                            ->required(),
                    ])
                    ->columns(1),
            ])
            ->statePath('data')
            ->live()
            ->reactive();
    }

    public function filtrarDatos(): void
    {
        $data = $this->form->getState();

        if (empty($data)) {
            return;
        }

        $fechaInicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fechaFin = Carbon::parse($data['fecha_fin'])->endOfDay();

        // Obtener ventas sin anular en el rango de fechas
        $query = Sale::query()
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$fechaInicio, $fechaFin]);

        // Total de ventas y cantidad
        $this->totalVentas = $query->sum('precio');
        $this->cantidadVentas = $query->count();

        // Agrupar por fecha según el tipo de gráfico seleccionado
        switch ($data['tipo_grafico']) {
            case 'diario':
                $this->ventasPorFecha = $this->obtenerVentasDiarias($fechaInicio, $fechaFin);
                break;
            case 'semanal':
                $this->ventasPorFecha = $this->obtenerVentasSemanales($fechaInicio, $fechaFin);
                break;
            case 'mensual':
                $this->ventasPorFecha = $this->obtenerVentasMensuales($fechaInicio, $fechaFin);
                break;
        }

        // Emitir un evento para que la interfaz sepa que los datos cambiaron
        $this->dispatch('datosActualizados');
    }

    private function obtenerVentasDiarias(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $resultado = [];
        $periodo = $fechaInicio->clone()->startOfDay();
        $fin = $fechaFin->clone()->endOfDay();

        // Crear un array con todas las fechas en el intervalo
        $fechas = [];
        while ($periodo->lte($fin)) {
            $fechaStr = $periodo->format('Y-m-d');
            $fechas[$fechaStr] = [
                'fecha' => $fechaStr,
                'label' => $periodo->format('d/m/Y'),
                'cantidad' => 0,
                'total' => 0
            ];
            $periodo->addDay();
        }

        // Obtener los datos de las ventas
        $ventas = Sale::query()
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->get();

        // Agrupar las ventas por día
        foreach ($ventas as $venta) {
            $fecha = Carbon::parse($venta->created_at)->format('Y-m-d');
            if (isset($fechas[$fecha])) {
                $fechas[$fecha]['cantidad']++;
                $fechas[$fecha]['total'] += $venta->precio;
            }
        }

        // Filtrar solo los días con ventas
        $resultado = array_values(array_filter($fechas, fn($item) => $item['cantidad'] > 0));

        // Ordenar por fecha
        usort($resultado, fn($a, $b) => $a['fecha'] <=> $b['fecha']);

        return $resultado;
    }

    private function obtenerVentasSemanales(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $ventas = Sale::query()
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->get();

        $semanasAgrupadas = [];

        foreach ($ventas as $venta) {
            $fecha = Carbon::parse($venta->created_at);
            $inicioSemana = $fecha->copy()->startOfWeek()->format('Y-m-d');
            $finSemana = $fecha->copy()->endOfWeek()->format('Y-m-d');
            $claveSemana = $inicioSemana;

            if (!isset($semanasAgrupadas[$claveSemana])) {
                $semanasAgrupadas[$claveSemana] = [
                    'fecha' => $claveSemana,
                    'label' => Carbon::parse($inicioSemana)->format('d/m/Y') . ' - ' . Carbon::parse($finSemana)->format('d/m/Y'),
                    'cantidad' => 0,
                    'total' => 0
                ];
            }

            $semanasAgrupadas[$claveSemana]['cantidad']++;
            $semanasAgrupadas[$claveSemana]['total'] += $venta->precio;
        }

        // Convertir a array y ordenar por fecha
        $resultado = array_values($semanasAgrupadas);
        usort($resultado, fn($a, $b) => $a['fecha'] <=> $b['fecha']);

        return $resultado;
    }

    private function obtenerVentasMensuales(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $ventas = Sale::query()
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->get();

        $mesesAgrupados = [];

        foreach ($ventas as $venta) {
            $fecha = Carbon::parse($venta->created_at);
            $claveMes = $fecha->format('Y-m');

            if (!isset($mesesAgrupados[$claveMes])) {
                $mesesAgrupados[$claveMes] = [
                    'fecha' => $claveMes,
                    'label' => $fecha->format('M Y'),
                    'cantidad' => 0,
                    'total' => 0
                ];
            }

            $mesesAgrupados[$claveMes]['cantidad']++;
            $mesesAgrupados[$claveMes]['total'] += $venta->precio;
        }

        // Convertir a array y ordenar por fecha
        $resultado = array_values($mesesAgrupados);
        usort($resultado, fn($a, $b) => $a['fecha'] <=> $b['fecha']);

        return $resultado;
    }
}
