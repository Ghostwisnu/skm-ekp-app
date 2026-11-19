# Memory Bank System - Persistent Project Knowledge

## Goal
Eliminate re-reading project files by storing structured knowledge that persists across sessions.

---

## Architecture

```
Memory Bank (per project)
├── project-memory.json      # Static project knowledge
├── conversation-history/    # Chat history by session
│   ├── session-001.json
│   ├── session-002.json
│   └── ...
├── knowledge-graph.json     # Entity relationships
├── decisions/               # Architectural decisions (ADR)
│   ├── 001-auth-design.md
│   └── ...
└── embeddings/              # Vector embeddings for semantic search
    └── chunks/
```

---

## 1. Project Memory (Static)

```json
// project-memory.json
{
  "project": "zona-erp-app",
  "version": "1.0.0",
  "lastUpdated": "2026-11-19T10:00:00Z",
  "stack": {
    "runtime": "Node.js 20+",
    "language": "TypeScript 5.x",
    "framework": "Express 4.x",
    "orm": "Prisma 5.x",
    "database": "SQLite (dev) / PostgreSQL (prod)",
    "auth": "JWT (HS256) + Refresh Token",
    "frontend": "React 18 + Vite + Tailwind CSS"
  },
  "architecture": {
    "pattern": "Multi-tenant SaaS",
    "tenancy": "Company-scoped via AsyncLocalStorage + Prisma Middleware",
    "rbac": "Role-based with granular permissions",
    "api": "RESTful with Inertia-like patterns"
  },
  "keyFiles": {
    "auth": ["src/auth/tokens.ts", "src/middleware/index.ts"],
    "tenant": ["src/tenant/context.ts"],
    "db": ["prisma/schema.prisma", "src/db/prisma.ts"],
    "models": ["src/models/index.ts"],
    "controllers": ["src/Controllers/"]
  },
  "conventions": {
    "naming": "camelCase for code, PascalCase for types",
    "errors": "AppError with ERR codes",
    "validation": "Zod schemas",
    "testing": "Jest + ts-jest + supertest",
    "multiTenant": "ALL queries auto-scoped via Prisma middleware"
  },
  "security": {
    "jwtSecretMinLength": 32,
    "refreshTokenRotation": true,
    "superAdminBypassLogged": true,
    "invalidTokenFlagged": true
  },
  "commonTasks": {
    "createEndpoint": "Add route → controller → service → validation → test",
    "addPermission": "Permission → RolePermission → UserRole → middleware guard",
    "newModel": "Prisma schema → migration → model queries → tenant scope"
  }
}
```

---

## 2. Conversation History (Dynamic)

```json
// conversation-history/session-001.json
{
  "sessionId": "session-001",
  "project": "zona-erp-app",
  "startedAt": "2026-11-19T10:00:00Z",
  "endedAt": "2026-11-19T11:30:00Z",
  "participants": ["user", "team-brain", "backend", "security", "review"],
  "summary": "Security audit of auth middleware, fixed 4 critical issues",
  "topics": ["security", "auth", "jwt", "multi-tenant", "audit-log"],
  "messages": [
    {
      "id": "msg-001",
      "timestamp": "2026-11-19T10:00:00Z",
      "role": "user",
      "content": "@security \"Audit auth middleware di zona-erp-app\"",
      "tokens": 15
    },
    {
      "id": "msg-002",
      "timestamp": "2026-11-19T10:01:00Z",
      "role": "security",
      "content": "Found 4 critical issues: JWT secret strength, refresh token version, super admin bypass audit, invalid token handling",
      "tokens": 45,
      "references": ["src/auth/tokens.ts", "src/middleware/index.ts", "src/tenant/context.ts"]
    },
    {
      "id": "msg-003",
      "timestamp": "2026-11-19T10:02:00Z",
      "role": "user",
      "content": "Fix semua issue tersebut",
      "tokens": 8
    },
    {
      "id": "msg-004",
      "timestamp": "2026-11-19T10:03:00Z",
      "role": "backend",
      "content": "Fixed all 4 issues. JWT min 32 chars + complexity check. Refresh token version check. Super admin bypass audit log. Invalid token flag.",
      "tokens": 52,
      "filesModified": [
        "src/config/env.ts",
        "src/auth/tokens.ts",
        "src/tenant/context.ts",
        "src/middleware/index.ts"
      ],
      "decisions": [
        "JWT secrets now require 32+ chars with uppercase, lowercase, numbers, special chars",
        "Refresh tokens check version for rotation support",
        "Super admin bypass now logs to AuditLog table"
      ]
    }
  ],
  "outcomes": {
    "filesChanged": 4,
    "testsAdded": 0,
    "issuesFixed": 4,
    "tokensUsed": 1200
  }
}
```

---

## 3. Knowledge Graph (Relational)

```json
// knowledge-graph.json
{
  "entities": {
    "AuthMiddleware": {
      "type": "File",
      "path": "src/middleware/index.ts",
      "description": "Express middleware for JWT auth + tenant context",
      "functions": ["authMiddleware", "withTenantContext", "requireAuth", "requirePermission", "requireRole"],
      "securityIssuesFixed": ["invalid-token-flag", "jwt-secret-strength"]
    },
    "JWTTokens": {
      "type": "Module",
      "path": "src/auth/tokens.ts",
      "description": "JWT sign/verify for access + refresh tokens",
      "functions": ["signAccessToken", "signRefreshToken", "verifyAccessToken", "verifyRefreshToken"],
      "securityIssuesFixed": ["refresh-token-version", "secret-strength"]
    },
    "TenantContext": {
      "type": "Module",
      "path": "src/tenant/context.ts",
      "description": "Multi-tenant core with AsyncLocalStorage + Prisma middleware",
      "keyConcepts": ["ALS", "PrismaMiddleware", "CompanyScoping", "SuperAdminBypass"],
      "securityIssuesFixed": ["super-admin-bypass-audit"]
    },
    "User": {
      "type": "Model",
      "fields": ["id", "email", "password", "tokenVersion", "memberships"],
      "relations": ["UserCompany", "UserRole", "AuditLog"]
    },
    "Company": {
      "type": "Model",
      "description": "Tenant root - all data scoped to companyId",
      "fields": ["id", "code", "name", "status"]
    },
    "AuditLog": {
      "type": "Model",
      "description": "Security audit trail",
      "fields": ["id", "userId", "action", "entity", "entityId", "meta", "createdAt"],
      "loggedActions": ["SUPER_ADMIN_BYPASS", "USER_STATUS_CHANGED", "LOGIN", "LOGOUT"]
    },
    "Permission": {
      "type": "Model",
      "description": "Resource.action format (e.g., users.create)",
      "fields": ["id", "code", "resource", "action"]
    }
  },
  "relationships": [
    { "from": "AuthMiddleware", "to": "JWTTokens", "type": "USES", "description": "Calls verifyAccessToken" },
    { "from": "AuthMiddleware", "to": "TenantContext", "type": "USES", "description": "Uses ALS for tenant context" },
    { "from": "TenantContext", "to": "AuditLog", "type": "CREATES", "description": "Logs super admin bypass" },
    { "from": "User", "to": "Company", "type": "BELONGS_TO", "via": "UserCompany" },
    { "from": "User", "to": "Permission", "type": "HAS", "via": "UserRole → RolePermission" },
    { "from": "JWTTokens", "to": "User", "type": "READS", "description": "Token contains userId, companyId, roles, permissions" }
  ]
}
```

---

## 4. Decisions Log (ADR)

```markdown
# decisions/001-auth-design.md

# ADR 001: JWT Authentication with Refresh Token Rotation

## Status: Accepted

## Context
Need stateless auth for multi-tenant ERP with company switching.

## Decision
- Access Token: 15 min, HS256, contains roles/permissions per company
- Refresh Token: 7 days, HS256, contains userId + version for rotation
- Version checked on refresh - reject if stale
- httpOnly cookie for refresh token

## Consequences
- Short-lived access tokens = limited exposure
- Rotation support = revoke compromised tokens
- Company switch = new access token required

## Related
- ADR 002: Multi-tenant scoping via Prisma Middleware
- ADR 003: Super Admin Audit Logging
```

```markdown
# decisions/002-multi-tenant.md

# ADR 002: Multi-Tenant Scoping via Prisma Middleware

## Status: Accepted

## Context
All data must be company-scoped. Manual where clauses error-prone.

## Decision
- AsyncLocalStorage carries tenant context through request
- Prisma middleware intercepts ALL queries on tenant-scoped models
- Auto-injects companyId into WHERE (reads) and data (writes)
- Super admin with NO active company = bypass (see all)

## Models Scoped: UserCompany, Role, UserRole, MenuItem, AuditLog
## Models NOT Scoped: Company (tenant root), Permission (global catalog)

## Consequences
- Impossible to forget scoping
- Super admin bypass is explicit + audited
- Migration: existing data needs companyId backfill
```

---

## 5. Implementation: Memory Bank Manager

```typescript
// ai-team/memory-bank.ts
import fs from 'fs/promises';
import path from 'path';

interface ProjectMemory {
  project: string;
  version: string;
  lastUpdated: string;
  stack: Record<string, string>;
  architecture: Record<string, string>;
  keyFiles: Record<string, string[]>;
  conventions: Record<string, string>;
  security: Record<string, any>;
  commonTasks: Record<string, string>;
}

interface ConversationMessage {
  id: string;
  timestamp: string;
  role: string;
  content: string;
  tokens: number;
  references?: string[];
  filesModified?: string[];
  decisions?: string[];
}

interface SessionHistory {
  sessionId: string;
  project: string;
  startedAt: string;
  endedAt?: string;
  participants: string[];
  summary: string;
  topics: string[];
  messages: ConversationMessage[];
  outcomes: {
    filesChanged: number;
    testsAdded: number;
    issuesFixed: number;
    tokensUsed: number;
  };
}

interface KnowledgeEntity {
  type: string;
  path?: string;
  description: string;
  [key: string]: any;
}

interface KnowledgeGraph {
  entities: Record<string, KnowledgeEntity>;
  relationships: Array<{
    from: string;
    to: string;
    type: string;
    description: string;
  }>;
}

export class MemoryBank {
  private projectRoot: string;
  private memoryDir: string;
  
  constructor(projectRoot: string) {
    this.projectRoot = projectRoot;
    this.memoryDir = path.join(projectRoot, '.ai-memory');
  }
  
  async init(): Promise<void> {
    await fs.mkdir(this.memoryDir, { recursive: true });
    await fs.mkdir(path.join(this.memoryDir, 'conversation-history'), { recursive: true });
    await fs.mkdir(path.join(this.memoryDir, 'decisions'), { recursive: true });
    
    // Create default project memory if not exists
    const memoryPath = path.join(this.memoryDir, 'project-memory.json');
    if (!(await this.exists(memoryPath))) {
      await this.createDefaultMemory();
    }
    
    // Create default knowledge graph if not exists
    const graphPath = path.join(this.memoryDir, 'knowledge-graph.json');
    if (!(await this.exists(graphPath))) {
      await this.createDefaultGraph();
    }
  }
  
  // ========== PROJECT MEMORY ==========
  
  async getProjectMemory(): Promise<ProjectMemory> {
    const content = await fs.readFile(
      path.join(this.memoryDir, 'project-memory.json'),
      'utf-8'
    );
    return JSON.parse(content);
  }
  
  async updateProjectMemory(updates: Partial<ProjectMemory>): Promise<void> {
    const memory = await this.getProjectMemory();
    const updated = { ...memory, ...updates, lastUpdated: new Date().toISOString() };
    await fs.writeFile(
      path.join(this.memoryDir, 'project-memory.json'),
      JSON.stringify(updated, null, 2)
    );
  }
  
  // ========== CONVERSATION HISTORY ==========
  
  async saveSession(session: SessionHistory): Promise<void> {
    const file = path.join(this.memoryDir, 'conversation-history', `${session.sessionId}.json`);
    await fs.writeFile(file, JSON.stringify(session, null, 2));
  }
  
  async getSession(sessionId: string): Promise<SessionHistory | null> {
    const file = path.join(this.memoryDir, 'conversation-history', `${sessionId}.json`);
    if (!(await this.exists(file))) return null;
    const content = await fs.readFile(file, 'utf-8');
    return JSON.parse(content);
  }
  
  async listSessions(): Promise<SessionHistory[]> {
    const dir = path.join(this.memoryDir, 'conversation-history');
    const files = await fs.readdir(dir);
    const sessions: SessionHistory[] = [];
    
    for (const file of files) {
      if (file.endsWith('.json')) {
        const content = await fs.readFile(path.join(dir, file), 'utf-8');
        sessions.push(JSON.parse(content));
      }
    }
    
    return sessions.sort((a, b) => 
      new Date(b.startedAt).getTime() - new Date(a.startedAt).getTime()
    );
  }
  
  async getRecentContext(limit: number = 5): Promise<string> {
    const sessions = await this.listSessions();
    const recent = sessions.slice(0, limit);
    
    return recent.map(s => `
=== Session ${s.sessionId} (${s.startedAt}) ===
Summary: ${s.summary}
Topics: ${s.topics.join(', ')}
Outcomes: ${s.outcomes.filesChanged} files, ${s.outcomes.issuesFixed} issues fixed
Key Decisions: ${s.messages
  .filter(m => m.decisions && m.decisions.length > 0)
  .flatMap(m => m.decisions!)
  .join('; ') || 'None'}
`).join('\n');
  }
  
  // ========== KNOWLEDGE GRAPH ==========
  
  async getKnowledgeGraph(): Promise<KnowledgeGraph> {
    const content = await fs.readFile(
      path.join(this.memoryDir, 'knowledge-graph.json'),
      'utf-8'
    );
    return JSON.parse(content);
  }
  
  async queryGraph(entityName: string): Promise<KnowledgeEntity | null> {
    const graph = await this.getKnowledgeGraph();
    return graph.entities[entityName] || null;
  }
  
  async getRelated(entityName: string): Promise<Array<{entity: KnowledgeEntity, relation: string}>> {
    const graph = await this.getKnowledgeGraph();
    const results = [];
    
    for (const rel of graph.relationships) {
      if (rel.from === entityName) {
        const entity = graph.entities[rel.to];
        if (entity) results.push({ entity, relation: rel.type });
      } else if (rel.to === entityName) {
        const entity = graph.entities[rel.from];
        if (entity) results.push({ entity, relation: `inverse:${rel.type}` });
      }
    }
    
    return results;
  }
  
  async addEntity(name: string, entity: KnowledgeEntity): Promise<void> {
    const graph = await this.getKnowledgeGraph();
    graph.entities[name] = entity;
    await fs.writeFile(
      path.join(this.memoryDir, 'knowledge-graph.json'),
      JSON.stringify(graph, null, 2)
    );
  }
  
  // ========== DECISIONS ==========
  
  async addDecision(id: string, title: string, content: string): Promise<void> {
    const file = path.join(this.memoryDir, 'decisions', `${id}-${title.toLowerCase().replace(/\s+/g, '-')}.md`);
    await fs.writeFile(file, content);
  }
  
  async getDecisions(): Promise<string[]> {
    const dir = path.join(this.memoryDir, 'decisions');
    const files = await fs.readdir(dir);
    return files.filter(f => f.endsWith('.md'));
  }
  
  // ========== HELPERS ==========
  
  private async exists(filePath: string): Promise<boolean> {
    try {
      await fs.access(filePath);
      return true;
    } catch {
      return false;
    }
  }
  
  private async createDefaultMemory(): Promise<void> {
    const defaultMemory: ProjectMemory = {
      project: path.basename(this.projectRoot),
      version: "1.0.0",
      lastUpdated: new Date().toISOString(),
      stack: {},
      architecture: {},
      keyFiles: {},
      conventions: {},
      security: {},
      commonTasks: {}
    };
    await fs.writeFile(
      path.join(this.memoryDir, 'project-memory.json'),
      JSON.stringify(defaultMemory, null, 2)
    );
  }
  
  private async createDefaultGraph(): Promise<void> {
    const defaultGraph: KnowledgeGraph = {
      entities: {},
      relationships: []
    };
    await fs.writeFile(
      path.join(this.memoryDir, 'knowledge-graph.json'),
      JSON.stringify(defaultGraph, null, 2)
    );
  }
}

// ========== QUICK ACCESS FUNCTIONS ==========

export async function getProjectContext(projectRoot: string): Promise<string> {
  const bank = new MemoryBank(projectRoot);
  await bank.init();
  
  const [memory, recentContext, graph] = await Promise.all([
    bank.getProjectMemory(),
    bank.getRecentContext(3),
    bank.getKnowledgeGraph()
  ]);
  
  const entityCount = Object.keys(graph.entities).length;
  const relationCount = graph.relationships.length;
  
  return `
# PROJECT CONTEXT: ${memory.project}
Last Updated: ${memory.lastUpdated}

## Stack
${Object.entries(memory.stack).map(([k, v]) => `- ${k}: ${v}`).join('\n')}

## Architecture
${Object.entries(memory.architecture).map(([k, v]) => `- ${k}: ${v}`).join('\n')}

## Key Files
${Object.entries(memory.keyFiles).map(([k, v]) => `- ${k}: ${v.join(', ')}`).join('\n')}

## Conventions
${Object.entries(memory.conventions).map(([k, v]) => `- ${k}: ${v}`).join('\n')}

## Security
${Object.entries(memory.security).map(([k, v]) => `- ${k}: ${JSON.stringify(v)}`).join('\n')}

## Knowledge Graph
Entities: ${entityCount} | Relationships: ${relationCount}

## Recent Sessions
${recentContext || 'No previous sessions'}
`;
}
```

---

## 6. Integration with Team Brain

```typescript
// ai-team/team-brain-with-memory.ts

export class TeamBrainWithMemory {
  private memoryBank: MemoryBank;
  
  constructor(projectRoot: string) {
    this.memoryBank = new MemoryBank(projectRoot);
  }
  
  async processRequest(userInput: string): Promise<string> {
    // 1. Initialize memory bank
    await this.memoryBank.init();
    
    // 2. Get compressed project context (replaces reading all files)
    const projectContext = await getProjectContext(this.memoryBank.projectRoot);
    
    // 3. Build prompt with context
    const prompt = this.buildPrompt(userInput, projectContext);
    
    // 4. Process with appropriate model
    const response = await this.dispatchToAgent(userInput, prompt);
    
    // 5. Save session
    await this.saveSession(userInput, response);
    
    return response;
  }
  
  private buildPrompt(userInput: string, projectContext: string): string {
    return `
${projectContext}

---

# CURRENT REQUEST
${userInput}

# INSTRUCTIONS
- Use project context above instead of reading files
- Reference specific entities from knowledge graph
- Follow conventions and security rules from project memory
- Update knowledge graph with new discoveries
`;
  }
  
  private async saveSession(input: string, output: string): Promise<void> {
    const sessionId = `session-${Date.now()}`;
    const session = {
      sessionId,
      project: path.basename(this.memoryBank.projectRoot),
      startedAt: new Date().toISOString(),
      participants: ['user', 'team-brain'],
      summary: this.extractSummary(input, output),
      topics: this.extractTopics(input),
      messages: [
        { id: 'msg-1', timestamp: new Date().toISOString(), role: 'user', content: input, tokens: this.countTokens(input) },
        { id: 'msg-2', timestamp: new Date().toISOString(), role: 'assistant', content: output, tokens: this.countTokens(output) }
      ],
      outcomes: { filesChanged: 0, testsAdded: 0, issuesFixed: 0, tokensUsed: this.countTokens(input + output) }
    };
    
    await this.memoryBank.saveSession(session);
  }
}
```

---

## 7. Usage Commands

```bash
# Initialize memory bank for project
@team memory init

# Show project context (replaces reading files)
@team memory context

# Show recent sessions
@team memory history

# Query knowledge graph
@team memory graph "AuthMiddleware"

# Show related entities
@team memory related "JWTTokens"

# Add decision
@team memory decision "004" "New caching strategy for API responses"

# Update project memory
@team memory update "keyFiles.controllers" "['src/Controllers/', 'src/routes/']"
```

---

## 8. Storage Structure (Per Project)

```
zona-erp-app/
├── .ai-memory/
│   ├── project-memory.json        # ~5KB - static knowledge
│   ├── knowledge-graph.json       # ~10KB - entities + relations
│   ├── conversation-history/
│   │   ├── session-1700000000000.json
│   │   ├── session-1700000001000.json
│   │   └── ...
│   └── decisions/
│       ├── 001-auth-design.md
│       ├── 002-multi-tenant.md
│       └── 003-super-admin-audit.md
```

---

## 9. Token Savings

| Before (Per Session) | After (With Memory Bank) |
|---------------------|-------------------------|
| Read 20 files: ~15,000 tokens | Load context: ~2,000 tokens |
| Re-explain architecture: 2,000 tokens | Already in context: 0 tokens |
| Repeat conventions: 1,000 tokens | Already in context: 0 tokens |
| **Total: ~18,000 tokens** | **Total: ~2,000 tokens** |

**Hemat: ~89% token reduction for context loading**

---

## 10. Auto-Update Rules

```typescript
// Auto-update triggers
const AUTO_UPDATE_TRIGGERS = {
  // When files modified, update keyFiles + graph
  onFileChange: ['project-memory.keyFiles', 'knowledge-graph.entities'],
  
  // When security issue fixed, update security + decisions
  onSecurityFix: ['project-memory.security', 'decisions'],
  
  // When new pattern established, update conventions
  onNewPattern: ['project-memory.conventions', 'knowledge-graph.entities'],
  
  // When session ends, update recent context
  onSessionEnd: ['conversation-history', 'project-memory.lastUpdated']
};
```