<?php
// conexao.php

$host = 'localhost';
$usuario = '';
$senha = '???????';
$banco = 'adapt_vis_db';

$conn = new mysqli($host, $usuario, $senha, $banco);

// Verifica se houve erro na conexão
if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

// Opcional: definir charset para garantir compatibilidade com utf8mb4
$conn->set_charset("utf8mb4");

// Nenhuma mensagem de sucesso é exibida aqui — conexão silenciosa
?>
