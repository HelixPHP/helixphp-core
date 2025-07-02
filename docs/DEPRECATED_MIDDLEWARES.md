# 🚨 Middlewares Depreciados

A partir da versão 2.1, todos os middlewares antigos (não compatíveis com PSR-15) foram depreciados e não são mais recomendados para uso em novos projetos.

**Use apenas middlewares compatíveis com PSR-15.**

- Os middlewares antigos (baseados em handle($request, $response, $next) ou mocks) não recebem mais atualizações de segurança ou performance.
- Toda a documentação, exemplos e testes foram migrados para o padrão PSR-15.
- Para migração, consulte os exemplos atualizados e utilize sempre objetos PSR-7/PSR-15.

> **Nota:** O uso de middlewares legados pode causar incompatibilidade com ferramentas modernas, análise estática e frameworks atuais.

# Middlewares legados

Todos os arquivos de testes e implementações legadas foram movidos para a pasta `legacy/` na raiz do projeto. Essa pasta contém apenas código obsoleto, mantido para referência histórica. Não utilize middlewares ou testes dessa pasta em novos projetos.

- Apenas middlewares PSR-15 são suportados oficialmente.
- Para exemplos e testes atualizados, consulte a pasta `src/Http/Psr15/Middleware/` e `tests/Core/`.
