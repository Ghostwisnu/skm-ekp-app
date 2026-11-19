# ADR 002: Multi-Tenant Data Isolation

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
