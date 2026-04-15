<?php
require_once 'db.php';
require_once 'auth.php';

// Busca Cupons Fiscais Emulados
$sql = "SELECT cf.*, v.total, c.nome as cliente_nome 
        FROM cupons_fiscais cf
        JOIN vendas v ON cf.venda_id = v.id
        LEFT JOIN clientes c ON v.cliente_id = c.id
        ORDER BY cf.data_emissao DESC";
$stmt = $pdo->query($sql);
$cupons = $stmt->fetchAll();
$totalCupons = count($cupons);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockControl ERP - Painel Fiscal (NFC-e)</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .table-custom th { background-color: #2b3035; color: #fff; font-weight: 500; }
        .chave-acesso { 
            font-family: 'Courier New', Courier, monospace; 
            letter-spacing: 1px; 
            font-size: 13px;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar-wrapper">
        <div class="sidebar-brand">
            <i class="fas fa-layer-group"></i> StockControl
        </div>
        
        <div class="sidebar-user">
            <?php
                $userName    = htmlspecialchars($_SESSION['user_name']   ?? 'UsuÃ¡rio');
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
            <li><a href="fiscal.php" class="active"><i class="fas fa-receipt"></i> Fiscal (NFC-e)</a></li>
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
<div class="content-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold text-dark m-0"><i class="fas fa-receipt text-danger me-2"></i>Controle Fiscal Simulado</h2>
                <p class="text-muted m-0">Consulte aqui os XMLs e Cupons NFC-e gerados nas Vendas.</p>
            </div>
            <a href="venda.php" class="btn btn-danger fw-bold shadow-sm">
                <i class="fas fa-shopping-cart me-1"></i> Ir para o PDV
            </a>
        </div>

        <div class="content-body">

            <div class="alert alert-info border-0 shadow-sm mb-4">
                <i class="fas fa-info-circle me-2"></i> O ambiente fiscal opera em modo de <strong>Homologação (Simulação TCC)</strong>. As chaves de acesso apresentadas abaixo são geradas aleatoriamente e não possuem valor legal na SEFAZ.
            </div>

            <div class="card overflow-hidden shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="15%">Data da Emissão</th>
                                    <th width="10%" class="text-center">ID Venda</th>
                                    <th width="35%">Chave de Acesso (44 dígitos)</th>
                                    <th width="20%">Cliente</th>
                                    <th width="10%">Total Cupom</th>
                                    <th width="10%" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($totalCupons > 0): ?>
                                    <?php foreach($cupons as $cf): ?>
                                    <tr>
                                        <td><span class="text-muted"><i class="far fa-calendar-alt me-1"></i> <?= date('d/m/Y H:i', strtotime($cf['data_emissao'])) ?></span></td>
                                        <td class="text-center fw-bold text-secondary">#<?= str_pad($cf['venda_id'], 5, '0', STR_PAD_LEFT) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border chave-acesso p-2">
                                                <?= preg_replace('/(\d{4})/', '$1 ', $cf['chave_acesso']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($cf['cliente_nome'] ?? 'Consumidor Final') ?></td>
                                        <td class="fw-bold text-success">R$ <?= number_format($cf['total'], 2, ',', '.') ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-danger" title="Simular Impressão JSON/XML" onclick="alert('Emulação: O cupom fiscal impresso para a chave <?= $cf['chave_acesso'] ?> será gerado.')"><i class="fas fa-print"></i></button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-receipt fs-1 d-block mb-3 text-light"></i>Nenhum cupom fiscal emitido até o momento.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>