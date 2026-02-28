<?php

declare(strict_types=1);

namespace Drupal\claude_agent_sdk\Runtime;

/**
 * Registry and executor for tools available within the Claude Agent runtime.
 *
 * The ToolExecutor acts as a central registry where ToolInterface
 * implementations are registered by name. During a session the runtime can
 * look up a tool, execute it with parameters, and receive a structured
 * result that is fed back into the agent loop.
 *
 * Usage:
 * @code
 * $executor = new ToolExecutor();
 * $executor->register($myTool);
 * $result = $executor->execute('my_tool', $session, ['key' => 'value']);
 * @endcode
 */
final class ToolExecutor {

  /**
   * Registered tools keyed by name.
   *
   * @var array<string, \Drupal\claude_agent_sdk\Runtime\ToolInterface>
   */
  private array $tools = [];

  /**
   * Registers a tool with the executor.
   *
   * If a tool with the same name is already registered it will be replaced.
   *
   * @param \Drupal\claude_agent_sdk\Runtime\ToolInterface $tool
   *   The tool to register.
   *
   * @return $this
   */
  public function register(ToolInterface $tool): self {
    $this->tools[$tool->getName()] = $tool;
    return $this;
  }

  /**
   * Checks whether a tool with the given name is registered.
   *
   * @param string $name
   *   The tool name.
   *
   * @return bool
   *   TRUE if the tool exists, FALSE otherwise.
   */
  public function has(string $name): bool {
    return isset($this->tools[$name]);
  }

  /**
   * Returns a registered tool by name.
   *
   * @param string $name
   *   The tool name.
   *
   * @return \Drupal\claude_agent_sdk\Runtime\ToolInterface
   *   The tool instance.
   *
   * @throws \InvalidArgumentException
   *   When no tool with the given name is registered.
   */
  public function get(string $name): ToolInterface {
    if (!$this->has($name)) {
      throw new \InvalidArgumentException(sprintf(
        'Tool "%s" is not registered. Available tools: %s',
        $name,
        implode(', ', array_keys($this->tools)) ?: '(none)',
      ));
    }

    return $this->tools[$name];
  }

  /**
   * Executes a registered tool by name within the given session.
   *
   * @param string $name
   *   The tool name.
   * @param \Drupal\claude_agent_sdk\Runtime\ClaudeAgentSession $session
   *   The active session.
   * @param array<string, mixed> $parameters
   *   Parameters forwarded to the tool.
   *
   * @return array<string, mixed>
   *   The tool result array.
   *
   * @throws \InvalidArgumentException
   *   When no tool with the given name is registered.
   */
  public function execute(string $name, ClaudeAgentSession $session, array $parameters = []): array {
    $tool = $this->get($name);
    return $tool->execute($session, $parameters);
  }

  /**
   * Returns an array of all registered tool names.
   *
   * @return string[]
   *   The tool names.
   */
  public function listTools(): array {
    return array_keys($this->tools);
  }

  /**
   * Returns descriptions for all registered tools.
   *
   * Useful for presenting available capabilities to the agent.
   *
   * @return array<string, string>
   *   An associative array of tool name => description.
   */
  public function describeTools(): array {
    $descriptions = [];
    foreach ($this->tools as $name => $tool) {
      $descriptions[$name] = $tool->getDescription();
    }
    return $descriptions;
  }

  /**
   * Removes a registered tool by name.
   *
   * @param string $name
   *   The tool name.
   *
   * @return $this
   */
  public function remove(string $name): self {
    unset($this->tools[$name]);
    return $this;
  }

}
