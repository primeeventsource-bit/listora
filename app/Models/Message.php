<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One message in a conversation.
 *
 * Intentionally thin. Listora forwards what the two parties say to each other
 * and does not interpret it, so there is no status, no read receipt per
 * message, and no structure imposed on the body.
 */
class Message extends Model
{
    use HasFactory;

    protected $fillable = ['conversation_id', 'sender_user_id', 'body'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
