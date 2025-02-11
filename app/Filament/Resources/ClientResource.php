<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Clientes';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('nombre_completo')
                            ->label('Nombre Completo')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ingrese el nombre completo')
                            ->columnSpan('full'),

                        Forms\Components\TextInput::make('identificacion')
                            ->label('Identificación')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Ingrese el número de identificación')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('telefono')
                            ->label('Teléfono')
                            ->tel()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ingrese el número de teléfono')
                            ->columnSpan(1),

                        Forms\Components\Select::make('area')
                            ->label('Área')
                            ->required()
                            ->options([
                                'Auditoría y Control' => 'Auditoría y Control',
                                'Cartera' => 'Cartera',
                                'Colchonería' => 'Colchonería',
                                'Contabilidad' => 'Contabilidad',
                                'Corte' => 'Corte',
                                'Costos e Inventarios' => 'Costos e Inventarios',
                                'Cumplimiento' => 'Cumplimiento',
                                'Direccionamiento Estratégico' => 'Direccionamiento Estratégico',
                                'Diseño y Desarrollo' => 'Diseño y Desarrollo',
                                'Gerencia Financiera' => 'Gerencia Financiera',
                                'Gestión Ambiental' => 'Gestión Ambiental',
                                'Gestión Calidad' => 'Gestión Calidad',
                                'Gestión Comercial' => 'Gestión Comercial',
                                'Gestión de Compras y Almacén' => 'Gestión de Compras y Almacén',
                                'Gestión Documental' => 'Gestión Documental',
                                'Gestión Humana' => 'Gestión Humana',
                                'Ingeniería' => 'Ingeniería',
                                'Logística y Despachos' => 'Logística y Despachos',
                                'Mantenimiento' => 'Mantenimiento',
                                'Mercadeo y Publicidad' => 'Mercadeo y Publicidad',
                                'Planificación de la Producción' => 'Planificación de la Producción',
                                'Proceso 1' => 'Proceso 1',
                                'Producción de Espuma y Cassata' => 'Producción de Espuma y Cassata',
                                'Seguridad y Salud en el Trabajo' => 'Seguridad y Salud en el Trabajo',
                                'Servicio al Cliente' => 'Servicio al Cliente',
                                'Tecnología de la Información' => 'Tecnología de la Información',
                                'Tesorería' => 'Tesorería',
                                'Varios' => 'Varios',
                                'Ventas internacionales' => 'Ventas internacionales',
                            ])
                            ->searchable()
                            ->optionsLimit(3)
                            ->placeholder('Buscar área...')
                            ->columnSpan(1)
                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Client $record): string => "ID: {$record->identificacion}")
                    ->wrap(),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Teléfono copiado')
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('area')
                    ->label('Área')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('area')
                    ->multiple()
                    ->options([
                        'Auditoría y Control' => 'Auditoría y Control',
                        'Cartera' => 'Cartera',
                        'Colchonería' => 'Colchonería',
                        'Contabilidad' => 'Contabilidad',
                        'Corte' => 'Corte',
                        'Costos e Inventarios' => 'Costos e Inventarios',
                        'Cumplimiento' => 'Cumplimiento',
                        'Direccionamiento Estratégico' => 'Direccionamiento Estratégico',
                        'Diseño y Desarrollo' => 'Diseño y Desarrollo',
                        'Gerencia Financiera' => 'Gerencia Financiera',
                        'Gestión Ambiental' => 'Gestión Ambiental',
                        'Gestión Calidad' => 'Gestión Calidad',
                        'Gestión Comercial' => 'Gestión Comercial',
                        'Gestión de Compras y Almacén' => 'Gestión de Compras y Almacén',
                        'Gestión Documental' => 'Gestión Documental',
                        'Gestión Humana' => 'Gestión Humana',
                        'Ingeniería' => 'Ingeniería',
                        'Logística y Despachos' => 'Logística y Despachos',
                        'Mantenimiento' => 'Mantenimiento',
                        'Mercadeo y Publicidad' => 'Mercadeo y Publicidad',
                        'Planificación de la Producción' => 'Planificación de la Producción',
                        'Proceso 1' => 'Proceso 1',
                        'Producción de Espuma y Cassata' => 'Producción de Espuma y Cassata',
                        'Seguridad y Salud en el Trabajo' => 'Seguridad y Salud en el Trabajo',
                        'Servicio al Cliente' => 'Servicio al Cliente',
                        'Tecnología de la Información' => 'Tecnología de la Información',
                        'Tesorería' => 'Tesorería',
                        'Varios' => 'Varios',
                        'Ventas internacionales' => 'Ventas internacionales',
                    ])
                    ->searchable(),
                Tables\Filters\Filter::make('identificacion')
                    ->form([
                        Forms\Components\TextInput::make('identificacion')
                            ->label('Documento')
                            ->placeholder('Buscar por documento')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['identificacion'],
                                fn(Builder $query, $value): Builder => $query->where('identificacion', 'like', "%{$value}%")
                            );
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Editar Cliente')
                    ->modalWidth('md'),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Eliminar Cliente')
                    ->modalWidth('sm'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
        ];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->nombre_completo;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre_completo', 'identificacion', 'telefono', 'area'];
    }
}
