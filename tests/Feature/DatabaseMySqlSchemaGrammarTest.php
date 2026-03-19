<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Tests\Concerns\MocksDatabaseConnection;

uses(MocksDatabaseConnection::class);

test('adding uuid', function () {
    $blueprint = new Blueprint($this->mockConnection('MySql'), 'users', function ($table) {
        $table->uuid('foo');
        $table->efficientUuid('bar');
    });

    expect($blueprint->toSql())->toEqual([
        'alter table `users` add `foo` char(36) not null',
        'alter table `users` add `bar` binary(16) not null',
    ]);
});
