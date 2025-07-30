<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;  // ✅ Add this import

class User extends Authenticatable
{   use HasApiTokens, HasFactory, Notifiable;
    use HasFactory, Notifiable, HasApiTokens;  // ✅ Add HasApiTokens here

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'organization',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function polygons()
    {
        return $this->hasMany(Polygon::class);
    }

    public function canEdit(): bool
    {
        return in_array($this->role, ['FGV', 'PLANTATION_OWNER']);
    }

    public function canViewAll(): bool
    {
        return in_array($this->role, ['MAPO', 'FGV']);
    }
        public function forestPolygons()
    {
        return $this->hasMany(ForestPolygon::class);
    }
}
