<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $name
 * @property int $range_min
 * @property int $range_max
 * @property Collection $teams
 */
class Form extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'range_min',
        'range_max',
    ];

    /**
     * Get all of the teams that are assigned this form.
     *
     * @return MorphToMany
     */
    public function teams()
    {
        return $this->morphedByMany(Team::class, 'model', 'model_has_forms');
    }

    /**
     * Get all of the values that are assigned this form.
     *
     * @return MorphToMany
     */
    public function values()
    {
        return $this->morphedByMany(Value::class, 'model', 'model_has_forms');
    }

    /**
     * @return Team
     */
    public function getCachedTeam(): Team
    {
        $cacheKey = sprintf('%s:%d:team', $this->getTable(), $this->getKey());

        return Cache::sear($cacheKey, fn() => $this->teams()->first());
    }
}
