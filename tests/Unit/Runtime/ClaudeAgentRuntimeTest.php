<?php

declare(strict_types=1);

namespace Drupal\claude_agent_sdk\Tests\Unit\Runtime;

use Drupal\claude_agent_sdk\Runtime\ClaudeAgentRuntime;
use Drupal\claude_agent_sdk\Runtime\ClaudeAgentSession;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\claude_agent_sdk\Runtime\ClaudeAgentRuntime
 */
final class ClaudeAgentRuntimeTest extends TestCase {

  /**
   * @covers ::startSession
   */
  public function testStartSessionCreatesIdAndContext(): void {
    $runtime = new ClaudeAgentRuntime();
    $session = $runtime->startSession(['project' => 'apollo']);

    $this->assertInstanceOf(ClaudeAgentSession::class, $session);
    $this->assertStringStartsWith('claude_', $session->id());
    $this->assertSame(['project' => 'apollo'], $session->context());
  }

  /**
   * @covers ::run
   */
  public function testRunReturnsStructuredResult(): void {
    $runtime = new ClaudeAgentRuntime();
    $session = $runtime->startSession(['goal' => 'qa']);
    $result = $runtime->run($session, 'Prepare a deployment checklist');

    $this->assertSame($session->id(), $result->sessionId());
    $this->assertSame('Prepare a deployment checklist', $result->input());
    $this->assertStringContainsString('Claude Code Runtime', $result->output());
    $metadata = $result->metadata();
    $this->assertArrayHasKey('runtime', $metadata);
    $this->assertArrayHasKey('timestamp', $metadata);
    $this->assertArrayHasKey('tokens_estimate', $metadata);
    $this->assertSame('Claude Code Runtime', $metadata['runtime']);
    $this->assertGreaterThan(0, $metadata['tokens_estimate']);
  }

}
