# Token Optimization Strategy - Zona ERP

## Goal
Reduce AI token usage by 40-60% through intelligent model selection based on task complexity.

## Model Tier Assignment

### 🟢 CHEAP (Flash/Fast Models) - 60% of tasks
Use for: simple queries, code review, database schema check, test generation, formatting

| Model | Cost | Best For |
|-------|------|----------|
| `deepseek-v4.1-flash` | $0.0003/M | Prisma queries, schema analysis |
| `qwen3.7-flash` | $0.001/M | Test generation, simple fixes |
| `claude-haiku-4-5` | $0.001/M | Quick code review, lint checks |
| `mistral-small-4` | $0.001/M | Simple refactoring |

### 🟡 MEDIUM (Pro Models) - 30% of tasks
Use for: feature implementation, API design, component creation, bug fixes

| Model | Cost | Best For |
|-------|------|----------|
| `qwen3.8-max` | $0.002/M | Node/Express backend, API routes |
| `qwen3-coder-plus` | $0.002/M | React/TypeScript frontend |
| `claude-sonnet-4-5` | $0.003/M | Security audit, auth design |
| `mistral-medium-3.5` | $0.003/M | Complex refactoring |

### 🔴 PREMIUM (Ultra/Opus) - 10% of tasks
Use for: architecture design, complex debugging, security deep-dive, multi-file coordination

| Model | Cost | Best For |
|-------|------|----------|
| `claude-opus-4-5` | $0.015/M | Architecture decisions, complex bugs |
| `qwen3.5-397b` | $0.004/M | Large context analysis |
| `nemotron-3-ultra-550b` | $0.008/M | Deep reasoning |
| `grok-4.7` | $0.006/M | Alternative complex tasks |

## Automatic Model Selection Rules

### Rule 1: Task Type Detection
```
Simple keywords → CHEAP model:
  - "review", "check", "format", "lint", "test", "schema", "query"
  - "fix typo", "rename", "simple", "quick"

Medium keywords → MEDIUM model:
  - "create", "implement", "build", "add feature", "fix bug"
  - "endpoint", "component", "route", "migration"

Complex keywords → PREMIUM model:
  - "architect", "design", "refactor", "optimize", "investigate"
  - "security", "multi-tenant", "complex", "debug"
```

### Rule 2: File Count
- 1-2 files → CHEAP or MEDIUM
- 3-5 files → MEDIUM
- 6+ files → PREMIUM

### Rule 3: Response Size
- < 500 tokens → CHEAP model OK
- 500-2000 tokens → MEDIUM model
- > 2000 tokens → PREMIUM model

## Cost Comparison (Per 1000 Requests)

| Scenario | All Premium | Optimized | Savings |
|----------|-------------|-----------|---------|
| Simple tasks (60%) | $900 | $180 | 80% |
| Medium tasks (30%) | $450 | $270 | 40% |
| Complex tasks (10%) | $150 | $150 | 0% |
| **TOTAL** | **$1500** | **$600** | **60%** |

## Quick Commands

### Force Cheap Model
```
@backend cheap "Buat function validate email"
@database cheap "Cek schema tabel users"
@review cheap "Review code di file auth.ts"
```

### Force Medium Model (default)
```
@backend "Buat API endpoint login"
@frontend "Buat React component Dashboard"
```

### Force Premium Model
```
@architect premium "Redesign arsitektur multi-tenant"
@security premium "Deep audit auth middleware"
```

## Monitoring

Track token usage by checking:
- Response time (faster = cheaper model)
- Output quality (premium models better for complex tasks)
- Monthly cost (aim for 40-60% reduction)

## Fallback Strategy
If cheap model fails or produces low-quality output:
1. Retry with medium model
2. If still fails, escalate to premium
3. Log the escalation for future optimization

---

## 🆓 Free Model Quota Management (freellmapi)

### Tier Assignment for Free Models

| Tier | Models | Daily Quota Est. | Use Case |
|------|--------|------------------|----------|
| 🟢 **DAILY_DRIVER** (60%) | `deepseek-v4.1-flash`, `qwen3.7-flash`, `mistral-small-4`, `glm-5.3-flash` | ~600k tokens | Simple tasks, review, test, schema |
| 🟡 **QUALITY_BOOST** (30%) | `qwen3.8-max`, `qwen3-coder-plus`, `claude-sonnet-4-5`, `gemini-4.0-flash` | ~300k tokens | Implementation, bug fix, API design |
| 🔴 **RESERVE** (10%) | `claude-opus-4-5`, `fusion`, `grok-4.7`, `qwen3.5-397b` | ~100k tokens | **Emergency only** - complex arch, security deep-dive |

### Quota Monitoring Rules

```markdown
# Daily Quota Budget (Est. 1M tokens/day free)

DAILY_DRIVER      → 600k tokens (60%) → deepseek-flash, qwen-flash
QUALITY_BOOST     → 300k tokens (30%) → qwen3.8-max, claude-sonnet
RESERVE           → 100k tokens (10%) → claude-opus, fusion

🚨 QUOTA ALERTS:
- 70% used → Warning: "Masuk mode hemat, kurangi RESERVE usage"
- 85% used → Auto-disable RESERVE tier, redirect ke QUALITY_BOOST
- 95% used → Emergency mode: HANYA DAILY_DRIVER, reject complex tasks
- 100% used → STOP, wait quota reset (biasanya 00:00 UTC)
```

### Rate Limit Avoidance (Free API Limits)

```markdown
# Typical Free Tier Limits: 60 req/min, 1000 req/hour

✅ DO:
- Batch simple requests (3-5 task → 1 call)
- Cache responses 1 hour (review, test, schema results)
- Spread load: max 10 req/menit sustained
- Use streaming for long responses

❌ DON'T:
- Burst 20+ requests sekaligus
- Retry immediately on 429 (wait 30-60s)
- Call fusion/panel untuk task simple
- Loop tanpa delay
```

### Auto-Quota Management Commands

```
# Check quota status
@team quota status

# Force daily driver (hemat quota)
@backend daily "Buat Pest test user"
@review daily "Quick review auth.ts"

# Force quality boost (standar)
@backend "Buat Laravel controller Company"

# Force reserve (EMERGENCY ONLY)
@architect reserve "Redesign arsitektur multi-tenant"
@security reserve "Deep audit Fortify + spatie"

# Emergency mode (quota critical)
@team emergency-mode on
```

### Quota Tracking (Manual/Simple)

Track di file log sederhana:
```bash
# ~/.ai-quota-log.txt
2026-11-19 10:00  DAILY_DRIVER  deepseek-flash    2.3k tokens
2026-11-19 10:05  QUALITY_BOOST claude-sonnet     8.7k tokens
2026-11-19 10:15  DAILY_DRIVER  qwen-flash        1.1k tokens
TODAY: 12.1k / 1,000,000 (1.2%)
```

### Fallback untuk Free Model Errors

| Error | Action |
|-------|--------|
| `429 Rate Limited` | Wait 60s → retry with DAILY_DRIVER |
| `Quota Exceeded` | Switch to next tier down, alert user |
| `Model Unavailable` | Auto-fallback: opus→sonnet→haiku→qwen→deepseek |
| `Timeout` | Retry once with same model, then downgrade |

### Quality vs Quota Trade-off

```
Task Type          | Preferred (Quota OK) | Fallback (Quota Low)
-------------------|---------------------|---------------------
Code Review        | claude-sonnet-4-5   | claude-haiku-4-5 → qwen-flash
Test Generation    | qwen3-coder-plus    | deepseek-v4.1-flash
Architecture       | claude-opus-4-5     | claude-sonnet-4-5 (simplified)
Security Audit     | claude-opus-4-5     | claude-sonnet-4-5 + manual check
Simple Fix         | deepseek-flash      | qwen3.7-flash
Schema Analysis    | deepseek-flash      | glm-5.3-flash
```