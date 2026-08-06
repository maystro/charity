<?php

namespace App\Models;

use App\Enums\DonorType;
use Database\Factories\DonorFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Donor extends Model
{
    /** @use HasFactory<DonorFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'type',
        'city',
        'notes',
    ];

    protected $casts = [
        'type' => DonorType::class,
    ];

    /**
     * @return HasMany<Donation, $this>
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * إجمالي قيمة التبرعات النقدية للمتبرع.
     */
    protected function totalDonations(): Attribute
    {
        return Attribute::get(fn () => (float) $this->donations()->sum('amount'));
    }

    /**
     * عدد التبرعات المرتبطة بالمتبرع.
     */
    protected function donationsCount(): Attribute
    {
        return Attribute::get(fn () => (int) $this->donations()->count());
    }
}
