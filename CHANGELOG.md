# Changelog

All notable changes to `breadcrumbs` will be documented in this file.

## v1.1.0 - 2026-06-02

### Added

- `Breadcrumbs::current()` now accepts explicit parameters (`current(mixed ...$params)`) that override the current route's bound parameters. This lets you pass an already-resolved model to a breadcrumb closure when the route only binds a scalar (e.g. a slug), avoiding a second lookup. Parameters are matched positionally, so the closure's parameter name does not matter.
- The `<x-breadcrumbs />` Blade component forwards its `:params` to `current()` when no `route-name` is given, so `<x-breadcrumbs :params="[$post]" />` resolves the current route with the supplied object.

Calling `current()` with no arguments is unchanged and remains fully backward compatible.

## v1.0.0 - 2026-03-19

Initial Release
