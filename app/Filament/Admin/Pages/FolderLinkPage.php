<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use App\Models\Folder;
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

    protected function getFormSchema(): array
    {
        return [
            // Source Folder
            Forms\Components\Select::make('sourceFolder')
                ->label('Select Source Folder')
                ->options(function () {
                    $companyId = auth()->user()->companies()->first()->id;

                    return Folder::where(function ($q) {
                            $q->whereNull('parent_id')
                            ->orWhere('parent_id', 0);
                        })
                        ->whereIn('user_id', function ($q) use ($companyId) {
                            $q->select('user_id')
                            ->from('company_user')
                            ->where('company_id', $companyId);
                        })
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->searchable()
                ->reactive()
                ->afterStateUpdated(fn () => $this->reset('targetFolders'))
                ->required(),

            // Target Folders (multi-select)
            Forms\Components\Select::make('targetFolders')
                ->label('Select Target Folders')
                ->multiple()
                ->searchable()
                ->reactive()
                ->options(function () {
                    $companyId = auth()->user()->companies()->first()->id;

                    $query = Folder::where(function ($q) {
                            $q->whereNull('parent_id')
                            ->orWhere('parent_id', 0);
                        })
                        ->whereIn('user_id', function ($q) use ($companyId) {
                            $q->select('user_id')
                            ->from('company_user')
                            ->where('company_id', $companyId);
                        });

                    if ($this->sourceFolder) {
                        $isLocked = DB::table('folder_links')
                            ->where('source_folder_id', $this->sourceFolder)
                            ->where('link_type', 'full')
                            ->exists();

                        if ($isLocked) {
                            return [];
                        }
                        $query->where('id', '!=', $this->sourceFolder);
                    }

                    if ($this->linkType === 'full') {
                        $query->whereNotIn(
                            'id',
                            DB::table('folder_links')->pluck('target_folder_id')
                        );
                    }

                    return $query->pluck('name', 'id')->toArray();
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