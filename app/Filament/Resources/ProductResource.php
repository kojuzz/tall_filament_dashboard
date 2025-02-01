<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([

                    Section::make('Product Information')->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            // onBlur: true ဆိုတာက input field ကနေ focus ပြုတ်သွားတဲ့အခါမှသာ value ပြောင်းလဲမှုကို track လုပ်မယ်လို့ ဆိုလိုတာပါ
                            ->live(onBlur: true)
                            // ဒီ method က input field ရဲ့ state (တန်ဖိုး) ပြောင်းလဲသွားတဲ့အခါမှာ သတ်မှတ်ထားတဲ့ callback function ကို run ဖို့အတွက် သုံးပါတယ်
                            ->afterStateUpdated(function(string $operation, $state, Set $set) {
                                // create မဟုတ်ရင် (ဥပမာ edit လုပ်နေရင်) callback function ကို ဆက်မလုပ်တော့ပါဘူး။ (edit မှာပါ slug ပြောင်းချင်ရင် if ကို ပိတ်ထားပါ)
                                if($operation !== 'create') {
                                    return;
                                }
                                // name field ရဲ့ value ($state) ကို Str::slug() သုံးပြီး slug format ပြောင်းပါတယ်။ ပြီးရင် slug field ရဲ့ value ကို update လုပ်ပါတယ်
                                $set('slug', Str::slug($state));
                            }),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->disabled()
                            // Form Field တစ်ခုကို Disabled လုပ်ထားတဲ့အခါ Laravel Request ထဲမှာ Data မပါဘူး, User Input ပြုလုပ်ခွင့်မပြုချင်ပေမယ့် Backend မှာ Data ကို ပို့ပြီး Update ချင်တဲ့အခါ ->dehydrated() သုံးတယ်
                            ->dehydrated()
                            // slug field ကို products table ထဲမှာ unique ဖြစ်အောင် စစ်ဆေးဖို့အတွက် သုံးပြီး edit mode မှာ လက်ရှိ record ကို ignore လုပ်ဖို့အတွက် သုံးပါတယ်
                            ->unique(Product::class, 'slug', ignoreRecord: true),

                        MarkdownEditor::make('description')
                            // columns(2) ထဲမပါပဲ full width ရအောင်လုပ်တာပါ
                            ->columnSpanFull()
                            // markdownEditor ကပါလာတဲ့ file တွေကို storage/app/public/products directory ထဲမှာ သိမ်းဆည်းဖို့ပါ
                            ->fileAttachmentsDirectory('products')
                    ])->columns(2),

                    Section::make('Images')->schema([
                        FileUpload::make('images')
                            ->multiple()
                            ->directory('products')
                            ->maxFiles(5)
                            ->reorderable()
                    ])

                ])->columnSpan(2),
                
                Group::make()->schema([

                    Section::make('Price')->schema([
                        TextInput::make('price')->numeric()->required()->prefix('USD')
                    ]),

                    Section::make('Associations')->schema([
                        Select::make('category_id')
                            ->required()
                            ->searchable()
                            // ဒီ method က dropdown options တွေကို preload (ကြိုတင်လုပ်ဆောင်ခြင်း) လုပ်ဖို့အတွက် သုံးပါတယ်
                            ->preload()
                            ->relationship('category', 'name'),
                        Select::make('brand_id')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship('brand', 'name')
                    ]),

                    Section::make('Status')->schema([
                        Toggle::make('in_stock')->required()->default(true),
                        Toggle::make('is_active')->required()->default(true),
                        Toggle::make('is_featured')->required(),
                        Toggle::make('on_sale')->required(),
                    ])
                ])->columnSpan(1)

            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('category.name')->sortable(),
                TextColumn::make('brand.name')->sortable(),
                TextColumn::make('price')->sortable()->money('USD'),

                IconColumn::make('is_featured')->boolean(),
                IconColumn::make('on_sale')->boolean(),
                IconColumn::make('in_stock')->boolean(),
                IconColumn::make('is_active')->boolean(),

                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->relationship('category', 'name'),
                SelectFilter::make('brand')->relationship('brand', 'name'),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
