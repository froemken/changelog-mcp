# TYPO3 Changelog Model Context Protocol (MCP) Server

This TYPO3 extension catalogues official TYPO3 Core changelogs, converts them to Markdown, and provides them via the **Model Context Protocol (MCP)**. This allows AI assistants (like Claude, PhpStorm, or other MCP-compatible clients) to access up-to-date information about TYPO3 Core APIs, deprecations, breaking changes, features, and important notes directly from your TYPO3 instance.

---

## Features

- **Changelog Parser & Importer**: Converts TYPO3 Core RST changelogs into Markdown format and stores them in a database for fast querying.
- **Model Context Protocol (MCP)**:
  - **STDIO Transport**: Supported via a TYPO3 console command.
  - **HTTP Transport**: Supported via the TYPO3 Reactions extension (SSE/GET for connections, POST for incoming requests).
- **Session Persistence**: Utilizes `FileSessionStore` inside TYPO3's writeable directory (`var/mcp_sessions`) to persist client sessions across stateless HTTP requests.

---

## Installation & Setup

1. **Require the Extension**:
   ```bash
   composer require stefanfroemken/changelog-mcp
   ```

2. **Run Schema Migration**:
   Update your database schema via the TYPO3 Install Tool, TYPO3 Backend, or CLI:
   ```bash
   vendor/bin/typo3 extension:setup
   ```

3. **Import TYPO3 Changelogs**:
   Process and import the RST files into the TYPO3 database:
   ```bash
   vendor/bin/typo3 changelog:mcp:prepare
   ```

---

## Usage & Integration

### 1. STDIO Transport (e.g. for IDE integrations)
Run the MCP server locally over standard input/output:
```bash
vendor/bin/typo3 changelog:mcp:server
```

### 2. HTTP Transport (via TYPO3 Reactions)
The extension implements [ChangelogMcpReaction](file:///Users/froemken/htdocs/typo3143/packages/changelog-mcp/Classes/Reaction/ChangelogMcpReaction.php) to expose the MCP server over HTTP endpoint under TYPO3 Reactions.

---

## Development & Testing

You can manually test the HTTP/JSON-RPC communication using `curl`.

### Step 1: Initialize Session
Send an `initialize` request to the reaction endpoint to start an MCP session. Make sure to replace the endpoint URL, `API_SECRET`, and reaction ID with your actual values:

```bash
curl -i -X POST "https://typo3143.ddev.site/typo3/reaction/a7279da8-56c1-4642-8248-74668bd50a82" \
      -H "x-api-key: API_SECRET" \
      -H "Content-Type: application/json" \
      -H "Accept: application/json" \
      -d '{
        "jsonrpc": "2.0",
        "method": "initialize",
        "params": {
          "protocolVersion": "2024-11-05",
          "capabilities": {},
          "clientInfo": {
            "name": "mcp-test-client",
            "version": "1.0.0"
          }
        },
        "id": 1
      }'
```

*Note: The response will contain the `Mcp-Session-Id` header (e.g., `Mcp-Session-Id: 250fd04a-9a0c-48d3-b6e2-99e3d9c8ebca`), which you must use in subsequent requests.*

### Step 2: Call MCP Tool
Query the `search_changelogs` tool with a search query using the session ID retrieved from the initialization step:

```bash
curl -X POST "https://typo3143.ddev.site/typo3/reaction/a7279da8-56c1-4642-8248-74668bd50a82" \
      -H "x-api-key: API_SECRET" \
      -H "Mcp-Session-Id: YOUR_SESSION_ID" \
      -H "Content-Type: application/json" \
      -H "Accept: application/json" \
      -d '{
        "jsonrpc": "2.0",
        "method": "tools/call",
        "params": {
          "name": "search_changelogs",
          "arguments": {
            "query": "encryption"
          }
        },
        "id": 2
      }'
```
