<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\BinaryUuidPost;
use Tests\Fixtures\BinaryUuidProfile;
use Tests\Fixtures\BinaryUuidUser;

class BinaryUuidRelationshipsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create test tables with binary UUID columns
        Schema::create('binary_uuid_users', function (Blueprint $table) {
            $table->binary('id', 16)->primary();
            $table->string('name');
        });

        Schema::create('binary_uuid_posts', function (Blueprint $table) {
            $table->id();
            $table->binary('user_id', 16);
            $table->foreign('user_id')->references('id')->on('binary_uuid_users')->onDelete('cascade');
            $table->string('title');
        });

        Schema::create('binary_uuid_profiles', function (Blueprint $table) {
            $table->id();
            $table->binary('user_id', 16)->unique();
            $table->foreign('user_id')->references('id')->on('binary_uuid_users')->onDelete('cascade');
            $table->string('bio');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('binary_uuid_profiles');
        Schema::dropIfExists('binary_uuid_posts');
        Schema::dropIfExists('binary_uuid_users');

        parent::tearDown();
    }

    #[Test]
    public function it_can_create_model_with_binary_uuid_primary_key()
    {
        $user = BinaryUuidUser::create(['name' => 'John Doe']);

        $this->assertNotNull($user->id);
        $this->assertIsString($user->id);
        $this->assertTrue(\Ramsey\Uuid\Uuid::isValid($user->id));
    }

    #[Test]
    public function it_can_query_by_uuid_using_where_uuid_scope()
    {
        $user = BinaryUuidUser::create(['name' => 'Jane Doe']);

        $found = BinaryUuidUser::whereUuid($user->id, 'id')->first();

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    #[Test]
    public function it_handles_belongs_to_relationship_with_binary_uuid()
    {
        $user = BinaryUuidUser::create(['name' => 'Author']);

        $post = BinaryUuidPost::create([
            'user_id' => $user->id,
            'title' => 'Test Post',
        ]);

        // Lazy loading - refresh to simulate loading from database
        $post = BinaryUuidPost::find($post->id);
        $loadedUser = $post->user;

        $this->assertNotNull($loadedUser, 'belongsTo relationship should work');
        $this->assertEquals($user->id, $loadedUser->id);
        $this->assertEquals('Author', $loadedUser->name);
    }

    #[Test]
    public function it_handles_has_many_relationship_with_binary_uuid()
    {
        $user = BinaryUuidUser::create(['name' => 'Author']);

        BinaryUuidPost::create(['user_id' => $user->id, 'title' => 'Post 1']);
        BinaryUuidPost::create(['user_id' => $user->id, 'title' => 'Post 2']);
        BinaryUuidPost::create(['user_id' => $user->id, 'title' => 'Post 3']);

        // Refresh to test lazy loading from database
        $user = BinaryUuidUser::find($user->id);
        $posts = $user->posts;

        $this->assertCount(3, $posts, 'hasMany relationship should return all related records');
        $this->assertEquals('Post 1', $posts[0]->title);
        $this->assertEquals('Post 2', $posts[1]->title);
        $this->assertEquals('Post 3', $posts[2]->title);
    }

    #[Test]
    public function it_handles_has_one_relationship_with_binary_uuid()
    {
        $user = BinaryUuidUser::create(['name' => 'John']);

        BinaryUuidProfile::create([
            'user_id' => $user->id,
            'bio' => 'Software developer',
        ]);

        // Refresh to test lazy loading
        $user = BinaryUuidUser::find($user->id);
        $profile = $user->profile;

        $this->assertNotNull($profile, 'hasOne relationship should work');
        $this->assertEquals('Software developer', $profile->bio);
        $this->assertEquals($user->id, $profile->user_id);
    }

    #[Test]
    public function it_handles_eager_loading_with_binary_uuid()
    {
        $user1 = BinaryUuidUser::create(['name' => 'User 1']);
        $user2 = BinaryUuidUser::create(['name' => 'User 2']);

        BinaryUuidPost::create(['user_id' => $user1->id, 'title' => 'Post 1.1']);
        BinaryUuidPost::create(['user_id' => $user1->id, 'title' => 'Post 1.2']);
        BinaryUuidPost::create(['user_id' => $user2->id, 'title' => 'Post 2.1']);

        // Eager loading - This uses WHERE IN with multiple UUIDs
        $posts = BinaryUuidPost::with('user')->get();

        $this->assertCount(3, $posts);
        $posts->each(function ($post) {
            $this->assertNotNull($post->user, 'Eager loaded user should not be null');
            $this->assertInstanceOf(BinaryUuidUser::class, $post->user);
        });
    }

    #[Test]
    public function it_handles_reverse_eager_loading_with_binary_uuid()
    {
        $user1 = BinaryUuidUser::create(['name' => 'User 1']);
        $user2 = BinaryUuidUser::create(['name' => 'User 2']);

        BinaryUuidPost::create(['user_id' => $user1->id, 'title' => 'Post 1.1']);
        BinaryUuidPost::create(['user_id' => $user1->id, 'title' => 'Post 1.2']);
        BinaryUuidPost::create(['user_id' => $user2->id, 'title' => 'Post 2.1']);

        // Eager load posts on users
        $users = BinaryUuidUser::with('posts')->get();

        $this->assertCount(2, $users);
        $this->assertCount(2, $users[0]->posts);
        $this->assertCount(1, $users[1]->posts);
    }

    #[Test]
    public function it_correctly_converts_uuid_in_where_in_queries()
    {
        $user1 = BinaryUuidUser::create(['name' => 'User 1']);
        $user2 = BinaryUuidUser::create(['name' => 'User 2']);
        $user3 = BinaryUuidUser::create(['name' => 'User 3']);

        // Query with multiple UUIDs
        $found = BinaryUuidUser::whereIn('id', [
            $user1->id,
            $user3->id,
        ])->get();

        $this->assertCount(2, $found);
        $this->assertTrue($found->contains('id', $user1->id));
        $this->assertTrue($found->contains('id', $user3->id));
        $this->assertFalse($found->contains('id', $user2->id));
    }

    #[Test]
    public function it_handles_where_queries_with_binary_uuid()
    {
        $user = BinaryUuidUser::create(['name' => 'Test User']);

        // Standard WHERE query (not using whereUuid)
        $found = BinaryUuidUser::where('id', $user->id)->first();

        $this->assertNotNull($found, 'WHERE query with UUID string should work');
        $this->assertEquals($user->id, $found->id);
    }

    #[Test]
    public function it_does_not_break_non_uuid_queries()
    {
        $user = BinaryUuidUser::create(['name' => 'John']);

        // Query on non-UUID column should work normally
        $found = BinaryUuidUser::where('name', 'John')->first();

        $this->assertNotNull($found);
        $this->assertEquals('John', $found->name);
    }

    #[Test]
    public function it_preserves_existing_where_uuid_functionality()
    {
        $user = BinaryUuidUser::create(['name' => 'Test']);

        // The existing whereUuid scope should still work
        $found1 = BinaryUuidUser::whereUuid($user->id, 'id')->first();

        // And now regular where should also work
        $found2 = BinaryUuidUser::where('id', $user->id)->first();

        $this->assertNotNull($found1);
        $this->assertNotNull($found2);
        $this->assertEquals($found1->id, $found2->id);
    }

    #[Test]
    public function it_handles_eager_loading_with_has_one_relationship()
    {
        $user1 = BinaryUuidUser::create(['name' => 'User 1']);
        $user2 = BinaryUuidUser::create(['name' => 'User 2']);

        BinaryUuidProfile::create(['user_id' => $user1->id, 'bio' => 'Bio 1']);
        BinaryUuidProfile::create(['user_id' => $user2->id, 'bio' => 'Bio 2']);

        $users = BinaryUuidUser::with('profile')->get();

        $this->assertCount(2, $users);
        $users->each(function ($user) {
            $this->assertNotNull($user->profile, 'Eager loaded profile should not be null');
            $this->assertInstanceOf(BinaryUuidProfile::class, $user->profile);
        });
    }

    #[Test]
    public function it_handles_where_not_in_with_binary_uuid()
    {
        $user1 = BinaryUuidUser::create(['name' => 'User 1']);
        $user2 = BinaryUuidUser::create(['name' => 'User 2']);
        $user3 = BinaryUuidUser::create(['name' => 'User 3']);

        // Exclude specific UUIDs
        $found = BinaryUuidUser::whereNotIn('id', [
            $user1->id,
            $user3->id,
        ])->get();

        $this->assertCount(1, $found);
        $this->assertEquals($user2->id, $found->first()->id);
        $this->assertFalse($found->contains('id', $user1->id));
        $this->assertFalse($found->contains('id', $user3->id));
    }

    #[Test]
    public function it_handles_where_with_different_operators()
    {
        $user1 = BinaryUuidUser::create(['name' => 'User 1']);
        $user2 = BinaryUuidUser::create(['name' => 'User 2']);

        // Test != operator
        $found = BinaryUuidUser::where('id', '!=', $user1->id)->get();

        $this->assertCount(1, $found);
        $this->assertEquals($user2->id, $found->first()->id);
    }

    #[Test]
    public function it_handles_where_with_qualified_column_name()
    {
        $user = BinaryUuidUser::create(['name' => 'Test User']);

        // Query with table.column syntax
        $found = BinaryUuidUser::where('binary_uuid_users.id', $user->id)->first();

        $this->assertNotNull($found, 'WHERE with qualified column name should work');
        $this->assertEquals($user->id, $found->id);
    }

    #[Test]
    public function it_handles_where_with_invalid_uuid_string()
    {
        BinaryUuidUser::create(['name' => 'Valid User']);

        // Query with non-UUID string should not crash, just return no results
        $found = BinaryUuidUser::where('id', 'not-a-valid-uuid')->get();

        $this->assertCount(0, $found, 'Invalid UUID should not match any records');
    }

    #[Test]
    public function it_handles_where_with_non_string_values()
    {
        $user = BinaryUuidUser::create(['name' => 'Test User']);

        // WHERE with integer value on UUID column - should not crash
        $foundInt = BinaryUuidUser::where('id', 123)->get();
        $this->assertCount(0, $foundInt);

        // WHERE with null value
        $foundNull = BinaryUuidUser::where('id', null)->get();
        $this->assertCount(0, $foundNull);

        // Normal WHERE on name column should still work
        $foundName = BinaryUuidUser::where('name', 'Test User')->first();
        $this->assertNotNull($foundName);
    }

    #[Test]
    public function it_handles_where_in_with_empty_array()
    {
        BinaryUuidUser::create(['name' => 'User 1']);
        BinaryUuidUser::create(['name' => 'User 2']);

        // WHERE IN with empty array should return no results
        $found = BinaryUuidUser::whereIn('id', [])->get();

        $this->assertCount(0, $found);
    }

    #[Test]
    public function it_handles_where_in_with_mixed_valid_invalid_uuids()
    {
        $user1 = BinaryUuidUser::create(['name' => 'User 1']);
        $user2 = BinaryUuidUser::create(['name' => 'User 2']);

        // Mix of valid UUIDs and invalid strings
        $found = BinaryUuidUser::whereIn('id', [
            $user1->id,
            'not-a-uuid',
            $user2->id,
            'also-not-uuid',
        ])->get();

        // Should only find the valid UUIDs
        $this->assertCount(2, $found);
        $this->assertTrue($found->contains('id', $user1->id));
        $this->assertTrue($found->contains('id', $user2->id));
    }

    #[Test]
    public function it_handles_or_where_with_binary_uuid()
    {
        $user1 = BinaryUuidUser::create(['name' => 'User 1']);
        $user2 = BinaryUuidUser::create(['name' => 'User 2']);
        $user3 = BinaryUuidUser::create(['name' => 'User 3']);

        // Query with orWhere
        $found = BinaryUuidUser::where('id', $user1->id)
            ->orWhere('id', $user3->id)
            ->get();

        $this->assertCount(2, $found);
        $this->assertTrue($found->contains('id', $user1->id));
        $this->assertTrue($found->contains('id', $user3->id));
        $this->assertFalse($found->contains('id', $user2->id));
    }

    #[Test]
    public function it_handles_where_shorthand_syntax()
    {
        $user = BinaryUuidUser::create(['name' => 'Test User']);

        // WHERE with 2 arguments (shorthand for =)
        $found = BinaryUuidUser::where('id', $user->id)->first();

        $this->assertNotNull($found, 'WHERE shorthand syntax should work');
        $this->assertEquals($user->id, $found->id);
    }

    #[Test]
    public function it_handles_complex_nested_queries()
    {
        $user1 = BinaryUuidUser::create(['name' => 'Admin']);
        $user2 = BinaryUuidUser::create(['name' => 'User']);

        BinaryUuidPost::create(['user_id' => $user1->id, 'title' => 'Admin Post']);
        BinaryUuidPost::create(['user_id' => $user2->id, 'title' => 'User Post']);

        // Complex query with WHERE and relationships
        $posts = BinaryUuidPost::where('title', 'LIKE', '%Post%')
            ->whereIn('user_id', [$user1->id, $user2->id])
            ->with('user')
            ->get();

        $this->assertCount(2, $posts);
        $posts->each(function ($post) {
            $this->assertNotNull($post->user);
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            \Dyrynda\Database\Support\LaravelModelUuidServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
