<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrders extends BaseWidget
{
    // full width လုပ်ဖို့အတွက်ပါ
    protected int | string | array $columnSpan = 'full';

    // OrderState widget ကို အပေါ်မှာ ပြအောင်လို့ပါ
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(OrderResource::getEloquentQuery())
            ->defaultPaginationPageOption(5)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('Order Id')
                    ->searchable(),

                TextColumn::make('user.name')
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
            ->actions([
                Action::make('View Order')
                    ->url(fn (Order $record):string => OrderResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-m-eye'),
            ]);
    }
}
