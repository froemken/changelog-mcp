..  include:: /Includes.rst.txt

..  _changelog:

=========
ChangeLog
=========

This document lists the changes between releases of the `changelog_mcp` extension.

..  _changelog-0-0-3:

0.0.3
=====

*   **[BUGFIX] Preserve admonitions and cross-references during Markdown transformation:**
    Enhanced AST Twig templates to map `note`, `tip`, `warning`, and `important`
    directives to GitHub-flavored Markdown callouts.
*   **[DOCS] Scaffold complete extension documentation:**
    Adopted official TYPO3 documentation structure with comprehensive guides for
    installation, configuration, usage, development, and troubleshooting.

..  _changelog-0-0-2:

0.0.2
=====

*   **[FEATURE] HTTP transport via TYPO3 Reactions:**
    Added `ChangelogReactionInstructionHandler` supporting Server-Sent Events (SSE)
    and JSON-RPC over HTTP.
*   **[FEATURE] Session persistence:**
    Implemented `FileSessionStore` inside :file:`var/changelog_mcp_sessions/`.

..  _changelog-0-0-1:

0.0.1
=====

*   **[FEATURE] Initial release:**
    Core changelog indexing via `changelog:mcp:prepare`, STDIO transport via
    `changelog:mcp:server`, and MCP tools `search_changelogs` and `show_changelog`.
