<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\WithFaker;
use Ramsey\Uuid\Rfc4122\FieldsInterface;
use Ramsey\Uuid\Uuid;
use Tests\Fixtures\Comment;
use Tests\Fixtures\CustomCastUuidPost;
use Tests\Fixtures\CustomUuidPost;
use Tests\Fixtures\EfficientUuidPost;
use Tests\Fixtures\MultipleUuidPost;
use Tests\Fixtures\OrderedPost;
use Tests\Fixtures\Post;
use Tests\Fixtures\UncastPost;
use Tests\Fixtures\Uuid1Post;
use Tests\Fixtures\Uuid4Post;
use Tests\Fixtures\Uuid6Post;
use Tests\Fixtures\Uuid7Post;

uses(WithFaker::class);

it('sets the uuid when creating a new model', function () {
    $post = Post::create(['title' => 'Test post']);

    expect($post->uuid)->not->toBeNull();
});

it('does not override the uuid if it is already set', function () {
    $uuid = '24f6c768-6276-4f34-bfa1-e7c8ba9514ea';

    $post = Post::create(['title' => 'Test post', 'uuid' => $uuid]);

    expect($post->uuid)->toBe($uuid);
});

test('you can find a model by its uuid', function () {
    $uuid = '55635d83-10bc-424f-bf3f-395ea7a5b47f';

    Post::create(['title' => 'test post', 'uuid' => $uuid]);

    $post = Post::whereUuid($uuid)->first();

    expect($post)
        ->toBeInstanceOf(Post::class)
        ->uuid->toBe($uuid);
});

test('you can exclude a model by its uuid', function () {
    $uuid = '55635d83-10bc-424f-bf3f-395ea7a5b47f';

    Post::create(['title' => 'test post', 'uuid' => $uuid]);

    expect(Post::whereNotUuid($uuid))->first()->toBeNull();
});

test('you can find a model by custom uuid parameter', function () {
    $uuid = '6499332d-25e1-4d75-bd92-c6ded0820fb3';
    $custom_uuid = '99635d83-05bc-424f-bf3f-395ea7a5b323';

    MultipleUuidPost::create(['title' => 'test post', 'uuid' => $uuid, 'custom_uuid' => $custom_uuid]);

    $post1 = MultipleUuidPost::whereUuid($uuid)->first();

    expect($post1)
        ->toBeInstanceOf(MultipleUuidPost::class)
        ->uuid->toBe($uuid);

    $post2 = MultipleUuidPost::whereUuid($uuid, 'uuid')->first();

    expect($post2)
        ->toBeInstanceOf(MultipleUuidPost::class)
        ->uuid->toBe($uuid);

    $post3 = MultipleUuidPost::whereUuid($custom_uuid, 'custom_uuid')->first();

    expect($post3)
        ->toBeInstanceOf(MultipleUuidPost::class)
        ->custom_uuid->toBe($custom_uuid);
});

test('you can search by array of uuids', function () {
    Post::create(['title' => 'first post', 'uuid' => '8ab48e77-d9cd-4fe7-ace5-a5a428590c18']);
    Post::create(['title' => 'second post', 'uuid' => 'c7c26456-ddb0-45cd-9b1c-318296cce7a3']);

    expect(Post::whereUuid([
        '8ab48e77-d9cd-4fe7-ace5-A5A428590C18',
        'c7c26456-ddb0-45cd-9b1c-318296cce7a3',
    ]))->count()->toEqual(2);
});

test('you can exclude by array of uuids', function () {
    Post::create(['title' => 'first post', 'uuid' => '8ab48e77-d9cd-4fe7-ace5-a5a428590c18']);
    Post::create(['title' => 'second post', 'uuid' => 'c7c26456-ddb0-45cd-9b1c-318296cce7a3']);
    Post::create(['title' => 'third post', 'uuid' => 'e99d440e-fa25-45f2-ba2f-7c4c48f6fb5d']);

    $uuids = [
        '8ab48e77-d9cd-4fe7-ace5-A5A428590C18',
        'c7c26456-ddb0-45cd-9b1c-318296cce7a3',
    ];

    $posts = Post::whereNotUuid($uuids)->get();

    expect($posts)
        ->count()->toEqual(1)
        ->get(0)->uuid->toEqual('e99d440e-fa25-45f2-ba2f-7c4c48f6fb5d');
});

test('you can search by array of efficient uuids', function () {
    EfficientUuidPost::create(['title' => 'first post', 'efficient_uuid' => '8ab48e77-d9cd-4fe7-ace5-a5a428590c18']);
    EfficientUuidPost::create(['title' => 'second post', 'efficient_uuid' => 'c7c26456-ddb0-45cd-9b1c-318296cce7a3']);

    expect(EfficientUuidPost::whereUuid([
        '8ab48e77-d9cd-4fe7-ace5-A5A428590C18',
        'c7c26456-ddb0-45cd-9b1c-318296cce7a3',
    ], 'efficient_uuid'))->count()->toEqual(2);
});

test('you can exclude by array of efficient uuids', function () {
    EfficientUuidPost::create(['title' => 'first post', 'efficient_uuid' => '8ab48e77-d9cd-4fe7-ace5-a5a428590c18']);
    EfficientUuidPost::create(['title' => 'second post', 'efficient_uuid' => 'c7c26456-ddb0-45cd-9b1c-318296cce7a3']);
    EfficientUuidPost::create(['title' => 'third post', 'efficient_uuid' => 'e99d440e-fa25-45f2-ba2f-7c4c48f6fb5d']);

    $uuids = [
        '8ab48e77-d9cd-4fe7-ace5-A5A428590C18',
        'c7c26456-ddb0-45cd-9b1c-318296cce7a3',
    ];

    $posts = EfficientUuidPost::whereNotUuid($uuids, 'efficient_uuid')->get();

    expect($posts)
        ->count()->toEqual(1)
        ->get(0)->efficient_uuid->toBe('e99d440e-fa25-45f2-ba2f-7c4c48f6fb5d');
});

test('you can search by array of uuids for custom column', function () {
    CustomCastUuidPost::create(['title' => 'first post', 'custom_uuid' => '8ab48e77-d9cd-4fe7-ace5-a5a428590c18']);
    CustomCastUuidPost::create(['title' => 'second post', 'custom_uuid' => 'c7c26456-ddb0-45cd-9b1c-318296cce7a3']);

    expect(CustomCastUuidPost::whereUuid([
        '8ab48e77-d9cd-4fe7-ace5-A5A428590C18',
        'c7c26456-ddb0-45cd-9b1c-318296cce7a3',
    ], 'custom_uuid'))->count()->toEqual(2);
});

test('you can exclude by array of uuids for custom column', function () {
    CustomCastUuidPost::create(['title' => 'first post', 'custom_uuid' => '8ab48e77-d9cd-4fe7-ace5-a5a428590c18']);
    CustomCastUuidPost::create(['title' => 'second post', 'custom_uuid' => 'c7c26456-ddb0-45cd-9b1c-318296cce7a3']);
    CustomCastUuidPost::create(['title' => 'third post', 'custom_uuid' => 'e99d440e-fa25-45f2-ba2f-7c4c48f6fb5d']);

    $uuids = [
        '8ab48e77-d9cd-4fe7-ace5-A5A428590C18',
        'c7c26456-ddb0-45cd-9b1c-318296cce7a3',
    ];

    $posts = CustomCastUuidPost::whereNotUuid($uuids, 'custom_uuid')->get();

    expect($posts)
        ->count()->toEqual(1)
        ->get(0)->custom_uuid->toBe('e99d440e-fa25-45f2-ba2f-7c4c48f6fb5d');
});

test('you can search by array of uuids which contains an invalid uuid', function () {
    Post::create(['title' => 'first post', 'uuid' => '8ab48e77-d9cd-4fe7-ace5-a5a428590c18']);
    Post::create(['title' => 'second post', 'uuid' => 'c7c26456-ddb0-45cd-9b1c-318296cce7a3']);

    expect(Post::whereUuid([
        '8ab48e77-d9cd-4fe7-ace5-A5A428590C18',
        'c7c26456-ddb0-45cd-9b1c-318296cce7a3',
        'this is invalid',
    ]))->count()->toEqual(2);
});

test('you can exclude by array of uuids which contains an invalid uuid', function () {
    Post::create(['title' => 'first post', 'uuid' => '8ab48e77-d9cd-4fe7-ace5-a5a428590c18']);
    Post::create(['title' => 'second post', 'uuid' => 'c7c26456-ddb0-45cd-9b1c-318296cce7a3']);
    Post::create(['title' => 'third post', 'uuid' => 'e99d440e-fa25-45f2-ba2f-7c4c48f6fb5d']);

    $uuids = [
        '8ab48e77-d9cd-4fe7-ace5-A5A428590C18',
        'c7c26456-ddb0-45cd-9b1c-318296cce7a3',
        'this is invalid',
    ];

    $posts = Post::whereNotUuid($uuids)->get();

    expect($posts)
        ->count()->toEqual(1)
        ->get(0)->uuid->toEqual('e99d440e-fa25-45f2-ba2f-7c4c48f6fb5d');
});

test('you can generate a uuid without casting', function () {
    $post = UncastPost::create(['title' => 'test post']);

    expect($post)->uuid->not->toBeNull();
});

test('you can generate a uuid with casting and a custom field name', function () {
    $post = CustomCastUuidPost::create(['title' => 'test post']);

    expect($post)->custom_uuid->not->toBeNull();
});

test('you can specify a uuid without casting', function () {
    $uuid = 'aa9832e0-5fea-492c-8fe2-6f2d1e209209';

    $post = UncastPost::create(['title' => 'test-post', 'uuid' => $uuid]);

    expect($post)->uuid->toBe($uuid);
});

test('you can find a model by uuid without casting', function () {
    $uuid = 'b270f651-4db8-407b-aade-8666aca2750e';

    UncastPost::create(['title' => 'test-post', 'uuid' => $uuid]);

    $post = UncastPost::whereUuid($uuid)->first();

    expect($post)
        ->toBeInstanceOf(UncastPost::class)
        ->uuid->toBe($uuid);
});

test('you can exclude a model by uuid without casting', function () {
    $uuid = 'b270f651-4db8-407b-aade-8666aca2750e';

    UncastPost::create(['title' => 'test-post', 'uuid' => $uuid]);

    $post = UncastPost::whereNotUuid($uuid)->first();

    expect($post)->toBeNull();
});

test('you can find a model by uuid with casting', function () {
    $uuid = 'b270f651-4db8-407b-aade-8666aca2750e';

    EfficientUuidPost::create(['title' => 'efficient uuid', 'uuid' => $uuid]);

    $post = EfficientUuidPost::whereUuid($uuid)->first();

    expect($post)
        ->toBeInstanceOf(EfficientUuidPost::class)
        ->uuid->toBe($uuid);
});

test('you can exclude a model by uuid with casting', function () {
    $uuid = 'b270f651-4db8-407b-aade-8666aca2750e';

    EfficientUuidPost::create(['title' => 'efficient uuid', 'uuid' => $uuid]);

    $post = EfficientUuidPost::whereNotUuid($uuid)->first();

    expect($post)->toBeNull();
});

it('handles time ordered uuids', function () {
    $post = OrderedPost::create(['title' => 'test-post']);

    expect($post)
        ->toBeInstanceOf(OrderedPost::class)
        ->uuid->not->toBeNull();
});

it('allows configurable uuid column names', function () {
    $post = CustomUuidPost::create(['title' => 'test-post']);

    expect($post)->custom_uuid->not->toBeNull();
});

it('handles working with various uuid casts', function ($model, $column) {
    tap(factory($model)->create(), function ($post) use ($column) {
        expect($post->{$column})->not->toBeNull();
    });
})->with('factoriesWithUuidProvider');

it('handles setting an efficient uuid', function () {
    tap(EfficientUuidPost::create([
        'title' => 'Efficient uuid post',
        'efficient_uuid' => $uuid = $this->faker->uuid,
    ]), function ($post) use ($uuid) {
        expect($post)
            ->efficient_uuid->toEqual($uuid)
            ->getRawOriginal('efficient_uuid')->toBe(Uuid::fromString($uuid)->getBytes());
    });
});

it('handles an invalid uuid', function () {
    $uuid = 'b270f651-4db8-407b-aade-8666aca2750e';

    EfficientUuidPost::create(['title' => 'efficient uuid', 'efficient_uuid' => $uuid]);

    EfficientUuidPost::whereUuid('invalid uuid')->firstOrFail();
})->throws(ModelNotFoundException::class);

it('handles a null uuid column', function () {
    tap(Model::withoutEvents(function () {
        return Post::create([
            'title' => 'Nullable uuid',
            'uuid' => null,
        ]);
    }), function ($post) {
        expect($post)->uuid->toBeNull();
    });
});

it('handles a null efficient uuid column', function () {
    tap(Model::withoutEvents(function () {
        return EfficientUuidPost::create([
            'title' => 'Nullable uuid',
            'custom_efficient_uuid' => null,
        ]);
    }), function ($post) {
        expect($post)->custom_efficient_uuid->toBeNull();
    });
});

it('handles supported uuid versions', function ($model, $version) {
    tap($model::create(['title' => 'test title']), function ($model) use ($version) {
        $uuid = Uuid::fromString($model->uuid);

        if ($uuid->getFields() instanceof FieldsInterface) {
            expect($uuid->getFields())->getVersion()->toEqual($version);
        } else {
            throw new InvalidArgumentException("Version {$version} is not supported.");
        }
    });
})->with('uuidVersionsProvider');

it('handles queries with multiple uuid columns', function () {
    $post = factory(Post::class)->create([
        'uuid' => '4e6c964d-4e9b-4023-be0e-f5a6529b7184',
    ]);
    $comment = $post->comments()->save(factory(Comment::class)->make([
        'uuid' => '8b8f4d17-e2b9-4f9d-9b1d-4e94ef5db644',
    ]));

    tap($post->comments()->whereUuid($comment->uuid)->first(), function ($comment) {
        expect($comment)
            ->not->toBeNull()
            ->post->uuid->toEqual('4e6c964d-4e9b-4023-be0e-f5a6529b7184');
    });
});

dataset('factoriesWithUuidProvider', function () {
    return [
        'regular uuid' => [Post::class, 'uuid'],
        'custom uuid' => [CustomUuidPost::class, 'custom_uuid'],
        'efficient uuid' => [EfficientUuidPost::class, 'uuid'],
    ];
});

dataset('uuidVersionsProvider', function () {
    return [
        'uuid1' => [Uuid1Post::class, Uuid::UUID_TYPE_TIME],
        'uuid4' => [Uuid4Post::class, Uuid::UUID_TYPE_RANDOM],
        'uuid6' => [Uuid6Post::class, Uuid::UUID_TYPE_REORDERED_TIME],
        'uuid7' => [Uuid7Post::class, Uuid::UUID_TYPE_UNIX_TIME],
        'ordered' => [OrderedPost::class, Uuid::UUID_TYPE_REORDERED_TIME],
    ];
});
