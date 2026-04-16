<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use App\Models\Folder;
use App\Models\User;
use App\Models\Photo;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class FolderLinkPage extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $view = 'filament.admin.pages.folder-link-page';
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationGroup = 'Folder Management';
    protected static ?int $navigationSort = 7;

    public $sourceFolder;
    public $targetFolders = [];
    public $linkType = 'partial';

    public function mount(): void
    {
        $this->form->fill([
            'sourceFolder' => $this->sourceFolder,
            'targetFolders' => $this->targetFolders,
            'linkType' => $this->linkType,
        ]);
    }

    // ✅ NEW HELPER METHOD: Get valid User IDs based on role
    protected function getAccessibleUserIds(): array
    {
        $authUser = auth()->user();

        // If Manager: Get users created by this manager + the manager themselves
        if ($authUser->role === 'manager') {
            $userIds = User::where('created_by', $authUser->id)->pluck('id')->toArray();
            $userIds[] = $authUser->id;
            return $userIds;
        }

        // If Admin: Get admin's users + manager's users + managers themselves + admin themselves
        if ($authUser->role === 'admin') {
            $managerIds = User::where('role', 'manager')
                ->where('created_by', $authUser->id)
                ->pluck('id')
                ->toArray();

            $userIds = User::whereIn('created_by', array_merge([$authUser->id], $managerIds))
                ->pluck('id')
                ->toArray();

            return array_unique(array_merge([$authUser->id], $managerIds, $userIds));
        }

        return [];
    }

    protected function getFormSchema(): array
    {
        return [
            // Source Folder
            Forms\Components\Select::make('sourceFolder')
                ->label('Search Source Folder')
                ->placeholder('Type at least 6 characters to search source folder')
                ->searchable()
                ->searchDebounce(500)
                ->searchPrompt('Type 6 or more characters...')
                ->loadingMessage('Searching folders...')
                ->getSearchResultsUsing(function (string $search) {

                    if (strlen($search) < 6) return [];

                    $companyId = auth()->user()->companies()->first()?->id;
                    $userIds = $this->getAccessibleUserIds(); // ✅ Get allowed users

                    return Folder::where('company_id', $companyId)
                        ->whereIn('user_id', $userIds) // ✅ Filter by allowed users
                        ->where('name', 'like', "%{$search}%")
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->getOptionLabelUsing(fn ($value) => Folder::find($value)?->name)
                ->reactive()
                ->afterStateUpdated(fn () => $this->reset('targetFolders'))
                ->required(),

            // Target Folders (multi-select)
            Forms\Components\Select::make('targetFolders')
                ->label('Search Target Folders')
                ->placeholder('Type at least 6 characters to search target folder')
                ->multiple()
                ->searchable()
                ->searchDebounce(500)
                ->searchPrompt('Type 6 or more characters...')
                ->loadingMessage('Searching folders...')
                ->getSearchResultsUsing(function (string $search) {

                    if (strlen($search) < 6) return [];

                    $companyId = auth()->user()->companies()->first()?->id;
                    $userIds = $this->getAccessibleUserIds(); // ✅ Get allowed users

                    $query = Folder::where('company_id', $companyId)
                        ->whereIn('user_id', $userIds) // ✅ Filter by allowed users
                        ->where('name', 'like', "%{$search}%");

                    // ❗ prevent selecting same as source
                    if ($this->sourceFolder) {
                        $query->where('id', '!=', $this->sourceFolder);
                    }

                    return $query->pluck('name', 'id')->toArray();
                })
                ->getOptionLabelsUsing(function ($values) {
                    return Folder::whereIn('id', $values)->pluck('name', 'id')->toArray();
                })
                ->required(),

            // Link Type
            Forms\Components\Radio::make('linkType')
                ->label('Link Type')
                ->options([
                    'partial' => 'Partial Link',
                    'full' => 'Full Link',
                ])
                ->reactive()
                ->afterStateUpdated(fn() => $this->reset('targetFolders'))
                ->required(),
        ];
    }

    public function linkFolders()
    {
        $data = $this->form->getState();

        $sourceFolder  = $data['sourceFolder'] ?? null;
        $targetFolders = $data['targetFolders'] ?? [];
        $linkType      = $data['linkType'] ?? 'partial';

        if (!$sourceFolder || empty($targetFolders)) {
            Notification::make()
                ->warning()
                ->title('Select source and target folders')
                ->send();
            return;
        }

        // 1️⃣ Prevent source folder being already a target
        $sourceIsTarget = DB::table('folder_links')
            ->where('target_folder_id', $sourceFolder)
            ->exists();

        if ($sourceIsTarget) {
            Notification::make()
                ->danger()
                ->title('Invalid folder')
                ->body('This folder is already linked as a target and cannot be used as a source.')
                ->send();
            return;
        }

        // 2️⃣ Load source folder (ID-based, not name-based)
        $source = Folder::find($sourceFolder);

        if (!$source) {
            Notification::make()
                ->danger()
                ->title('Source folder not found')
                ->send();
            return;
        }

        // 3️⃣ Filter valid target folders (same company, not self)
        $validTargets = Folder::whereIn('id', $targetFolders)
            ->where('company_id', $source->company_id)
            ->where('id', '!=', $sourceFolder)
            ->pluck('id')
            ->toArray();

        if (empty($validTargets)) {
            Notification::make()
                ->danger()
                ->title('Invalid target folders')
                ->send();
            return;
        }

        // 4️⃣ Prevent multiple full links
        if ($linkType === 'full') {
            $hasFullLink = DB::table('folder_links')
                ->where('source_folder_id', $sourceFolder)
                ->where('link_type', 'full')
                ->exists();

            if ($hasFullLink) {
                Notification::make()
                    ->danger()
                    ->title('This folder is already fully linked')
                    ->send();
                return;
            }
        }

        // 5️⃣ Save links (ID → ID, clean and deterministic)
        DB::transaction(function () use ($sourceFolder, $validTargets, $linkType) {
            foreach ($validTargets as $targetId) {
                DB::table('folder_links')->updateOrInsert(
                    [
                        'source_folder_id' => $sourceFolder,
                        'target_folder_id' => $targetId,
                    ],
                    [
                        'link_type'  => $linkType,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            if ($linkType === 'full') {
                DB::table('folder_links')
                    ->where('source_folder_id', $sourceFolder)
                    ->update(['link_type' => 'full']);
            }
        });

        $this->form->fill(['targetFolders' => []]);

        Notification::make()
            ->success()
            ->title('Folders linked successfully')
            ->send();
    }

    public function unlinkFolder($linkId)
    {
        DB::table('folder_links')->where('id', $linkId)->delete();

        Notification::make()
            ->success()
            ->title('Folder unlinked successfully')
            ->send();
    }

    public function getLinkedFoldersProperty()
    {
        if (! $this->sourceFolder) {
            return collect();
        }

        $companyId = auth()->user()->companies()->first()->id;

        return DB::table('folder_links')
            ->leftJoin('folders as f', 'f.id', '=', 'folder_links.target_folder_id')
            ->where('folder_links.source_folder_id', $this->sourceFolder)
            ->whereIn('f.user_id', function ($q) use ($companyId) {
                $q->select('user_id')
                ->from('company_user')
                ->where('company_id', $companyId);
            })
            ->select(
                'folder_links.id',
                'folder_links.link_type',
                'f.name as target_name'
            )
            ->get();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        // Only Admin & Manager can open the page directly
        return $user && in_array($user->role, ['admin', 'manager']);
    }
}