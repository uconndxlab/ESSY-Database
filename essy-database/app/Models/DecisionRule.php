<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DecisionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_code',
        'frequency',
        'domain',
        'decision_text'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get decision text for a specific item code and frequency combination
     *
     * @param string $itemCode
     * @param string $frequency
     * @return string|null
     */
    public static function getDecisionText(string $itemCode, string $frequency): ?string
    {
        $rule = self::getByItemAndFrequency($itemCode, $frequency);
        return $rule?->decision_text;
    }

    /**
     * Get decision rule by item code and frequency
     *
     * @param string $itemCode
     * @param string $frequency
     * @return DecisionRule|null
     */
    public static function getByItemAndFrequency(string $itemCode, string $frequency): ?self
    {
        return self::where('item_code', $itemCode)
                   ->where('frequency', $frequency)
                   ->first();
    }

    /**
     * Scope to filter by item code
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $itemCode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByItemCode($query, string $itemCode)
    {
        return $query->where('item_code', $itemCode);
    }

    /**
     * Scope to filter by frequency
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $frequency
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Scope to filter by domain
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $domain
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }

    /**
     * Get all available frequencies
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getAvailableFrequencies()
    {
        return self::distinct('frequency')->pluck('frequency');
    }

    /**
     * Get all available domains
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getAvailableDomains()
    {
        return self::distinct('domain')->pluck('domain');
    }

    /**
     * Get all item codes for a specific domain
     *
     * @param string $domain
     * @return \Illuminate\Support\Collection
     */
    public static function getItemCodesByDomain(string $domain)
    {
        return self::where('domain', $domain)->distinct('item_code')->pluck('item_code');
    }
}