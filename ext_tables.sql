CREATE TABLE tx_changelogmcp_changelog (
    KEY major_version (major_version),
    KEY change_type (change_type(30)),
    KEY version_type (major_version, change_type(30)),
    KEY version_string (version_string)
);
