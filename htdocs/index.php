<?php
require_once 'db.php';
require_once 'auth.php';

// Total de Produtos
$stmtTotal = $pdo->query("SELECT COUNT(*) as total FROM produtos");
$totalProdutos = $stmtTotal->fetch()['total'];

// Estoque Baixo (Quantidade <= Estoque Mínimo)
$stmtEstoqueBaixo = $pdo->query("
    SELECT p.*, f.nome as fornecedor_nome 
    FROM produtos p 
    LEFT JOIN fornecedores f ON p.fornecedor_id = f.id 
    WHERE p.quantidade <= p.estoque_minimo 
    ORDER BY p.quantidade ASC Limit 5
");
$produtosEstoqueBaixo = $stmtEstoqueBaixo->fetchAll();

$stmtTotalBaixo = $pdo->query("SELECT COUNT(*) as total FROM produtos WHERE quantidade <= estoque_minimo");
$totalEstoqueBaixo = $stmtTotalBaixo->fetch()['total'];

// Vencendo em 30 dias ou vencido
$stmtVencimento = $pdo->query("SELECT * FROM produtos WHERE validade IS NOT NULL AND validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY validade ASC Limit 5");
$produtosVencimento = $stmtVencimento->fetchAll();

$stmtTotalVenc = $pdo->query("SELECT COUNT(*) as total FROM produtos WHERE validade IS NOT NULL AND validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
$totalVencimento = $stmtTotalVenc->fetch()['total'];

// Total em valor de estoque
$stmtValor = $pdo->query("SELECT SUM(quantidade * preco_venda) as total_valor FROM produtos");
$valorEstoque = $stmtValor->fetch()['total_valor'];

// Vendas de Hoje
$stmtVendasHoje = $pdo->query("SELECT SUM(total) as vendas_hoje FROM vendas WHERE DATE(data_venda) = CURDATE()");
$vendasHoje = $stmtVendasHoje->fetch()['vendas_hoje'];

// Busca rápida de produtos para o PDV Simplificado
$stmtBuscaPDV = $pdo->query("SELECT id, nome, preco_venda, quantidade FROM produtos WHERE quantidade > 0 ORDER BY nome ASC LIMIT 50");
$produtosPDV = $stmtBuscaPDV->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockControl ERP - Dashboard Manager</title>
    <!-- Bootstrap 5 CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="assets/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar-wrapper">
        <div class="sidebar-brand">
            <i class="fas fa-layer-group"></i> StockControl
        </div>
        
        <?php 
            $userName    = htmlspecialchars($_SESSION['user_name']   ?? 'Usuário');
            $userPerfil  = htmlspecialchars($_SESSION['user_perfil']  ?? 'gestor');
            $perfilLabel = match($userPerfil) {
                'admin'  => 'Administrador',
                'caixa'  => 'Operador de Caixa',
                default  => 'Gestor'
            };
        ?>
        <div class="sidebar-user">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($userName) ?>&background=2d6a4f&color=fff" width="40" height="40" alt="<?= $userName ?>" style="border-radius:50%;border:2px solid #6ae49b;">
            <div>
                <span class="d-block" style="font-size: 13px; font-weight: bold;"><?= $userName ?></span>
                <span class="d-block" style="font-size: 11px; color: #6ae49b;"><?= $perfilLabel ?></span>
            </div>
        </div>


        <ul class="sidebar-menu">
            <li><a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="produtos.php"><i class="fas fa-box"></i> Estoque Mín. & Produtos</a></li>
            <li><a href="movimentacoes.php"><i class="fas fa-exchange-alt"></i> Movimentações</a></li>
            <li><a href="clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
            <li><a href="fornecedores.php"><i class="fas fa-truck"></i> Fornecedores</a></li>
            <li><a href="venda.php"><i class="fas fa-shopping-cart"></i> PDV Completo</a></li>
            <li><a href="fiscal.php"><i class="fas fa-receipt"></i> Fiscal (NFC-e)</a></li>
            <li><a href="analise.php"><i class="fas fa-chart-pie"></i> Análise & Dashboards</a></li>
            <li><a href="relatorio_pdf.php"><i class="fas fa-print"></i> Relatórios PDF</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-panel">
        <div class="topbar">
            <div class="topbar-left">
                <i class="fas fa-calendar-alt text-muted me-2"></i> <span style="font-weight: 500; font-size: 13px;"><?= date('d/m/Y') ?></span> 
                <span class="ms-3 badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="fas fa-circle me-1" style="font-size: 6px; vertical-align: middle;"></i> ERP Ativo</span>
            </div>
            <div class="topbar-icons">
                <span class="user-name"><i class="fas fa-user-circle text-muted me-1"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
                <span class="divider">|</span>
                <a href="logout.php" class="text-danger ms-1" title="Sair do Sistema" style="font-size: 1.15rem; transition: opacity 0.25s;" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
<div class="content-header">
            <h2 class="fw-bold text-dark"><i class="fas fa-tachometer-alt me-2 text-primary"></i> Dashboard Operacional</h2>
            <p class="text-muted">Visão global do estoque e atalhos de vendas.</p>
        </div>

        <div class="content-body">
            <!-- Cards de Resumo -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #0d6efd !important;">
                        <div class="card-body">
                            <h6 class="text-muted fw-bold">Produtos Ativos</h6>
                            <h3 class="mb-0 fw-bold"><?= $totalProdutos ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #198754 !important;">
                        <div class="card-body">
                            <h6 class="text-muted fw-bold">Vendas de Hoje</h6>
                            <h3 class="mb-0 fw-bold text-success">R$ <?= number_format($vendasHoje ?? 0, 2, ',', '.') ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #ffc107 !important;">
                        <div class="card-body">
                            <h6 class="text-muted fw-bold">Estoque Baixo</h6>
                            <h3 class="mb-0 fw-bold text-warning"><?= $totalEstoqueBaixo ?> <small class="text-muted fs-6">itens</small></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #dc3545 !important;">
                        <div class="card-body">
                            <h6 class="text-muted fw-bold">Vencimentos (30d)</h6>
                            <h3 class="mb-0 fw-bold text-danger"><?= $totalVencimento ?> <small class="text-muted fs-6">itens</small></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Coluna da Esquerda (Alertas) -->
                <div class="col-lg-8">
                    <!-- Tabela de Próximos do Vencimento -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-danger m-0"><i class="fas fa-calendar-alt me-2"></i> Perto do Vencimento</h5>
                            <a href="produtos.php" class="btn btn-sm btn-outline-danger">Gerenciar Estoque</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Produto</th>
                                            <th>Validade</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($produtosVencimento) > 0): ?>
                                            <?php foreach($produtosVencimento as $p): 
                                                $dataValidade = new DateTime($p['validade']);
                                                $hoje = new DateTime();
                                                $dias = $hoje->diff($dataValidade)->format('%R%a');
                                                $statusClass = ($dias < 0) ? "bg-dark" : (($dias <= 15) ? "bg-danger" : "bg-warning text-dark");
                                                $statusLabel = ($dias < 0) ? "Vencido" : "Faltam {$dias} dias";
                                            ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($p['nome']) ?></strong><br><small class="text-muted">Estoque atual: <?= $p['quantidade'] ?></small></td>
                                                <td><?= date('d/m/Y', strtotime($p['validade'])) ?></td>
                                                <td class="text-center"><span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="3" class="text-center py-4 text-muted">Tudo certo com os vencimentos.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Tabela de Estoque Baixo -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-warning m-0"><i class="fas fa-exclamation-triangle me-2"></i> Ruptura de Estoque (Baixo)</h5>
                            <a href="produtos.php" class="btn btn-sm btn-outline-warning">Ajustar Mínimos</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Produto / Fornecedor</th>
                                            <th class="text-center">Qtd Atual</th>
                                            <th class="text-center">Qtd Mínima</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($produtosEstoqueBaixo) > 0): ?>
                                            <?php foreach($produtosEstoqueBaixo as $p): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($p['nome']) ?></strong><br><small class="text-muted"><i class="fas fa-truck"></i> <?= htmlspecialchars($p['fornecedor_nome'] ?? 'Sem Fornecedor') ?></small></td>
                                                <td class="text-center"><span class="badge bg-danger fs-6"><?= $p['quantidade'] ?></span></td>
                                                <td class="text-center text-muted fw-bold"><?= $p['estoque_minimo'] ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="3" class="text-center py-4 text-muted">Estoque saudável.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Coluna da Direita (Venda Simplificada) -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-dark text-white pt-3 pb-3">
                            <h5 class="m-0 fw-bold"><i class="fas fa-bolt text-warning me-2"></i> Venda Rápida</h5>
                        </div>
                        <div class="card-body bg-light">
                            <form action="venda_action.php" method="POST">
                                <input type="hidden" name="acao" value="venda_rapida">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Produto</label>
                                    <select class="form-select" name="produto_id" required>
                                        <option value="">Selecione um produto...</option>
                                        <?php if(isset($produtosPDV) && is_array($produtosPDV)): foreach($produtosPDV as $prod): ?>
                                            <option value="<?= $prod['id'] ?>" data-preco="<?= $prod['preco_venda'] ?>">
                                                <?= htmlspecialchars($prod['nome']) ?> (Estoque: <?= $prod['quantidade'] ?>)
                                            </option>
                                        <?php endforeach; endif; ?>
                                    </select>
                                </div>
                                
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label fw-bold">Quantidade</label>
                                        <input type="number" name="quantidade" class="form-control" value="1" min="1" required>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label fw-bold">Preço Unit.</label>
                                        <input type="text" class="form-control bg-white" readonly value="R$ 0,00" id="preco_display">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Forma de Pagto.</label>
                                    <select class="form-select" name="forma_pagamento" required>
                                        <option value="PIX">PIX</option>
                                        <option value="Dinheiro">Dinheiro</option>
                                        <option value="Cartão de Crédito">Cartão de Crédito</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-success w-100 py-2 fw-bold text-uppercase shadow-sm">
                                    <i class="fas fa-check-circle me-2"></i> Finalizar Venda
                                </button>
                                <a href="venda.php" class="btn btn-link w-100 mt-2 text-decoration-none text-muted text-center d-block border border-secondary rounded">Ir para o PDV Completo</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap & Scripts -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script simples para atualizar o preço ao selecionar o produto na Venda Rápida
        document.querySelector('select[name="produto_id"]').addEventListener('change', function() {
            if(this.selectedIndex > 0) {
                const preco = this.options[this.selectedIndex].getAttribute('data-preco');
                document.getElementById('preco_display').value = 'R$ ' + parseFloat(preco).toFixed(2).replace('.', ',');
            } else {
                document.getElementById('preco_display').value = 'R$ 0,00';
            }
        });
    </script>
</body>
</html>