..  include:: /Includes.rst.txt

..  _installation:

======================
Installation and setup
======================

..  _installation-prerequisites:

Prerequisites
=============

Before installing the extension, ensure that your environment meets the
following requirements:

*   **PHP:** Version 8.2 or higher with the `ext-mbstring` extension enabled.
*   **TYPO3:** Version 14.0 or higher.
*   **System extensions:** `typo3/cms-reactions` must be installed and active.
*   **TYPO3 Core files:** The TYPO3 Core changelogs must exist in your project
    under :file:`vendor/typo3/cms-core/Documentation/Changelog/`.
*   **Filesystem permissions:** The TYPO3 environment var directory must be
    writable so the server can create session files in
    :file:`var/changelog_mcp_sessions/`.

..  _installation-composer:

Install via Composer
====================

Install the package via Composer in your TYPO3 project root:

..  code-block:: bash

    composer require stefanfroemken/changelog-mcp

..  _installation-database-migration:

Database schema migration
=========================

After adding the extension, run the TYPO3 database schema update command to
create the `tx_changelogmcp_changelog` table:

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Alternatively, execute the schema analyzer in the TYPO3 backend under
:guilabel:`Admin Tools > Maintenance > Analyze Database Structure`.

..  _installation-import-changelogs:

Import TYPO3 changelogs
=======================

The MCP server queries an indexed database table rather than parsing raw files
on every incoming request. To populate the database, run the preparation
command:

..  code-block:: bash

    vendor/bin/typo3 changelog:mcp:prepare

This command performs the following operations:

1.  Scans :file:`vendor/typo3/cms-core/Documentation/Changelog/` recursively for
    version folders (such as :file:`10.4/`, :file:`13.4/`, and :file:`14.0/`).
2.  Cleans and normalizes ReST directives and external references.
3.  Truncates any existing records in `tx_changelogmcp_changelog`.
4.  Renders the ReST content to structured Markdown using custom Twig
    templates.
5.  Extracts title, change type, target version, issue number, and summary.
6.  Inserts each changelog entry into the database.

..  tip::
    To observe every changelog file being processed in real time, add the
    verbose flag `-v`:

    ..  code-block:: bash

        vendor/bin/typo3 changelog:mcp:prepare -v
