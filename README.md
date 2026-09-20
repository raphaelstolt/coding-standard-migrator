# Coding Standard Migrator

![Test Status](https://github.com/raphaelstolt/coding-standard-migrator/workflows/ci/badge.svg)
[![Version](http://img.shields.io/packagist/v/stolt/coding-standard-migrator.svg?style=flat)](https://packagist.org/packages/stolt/coding-standard-migrator)
![Downloads](https://img.shields.io/packagist/dt/stolt/coding-standard-migrator)
![PHP Version](https://img.shields.io/badge/php-8.3+-ff69b4.svg)
[![PDS Skeleton](https://img.shields.io/badge/pds-skeleton-blue.svg?style=flat)](https://github.com/php-pds/skeleton)
[![Lean dist package](https://img.shields.io/badge/lean-dist%20package-00ffb6.svg?style=flat)](https://github.com/raphaelstolt/lean-package-validator)

<p align="center">
    <img src="logo.png" 
         title="Coding Standard Migrator"
         alt="Coding standard migrator logo">
</p>

A CLI tool and library which migrates a PHP coding-standard configuration to another
coding standard. [Mago](https://mago.carthage.software) is the migration target, the supported sources are [PHP-CS-Fixer](https://cs.symfony.com),
[PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer), and [Pint](https://laravel.com/docs/pint).

| Migration path       | Source configuration                                                    |
|----------------------|-------------------------------------------------------------------------|
| `php-cs-fixer` → `mago` | `.php-cs-fixer.php`, `.php-cs-fixer.dist.php`, `.php_cs`, `.php_cs.dist` |
| `phpcs` → `mago`     | `phpcs.xml`, `phpcs.xml.dist`, `.phpcs.xml`, `.phpcs.xml.dist`, `ruleset.xml` |
| `pint` → `mago`      | `pint.json`, `.pint.json`                                               |

Since the coding standards do not overlap one to one, a migration is a starting point
and not a finished configuration. Every migration therefore comes with a report which
states what was translated, what the target standard does implicitly, and which rules
need a manual decision.

## Why Coding Standard Migrator?

Moving from PHP-CS-Fixer, PHP_CodeSniffer, or Pint to Mago is not just a configuration-file rename.

Different tools expose different rule sets and semantics.

Coding Standard Migrator makes that migration explicit:

1. analyze what can be migrated
2. identify gaps
3. generate a Mago configuration
4. review the migration report
5. verify the result with Mago

## What the Coding Standard Migrator does not do

The `Coding Standard Migrator` does not guarantee behavioural equivalence.

It translates configuration where a known Mago equivalent exists and explicitly reports mappings that require a manual
review.

## Installation

``` bash
composer require --dev stolt/coding-standard-migrator
```

## Usage

See how much of the configuration of the current directory a migration would carry
 over before running one:

``` bash
vendor/bin/cs-migrator analyze
```

``` console
 PHP-CS-Fixer configuration
 ──────────────────────────

Rules:                  10
Mappable:                6
Equivalent:              5
Partial:                 1
Unsupported:             2
No mapping known:        1
Disabled in source:      0
Linter candidates:       3
Redundant with Mago:     1

Migration confidence: 65%
```

`Mappable` is the sum of `Equivalent` and `Partial`, the rules which end up in the
migrated configuration. The other three rule counts each name a reason why a rule does
not: `Unsupported` and `No mapping known` need a manual decision, `Disabled in source`
never asked for anything. `Linter candidates` and `Redundant with Mago` are cross
sections of the same rules, the ones which need a `[linter.rules]` entry, and the ones
the Mago formatter applies anyway.

The migration confidence is the share of the source configuration which survives, with
a partial mapping counting half and a rule the target standard covers anyway or which
was disabled to begin with counting fully. Add `--verbose` to list the rules which need
a manual decision, or `--fail-under 80` to turn the confidence into a CI gate.

Migrate the PHP-CS-Fixer configuration of the current directory to a `mago.toml`:

``` bash
vendor/bin/cs-migrator migrate
```

Migrate a PHP_CodeSniffer ruleset or a Pint configuration instead:

``` bash
vendor/bin/cs-migrator migrate --from phpcs
vendor/bin/cs-migrator migrate --from pint
```

Preview the migration without writing anything:

``` bash
vendor/bin/cs-migrator migrate --dry-run
```

``` console
Mapped rules
------------

 ------------------------ ---------------------- ---------------------------------- 
  Rule                     Outcome                Mago
 ------------------------ ---------------------- ---------------------------------- 
  Config::setIndent()      formatter option       use-tabs = false, tab-width = 4
  @PSR12                   formatter option       preset = "psr-12"
  array_syntax             linter rule            array-style
  no_trailing_whitespace   covered by formatter   -
  header_comment           unsupported            -
 ------------------------ ---------------------- ---------------------------------- 
```

### Options of the `migrate` command

| Option                | Description                                                      |
|-----------------------|------------------------------------------------------------------|
| `--from`              | The coding standard to migrate from, defaults to `php-cs-fixer`  |
| `--to`                | The coding standard to migrate to, defaults to `mago`            |
| `--working-dir`, `-d` | The directory to run in, defaults to the current one             |
| `--config`, `-c`      | The source configuration file, autodetected when omitted         |
| `--target`, `-t`      | The file to write to, defaults to the target standard's default  |
| `--php-version`       | The PHP version to pin in the migrated configuration             |
| `--path`, `-p`        | A path the coding standard applies to, repeatable                |
| `--dry-run`           | Shows the migrated configuration instead of writing it           |
| `--force`, `-f`       | Overwrites an existing target configuration                      |
| `--fail-on-unmapped`  | Exits non-zero when a rule could not be migrated, made for CI    |

### Options of the `analyze` command

| Option                | Description                                                      |
|-----------------------|------------------------------------------------------------------|
| `--from`              | The coding standard to analyze, defaults to `php-cs-fixer`       |
| `--to`                | The coding standard to analyze the migration to, defaults to `mago` |
| `--working-dir`, `-d` | The directory to run in, defaults to the current one             |
| `--config`, `-c`      | The configuration file to analyze, autodetected when omitted     |
| `--fail-under`        | Exits non-zero below this migration confidence, made for CI      |

The command is also reachable as `analyse`. The supported standards and migration paths
are listed by the `standards` command.

## Usage via cpx

If you don't want to add `coding-standard-migrator` as a development dependency, you can run it directly with [cpx](https://cpx.dev/).

Install cpx globally:

```bash
composer global require cpx/cpx
```

Then run the migrator directly from your project:

```bash
cpx stolt/coding-standard-migrator analyze
```

To migrate your PHP-CS-Fixer configuration to Mago:

```bash
cpx stolt/coding-standard-migrator migrate
```

You can pass the same options as with the locally installed binary:

```bash
cpx stolt/coding-standard-migrator migrate --from pint
cpx stolt/coding-standard-migrator migrate --from phpcs
cpx stolt/coding-standard-migrator migrate --dry-run
cpx stolt/coding-standard-migrator analyze --fail-under 80
```

cpx keeps the package isolated from your project's Composer dependencies, so you can use `coding-standard-migrator`
without adding it to `composer.json`. It also caches the package between runs, making later invocations faster.

> **Tip:** If `coding-standard-migrator` is already installed in your project, cpx can use the project's local binary
> first. This makes `cpx stolt/coding-standard-migrator ...` convenient both for projects that have the tool installed
> and for one-off migrations.

## Usage as a library

``` php
use Stolt\CodingStandardMigrator\Migration\MigrationEngine;
use Stolt\CodingStandardMigrator\Migration\MigrationRequest;
use Stolt\CodingStandardMigrator\Standard\Standard;

$engine = new MigrationEngine();

$request = new MigrationRequest(
    from: Standard::PhpCsFixer,
    to: Standard::Mago,
    sourceFile: $engine->locateSourceConfiguration(Standard::PhpCsFixer, \getcwd()),
    phpVersion: '8.3',
);

echo $engine->analyze($request)->confidence() . '%' . \PHP_EOL;

$result = $engine->migrate($request);

foreach ($result->report as $mapping) {
    echo $mapping->sourceRule . ': ' . $mapping->outcome->label() . \PHP_EOL;
}

if ($result->isComplete()) {
    \file_put_contents($result->targetFileName, $result->configuration);
}
```

## How a migration works

```
ConfigurationReader  ->  Ruleset  ->  RuleMapper  ->  MappingReport  ->  ConfigurationWriter
```

1. A `ConfigurationReader` turns the configuration file of the source standard into a
   tool-agnostic `SourceConfiguration`, holding a `Ruleset` plus the settings which live
   outside of the rules, such as the indent and the line ending.
2. A `RuleMapper` translates every `Rule` into a `Mapping` and collects them in a
   `MappingReport`. Each mapping carries one of these outcomes:

   | Outcome                | Meaning                                                          |
   |------------------------|------------------------------------------------------------------|
   | `linter rule`          | The rule became one or more rules of the target linter           |
   | `formatter option`     | The rule became one or more options of the target formatter      |
   | `covered by formatter` | The target formatter does this anyway, no configuration needed   |
   | `skipped`              | The rule was disabled in the source and needed no translation    |
   | `unsupported`          | The target standard knowingly has no equivalent                  |
   | `unknown`              | No mapping is known, the rule needs a manual decision            |

   A translated mapping additionally carries a `MappingFidelity`, either `equivalent`
   or `partial`. Mapping tables state their caveats as notes, which makes a note the
   default signal for a partial mapping, overridable per mapping.
3. A `ConfigurationWriter` renders the report as the configuration file of the target
   standard. Rules which need attention are added as comments to the generated file.
   `MigrationAnalysis` instead reduces the report to the counts the `analyze` command
   shows, which is why analyzing never touches a writer.

Mappings are only added to a
[`RuleTable`](src/Mapping/Mago/RuleTable.php) once they are verified against the
[Mago formatter reference](https://mago.carthage.software/main/en/tools/formatter/configuration-reference/)
and the [Mago linter rules](https://mago.carthage.software/main/en/tools/linter/rules/).
Everything else stays `unknown`, so a migration never invents a setting which does not
exist.

Rule sets are shared ground between the standards: a `@PSR12` rule of PHP-CS-Fixer, a
`<rule ref="PSR12"/>` of PHP_CodeSniffer, and a `"preset": "psr12"` of Pint all end up
as the same rule-set entry, which the tables map onto a Mago formatter preset.

There are two rule vocabularies and therefore two tables:

- [`PhpCsFixerToMago\MappingTable`](src/Mapping/PhpCsFixerToMago/MappingTable.php),
  which is shared by the PHP-CS-Fixer and the Pint migration, as Pint is a wrapper
  around PHP-CS-Fixer and configures its rules;
- [`PhpCodeSnifferToMago\MappingTable`](src/Mapping/PhpCodeSnifferToMago/MappingTable.php),
  which maps sniffs by their `Standard.Category.Sniff` name and ignores the error code
  of a `Standard.Category.Sniff.ErrorCode` reference.

## Supporting another coding standard

Adding one means adding implementations, not changing the engine:

- as a **migration source**: implement `Reader\ConfigurationReader` plus a
  `Mapping\RuleMapper` per target standard. For Mago as the target, extend
  `Mapping\Mago\AbstractRuleMapper`, which brings the pipeline, and point it at a
  `Mapping\Mago\RuleTable`;
- as a **migration target**: implement `Writer\ConfigurationWriter` plus a
  `Mapping\RuleMapper` per source standard.

Register them in `Standard\StandardRegistry` and the `migrate` command picks up the new
migration path.

## Known limitations

- Rule sets without a Mago preset, such as `@Symfony` or the `Squiz` standard, are not
  expanded into their rules. They are reported so they can be migrated rule by rule,
  for instance by way of `php-cs-fixer describe` or `phpcs --explain`.
- For PHP-CS-Fixer and Pint the paths of the target configuration are guessed from the
  directories next to the source configuration, as PHP-CS-Fixer keeps them in a
  `Symfony\Component\Finder\Finder` instance which does not expose them, and Pint
  defaults to the whole project. Pass `--path` to be explicit. PHP_CodeSniffer rulesets
  declare their paths, so they are read from the `<file>` elements.
- The mapping tables cover the commonly used rules and sniffs, not all of the several
  hundred PHP-CS-Fixer rules or the sniffs of every third party standard.
- Exclusions are not migrated yet, neither the `exclude`, `notName`, and `notPath` keys
  of a Pint configuration nor the `<exclude-pattern>` elements of a PHP_CodeSniffer
  ruleset end up in Mago's `source.excludes`.

## Contributing

If you're considering contributing to this library, have a look at this repository's [CONTRIBUTING.md](.github/CONTRIBUTING.md)
for more advice.

## License

This package is licensed under the MIT license, see [LICENSE.md](LICENSE.md).
