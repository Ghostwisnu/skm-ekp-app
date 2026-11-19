# 📋 How to View Status (No Code Required)

## Option 1: Open File Directly (Easiest)

**VS Code / Cursor / Any Editor:**
```
File → Open File → ai-team/TEAM_STATUS.md
```

**Command Line:**
```bash
# Windows
notepad ai-team/TEAM_STATUS.md

# Mac/Linux
open ai-team/TEAM_STATUS.md      # Mac
xdg-open ai-team/TEAM_STATUS.md  # Linux
```

---

## Option 2: Browser Preview (Best Visual)

**VS Code:** `Ctrl+Shift+V` (Markdown Preview)  
**Obsidian / Typora / MarkText:** Buka file langsung  
**GitHub/GitLab:** Push repo → lihat di web UI  

---

## Option 3: Quick Commands (Di Chat)

```bash
# View status inline
@team status

# View specific section
@team status quota
@team status memory
@team status models
@team status activity
@team status handoff
@team status focus
@team status alerts
```

---

## Option 4: Auto-Refresh (Live)

**VS Code Extension:** "Markdown Preview Enhanced" → Auto-refresh on save

**Browser Extension:** "Markdown Viewer" → Drag file ke browser

---

## Option 5: Terminal (Simple)

```bash
# Quick view (first 50 lines)
head -50 ai-team/TEAM_STATUS.md

# Watch for changes (Linux/Mac)
watch -n 5 'head -50 ai-team/TEAM_STATUS.md'

# Windows PowerShell
while ($true) { Clear-Host; Get-Content ai-team/TEAM_STATUS.md -Head 50; Start-Sleep 5 }
```

---

## 📱 Mobile Friendly

**GitHub Mobile App:** Push repo → buka `ai-team/TEAM_STATUS.md`  
**Obsidian Sync:** Sync vault → buka di HP  

---

## 🔄 Update Cara Manual (Gampang)

Cukup edit file markdown:
```markdown
# Edit bagian yang berubah
| Metric | Status | Target |
|--------|--------|--------|
| **Quota Used** | 🟢 15% | < 80% daily |  # ← ganti 12% → 15%
```

**Atau via command:**
```
@team dashboard update "quota" "15%"
@team dashboard update "focus" "Add rate limiting"
@team dashboard update "alert" "Quota 85% - reduce reserve"
```

---

## 📍 File Locations (All 3 Projects)

| Project | Dashboard File |
|---------|----------------|
| **zona-erp-app** | `/c/Users/HYPE/orca/projects/zona-erp-app/ai-team/TEAM_STATUS.md` |
| **ai_erp_lvl-app** | `/c/Project/ai_erp_lvl-app/ai-team/TEAM_STATUS.md` |
| **skm-ekp-app** | `/c/Project/skm-ekp-app/ai-team/TEAM_STATUS.md` |

---

## 🎯 Quick Start

1. **Buka file** `ai-team/TEAM_STATUS.md` di editor favorit
2. **Baca overview** - 10 detik paham status
3. **Update manual** saat selesai task
4. **Commit** ke git untuk history

**No code, no script, just markdown!** 📝