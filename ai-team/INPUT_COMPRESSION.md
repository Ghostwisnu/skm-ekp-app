# Input Token Compression Strategy

## Goal
Reduce input token usage by 50-80% through smart context management, prompt optimization, and caching.

---

## 1. CONTEXT COMPRESSION (Paling Efektif: 90% Hemat)

### Problem
```
User: "Buat fitur login"
→ Kirim 5000 token: ARCHITECTURE.md + PROVIDER_MAP.md + TEAM_SETUP.md + file auth.ts + file middleware.ts + file tokens.ts
```

### Solution: Summary Cache
```
User: "Buat fitur login"
→ Cek cache: "login-feature-context" sudah ada?
  ✓ Ya → Kirim 300 token summary
  ✗ Tidak → Kirim full context sekali, lalu cache summary
```

### Implementation

```typescript
// ai-team/context-compressor.ts
interface CompressedContext {
  project: string;
  feature: string;
  summary: string;           // 200-500 tokens
  keyFiles: string[];        // Hanya path file penting
  lastUpdated: Date;
  tokenCount: number;
}

class ContextCompressor {
  private cache = new Map<string, CompressedContext>();
  
  async compress(fullContext: string, feature: string): Promise<CompressedContext> {
    // Gunakan model cheap untuk summarize
    const summary = await this.summarizeWithCheapModel(fullContext);
    
    return {
      project: 'zona-erp-app',
      feature,
      summary,
      keyFiles: this.extractKeyFiles(fullContext),
      lastUpdated: new Date(),
      tokenCount: this.countTokens(summary)
    };
  }
  
  private async summarizeWithCheapModel(context: string): string {
    const prompt = `Summarize this project context for a developer working on ${feature}. 
    Keep ONLY: architecture patterns, key files, auth flow, multi-tenant rules.
    Output: max 300 tokens, bullet points.`;
    
    // Call deepseek-v4.1-flash atau qwen3.7-flash
    return await callModel('deepseek-v4.1-flash', prompt + context);
  }
}
```

---

## 2. PROMPT COMPRESSION (30-50% Hemat)

### Before (Verbose)
```
"Sebagai backend developer untuk proyek Zona ERP yang menggunakan Node.js Express 
dengan TypeScript, Prisma ORM, dan arsitektur multi-tenant berbasis AsyncLocalStorage, 
silakan buatkan endpoint API POST /api/auth/login yang menerima email dan password, 
memvalidasi kredensial, generate JWT access token 15 menit dan refresh token 7 hari 
dengan httpOnly cookie, serta mengembalikan user dengan roles dan permissions 
per company sesuai konteks tenant yang aktif."
```
**~200 tokens**

### After (Compressed)
```
@backend daily "POST /api/auth/login: email/password → JWT(15m) + refresh(7d, httpOnly) → user+roles+perms per company. Multi-tenant ALS context. Follow existing auth patterns."
```
**~60 tokens** (70% hemat)

### Compression Rules

| Original | Compressed | Hemat |
|----------|------------|-------|
| "Sebagai backend developer untuk proyek..." | "@backend daily" | 90% |
| "menggunakan Node.js Express dengan TypeScript..." | (implicit in project config) | 100% |
| "arsitektur multi-tenant berbasis AsyncLocalStorage" | "Multi-tenant ALS context" | 70% |
| "generate JWT access token 15 menit..." | "JWT(15m) + refresh(7d, httpOnly)" | 60% |
| "mengembalikan user dengan roles dan permissions per company" | "user+roles+perms per company" | 50% |

### Auto-Compression Template

```markdown
# Template: @role tier "action: input → output. constraints. context."

@backend daily "POST /api/auth/login: email/password → JWT(15m)+refresh(7d,httpOnly) → user+roles+perms. Multi-tenant ALS. Follow existing."
@frontend daily "Login page: form email/pass → POST /api/auth/login → redirect dashboard. Tailwind + React."
@database daily "Check users table: email unique, password hash, companyId FK, tokenVersion for rotation."
```

---

## 3. RESPONSE CACHING (40-60% Hemat Repetitive Tasks)

### Cache Strategy

```typescript
// ai-team/response-cache.ts
interface CacheEntry {
  prompt: string;
  promptHash: string;
  response: string;
  model: string;
  timestamp: Date;
  hitCount: number;
}

class ResponseCache {
  private cache = new Map<string, CacheEntry>();
  private maxAge = 3600000; // 1 hour
  
  get(prompt: string, model: string): string | null {
    const hash = this.hash(prompt + model);
    const entry = this.cache.get(hash);
    
    if (!entry) return null;
    if (Date.now() - entry.timestamp.getTime() > this.maxAge) {
      this.cache.delete(hash);
      return null;
    }
    
    entry.hitCount++;
    return entry.response;
  }
  
  set(prompt: string, model: string, response: string): void {
    const hash = this.hash(prompt + model);
    this.cache.set(hash, {
      prompt,
      promptHash: hash,
      response,
      model,
      timestamp: new Date(),
      hitCount: 0
    });
  }
  
  // Auto-cleanup old entries
  cleanup(): void {
    const now = Date.now();
    for (const [key, entry] of this.cache.entries()) {
      if (now - entry.timestamp.getTime() > this.maxAge) {
        this.cache.delete(key);
      }
    }
  }
}
```

### What to Cache
| Task Type | Cache? | TTL |
|-----------|--------|-----|
| Code review | ✅ | 1 hour |
| Schema analysis | ✅ | 1 hour |
| Test generation | ✅ | 30 min |
| Simple fixes | ✅ | 1 hour |
| Architecture design | ❌ | Never |
| Security audit | ❌ | Never |
| Creative tasks | ❌ | Never |

---

## 4. BATCHING (20-30% Hemat Overhead)

### Before (Multiple Calls)
```
Call 1: @backend "Buat Pest test User model"
Call 2: @backend "Buat Pest test Company model"  
Call 3: @backend "Buat Pest test Role model"
Call 4: @review "Review auth middleware"
Call 5: @review "Review user controller"
```
**5 calls × overhead = 5000+ tokens**

### After (Single Batched Call)
```
@backend daily "Batch tests: User, Company, Role models. Follow Pest patterns. Return 3 test files."
@review daily "Batch review: auth.middleware.ts + user.controller.ts. Check security + patterns."
```
**2 calls × overhead = 1500 tokens** (70% hemat)

### Batching Rules
```
✅ BATCH WHEN:
- Same role (backend, frontend, review)
- Same tier (daily, quality)
- Related files (tests, reviews, similar components)
- Simple independent tasks

❌ DON'T BATCH:
- Different roles
- Complex dependent tasks
- Architecture decisions
- Security audits
```

---

## 5. SMART FILE INCLUSION (50-70% Hemat Context)

### Problem
```
Kirim 50 file ke model untuk "buat login" → 15000 tokens
```

### Solution: Dependency Graph
```typescript
// ai-team/dependency-analyzer.ts
interface FileDependency {
  file: string;
  imports: string[];
  exports: string[];
  type: 'controller' | 'service' | 'middleware' | 'model' | 'util';
}

class DependencyAnalyzer {
  async getRelevantFiles(task: string, changedFile?: string): Promise<string[]> {
    // 1. Parse task keywords
    const keywords = this.extractKeywords(task);
    
    // 2. Find directly related files
    const direct = await this.findByKeywords(keywords);
    
    // 3. Add imports (1 level deep)
    const imports = await this.getImports(direct);
    
    // 4. If changedFile provided, add its dependents
    const dependents = changedFile ? await this.getDependents(changedFile) : [];
    
    // 5. Deduplicate & limit
    return [...new Set([...direct, ...imports, ...dependents])].slice(0, 10);
  }
}
```

### Result
| Task | Files Before | Files After | Tokens Saved |
|------|-------------|-------------|--------------|
| Buat login | 50 | 5 (auth.ts, tokens.ts, middleware.ts, user.service.ts, schema) | 70% |
| Fix bug chart | 30 | 3 (dashboard.tsx, chart.service.ts, api route) | 90% |
| Add permission | 20 | 4 (role.ts, permission.ts, middleware, schema) | 80% |

---

## 6. IMPLEMENTATION: COMPRESSION PIPELINE

```typescript
// ai-team/compression-pipeline.ts
class CompressionPipeline {
  private contextCompressor = new ContextCompressor();
  private responseCache = new ResponseCache();
  private dependencyAnalyzer = new DependencyAnalyzer();
  
  async process(request: AIRequest): Promise<AIResponse> {
    // 1. Check cache first
    const cached = this.responseCache.get(request.prompt, request.model);
    if (cached) return { response: cached, cached: true, tokensSaved: estimateTokens(request.prompt) };
    
    // 2. Compress prompt
    const compressedPrompt = this.compressPrompt(request.prompt);
    
    // 3. Get minimal relevant files
    const files = await this.dependencyAnalyzer.getRelevantFiles(request.task);
    const fileContext = await this.readFiles(files);
    
    // 4. Compress file context if large
    const context = fileContext.length > 3000 
      ? await this.contextCompressor.compress(fileContext, request.task)
      : fileContext;
    
    // 5. Build final prompt
    const finalPrompt = this.buildPrompt(compressedPrompt, context, request.tier);
    
    // 6. Call model
    const response = await callModel(request.model, finalPrompt);
    
    // 7. Cache response
    this.responseCache.set(request.prompt, request.model, response);
    
    return { response, cached: false, tokensSaved: estimateTokens(request.prompt) - estimateTokens(finalPrompt) };
  }
}
```

---

## 7. ESTIMATED TOTAL SAVINGS

| Technique | Savings | Applies To |
|-----------|---------|------------|
| Context Compression | 90% | Repeated features |
| Prompt Compression | 70% | All requests |
| Response Caching | 60% | Repetitive tasks |
| Smart File Inclusion | 80% | All requests |
| Batching | 70% | Related tasks |
| **COMBINED** | **50-85%** | **Typical workflow** |

---

## 8. QUICK START COMMANDS

```
# Enable compression globally
@team compression on

# Force compressed prompt
@backend daily "login: email/pass→JWT(15m)+refresh(7d,cookie)→user+roles+perms. ALS. Follow patterns."

# Batch similar tasks
@backend daily "Batch: Pest tests User,Company,Role. Pest patterns."
@review daily "Batch review: auth.middleware + user.controller. Security+patterns."

# Check compression stats
@team compression stats
```

---

## 9. MONITORING

```bash
# ~/.ai-compression-log.txt
2026-11-19 10:00  DAILY_DRIVER  login-prompt     180→54  tokens (70% saved)  cached:no
2026-11-19 10:05  DAILY_DRIVER  pest-tests-batch 500→120 tokens (76% saved)  cached:no
2026-11-19 10:10  DAILY_DRIVER  review-auth       400→0   tokens (100% saved) cached:YES
2026-11-19 10:15  QUALITY_BOOST api-design       1200→450 tokens (62% saved) cached:no
TODAY: 2280→624 tokens (73% saved) | Cache hits: 1/4
```