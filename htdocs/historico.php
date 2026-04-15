<?php
require_once 'db.php';
require_once 'auth.php';

if ($_SESSION['user_perfil'] !== 'admin') {
    die("Acesso Negado. Apenas administradores podem acessar o histórico.");
}

$stmt = $pdo->query("
    SELECT v.id, v.data_venda, v.total, v.forma_pagamento, COUNT(vi.id) as qtd_itens 
    FROM vendas v 
    LEFT JOIN vendas_itens vi ON v.id = vi.venda_id 
    GROUP BY v.id 
    ORDER BY v.data_venda DESC
");
$vendas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyStock ERP - Histórico de Vendas</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="sidebar-wrapper">
        <div class="sidebar-brand">
            <i class="fas fa-layer-group"></i> StockControl
        </div>
        
        <div class="sidebar-user">
            <?php
                $userName    = htmlspecialchars($_SESSION['user_name']   ?? 'Usuário');
                $userPerfil  = htmlspecialchars($_SESSION['user_perfil'] ?? 'gestor');
                $perfilLabel = match($userPerfil) {
                    'admin'  => 'Administrador',
                    'caixa'  => 'Operador de Caixa',
                    default  => 'Gestor'
                };
            ?>
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($userName) ?>&background=2d6a4f&color=fff" width="40" height="40" alt="<?= $userName ?>" style="border-radius:50%;border:2px solid #6ae49b;">
            <div>
                <span class="d-block" style="font-size: 13px; font-weight: bold;"><?= $userName ?></span>
                <span class="d-block" style="font-size: 11px; color: #6ae49b;"><?= $perfilLabel ?></span>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="produtos.php"><i class="fas fa-box"></i> Estoque Mín. & Produtos</a></li>
            <li><a href="movimentacoes.php"><i class="fas fa-exchange-alt"></i> Movimentações</a></li>
            <li><a href="clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
            <li><a href="fornecedores.php"><i class="fas fa-truck"></i> Fornecedores</a></li>
            <li><a href="venda.php"><i class="fas fa-shopping-cart"></i> PDV Completo</a></li>
            <li><a href="fiscal.php"><i class="fas fa-receipt"></i> Fiscal (NFC-e)</a></li>
            <li><a href="analise.php"><i class="fas fa-chart-pie"></i> Análise & Dashboards</a></li>
            <li><a href="historico.php" class="active"><i class="fas fa-history"></i> Histórico Vendas</a></li>
            <li><a href="relatorio_pdf.php"><i class="fas fa-print"></i> Relatórios PDF</a></li>
        </ul>
    </div>

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
            <h1><i class="fas fa-history text-secondary me-2"></i> Histórico de Vendas</h1>
        </div>

        <div class="content-body">
            
            <div class="card panel-border shadow-sm border-0 mt-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th width="10%">Nº Venda</th>
                                    <th width="20%">Data / Hora</th>
                                    <th width="15%" class="text-center">Qtd. Itens Diferentes</th>
                                    <th width="20%">Forma Pagamento</th>
                                    <th width="15%">Total (R$)</th>
                                    <th width="20%" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($vendas) > 0): ?>
                                    <?php foreach($vendas as $v): ?>
                                    <tr>
                                        <td class="fw-bold">#<?= str_pad($v['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($v['data_venda'])) ?></td>
                                        <td class="text-center"><?= $v['qtd_itens'] ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($v['forma_pagamento']) ?></span></td>
                                        <td class="text-success fw-bold">R$ <?= number_format($v['total'], 2, ',', '.') ?></td>
                                        <td class="text-center">
                                            <a href="cupom.php?venda_id=<?= $v['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-receipt me-1"></i> Ver Cupom</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">Ainda não há vendas registradas no sistema.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>
</html>