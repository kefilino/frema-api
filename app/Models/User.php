<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'phone', 'description', 'skills',
        'university', 'major', 'gender', 'work_hour_start', 'work_hour_end', 'address',
        'linkedin', 'instagram', 'github'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    public function image()
    {
        return $this->hasOne(Image::class)->withDefault([
            'src' => '',
        ]);
    }

    public function album()
    {
        return $this->hasMany(Album::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function portfolios()
    {
        return $this->hasMany(Portfolio::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function purchases()
    {
        return $this->hasMany(Transaction::class, 'buyer_id');
    }
    
    public function reviewsAsClient()
    {
        return $this->hasMany(Review::class, 'client_id');
    }
    
    public function reviewsAsFreelancer()
    {
        return $this->hasMany(Review::class, 'freelancer_id');
    }

    public function sales()
    {
        return $this->hasMany(Transaction::class, 'seller_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
