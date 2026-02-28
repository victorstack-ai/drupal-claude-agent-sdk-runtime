<?php

declare(strict_types=1);

namespace Drupal\claude_agent_sdk\Tests\Unit\Runtime;

use Drupal\claude_agent_sdk\Runtime\ClaudeAgentResult;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\claude_agent_sdk\Runtime\ClaudeAgentResult
 */
final class ClaudeAgentResultTest extends TestCase {

  /**
   * @covers ::__construct
   * @covers ::sessionId
   * @covers ::input
   * @covers ::output
   * @covers ::metadata
   */
  public function testResultExposesAllFields(): void {
    $metadata = [
      'runtime' => 'Claude Code Runtime',
      'timestamp' => '2025-01-01T00:00:00+00:00',
      'tokens_estimate' => 10,
    ];

    $result = new ClaudeAgentResult('sess_100', 'hello', 'world', $metadata);

    $this->assertSame('sess_100', $result->sessionId());
    $this->assertSame('hello', $result->input());
    $this->assertSame('world', $result->output());
    $this->assertSame($metadata, $result->metadata());
  }

  /**
   * @covers ::metadata
   */
  public function testMetadataDefaultsToEmptyArray(): void {
    $result = new ClaudeAgentResult('sess_101', 'in', 'out');

    $this->assertSame([], $result->metadata());
  }

  /**
   * @covers ::sessionId
   */
  public function testSessionIdIsPreserved(): void {
    $result = new ClaudeAgentResult('claude_abc123', 'prompt', 'response');

    $this->assertSame('claude_abc123', $result->sessionId());
  }

  /**
   * @covers ::output
   */
  public function testOutputPreservesMultilineContent(): void {
    $output = "Line 1\nLine 2\nLine 3";
    $result = new ClaudeAgentResult('sess_102', 'prompt', $output);

    $this->assertSame($output, $result->output());
    $this->assertStringContainsString("\n", $result->output());
  }

  /**
   * @covers ::metadata
   */
  public function testMetadataPreservesNestedStructures(): void {
    $metadata = [
      'tool' => 'file_search',
      'parameters' => ['path' => '/src', 'pattern' => '*.php'],
      'timing' => ['start' => 0, 'end' => 42],
    ];

    $result = new ClaudeAgentResult('sess_103', 'in', 'out', $metadata);

    $this->assertSame('file_search', $result->metadata()['tool']);
    $this->assertSame('*.php', $result->metadata()['parameters']['pattern']);
    $this->assertSame(42, $result->metadata()['timing']['end']);
  }

}
