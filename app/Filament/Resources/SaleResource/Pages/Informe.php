<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Client;
use App\Models\Sale;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Filament\Support\Colors\Color;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;

class Informe extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = SaleResource::class;

    protected static string $view = 'filament.resources.sale-resource.pages.informe';

    protected static ?string $title = 'Informes de Ventas';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'tipo_informe' => 'general',
            'tipo_fecha' => 'cierre',
            'mes_cierre' => now()->format('m'),
            'periodo_cierre' => 'cierre1',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                Select::make('tipo_informe')
                                    ->options([
                                        'general' => 'Informe General',
                                        'individual' => 'Informe por Cliente',
                                        'todos_clientes' => 'Informe de Todos los Clientes',
                                    ])
                                    ->live()
                                    ->default('general')
                                    ->required(),

                                Select::make('client_id')
                                    ->label('Cliente')
                                    ->options(Client::query()->pluck('nombre_completo', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(fn(Get $get): bool => $get('tipo_informe') === 'individual')
                                    ->visible(fn(Get $get): bool => $get('tipo_informe') === 'individual'),

                                Radio::make('tipo_fecha')
                                    ->label('Periodo')
                                    ->options([
                                        'cierre' => 'Por Cierre',
                                        'personalizado' => 'Personalizado',
                                    ])
                                    ->default('cierre')
                                    ->inline()
                                    ->live(),

                                Grid::make()
                                    ->schema([
                                        Select::make('mes_cierre')
                                            ->label('Mes')
                                            ->options([
                                                '01' => 'Enero',
                                                '02' => 'Febrero',
                                                '03' => 'Marzo',
                                                '04' => 'Abril',
                                                '05' => 'Mayo',
                                                '06' => 'Junio',
                                                '07' => 'Julio',
                                                '08' => 'Agosto',
                                                '09' => 'Septiembre',
                                                '10' => 'Octubre',
                                                '11' => 'Noviembre',
                                                '12' => 'Diciembre',
                                            ])
                                            ->default(now()->format('m'))
                                            ->visible(fn(Get $get): bool => $get('tipo_fecha') === 'cierre')
                                            ->required(fn(Get $get): bool => $get('tipo_fecha') === 'cierre'),

                                        Select::make('periodo_cierre')
                                            ->label('Periodo')
                                            ->options([
                                                'cierre1' => 'Cierre 1 (23 al 10)',
                                                'cierre2' => 'Cierre 2 (11 al 22)',
                                            ])
                                            ->default('cierre1')
                                            ->visible(fn(Get $get): bool => $get('tipo_fecha') === 'cierre')
                                            ->required(fn(Get $get): bool => $get('tipo_fecha') === 'cierre'),
                                    ])
                                    ->columns(2)
                                    ->visible(fn(Get $get): bool => $get('tipo_fecha') === 'cierre'),

                                Grid::make()
                                    ->schema([
                                        DatePicker::make('fecha_inicio')
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->visible(fn(Get $get): bool => $get('tipo_fecha') === 'personalizado')
                                            ->required(fn(Get $get): bool => $get('tipo_fecha') === 'personalizado'),

                                        DatePicker::make('fecha_fin')
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->visible(fn(Get $get): bool => $get('tipo_fecha') === 'personalizado')
                                            ->required(fn(Get $get): bool => $get('tipo_fecha') === 'personalizado'),
                                    ])
                                    ->columns(2)
                                    ->visible(fn(Get $get): bool => $get('tipo_fecha') === 'personalizado'),
                            ]),
                    ])->columns(1),
            ])
            ->statePath('data');
    }

    protected function getFechasReporte(): array
    {
        $data = $this->form->getState();

        if ($data['tipo_fecha'] === 'cierre') {
            $año = now()->year;
            $mes = $data['mes_cierre'];

            if ($mes == '01' && $data['periodo_cierre'] === 'cierre1') {
                $año = now()->subYear()->year;
            }

            if ($data['periodo_cierre'] === 'cierre1') {
                $inicio = Carbon::create($año, $mes, 1)->subMonth()->setDay(23)->startOfDay();
                $fin = Carbon::create($año, $mes, 10)->endOfDay();
            } else {
                $inicio = Carbon::create($año, $mes, 11)->startOfDay();
                $fin = Carbon::create($año, $mes, 22)->endOfDay();
            }
        } else {
            $inicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
            $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        }

        return [
            'inicio' => $inicio,
            'fin' => $fin
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generar_pdf')
                ->label('Generar Informe')
                ->color(Color::Emerald)
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    try {
                        $data = $this->form->getState();
                        $fechas = $this->getFechasReporte();

                        if ($data['tipo_informe'] === 'todos_clientes') {
                            return $this->generarInformesTodosClientes($fechas);
                        }

                        return $this->generarInformeIndividualOGeneral($data, $fechas);
                    } catch (\Exception $e) {
                        Log::error('Error generando informe', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);

                        Notification::make()
                            ->danger()
                            ->title('Error generando informe')
                            ->body($e->getMessage())
                            ->send();
                    }
                })
        ];
    }

    protected function generarInformeIndividualOGeneral($data, $fechas)
    {
        $query = Sale::query()
            ->whereBetween('fecha_venta', [
                $fechas['inicio'],
                $fechas['fin']
            ]);

        if ($data['tipo_informe'] === 'individual') {
            $query->where('client_id', $data['client_id']);
        }

        $ventas = $query->with(['client' => function ($query) {
            $query->select('id', 'nombre_completo', 'identificacion', 'area');
        }])
            ->orderBy('fecha_venta', 'asc')
            ->get();

        $datosInforme = [
            'ventas' => $ventas,
            'fecha_inicio' => $fechas['inicio'],
            'fecha_fin' => $fechas['fin'],
            'tipo_informe' => $data['tipo_informe'],
            'cliente' => $data['tipo_informe'] === 'individual' ?
                Client::find($data['client_id']) : null
        ];

        $vista = $data['tipo_informe'] === 'individual' ?
            'filament.pages.reports.individual' :
            'filament.pages.reports.general';

        $pdf = Pdf::loadView($vista, $datosInforme);

        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->output();
            },
            $this->generarNombreArchivo($data)
        );
    }

    protected function generarInformesTodosClientes($fechas)
    {
        // Obtener todos los clientes que tienen ventas en el período
        $clientesConVentas = Sale::whereBetween('fecha_venta', [
            $fechas['inicio'],
            $fechas['fin']
        ])
            ->select('client_id')
            ->distinct()
            ->with(['client' => function ($query) {
                $query->select('id', 'nombre_completo', 'identificacion', 'area');
            }])
            ->get()
            ->pluck('client');

        if ($clientesConVentas->isEmpty()) {
            Notification::make()
                ->warning()
                ->title('Sin datos')
                ->body('No se encontraron ventas en el período seleccionado.')
                ->send();
            return;
        }

        // Crear archivo ZIP temporal
        $tempFile = tempnam(sys_get_temp_dir(), 'informes_');
        $zip = new ZipArchive();
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Generar un PDF por cada cliente
        foreach ($clientesConVentas as $cliente) {
            $ventas = Sale::where('client_id', $cliente->id)
                ->whereBetween('fecha_venta', [$fechas['inicio'], $fechas['fin']])
                ->with(['client' => function ($query) {
                    $query->select('id', 'nombre_completo', 'identificacion', 'area');
                }])
                ->orderBy('fecha_venta', 'asc')
                ->get();

            $datosInforme = [
                'ventas' => $ventas,
                'fecha_inicio' => $fechas['inicio'],
                'fecha_fin' => $fechas['fin'],
                'tipo_informe' => 'individual',
                'cliente' => $cliente
            ];

            $pdf = Pdf::loadView('filament.pages.reports.individual', $datosInforme);
            $nombreArchivo = "informe_cliente_{$cliente->id}.pdf";
            $zip->addFromString($nombreArchivo, $pdf->output());
        }

        $zip->close();

        return response()->download(
            $tempFile,
            "informes_clientes_" . now()->format('Y-m-d_His') . '.zip'
        )->deleteFileAfterSend(true);
    }

    protected function generarNombreArchivo($data): string
    {
        $tipo = $data['tipo_informe'] === 'individual' ?
            'cliente_' . $data['client_id'] :
            'general';

        return "informe_{$tipo}_" . now()->format('Y-m-d_His') . '.pdf';
    }
}
