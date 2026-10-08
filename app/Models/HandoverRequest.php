<?php

namespace App\Models;

use App\Modules\Conversation\Enums\HandoverReason;
use App\Modules\Conversation\Enums\HandoverStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'conversation_id',
    'reason',
    'summary',
    'status',
    'assigned_to',
    'resolved_at',
])]
class HandoverRequest extends Model
{
    use HasFactory;

    public const REASON_SPECIAL_REQUEST = HandoverReason::SpecialRequest->value;

    public const REASON_COMPLAINT = HandoverReason::Complaint->value;

    public const REASON_GROUP_RESERVATION = HandoverReason::GroupReservation->value;

    public const REASON_PRIVATE_EVENT = HandoverReason::PrivateEvent->value;

    public const REASON_CUSTOM_ORDER = HandoverReason::CustomOrder->value;

    public const REASON_PAYMENT_ISSUE = HandoverReason::PaymentIssue->value;

    public const REASON_LOW_CONFIDENCE = HandoverReason::LowConfidence->value;

    public const STATUS_OPEN = HandoverStatus::Open->value;

    public const STATUS_RESOLVED = HandoverStatus::Resolved->value;

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
