<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\Resource;

class UserResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'email' => (string) $this->email,
            'role' => (string) $this->roles[0]['slug'],
            'is_active' => (boolean) $this->is_active == 1 ? true : false,
            'is_logged_in' => (boolean) $this->is_logged_in == 1 ? true : false,
            'last_seen' => (string) Carbon::parse($this->logout_at)->format('M d, Y H:i'),
            'date_added' => (string) Carbon::parse($this->created_at)->format('M d, Y H:i'),
            'verified_at' => (string) Carbon::parse($this->email_verified_at)->format('M d, Y H:i')
        ];
    }
}
