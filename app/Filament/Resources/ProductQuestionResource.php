<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductQuestionResource\Pages;
use App\Models\ProductQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductQuestionResource extends Resource
{
    protected static ?string $model = ProductQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Customer Engagement';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Question & Answer')
                    ->description('View the question and provide an answer.')
                    ->aside()
                    ->schema([
                        Forms\Components\Textarea::make('question')
                            ->required()
                            ->disabled()
                            ->helperText('The question asked by the customer.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->disabled()
                            ->helperText('The email of the person who asked the question.')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('answer')
                            ->helperText('Provide an answer to the question.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Associations')
                    ->description('Product and customer associated with this question.')
                    ->aside()
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->required()
                            ->disabled()
                            ->helperText('The product this question is about.'),

                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer.user', 'name')
                            ->disabled()
                            ->helperText('The customer who asked the question.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->sortable()
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('customer.user.name')
                    ->sortable()
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('question')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('answer')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\IconColumn::make('is_answered')
                    ->label('Answered')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email address copied')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_answered')
                    ->label('Answered Status')
                    ->boolean()
                    ->trueLabel('Answered')
                    ->falseLabel('Unanswered')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('answer'),
                        false: fn (Builder $query) => $query->whereNull('answer'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->slideOver(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListProductQuestions::route('/'),
            'edit' => Pages\EditProductQuestion::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereNull('answer')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::whereNull('answer')->count() > 0 ? 'warning' : 'primary';
    }
}
