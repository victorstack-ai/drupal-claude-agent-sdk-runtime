<?php

declare(strict_types=1);

namespace Drupal\claude_agent_sdk\Runtime;

/**
 * Lightweight runtime facade for Claude Agent SDK experiments.
 */
final class ClaudeAgentRuntime {

  /**
   * The runtime identifier.
   *
   * @var string
   */
  private string $runtimeName = 'Claude Code Runtime';

  /**
   * Starts a runtime session with optional context.
   *
   * @param array<string, mixed> $context
   *   Optional context data.
   */
  public function startSession(array $context = []): ClaudeAgentSession {
    $id = 'claude_' . bin2hex(random_bytes(8));
    return new ClaudeAgentSession($id, $context);
  }

  /**
   * Runs a prompt through the runtime.
   *
   * @param \Drupal\claude_agent_sdk\Runtime\ClaudeAgentSession $session
   *   The active session.
   * @param string $input
   *   The input prompt.
   */
  public function run(ClaudeAgentSession $session, string $input): ClaudeAgentResult {
    $metadata = [
      'runtime' => $this->runtimeName,
      'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeImmutable::ATOM),
      'tokens_estimate' => $this->estimateTokens($input),
    ];

    $output = $this->renderOutput($session, $input);

    return new ClaudeAgentResult($session->id(), $input, $output, $metadata);
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
