<?php

namespace App\Filament\Resources\Articles\Schemas;

use App\Models\Author;
use App\Models\Category;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('news_title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->autocomplete(false)
                    ->autofocus(),

                Select::make('id_cat')
                    ->label('Type')
                    ->options(Category::all()->pluck('name', 'id'))
                    ->searchable()
                    ->default(20)
                    ->required(),

                Select::make('author')
                    ->label('Author')
                    ->options(Author::all()->pluck('name', 'name'))
                    ->searchable()
                    ->placeholder('No specific author')
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->createOptionUsing(function (array $data) {
                        return Author::create($data)->name;
                    })
                    ->dehydrateStateUsing(fn($state) => $state ?? ''),

                DatePicker::make('news_date')
                    ->label('Date')
                    ->default(now())
                    ->required()
                    ->hidden(fn($record) => $record === null),

                TimePicker::make('news_time')
                    ->label('Time')
                    ->default(now())
                    ->required()
                    ->hidden(fn($record) => $record === null),

                // 1. COVER IMAGE (Main)
                // Model accessor returns full path for preview, mutator handles save
                FileUpload::make('image')
                    ->label('Cover Image')
                    ->image()
                    ->disk('public')
                    ->columnSpanFull()
                    ->directory('uploads/news/temp')
                    ->visibility('public'),


                // 2. GALLERY (Multiple Images)
                // ArticleImage model accessor/mutator handles path conversion
                Repeater::make('images')
                    ->relationship('images')
                    ->label('Gallery')
                    ->schema([
                        FileUpload::make('image_name')
                            ->label('Image')
                            ->image()
                            ->disk('public')
                            ->directory('uploads/news/temp')
                            ->visibility('public')
                            ->required(),

                        Hidden::make('thumb_name')
                            ->default('')
                            ->dehydrateStateUsing(fn($state) => $state ?? ''),

                        Hidden::make('active')
                            ->default('1')
                            ->dehydrateStateUsing(fn($state) => '1'),

                        Hidden::make('coverpage')
                            ->default('0')
                            ->dehydrateStateUsing(fn($state) => '0'),
                    ])
                    ->grid(2)
                    ->addActionLabel('Add Image')
                    ->addActionAlignment('start')
                    ->columnSpanFull()
                    ->defaultItems(0),


                Group::make([
                    Checkbox::make('important')
                        ->label('Important')
                        ->default(false)
                        ->dehydrateStateUsing(fn($state) => $state ? '1' : '0'),

                    Checkbox::make('show_slider')
                        ->label('Slider')
                        ->default(false)
                        ->dehydrateStateUsing(fn($state) => $state ? '1' : '0'),

                    Checkbox::make('notification')
                        ->label('Notification')
                        ->default(false)
                        ->dehydrateStateUsing(fn($state) => $state ? '1' : '0'),
                ])->columns(1),



                RichEditor::make('news_desc')
                    ->label('Description')
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'link',
                        'h2',
                        'h3',
                        'bulletList',
                        'orderedList',
                    ]),


                // HIDDEN AUDIT FIELDS
                Hidden::make('user_id')
                    ->default(fn() => Auth::id())
                    ->dehydrated(true),


                Hidden::make('addBy')
                    ->default(fn() => Auth::user()?->name ?? 'System')
                    ->dehydrated(true),

                Hidden::make('updateBy')
                    ->default(fn() => Auth::user()?->name ?? 'System')
                    ->dehydrated(true),

                Hidden::make('addDate')
                    ->default(now())
                    ->dehydrated(true),

                Hidden::make('updateDate')
                    ->default(now())
                    ->dehydrated(true),

                Hidden::make('views')
                    ->default(0)
                    ->dehydrated(true),

            ])
            ->dense();
    }
}
