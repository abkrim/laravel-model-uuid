<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Tests\Concerns\MocksDatabaseConnection;

uses(MocksDatabaseConnection::class);

test('adding uuid', function () {
    $blueprint = new Blueprint($this->mockConnection('SQLite'), 'users', function ($table) {
        $table->uuid('foo');
        $table->efficientUuid('bar');
    });

    expect($blueprint->toSql())->toEqual([
        'alter table "users" add column "foo" varchar not null',
        'alter table "users" add column "bar" blob(256) not null',
    ]);
});
