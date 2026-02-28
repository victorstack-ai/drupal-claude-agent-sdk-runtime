<?php

declare(strict_types=1);

namespace Drupal\claude_agent_sdk\Tests\Unit\Runtime;

use Drupal\claude_agent_sdk\Runtime\ClaudeAgentResult;
use Drupal\claude_agent_sdk\Runtime\ClaudeAgentRuntime;
use Drupal\claude_agent_sdk\Runtime\ClaudeAgentSession;
use Drupal\claude_agent_sdk\Runtime\ToolExecutor;
use Drupal\claude_agent_sdk\Runtime\ToolInterface;
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
   * @covers ::startSession
   */
  public function testStartSessionWithoutContextReturnsEmptyContext(): void {
    $runtime = new ClaudeAgentRuntime();
    $session = $runtime->startSession();

    $this->assertSame([], $session->context());
    $this->assertFalse($session->isClosed());
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

  /**
   * @covers ::closeSession
   */
  public function testCloseSessionMarksSessionClosed(): void {
    $runtime = new ClaudeAgentRuntime();
    $session = $runtime->startSession(['env' => 'staging']);

    $this->assertFalse($session->isClosed());

    $closed = $runtime->closeSession($session);

    $this->assertTrue($closed->isClosed());
    $this->assertSame($session->id(), $closed->id());
  }

  /**
   * @covers ::closeSession
   */
  public function testCloseSessionTwiceThrowsException(): void {
    $runtime = new ClaudeAgentRuntime();
    $session = $runtime->startSession();
    $runtime->closeSession($session);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('already closed');
    $runtime->closeSession($session);
  }

  /**
   * @covers ::run
   */
  public function testRunOnClosedSessionThrowsException(): void {
    $runtime = new ClaudeAgentRuntime();
    $session = $runtime->startSession();
    $runtime->closeSession($session);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('closed session');
    $runtime->run($session, 'This should fail');
  }

  /**
   * Tests the full session lifecycle: create, execute, close.
   *
   * @covers ::startSession
   * @covers ::run
   * @covers ::closeSession
   */
  public function testFullSessionLifecycle(): void {
    $runtime = new ClaudeAgentRuntime();

    // 1. Create session.
    $session = $runtime->startSession(['project' => 'lifecycle-test']);
    $this->assertFalse($session->isClosed());
    $this->assertStringStartsWith('claude_', $session->id());

    // 2. Execute a prompt.
    $result = $runtime->run($session, 'Plan the migration');
    $this->assertInstanceOf(ClaudeAgentResult::class, $result);
    $this->assertSame($session->id(), $result->sessionId());
    $this->assertStringContainsString('Session ' . $session->id(), $result->output());

    // 3. Close session.
    $runtime->closeSession($session);
    $this->assertTrue($session->isClosed());

    // 4. Verify no further runs are allowed.
    $this->expectException(\RuntimeException::class);
    $runtime->run($session, 'Should not work');
  }

  /**
   * @covers ::executeTool
   */
  public function testExecuteToolReturnsResult(): void {
    $tool = $this->createMock(ToolInterface::class);
    $tool->method('getName')->willReturn('test_tool');
    $tool->method('getDescription')->willReturn('A test tool');
    $tool->method('execute')->willReturn(['output' => 'tool output value']);

    $executor = new ToolExecutor();
    $executor->register($tool);

    $runtime = new ClaudeAgentRuntime($executor);
    $session = $runtime->startSession();

    $result = $runtime->executeTool($session, 'test_tool', ['key' => 'val']);

    $this->assertSame('tool:test_tool', $result->input());
    $this->assertSame('tool output value', $result->output());
    $this->assertSame('test_tool', $result->metadata()['tool']);
  }

  /**
   * @covers ::executeTool
   */
  public function testExecuteToolOnClosedSessionThrowsException(): void {
    $runtime = new ClaudeAgentRuntime();
    $session = $runtime->startSession();
    $runtime->closeSession($session);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('closed session');
    $runtime->executeTool($session, 'any_tool');
  }

  /**
   * @covers ::toolExecutor
   */
  public function testToolExecutorReturnsInstance(): void {
    $runtime = new ClaudeAgentRuntime();
    $this->assertInstanceOf(ToolExecutor::class, $runtime->toolExecutor());
  }

}
