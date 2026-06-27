<?php
// download_atividade.php

// 1) Pega o nome do arquivo da query string
$arquivo = $_GET['arquivo'] ?? '';
$arquivo = basename($arquivo); // evita caminhos maliciosos

// 2) Caminho base NO SISTEMA DE ARQUIVOS
$baseDir = '/var/www/moodle-adaptativa/public/assets/pdf';
$caminhoCompleto = $baseDir . DIRECTORY_SEPARATOR . $arquivo;

// 3) Valida se o arquivo existe e está dentro da pasta correta
if (!is_file($caminhoCompleto)) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

// 4) Garante que não tem nada no buffer antes dos headers
if (ob_get_level()) {
    ob_end_clean();
}

// 5) Cabeçalhos do download
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($caminhoCompleto) . '"');
header('Content-Length: ' . filesize($caminhoCompleto));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');

// 6) Envia o arquivo
flush();
readfile($caminhoCompleto);
exit;
