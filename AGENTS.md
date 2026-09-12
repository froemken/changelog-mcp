# Agent Guidelines for changelog_mcp

This document specifies architectural details, operational workflows, and technical constraints for AI agents working in this repository.

## Extension Overview

The `changelog_mcp` extension (`stefanfroemken/changelog-mcp`) transforms official TYPO3 Core changelog documents into an offline database and exposes them via the Model Context Protocol (MCP).
This architecture enables local language models and IDE clients to query exact TYPO3 Core deprecations, breaking changes, features, and migration instructions without internet connectivity.

## System Architecture and Execution Flow

The extension operates across three functional layers:

### 1. Changelog Parsing and Database Ingestion
- Triggered via CLI: `vendor/bin/typo3 changelog:mcp:prepare` (`PrepareChangelogCommand`).
- Source files: Reads original ReST changelogs located in `EXT:core/Documentation/Changelog/`.
- Sanitization: `ChangelogService::cleanUpChangelogContent()` normalizes spacing for Doctrine directives, standardizes casing, and removes nested include directives.
- Parser configuration: `ParserFactory` initializes `Doctrine\RST\Parser` with custom Twig templates (`Resources/Private/Templates/Default/md/`) and markdown node renderers.
- Directives and References: `MarkDownFormat` defines directive handlers and node renderers. `GenericReference` handles roles such as `:issue:` by pointing to Forge.
- Extraction: `Changelog` extracts title, change category, target version, major version number, issue number, and description.
- Storage: `ChangelogRepository` truncates and writes records into the database table `tx_changelogmcp_changelog`.

### 2. MCP Server (STDIO Transport)
- Triggered via CLI: `vendor/bin/typo3 changelog:mcp:server` (`McpServerCommand`).
- Built by `ServerBuilderFactory` using `mcp/sdk`.
- Scans `Classes/Mcp/Tool/` for MCP attribute definitions.
- Runs on `Mcp\Server\Transport\StdioTransport` using standard input and output streams.
- Intended for local IDE integration, such as PhpStorm or desktop MCP clients.

### 3. MCP Server (HTTP Streamable Transport via TYPO3 Reactions)
- Implementation: `ChangelogMcpReaction` implements `TYPO3\CMS\Reactions\Reaction\ReactionInterface`.
- Endpoint: Exposes `/typo3/reaction/{reaction-uuid}` authenticated by `x-api-key`.
- SSE Streaming: GET requests establish a server-sent events stream (`text/event-stream`) returning an endpoint URL with a session ID query parameter.
- JSON-RPC Requests: POST requests receive JSON-RPC payloads and forward them to `Mcp\Server\Transport\StreamableHttpTransport`.
- Session persistence: Stores session state in `var/changelog_mcp_sessions/{sessionId}` via `Mcp\Server\Session\FileSessionStore`.

## MCP Tools Provided

The MCP server publishes two primary tools via `FindChangelogTool`:

1. `search_changelogs`:
   - Arguments: `query` (string), `version` (string, target TYPO3 version), `type` (string, change category).
   - Implementation: Performs fulltext search in `ChangelogRepository::getChangelogs()`.
   - Scoring algorithm: Weights title matches (20 points), content matches (5 points), and intent category bonuses (50 points).
   - Result: Returns matched records with metadata, markdown summary, and internal URI references (`typo3://changelog/{uid}`).

2. `show_changelog`:
   - Arguments: `uid` (integer).
   - Implementation: `ChangelogRepository::getChangelogContentByUid()`.
   - Result: Returns complete Markdown content containing detailed migration steps and code examples.

## Files and Paths Processed

- Input changelogs: `vendor/typo3/cms-core/Documentation/Changelog/**/*.rst` (matches directories like `10.4/`, `13.4/`, `14.0/`, excluding `Howto.rst` and `Index.rst`).
- Database schema: `Configuration/TCA/tx_changelogmcp_changelog.php` defining table `tx_changelogmcp_changelog`.
- Session cache: `var/changelog_mcp_sessions/` under the TYPO3 environment var path (`Environment::getVarPath()`).
- Twig templates: `Resources/Private/Templates/Default/md/*.md.twig` for generating markdown structures from ReST AST nodes.

## Required Tools and Prerequisites

The following runtime components and tools must be present:

- PHP 8.2 or higher with `ext-mbstring`.
- TYPO3 v14 core packages: `typo3/cms-core` and `typo3/cms-reactions`.
- Composer packages: `doctrine/rst-parser` (^0.5) and `mcp/sdk` (^0.6).
- Initialized TYPO3 database containing table `tx_changelogmcp_changelog` via `vendor/bin/typo3 extension:setup`.
- Write permissions for the session directory `var/changelog_mcp_sessions/`.
- Development tools: `Build/Scripts/runTests.sh`, `phpstan/phpstan`, and `typo3/coding-standards`.
- GitHub CLI (`gh`) for inspecting and managing repository issues.

## Development and Coding Guidelines

- Follow official TYPO3 PHP Coding Guidelines (CGL, PER-CS 3.0, strict types enabled).
- Keep methods under 20 lines of pure executable code where possible.
- Avoid em-dashes and en-dashes in code comments and documentation.
- Maintain compatibility with PHP 8.2. Do not declare `ChangelogMcpReaction` as `readonly` because lazy proxy generation in `typo3/cms-reactions` requires subclass inheritance.
