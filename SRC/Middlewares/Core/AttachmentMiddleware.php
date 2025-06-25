<?php
namespace Express\SRC\Middlewares\Core;

/**
 * Middleware padrão para tratar anexos (uploads de arquivos).
 * Adiciona validação e manipulação centralizada dos arquivos enviados.
 */
class AttachmentMiddleware
{
    /**
     * Middleware para tratar anexos.
     * Exemplo de uso:
     * $app->use(AttachmentMiddleware::handle());
     *
     * @param array $options Opções de validação (tipos, tamanho, etc)
     * @return callable
     */
    public static function handle($options = [])
    {
        return function ($request, $response, $next) use ($options) {
            // Exemplo: validação de tamanho máximo (em bytes)
            if (!empty($options['maxSize'])) {
                foreach ($request->files as $file) {
                    if (isset($file['size']) && $file['size'] > $options['maxSize']) {
                        $response->status(400)->json([
                            'error' => 'Arquivo muito grande',
                            'field' => $file['name'],
                            'maxSize' => $options['maxSize'],
                        ]);
                        return;
                    }
                }
            }
            // Exemplo: validação de tipos permitidos
            if (!empty($options['types'])) {
                foreach ($request->files as $file) {
                    if (isset($file['type']) && !in_array($file['type'], $options['types'])) {
                        $response->status(400)->json([
                            'error' => 'Tipo de arquivo não permitido',
                            'field' => $file['name'],
                            'type' => $file['type'],
                            'allowed' => $options['types'],
                        ]);
                        return;
                    }
                }
            }
            // Você pode adicionar mais validações ou manipulações aqui
            $next();
        };
    }
}
