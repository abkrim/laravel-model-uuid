<?php

declare(strict_types=1);

use Dyrynda\Database\Support\Exceptions\UnknownGrammarClass;
use Illuminate\Database\Schema\Blueprint;
use Tests\Concerns\MocksDatabaseConnection;

uses(MocksDatabaseConnection::class);

test('adding uuid', function () {
    $connection = $this->mockConnection('SqlServer');

    $blueprint = new Blueprint($connection, 'users', function ($table) {
        $table->uuid('foo');
        $table->efficientUuid('bar');
    });

    $this->expectException(UnknownGrammarClass::class);

    $blueprint->toSql();
});
