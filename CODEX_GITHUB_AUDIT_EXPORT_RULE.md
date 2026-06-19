SONYRA Site Manager — GitHub Audit Export Rule

GitHub repository Argolio/sonyra-site-manager is used as a readable source export for ChatGPT audit.

Codex must not publish plugin folders directly into the repository root.

Correct structure:

repo root/
  sonyra-site-manager-<VERSION>-github/
    sonyra-site-manager.php
    assets/
    includes/
    docs/
    templates/
    dist/

Wrong structure:

repo root/
  assets/
  includes/
  docs/
  dist/
  templates/
  sonyra-site-manager.php

Release export folder name must use the canonical plugin slug:

sonyra-site-manager-<VERSION>-github

Examples:

sonyra-site-manager-0.1.97-github
sonyra-site-manager-0.1.98-github

Before publishing a new audit export, Codex must remove old audit exports and root-level plugin folders:

audit-*
sonyra-site-manager-*-github/
assets/
includes/
docs/
dist/
templates/
languages/
vendor/
sonyra-site-manager.php
*.zip

Codex must never delete or modify the main development working folder during GitHub cleanup.

Main development folder:

/Users/stanislavkataev/projects/SONYRA Site Platform/SONYRA Site Manager

GitHub clone folder:

/Users/stanislavkataev/projects/SONYRA Site Platform/github-sonyra-site-manager

Correct workflow:

1. Work in the main development folder.
2. Run checks in the main development folder.
3. Build ZIP in dist/.
4. Sync the finished release into GitHub clone under sonyra-site-manager-<VERSION>-github/.
5. Commit and push GitHub.
6. User installs ZIP manually in WordPress only after GitHub source sync.

END OF FILE.
