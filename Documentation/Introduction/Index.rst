..  include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

..  _introduction-what-is-it:

What is the TYPO3 changelog MCP server?
=======================================

The `changelog_mcp` extension catalogues official TYPO3 Core changelogs,
converts them from reStructuredText to Markdown, and provides them via the
Model Context Protocol (MCP).

By implementing the Model Context Protocol, the extension acts as a specialized
context provider for artificial intelligence tools, IDE extensions, and chat
interfaces such as Claude Desktop, PhpStorm, or custom MCP clients. It enables
these clients to query up-to-date deprecations, breaking changes, features,
and migration instructions directly from your TYPO3 instance.

..  _introduction-use-cases:

Target audience and use cases
=============================

Large cloud-based language models often answer questions about TYPO3 based on
outdated training sets or general web crawl data. This extension is designed
specifically for:

*   **Local language models:** Small models running locally through Ollama,
    Llama.cpp, or LM Studio that lack comprehensive knowledge of recent TYPO3
    versions (such as TYPO3 v13 and TYPO3 v14).
*   **Air-gapped and isolated networks:** Secure environments where developer
    workstations and servers cannot connect to external documentation hosts.
*   **Zero-hallucination guarantees:** Preventing model hallucinations by
    forcing language models to retrieve verified, official changelog entries
    and code examples from the local database before responding.

..  note::
    AI agents and assistants should always execute the `search_changelogs` tool
    first to locate matching entries, followed by `show_changelog` with the
    target UID to inspect full migration instructions and code snippets.

..  _introduction-key-features:

Key features
============

*   **Automated parser and importer:** Parses raw `.rst` changelogs from
    `vendor/typo3/cms-core/Documentation/Changelog/`, converts AST nodes to
    Markdown using custom Twig templates, and indexes them in the database.
*   **STDIO transport:** Supports standard input and output streams for local
    command-line execution and direct IDE integration.
*   **HTTP Reactions transport:** Exposes a streamable HTTP JSON-RPC endpoint
    using the native TYPO3 Reactions API (`typo3/cms-reactions`), complete
    with Server-Sent Events (SSE) support.
*   **Weighted relevance search:** Calculates relevance scores based on title
    matches (20 points), content matches (5 points), and intent category
    matches (50 points).
*   **File-based session persistence:** Maintains MCP client sessions across
    stateless HTTP requests using `FileSessionStore` inside
    :file:`var/changelog_mcp_sessions/`.

..  _introduction-available-tools:

Available MCP tools
===================

The extension registers two primary MCP tools:

1.  `search_changelogs`: Searches the database for relevant changelogs using
    fulltext queries, version filters, and change type filters. Returns a
    ranked list of results containing summaries and `typo3://changelog/{uid}`
    URIs.
2.  `show_changelog`: Retrieves the complete Markdown content of a specific
    changelog entry by its unique identifier (`uid`), including code diffs and
    step-by-step migration advice.
