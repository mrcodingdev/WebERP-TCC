<?php
require_once 'db.php';
require_once 'auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] == 'registrar') {
    $produto_id = filter_var($_POST['produto_id'], FILTER_VALIDATE_INT);
    $tipo = $_POST['tipo'] ?? '';
    $quantidade = filter_var($_POST['quantidade'], FILTER_VALIDATE_INT);
    $observacao = trim($_POST['observacao'] ?? '');

    // Validação básica
    if(!$produto_id || !$quantidade || $quantidade <= 0 || empty($tipo)) {
        header("Location: movimentacoes.php?msg=erro_dados");
        exit;
    }

    $pdo->beginTransaction();
    try {
        // 1. Registra no log de Movimentações
        $stmt = $pdo->prepare("INSERT INTO movimentacoes (produto_id, tipo, quantidade, observacao) VALUES (?, ?, ?, ?)");
        $stmt->execute([$produto_id, $tipo, $quantidade, $observacao]);

        // 2. Atualiza o saldo do Produto
        if (in_array($tipo, ['entrada_compra', 'devolucao_cliente'])) {
            $stmtUpd = $pdo->prepare("UPDATE produtos SET quantidade = quantidade + ? WHERE id = ?");
            $stmtUpd->execute([$quantidade, $produto_id]);
        } else {
            // Se for saida_venda, perda, devolucao_fornecedor
            $stmtUpd = $pdo->prepare("UPDATE produtos SET quantidade = quantidade - ? WHERE id = ?");
            $stmtUpd->execute([$quantidade, $produto_id]);
        }

        $pdo->commit();
        header("Location: movimentacoes.php?msg=sucesso");
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: movimentacoes.php?msg=erro_banco");
    }
    exit;
} else {
    header("Location: movimentacoes.php");
    exit;
}
?>
