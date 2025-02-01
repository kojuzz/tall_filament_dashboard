<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('Order Id')
                    ->searchable(),

                TextColumn::make('grand_total')
                    ->money('USD'),

                TextColumn::make('status')
                    ->badge()   // bedge အနေနဲ့ပြချင်လို့ပါ
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info', 
                        'processing' => 'gray', 
                        'shipped' => 'warning', 
                        'delivered' => 'success', 
                        'canceled' => 'danger'
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'new' => 'heroicon-m-sparkles', 
                        'processing' => 'heroicon-m-arrow-path', 
                        'shipped' => 'heroicon-m-truck', 
                        'delivered' => 'heroicon-m-check-badge', 
                        'canceled' => 'heroicon-m-x-circle'
                    })
                    ->sortable(),

                    TextColumn::make('payment_method')
                        ->sortable()
                        ->searchable(),

                    TextColumn::make('payment_status')
                        ->badge()   // bedge အနေနဲ့ပြချင်လို့ပါ
                        ->sortable()
                        ->searchable(),

                    TextColumn::make('created_at')->label('Order Date')
                        ->dateTime()
                        ->sortable(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Action::make('View Order')
                    ->url(fn (Order $record):string => OrderResource::getUrl('view', ['record' => $record]))
                    ->color('info')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
