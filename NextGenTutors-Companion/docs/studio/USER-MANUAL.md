# User Manual — Automation Studio

## What is Automation Studio?

Automation Studio lets administrators build tutoring workflows visually — without editing code or redeploying plugins.

## What you can do

- Build workflows with drag-and-drop nodes (Start, Event, Email, CRM, Role, etc.)
- Save changes that apply **immediately**
- Publish workflows to activate triggers
- Simulate workflows before going live
- Monitor execution history
- Start from 24 prebuilt templates

## Typical workflow: Tutor Approval

1. Open template **Tutor Approval** or build from scratch.
2. Ensure the trigger node is `TUTOR_APPROVED`.
3. Add steps: Condition → Role → CRM → LMS → Email → End.
4. Click **Save & Apply**, then **Publish**.
5. When an admin approves a tutor, the workflow runs automatically.

## Access

wp-admin → **Automation Studio** (left menu, networking icon)
