<?php

namespace App\Filament\Pages;

use App\Enums\LicenseType;
use App\Models\License;
use App\Models\Team;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use function App\Support\enum_equals;
use function App\Support\tenant;

class ManageLicenses extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.licenses';

    protected static ?string $slug = 'licenses';

    #[Locked]
    public ?Team $record = null;

    public array $data = [];

    public function mount()
    {
        $this->record = tenant(Team::class);
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        if (Gate::denies('create', License::class)) {
            return $form;
        }

        return $form
            ->model($this->record)
            ->schema([
                Forms\Components\Section::make('Adicionar Licença')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(
                                fn(Forms\Get $get) => match ($get('type')) {
                                    LicenseType::Composer => 'vendor/package',
                                    LicenseType::Individual => 'Nome do Produto',
                                    LicenseType::Github => 'user/repo',
                                }
                            )
                            ->rule(
                                fn(Forms\Get $get): Closure => function (string $attribute, string $value, Closure $fail) use ($get) {
                                    if ($get('type') === LicenseType::Composer) {
                                        if (preg_match('/^[a-z0-9-]+\/[a-z0-9-]+$/', $value)) {
                                            return;
                                        }

                                        $fail('O nome do pacote deve seguir o formato "vendor/package".');
                                    }

                                    if ($get('type') === LicenseType::Github) {
                                        if (preg_match('/^[a-z0-9-]+\/[a-z0-9-]+$/', $value)) {
                                            return;
                                        }

                                        $fail('O nome do pacote deve seguir o formato "user/repo".');
                                    }
                                },
                            )
                            ->required(),

                        Forms\Components\ToggleButtons::make('type')
                            ->label('Tipo')
                            ->live()
                            ->options(LicenseType::class)
                            ->default(LicenseType::Composer)
                            ->required(),

                        Forms\Components\Fieldset::make()
                            ->columns(3)
                            ->schema([
                                Forms\Components\Placeholder::make('composer-instructions')
                                    ->label('Configurações do Composer')
                                    ->content('Para adicionar uma licença do tipo Composer, você deve informar as credenciais de acesso ao repositório privado. O produto será sub-licenciado usando o Satis. Cada membro do time receberá uma credencial de acesso individual. Não compartilhe essas credenciais com ninguém.')
                                    ->visible(fn(Forms\Get $get): bool => enum_equals($get('type'), LicenseType::Composer)),

                                Forms\Components\Placeholder::make('individual-instructions')
                                    ->label('Configurações Individuais')
                                    ->content('Para adicionar uma licença do tipo Individual, você deve informar as credenciais de acesso ao produto. Cada membro do time receberá uma credencial de acesso individual. Não compartilhe essas credenciais com ninguém.')
                                    ->visible(fn(Forms\Get $get): bool => enum_equals($get('type'), LicenseType::Individual)),

                                Forms\Components\Placeholder::make('github-instructions')
                                    ->label('Configurações do GitHub')
                                    ->content('Para adicionar uma licença do tipo GitHub, você deve informar a URL SSH do repositório privado e um Fine-grained Personal Access Token (PAT). Cada membro do time receberá uma credencial de acesso individual.')
                                    ->visible(fn(Forms\Get $get): bool => enum_equals($get('type'), LicenseType::Github)),

                                Forms\Components\TextInput::make('url')
                                    ->label(
                                        fn(Forms\Get $get) => match ($get('type')) {
                                            LicenseType::Composer => 'URL do Repositório Composer',
                                            LicenseType::Individual => 'URL do Produto',
                                            LicenseType::Github => 'URL SSH do Repositório',
                                        }
                                    )
                                    ->rule(
                                        fn(Forms\Get $get): Closure => function (string $attribute, string $value, Closure $fail) use ($get) {
                                            if ($get('type') !== LicenseType::Github) {
                                                return filter_var($value, FILTER_VALIDATE_URL);
                                            }

                                            if (preg_match('/^git@github.com:/', $value)) {
                                                return;
                                            }

                                            $fail('Utilize uma URL SSH válida para o repositório do GitHub.');
                                        },
                                    )
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('username')
                                    ->label(
                                        fn(Forms\Get $get) => match ($get('type')) {
                                            LicenseType::Composer => 'Username do Composer',
                                            LicenseType::Individual => 'Email de Acesso',
                                        }
                                    )
                                    ->required()
                                    ->visible(
                                        fn(Forms\Get $get): bool => !enum_equals($get('type'), LicenseType::Github)
                                    )
                                    ->columnStart(1),

                                Forms\Components\TextInput::make('password')
                                    ->label(
                                        fn(Forms\Get $get) => match ($get('type')) {
                                            LicenseType::Composer => 'Password do Composer',
                                            LicenseType::Individual => 'Senha de Acesso',
                                            LicenseType::Github => 'Personal Access Token (PAT)',
                                        }
                                    )
                                    ->password()
                                    ->revealable()
                                    ->rule(
                                        fn(Forms\Get $get): Closure => function (string $attribute, string $value, Closure $fail) use ($get) {
                                            if ($get('type') !== LicenseType::Github) {
                                                return;
                                            }

                                            if (preg_match('/^github_pat_/', $value)) {
                                                return;
                                            }

                                            $fail('O Personal Access Token (PAT) deve seguir o formato "github_pat_".');
                                        },
                                    )
                                    ->required(),
                            ]),
                    ])
                    ->footerActions([
                        Forms\Components\Actions\Action::make('create')
                            ->label('Adicionar')
                            ->action(
                                function (Team $record) {
                                    $license = $record->licenses()->create($this->form->getState());
                                    $this->form->fill();

                                    return $license;
                                }
                            ),
                    ]),
            ])
            ->statePath('data');
    }

    public function licensesInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Infolists\Components\RepeatableEntry::make('licenses')
                    ->label(false)
                    ->schema([
                        Infolists\Components\Section::make()
                            ->heading(fn(License $record) => $record->name)
                            ->description(fn(License $record) => $record->type->getLabel())
                            ->icon(fn(License $record) => $record->type->getIcon())
                            ->headerActions([
                                Infolists\Components\Actions\Action::make('versions')
                                    ->url(fn(License $record) => LicenseVersions::getUrl(['license' => $record->id]))
                                    ->visible(
                                        fn(License $record) => match ($record->type) {
                                            LicenseType::Individual => false,
                                            default => true,
                                        }
                                    ),
                            ])
                            ->columns(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('url')
                                    ->label(
                                        fn(License $record) => match ($record->type) {
                                            LicenseType::Composer => 'URL do Repositório',
                                            LicenseType::Individual => 'URL do Produto',
                                            LicenseType::Github => 'URL do Repositório',
                                        }
                                    )
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('username')
                                    ->label(
                                        fn(License $record) => match ($record->type) {
                                            LicenseType::Individual => 'Email',
                                            default => 'Username',
                                        }
                                    )
                                    ->copyable()
                                    ->getStateUsing(
                                        fn(License $record) => match ($record->type) {
                                            LicenseType::Individual => $record->username,
                                            default => '[Redacted]',
                                        }
                                    )
                                    ->visible(
                                        fn(License $record) => match ($record->type) {
                                            LicenseType::Github => false,
                                            default => true,
                                        }
                                    ),

                                Infolists\Components\TextEntry::make('password')
                                    ->label(
                                        fn(License $record) => match ($record->type) {
                                            LicenseType::Github => 'Personal Access Token',
                                            default => 'Senha',
                                        }
                                    )
                                    ->copyable()
                                    ->getStateUsing(
                                        fn(License $record) => match ($record->type) {
                                            LicenseType::Individual => $record->password,
                                            default => '[Redacted]',
                                        }
                                    ),
                            ]),
                    ]),
            ]);
    }
}
