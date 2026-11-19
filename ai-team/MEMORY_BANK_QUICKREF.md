# Memory Bank - Quick Reference

## Commands

```
# Initialize (run once per project)
node ai-team/scripts/init-memory-bank.cjs

# Get project context (loads ~2KB instead of reading 50+ files)
@team memory context

# Show recent conversation history
@team memory history

# Query knowledge graph for entity
@team memory graph "AuthMiddleware"

# Get related entities
@team memory related "JWTTokens"

# Add architectural decision
@team memory decision "003" "New caching strategy"

# Update project memory
@team memory update "keyFiles.services" "['src/services/', 'src/lib/']"

# Search conversations
@team memory search "jwt secret"

# Export/Backup
@team memory export
```

---

## File Structure (Per Project)

```
project-root/
├── .ai-memory/
│   ├── project-memory.json        # Auto-detected + manual
│   ├── knowledge-graph.json       # Entities + relationships
│   ├── conversation-history/      # Session JSON files
│   │   ├── session-1700000000000.json
│   │   └── ...
│   └── decisions/                 # ADR markdown files
│       ├── 001-auth-design.md
│       └── 002-multi-tenant.md
```

---

## Auto-Detected Fields

| Field | Source |
|-------|--------|
| Stack (language, framework, ORM, DB) | package.json, composer.json, prisma/schema.prisma |
| Key Files | src/ directory structure, Controllers/ |
| Conventions | Based on framework (Laravel vs Express) |
| Security | Framework-specific defaults |
| Common Tasks | Framework-specific patterns |

---

## Token Savings

| Before | After | Savings |
|--------|-------|---------|
| Read 20+ files: ~15,000 tokens | Load context: ~2,000 tokens | **87%** |
| Re-explain architecture | Already in context | 100% |
| Repeat conventions | Already in context | 100% |

---

## Integration with Team Brain

```bash
# In any new session:
@team memory context
# Returns: Full project context in ~2KB

# Then work normally:
@team "Buat fitur login"
# Team Brain uses context from memory bank
```

---

## Maintenance

```bash
# Update after major changes
@team memory update "architecture.pattern" "Microservices"

# Add decision after architectural choice
@team memory decision "004" "Event-driven async processing"

# Add entity to knowledge graph
@team memory graph add "RedisCache" "type: Service, description: Caching layer"

# Prune old history (keep last 50 sessions)
@team memory prune 50
```

---

## Status: ✅ Ready for All 3 Projects

| Project | Memory Bank | Auto-Detected | Decisions |
|---------|-------------|---------------|-----------|
| zona-erp-app | ✅ | ✅ Express + Prisma | 2 ADRs |
| ai_erp_lvl-app | ✅ | ✅ Laravel + Inertia | 2 ADRs |
| skm-ekp-app | ✅ | ✅ Laravel + Rules | 2 ADRs |

Run `node ai-team/scripts/init-memory-bank.cjs` anytime to re-detect.