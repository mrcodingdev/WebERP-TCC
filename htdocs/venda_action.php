<?php
require_once 'db.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $acao = $_POST['acao'] ?? 'venda_completa';
    
    $pdo->beginTransaction();
    try {
        if ($acao == 'venda_rapida') {
            // Vem do Dashboard (Apenas 1 item anonimo)
            $produto_id = (int) $_POST['produto_id'];
            $quantidade = (int) $_POST['quantidade'];
            $forma_pagamento = $_POST['forma_pagamento'] ?? 'DINHEIRO';
            
            // Busca o preco
            $stmtP = $pdo->prepare("SELECT preco_venda FROM produtos WHERE id = ?");
            $stmtP->execute([$produto_id]);
            $preco = $stmtP->fetchColumn();
            
            $total = $preco * $quantidade;
            
            // 1. Cria a venda (sem cliente)
            $stmtV = $pdo->prepare("INSERT INTO vendas (cliente_id, total, forma_pagamento) VALUES (NULL, ?, ?)");
            $stmtV->execute([$total, $forma_pagamento]);
            $venda_id = $pdo->lastInsertId();
            
            // 2. Insere Item
            $stmtI = $pdo->prepare("INSERT INTO vendas_itens (venda_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
            $stmtI->execute([$venda_id, $produto_id, $quantidade, $preco]);
            
            // 3. Atualiza Estoque
            $stmtE = $pdo->prepare("UPDATE produtos SET quantidade = quantidade - ? WHERE id = ?");
            $stmtE->execute([$quantidade, $produto_id]);
            
            // 4. Log Movimentacao
            $stmtM = $pdo->prepare("INSERT INTO movimentacoes (produto_id, tipo, quantidade, observacao) VALUES (?, 'saida_venda', ?, ?)");
            $stmtM->execute([$produto_id, $quantidade, "Venda Rápida PDV #" . $venda_id]);
            
        } else {
            // Vem do PDV Completo (venda.php)
            $forma_pagamento = $_POST['forma_pagamento'] ?? 'DINHEIRO';
            $cliente_id = !empty($_POST['cliente_id']) ? $_POST['cliente_id'] : null;
            $cart_data = $_POST['cart_data'] ?? '[]';
            $cart = json_decode($cart_data, true);
            
            if (empty($cart)) die("Erro: O carrinho está vazio.");
            
            $total = 0;
            foreach ($cart as $item) {
                $total += $item['preco'] * $item['quantidade'];
            }
            
            // 1. Cria a venda
            $stmtV = $pdo->prepare("INSERT INTO vendas (cliente_id, total, forma_pagamento) VALUES (?, ?, ?)");
            $stmtV->execute([$cliente_id, $total, $forma_pagamento]);
            $venda_id = $pdo->lastInsertId();
            
            // Processa cada item
            foreach ($cart as $item) {
                // 2. Insere Item
                $stmtI = $pdo->prepare("INSERT INTO vendas_itens (venda_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
                $stmtI->execute([$venda_id, $item['id'], $item['quantidade'], $item['preco']]);
                
                // 3. Reduz Estoque
                $stmtE = $pdo->prepare("UPDATE produtos SET quantidade = quantidade - ? WHERE id = ?");
                $stmtE->execute([$item['quantidade'], $item['id']]);
                
                // 4. Log Movimentacao
                $stmtM = $pdo->prepare("INSERT INTO movimentacoes (produto_id, tipo, quantidade, observacao) VALUES (?, 'saida_venda', ?, ?)");
                $stmtM->execute([$item['id'], $item['quantidade'], "Venda Direta PDV #" . $venda_id]);
            }
        }
        
        // 5. Gera a Emulação do Cupom Fiscal (NFC-e)
        // Chave de acesso NFC-e de 44 numeros
        $chave = "3526030000000000000065001" . str_pad($venda_id, 9, '0', STR_PAD_LEFT) . "100000000";
        $chave .= rand(111, 999);
        $chave = str_pad(substr($chave, 0, 44), 44, '0');

        $stmtF = $pdo->prepare("INSERT INTO cupons_fiscais (venda_id, chave_acesso) VALUES (?, ?)");
        $stmtF->execute([$venda_id, $chave]);
        
        $pdo->commit();
        
        if ($acao == 'venda_rapida') {
            header("Location: index.php?msg=sucesso");
        } else {
            header("Location: fiscal.php?msg=sucesso");
        }
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Houve um erro ao salvar a venda: " . $e->getMessage());
    }
} else {
    header("Location: venda.php");
    exit;
}
?>
