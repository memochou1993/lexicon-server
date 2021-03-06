<?php

namespace App\Models;

use App\Models\Traits\HasCache;
use App\Models\Traits\HasLanguages;
use App\Models\Traits\HasSetting;
use App\Models\Traits\HasTokens;
use App\Models\Traits\HasUsers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Team $team
 * @property Setting $setting
 * @property Collection $keys
 * @property Collection $hooks
 * @property Collection $values
 */
class Project extends Authenticatable
{
    use HasFactory;
    use HasApiTokens, HasTokens {
        HasTokens::tokens insteadof HasApiTokens;
    }
    use HasCache;
    use HasUsers;
    use HasLanguages;
    use HasSetting;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'team_id',
    ];

    /**
     * Get the team that owns the project.
     *
     * @return BelongsTo
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the keys for the project.
     *
     * @return HasMany
     */
    public function keys(): HasMany
    {
        return $this->hasMany(Key::class);
    }

    /**
     * Get all of the values for the project.
     *
     * @return HasManyThrough
     */
    public function values(): HasManyThrough
    {
        return $this->hasManyThrough(Value::class, Key::class);
    }

    /**
     * Get the hooks for the project.
     *
     * @return HasMany
     */
    public function hooks(): HasMany
    {
        return $this->hasMany(Hook::class);
    }

    /**
     * @return Team
     */
    public function getCachedTeam(): Team
    {
        $tag = sprintf('%s:%', $this->getTable(), $this->getKey());

        return Cache::tags($tag)->sear('team', fn() => $this->team);
    }
}
