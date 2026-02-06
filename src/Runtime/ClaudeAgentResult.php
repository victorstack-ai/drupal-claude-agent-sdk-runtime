<?php

declare(strict_types=1);

namespace Drupal\claude_agent_sdk\Runtime;

/**
 * Value object returned from the runtime.
 */
final class ClaudeAgentResult {

  /**
   * The originating session id.
   *
   * @var string
   */
  private string $sessionId;

  /**
   * The input prompt submitted to the runtime.
   *
   * @var string
   */
  private string $input;

  /**
   * The output response.
   *
   * @var string
   */
  private string $output;

  /**
   * Metadata for the response.
   *
   * @var array<string, mixed>
   */
  private array $metadata;

  /**
   * Creates a runtime result.
   *
   * @param string $session_id
   *   The session id.
   * @param string $input
   *   The input prompt.
   * @param string $output
   *   The output response.
   * @param array<string, mixed> $metadata
   *   Additional metadata for the response.
   */
  public function __construct(string $session_id, string $input, string $output, array $metadata = []) {
    $this->sessionId = $session_id;
    $this->input = $input;
    $this->output = $output;
    $this->metadata = $metadata;
  }

  /**
   * Returns the session id.
   */
  public function sessionId(): string {
    return $this->sessionId;
  }

  /**
   * Returns the input prompt.
   */
  public function input(): string {
    return $this->input;
  }

  /**
   * Returns the output response.
   */
  public function output(): string {
    return $this->output;
  }

  /**
   * Returns response metadata.
   *
   * @return array<string, mixed>
   *   The metadata payload.
   */
  public function metadata(): array {
    return $this->metadata;
  }

}
