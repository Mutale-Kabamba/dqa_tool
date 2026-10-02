<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'System Administration';

    protected static ?string $navigationLabel = 'User & Role Management';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isMealOfficer() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Account Information')
                    ->description('User credentials, active status, and core operational roles')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->placeholder('e.g., Jane Banda')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->helperText(fn (string $context): string => $context === 'edit' ? 'Leave empty to keep the existing password.' : 'Enter a secure password.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Account Active')
                            ->helperText('Inactive accounts cannot access the DQA panel.')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Operational Roles & Governance Permissions')
                    ->description('Assign one or more roles. Users may hold both Project Officer and Auditor roles simultaneously to enable Peer-Audits across projects under strict Anti-Self-Audit governance.')
                    ->schema([
                        Forms\Components\CheckboxList::make('roles')
                            ->label('Assigned Roles')
                            ->options([
                                User::ROLE_MEAL_OFFICER => 'MEAL Officer (Full System Administration, RAG Thresholds, Auditor Assignments)',
                                User::ROLE_PROJECT_OFFICER => 'Project Officer (Submits Data Reports, Oversees Project Quality, Responds to CAPA)',
                                User::ROLE_AUDITOR => 'Auditor (Performs Field & Peer Audits, Dimensional Inputs, Qualitative Feedback)',
                            ])
                            ->descriptions([
                                User::ROLE_MEAL_OFFICER => 'Can configure benchmark thresholds, manage projects, assign field auditors, and administer all users.',
                                User::ROLE_PROJECT_OFFICER => 'Oversees facility data for their assigned project, tracks remediation progress, and answers CAPA action items.',
                                User::ROLE_AUDITOR => 'Conducts audits on assigned sites. Anti-Self-Audit Policy automatically prevents auditing own projects.',
                            ])
                            ->columns(1)
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('User Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('roles')
                    ->label('Operational Roles')
                    ->badge()
                    ->separator(',')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        User::ROLE_MEAL_OFFICER => 'MEAL Officer',
                        User::ROLE_PROJECT_OFFICER => 'Project Officer',
                        User::ROLE_AUDITOR => 'Auditor',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        User::ROLE_MEAL_OFFICER => 'primary',
                        User::ROLE_PROJECT_OFFICER => 'info',
                        User::ROLE_AUDITOR => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean(),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
