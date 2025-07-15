# PivotPHP Core - Migration Guide

## 📋 Current Migration Documentation

**For detailed migration instructions, please refer to the official release documentation:**

### 🔄 Latest Version: v1.1.4
**[Complete Migration Guide →](releases/v1.1.4/MIGRATION_GUIDE.md)**

**Migration highlights:**
- **🔧 Infrastructure Consolidation**: 40% script reduction (25 → 15)
- **📦 Automatic Version Management**: VERSION file requirement with strict validation
- **🚀 GitHub Actions Optimization**: 25% workflow reduction (4 → 3)
- **✅ Zero Breaking Changes**: 100% backward compatibility maintained

### 📚 Version-Specific Migration Guides

| From Version | Migration Guide | Effort Level |
|--------------|----------------|--------------|
| **v1.1.3** | [v1.1.4 Migration Guide](releases/v1.1.4/MIGRATION_GUIDE.md) | **Low** (mostly optional) |
| **v1.1.2** | [v1.1.4 Migration Guide](releases/v1.1.4/MIGRATION_GUIDE.md) | **Low** (infrastructure only) |
| **v1.1.1** | [v1.1.4 Migration Guide](releases/v1.1.4/MIGRATION_GUIDE.md) | **Low** (backward compatible) |
| **v1.1.0** | [v1.1.4 Migration Guide](releases/v1.1.4/MIGRATION_GUIDE.md) | **Medium** (multiple versions) |
| **v1.0.x** | [v1.1.4 Migration Guide](releases/v1.1.4/MIGRATION_GUIDE.md) | **Medium** (feature changes) |

### 🎯 Quick Migration Checklist

#### ⚠️ Required Actions (v1.1.4):
- [ ] **Create VERSION file** in project root: `echo "1.1.4" > VERSION`
- [ ] **Update script references** in custom CI/CD (if any)
- [ ] **Test consolidated scripts** work correctly

#### ✅ Recommended Actions:
- [ ] **Use consolidated scripts** (`scripts/quality/quality-check.sh`)
- [ ] **Adopt automatic versioning** (`scripts/release/version-bump.sh`)
- [ ] **Read versioning guide** ([docs/VERSIONING_GUIDE.md](VERSIONING_GUIDE.md))

### 📖 Additional Resources

- **[Versioning Guide](VERSIONING_GUIDE.md)** - Complete semantic versioning guidance
- **[Framework Overview v1.1.4](releases/FRAMEWORK_OVERVIEW_v1.1.4.md)** - Complete release overview
- **[Release Notes v1.1.4](releases/v1.1.4/RELEASE_NOTES.md)** - Detailed release notes
- **[Changelog](../CHANGELOG.md)** - Complete version history

### 🆘 Migration Support

If you encounter migration issues:

1. **Check the specific migration guide** for your version
2. **Review error messages** (now in Portuguese for clarity)
3. **Consult the troubleshooting section** in the migration guide
4. **Ask in Discord community**: https://discord.gg/DMtxsP7z
5. **Create GitHub issue**: https://github.com/PivotPHP/pivotphp-core/issues

---

**Note**: This general migration guide has been replaced by version-specific documentation for better accuracy and detail. Please use the appropriate version-specific guide above.