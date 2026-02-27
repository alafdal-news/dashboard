<?php

namespace App\Filament\Resources\Articles\Tables;

use App\Services\FirebaseNotificationService;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
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

                // 5. Views (Visible by default)
                TextColumn::make('views')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('news_id')
                    ->label('ID')
                    ->width('5%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->defaultSort('news_id', 'desc')
            ->filters([])
            ->recordUrl(fn($record) => "https://alafdalnews.com/article/{$record->news_id}")
            ->openRecordUrlInNewTab()
            ->recordActions([
                Action::make('resendNotification')
                    ->icon('heroicon-o-bell-alert')
                    ->iconButton()
                    ->tooltip('Resend Notification')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Resend Push Notification')
                    ->modalDescription(fn ($record) => "Send a push notification again for: \"{$record->news_title}\"?")
                    ->modalSubmitActionLabel('Send Notification')
                    ->action(function ($record) {
                        try {
                            $service = app(FirebaseNotificationService::class);
                            $success = $service->sendArticleNotification(
                                $record->news_id,
                                $record->news_title
                            );

                            if ($success) {
                                Notification::make()
                                    ->title('Notification sent successfully!')
                                    ->success()
                                    ->duration(3000)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Failed to send notification')
                                    ->danger()
                                    ->duration(3000)
                                    ->send();
                            }
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Error sending notification')
                                ->body($e->getMessage())
                                ->danger()
                                ->duration(5000)
                                ->send();
                        }
                    }),
                Action::make('copyLink')
                    ->icon('heroicon-o-clipboard-document')
                    ->iconButton()
                    ->tooltip('Copy Link')
                    ->color('info')
                    ->action(function ($record, $livewire) {
                        $url = "https://alafdalnews.com/article/{$record->news_id}";
                        $title = str_replace(["'", "\n", "\r"], ["\\'", '', ''], $record->news_title);
                        $livewire->js("navigator.clipboard.writeText('{$title}\\n{$url}')");
                        Notification::make()
                            ->title('Link copied to clipboard!')
                            ->success()
                            ->duration(1200)
                            ->send();
                    }),
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
