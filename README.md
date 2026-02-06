# Claude Agent SDK Runtime (Experimental)

This experimental Drupal module models a Claude Agent SDK runtime with "Claude Code" semantics. It exposes a lightweight runtime service that creates sessions and returns structured responses for tool-like commands.

## Why this module exists

Drupal teams experimenting with agent runtimes often need a consistent abstraction while the upstream SDKs evolve. This module provides a small, testable surface area that can later be swapped for real Claude Agent SDK calls.

## Usage

```php
/** @var \Drupal\claude_agent_sdk\Runtime\ClaudeAgentRuntime $runtime */
$runtime = \Drupal::service('claude_agent_sdk.runtime');
$session = $runtime->startSession(['project' => 'demo']);
$result = $runtime->run($session, 'Generate a migration plan');

$output = $result->output();
$metadata = $result->metadata();
```

## Status

- Experimental module intended for prototyping.
- No external API calls are made yet.

## Development

```bash
composer install
composer phpcs
composer phpunit
```
