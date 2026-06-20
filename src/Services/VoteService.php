<?php

declare(strict_types=1);

namespace AndyDefer\LaravelVote\Services;

use AndyDefer\LaravelVote\Enums\VoteType;
use AndyDefer\LaravelVote\Records\VoteFilterRecord;
use AndyDefer\LaravelVote\Records\VoteRecord;
use AndyDefer\LaravelVote\Repositories\VoteRepository;
use AndyDefer\Repository\Records\FindByRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use RuntimeException;

final class VoteService
{
    public function __construct(
        private readonly VoteRepository $voteRepository,
    ) {}

    public function vote(Model $voter, Model $votable, VoteType $type): Model
    {
        $existing = $this->findExisting($voter, $votable);

        if ($existing) {
            throw new RuntimeException(sprintf(
                '%s %s has already voted on %s %s',
                $voter->getMorphClass(),
                $voter->getKey(),
                $votable->getMorphClass(),
                $votable->getKey()
            ));
        }

        $record = VoteRecord::from([
            'voter_type' => $voter->getMorphClass(),
            'voter_id' => $voter->getKey(),
            'votable_type' => $votable->getMorphClass(),
            'votable_id' => $votable->getKey(),
            'type' => $type,
        ]);

        return $this->voteRepository->create($record);
    }

    public function toggle(Model $voter, Model $votable, VoteType $type): bool
    {
        $existing = $this->findExisting($voter, $votable);

        if (! $existing) {
            $this->vote($voter, $votable, $type);

            return true;
        }

        if ($existing->type === $type) {
            $this->voteRepository->delete($existing->id);

            return false;
        }

        $updateRecord = VoteRecord::from([
            'type' => $type,
        ]);

        $this->voteRepository->update($existing->id, $updateRecord);

        return true;
    }

    public function updateVote(Model $voter, Model $votable, VoteType $type): Model
    {
        $existing = $this->findExisting($voter, $votable);

        if (! $existing) {
            throw new RuntimeException(sprintf(
                '%s %s has not voted on %s %s',
                $voter->getMorphClass(),
                $voter->getKey(),
                $votable->getMorphClass(),
                $votable->getKey()
            ));
        }

        $updateRecord = VoteRecord::from([
            'type' => $type,
        ]);

        return $this->voteRepository->update($existing->id, $updateRecord);
    }

    public function deleteVote(Model $voter, Model $votable): void
    {
        $existing = $this->findExisting($voter, $votable);

        if (! $existing) {
            throw new RuntimeException(sprintf(
                '%s %s has not voted on %s %s',
                $voter->getMorphClass(),
                $voter->getKey(),
                $votable->getMorphClass(),
                $votable->getKey()
            ));
        }

        $this->voteRepository->delete($existing->id);
    }

    private function findExisting(Model $voter, Model $votable): ?Model
    {
        $filter = VoteFilterRecord::from([
            'voter_type' => $voter->getMorphClass(),
            'voter_id' => $voter->getKey(),
            'votable_type' => $votable->getMorphClass(),
            'votable_id' => $votable->getKey(),
        ]);

        $findByRecord = new FindByRecord(
            filters: $filter,
            limit: 1,
        );

        $collection = $this->voteRepository->findBy($findByRecord);

        return $collection->first();
    }

    public function hasVoted(Model $voter, Model $votable): bool
    {
        $filter = VoteFilterRecord::from([
            'voter_type' => $voter->getMorphClass(),
            'voter_id' => $voter->getKey(),
            'votable_type' => $votable->getMorphClass(),
            'votable_id' => $votable->getKey(),
        ]);

        return $this->voteRepository->exists($filter);
    }

    public function hasVotedType(Model $voter, Model $votable, VoteType $type): bool
    {
        $filter = VoteFilterRecord::from([
            'voter_type' => $voter->getMorphClass(),
            'voter_id' => $voter->getKey(),
            'votable_type' => $votable->getMorphClass(),
            'votable_id' => $votable->getKey(),
            'type' => $type,
        ]);

        return $this->voteRepository->exists($filter);
    }

    public function getVoterVote(Model $voter, Model $votable): ?Model
    {
        $filter = VoteFilterRecord::from([
            'voter_type' => $voter->getMorphClass(),
            'voter_id' => $voter->getKey(),
            'votable_type' => $votable->getMorphClass(),
            'votable_id' => $votable->getKey(),
        ]);

        $findByRecord = new FindByRecord(
            filters: $filter,
            limit: 1,
        );

        $collection = $this->voteRepository->findBy($findByRecord);

        return $collection->first();
    }

    public function getVoterVoteType(Model $voter, Model $votable): ?VoteType
    {
        $vote = $this->getVoterVote($voter, $votable);

        return $vote?->getType();
    }

    public function getVotes(Model $votable): Collection
    {
        $filter = VoteFilterRecord::from([
            'votable_type' => $votable->getMorphClass(),
            'votable_id' => $votable->getKey(),
        ]);

        $findByRecord = new FindByRecord(filters: $filter);

        return $this->voteRepository->findBy($findByRecord);
    }

    public function getVoterVotes(Model $voter): Collection
    {
        $filter = VoteFilterRecord::from([
            'voter_type' => $voter->getMorphClass(),
            'voter_id' => $voter->getKey(),
        ]);

        $findByRecord = new FindByRecord(filters: $filter);

        return $this->voteRepository->findBy($findByRecord);
    }

    public function getVoterVotesByType(Model $voter, VoteType $type): Collection
    {
        $filter = VoteFilterRecord::from([
            'voter_type' => $voter->getMorphClass(),
            'voter_id' => $voter->getKey(),
            'type' => $type,
        ]);

        $findByRecord = new FindByRecord(filters: $filter);

        return $this->voteRepository->findBy($findByRecord);
    }

    public function getVoters(Model $votable): Collection
    {
        return $this->voteRepository->getVoters($votable);
    }

    public function getVotersByType(Model $votable, VoteType $type): Collection
    {
        return $this->voteRepository->getVotersByType($votable, $type);
    }

    public function countVotes(Model $votable): int
    {
        $filter = VoteFilterRecord::from([
            'votable_type' => $votable->getMorphClass(),
            'votable_id' => $votable->getKey(),
        ]);

        return $this->voteRepository->count($filter);
    }

    public function countVotesByType(Model $votable, VoteType $type): int
    {
        return $this->voteRepository->countVotesByType($votable, $type);
    }

    public function getScore(Model $votable): int
    {
        return $this->voteRepository->getScore($votable);
    }

    public function getParticipationRate(Model $votable): float
    {
        $totalVotes = $this->countVotes($votable);

        // Vous pouvez personnaliser cette méthode selon vos besoins
        // Par exemple, si vous avez un total de votants potentiels
        $totalPotentialVoters = 100; // À adapter selon votre contexte

        if ($totalPotentialVoters === 0) {
            return 0.0;
        }

        return round(($totalVotes / $totalPotentialVoters) * 100, 2);
    }

    public function getDistribution(Model $votable): array
    {
        return $this->voteRepository->getDistribution($votable);
    }

    public function getPercentage(Model $votable, VoteType $type): float
    {
        $distribution = $this->getDistribution($votable);
        $total = $distribution['total'] ?? 0;

        if ($total === 0) {
            return 0.0;
        }

        $count = $distribution[$type->value] ?? 0;

        return round(($count / $total) * 100, 2);
    }

    public function getStats(Model $votable): array
    {
        $distribution = $this->getDistribution($votable);
        $total = $distribution['total'] ?? 0;

        return [
            'positive' => $distribution[VoteType::POSITIVE->value] ?? 0,
            'negative' => $distribution[VoteType::NEGATIVE->value] ?? 0,
            'abstention' => $distribution[VoteType::ABSTENTION->value] ?? 0,
            'neutral' => $distribution[VoteType::NEUTRAL->value] ?? 0,
            'total' => $total,
            'score' => $this->getScore($votable),
            'participation_rate' => $this->getParticipationRate($votable),
            'distribution' => [
                VoteType::POSITIVE->value => $this->getPercentage($votable, VoteType::POSITIVE),
                VoteType::NEGATIVE->value => $this->getPercentage($votable, VoteType::NEGATIVE),
                VoteType::ABSTENTION->value => $this->getPercentage($votable, VoteType::ABSTENTION),
                VoteType::NEUTRAL->value => $this->getPercentage($votable, VoteType::NEUTRAL),
            ],
        ];
    }
}
