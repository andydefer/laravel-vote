<?php

declare(strict_types=1);

namespace AndyDefer\LaravelVote\Tests\Integration;

use AndyDefer\LaravelVote\Enums\VoteType;
use AndyDefer\LaravelVote\Models\Vote;
use AndyDefer\LaravelVote\Repositories\VoteRepository;
use AndyDefer\LaravelVote\Services\VoteService;
use AndyDefer\LaravelVote\Tests\Fixtures\Models\TestPost;
use AndyDefer\LaravelVote\Tests\Fixtures\Models\TestUser;
use AndyDefer\LaravelVote\Tests\TestCase;
use RuntimeException;

final class VoteServiceTest extends TestCase
{
    private VoteService $voteService;

    private TestUser $user;

    private TestPost $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->voteService = new VoteService(
            new VoteRepository
        );

        $this->user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->post = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);
    }

    public function test_vote_creates_vote_when_not_exists(): void
    {
        $vote = $this->voteService->vote($this->user, $this->post, VoteType::POSITIVE);

        $this->assertInstanceOf(Vote::class, $vote);
        $this->assertSame(VoteType::POSITIVE, $vote->getType());
        $this->assertTrue($this->voteService->hasVoted($this->user, $this->post));
    }

    public function test_vote_throws_exception_when_already_voted(): void
    {
        $this->voteService->vote($this->user, $this->post, VoteType::POSITIVE);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('has already voted');

        $this->voteService->vote($this->user, $this->post, VoteType::NEGATIVE);
    }

    public function test_toggle_adds_vote_when_not_exists(): void
    {
        $result = $this->voteService->toggle($this->user, $this->post, VoteType::POSITIVE);

        $this->assertTrue($result);
        $this->assertTrue($this->voteService->hasVoted($this->user, $this->post));
        $this->assertSame(
            VoteType::POSITIVE,
            $this->voteService->getVoterVoteType($this->user, $this->post)
        );
    }

    public function test_toggle_removes_vote_when_same_type(): void
    {
        $this->voteService->vote($this->user, $this->post, VoteType::POSITIVE);

        $result = $this->voteService->toggle($this->user, $this->post, VoteType::POSITIVE);

        $this->assertFalse($result);
        $this->assertFalse($this->voteService->hasVoted($this->user, $this->post));
    }

    public function test_toggle_changes_vote_type(): void
    {
        $this->voteService->vote($this->user, $this->post, VoteType::POSITIVE);

        $result = $this->voteService->toggle($this->user, $this->post, VoteType::NEGATIVE);

        $this->assertTrue($result);
        $this->assertTrue($this->voteService->hasVoted($this->user, $this->post));
        $this->assertSame(
            VoteType::NEGATIVE,
            $this->voteService->getVoterVoteType($this->user, $this->post)
        );
    }

    public function test_update_vote_modifies_existing_vote(): void
    {
        $this->voteService->vote($this->user, $this->post, VoteType::POSITIVE);

        $updated = $this->voteService->updateVote($this->user, $this->post, VoteType::NEUTRAL);

        $this->assertSame(VoteType::NEUTRAL, $updated->getType());
    }

    public function test_update_vote_throws_exception_when_not_voted(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('has not voted');

        $this->voteService->updateVote($this->user, $this->post, VoteType::POSITIVE);
    }

    public function test_delete_vote_removes_vote(): void
    {
        $this->voteService->vote($this->user, $this->post, VoteType::POSITIVE);

        $this->assertTrue($this->voteService->hasVoted($this->user, $this->post));

        $this->voteService->deleteVote($this->user, $this->post);

        $this->assertFalse($this->voteService->hasVoted($this->user, $this->post));
    }

    public function test_has_voted_type_returns_correct_value(): void
    {
        $this->voteService->vote($this->user, $this->post, VoteType::POSITIVE);

        $this->assertTrue($this->voteService->hasVotedType($this->user, $this->post, VoteType::POSITIVE));
        $this->assertFalse($this->voteService->hasVotedType($this->user, $this->post, VoteType::NEGATIVE));
    }

    public function test_get_voter_vote_returns_vote(): void
    {
        $this->voteService->vote($this->user, $this->post, VoteType::POSITIVE);

        $vote = $this->voteService->getVoterVote($this->user, $this->post);

        $this->assertNotNull($vote);
        $this->assertSame(VoteType::POSITIVE, $vote->getType());
    }

    public function test_get_voter_vote_returns_null_when_not_voted(): void
    {
        $vote = $this->voteService->getVoterVote($this->user, $this->post);

        $this->assertNull($vote);
    }

    public function test_get_voter_vote_type_returns_correct_type(): void
    {
        $this->voteService->vote($this->user, $this->post, VoteType::POSITIVE);

        $type = $this->voteService->getVoterVoteType($this->user, $this->post);

        $this->assertSame(VoteType::POSITIVE, $type);
    }

    public function test_get_votes_returns_all_votes(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->voteService->vote($this->user, $this->post, VoteType::POSITIVE);
        $this->voteService->vote($user2, $this->post, VoteType::NEGATIVE);

        $votes = $this->voteService->getVotes($this->post);

        $this->assertCount(2, $votes);
    }

    public function test_get_voter_votes_returns_all_votes_from_voter(): void
    {
        $post2 = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Second Post',
            'body' => 'Another content',
        ]);

        $this->voteService->vote($this->user, $this->post, VoteType::POSITIVE);
        $this->voteService->vote($this->user, $post2, VoteType::NEUTRAL);

        $votes = $this->voteService->getVoterVotes($this->user);

        $this->assertCount(2, $votes);
    }

    public function test_get_score_returns_correct_score(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
        $user3 = TestUser::create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
        ]);

        $this->voteService->vote($this->user, $this->post, VoteType::POSITIVE);
        $this->voteService->vote($user2, $this->post, VoteType::POSITIVE);
        $this->voteService->vote($user3, $this->post, VoteType::NEGATIVE);

        $score = $this->voteService->getScore($this->post);

        $this->assertSame(1, $score);
    }

    public function test_get_distribution_returns_correct_distribution(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
        $user3 = TestUser::create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
        ]);

        $this->voteService->vote($this->user, $this->post, VoteType::POSITIVE);
        $this->voteService->vote($user2, $this->post, VoteType::POSITIVE);
        $this->voteService->vote($user3, $this->post, VoteType::NEGATIVE);

        $distribution = $this->voteService->getDistribution($this->post);

        $this->assertSame(2, $distribution[VoteType::POSITIVE->value]);
        $this->assertSame(1, $distribution[VoteType::NEGATIVE->value]);
        $this->assertSame(0, $distribution[VoteType::ABSTENTION->value]);
        $this->assertSame(0, $distribution[VoteType::NEUTRAL->value]);
        $this->assertSame(3, $distribution['total']);
    }

    public function test_get_percentage_returns_correct_percentage(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
        $user3 = TestUser::create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
        ]);

        $this->voteService->vote($this->user, $this->post, VoteType::POSITIVE);
        $this->voteService->vote($user2, $this->post, VoteType::POSITIVE);
        $this->voteService->vote($user3, $this->post, VoteType::NEGATIVE);

        $percentage = $this->voteService->getPercentage($this->post, VoteType::POSITIVE);

        $this->assertSame(66.67, $percentage);
    }

    public function test_get_stats_returns_complete_stats(): void
    {
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
        $user3 = TestUser::create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
        ]);

        $this->voteService->vote($this->user, $this->post, VoteType::POSITIVE);
        $this->voteService->vote($user2, $this->post, VoteType::POSITIVE);
        $this->voteService->vote($user3, $this->post, VoteType::NEGATIVE);

        $stats = $this->voteService->getStats($this->post);

        $this->assertSame(2, $stats['positive']);
        $this->assertSame(1, $stats['negative']);
        $this->assertSame(0, $stats['abstention']);
        $this->assertSame(0, $stats['neutral']);
        $this->assertSame(3, $stats['total']);
        $this->assertSame(1, $stats['score']);
        $this->assertIsArray($stats['distribution']);
        $this->assertArrayHasKey(VoteType::POSITIVE->value, $stats['distribution']);
        $this->assertArrayHasKey(VoteType::NEGATIVE->value, $stats['distribution']);
        $this->assertArrayHasKey(VoteType::ABSTENTION->value, $stats['distribution']);
        $this->assertArrayHasKey(VoteType::NEUTRAL->value, $stats['distribution']);
    }
}
