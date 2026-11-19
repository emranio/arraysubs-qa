# Translation download deployment verification

Scope confirmed by the user on 2026-11-17: locate the Modules page translation download, verify the deployed download works, and determine whether its contents are current or stale.

## Targets and access

- Admin: https://mirror-help.arrayhash.com/wp-admin
- Credentials: current live QA credentials supplied in this chat and the workspace AGENTS.md; do not copy credentials into evidence.
- Download: resolve from the Multilingual Modules card and core TranslationCatalogs/Documentation constants.
- Source: arraysubs/languages, arraysubspro/languages, and user-guide/public/downloads/arraysubs-translations.zip.

## Tasks

- TD-01: Trace the source URL and packaging/deployment behavior.
- TD-02: Open the actual staging Modules page with agent-browser, inspect and activate the download link, capture browser evidence, and validate the downloaded archive.
- TD-03: Compare every packaged language file against current core/pro sources and the repository pack; inspect metadata and coverage, recording any differences.
- TD-04: Record results, preserve existing issues, and log confirmed defects in qa/issues.

This is focused download QA. It does not change plugin settings, install translations, rebuild catalogs, or deploy files.

Existing open deployment-alignment issue #151 is preserved; staging/source differences will be called out if observed.
