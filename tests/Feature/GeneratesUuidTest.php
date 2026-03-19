<?php

declare(strict_types=1);

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Support\Facades\Config;

it('gets default column name', function () {
    $testModelThatGeneratesUuid = new class
    {
        use GeneratesUuid;
    };

    expect($testModelThatGeneratesUuid->uuidColumn())
        ->toBe('uuid', 'The UUID column should be "uuid" when no default is configured.');

    Config::set('model-uuid.column_name', 'uuid_custom');

    expect($testModelThatGeneratesUuid->uuidColumn())
        ->toBe('uuid_custom', 'The UUID column should match the configured value.');
});

it('inherits uuid version from config', function () {
    Config::set('model-uuid.uuid_version', 'uuid1');

    $testClass = new class
    {
        use GeneratesUuid;
    };
    expect($testClass)->resolveUuidVersion()->toBe('uuid1');
});
it('defaults to uuid4 when config not set', function () {
    Config::set('model-uuid.uuid_version', null);

    $testClass = new class
    {
        use GeneratesUuid;
    };
    expect($testClass)->resolveUuidVersion()->toBe('uuid4');
});
it('defaults to uuid4 when config is empty', function () {
    Config::set('model-uuid.uuid_version', '');

    $testClass = new class
    {
        use GeneratesUuid;
    };

    expect($testClass)->resolveUuidVersion()->toBe('uuid4');
});

it('uses model definition as highest precedence', function () {
    Config::set('model-uuid.uuid_version', 'uuid7');

    $testClass = new class
    {
        use GeneratesUuid;

        public function uuidVersion(): ?string
        {
            return 'uuid6';
        }
    };

    expect($testClass)->resolveUuidVersion()->toBe('uuid6');
});
