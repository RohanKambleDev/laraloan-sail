<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Loan extends Model
{
    /**
     * as per requirement doc
     * All the loans will be assumed to have a “weekly” repayment frequency.
     */
    const FREQUENCY = 'weekly';

    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'loans';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are not mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id'
    ];

    /**
     * get carbon object of created_at with 'Y-m-d h:i:s' format
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->format('Y-m-d h:i:s')
        );
    }

    /**
     * get carbon object of updated_at with 'Y-m-d h:i:s' format
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->format('Y-m-d h:i:s')
        );
    }

    // protected function statusId(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn ($id) => Status::getSlugById($id)
    //     );
    // }

    public function scheduledPayments()
    {
        return $this->hasMany(ScheduledPayment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
