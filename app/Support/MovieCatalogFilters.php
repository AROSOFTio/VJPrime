<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class MovieCatalogFilters
{
    public static function validationRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'genre' => ['nullable', 'string', 'max:120'],
            'language' => ['nullable', 'string', 'max:120'],
            'vj' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'in:movie,series'],
            'sort' => ['nullable', 'in:trending,new,rating'],
        ];
    }

    public static function normalize(array $filters): array
    {
        return [
            'search' => self::cleanString($filters['search'] ?? null),
            'genre' => self::cleanString($filters['genre'] ?? null),
            'language' => self::cleanString($filters['language'] ?? null),
            'vj' => self::cleanString($filters['vj'] ?? null),
            'type' => self::cleanString($filters['type'] ?? null),
            'sort' => self::cleanString($filters['sort'] ?? null) ?: 'trending',
        ];
    }

    public static function apply(Builder $query, array $filters): void
    {
        $normalized = self::normalize($filters);
        $searchOperator = self::searchOperator($query);

        if ($normalized['search']) {
            $search = '%'.$normalized['search'].'%';

            $query->where(function (Builder $builder) use ($search, $searchOperator) {
                $builder
                    ->where('title', $searchOperator, $search)
                    ->orWhere('series_title', $searchOperator, $search)
                    ->orWhere('description', $searchOperator, $search);
            });
        }

        if ($normalized['genre']) {
            $query->whereHas('genres', function (Builder $builder) use ($normalized): void {
                $builder->where('genres.slug', $normalized['genre']);

                if (self::isIntegerLike($normalized['genre'])) {
                    $builder->orWhere('genres.id', (int) $normalized['genre']);
                }
            });
        }

        if ($normalized['language']) {
            $query->whereHas('language', function (Builder $builder) use ($normalized): void {
                $builder->where('languages.code', $normalized['language']);

                if (self::isIntegerLike($normalized['language'])) {
                    $builder->orWhere('languages.id', (int) $normalized['language']);
                }
            });
        }

        if ($normalized['vj']) {
            $query->whereHas('vj', function (Builder $builder) use ($normalized): void {
                $builder->where('vjs.slug', $normalized['vj']);

                if (self::isIntegerLike($normalized['vj'])) {
                    $builder->orWhere('vjs.id', (int) $normalized['vj']);
                }
            });
        }

        if ($normalized['type']) {
            $query->where('content_type', $normalized['type']);
        }

        self::applySort($query, $normalized['sort']);
    }

    public static function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'new' => $query->latest('published_at'),
            'rating' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('published_at'),
            default => $query
                ->orderByDesc('weekly_views_count')
                ->orderByDesc('favorites_count')
                ->orderByDesc('published_at'),
        };
    }

    private static function searchOperator(Builder $query): string
    {
        return $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    private static function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = trim($value);

        return $cleaned !== '' ? $cleaned : null;
    }

    private static function isIntegerLike(?string $value): bool
    {
        return $value !== null && preg_match('/^\d+$/', $value) === 1;
    }
}
