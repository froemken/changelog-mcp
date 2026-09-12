..  include:: /Includes.rst.txt

..  _configuration:

=============
Configuration
=============

The `changelog_mcp` extension is designed to work out of the box for STDIO
transport, while HTTP transport requires a one-time setup using TYPO3 Reactions.

..  _configuration-reaction:

Configure TYPO3 Reaction for HTTP transport
===========================================

To enable remote AI clients and IDE extensions to communicate with your TYPO3
instance over HTTP, configure an incoming reaction record:

1.  Log in to the TYPO3 backend with administrative privileges.
2.  Navigate to :guilabel:`Admin Tools > Integrations > Reactions`.
3.  Click :guilabel:`Create new reaction`.
4.  Select the reaction type :guilabel:`TYPO3 Changelog MCP`.
5.  Fill in the form fields:

    *   **Name:** Enter a recognizable description, such as `Changelog MCP Server`.
    *   **Secret:** Enter a secure, random API secret key. Clients must provide
        this secret in the `x-api-key` HTTP header.

6.  Save the reaction record.
7.  Copy the generated Reaction ID (UUID) displayed in the overview list.

The reaction endpoint will be accessible at:

..  code-block:: text

    https://example.org/typo3/reaction/{reaction-uuid}

..  _configuration-session-storage:

Session storage configuration
=============================

The HTTP transport utilizes `FileSessionStore` to maintain MCP protocol sessions
across stateless HTTP requests.

Session files are stored in:

..  code-block:: text

    var/changelog_mcp_sessions/{session-id}.json

Ensure that the web server user has read and write permissions to the
:file:`var/` directory in your TYPO3 project root.

..  note::
    Session records automatically capture client protocol capabilities,
    initialized timestamps, and protocol versions. Stale sessions can be safely
    purged by deleting files from :file:`var/changelog_mcp_sessions/`.

..  _configuration-cli-options:

CLI command reference and options
=================================

The extension provides two Symfony console commands:

..  _configuration-cli-prepare:

changelog:mcp:prepare
---------------------

Indexes and prepares the TYPO3 Core changelogs into the database.

..  code-block:: bash

    vendor/bin/typo3 changelog:mcp:prepare [options]

Available options:

*   `-v`, `-vv`, `-vvv`: Increases verbosity. Displays each scanned version folder
    and changelog file name during parsing.

..  _configuration-cli-server:

changelog:mcp:server
--------------------

Starts the MCP server in STDIO mode, reading JSON-RPC requests from `php://stdin`
and returning responses to `php://stdout`.

..  code-block:: bash

    vendor/bin/typo3 changelog:mcp:server
