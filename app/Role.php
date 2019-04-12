<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'slug'];

    /**
     * Sets the slug attribute value to a lowercase underscore separated string.
     *
     * @param mixed $value
     * @return void
     */
    public function setRoleSlugAttribute($value)
    {
        $this->attributes['slug'] = str_replace(" ", "_", strtolower($value));
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }
}
