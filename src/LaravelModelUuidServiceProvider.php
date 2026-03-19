<?php

namespace Dyrynda\Database\Support;

use Dyrynda\Database\Support\Exceptions\UnknownGrammarClass;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Support\Fluent;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelModelUuidServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('model-uuid')
            ->hasConfigFile()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command->publishConfigFile();
            });
    }

    public function packageRegistered()
    {
        Grammar::macro('typeEfficientUuid', function (Fluent $column) {
            return match (true) {
                $this instanceof MySqlGrammar => sprintf('binary(%d)', $column->length ?? 16),
                $this instanceof PostgresGrammar => 'bytea',
                $this instanceof SQLiteGrammar => 'blob(256)',
                default => throw new UnknownGrammarClass
            };
        });

        Blueprint::macro('efficientUuid', function ($column): ColumnDefinition {
            /** @var Blueprint $this */
            return $this->addColumn('efficientUuid', $column);
        });
    }
}
