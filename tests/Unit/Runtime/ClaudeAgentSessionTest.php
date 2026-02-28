<?php

declare(strict_types=1);

namespace Drupal\claude_agent_sdk\Tests\Unit\Runtime;

use Drupal\claude_agent_sdk\Runtime\ClaudeAgentSession;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\claude_agent_sdk\Runtime\ClaudeAgentSession
 */
final class ClaudeAgentSessionTest extends TestCase {

  /**
   * @covers ::__construct
   * @covers ::id
   * @covers ::context
   */
  public function testConstructorSetsIdAndContext(): void {
    $session = new ClaudeAgentSession('sess_001', ['env' => 'test']);

    $this->assertSame('sess_001', $session->id());
    $this->assertSame(['env' => 'test'], $session->context());
  }

  /**
   * @covers ::__construct
   * @covers ::context
   */
  public function testDefaultContextIsEmpty(): void {
    $session = new ClaudeAgentSession('sess_002');

    $this->assertSame([], $session->context());
  }

  /**
   * @covers ::isClosed
   */
  public function testNewSessionIsNotClosed(): void {
    $session = new ClaudeAgentSession('sess_003');

    $this->assertFalse($session->isClosed());
  }

  /**
   * @covers ::close
   * @covers ::isClosed
   */
  public function testCloseMarksSessionAsClosed(): void {
    $session = new ClaudeAgentSession('sess_004');
    $session->close();

    $this->assertTrue($session->isClosed());
  }

  /**
   * @covers ::close
   */
  public function testClosingTwiceThrowsException(): void {
    $session = new ClaudeAgentSession('sess_005');
    $session->close();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('already closed');
    $session->close();
  }

  /**
   * @covers ::id
   * @covers ::context
   */
  public function testContextPreservesComplexData(): void {
    $context = [
      'project' => 'phoenix',
      'tags' => ['alpha', 'beta'],
      'config' => ['retries' => 3],
    ];

    $session = new ClaudeAgentSession('sess_006', $context);

    $this->assertSame($context, $session->context());
    $this->assertSame('sess_006', $session->id());
  }

}
