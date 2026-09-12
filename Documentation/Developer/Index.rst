..  include:: /Includes.rst.txt

..  _developer:

===============
Developer guide
===============

This chapter outlines the technical architecture, data processing pipeline, and
internal design of the `changelog_mcp` extension.

..  _developer-architecture:

Architecture overview
=====================

The extension consists of several decoupled subsystems:

1.  **Ingestion & parsing:** Recursively crawls TYPO3 Core changelogs, builds an
    Abstract Syntax Tree (AST), transforms the AST to Markdown via Twig
    templates, and stores indexed records in the database.
2.  **Transport layers:**
    *   **STDIO transport:** Command-line runner (`changelog:mcp:server`)
        reading from standard input and output.
    *   **HTTP transport:** Reaction handler
        (`ChangelogReactionInstructionHandler`) receiving authenticated JSON-RPC
        requests from `typo3/cms-reactions`.
3.  **MCP protocol server:** Dispatches incoming JSON-RPC calls, lists available
    tools, and invokes handlers.
4.  **Session store:** `FileSessionStore` persists client state and protocol
    capabilities in :file:`var/changelog_mcp_sessions/`.
5.  **Repository layer:** `ChangelogDatabaseRepository` performs weighted,
    multi-criteria search and retrieval against `tx_changelogmcp_changelog`.

..  _developer-parser-and-ast:

Parser and Markdown transformation
==================================

The `ChangelogParser` utilizes phpDocumentor Guides to parse raw reStructuredText
files into structured AST document trees.

Custom Twig templates in `Resources/Private/Templates/Guides/` format each AST
node into clean Markdown:

*   **Admonitions:** Directives such as `.. note::`, `.. tip::`,
    `.. important::`, and `.. warning::` are transformed into GitHub-style
    markdown callouts (`> [!NOTE]`, `> [!TIP]`, `> [!IMPORTANT]`,
    `> [!WARNING]`).
*   **Code blocks:** `.. code-block:: php` and other languages are transformed
    into fenced code blocks with appropriate language tags.
*   **Links and references:** Cross-references and external links are converted
    to Markdown hyperlinks.

..  _developer-database-repository:

Database schema and scoring algorithm
=====================================

Changelog entries are stored in the `tx_changelogmcp_changelog` table with the
following fields:

*   `uid` (integer): Auto-incrementing unique identifier.
*   `title` (string): Title of the changelog entry.
*   `change_type` (string): Classification (`breaking`, `deprecation`,
    `feature`, `important`).
*   `version` (string): Target TYPO3 version (e.g. `14.0`, `13.4`).
*   `issue` (string): Associated Forge issue number.
*   `summary` (string): Concise description of the change.
*   `content` (text): Full rendered Markdown documentation.

Relevance scoring
-----------------

When an AI agent searches via `search_changelogs`, results are ranked using a
weighted scoring algorithm:

*   **Intent category match:** 50 points if the query matches the change type.
*   **Title match:** 20 points per match in the changelog headline.
*   **Content match:** 5 points per match in the rendered Markdown body.

..  _developer-session-management:

Session management and persistence
==================================

HTTP MCP connections require state preservation between subsequent JSON-RPC
requests. The `FileSessionStore` manages this persistence:

*   Generates cryptographically secure session IDs upon initialization.
*   Stores session JSON files in :file:`var/changelog_mcp_sessions/{id}.json`.
*   Stores protocol version, client capabilities, and initialized timestamps.

..  _developer-reactions-handler:

Reaction instruction handler
============================

The `ChangelogReactionInstructionHandler` implements
`TYPO3\CMS\Reactions\Reaction\ReactionInstructionHandlerInterface`.

When an HTTP request reaches `/typo3/reaction/{reaction-uuid}`:

1.  Validates the `x-api-key` header against the secret configured in the
    Reaction record.
2.  Extracts the session ID from headers or query parameters.
3.  Directs JSON-RPC payloads to the MCP server.
4.  Returns appropriate JSON or SSE responses.
