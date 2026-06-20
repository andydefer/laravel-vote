<?php

declare(strict_types=1);

namespace AndyDefer\LaravelVote\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelVote\Enums\VoteType;

final class VoteFilterRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $voter_type = null,
        public readonly ?int $voter_id = null,
        public readonly ?string $votable_type = null,
        public readonly ?int $votable_id = null,
        public readonly ?VoteType $type = null,
    ) {}
}
