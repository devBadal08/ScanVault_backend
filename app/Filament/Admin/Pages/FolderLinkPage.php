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
    protected static ?int $navigationSort = 9;

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
                ->options(
                    Folder::whereNull('parent_id')
                        ->whereHas('user', function ($q) {
                            $q->where('company_id', auth()->user()->company_id);
                        })
                        ->pluck('name', 'id')
                )
                ->searchable()
                ->reactive() // Livewire reacts to changes
                ->afterStateUpdated(fn() => $this->reset('targetFolders'))
                ->required(),

            // Target Folders (multi-select)
            Forms\Components\Select::make('targetFolders')
                ->label('Select Target Folders')
                ->multiple()
                ->searchable()
                ->reactive()
                ->options(function () {
                    $companyId = auth()->user()->company_id;
                    $query = Folder::whereNull('parent_id')
                        ->whereHas('user', function ($q) use ($companyId) {
                            $q->where('company_id', $companyId);
                        });
                    // Exclude source folder
                    if ($this->sourceFolder) {
                        $query->where('id', '!=', $this->sourceFolder);
                    }
                    // Exclude fully linked folders
                    if ($this->linkType === 'full') {
                        $linkedFullIds = DB::table('folder_links')
                            ->pluck('target_folder_id')
                            ->toArray();

                        $query->whereNotIn('id', $linkedFullIds);
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
        $data = $this->form->getState(); // <-- get form state
        $sourceFolder = $data['sourceFolder'] ?? null;
        $targetFolders = $data['targetFolders'] ?? [];
        $linkType = $data['linkType'] ?? 'partial';

        if (!$sourceFolder || empty($targetFolders)) {
            Notification::make()->warning()->title('Select source and target folders')->send();
            return;
        }

        foreach ($targetFolders as $targetId) {
            // store the link
            DB::table('folder_links')->updateOrInsert(
                [
                    'source_folder_id' => $sourceFolder,
                    'target_folder_id' => $targetId,
                ],
                [
                    'link_type' => $linkType,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // Merge photos: add entries from target folder into source folder (database only)
            if ($linkType === 'partial') {
                $photos = Photo::where('folder_id', $targetId)->get();
                foreach ($photos as $photo) {
                    Photo::create([
                        'folder_id' => $sourceFolder,
                        'path' => $photo->path,
                        'user_id' => $photo->user_id, // if applicable
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // reset only the multi-select
        $this->form->fill(['targetFolders' => []]);

        Notification::make()->success()->title('Folders linked successfully')->send();
    }

    public function unlinkFolder($linkId)
    {
        // Get the link record first
        $link = DB::table('folder_links')->where('id', $linkId)->first();

        if ($link) {
            $sourceFolder = $link->source_folder_id;
            $targetFolder = $link->target_folder_id;

            // Delete photos in source folder that came from this target folder
            Photo::where('folder_id', $sourceFolder)
                ->whereIn('path', function ($query) use ($targetFolder) {
                    $query->select('path')
                        ->from('photos')
                        ->where('folder_id', $targetFolder);
                })->delete();

            // Delete the folder link
            DB::table('folder_links')->where('id', $linkId)->delete();

            Notification::make()
                ->success()
                ->title('Folder unlinked and merged photos removed')
                ->send();
        }
    }

    public function getLinkedFoldersProperty()
    {
        if (! $this->sourceFolder) {
            return collect();
        }

        $companyId = auth()->user()->company_id;

        return DB::table('folder_links')
            ->leftJoin('folders as f', 'f.id', '=', 'folder_links.target_folder_id')
            ->leftJoin('users as u', 'u.id', '=', 'f.user_id')
            ->where('folder_links.source_folder_id', $this->sourceFolder)
            ->where('u.company_id', $companyId)
            ->select('folder_links.*', 'f.name as target_name')
            ->get();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        // Only Admin & Manager can open the page directly
        return $user && in_array($user->role, ['admin', 'manager']);
    }
}
