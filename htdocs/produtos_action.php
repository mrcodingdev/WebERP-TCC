<?php
require_once 'db.php';
require_once 'auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $acao = $_POST['acao'] ?? '';

    if ($acao == 'salvar') {
        $id = $_POST['id'] ?? '';
        $nome = $_POST['nome'] ?? '';
        $categoria = $_POST['categoria'] ?? '';
        $validade = !empty($_POST['validade']) ? $_POST['validade'] : null;
        $fornecedor_id = !empty($_POST['fornecedor_id']) ? $_POST['fornecedor_id'] : null;
        $preco_venda = $_POST['preco_venda'] ?? 0.00;
        $preco_compra = $_POST['preco_compra'] ?? 0.00;
        $quantidade = $_POST['quantidade'] ?? 0;
        $estoque_minimo = $_POST['estoque_minimo'] ?? 5;
        
        // Verifica variação de quantidade para o log de Movimentações (ajuste manual)
        $diff_quantidade = 0;

        if ($id) {
            // Verifica Qtd Anterior
            $stmtAnt = $pdo->prepare("SELECT quantidade FROM produtos WHERE id = ?");
            $stmtAnt->execute([$id]);
            $qtdAnterior = $stmtAnt->fetchColumn();
            $diff_quantidade = $quantidade - $qtdAnterior;

            // Update
            $sql = "UPDATE produtos SET nome=?, categoria=?, validade=?, fornecedor_id=?, preco_venda=?, preco_compra=?, quantidade=?, estoque_minimo=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $categoria, $validade, $fornecedor_id, $preco_venda, $preco_compra, $quantidade, $estoque_minimo, $id]);
            
            $prod_id = $id;
        } else {
            // Insert
            $sql = "INSERT INTO produtos (nome, categoria, validade, fornecedor_id, preco_venda, preco_compra, quantidade, estoque_minimo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $categoria, $validade, $fornecedor_id, $preco_venda, $preco_compra, $quantidade, $estoque_minimo]);
            
            $prod_id = $pdo->lastInsertId();
            $diff_quantidade = $quantidade;
        }

        // Se houve alteração manual de quantidade pela tela de Produtos, registra na Movimentacao para não quebrar o histórico
        if ($diff_quantidade > 0) {
            $stmtMov = $pdo->prepare("INSERT INTO movimentacoes (produto_id, tipo, quantidade, observacao) VALUES (?, 'entrada_compra', ?, 'Ajuste Manual via Cadastro')");
            $stmtMov->execute([$prod_id, $diff_quantidade]);
        } elseif ($diff_quantidade < 0) {
            $stmtMov = $pdo->prepare("INSERT INTO movimentacoes (produto_id, tipo, quantidade, observacao) VALUES (?, 'perda', ?, 'Ajuste Manual via Cadastro')");
            $stmtMov->execute([$prod_id, abs($diff_quantidade)]);
        }

        header("Location: produtos.php?msg=sucesso");
        exit;

    } elseif ($acao == 'deletar') {
        $id = $_POST['id'] ?? '';
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM produtos WHERE id=?");
            $stmt->execute([$id]);
        }
        header("Location: produtos.php?msg=sucesso");
        exit;
    }
}
?>
