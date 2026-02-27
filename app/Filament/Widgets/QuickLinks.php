<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Analytics;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Authors\AuthorResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Widgets\Widget;

class QuickLinks extends Widget
{
    protected string $view = 'filament.widgets.quick-links';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 0;

    public function getLinks(): array
    {
        $links = [];

        $resources = [
            'News' => ['resource' => ArticleResource::class, 'icon' => 'heroicon-o-newspaper'],
            'Authors' => ['resource' => AuthorResource::class, 'icon' => 'heroicon-o-user-group'],
            'Categories' => ['resource' => CategoryResource::class, 'icon' => 'heroicon-o-tag'],
            'Users' => ['resource' => UserResource::class, 'icon' => 'heroicon-o-users'],
        ];

        foreach ($resources as $label => $config) {
            if ($config['resource']::canAccess()) {
                $links[] = [
                    'label' => $label,
                    'url' => $config['resource']::getUrl(),
                    'icon' => $config['icon'],
                ];
            }
        }

        if (Analytics::canAccess()) {
            $links[] = [
                'label' => 'Analytics',
                'url' => Analytics::getUrl(),
                'icon' => 'heroicon-o-presentation-chart-line',
            ];
        }

        return $links;
    }
}
