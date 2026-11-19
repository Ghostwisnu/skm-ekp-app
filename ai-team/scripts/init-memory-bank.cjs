#!/usr/bin/env node
/**
 * Memory Bank Initialization Script - JavaScript Version
 * Run: node scripts/init-memory-bank.js
 */

const fs = require('fs').promises;
const path = require('path');

const PROJECTS = [
  'C:/Users/HYPE/orca/projects/zona-erp-app',
  'C:/Project/ai_erp_lvl-app',
  'C:/Project/skm-ekp-app'
].map(p => p.replace(/\\/g, '/'));

async function exists(filePath) {
  try {
    await fs.access(filePath);
    return true;
  } catch {
    return false;
  }
}

async function initMemoryBank(projectRoot) {
  const projectName = path.basename(projectRoot);
  const memoryDir = path.join(projectRoot, '.ai-memory');
  const historyDir = path.join(memoryDir, 'conversation-history');
  const decisionsDir = path.join(memoryDir, 'decisions');
  
  console.log(`\n📦 Initializing Memory Bank for: ${projectName}`);
  console.log(`   Path: ${memoryDir}`);
  
  // Create directories
  await fs.mkdir(memoryDir, { recursive: true });
  await fs.mkdir(historyDir, { recursive: true });
  await fs.mkdir(decisionsDir, { recursive: true });
  
  // 1. Create project-memory.json
  const projectMemory = {
    project: projectName,
    version: "1.0.0",
    lastUpdated: new Date().toISOString(),
    stack: {},
    architecture: {},
    keyFiles: {},
    conventions: {},
    security: {},
    commonTasks: {}
  };
  
  // Auto-detect stack from project files
  const packageJsonPath = path.join(projectRoot, 'package.json');
  const composerJsonPath = path.join(projectRoot, 'composer.json');
  const prismaPath = path.join(projectRoot, 'prisma', 'schema.prisma');
  
  if (await exists(packageJsonPath)) {
    const pkg = JSON.parse(await fs.readFile(packageJsonPath, 'utf-8'));
    projectMemory.stack.language = pkg.type === 'module' ? 'TypeScript/ESM' : 'TypeScript/CommonJS';
    projectMemory.stack.framework = pkg.dependencies?.express ? 'Express' : 
                                    pkg.dependencies?.next ? 'Next.js' :
                                    pkg.dependencies?.['@inertiajs/react'] ? 'Laravel + Inertia + React' : 'Unknown';
    projectMemory.stack.orm = pkg.dependencies?.['@prisma/client'] ? 'Prisma' :
                              pkg.dependencies?.sequelize ? 'Sequelize' :
                              pkg.dependencies?.typeorm ? 'TypeORM' : 'None';
    projectMemory.stack.database = pkg.dependencies?.sqlite3 ? 'SQLite' :
                                   pkg.dependencies?.pg ? 'PostgreSQL' :
                                   pkg.dependencies?.mysql2 ? 'MySQL' : 'Unknown';
  }
  
  if (await exists(composerJsonPath)) {
    const composer = JSON.parse(await fs.readFile(composerJsonPath, 'utf-8'));
    projectMemory.stack.php = composer.require?.php || '^8.0';
    projectMemory.stack.laravel = composer.require?.['laravel/framework'] || 'Unknown';
    projectMemory.stack.inertia = composer.require?.['inertiajs/inertia-laravel'] ? true : false;
  }
  
  if (await exists(prismaPath)) {
    projectMemory.stack.orm = 'Prisma';
    const schema = await fs.readFile(prismaPath, 'utf-8');
    if (schema.includes('provider = "sqlite"')) projectMemory.stack.database = 'SQLite (dev) / PostgreSQL (prod)';
    if (schema.includes('provider = "postgresql"')) projectMemory.stack.database = 'PostgreSQL';
    if (schema.includes('provider = "mysql"')) projectMemory.stack.database = 'MySQL';
  }
  
  // Detect key files
  const srcPath = path.join(projectRoot, 'src');
  if (await exists(srcPath)) {
    const entries = await fs.readdir(srcPath);
    for (const entry of entries) {
      const fullPath = path.join(srcPath, entry);
      const stat = await fs.stat(fullPath);
      if (stat.isDirectory()) {
        projectMemory.keyFiles[entry] = [`src/${entry}/`];
      }
    }
  }
  
  // Detect controllers
  const controllersPath = path.join(projectRoot, 'src', 'Controllers');
  if (await exists(controllersPath)) {
    const files = await fs.readdir(controllersPath);
    projectMemory.keyFiles.controllers = files.filter(f => f.endsWith('.ts') || f.endsWith('.js'));
  }
  
  // Set conventions based on stack
  const isLaravel = projectMemory.stack.framework?.includes('Laravel') || projectMemory.stack.laravel;
  
  if (isLaravel) {
    projectMemory.conventions = {
      naming: "PSR-12 for PHP, camelCase for JS/TS",
      errors: "AppError with ERR codes",
      validation: "Laravel FormRequest + Zod",
      testing: "Pest PHP + Jest for frontend",
      multiTenant: "Company-scoped via middleware + Prisma/Als"
    };
    projectMemory.security = {
      jwtSecretMinLength: 32,
      refreshTokenRotation: true,
      superAdminBypassLogged: true,
      invalidTokenFlagged: true,
      csrfProtection: true,
      rateLimiting: true
    };
    projectMemory.commonTasks = {
      createEndpoint: "Route → Controller → FormRequest → Service → Resource → Test",
      addPermission: "Permission → RolePermission → UserRole → Gate/Policy",
      newModel: "Migration → Model → Factory → Seeder → Query Scopes"
    };
  } else {
    projectMemory.conventions = {
      naming: "camelCase for code, PascalCase for types",
      errors: "AppError with ERR codes",
      validation: "Zod schemas",
      testing: "Jest + ts-jest + supertest",
      multiTenant: "Company-scoped via AsyncLocalStorage + Prisma Middleware"
    };
    projectMemory.security = {
      jwtSecretMinLength: 32,
      refreshTokenRotation: true,
      superAdminBypassLogged: true,
      invalidTokenFlagged: true
    };
    projectMemory.commonTasks = {
      createEndpoint: "Route → Controller → Service → Validation → Test",
      addPermission: "Permission → RolePermission → UserRole → Middleware Guard",
      newModel: "Prisma Schema → Migration → Model Queries → Tenant Scope"
    };
  }
  
  // Architecture detection
  projectMemory.architecture = {
    pattern: isLaravel ? 'MVC + Inertia SPA' : 'Multi-tenant SaaS',
    tenancy: 'Company-scoped via middleware + Prisma/ALS',
    rbac: 'Role-based with granular permissions',
    api: isLaravel ? 'Laravel Routes + Inertia' : 'RESTful Express'
  };
  
  await fs.writeFile(
    path.join(memoryDir, 'project-memory.json'),
    JSON.stringify(projectMemory, null, 2)
  );
  console.log(`   ✅ project-memory.json created`);
  
  // 2. Create knowledge-graph.json (empty template)
  const knowledgeGraph = {
    entities: {},
    relationships: []
  };
  
  await fs.writeFile(
    path.join(memoryDir, 'knowledge-graph.json'),
    JSON.stringify(knowledgeGraph, null, 2)
  );
  console.log(`   ✅ knowledge-graph.json created`);
  
  // 3. Create sample decisions
  const decisions = [
    {
      id: '001',
      title: 'auth-design',
      content: `# ADR 001: Authentication Design

## Status: Accepted

## Context
Need stateless auth for multi-tenant application.

## Decision
- JWT Access Token (15 min) + Refresh Token (7 days)
- HS256 signing
- Refresh token rotation via version field
- httpOnly cookie for refresh token

## Consequences
- Short-lived access = limited exposure
- Rotation = revoke compromised tokens
`
    },
    {
      id: '002',
      title: 'multi-tenant',
      content: `# ADR 002: Multi-Tenant Data Isolation

## Status: Accepted

## Context
All data must be isolated per company.

## Decision
- AsyncLocalStorage for tenant context propagation
- Prisma middleware auto-injects companyId
- Super admin bypass explicit + audited
- Models scoped: UserCompany, Role, UserRole, MenuItem, AuditLog

## Consequences
- Impossible to forget scoping
- Audit trail for admin access
`
    }
  ];
  
  for (const decision of decisions) {
    const file = path.join(decisionsDir, `${decision.id}-${decision.title}.md`);
    await fs.writeFile(file, decision.content);
  }
  console.log(`   ✅ Sample decisions created`);
  
  console.log(`   🎉 Memory Bank initialized for ${projectName}!`);
}

async function main() {
  console.log('🚀 Initializing Memory Banks for all projects...\n');
  
  for (const project of PROJECTS) {
    try {
      await initMemoryBank(project);
    } catch (error) {
      console.error(`   ❌ Error initializing ${project}:`, error.message);
    }
  }
  
  console.log('\n✅ All Memory Banks initialized!');
  console.log('\nNext steps:');
  console.log('  1. Run: node scripts/init-memory-bank.js');
  console.log('  2. Use @team memory context to load project context');
}

main().catch(console.error);