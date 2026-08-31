# Contributing

Thanks for considering a contribution to this SDK.

## Requirements

- PHP 8.3 or higher
- [Composer](https://getcomposer.org/)

## Getting started

```bash
composer install
```

## Quality checks

Run the full quality gate before opening a pull request:

```bash
composer run quality
```

This runs, in order:

- `composer validate --strict`
- the test suite (`composer run test`)
- PHPStan at level 10 (`composer run analyse`)
- coding style checks (`composer run lint`; use `composer run lint:fix` to auto-fix)

All of these must pass. PHPStan runs at level 10 with no baseline; new code must not introduce errors or suppress them.

## Making changes

- Keep pull requests focused on a single change.
- Add or update tests for any behavioral change; bug fixes should include a regression test.
- Treat all public classes, methods, and signatures as part of the SDK's public API — avoid accidental breaking changes. If a change is intentionally breaking, call it out explicitly in the pull request description.
- Update `README.md` or `CHANGELOG.md` only where user-facing behavior changes; `CHANGELOG.md` is otherwise generated automatically by [Release Please](https://github.com/googleapis/release-please) — do not hand-edit past entries.

## Commit messages

This project uses [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add invoice creation support
fix: handle nullable customer email
docs: document pagination
refactor: extract response mapper
test: cover rate limit handling
chore: update development tooling
```

Mark breaking changes explicitly, either with `!` after the type (`feat!: ...`) or a `BREAKING CHANGE:` footer.

## Reporting security issues

Please do not open a public issue for security vulnerabilities — see [SECURITY.md](SECURITY.md).
