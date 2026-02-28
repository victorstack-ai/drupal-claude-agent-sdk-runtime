# Claude Agent SDK Runtime

A Drupal module that provides a lightweight abstraction layer for Claude Agent SDK semantics. It models the core concepts of an agent runtime -- sessions, tool execution, and structured results -- so that Drupal teams can prototype agent-driven workflows today and swap in the real Claude Agent SDK when it becomes available.

## What It Provides

- **Session Management** -- Create, use, and close sessions that carry context across multiple runtime operations. Sessions enforce a strict lifecycle: once closed, no further operations are accepted.
- **Tool Execution Framework** -- Register tools that implement `ToolInterface`, then execute them within a session through the `ToolExecutor`. This mirrors how real agent runtimes expose discrete capabilities (file search, code generation, data retrieval) to an LLM.
- **Result Wrapping** -- Every runtime operation returns a `ClaudeAgentResult` value object containing the session ID, input, output, and metadata (timestamps, token estimates, tool information).
- **Deterministic Mock Responses** -- The runtime currently returns predictable, structured responses without making external API calls, making it safe for prototyping and testing.

## Architecture

```
ClaudeAgentRuntime (facade)
├── startSession(context) -> ClaudeAgentSession
├── run(session, input) -> ClaudeAgentResult
├── executeTool(session, name, params) -> ClaudeAgentResult
├── closeSession(session) -> ClaudeAgentSession
└── toolExecutor() -> ToolExecutor

ToolExecutor (registry)
├── register(ToolInterface)
├── execute(name, session, params) -> array
├── has(name) / get(name) / remove(name)
├── listTools() -> string[]
└── describeTools() -> array<name, description>

ToolInterface (contract)
├── getName() -> string
├── getDescription() -> string
└── execute(session, params) -> array

ClaudeAgentSession (value object)
├── id() / context()
├── isClosed() / close()

ClaudeAgentResult (value object)
├── sessionId() / input() / output() / metadata()
```

## Requirements

- PHP >= 8.1
- Drupal 10 or 11

## Installation

1. Clone or download into your Drupal modules directory:

   ```bash
   cd /path/to/drupal/modules/custom
   git clone https://github.com/your-org/drupal-claude-agent-sdk-runtime.git claude_agent_sdk
   ```

2. Install dependencies:

   ```bash
   cd claude_agent_sdk
   composer install
   ```

3. Enable the module:

   ```bash
   drush en claude_agent_sdk
   ```

## Usage

### Basic Session Lifecycle

```php
/** @var \Drupal\claude_agent_sdk\Runtime\ClaudeAgentRuntime $runtime */
$runtime = \Drupal::service('claude_agent_sdk.runtime');

// 1. Start a session with context.
$session = $runtime->startSession(['project' => 'migration', 'env' => 'staging']);

// 2. Run a prompt.
$result = $runtime->run($session, 'Generate a migration plan for the user entity');

// 3. Read the result.
$output   = $result->output();      // The response body.
$metadata = $result->metadata();    // Runtime name, timestamp, token estimate.

// 4. Close the session when done.
$runtime->closeSession($session);
```

### Registering and Executing Tools

```php
use Drupal\claude_agent_sdk\Runtime\ClaudeAgentSession;
use Drupal\claude_agent_sdk\Runtime\ToolInterface;

// Define a tool by implementing the interface.
class FileSearchTool implements ToolInterface {

  public function getName(): string {
    return 'file_search';
  }

  public function getDescription(): string {
    return 'Searches the codebase for files matching a pattern.';
  }

  public function execute(ClaudeAgentSession $session, array $parameters = []): array {
    $pattern = $parameters['pattern'] ?? '*';
    // Your search logic here...
    return ['output' => "Found 12 files matching {$pattern}"];
  }

}

// Register the tool and use it.
$runtime = \Drupal::service('claude_agent_sdk.runtime');
$runtime->toolExecutor()->register(new FileSearchTool());

$session = $runtime->startSession();
$result  = $runtime->executeTool($session, 'file_search', ['pattern' => '*.module']);

echo $result->output(); // "Found 12 files matching *.module"
```

### Listing Available Tools

```php
$runtime = \Drupal::service('claude_agent_sdk.runtime');

// Get all registered tool names.
$names = $runtime->toolExecutor()->listTools();

// Get name => description map (useful for presenting capabilities to an LLM).
$descriptions = $runtime->toolExecutor()->describeTools();
```

## Swapping the Mock for the Real SDK

The module is designed so that swapping in real Claude Agent SDK calls requires minimal changes:

1. **Runtime facade** -- Replace the body of `ClaudeAgentRuntime::run()` with a call to the real SDK client. The method signature (`session + input -> result`) stays the same.
2. **Tool execution** -- `ToolInterface` implementations can wrap real SDK tool calls. The `ToolExecutor` registry does not need to change.
3. **Service override** -- Alternatively, create a new service class implementing the same public API and swap it in `claude_agent_sdk.services.yml`.

The session, result, and tool abstractions remain stable regardless of the backend.

## Development

```bash
# Install dependencies.
composer install

# Run coding standards checks.
composer phpcs

# Run the test suite.
composer phpunit
```

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
