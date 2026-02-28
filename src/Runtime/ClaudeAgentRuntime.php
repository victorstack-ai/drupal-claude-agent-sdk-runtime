<?php

declare(strict_types=1);

namespace Drupal\claude_agent_sdk\Runtime;

/**
 * Lightweight runtime facade for Claude Agent SDK experiments.
 *
 * This is the primary entry point for interacting with the runtime.
 * It manages the full session lifecycle (create, execute, close) and
 * delegates tool invocations to a ToolExecutor instance.
 */
class ClaudeAgentRuntime {

  /**
   * The runtime identifier.
   *
   * @var string
   */
  private string $runtimeName = 'Claude Code Runtime';

  /**
   * The tool executor for this runtime.
   *
   * @var \Drupal\claude_agent_sdk\Runtime\ToolExecutor
   */
  private ToolExecutor $toolExecutor;

  /**
   * Constructs a ClaudeAgentRuntime.
   *
   * @param \Drupal\claude_agent_sdk\Runtime\ToolExecutor|null $tool_executor
   *   An optional tool executor. A new instance is created if none is provided.
   */
  public function __construct(?ToolExecutor $tool_executor = NULL) {
    $this->toolExecutor = $tool_executor ?? new ToolExecutor();
  }

  /**
   * Returns the tool executor for this runtime.
   *
   * @return \Drupal\claude_agent_sdk\Runtime\ToolExecutor
   *   The tool executor instance.
   */
  public function toolExecutor(): ToolExecutor {
    return $this->toolExecutor;
  }

  /**
   * Starts a runtime session with optional context.
   *
   * @param array<string, mixed> $context
   *   Optional context data.
   *
   * @return \Drupal\claude_agent_sdk\Runtime\ClaudeAgentSession
   *   A new open session.
   */
  public function startSession(array $context = []): ClaudeAgentSession {
    $id = 'claude_' . bin2hex(random_bytes(8));
    return new ClaudeAgentSession($id, $context);
  }

  /**
   * Closes an active session.
   *
   * After closing, the session is marked as closed and no further operations
   * should be performed on it.
   *
   * @param \Drupal\claude_agent_sdk\Runtime\ClaudeAgentSession $session
   *   The session to close.
   *
   * @return \Drupal\claude_agent_sdk\Runtime\ClaudeAgentSession
   *   The closed session.
   *
   * @throws \RuntimeException
   *   If the session is already closed.
   */
  public function closeSession(ClaudeAgentSession $session): ClaudeAgentSession {
    if ($session->isClosed()) {
      throw new \RuntimeException(sprintf(
        'Session "%s" is already closed.',
        $session->id(),
      ));
    }

    $session->close();
    return $session;
  }

  /**
   * Runs a prompt through the runtime.
   *
   * @param \Drupal\claude_agent_sdk\Runtime\ClaudeAgentSession $session
   *   The active session.
   * @param string $input
   *   The input prompt.
   *
   * @return \Drupal\claude_agent_sdk\Runtime\ClaudeAgentResult
   *   The structured result.
   *
   * @throws \RuntimeException
   *   If the session is closed.
   */
  public function run(ClaudeAgentSession $session, string $input): ClaudeAgentResult {
    if ($session->isClosed()) {
      throw new \RuntimeException(sprintf(
        'Cannot run on closed session "%s".',
        $session->id(),
      ));
    }

    $metadata = [
      'runtime' => $this->runtimeName,
      'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeImmutable::ATOM),
      'tokens_estimate' => $this->estimateTokens($input),
    ];

    $output = $this->renderOutput($session, $input);

    return new ClaudeAgentResult($session->id(), $input, $output, $metadata);
  }

  /**
   * Executes a registered tool within a session.
   *
   * @param \Drupal\claude_agent_sdk\Runtime\ClaudeAgentSession $session
   *   The active session.
   * @param string $tool_name
   *   The name of the tool to execute.
   * @param array<string, mixed> $parameters
   *   Parameters forwarded to the tool.
   *
   * @return \Drupal\claude_agent_sdk\Runtime\ClaudeAgentResult
   *   The result wrapping the tool output.
   *
   * @throws \RuntimeException
   *   If the session is closed.
   * @throws \InvalidArgumentException
   *   If the tool is not registered.
   */
  public function executeTool(ClaudeAgentSession $session, string $tool_name, array $parameters = []): ClaudeAgentResult {
    if ($session->isClosed()) {
      throw new \RuntimeException(sprintf(
        'Cannot execute tool on closed session "%s".',
        $session->id(),
      ));
    }

    $tool_result = $this->toolExecutor->execute($tool_name, $session, $parameters);

    $metadata = [
      'runtime' => $this->runtimeName,
      'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeImmutable::ATOM),
      'tool' => $tool_name,
      'parameters' => $parameters,
    ];

    $output = $tool_result['output'] ?? json_encode($tool_result);

    return new ClaudeAgentResult(
      $session->id(),
      sprintf('tool:%s', $tool_name),
      is_string($output) ? $output : json_encode($output),
      $metadata,
    );
  }

  /**
   * Renders a predictable response body for the runtime.
   */
  private function renderOutput(ClaudeAgentSession $session, string $input): string {
    $context = $session->context();
    $context_list = [];
    foreach ($context as $key => $value) {
      $context_list[] = sprintf('%s=%s', $key, is_scalar($value) ? (string) $value : json_encode($value));
    }

    $context_label = $context_list ? implode(', ', $context_list) : 'none';

    return sprintf(
      "[%s] Session %s\nInput: %s\nContext: %s\nOutput: Drafted a plan with steps and checks.",
      $this->runtimeName,
      $session->id(),
      $input,
      $context_label
    );
  }

  /**
   * Estimates token usage for a prompt.
   */
  private function estimateTokens(string $input): int {
    $words = preg_split('/\s+/', trim($input));
    $word_count = $words ? count(array_filter($words)) : 0;

    return max(1, (int) ceil($word_count * 1.3));
  }

}
