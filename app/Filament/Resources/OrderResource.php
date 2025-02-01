<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Filament\Resources\OrderResource\RelationManagers\AddressRelationManager;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    // Order Card
                    Section::make('Order Information')->schema([
                        // User Select Input Field
                        Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        // Payment Method Select Input Field
                        Select::make('payment_method')
                            ->options(['stripe' => 'Stripe', 'cod' => 'Cash on Delivery'])
                            ->required(),

                        // Payment Status Select Input Field
                        Select::make('payment_status')
                            ->options(['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed'])
                            ->default('pending')
                            ->required(),

                        // Order Status Toggle Buttons
                        ToggleButtons::make('status')->
                            options([
                                'new' => 'New', 
                                'processing' => 'Processing', 
                                'shipped' => 'Shipped', 
                                'delivered' => 'Delivered', 
                                'canceled' => 'Canceled'
                            ])
                            ->inline()  // Display the buttons in a single line
                            ->default('new')
                            ->required()
                            ->colors([
                                'new' => 'info', 
                                'processing' => 'gray', 
                                'shipped' => 'warning', 
                                'delivered' => 'success', 
                                'canceled' => 'danger'
                            ])
                            ->icons([
                                'new' => 'heroicon-m-sparkles', 
                                'processing' => 'heroicon-m-arrow-path', 
                                'shipped' => 'heroicon-m-truck', 
                                'delivered' => 'heroicon-m-check-badge', 
                                'canceled' => 'heroicon-m-x-circle'
                            ]),

                        // Currency Select Input Field
                        Select::make('currency')->options([
                            'usd' => 'USD',
                            'thb' => 'THB',
                            'eur' => 'EUR',
                            'inr' => 'INR'
                        ])
                            ->default('usd')
                            ->required(),

                        // Shipping Method Select Input Field
                        Select::make('shipping_method')->options([
                            'fedex' => 'FedEx',
                            'ups' => 'UPS',
                            'dhl' => 'DHL'
                        ]),

                        Textarea::make('notes')
                            ->columnSpanFull()
                            ->columnSpanFull()
                    ])->columns(2),

                    // Order Items Repeater Card
                    Section::make('Order Items')->schema([

                        // items က Order Model ထဲက hasMany ပါ, ဒီ Repeater သုံးတာနဲ့ Add to items button ပေါ်လာပါတယ်။
                        Repeater::make('items')->relationship()->schema([
                            
                            Select::make('product_id')
                                ->relationship('product', 'name')   // belongsTo
                                ->searchable()
                                ->preload()
                                ->required()
                                ->distinct()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()    // ရွေးပြီးသား product ကို ထပ်မရွေးတော့အောင်သုံးပါတယ်
                                ->reactive()    // User Input ပြောင်းတဲ့အခါမှာ Live Update လုပ်လိုတဲ့အခါ သုံးနိုင်တယ်
                                // unit_amount နေရာမှာ Product Selection ပြုလုပ်တဲ့အခါ Auto-Update Price (Unit Amount) လုပ်ပေးတယ်
                                ->afterStateUpdated(fn($state, Set $set) => $set('unit_amount', Product::find($state)?->price ?? 0))
                                // total_amount နေရာမှာ Quantity နှင့် Unit Amount အတွက် Auto-Update Total Amount လုပ်ပေးတယ်
                                ->afterStateUpdated(fn($state, Set $set) => $set('total_amount', Product::find($state)?->price ?? 0))
                                ->columnSpan(4),

                            TextInput::make('quantity')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->columnSpan(2)
                                ->reactive()
                                // $state က quantity value ဖြစ်ပြီး $get('unit_amount') က အပေါ် afterStateUpdated ကလာတာပါ။ ပြီးတော့ အဲဒီ ၂ ခုကို မြှောက်ပြီး 'total_amount' ထဲ သွားထည့်ပေးပါတယ်။
                                // အပေါ်က total_amount ကိုဖျက်လိုက်လို့ရသော်လည်း initial အခြေအနေအတွက် ထည့်ထားတာပါ။
                                ->afterStateUpdated(fn($state, Set $set, Get $get) => $set('total_amount', $state * $get('unit_amount'))),

                            TextInput::make('unit_amount')
                                ->numeric()
                                ->required()
                                ->disabled()    // afterStateUpdated ကလာမှာမို့လို့ disable ပေးထားပါတယ်
                                ->dehydrated()  // disabled လုပ်ထားသော်လည်း database ထဲဝင်အောင်လုပ်ပေးတယ်
                                ->columnSpan(3),

                            TextInput::make('total_amount')
                                ->numeric()
                                ->required()
                                ->dehydrated()
                                ->columnSpan(3)
                        ])->columns(12),

                        Placeholder::make('grand_total_placeholder')
                            ->label('Grand Total')
                            ->content(function (Get $get, Set $set) {
                                $total = 0;
                                if ( !$repeaters = $get('items') ) {
                                    return $total;
                                }
                                // Repeater::make ကနေ $repeaters ကိုရတာပါ
                                foreach($repeaters as $key => $repeater) {
                                    $total += $get("items.{$key}.total_amount");
                                }
                                $set('grand_total', $total);
                                return Number::currency($total, $get('currency'));
                            }),
                            // orders DB ထဲက grand_total ထဲကို သိမ်းဖို့ပါ။
                            Hidden::make('grand_total')
                                ->default(0)
                        ])

                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('grand_total')
                    ->numeric()
                    ->sortable()
                    ->money('USD'),

                TextColumn::make('payment_method')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('currency')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('shipping_method')
                    ->searchable()
                    ->sortable(),

                SelectColumn::make('status')
                    ->options([
                        'new' => 'New', 
                        'processing' => 'Processing', 
                        'shipped' => 'Shipped', 
                        'delivered' => 'Delivered', 
                        'canceled' => 'Canceled'
                    ])
                    ->searchable()
                    ->selectablePlaceholder(false)  // Select an option ကိုဖျေက်ဖို့ပါ၊
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
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
            AddressRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    // sidebar ထဲက order count ကိုပြပေးပါတယ်
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    // sidebar ထဲက order count color ကိုပြပေးပါတယ်
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() > 10 ? 'danger' : 'success';
    }
}
