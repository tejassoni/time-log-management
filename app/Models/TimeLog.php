<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;


class TimeLog extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'work_date',
        'minutes',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function duration(): Attribute
    {
        return Attribute::get(fn () => sprintf('%02d:%02d', intdiv($this->minutes, 60), $this->minutes % 60));
    }
}
