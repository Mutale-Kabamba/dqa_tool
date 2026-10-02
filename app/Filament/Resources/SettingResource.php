<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'System Administration';

    protected static ?string $navigationLabel = 'RAG Thresholds & Settings';

    protected static ?string $modelLabel = 'RAG Threshold Setting';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isMealOfficer() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('RAG Performance Benchmark Thresholds')
                    ->description('Set decimal cut-offs for Green, Yellow, Orange, and Red status classifications across audits.')
                    ->schema([
                        Forms\Components\TextInput::make('green_threshold')
                            ->label('Green Threshold (Good)')
                            ->helperText('e.g., 0.85 for 85% compliance and above')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('yellow_threshold')
                            ->label('Yellow Threshold (Needs Improvement)')
                            ->helperText('e.g., 0.70 for 70% to 84.99% compliance')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('orange_threshold')
                            ->label('Orange Threshold (Significant Improvement Required)')
                            ->helperText('e.g., 0.55 for 55% to 69.99% compliance (Below 0.55 is Red/Critical)')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(1)
                            ->required(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('green_threshold')
                    ->label('Green Benchmark (>=)')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 1) . '%')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('yellow_threshold')
                    ->label('Yellow Benchmark (>=)')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 1) . '%')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('orange_threshold')
                    ->label('Orange Benchmark (>=)')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 1) . '%')
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y H:i'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('recalculate')
                    ->label('Recalculate Audits')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Recalculate All Audit Scores')
                    ->modalDescription('This will re-evaluate all historical audits and dimension scores using the active RAG benchmark thresholds. Do you wish to proceed?')
                    ->action(function () {
                        $engine = new \App\Services\DqaEngineService();
                        $count = $engine->recalculateAllAudits();
                        \Filament\Notifications\Notification::make()
                            ->title('Audits Recalculated')
                            ->body("Successfully re-evaluated {$count} audits against the updated thresholds.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
