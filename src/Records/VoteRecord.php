<?php

declare(strict_types=1);

namespace AndyDefer\LaravelVote\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\StrictDataObject;
use AndyDefer\LaravelVote\Enums\VoteType;
use AndyDefer\PhpVo\ValueObjects\DateTimeVO;

final class VoteRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $voter_type = null,
        public readonly ?int $voter_id = null,
        public readonly ?string $votable_type = null,
        public readonly ?int $votable_id = null,
        public readonly ?VoteType $type = null,
        public readonly ?StrictDataObject $metadata = null,
        public readonly ?DateTimeVO $created_at = null,
        public readonly ?DateTimeVO $updated_at = null,
        public readonly ?DateTimeVO $deleted_at = null,
    ) {}
}
