<?php

declare(strict_types=1);

use Dyrynda\Database\Support\GeneratesUuid;

dataset('provider_for_it_handles_uuid_versions', function () {
    return [
        ['uuid1', 'uuid1'],
        ['uuid4', 'uuid4'],
        ['uuid6', 'uuid6'],
        ['ordered', 'uuid6'],
        ['uuid999', 'uuid4'],
        ['uuid7', 'uuid7'],
    ];
});

it('handles uuid versions', function ($version, $resolved) {
    $generator = Mockery::mock(UuidTestClass::class)->makePartial();
    $generator->shouldReceive('uuidVersion')->once()->andReturn($version);

    expect($generator->resolveUuidVersion())->toBe($resolved);
})->with('provider_for_it_handles_uuid_versions');

class UuidTestClass
{
    use GeneratesUuid;
}
