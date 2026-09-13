# Deployment architecture

Two Docker stories:

1. **Developer bind-mount** (`docker/docker-compose.yml` + `start.ps1`) — source mounted; Windows junctions not followed in Linux, so extra binds exist for `assets/`, `inc/`, `prototypes/`. Port **8890**.
2. **Clean-install** (`docker/docker-compose.clean-install.yml` + `clean-install.ps1`) — vanilla WordPress, ZIPs from PRODUCTION only. This is the acceptance path.

Production hosts use WP upload, not Docker mounts.