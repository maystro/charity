<?php

namespace App\Models;

use Database\Factories\ProjectPhaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectPhase extends Model
{
    /** @use HasFactory<ProjectPhaseFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'cost',
        'sort_order',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
