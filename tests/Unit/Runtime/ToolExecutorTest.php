<?php

declare(strict_types=1);

namespace Drupal\claude_agent_sdk\Tests\Unit\Runtime;

use Drupal\claude_agent_sdk\Runtime\ClaudeAgentSession;
use Drupal\claude_agent_sdk\Runtime\ToolExecutor;
use Drupal\claude_agent_sdk\Runtime\ToolInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\claude_agent_sdk\Runtime\ToolExecutor
 */
final class ToolExecutorTest extends TestCase {

  /**
   * @covers ::register
   * @covers ::has
   */
  public function testRegisterAndHas(): void {
    $tool = $this->createToolMock('search', 'Search tool');
    $executor = new ToolExecutor();

    $this->assertFalse($executor->has('search'));

    $executor->register($tool);

    $this->assertTrue($executor->has('search'));
  }

  /**
   * @covers ::get
   */
  public function testGetReturnsRegisteredTool(): void {
    $tool = $this->createToolMock('code_gen', 'Code generator');
    $executor = new ToolExecutor();
    $executor->register($tool);

    $retrieved = $executor->get('code_gen');

    $this->assertSame($tool, $retrieved);
  }

  /**
   * @covers ::get
   */
  public function testGetThrowsForUnregisteredTool(): void {
    $executor = new ToolExecutor();

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Tool "missing" is not registered');
    $executor->get('missing');
  }

  /**
   * @covers ::execute
   */
  public function testExecuteDelegatesToTool(): void {
    $session = new ClaudeAgentSession('sess_exec', ['env' => 'test']);

    $tool = $this->createMock(ToolInterface::class);
    $tool->method('getName')->willReturn('analyzer');
    $tool->method('execute')
      ->with($session, ['file' => 'app.php'])
      ->willReturn(['output' => 'Analysis complete', 'issues' => 0]);

    $executor = new ToolExecutor();
    $executor->register($tool);

    $result = $executor->execute('analyzer', $session, ['file' => 'app.php']);

    $this->assertSame('Analysis complete', $result['output']);
    $this->assertSame(0, $result['issues']);
  }

  /**
   * @covers ::execute
   */
  public function testExecuteThrowsForUnregisteredTool(): void {
    $session = new ClaudeAgentSession('sess_fail');
    $executor = new ToolExecutor();

    $this->expectException(\InvalidArgumentException::class);
    $executor->execute('nonexistent', $session);
  }

  /**
   * @covers ::listTools
   */
  public function testListToolsReturnsRegisteredNames(): void {
    $executor = new ToolExecutor();
    $executor->register($this->createToolMock('alpha', 'Alpha tool'));
    $executor->register($this->createToolMock('beta', 'Beta tool'));

    $names = $executor->listTools();

    $this->assertSame(['alpha', 'beta'], $names);
  }

  /**
   * @covers ::listTools
   */
  public function testListToolsEmptyByDefault(): void {
    $executor = new ToolExecutor();

    $this->assertSame([], $executor->listTools());
  }

  /**
   * @covers ::describeTools
   */
  public function testDescribeToolsReturnsNamesAndDescriptions(): void {
    $executor = new ToolExecutor();
    $executor->register($this->createToolMock('search', 'Searches files'));
    $executor->register($this->createToolMock('lint', 'Lints code'));

    $descriptions = $executor->describeTools();

    $this->assertSame([
      'search' => 'Searches files',
      'lint' => 'Lints code',
    ], $descriptions);
  }

  /**
   * @covers ::remove
   */
  public function testRemoveUnregistersTool(): void {
    $executor = new ToolExecutor();
    $executor->register($this->createToolMock('temp', 'Temporary'));

    $this->assertTrue($executor->has('temp'));

    $executor->remove('temp');

    $this->assertFalse($executor->has('temp'));
  }

  /**
   * @covers ::remove
   */
  public function testRemoveNonexistentToolIsNoOp(): void {
    $executor = new ToolExecutor();

    // Should not throw.
    $executor->remove('nonexistent');
    $this->assertFalse($executor->has('nonexistent'));
  }

  /**
   * @covers ::register
   */
  public function testRegisterReplacesExistingTool(): void {
    $tool_v1 = $this->createToolMock('versioned', 'Version 1');
    $tool_v2 = $this->createToolMock('versioned', 'Version 2');

    $executor = new ToolExecutor();
    $executor->register($tool_v1);
    $executor->register($tool_v2);

    $descriptions = $executor->describeTools();
    $this->assertSame('Version 2', $descriptions['versioned']);
    $this->assertCount(1, $executor->listTools());
  }

  /**
   * Creates a simple ToolInterface mock with a name and description.
   *
   * @param string $name
   *   The tool name.
   * @param string $description
   *   The tool description.
   *
   * @return \Drupal\claude_agent_sdk\Runtime\ToolInterface
   *   The mock tool.
   */
  private function createToolMock(string $name, string $description): ToolInterface {
    $tool = $this->createMock(ToolInterface::class);
    $tool->method('getName')->willReturn($name);
    $tool->method('getDescription')->willReturn($description);
    return $tool;
  }

}
