# Token Optimization - Quick Reference Card

## 3-Project Cost/Quota Overview

| Project | Stack | Monthly Cost (Paid) | Daily Quota (Free) | Mode |
|---------|-------|-------------------|-------------------|------|
| **zona-erp-app** | Node/Express + React + Prisma | ~$50 | ~1M tokens | Hybrid |
| **ai_erp_lvl-app** | Laravel + Inertia + React | ~$55 | ~1M tokens | Hybrid |
| **skm-ekp-app** | Laravel + Inertia + React + Rules | ~$55 | ~1M tokens | Hybrid |
| **TOTAL** | | **~$160** | **~3M tokens** | Hybrid |

## Per-Project Model Assignment

### zona-erp-app (Node/Express)
| Role | Paid Model | Free Model | Tier |
|------|-----------|------------|------|
| @backend | `qwen3.8-max` | `qwen3.8-max` | 🟡 Quality Boost |
| @frontend | `qwen3-coder-plus` | `qwen3-coder-plus` | 🟡 Quality Boost |
| @database | `deepseek-v4.1-flash` | `deepseek-v4.1-flash` | 🟢 Daily Driver |
| @security | `claude-sonnet-4-5` | `claude-sonnet-4-5` | 🟡 Quality Boost |
| @review | `claude-haiku-4-5` | `claude-haiku-4-5` | 🟢 Daily Driver |
| @architect | `claude-opus-4-5` | `claude-opus-4-5` | 🔴 Reserve |
| @tester | `qwen3.7-flash` | `qwen3.7-flash` | 🟢 Daily Driver |
| @team (CEO) | `fusion` | `fusion` | 🔴 Reserve |

### ai_erp_lvl-app (Laravel + Inertia)
| Role | Paid Model | Free Model | Tier |
|------|-----------|------------|------|
| @backend | `qwen3.8-max` | `qwen3.8-max` | 🟡 Quality Boost |
| @frontend | `qwen3-coder-plus` | `qwen3-coder-plus` | 🟡 Quality Boost |
| @database | `deepseek-v4.1-flash` | `deepseek-v4.1-flash` | 🟢 Daily Driver |
| @security | `claude-sonnet-4-5` | `claude-sonnet-4-5` | 🟡 Quality Boost |
| @review | `claude-haiku-4-5` | `claude-haiku-4-5` | 🟢 Daily Driver |
| @architect | `claude-opus-4-5` | `claude-opus-4-5` | 🔴 Reserve |
| @tester | `qwen3.7-flash` | `qwen3.7-flash` | 🟢 Daily Driver |
| @team (CEO) | `fusion` | `fusion` | 🔴 Reserve |

### skm-ekp-app (Laravel + Rules)
| Role | Paid Model | Free Model | Tier |
|------|-----------|------------|------|
| @backend | `qwen3.8-max` | `qwen3.8-max` | 🟡 Quality Boost |
| @frontend | `qwen3-coder-plus` | `qwen3-coder-plus` | 🟡 Quality Boost |
| @database | `deepseek-v4.1-flash` | `deepseek-v4.1-flash` | 🟢 Daily Driver |
| @security | `claude-sonnet-4-5` | `claude-sonnet-4-5` | 🟡 Quality Boost |
| @review | `claude-haiku-4-5` | `claude-haiku-4-5` | 🟢 Daily Driver |
| @architect | `claude-opus-4-5` | `claude-opus-4-5` | 🔴 Reserve |
| @tester | `qwen3.7-flash` | `qwen3.7-flash` | 🟢 Daily Driver |
| @team (CEO) | `fusion` | `fusion` | 🔴 Reserve |

## Quick Decision Tree

```
Task masuk?
  │
  ├─ Simple? (review, lint, test, schema, query, fix typo)
  │     → DAILY_DRIVER: deepseek-flash / qwen-flash / claude-haiku
  │
  ├─ Standard? (controller, component, route, migration, API)
  │     → QUALITY_BOOST: qwen3.8-max / qwen-coder-plus / claude-sonnet
  │
  └─ Complex? (architect, security, refactor, multi-tenant)
        → RESERVE: claude-opus / fusion / grok-4.7 (EMERGENCY ONLY)
```

## Usage Commands

### Daily Driver (60% tasks - Hemat Quota)
```
@backend daily "Buat Pest test user"
@database daily "Cek schema tabel companies"
@review daily "Review code auth middleware"
@frontend daily "Review component Button"
```

### Quality Boost (30% tasks - DEFAULT)
```
@backend "Buat Laravel controller Company"
@frontend "Buat Inertia page Dashboard"
@database "Optimasi query Eloquent"
@security "Audit Fortify config"
```

### Reserve (10% tasks - EMERGENCY ONLY)
```
@architect reserve "Design arsitektur multi-tenant"
@security reserve "Deep audit Fortify + spatie"
@team reserve "Koordinasi complex multi-file refactor"
```

## Quota Monitoring Commands

```
# Check quota status
@team quota status

# Emergency mode (quota < 20%)
@team emergency-mode on

# Auto-fallback demo
@backend "task"        # Default: QUALITY_BOOST
@backend daily "task"  # Force DAILY_DRIVER
@backend reserve "task" # Force RESERVE (hati-hati!)
```

## Free Model Specifics (freellmapi)

| Model | Tier | Rate Limit | Best For |
|-------|------|------------|----------|
| `deepseek-v4.1-flash` | 🟢 Daily Driver | High | Schema, queries, simple fix |
| `qwen3.7-flash` | 🟢 Daily Driver | High | Test gen, lint, format |
| `claude-haiku-4-5` | 🟢 Daily Driver | Medium | Quick review, simple code |
| `qwen3.8-max` | 🟡 Quality Boost | Medium | Backend, API, logic |
| `qwen3-coder-plus` | 🟡 Quality Boost | Medium | Frontend, React, TS |
| `claude-sonnet-4-5` | 🟡 Quality Boost | Low | Security, auth, complex |
| `claude-opus-4-5` | 🔴 Reserve | Very Low | Architecture, deep debug |
| `fusion` | 🔴 Reserve | Very Low | Multi-model synthesis |

## Rate Limit Rules (Free Tier)

```
✅ DO:
- Batch 3-5 simple requests → 1 call
- Cache responses 1 hour
- Max 10 req/menit sustained
- Use streaming for long output

❌ DON'T:
- Burst 20+ requests
- Retry immediately on 429 (wait 60s)
- Fusion untuk task simple
- Loop tanpa delay
```

## Fallback Chain (Auto on Error)

```
claude-opus-4-5 → claude-sonnet-4-5 → claude-haiku-4-5 → qwen3.8-max → deepseek-v4.1-flash
      (Reserve)       (Quality)           (Daily)          (Daily)          (Daily)
```

## Quota Tracking (Manual)

```bash
# ~/.ai-quota-log.txt
2026-11-19 10:00  DAILY_DRIVER  deepseek-flash    2.3k
2026-11-19 10:05  QUALITY_BOOST claude-sonnet     8.7k
2026-11-19 10:15  DAILY_DRIVER  qwen-flash        1.1k
TODAY: 12.1k / 1,000,000 (1.2%) ✅
```