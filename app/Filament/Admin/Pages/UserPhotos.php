<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class UserPhotos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationLabel = 'My Photos';
    protected static ?string $navigationGroup = 'Photos of user';
    protected static string $view = 'filament.admin.pages.user-photos';
    protected static ?int $navigationSort = 10;

    public $selectedUser = null;
    public $selectedFolder = null;
    public $selectedSubfolder = null;

    public $folders = [];
    public $items = [];

    public $globalSearch = '';
    public $globalResults = [];

    public int $perPage = 10;
    public int $page = 1;
    public int $total = 0;
    public int $datesPerPage = 3;

    protected function groupByDate(array $items): array
    {
        $groups = [];

        foreach ($items as $item) {

            if (empty($item['created_at'])) continue;

            $created = Carbon::parse($item['created_at']);

            // HARD BLOCK
            if ($created->year < 2000) {
                logger()->warning('Invalid Date Blocked', [
                    'item' => $item
                ]);
                continue;
            }

            if ($created->isToday()) {
                $label = 'Today';
            } elseif ($created->isYesterday()) {
                $label = 'Yesterday';
            } else {
                $label = $created->format('d-m-Y');
            }

            $groups[$label][] = $item;
        }

        uksort($groups, function ($a, $b) {
            if (in_array($a, ['Today','Yesterday']) || in_array($b, ['Today','Yesterday'])) {
                return 0;
            }

            return Carbon::createFromFormat('d-m-Y', $b)->timestamp
                <=> Carbon::createFromFormat('d-m-Y', $a)->timestamp;
        });

        return $groups;
    }

    protected function paginateDateGroups(array $grouped): array
    {
        $keys = array_keys($grouped);

        $pagedKeys = array_slice(
            $keys,
            ($this->page - 1) * $this->datesPerPage,
            $this->datesPerPage
        );

        $result = [];

        foreach ($pagedKeys as $key) {
            $result[$key] = $grouped[$key];
        }

        $this->total = count($keys); // total DATE groups

        return $result;
    }

    protected function getMediaDate(string $filePath): Carbon
    {
        $absolutePath = storage_path('app/public/' . $filePath);
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['jpg','jpeg','png']) && function_exists('exif_read_data')) {

            $exif = @exif_read_data($absolutePath);

            if (!empty($exif['DateTimeOriginal'])) {

                try {
                    $date = Carbon::createFromFormat('Y:m:d H:i:s', $exif['DateTimeOriginal']);

                    // ✅ Reject unrealistic years
                    if ($date->year < 2000) {
                        throw new \Exception('Invalid EXIF year');
                    }

                    return $date;

                } catch (\Throwable $e) {
                    // fallback silently
                }
            }
        }

        // Filename timestamp fallback
        if (preg_match('/_(\d{13})\./', $filePath, $matches)) {
            return Carbon::createFromTimestampMs((int) $matches[1]);
        }

        // Filesystem fallback
        return Carbon::createFromTimestamp(
            max(946684800, Storage::disk('public')->lastModified($filePath))
        );
    }

    protected function getFolderDate(string $folderPath): Carbon
    {
        $latestDate = null;

        foreach (Storage::disk('public')->files($folderPath) as $file) {
            $date = $this->getMediaDate($file);

            if ($date->year < 2000) continue;

            if (!$latestDate || $date->gt($latestDate)) {
                $latestDate = $date;
            }
        }

        // Filesystem fallback
        try {
            $timestamp = Storage::disk('public')->lastModified($folderPath);

            if (!$timestamp || $timestamp < 946684800) { 
                // 946684800 = 01-01-2000
                return now();
            }

            $fallback = Carbon::createFromTimestamp($timestamp);

            if ($fallback->year < 2000) {
                return now();
            }

            return $latestDate ?? $fallback;

        } catch (\Throwable $e) {
            return now();
        }
    }

    public function mount(): void
    {
        $user = Auth::user();

        // 🔒 HARD SECURITY
        if ($user->role !== 'user') {
            abort(403);
        }

        $this->selectedUser = $user;

        $this->selectedFolder = request()->get('folder');
        $this->selectedSubfolder = request()->get('subfolder');
        $this->page = request()->get('page', 1);

        $this->loadFoldersAndItems();
    }

    protected function loadFoldersAndItems(): void
    {
        $companyId = $this->selectedUser->companies()->first()?->id;
        $userId = $this->selectedUser->id;

        $basePath = "{$companyId}/{$userId}";

        if (!Storage::disk('public')->exists($basePath)) return;

        // ================= MAIN FOLDERS =================
        if (!$this->selectedFolder) {

            $allFolders = collect(Storage::disk('public')->directories($basePath))
                ->map(fn ($dir) => [
                    'type' => 'folder',
                    'path' => $dir,
                    'name' => basename($dir),
                    'created_at' => $this->getFolderDate($dir)->toDateTimeString(),
                ])
                ->sortByDesc('created_at')
                ->values()
                ->toArray();

            $grouped = $this->groupByDate($allFolders);

            // ✅ paginate DATE SECTIONS instead of folders
            $this->folders = $this->paginateDateGroups($grouped);

            return;
        }

        // ================= INSIDE FOLDER =================
        $path = $this->selectedSubfolder
            ? "{$this->selectedFolder}/{$this->selectedSubfolder}"
            : $this->selectedFolder;

        if (!Storage::disk('public')->exists($path)) return;

        $directories = Storage::disk('public')->directories($path);
        $files = Storage::disk('public')->files($path);

        $folderItems = collect($directories)->map(fn ($dir) => [
            'type' => 'folder',
            'path' => $dir,
            'name' => basename($dir),
            'created_at' => $this->getFolderDate($dir)->toDateTimeString(),
        ]);

        $mediaItems = collect($files)
            ->filter(fn ($file) =>
                in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)),
                ['jpg','jpeg','png','mp4','pdf'])
            )
            ->map(fn ($file) => [
                'type' => match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
                    'mp4' => 'video',
                    'pdf' => 'pdf',
                    default => 'image',
                },
                'path' => $file,
                'name' => basename($file),
                'created_at' => $this->getMediaDate($file)->toDateTimeString(),
            ]);

        $combined = $folderItems->merge($mediaItems)
            ->sortByDesc('created_at')
            ->values()
            ->toArray();

        // ✅ GROUP EVERYTHING FIRST
        $grouped = $this->groupByDate($combined);

        // ✅ FLATTEN (preserve correct order)
        $flat = collect($grouped)->flatten(1)->values();

        // ✅ TOTAL ITEMS (not folders, not dates)
        $this->total = $flat->count();

        // ✅ SLICE PAGE
        $paged = $flat->slice(
            ($this->page - 1) * $this->perPage,
            $this->perPage
        )->values();

        // ✅ REGROUP for UI
        $this->items = $this->groupByDate($paged->toArray());
    }

    public function searchGlobal(): void
    {
        $this->globalResults = [];

        if (!$this->globalSearch) {
            return;
        }

        $companyId = $this->selectedUser->companies()->first()?->id;
        $userId = $this->selectedUser->id;

        $basePath = "{$companyId}/{$userId}";

        $files = Storage::disk('public')->allFiles($basePath);

        $this->globalResults = collect($files)
            ->filter(fn ($file) =>
                str_contains(strtolower($file), strtolower($this->globalSearch))
            )
            ->take(50)
            ->map(fn ($file) => [
                'name' => basename($file),
                'path' => dirname($file),
                'type' => pathinfo($file, PATHINFO_EXTENSION),
                'user_id' => $userId,
                'user' => $this->selectedUser->name,
            ])
            ->values()
            ->toArray();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check() && Auth::user()->role === 'user';
    }
}
