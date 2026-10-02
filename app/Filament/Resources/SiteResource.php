<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteResource\Pages;
use App\Models\Project;
use App\Models\Site;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Portfolio & Governance';

    protected static ?string $navigationLabel = 'Sites & Facilities';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isMealOfficer() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Facility / Site Details')
                    ->description('Manage health facilities, geographic district, catchment population, and project link')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Facility / Site Name')
                            ->placeholder('e.g., Chilenje Level 1 Hospital')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('code')
                            ->label('Site Code')
                            ->placeholder('e.g., SITE-LUS-001')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        Forms\Components\Select::make('project_id')
                            ->label('Linked Project')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('district')
                            ->label('District')
                            ->options([
                                'Lusaka' => 'Lusaka',
                                'Livingstone' => 'Livingstone',
                                'Choma' => 'Choma',
                                'Ndola' => 'Ndola',
                                'Kitwe' => 'Kitwe',
                                'Chipata' => 'Chipata',
                                'Kasama' => 'Kasama',
                                'Solwezi' => 'Solwezi',
                                'Mansa' => 'Mansa',
                                'Mongu' => 'Mongu',
                                'Kabwe' => 'Kabwe',
                            ])
                            ->default('Lusaka')
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('province')
                            ->label('Province')
                            ->options([
                                'Lusaka Province' => 'Lusaka Province',
                                'Southern Province' => 'Southern Province',
                                'Copperbelt Province' => 'Copperbelt Province',
                                'Eastern Province' => 'Eastern Province',
                                'Central Province' => 'Central Province',
                                'Northern Province' => 'Northern Province',
                                'North-Western Province' => 'North-Western Province',
                                'Luapula Province' => 'Luapula Province',
                                'Western Province' => 'Western Province',
                                'Muchinga Province' => 'Muchinga Province',
                            ])
                            ->default('Lusaka Province')
                            ->required(),

                        Forms\Components\Select::make('facility_type')
                            ->label('Facility Level / Type')
                            ->options([
                                'Level 1 District Hospital' => 'Level 1 District Hospital',
                                'Level 2 Provincial Hospital' => 'Level 2 Provincial Hospital',
                                'Urban Health Centre' => 'Urban Health Centre',
                                'Rural Health Centre' => 'Rural Health Centre',
                                'Community Health Post' => 'Community Health Post',
                                'Specialized Clinic' => 'Specialized Clinic',
                            ])
                            ->default('Urban Health Centre')
                            ->required(),

                        Forms\Components\TextInput::make('catchment_area')
                            ->label('Catchment Area / Population')
                            ->placeholder('e.g., Ward 14 - Approx. 45,000 population')
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Site is Active for Audits')
                            ->default(true)
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Facility / Site')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('district')
                    ->label('District')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('province')
                    ->label('Province')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('facility_type')
                    ->label('Type')
                    ->sortable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->placeholder('General Portfolio')
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('district', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('district')
                    ->options([
                        'Lusaka' => 'Lusaka',
                        'Livingstone' => 'Livingstone',
                        'Choma' => 'Choma',
                        'Ndola' => 'Ndola',
                        'Kitwe' => 'Kitwe',
                    ]),
                Tables\Filters\SelectFilter::make('project_id')
                    ->relationship('project', 'name')
                    ->label('Project'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            'edit' => Pages\EditSite::route('/{record}/edit'),
        ];
    }
}
