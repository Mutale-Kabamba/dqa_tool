<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'project_officer_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function projectOfficer()
    {
        return $this->belongsTo(User::class, 'project_officer_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }
}
