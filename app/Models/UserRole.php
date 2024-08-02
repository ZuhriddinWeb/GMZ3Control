<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'role_id',
        'view',
        'create',
        'update',
        'delete',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles')->withPivot('view', 'create', 'update', 'delete');
    }
    
    protected $casts = [
        'view' => 'boolean',
        'update' => 'boolean',
        'create' => 'boolean',
        'delete' => 'boolean',
        'role_id' => 'integer',
        'user_id' => 'integer',
    ];
}
