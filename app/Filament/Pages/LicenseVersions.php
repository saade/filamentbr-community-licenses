<?php

namespace App\Filament\Pages;

use App\Models\License;
use App\Models\Team;
use Cache;
use Filament\Actions\Action;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use function App\Support\tenant;

class LicenseVersions extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.license-versions';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'licenses/{license}/versions';

    #[Locked]
    public ?Team $record = null;

    public ?License $license = null;

    public function mount(): void
    {
        $this->record = tenant(Team::class);
    }

    public function getTitle(): string
    {
        return __('License Versions: :license', ['license' => $this->license->name]);
    }
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->url(ManageLicenses::getUrl()),
        ];
    }
    public function licenseVersionsInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state($this->getVersions())
            ->schema([
                Infolists\Components\RepeatableEntry::make('versions')
                    ->label(false)
                    ->schema([
                        Infolists\Components\Section::make()
                            ->columns()
                            ->schema([
                                Infolists\Components\TextEntry::make('version'),
                                Infolists\Components\TextEntry::make('time')
                                    ->date(),
                            ]),
                    ]),
            ]);
    }

    private function getVersions(): array
    {
        return Cache::remember('license-' . $this->license->id, now()->addMinutes(60), function (): array {
            $file = Storage::disk("local")->json("satis/p2/" . $this->license->name . ".json");
            if (empty($file["packages"][$this->license->name])) {
                return [
                    'versions' => [],
                ];
            }
            return [
                'versions' => collect($file["packages"][$this->license->name])->sortByDesc("version_normalized")->select(['version', 'time'])->toArray(),
            ];
        });
    }
}
