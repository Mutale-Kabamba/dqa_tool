<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Portfolio & Sites';

    protected static ?string $navigationLabel = 'Projects & Campaigns';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isMealOfficer() || $user->isProjectOfficer());
    }

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();
        if ($user && $user->isProjectOfficer() && ! $user->isMealOfficer()) {
            return 'My Assigned Projects';
        }

        return 'Projects & Campaigns';
    }

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();
        if ($user && $user->isProjectOfficer() && ! $user->isMealOfficer()) {
            return 'My Project Operations';
        }

        return 'Portfolio & Sites';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Project Details')
                    ->description('Define programmatic identification and status for DQA auditing')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Project Name')
                            ->placeholder('e.g., Samalani Ana')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('code')
                            ->label('Project Code')
                            ->placeholder('e.g., SAMALANI-ANA')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        Forms\Components\Select::make('project_officer_id')
                            ->label('Designated Project Officer')
                            ->relationship('projectOfficer', 'name', fn ($query) => $query->whereJsonContains('roles', \App\Models\User::ROLE_PROJECT_OFFICER)->orWhereJsonContains('roles', \App\Models\User::ROLE_MEAL_OFFICER))
                            ->searchable()
                            ->preload()
                            ->helperText('Designates the Project Officer who submits data files, oversees performance, and responds to CAPA remediation.')
                            ->nullable(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active for Field Audits')
                            ->default(true),

                        Forms\Components\Textarea::make('description')
                            ->label('Description & Objectives')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Project Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('projectOfficer.name')
                    ->label('Project Officer')
                    ->placeholder('Unassigned')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('audits_count')
                    ->counts('audits')
                    ->label('Total Audits')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->tooltip('Actions')
                ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && ! $user->isMealOfficer() && $user->isProjectOfficer()) {
            $query->where('project_officer_id', $user->id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
