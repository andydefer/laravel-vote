<?php

declare(strict_types=1);

namespace AndyDefer\LaravelVote\Models;

use AndyDefer\DomainStructures\Utils\StrictDataObject;
use AndyDefer\LaravelVote\Enums\VoteType;
use AndyDefer\PhpVo\ValueObjects\DateTimeVO;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Vote extends Model
{
    use SoftDeletes;

    protected $table = 'votes';

    protected $fillable = [
        'voter_type',
        'voter_id',
        'votable_type',
        'votable_id',
        'type',
        'metadata',
    ];

    protected $casts = [
        'type' => VoteType::class,
        'metadata' => 'array',
    ];

    public function voter()
    {
        return $this->morphTo();
    }

    public function votable()
    {
        return $this->morphTo();
    }

    public function getCreatedAt(): ?DateTimeVO
    {
        return $this->created_at ? DateTimeVO::from($this->created_at) : null;
    }

    public function getUpdatedAt(): ?DateTimeVO
    {
        return $this->updated_at ? DateTimeVO::from($this->updated_at) : null;
    }

    public function getDeletedAt(): ?DateTimeVO
    {
        return $this->deleted_at ? DateTimeVO::from($this->deleted_at) : null;
    }

    public function getMetadata(): ?StrictDataObject
    {
        return $this->metadata ? StrictDataObject::from($this->metadata) : null;
    }

    public function getType(): VoteType
    {
        return $this->type;
    }
}