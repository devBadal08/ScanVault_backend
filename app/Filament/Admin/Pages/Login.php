<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

class Login extends BaseLogin
{
    protected static string $view = 'auth.custom-login'; 
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Username or Email')
                    ->required()
                    ->autocomplete('username'),

                TextInput::make('password')
                    ->password()
                    ->required()
                    ->autocomplete('current-password'),
            ]);
    }
}
