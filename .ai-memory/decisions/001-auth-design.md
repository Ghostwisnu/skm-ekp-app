# ADR 001: Authentication Design

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
