<?php

declare(strict_types=1);

namespace Drupal\claude_agent_sdk\Runtime;

/**
 * Immutable session value object for Claude Agent runtime calls.
 */
final class ClaudeAgentSession {

  /**
   * The unique session identifier.
   *
   * @var string
   */
  private string $id;

  /**
   * Session context for the runtime.
   *
   * @var array<string, mixed>
   */
  private array $context;

  /**
   * Creates a new runtime session.
   *
   * @param string $id
   *   The session id.
   * @param array<string, mixed> $context
   *   Optional context payload.
   */
  public function __construct(string $id, array $context = []) {
    $this->id = $id;
    $this->context = $context;
  }

  /**
   * Returns the session id.
   */
  public function id(): string {
    return $this->id;
  }

  /**
   * Returns the session context.
   *
   * @return array<string, mixed>
   *   The context payload.
   */
  public function context(): array {
    return $this->context;
  }

}
