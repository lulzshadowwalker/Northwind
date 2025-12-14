<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Shop Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Details')
                    ->description('Manage product information, and visibility.')
                    ->aside()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->translatable(),

                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->translatable()
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->required(),
                    ]),

                Forms\Components\Section::make('Media')
                    ->description('Upload a cover image and additional product images.')
                    ->aside()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('cover')
                            ->collection(Product::MEDIA_COLLECTION_COVER)
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios(['1:1', '4:3', '16:9'])
                            ->maxSize(10240)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('The main cover image for the product.')
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('images')
                            ->collection(Product::MEDIA_COLLECTION_IMAGES)
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios(['1:1', '4:3', '16:9'])
                            ->maxSize(10240)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Additional images for the product gallery.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Pricing')
                    ->description('Manage the product pricing.')
                    ->aside()
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('SAR')
                            ->helperText('The base price of the product.'),

                        Forms\Components\TextInput::make('sale_price')
                            ->numeric()
                            ->prefix('SAR')
                            ->helperText('An optional sale price. If set, this will be displayed as the current price.'),

                        Forms\Components\DateTimePicker::make('sale_end_date')
                            ->label('Sale End Date')
                            ->helperText('The date and time when the sale ends.'),
                    ]),

                Forms\Components\Section::make('Associations')
                    ->description('Assign categories and collections to this product.')
                    ->aside()
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('The primary category for this product.'),

                        Forms\Components\Select::make('collections')
                            ->relationship('collections', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('The collections this product belongs to.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')
                    ->collection(Product::MEDIA_COLLECTION_COVER)
                    ->label('Cover'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn (Product $record) => $record->name),

                Tables\Columns\TextColumn::make('price')
                    ->money('sar')
                    // ->getStateUsing(fn (Product $record) => $record->price->getAmount())
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_price')
                    ->money('sar')
                    // ->getStateUsing(fn (Product $record) => $record->sale_price->getAmount())
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('collections.name')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->limitList(2)
                    ->expandableLimitedList(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('reviews_count')->counts('reviews')
                    ->label('Reviews')
                    ->badge()
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload(),

                SelectFilter::make('collections')
                    ->relationship('collections', 'name')
                    ->multiple()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver(),

                Tables\Actions\EditAction::make()
                    ->slideOver(),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() > 0 ? 'primary' : 'gray';
    }
}
