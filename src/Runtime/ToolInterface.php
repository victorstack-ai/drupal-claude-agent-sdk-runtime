<?php

declare(strict_types=1);

namespace Drupal\claude_agent_sdk\Runtime;

/**
 * Defines the contract for tools executable within a Claude Agent session.
 *
 * Implementations represent discrete capabilities (e.g. file search, code
 * generation, data retrieval) that the runtime can invoke on behalf of the
 * agent. Each tool declares a unique name, describes its purpose, and
 * provides an execute method that receives arbitrary parameters and returns
 * a result array.
 */
interface ToolInterface {

  /**
   * Returns the unique machine name of the tool.
   *
   * The name is used to register and look up the tool inside a ToolExecutor.
   * It must be a non-empty string containing only lowercase letters, digits,
   * and underscores (e.g. "file_search", "code_generate").
   *
   * @return string
   *   The tool name.
   */
  public function getName(): string;

  /**
   * Returns a human-readable description of the tool.
   *
   * @return string
   *   A short sentence describing what the tool does.
   */
  public function getDescription(): string;

  /**
   * Executes the tool within the given session.
   *
   * @param \Drupal\claude_agent_sdk\Runtime\ClaudeAgentSession $session
   *   The active session providing context.
   * @param array<string, mixed> $parameters
   *   Arbitrary key-value parameters for the tool invocation.
   *
   * @return array<string, mixed>
   *   An associative array with at least an 'output' key containing the
   *   tool result. Implementations may add additional keys as needed.
   */
  public function execute(ClaudeAgentSession $session, array $parameters = []): array;

}
