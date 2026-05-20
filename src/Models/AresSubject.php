<?php

declare(strict_types=1);

namespace NyonCode\Ares\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use NyonCode\Ares\Data\SubjectData;

/**
 * @property string $ic
 * @property string $name
 * @property string|null $city
 * @property Carbon $indexed_at
 */
final class AresSubject extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'ic';

    protected $keyType = 'string';

    protected $table = 'ares_subjects';

    protected $fillable = [
        'ic',
        'name',
        'city',
        'indexed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'indexed_at' => 'datetime',
        ];
    }

    public function toSubjectData(): SubjectData
    {
        return new SubjectData(
            ic: $this->ic,
            name: $this->name,
            city: $this->city,
        );
    }
}
