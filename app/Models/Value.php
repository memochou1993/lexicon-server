<?php

namespace App\Models;

use App\Models\Traits\HasForms;
use App\Models\Traits\HasLanguages;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $text
 * @property Key $key
 * @property Collection $languages
 * @property Collection $forms
 */
class Value extends Model
{
    use HasLanguages;
    use HasForms;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'text',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'key_id',
        'laravel_through_key',
    ];

    /**
     * Get the key that owns the value.
     *
     * @return BelongsTo
     */
    public function key()
    {
        return $this->belongsTo(Key::class);
    }

    /**
     * @return Project
     */
    public function getCachedProject(): Project
    {
        $cacheKey = sprintf('%s:%d:project', $this->getTable(), $this->getKey());

        return Cache::sear($cacheKey, fn() => $this->key->project);
    }
}
