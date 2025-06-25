# Contributing to Express PHP

Thank you for your interest in contributing to Express PHP! We welcome contributions from the community.

## 🤝 How to Contribute

### 1. Fork the Repository
- Fork the project on GitHub
- Clone your fork locally
- Create a new branch for your feature

### 2. Set Up Development Environment
```bash
git clone https://github.com/your-username/Express-PHP.git
cd Express-PHP
```

### 3. Make Your Changes
- Follow the existing code style
- Add tests for new features
- Update documentation as needed

### 4. Test Your Changes
```bash
# Run security tests
php test/security_test.php

# Run examples to ensure they work
php examples/example_complete.php
php examples/example_security.php
```

### 5. Submit a Pull Request
- Push your changes to your fork
- Create a pull request with a clear description
- Link any related issues

## 📝 Code Style Guidelines

### PHP Code Style
- Use PSR-4 autoloading
- Follow PSR-12 coding standard
- Use meaningful variable and function names
- Add docblocks for classes and methods

### Middleware Development
- Place security middlewares in `SRC/Middlewares/Security/`
- Place core middlewares in `SRC/Middlewares/Core/`
- Follow the middleware template pattern
- Include comprehensive tests

### Documentation
- Update both English and Portuguese documentation
- Include code examples
- Keep README files updated

## 🐛 Bug Reports

When reporting bugs, please include:
- PHP version
- Express PHP version
- Steps to reproduce
- Expected vs actual behavior
- Error messages or logs

## 💡 Feature Requests

For new features:
- Describe the use case
- Explain the benefit to users
- Consider backward compatibility
- Provide implementation ideas if possible

## 🔒 Security Issues

For security vulnerabilities:
- **DO NOT** open a public issue
- Email security@expressphp.com (if available)
- Or create a private security advisory on GitHub

## 📚 Types of Contributions

We welcome:
- Bug fixes
- New middleware development
- Performance improvements
- Documentation improvements
- Example applications
- Test coverage improvements
- Translations

## 🌍 Internationalization

Help us support more languages:
- Translate documentation
- Add language-specific examples
- Localize error messages

## 📋 Pull Request Checklist

Before submitting:
- [ ] Code follows style guidelines
- [ ] Tests pass
- [ ] Documentation updated
- [ ] Backward compatibility maintained
- [ ] Examples work correctly
- [ ] Security implications considered

## 🏷️ Commit Messages

Use clear commit messages:
```
feat: add XSS protection middleware
fix: resolve CSRF token validation issue
docs: update security documentation
test: add middleware integration tests
```

## 📖 Development Resources

- [Express PHP Documentation](docs/en/README.md)
- [Middleware Documentation](SRC/Middlewares/README.md)
- [Security Implementation Guide](SECURITY_IMPLEMENTATION.md)
- [Migration Guide](MIDDLEWARE_MIGRATION.md)

## 🎯 Contribution Areas

High priority areas:
- Performance optimizations
- Additional security features
- More comprehensive tests
- Better error handling
- Enhanced documentation

## 📞 Getting Help

- Check existing issues and discussions
- Read the documentation thoroughly
- Look at example implementations
- Ask questions in issues (tag with "question")

## 📄 License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

Thank you for helping make Express PHP better! 🚀
