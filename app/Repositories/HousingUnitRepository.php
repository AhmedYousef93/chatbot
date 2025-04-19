<?php

namespace App\Repositories;

use App\Models\ChatHistory;
use App\Models\HousingUnit;

class HousingUnitRepository
{
    public function getPropertiesByFilters($input, $limit = 2): array
    {

        \Log::info('Filters:', ['input' => $input]);

        if (!str_contains($input, '=')) {
            return [];
        }
        $query = HousingUnit::query();
        if (strpos($input, '=') !== false) {
            $filters = array_map('trim', explode(',', $input));
            foreach ($filters as $filter) {
                [$key, $value] = array_map('trim', explode('=', $filter, 2));

                if ($key === 'location') {
                    $query->where('location', 'like', "%{$value}%");
                }
                if (preg_match('/<=(\d+)/', $value, $match)) {
                    $query->where('price', '<=', (int)$match[1]);
                } elseif (preg_match('/>=(\d+)/', $value, $match)) {
                    $query->where('price', '>=', (int)$match[1]);
                } elseif (preg_match('/^\d+$/', $value)) {
                    $query->where('price', '<=', (int)$value);
                } elseif ($key === 'rooms') {
                    $query->where('rooms', (int)$value);

                } elseif ($key === 'name') {
                    $query->where('name', 'like', "%{$value}%");

                } elseif ($key === 'description') {
                    $query->where('description', 'like', "%{$value}%");

                } elseif ($key === 'feature') {
                    $features = explode(',', $value);
                    $query->where(function ($q) use ($features) {
                        foreach ($features as $feature) {
                            $q->orWhereJsonContains('features', trim($feature));
                        }
                    });

                } elseif (in_array($key, ['wifi', 'parking', 'ac'])) {
                    if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                        $query->whereJsonContains('features', $key);
                    }
                }
            }
        }
        $properties = $query->take($limit)->get(['id', 'name', 'price', 'location', 'rooms']);

        return $properties->isEmpty() ? [] : $properties->toArray();
    }

    public function createChatHistory(array $queryArray, $limit): void
    {
        $array = array_merge($queryArray, ['after_number' => $limit]);
        ChatHistory::create($array);
    }

    public function callHistoryData(): array
    {
        $history = ChatHistory::orderby('id', 'desc')->first();
        $limit   = $history->after_number + 2;

        $history->update(['after_number' => $limit]);
        $query = HousingUnit::query();

        if ($history->location) {
            $query->where('location', $history->location);
        }

        if ($history->price) {
            if (preg_match('/(<=|>=|<|>|=)?\s*(\d+)/', $history->price, $matches)) {
                $operator = $matches[1] ? : '<='; // لو مفيش operator، اعتبره <=
                $value    = $matches[2];
                $query->where('price', $operator, $value);
            }
        }
        if ($history->room) {
            $query->where('rooms', $history->room);
        }

        if (!empty($history->features)) {
            foreach ($history->features as $feature) {
                $query->whereJsonContains('features', $feature);
            }
        }
        return $query->get()->toArray();
    }
}
