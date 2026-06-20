<?php

declare(strict_types=1);

namespace AndyDefer\LaravelVote\Repositories;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelVote\Enums\VoteType;
use AndyDefer\LaravelVote\Models\Vote;
use AndyDefer\LaravelVote\Records\VoteFilterRecord;
use AndyDefer\LaravelVote\Records\VoteRecord;
use AndyDefer\Repository\AbstractRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class VoteRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(
            modelClass: Vote::class,
            recordClass: VoteRecord::class,
        );
    }

    public function countVotesByType(Model $votable, VoteType $type): int
    {
        $filter = VoteFilterRecord::from([
            'votable_type' => $votable->getMorphClass(),
            'votable_id' => $votable->getKey(),
            'type' => $type,
        ]);

        return $this->count($filter);
    }

    public function getScore(Model $votable): int
    {
        $positive = $this->countVotesByType($votable, VoteType::POSITIVE);
        $negative = $this->countVotesByType($votable, VoteType::NEGATIVE);

        return $positive - $negative;
    }

    public function getDistribution(Model $votable): array
    {
        $filter = VoteFilterRecord::from([
            'votable_type' => $votable->getMorphClass(),
            'votable_id' => $votable->getKey(),
        ]);

        $query = $this->buildQuery($filter);
        $distribution = [];

        foreach (VoteType::cases() as $type) {
            $distribution[$type->value] = (clone $query)
                ->where('type', $type->value)
                ->count();
        }

        $distribution['total'] = array_sum($distribution);

        return $distribution;
    }

    public function getVoters(Model $votable): Collection
    {
        $filter = VoteFilterRecord::from([
            'votable_type' => $votable->getMorphClass(),
            'votable_id' => $votable->getKey(),
        ]);

        $query = $this->buildQuery($filter);

        return $query->get()->map(function ($vote) {
            return $vote->voter;
        })->unique('id')->values();
    }

    public function getVotersByType(Model $votable, VoteType $type): Collection
    {
        $filter = VoteFilterRecord::from([
            'votable_type' => $votable->getMorphClass(),
            'votable_id' => $votable->getKey(),
            'type' => $type,
        ]);

        $query = $this->buildQuery($filter);

        return $query->get()->map(function ($vote) {
            return $vote->voter;
        })->unique('id')->values();
    }

    protected function applyFilters(Builder $query, AbstractRecord $filters): void
    {
        if (! $filters instanceof VoteFilterRecord) {
            return;
        }

        if ($filters->voter_type !== null) {
            $query->where('voter_type', $filters->voter_type);
        }

        if ($filters->voter_id !== null) {
            $query->where('voter_id', $filters->voter_id);
        }

        if ($filters->votable_type !== null) {
            $query->where('votable_type', $filters->votable_type);
        }

        if ($filters->votable_id !== null) {
            $query->where('votable_id', $filters->votable_id);
        }

        if ($filters->type !== null) {
            $query->where('type', $filters->type->value);
        }
    }
}
