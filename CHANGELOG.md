# Changelog

All notable changes to this project will be documented in this file. This project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html) and the changelog
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added

- An `analyze` command, also reachable as `analyse`, which shows how much of a
  configuration a migration would carry over, before running one. `--fail-under` turns
  its migration confidence into a CI gate, `--verbose` lists the rules which need a
  manual decision.
- A `MappingFidelity` of `equivalent` or `partial` on every translated mapping, which is
  what lets the analysis tell a faithful translation from one whose scope or options
  need a review.
- A `migrate` command which migrates a PHP-CS-Fixer, PHP_CodeSniffer, or Pint
  configuration to a `mago.toml`, together with a report of the translated, implicitly
  covered, and unmapped rules.
- A `standards` command which lists the supported coding standards and migration paths.
- A migration engine with per-standard readers, writers, and rule mappers, so that
  another coding standard can be added without changing the engine.
- Reading `phpcs.xml` style rulesets, including sniff properties,
  `<severity>0</severity>` and `<exclude name="…"/>` opt outs, the paths of the `<file>`
  elements, the `tab-width` argument, and the `php_version` configuration.
- Reading `pint.json` configurations. Pint presets are normalised into rule sets, which
  lets this path reuse the PHP-CS-Fixer mapping table, as Pint configures PHP-CS-Fixer
  rules.
- `Mapping\Mago\AbstractRuleMapper` and `Mapping\Mago\RuleTable`, which hold the shared
  pipeline of all migrations targeting Mago, so that a new source standard only needs a
  reader and a rule table.
- A `php-version` which falls back to the version the source standard was configured
  for, when `--php-version` is not given.
