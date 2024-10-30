<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'service_id',
        'buyer_id',
        'seller_id',
    ];

    /**
     * Get the service associated with the chat.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the buyer associated with the chat.
     */
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Get the seller associated with the chat.
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get all of the messages for the Chat
    */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the latest message for the Chat
    */
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}
