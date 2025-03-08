<?php

namespace App\Filament\Pages\Tenancy;

use App\Actions\Cancelnvitation;
use App\Actions\InviteTeamMember;
use App\Actions\RemoveTeamMember;
use App\Models\TeamInvitation;
use App\Models\User;
use Closure;
use Filament\AvatarProviders\UiAvatarsProvider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

use function App\Support\html;

/**
 * @property-read Forms\ComponentContainer $form
 * @property-read Forms\ComponentContainer $inviteForm
 */
class EditTeam extends EditTenantProfile
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static string $view = 'filament.pages.tenancy.edit-team';

    public array $invitation = [];

    public function mount(): void
    {
        $this->inviteForm->fill();

        parent::mount();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Nome do time')
                    ->description('O nome do time e informações adicionais.')
                    ->aside()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome do time')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('seats')
                            ->label('Número de assentos')
                            ->numeric()
                            ->rule(
                                fn (): Closure => function (string $attribute, int $value, Closure $fail) {
                                    if ($value < ($count = $this->tenant->users->count())) {
                                        $fail("O número de assentos deve ser maior ou igual ao número de membros do time ({$count}).");
                                    }
                                },
                            ),
                    ])
                    ->footerActions([
                        Forms\Components\Actions\Action::make('save')
                            ->label('Salvar')
                            ->action('save'),
                    ])
                    ->footerActionsAlignment(Alignment::End),
            ])
            ->statePath('data');
    }

    public function inviteForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Adicionar membro')
                    ->description('Adicionar um novo membro ao time, permitindo que ele acesse as licenças e recursos do time.')
                    ->aside()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Placeholder::make('add-member-instructions')
                            ->label('Por favor, insira o endereço de e-mail da pessoal que você deseja adicionar ao time. O convite será enviado automaticamente.'),

                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required(),
                    ])
                    ->footerActions([
                        Forms\Components\Actions\Action::make('invite')
                            ->label('Enviar convite')
                            ->action('invite'),
                    ])
                    ->footerActionsAlignment(Alignment::End),
            ])
            ->statePath('invitation');
    }

    public function pendingInvitationsInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->tenant)
            ->schema([
                Infolists\Components\Section::make('Convites pendentes')
                    ->description('Essas pessoas foram convidadas para sua equipe e receberam um e-mail de convite. Elas podem se juntar à equipe aceitando o convite por e-mail.')
                    ->aside()
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('teamInvitations')
                            ->label(false)
                            ->columns(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('email')
                                    ->label(false),

                                Infolists\Components\Actions::make([])
                                    ->actions([
                                        Infolists\Components\Actions\Action::make('cancel-invitation')
                                            ->label('Cancelar')
                                            ->link()
                                            ->requiresConfirmation()
                                            ->action(
                                                fn (TeamInvitation $record) => app(Cancelnvitation::class)->cancel($record)
                                            ),
                                    ])
                                    ->alignEnd(),
                            ]),
                    ])->visible(fn () => $this->tenant->teamInvitations->isNotEmpty()),
            ]);
    }

    public function membersInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->tenant)
            ->schema([
                Infolists\Components\Section::make('Membros do time')
                    ->description('Todas as pessoas que fazem parte do time.')
                    ->aside()
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('users')
                            ->label(false)
                            ->columns(2)
                            ->schema([
                                Infolists\Components\Split::make([])
                                    ->verticallyAlignCenter()
                                    ->schema([
                                        Infolists\Components\ImageEntry::make('avatar')
                                            ->label(false)
                                            ->circular()
                                            ->size(50)
                                            ->state(
                                                fn (User $record, UiAvatarsProvider $provider) => $provider->get($record)
                                            )
                                            ->grow(false),

                                        Infolists\Components\TextEntry::make('email')
                                            ->label(fn (User $record) => $record->name)
                                            ->extraEntryWrapperAttributes(['class' => html('[&>.grid]:gap-y-0')]),
                                    ]),

                                Infolists\Components\Actions::make([])
                                    ->actions([
                                        Infolists\Components\Actions\Action::make('remove-member')
                                            ->label('Remover')
                                            ->link()
                                            ->requiresConfirmation()
                                            ->action(
                                                fn (User $record) => app(RemoveTeamMember::class)->remove($this->tenant, $record)
                                            ),
                                    ])
                                    ->verticallyAlignCenter()
                                    ->alignEnd(),
                            ]),
                    ])->visible(fn () => $this->tenant->users->isNotEmpty()),
            ]);
    }

    protected function getForms(): array
    {
        return [
            'form',
            'inviteForm',
        ];
    }

    public function invite(): void
    {
        $data = $this->inviteForm->getState();

        try {
            app(InviteTeamMember::class)->invite(
                team: $this->tenant,
                email: $data['email']
            );

            $this->inviteForm->fill();

            Notification::make()
                ->title('Convite enviado')
                ->body(sprintf('O convite foi enviado para o email %s.', $data['email']))
                ->success()
                ->send();

        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Erro ao enviar convite')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getLabel(): string
    {
        return 'Editar time';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Editar '.$this->tenant->name;
    }
}
