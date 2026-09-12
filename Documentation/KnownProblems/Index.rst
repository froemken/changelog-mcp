..  include:: /Includes.rst.txt

..  _known-problems:

======================
Known problems and FAQ
======================

This chapter outlines common limitations, troubleshooting steps, frequently
asked questions, and support resources.

..  _known-problems-limitations:

Known limitations
=================

*   **Local changelog source:** The `changelog:mcp:prepare` command requires
    the official TYPO3 Core changelog files located in
    :file:`vendor/typo3/cms-core/Documentation/Changelog/`. In production
    environments deployed without development files, ensure this directory is
    present before running the preparation indexer.
*   **Filesystem permissions:** The web server process must have write access
    to :file:`var/changelog_mcp_sessions/` to create and maintain HTTP client
    sessions.
*   **System extensions:** HTTP transport requires `typo3/cms-reactions` to be
    installed and enabled.

..  _faq:

Frequently asked questions
==========================

..  accordion::
    :name: general-faq

    ..  accordion-item:: Why does search_changelogs return zero results?
        :name: faq-zero-results
        :show:
        :header-level: 3

        The MCP server does not read raw files dynamically per query; it
        queries the indexed `tx_changelogmcp_changelog` table. Run the CLI
        preparation command to parse and index the changelog files:

        ..  code-block:: bash

            vendor/bin/typo3 changelog:mcp:prepare

    ..  accordion-item:: Can I run the server with Claude Desktop on my local machine?
        :name: faq-claude-desktop
        :header-level: 3

        Yes. You can use the STDIO transport directly by pointing your
        `claude_desktop_config.json` to `vendor/bin/typo3 changelog:mcp:server`.
        See :ref:`usage-stdio-transport` for configuration examples.

    ..  accordion-item:: How is the HTTP endpoint secured?
        :name: faq-http-security
        :header-level: 3

        Every reaction created in the TYPO3 backend can define a secret token.
        Clients must include this token in the `x-api-key` header on all
        requests. Requests without a valid key are rejected with an HTTP 401
        Unauthorized response.

..  _help:

Where to get help
=================

You can get help through community channels:

*   **TYPO3 Slack:** Join the `#typo3-cms` channel on
    `TYPO3 Slack <https://typo3.org/community/meet/chat-slack>`_.
*   **Official Forum:** Ask questions on
    `TYPO3 Talk <https://talk.typo3.org/c/typo3-questions/19>`_.

..  _report-issues:

Report issues
=============

If you find a bug or have a feature request, please open an issue in the
`GitHub issue tracker <https://github.com/stefanfroemken/changelog-mcp/issues>`_.
When submitting a bug report, include your TYPO3 version, PHP version, and any
relevant log messages from :file:`var/log/typo3_*.log`.
