<?php

namespace App\Models;

use App\Enums\DonationMethod;
use App\Enums\DonationType;
use App\Enums\DonorType;
use Database\Factories\DonationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    /** @use HasFactory<DonationFactory> */
    use HasFactory;

    protected $fillable = [
        'donor_id',
        'project_id',
        'donor_name',
        'donor_type',
        'amount',
        'method',
        'type',
        'donated_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'donor_type' => DonorType::class,
        'method' => DonationMethod::class,
        'type' => DonationType::class,
        'amount' => 'decimal:2',
        'donated_at' => 'date',
    ];

    /**
     * @return BelongsTo<Donor, $this>
     */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
