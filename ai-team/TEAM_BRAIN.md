# skm-ekp-app - Team Brain (CEO) - Multi-Provider

## Vision
Satu titik koordinasi untuk seluruh kebutuhan development AI. Team Brain adalah CEO yang mengoordinasikan multiple AI providers untuk distribute token load.

## Multi-Provider Architecture

```
User → Team Brain (fusion) → Multiple AI Providers (qwen, claude, deepseek, dll)
```

## Provider Assignments

| Role | Model | Provider | Specialization |
|------|-------|----------|----------------|
| **CEO/Coordinator** | `freellmapi/fusion` | FreeLLMAPI | Panel synthesis, coordinates all |
| **Backend** | `freellmapi/qwen3.8-max` | Qwen | PHP/Laravel specialist |
| **Frontend** | `freellmapi/qwen3-coder-plus` | Qwen | React/Inertia specialist |
| **Database** | `freellmapi/deepseek-v4.1-flash` | DeepSeek | Fast schema analysis |
| **Security** | `freellmapi/claude-sonnet-4-5` | Anthropic | Security review |
| **Review** | `freellmapi/claude-haiku-4-5` | Anthropic | Fast code review |
| **Architect** | `freellmapi/claude-opus-4-5` | Anthropic | Deep architecture |
| **Tester** | `freellmapi/qwen3.7-flash` | Qwen | Fast test generation |

## Commands

### @team - CEO Coordination
```
@team "Buat fitur login"
```
→ Fusion coordinates, dispatches to backend (qwen), frontend (qwen-coder), security (claude-sonnet)

### @backend - Direct Backend
```
@backend "Buat Laravel route"
```
→ Uses qwen3.8-max directly

### @frontend - Direct Frontend
```
@frontend "Buat Inertia page"
```
→ Uses qwen3-coder-plus directly

### @security - Direct Security
```
@security "Audit auth"
```
→ Uses claude-sonnet-4-5 directly

## Workflow

### Pattern A: New Feature
```
@team "Buat fitur company dashboard"
```
1. Fusion analyzes → dispatches:
   - qwen3.8-max: Laravel route + controller
   - qwen3-coder-plus: Inertia page + React component
   - deepseek-v4.1-flash: Database query optimization
   - claude-sonnet-4-5: Security review
2. Results merged by fusion
3. Returns integrated feature

### Pattern B: Bug Fix
```
@team "Fix bug: chart nggak muncul"
```
1. Fusion dispatches:
   - qwen3-coder-plus: Frontend investigation
   - qwen3.8-max: Backend investigation
   - claude-haiku-4-5: Code review
2. Fusion synthesizes findings
3. Returns fix plan

### Pattern C: Security Audit
```
@team "Review security auth"
```
1. Fusion dispatches:
   - claude-sonnet-4-5: Deep security analysis
   - claude-opus-4-5: Architecture review
   - qwen3.7-flash: Test generation
2. Returns comprehensive security report

## Cost Optimization
- High-cost: Security, Architect → Claude (worth it)
- Medium-cost: Backend, Frontend → Qwen (good coding)
- Low-cost: Database, Review, Tester → Flash/fast models (cheaper)
- Coordination: Fusion (panel synthesis) → Best judgment

## Quick Start
```
@team "perintahmu"
@backend "perintah khusus backend"
@frontend "perintah khusus frontend"
@security "perintah khusus security"
```

## Available Providers
Qwen, DeepSeek, Mistral, Claude, GPT, Gemini, GLM, MiniMax, Kimi, Agnes, Nemotron, Gemma, Devstral, ElevenLabs - 256+ models total