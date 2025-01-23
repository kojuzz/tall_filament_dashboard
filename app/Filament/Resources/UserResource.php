<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\FormsComponent;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // User Name Text Input ပါ
                Forms\Components\TextInput::make('name')->required(),

                // User Email Text Input
                // unique(ignoreRecord: true) => Edit လုပ်တဲ့ကခါ အဲဒီ လုပ်ခံရတဲ့ email ကို ignore လုပ်တာပါ
                Forms\Components\TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    -> required(),

                // DatePicker Input
                Forms\Components\DatePicker::make('email_verified_at')
                    ->label('Email Verified At')
                    ->default(now()),

                // Password Input
                Forms\Components\TextInput::make('password')
                    // ->label('Password')
                    ->password()
                    // dehydrated ဆိုတာ Laravel’s Form Components မှာ Form Data ကို Save လုပ်တဲ့အခါ, အချို့ Input Fields များကို Backend မှာ Data ထည့်မထည့် ဆုံးဖြတ်စေဖို့ သုံးပါတယ်
                    // fn($state) => filled($state) => Password Field ထဲမှာ Value ရှိရင်သာ Backend သို့ Data ကို Send လုပ်မယ်။ Value မရှိရင် Data ကို Ignore လုပ်တယ်
                    ->dehydrated(fn($state) => filled($state))
                    // CreateRecord Component (Create Form) မှာ Password Field က required ဖြစ်တယ်။ Edit Form (e.g., Update) မှာ Password Field ကို Required မဖြစ်အောင်လုပ်တယ်။
                    ->required(fn (Page $livewire): bool => $livewire instanceof CreateRecord),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
