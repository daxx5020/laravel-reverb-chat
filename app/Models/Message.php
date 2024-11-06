<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'chat_id',
        'sender_id',
        'message',
        'read_at',
    ];

    /**
     * Get the chat that the message belongs to.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Get the sender of the message.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Check if the message has been read.
     *
     * @return bool
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    public function getImageUrlAttribute()
    {
        return $this->image_path ? asset("storage/{$this->image_path}") : null;
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'mediaable');
    }
}
