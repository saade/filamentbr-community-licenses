<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Database\Eloquent\Model;

class RegisterTeam extends RegisterTenant
{

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->autofocus(),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $user = User::find(1);

        return $user->ownedTeams()->create($data);
    }

    public static function getLabel(): string
    {
        return 'Criar Time';
    }
}
