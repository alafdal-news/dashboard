<?php

namespace App\Filament\Resources\Articles\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                ToggleColumn::make('active')
                    ->label('Active')
                    ->sortable()
                    ->onColor('success')
                    ->offColor('danger')
                    ->updateStateUsing(function ($record, $state) {
                        $newValue = $state ? '1' : '0';
                        $record->update(['active' => $newValue]);
                        return $newValue;
                    }),

                TextColumn::make('title')
                    ->label('Title')
                    ->html()
                    ->searchable(query: function ($query, string $search) {
                        return $query->where('news_title', 'like', "%{$search}%");
                    })
                    ->formatStateUsing(fn(string $state): string => Str::limit(strip_tags($state), 50))
                    ->sortable(),

                TextColumn::make('addBy')
                    ->label('Added By')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // 4. Date
                TextColumn::make('news_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('news_time')
                    ->label('Time')
                    ->time('h:i A')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                // 5. Views (Hidden by default to keep interface clean)
                TextColumn::make('views')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('news_id')
                    ->label('ID')
                    ->width('5%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->defaultSort('news_id', 'desc')
            ->filters([])
            ->recordUrl(null)
            ->recordActions([
                EditAction::make()
                    ->icon('heroicon-s-pencil-square')
                    ->iconButton()
                    ->tooltip('Edit News'),
            ], position: RecordActionsPosition::BeforeColumns);
        // ->toolbarActions([
        //     BulkActionGroup::make([
        //         DeleteBulkAction::make(),
        //     ]),
        // ]);
    }
}
