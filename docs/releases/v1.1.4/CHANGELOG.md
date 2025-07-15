# Changelog - PivotPHP Core v1.1.4

**Release Date:** Janeiro 2025  
**Release Type:** Infrastructure Optimization  
**Breaking Changes:** None  

## 🎯 Summary

v1.1.4 focuses on **infrastructure consolidation** and **developer experience optimization**. This release eliminates script duplication, implements automatic version detection, and streamlines workflows while maintaining 100% backward compatibility.

## 🆕 Added

### New Scripts and Tools
- **`scripts/utils/version-utils.sh`** - Shared utility library for version detection and project validation
- **`scripts/release/version-bump.sh`** - Enhanced semantic version management with automation
- **VERSION file requirement** - Central version source with strict validation

### New Documentation
- **`docs/VERSIONING_GUIDE.md`** - Comprehensive 315-line guide for semantic versioning
- **`docs/releases/FRAMEWORK_OVERVIEW_v1.1.4.md`** - Complete release overview
- **`docs/releases/v1.1.4/RELEASE_NOTES.md`** - Detailed release notes
- **`docs/releases/v1.1.4/MIGRATION_GUIDE.md`** - Step-by-step migration instructions
- **`docs/releases/v1.1.4/CHANGELOG.md`** - This changelog

### New Features
- **Automatic version detection** from VERSION file across all scripts
- **Project root detection** that works from any directory
- **Semantic version validation** with X.Y.Z format enforcement
- **Portuguese error messages** for better clarity
- **Git integration** in version-bump.sh with automatic commit/tag creation
- **Dry-run mode** for version bump preview

## 🔄 Changed

### Scripts Updated
- **`scripts/quality/quality-check.sh`** - Now consolidates functionality from multiple removed scripts
- **`scripts/release/prepare_release.sh`** - Enhanced with automatic version detection and path independence
- **`scripts/validate_project.php`** - Updated to use VERSION file with strict validation
- **`scripts/validate-documentation.php`** - Enhanced with automatic version detection
- **`scripts/validate_openapi.sh`** - Updated with version detection and better error handling
- **`scripts/README.md`** - Completely rewritten with categorized organization and usage examples

### GitHub Actions Updated
- **`.github/workflows/ci.yml`** - Updated to use consolidated `quality-check.sh` script
- **`.github/workflows/pre-release.yml`** - Enhanced with automatic version detection from VERSION file
- **`.github/workflows/release.yml`** - Fixed repository URLs from express-php to pivotphp-core, added version consistency validation

### Workflow Improvements
- **Script execution** now works from any directory within the project
- **Error handling** improved with clear, actionable messages in Portuguese
- **Version consistency** enforced across all tools and workflows
- **Project context validation** ensures scripts run in correct environment

## 🗑️ Removed

### Duplicate/Obsolete Scripts (10 total)
- **`scripts/quality-check-v114.sh`** - Hardcoded version, functionality moved to `quality-check.sh`
- **`scripts/validate_all_v114.sh`** - Hardcoded version, functionality moved to `validate_all.sh`
- **`scripts/quick-quality-check.sh`** - Duplicate functionality, consolidated into `quality-check.sh`
- **`scripts/simple_pre_release.sh`** - Replaced by enhanced `prepare_release.sh`
- **`scripts/quality-gate.sh`** - Functionality integrated into `quality-check.sh`
- **`scripts/quality-metrics.sh`** - Functionality integrated into `quality-check.sh`
- **`scripts/test-php-versions-quick.sh`** - Functionality available in `test-all-php-versions.sh`
- **`scripts/ci-validation.sh`** - Functionality integrated into `quality-check.sh`
- **`scripts/setup-precommit.sh`** - One-time setup script, no longer needed
- **`scripts/adapt-psr7-v1.php`** - Specific utility script, removed to reduce complexity

### GitHub Actions Removed
- **`.github/workflows/quality-gate.yml`** - Duplicate functionality, consolidated into `ci.yml`

### Hardcoded References Removed
- All hardcoded version strings in scripts
- All hardcoded project paths
- All references to removed scripts in documentation

## 🔧 Fixed

### Script Issues
- **GitHub Actions workflow references** to non-existent scripts
- **Hardcoded project paths** that prevented running from different directories
- **Version inconsistencies** across different scripts and documentation
- **Error handling** improved with clear, actionable error messages
- **Repository URLs** corrected from express-php to pivotphp-core in release workflow

### Validation Improvements
- **Project context detection** more robust and reliable
- **Version format validation** stricter with semantic versioning enforcement
- **Error messages** now descriptive and actionable in Portuguese
- **File path resolution** works correctly from any directory

### Documentation Fixes
- **Script references** updated to point to correct consolidated scripts
- **Workflow examples** updated with current script names
- **Installation instructions** simplified and clarified

## 📊 Metrics

### Consolidation Results
- **Scripts reduced:** 25 → 15 (40% reduction)
- **Duplications eliminated:** 10 scripts removed
- **GitHub Actions workflows:** 4 → 3 (25% reduction)
- **Hardcoding eliminated:** 100% removed
- **Documentation added:** 500+ lines of new documentation

### Maintained Performance
- **Framework performance:** No changes (40,476 ops/sec maintained)
- **JSON pooling:** No changes (161K ops/sec small data maintained)
- **Test coverage:** No changes (≥30% maintained)
- **Code quality:** PHPStan Level 9, PSR-12 compliance maintained

## 🧪 Testing

### Validation Performed
- ✅ **All existing tests pass** (684 CI + 131 integration tests)
- ✅ **New script functionality tested** with various scenarios
- ✅ **Error conditions validated** (missing VERSION, invalid format, wrong directory)
- ✅ **GitHub Actions workflows tested** with consolidated scripts
- ✅ **Cross-platform compatibility** verified (Linux, macOS, WSL)
- ✅ **Version detection accuracy** tested across all scripts

### Quality Assurance
- ✅ **PHPStan Level 9:** 0 errors
- ✅ **PSR-12 Compliance:** 100%
- ✅ **Security validation:** No sensitive information in error outputs
- ✅ **Performance impact:** Zero impact on framework performance

## 📚 Documentation Updates

### New Documentation Files
- `docs/VERSIONING_GUIDE.md` - When to increment MAJOR, MINOR, PATCH
- `docs/releases/FRAMEWORK_OVERVIEW_v1.1.4.md` - Complete release overview
- `docs/releases/v1.1.4/RELEASE_NOTES.md` - Detailed release notes
- `docs/releases/v1.1.4/MIGRATION_GUIDE.md` - Migration instructions
- `docs/releases/v1.1.4/CHANGELOG.md` - This changelog

### Updated Documentation Files
- `scripts/README.md` - Completely rewritten with categorized organization
- Multiple script files with updated headers and descriptions

## 🛡️ Security

### Enhanced Validation
- **VERSION file validation** prevents malformed version injection
- **Project context validation** ensures scripts run in correct environment
- **Input sanitization** for version strings and file paths
- **Clear error messages** reduce security through obscurity without exposing sensitive information

### No Security Issues
- **No vulnerabilities introduced** in this release
- **No sensitive information exposed** in error messages
- **No breaking changes** to existing security features
- **No new attack vectors** created

## 🔄 Migration Impact

### Backward Compatibility
- ✅ **100% API compatibility** - No framework API changes
- ✅ **Existing code works** without modification
- ✅ **Gradual migration** - Old script references still work where possible
- ✅ **Clear migration path** with detailed documentation

### Migration Effort
- **Low effort:** Mostly optional changes
- **Required:** Create VERSION file (one command)
- **Recommended:** Update script references in custom CI/CD
- **Optional:** Adopt new version management workflow

## 🎯 Developer Experience

### Improvements
- **Fewer scripts to remember** (40% reduction)
- **Consistent command interface** across all scripts
- **Automatic version detection** eliminates manual errors
- **Better error messages** with clear solutions in Portuguese
- **Comprehensive documentation** with examples and troubleshooting

### New Capabilities
- **Semantic version management** with `version-bump.sh`
- **Project-wide validation** with `quality-check.sh`
- **Automatic release preparation** with enhanced scripts
- **Context-aware execution** from any directory

## 🔮 Future Compatibility

### Prepared for Future
- **Flexible architecture** supports future script additions
- **Version management system** ready for automated releases
- **Documentation structure** scalable for future versions
- **Consolidated approach** reduces maintenance burden

### Deprecation Policy
- **No deprecations** in v1.1.4
- **Backward compatibility maintained** for smooth transitions
- **Future changes** will follow semantic versioning principles
- **Migration guides** will be provided for any breaking changes

## 📊 Comparison with Previous Versions

| Feature | v1.1.3 | v1.1.4 | Change |
|---------|--------|--------|--------|
| Active Scripts | 25 | 15 | -40% |
| Script Duplicates | 10 | 0 | -100% |
| Hardcoded Versions | Yes | No | Eliminated |
| Version Detection | Manual | Automatic | Automated |
| GitHub Actions | 4 | 3 | -25% |
| Error Messages | English | Portuguese | Localized |
| Documentation Lines | Limited | 500+ | Major increase |
| Framework Performance | 40,476 ops/sec | 40,476 ops/sec | Maintained |

## 🙏 Acknowledgments

This release represents significant infrastructure improvements that will benefit the entire PivotPHP Core ecosystem:

- **Reduced complexity** makes the project more approachable for new contributors
- **Improved reliability** through automated validation and better error handling
- **Enhanced documentation** provides clear guidance for all development scenarios
- **Streamlined maintenance** reduces long-term technical debt

Special thanks to the community for feedback that drove these infrastructure improvements.

---

**v1.1.4 - Infrastructure Excellence for Better Developer Experience** 🚀