<?php

namespace PtPlugins\FilamentModelMergerFree\Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use PHPUnit\Framework\TestCase as BaseTestCase;
use PtPlugins\FilamentModelMergerFree\ModelMergerFree;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ModelMergerFree::flushMergers();

        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $schema = Capsule::schema();

        $schema->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->softDeletes();
        });

        $schema->create('posts', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('title');
        });

        $schema->create('profiles', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('bio');
        });

        $schema->create('tags', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        $schema->create('tag_user', function ($table) {
            $table->unsignedInteger('tag_id');
            $table->unsignedInteger('user_id');
        });

        $schema->create('comments', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('commentable_id');
            $table->string('commentable_type');
            $table->string('body');
        });

        $schema->create('companies', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        $schema->create('employees', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->string('name');
        });
    }

    protected function tearDown(): void
    {
        $schema = Capsule::schema();
        foreach (['users', 'posts', 'profiles', 'tags', 'tag_user', 'comments', 'companies', 'employees'] as $table) {
            $schema->dropIfExists($table);
        }

        parent::tearDown();
    }
}

class User extends Model
{
    use SoftDeletes;

    protected $table = 'users';

    public $timestamps = false;

    protected $guarded = [];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

class Post extends Model
{
    protected $table = 'posts';

    public $timestamps = false;

    protected $guarded = [];
}

class Profile extends Model
{
    protected $table = 'profiles';

    public $timestamps = false;

    protected $guarded = [];
}

class Tag extends Model
{
    protected $table = 'tags';

    public $timestamps = false;

    protected $guarded = [];
}

class Comment extends Model
{
    protected $table = 'comments';

    public $timestamps = false;

    protected $guarded = [];
}

class Company extends Model
{
    protected $table = 'companies';

    public $timestamps = false;

    protected $guarded = [];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}

class Employee extends Model
{
    protected $table = 'employees';

    public $timestamps = false;

    protected $guarded = [];
}
