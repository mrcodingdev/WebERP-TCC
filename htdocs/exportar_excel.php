<?php
require_once 'db.php';
require_once 'auth.php';
$tipo = $_GET['tipo'] ?? 'completo';

if ($tipo == 'baixo') {
    $nome_arquivo = "estoque_baixo_" . date('Ymd_H_i') . ".xls";
    $stmt = $pdo->query("SELECT p.id, p.nome, p.categoria, p.quantidade, p.estoque_minimo, p.preco_venda, f.nome as fornecedor FROM produtos p LEFT JOIN fornecedores f ON p.fornecedor_id = f.id WHERE p.quantidade <= p.estoque_minimo");
} elseif ($tipo == 'validade') {
    $nome_arquivo = "vencimentos_" . date('Ymd_H_i') . ".xls";
    $stmt = $pdo->query("SELECT p.id, p.nome, p.categoria, p.validade, p.quantidade, f.nome as fornecedor FROM produtos p LEFT JOIN fornecedores f ON p.fornecedor_id = f.id WHERE p.validade IS NOT NULL AND p.validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
} elseif ($tipo == 'vendas') {
    $nome_arquivo = "relatorio_vendas_" . date('Ymd_H_i') . ".xls";
    $stmt = $pdo->query("SELECT v.id as Venda_ID, c.nome as Cliente, v.data_venda, v.total as Valor_Total, v.forma_pagamento as Pagamento FROM vendas v LEFT JOIN clientes c ON v.cliente_id = c.id ORDER BY v.data_venda DESC");
} else {
    // Completo
    $nome_arquivo = "inventario_completo_" . date('Ymd_H_i') . ".xls";
    $stmt = $pdo->query("SELECT p.id as Codigo, p.nome as Produto, p.categoria as Categoria, p.quantidade as Estoque, p.estoque_minimo as Minimo, p.validade as Vencimento, p.preco_venda as Venda_Unit, p.preco_compra as Custo, f.nome as Fornecedor FROM produtos p LEFT JOIN fornecedores f ON p.fornecedor_id = f.id");
}

$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_clean();
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$nome_arquivo\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";
echo "<table border='1'>";
echo "<tr><th colspan='10'><strong>Relatório do StockControl ERP</strong> - Gerado em " . date('d/m/Y H:i') . "</th></tr>";

if (count($dados) > 0) {
    echo "<tr>";
    foreach (array_keys($dados[0]) as $chave) {
        $chaveFormatada = ucfirst(str_replace('_', ' ', $chave));
        echo "<td style='background-color:#0d6efd; color:#ffffff;'><strong>" . $chaveFormatada . "</strong></td>";
    }
    echo "</tr>";
    foreach ($dados as $row) {
        echo "<tr>";
        foreach ($row as $coluna => $valor) {
            echo "<td>" . htmlspecialchars($valor ?? '--') . "</td>";
        }
        echo "</tr>";
    }
} else {
    echo "<tr><td>Nenhum registro encontrado.</td></tr>";
}
echo "</table>";
exit;
?>
