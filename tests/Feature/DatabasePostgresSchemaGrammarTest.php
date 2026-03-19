<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Tests\Concerns\MocksDatabaseConnection;

uses(MocksDatabaseConnection::class);

test('adding uuid', function () {
    $blueprint = new Blueprint($this->mockConnection('Postgres'), 'users', function ($table) {
        $table->uuid('foo');
        $table->efficientUuid('bar');
    });

    expect($blueprint->toSql())->toEqual([
        'alter table "users" add column "foo" uuid not null',
        'alter table "users" add column "bar" bytea not null',
    ]);
});
