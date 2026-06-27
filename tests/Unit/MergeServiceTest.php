<?php

namespace PtPlugins\FilamentModelMergerFree\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;
use PtPlugins\FilamentModelMergerFree\Contracts\RelationMerger;
use PtPlugins\FilamentModelMergerFree\ModelMergerFree;
use PtPlugins\FilamentModelMergerFree\Services\MergeService;
use PtPlugins\FilamentModelMergerFree\Support\RelationMergeResult;
use PtPlugins\FilamentModelMergerFree\Tests\Company;
use PtPlugins\FilamentModelMergerFree\Tests\Tag;
use PtPlugins\FilamentModelMergerFree\Tests\TestCase;
use PtPlugins\FilamentModelMergerFree\Tests\User;

class MergeServiceTest extends TestCase
{
    public function test_has_many_relations_are_transferred_to_primary(): void
    {
        $primary = User::create(['name' => 'Primary']);
        $target = User::create(['name' => 'Target']);

        $primary->posts()->create(['title' => 'P1']);
        $target->posts()->create(['title' => 'T1']);
        $target->posts()->create(['title' => 'T2']);

        $result = (new MergeService)->merge($primary, $target, ['posts']);

        $this->assertSame(2, $result['transferred']['posts']);
        $this->assertSame(3, $primary->posts()->count());
    }

    public function test_has_one_is_transferred_when_primary_has_none(): void
    {
        $primary = User::create(['name' => 'Primary']);
        $target = User::create(['name' => 'Target']);
        $target->profile()->create(['bio' => 'target bio']);

        $result = (new MergeService)->merge($primary, $target, ['profile']);

        $this->assertSame(1, $result['transferred']['profile']);
        $this->assertSame('target bio', $primary->profile()->first()->bio);
    }

    public function test_has_one_conflict_is_skipped_when_primary_already_has_one(): void
    {
        $primary = User::create(['name' => 'Primary']);
        $target = User::create(['name' => 'Target']);
        $primary->profile()->create(['bio' => 'primary bio']);
        $target->profile()->create(['bio' => 'target bio']);

        $result = (new MergeService)->merge($primary, $target, ['profile']);

        $this->assertSame(0, $result['transferred']['profile']);
        $this->assertContains('profile', $result['conflicts']);
        $this->assertSame('primary bio', $primary->profile()->first()->bio);
    }

    public function test_belongs_to_many_is_skipped_as_pro_feature(): void
    {
        $primary = User::create(['name' => 'Primary']);
        $target = User::create(['name' => 'Target']);
        $tag = Tag::create(['name' => 'shared']);
        $target->tags()->attach($tag->id);

        $result = (new MergeService)->merge($primary, $target, ['tags']);

        $this->assertContains('tags', $result['skipped']);
        $this->assertArrayNotHasKey('tags', $result['transferred']);
        $this->assertSame(0, $primary->tags()->count(), 'Many-to-many must NOT transfer in Free');
    }

    public function test_morph_many_is_skipped_as_pro_feature(): void
    {
        // MorphMany extends HasMany — this guards against the Pro-only type
        // leaking through the HasMany branch.
        $primary = User::create(['name' => 'Primary']);
        $target = User::create(['name' => 'Target']);
        $target->comments()->create(['body' => 'hello']);

        $result = (new MergeService)->merge($primary, $target, ['comments']);

        $this->assertContains('comments', $result['skipped']);
        $this->assertArrayNotHasKey('comments', $result['transferred']);
    }

    public function test_a_custom_relation_merger_can_transfer_belongs_to_many(): void
    {
        ModelMergerFree::extend(new class implements RelationMerger
        {
            public function supports(Relation $relation): bool
            {
                return $relation instanceof BelongsToMany;
            }

            public function transfer(Model $primary, Model $target, string $relationName): RelationMergeResult
            {
                $ids = $target->{$relationName}()->allRelatedIds()->all();
                $primary->{$relationName}()->syncWithoutDetaching($ids);

                return new RelationMergeResult(count($ids));
            }
        });

        $primary = User::create(['name' => 'Primary']);
        $target = User::create(['name' => 'Target']);
        $tag = Tag::create(['name' => 'shared']);
        $target->tags()->attach($tag->id);

        $result = (new MergeService)->merge($primary, $target, ['tags']);

        $this->assertSame(1, $result['transferred']['tags']);
        $this->assertNotContains('tags', $result['skipped'], 'Custom merger must take over from the Free skip path');
        $this->assertSame(1, $primary->tags()->count());
    }

    public function test_target_is_soft_deleted_when_model_uses_soft_deletes(): void
    {
        $primary = User::create(['name' => 'Primary']);
        $target = User::create(['name' => 'Target']);

        $result = (new MergeService)->merge($primary, $target, []);

        $this->assertTrue($result['target_soft_deleted']);
        $this->assertNull(User::find($target->id));
        $this->assertNotNull(User::withTrashed()->find($target->id));
    }

    public function test_target_is_hard_deleted_when_model_has_no_soft_deletes(): void
    {
        $primary = Company::create(['name' => 'Primary']);
        $target = Company::create(['name' => 'Target']);
        $target->employees()->create(['name' => 'Joe']);

        $result = (new MergeService)->merge($primary, $target, ['employees']);

        $this->assertFalse($result['target_soft_deleted']);
        $this->assertSame(1, $primary->employees()->count());
        $this->assertNull(Company::find($target->id));
    }

    public function test_cannot_merge_a_record_with_itself(): void
    {
        $primary = User::create(['name' => 'Primary']);

        $this->expectException(InvalidArgumentException::class);

        (new MergeService)->merge($primary, $primary, []);
    }
}
