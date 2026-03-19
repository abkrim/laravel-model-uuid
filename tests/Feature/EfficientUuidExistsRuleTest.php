<?php

declare(strict_types=1);

use Dyrynda\Database\Support\Rules\EfficientUuidExists;
use Ramsey\Uuid\Uuid;
use Tests\Fixtures\EfficientUuidPost;

it('passes valid existing uuid', function () {
    /** @var EfficientUuidPost $post */
    $post = factory(EfficientUuidPost::class)->create();

    $rule = new EfficientUuidExists(EfficientUuidPost::class, 'efficient_uuid');

    expect($rule->passes('efficient_uuid', $post->efficient_uuid))->toBeTrue();
});

it('fails on non existing uuid', function () {
    $uuid = Uuid::uuid4();

    $rule = new EfficientUuidExists(EfficientUuidPost::class);

    expect($rule->passes('post_id', $uuid))->toBeFalse();
});

it('fails on any non uuid invalid strings', function () {
    $uuid = '1235123564354633';

    $rule = new EfficientUuidExists(EfficientUuidPost::class, 'uuid');

    expect($rule->passes('post_id', $uuid))->toBeFalse();
});

it('works with custom uuid column name', function () {
    /** @var EfficientUuidPost $post */
    $post = factory(EfficientUuidPost::class)->create();

    $rule = new EfficientUuidExists(EfficientUuidPost::class, 'custom_efficient_uuid');

    expect($rule->passes('custom_efficient_uuid', $post->custom_efficient_uuid))->toBeTrue();
});
