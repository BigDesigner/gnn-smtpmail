# System Coherence Guidelines

This document outlines the protocols, standards, and rules that govern the AI development lifecycle and ensure project-wide consistency and integrity.

## 1. Operating Rules

### Session Start Protocol
1. Search for `.memory-bank/active-session.json`. If present, read it to restore context.
2. Read `.tasks/pipeline.md` to identify active tasks.
3. Read `.tasks/handoff.md` to review the handoff notes from the previous session.
4. Generate a new `session_id` and update `last_active` and `timestamp`.

### Mode Detection & Overrides
- Detect presence of the `CI=true` environment variable.
- In **Interactive Mode** (default):
  - Strict worktree cleanliness checks before modifying code.
  - Require user feedback and explicit approval gates for plans and commits.
  - Present ADR decisions to the user first.
- In **CI Mode**:
  - Skip interactive gates.
  - Do not commit or stage files. Generate `ci-run-summary.md` instead.
  - Write errors and environment warnings directly to `bugs/bug-list.md`.

### Worktree cleanliness & Git State
- Check git status and branch name at session start.
- If the worktree is dirty in Interactive mode, notify the user and ask for instructions (stash/commit/abort).
- Ensure atomic, logical commits. Never group unrelated changes.

---

## 2. Development Quality Checklists

### Pre-Change Checklist
- [ ] Confirm task requirement scope.
- [ ] Ensure all relevant codebase files have been read and verified.
- [ ] Review `.specs/boundary-conditions.md` to prevent constraint violations.
- [ ] Review `.specs/security-standards.md` to enforce ecosystem-specific mitigations.

### Post-Change Checklist
- [ ] Perform static syntax checking (e.g. `php -l`).
- [ ] Verify security: nonces checked, inputs sanitized, outputs escaped, table names prepared.
- [ ] Ensure no docstrings or comments have been altered or deleted unnecessarily.
- [ ] Run test scripts or suggest validation plans.

---

## 3. Concurrency & Handoffs

### Locking Expectations
- To prevent context drift or overwriting from concurrent subagents, ensure the `concurrency_lock` status in `active-session.json` is reviewed before starting file edits.
- Only edit files relevant to the active task.

### Handoff Protocol
- At the end of a session, document all changes, file paths, test results, and next actions inside `.tasks/handoff.md`.
