<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\shippingAgent;
use App\Models\Yard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ReferenceDataService
{
    /**
     * Cache TTL in seconds (24 hours).
     * Invalidation is strictly managed via Model Observers on create, update, delete.
     */
    public const TTL_SECONDS = 86400;

    public const KEY_YARDS_ALL = 'reference_yards_all';
    public const KEY_YARDS_PLUCK = 'reference_yards_pluck';
    public const KEY_SHIPPING_AGENTS_ALL = 'reference_shipping_agents_all';
    public const KEY_SHIPPING_AGENTS_PLUCK = 'reference_shipping_agents_pluck';
    public const KEY_BRANCHES_ALL = 'reference_branches_all';
    public const KEY_BRANCHES_PLUCK = 'reference_branches_pluck';

    /**
     * Get all yards cached.
     */
    public static function getYards(): Collection
    {
        return Cache::remember(self::KEY_YARDS_ALL, self::TTL_SECONDS, function () {
            return Yard::all();
        });
    }

    /**
     * Get yards pluck title-id cached.
     */
    public static function getYardsPluck(): Collection
    {
        return Cache::remember(self::KEY_YARDS_PLUCK, self::TTL_SECONDS, function () {
            return Yard::pluck('title', 'id');
        });
    }

    /**
     * Get all shipping agents cached.
     */
    public static function getShippingAgents(): Collection
    {
        return Cache::remember(self::KEY_SHIPPING_AGENTS_ALL, self::TTL_SECONDS, function () {
            return shippingAgent::all();
        });
    }

    /**
     * Get shipping agents pluck title-id cached.
     */
    public static function getShippingAgentsPluck(): Collection
    {
        return Cache::remember(self::KEY_SHIPPING_AGENTS_PLUCK, self::TTL_SECONDS, function () {
            return shippingAgent::pluck('title', 'id');
        });
    }

    /**
     * Get all branches cached.
     */
    public static function getBranches(): Collection
    {
        return Cache::remember(self::KEY_BRANCHES_ALL, self::TTL_SECONDS, function () {
            return Branch::all();
        });
    }

    /**
     * Get branches pluck name-id cached.
     */
    public static function getBranchesPluck(): Collection
    {
        return Cache::remember(self::KEY_BRANCHES_PLUCK, self::TTL_SECONDS, function () {
            return Branch::pluck('name', 'id');
        });
    }

    /**
     * Invalidate Yard cache keys.
     */
    public static function clearYardsCache(): void
    {
        Cache::forget(self::KEY_YARDS_ALL);
        Cache::forget(self::KEY_YARDS_PLUCK);
    }

    /**
     * Invalidate Shipping Agent cache keys.
     */
    public static function clearShippingAgentsCache(): void
    {
        Cache::forget(self::KEY_SHIPPING_AGENTS_ALL);
        Cache::forget(self::KEY_SHIPPING_AGENTS_PLUCK);
    }

    /**
     * Invalidate Branch cache keys.
     */
    public static function clearBranchesCache(): void
    {
        Cache::forget(self::KEY_BRANCHES_ALL);
        Cache::forget(self::KEY_BRANCHES_PLUCK);
    }
}
