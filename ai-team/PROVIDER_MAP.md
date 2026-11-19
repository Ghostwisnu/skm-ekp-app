# Provider Map - Multi-Provider Team CEO

## Provider Assignment Strategy

Distributes token load across different models based on role specialization.

## Model Assignments

| Role | Model | Provider | Why |
|------|-------|----------|-----|
| **CEO/Coordinator** | `freellmapi/fusion` | FreeLLMAPI | Panel of models, synthesizes answers - best for coordination |
| **Backend** | `freellmapi/qwen3.8-max` | Qwen | Coding specialist, strong PHP/Laravel |
| **Frontend** | `freellmapi/qwen3-coder-plus` | Qwen | React/Inertia specialist |
| **Database** | `freellmapi/deepseek-v4.1-flash` | DeepSeek | Fast + cheap, good for schema analysis |
| **Security** | `freellmapi/claude-sonnet-4-5` | Anthropic | Security review strength |
| **Review** | `freellmapi/claude-haiku-4-5` | Anthropic | Fast code review |
| **Architect** | `freellmapi/claude-opus-4-5` | Anthropic | Deep architectural thinking |
| **Tester** | `freellmapi/qwen3.7-flash` | Qwen | Fast test generation |
| **Fallback** | `freellmapi/free-router` | FreeLLMAPI | Auto-routes to best available |

## Token Distribution

```
User → CEO (fusion) → Backend (qwen3.8-max)
                    → Frontend (qwen-coder-plus)
                    → Database (deepseek-v4.1-flash)
                    → Security (claude-sonnet-4-5)
                    → Review (claude-haiku-4-5)
                    → Architect (claude-opus-4-5)
                    → Tester (qwen3.7-flash)
```

## Cost Optimization

- **High-cost tasks**: Security, Architect → Claude models (worth it for quality)
- **Medium-cost tasks**: Backend, Frontend → Qwen models (good coding ability)
- **Low-cost tasks**: Database, Review, Tester → Flash/fast models (cheaper, faster)
- **Coordination**: Fusion (panel synthesis) → Best overall judgment

## How to Use

### Direct Model Call
```
@backend "Buat Laravel route" → uses qwen3.8-max
@frontend "Buat Inertia page" → uses qwen-coder-plus
@security "Audit auth" → uses claude-sonnet-4-5
```

### CEO Coordination
```
@team "Buat fitur login" → fusion coordinates, dispatches to all relevant models
```

## Available Models (256+ total)

Key providers: Qwen, DeepSeek, Mistral, Claude, GPT, Gemini, GLM, MiniMax, Kimi, Agnes, Nemotron, Gemma, Devstral, ElevenLabs