..  include:: /Includes.rst.txt

..  _usage:

=====================
Usage and integration
=====================

The extension supports both standard input/output (STDIO) transport and HTTP
transport via the TYPO3 Reactions system.

..  _usage-stdio-transport:

STDIO transport
===============

The STDIO transport runs the MCP server locally over standard input and output
streams. This transport is optimal for desktop clients and IDE extensions
running on the same host or inside a container where the CLI binary is
accessible.

Run the server from the command line:

..  code-block:: bash

    vendor/bin/typo3 changelog:mcp:server

To configure an MCP client (such as Claude Desktop or PhpStorm) to use the
STDIO transport, add the server to your client configuration file:

..  code-block:: json

    {
        "mcpServers": {
            "typo3-changelog-stdio": {
                "command": "/path/to/typo3/vendor/bin/typo3",
                "args": ["changelog:mcp:server"]
            }
        }
    }

..  _usage-http-transport:

HTTP transport via Reactions
============================

The HTTP transport integrates directly with the TYPO3 Reactions extension
(`typo3/cms-reactions`), enabling remote AI agents to connect via authenticated
HTTP endpoints.

Configure the Reaction in the TYPO3 backend:

1.  Open the TYPO3 backend and navigate to :guilabel:`Admin Tools > Integrations > Reactions`.
2.  Click :guilabel:`Create new reaction`.
3.  Select the reaction type :guilabel:`TYPO3 Changelog MCP`.
4.  Enter a descriptive title and define a secret API key.
5.  Save the reaction record and note the generated Reaction ID (UUID).

The HTTP endpoint URL follows this structure:

..  code-block:: text

    https://example.org/typo3/reaction/{reaction-uuid}

..  _usage-session-management:

Session management
------------------

The HTTP transport uses `FileSessionStore` to persist client session state in
:file:`var/changelog_mcp_sessions/`.

*   **GET requests:** Establish a Server-Sent Events (SSE) stream returning an
    initial endpoint event that contains the assigned session ID.
*   **POST requests:** Process incoming JSON-RPC payloads. The client provides
    the session identifier either as a query parameter (`?sessionId=...`) or via
    the `Mcp-Session-Id` HTTP header.

..  _usage-testing-curl:

Testing with curl
=================

You can verify the HTTP transport and inspect JSON-RPC messages using `curl`.

..  _usage-testing-initialize:

Initialize session
------------------

Send an `initialize` JSON-RPC request to the Reaction endpoint:

..  code-block:: bash

    curl -i -X POST "https://example.org/typo3/reaction/<reaction-uuid>" \
         -H "x-api-key: <your-secret-key>" \
         -H "Content-Type: application/json" \
         -H "Accept: application/json" \
         -d '{
           "jsonrpc": "2.0",
           "method": "initialize",
           "params": {
             "protocolVersion": "2024-11-05",
             "capabilities": {},
             "clientInfo": {
               "name": "manual-curl-client",
               "version": "1.0.0"
             }
           },
           "id": 1
         }'

Inspect the HTTP response headers and copy the returned `Mcp-Session-Id` header
value.

..  _usage-testing-search:

Search for changelogs
---------------------

Query the `search_changelogs` tool with your session ID:

..  code-block:: bash

    curl -X POST "https://example.org/typo3/reaction/<reaction-uuid>" \
         -H "x-api-key: <your-secret-key>" \
         -H "Mcp-Session-Id: <your-session-id>" \
         -H "Content-Type: application/json" \
         -H "Accept: application/json" \
         -d '{
           "jsonrpc": "2.0",
           "method": "tools/call",
           "params": {
             "name": "search_changelogs",
             "arguments": {
               "query": "ContentObjectRenderer",
               "version": "14"
             }
           },
           "id": 2
         }'

..  _usage-testing-show:

Show full changelog content
---------------------------

Retrieve the full changelog and migration instructions using its UID:

..  code-block:: bash

    curl -X POST "https://example.org/typo3/reaction/<reaction-uuid>" \
         -H "x-api-key: <your-secret-key>" \
         -H "Mcp-Session-Id: <your-session-id>" \
         -H "Content-Type: application/json" \
         -H "Accept: application/json" \
         -d '{
           "jsonrpc": "2.0",
           "method": "tools/call",
           "params": {
             "name": "show_changelog",
             "arguments": {
               "uid": 197
             }
           },
           "id": 3
         }'

..  _usage-mcp-client-configuration:

MCP client configuration
========================

To connect an external MCP client (such as Claude Desktop, Windsurf, Cursor,
or custom agents) to your TYPO3 instance over HTTP, add the server definition
to your `mcp_config.json`:

..  code-block:: json

    {
        "mcpServers": {
            "typo3-changelog-http": {
                "serverUrl": "https://example.org/typo3/reaction/<reaction-uuid>",
                "headers": {
                    "x-api-key": "<your-secret-key>",
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                }
            }
        }
    }

..  _usage-tools-reference:

MCP tools reference
===================

The MCP server exposes two tools for interacting with TYPO3 changelogs.

..  _usage-tool-search-changelogs:

search_changelogs
-----------------

Searches through all ingested TYPO3 Core changelog entries using fulltext
matching and metadata scoring.

Arguments:

*   `query` (string, optional): Free-text search terms (for example, class names,
    method names, or features). Default is an empty string.
*   `version` (string, optional): Target TYPO3 version (for example, `"10"`,
    `"11.5"`, `"12.4"`, `"13"`, or `"14"`). Limits results to changelogs up to
    this version.
*   `type` (string, optional): Filter by change category. Permitted values:
    `"breaking"`, `"deprecation"`, `"feature"`, `"important"`.

Return value:

A list of matching entries including UID, change type, title, target version,
summary, and embedded resource links (`typo3://changelog/{uid}`).

..  _usage-tool-show-changelog:

show_changelog
--------------

Retrieves the complete Markdown content of a single changelog entry.

Arguments:

*   `uid` (integer, required): The unique record ID of the changelog entry
    obtained from `search_changelogs`.

Return value:

The full Markdown text containing problem descriptions, impact assessments,
migration steps, and code examples.
