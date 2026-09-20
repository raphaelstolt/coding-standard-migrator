# How to contribute

Thanks for considering to contribute to the `coding-standard-migrator`.

## Setting up a development environment

To set up a development environment, please follow the next shown instructions.

```bash
git clone git@github.com:raphaelstolt/coding-standard-migrator.git
composer install

// implement your changes

composer ci

// open pull request
```

Please follow these guidelines:

- All code __MUST__ follow the PSR-2 coding standard. Please see [PSR-2](http://www.php-fig.org/psr/psr-2/) for more details.

- Coding standard compliance __MUST__ be ensured before committing or opening pull requests by running `composer cs-lint` in the root directory of this repository.

- Commits __MUST__ follow the [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) conventions.

- All upstreamed contributions __MUST__ use [feature / topic branches](https://git-scm.com/book/en/v2/Git-Branching-Branching-Workflows) to ease merging.

- Please run `composer ci` before opening a pull request.
