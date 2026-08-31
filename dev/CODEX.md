# Codex workflow approvals

Follow [AGENTS.md](../AGENTS.md) for task scope and fixture safety. This file covers Codex-specific execution approvals, not additional task authorisation.

Use stable invocations of the reviewed scripts with explicit environment arguments and a literal container name. These run the same scripts as Make and already initialise Bash and `phpenv`:

```bash
docker exec -e PHPUNIT_ARGS= uphelper-m24 /src/dev/Docker/run-phpunit-functional-tests.sh
docker exec -e SYNC_PATHS=dev/TestVendorModule/src/module/Model/Example.php -e DELETE_PATHS= uphelper-m24 /src/dev/Docker/sync-test-fixtures.sh
```

Examples do not grant approval. Persist approvals only for reviewed commands, named fixtures and relevant argument values. A different test filter, sync target or command may need another approval; identical invocations should reuse matching saved rules. Do not grant generic Make, Docker or Bash prefixes, add an arbitrary-command launcher, enable Full Access or grant Docker-socket access for this workflow. Keep ad hoc container commands, baseline seeding and lifecycle operations separately approved.

Keep rules user-local and request the required approval before editing them. Add concise rationale beside rules linking the [official rules documentation](https://learn.chatgpt.com/docs/agent-configuration/rules). An `allow` rule permits execution outside the sandbox: approved scripts and tests remain trusted code, and these containers mount the repository read-write. Review executable changes before reusing an approval; rules do not pin script contents.

Validate all loaded rule files together with `codex execpolicy check --rules <file> ... -- <command arguments>`. Check intended workflows and negative cases: arbitrary shells, unrelated containers, changed sync/delete arguments, seeding and lifecycle commands. Prefix rules may match trailing arguments; do not treat them as exact-length argument validation. Remove superseded broad rules, preserve unrelated approvals, and keep backups outside the `.rules` extension. Rules are loaded at startup, so restart Codex after changing saved rules rather than assuming the active session has reloaded them.
