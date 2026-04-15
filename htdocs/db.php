<?php
// Arquivo de Conexão com o Banco de Dados (PDO)
// Padrão do XAMPP: root sem senha

$host = 'sql100.infinityfree.com';
$dbname = 'if0_41554148_easystock_db';
$user = 'if0_41554148';
$pass = 'B9HWoBZxUn2';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    // Configura o PDO para lançar exceções em caso de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Configura para retornar arrays associativos por padrão
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro de Conexão: " . $e->getMessage());
}
?>
