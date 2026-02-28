<?php

declare(strict_types=1);

namespace Drupal\claude_agent_sdk\Runtime;

/**
 * Session value object for Claude Agent runtime calls.
 *
 * Tracks the session identifier, context payload, and open/closed state.
 * Once a session is closed no further operations should be performed on it.
 */
class ClaudeAgentSession {

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
   * Whether the session has been closed.
   *
   * @var bool
   */
  private bool $closed = FALSE;

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

  /**
   * Returns whether the session is closed.
   *
   * @return bool
   *   TRUE if the session has been closed.
   */
  public function isClosed(): bool {
    return $this->closed;
  }

  /**
   * Marks the session as closed.
   *
   * Once closed a session cannot be reopened. The runtime will reject any
   * further operations on a closed session.
   *
   * @throws \RuntimeException
   *   If the session is already closed.
   */
  public function close(): void {
    if ($this->closed) {
      throw new \RuntimeException(sprintf(
        'Session "%s" is already closed.',
        $this->id,
      ));
    }

    $this->closed = TRUE;
  }

}
