<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Navigation\NavigationItem;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Facades\Storage;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Create Companies';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('company_name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->label('Company Name'),
                TextInput::make('admin_name')
                    ->required()
                    ->label('Admin Name'),
                FileUpload::make('company_logo')
                    ->label('Company Logo')
                    ->image() // ensures only image files
                    ->disk('public')
                    ->columns(1)
                    ->directory('company-logos') // optional: folder to store logos
                    ->preserveFilenames()
                    ->maxSize(512)
                    ->visibility('public')
                    ->downloadable()               // allow download
                    ->openable()                   // allow opening
                    ->previewable(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')->label('Company Name')->sortable()->searchable()->alignLeft(),
                TextColumn::make('admin_name')->label('Admin Name')->sortable()->searchable()->alignLeft(),
                ImageColumn::make('company_logo')
                    ->label('Logo')
                    ->visibility('visible')
                    ->url(fn ($record) => asset('storage/' . $record->company_logo)) // clickable
                    ->openUrlInNewTab() 
                    ->square()
                    ->alignCenter(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->modalHeading('Confirm Delete')
                    ->modalDescription('Enter password to delete this company.')
                    ->form([
                        Forms\Components\TextInput::make('deletePassword')
                            ->label('Confirm Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule('in:Delete@123#')
                            ->validationMessages([
                                'required' => 'You must enter the password.',
                                'in' => 'You must enter the correct password.',
                            ]),
                    ])
                    ->action(function (array $data, Company $record) {

                        // delete users & children
                        $users = \App\Models\User::where('company_id', $record->id)->get();

                        foreach ($users as $user) {
                            self::deleteUserRecursively($user->id);
                        }

                        $record->delete();

                        \Filament\Notifications\Notification::make()
                            ->title('Company deleted successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
            ]);
    }

    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make('Create Companies')
                ->url(static::getUrl('index'))
                ->icon('heroicon-o-plus-circle')
                ->group('Administration'),

            NavigationItem::make('Company List')
                ->url(static::getUrl('company-list'))
                ->icon('heroicon-o-document')
                ->group('Administration'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
            'company-list' => Pages\CompanyList::route('/company-list'),
            'dashboard' => Pages\CompanyDashboard::route('/{record}/dashboard'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Super Admin');
    }

    public static function deleteUserRecursively(int $userId): void
    {
        $childUsers = \App\Models\User::where('created_by', $userId)->get();

        foreach ($childUsers as $child) {
            self::deleteUserRecursively($child->id);
        }

        $userFolder = storage_path("app/public/{$userId}");
        if (is_dir($userFolder)) {
            \File::deleteDirectory($userFolder);
        }

        \App\Models\User::where('id', $userId)->delete();
    }
}
