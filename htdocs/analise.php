<?php
require_once 'db.php';
require_once 'auth.php';

// --- KPIs Básicos ---
$faturamentoHoje = $pdo->query("SELECT SUM(total) FROM vendas WHERE DATE(data_venda) = CURDATE()")->fetchColumn() ?: 0;
$faturamentoMes = $pdo->query("SELECT SUM(total) FROM vendas WHERE MONTH(data_venda) = MONTH(CURDATE()) AND YEAR(data_venda) = YEAR(CURDATE())")->fetchColumn() ?: 0;
$patrimonioEstoque = $pdo->query("SELECT SUM(quantidade * preco_venda) FROM produtos")->fetchColumn() ?: 0;
$lucroEstoque = $pdo->query("SELECT SUM(quantidade * (preco_venda - preco_compra)) FROM produtos")->fetchColumn() ?: 0;

// --- 1. Top 5 Produtos Mais Vendidos ---
$stmtTop = $pdo->query("SELECT p.nome, SUM(vi.quantidade) as total_vendido 
                        FROM vendas_itens vi 
                        JOIN produtos p ON p.id = vi.produto_id 
                        GROUP BY p.id 
                        ORDER BY total_vendido DESC LIMIT 5");
$topProdutos = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

$labelsTop = [];
$dataTop = [];
foreach($topProdutos as $tp) {
    $labelsTop[] = substr($tp['nome'], 0, 15) . '...';
    $dataTop[] = $tp['total_vendido'];
}

// --- 2. Vendas dos últimos 7 dias (Diário) ---
$stmtSemana = $pdo->query("SELECT DATE_FORMAT(data_venda, '%d/%m') as dia, SUM(total) as faturamento 
                           FROM vendas 
                           WHERE data_venda >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
                           GROUP BY DATE(data_venda) 
                           ORDER BY DATE(data_venda) ASC");
$vendasSemana = $stmtSemana->fetchAll(PDO::FETCH_ASSOC);

// Preencher dias vazios com 0 se quiser perfeição, mas vamos usar os dados puros para o TCC
$labelsSemana = [];
$dataSemana = [];
foreach($vendasSemana as $vs) {
    $labelsSemana[] = $vs['dia'];
    $dataSemana[] = $vs['faturamento'];
}

// --- 3. Vendas dos últimos 6 meses (Mensal) ---
$stmtMes = $pdo->query("SELECT DATE_FORMAT(data_venda, '%m/%Y') as mes, SUM(total) as faturamento 
                        FROM vendas 
                        WHERE data_venda >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
                        GROUP BY DATE_FORMAT(data_venda, '%Y-%m') 
                        ORDER BY MIN(data_venda) ASC");
$vendasMes = $stmtMes->fetchAll(PDO::FETCH_ASSOC);

$labelsMes = [];
$dataMes = [];
foreach($vendasMes as $vm) {
    $labelsMes[] = $vm['mes'];
    $dataMes[] = $vm['faturamento'];
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockControl ERP - Análises e Dashboards</title>
    <!-- Bootstrap 5 CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="assets/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    <!-- Chart.js -->
    <script src="assets/js/chart.min.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
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
            <li><a href="fiscal.php"><i class="fas fa-receipt"></i> Fiscal (NFC-e)</a></li>
            <li><a href="analise.php" class="active"><i class="fas fa-chart-pie"></i> Análise & Dashboards</a></li>
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
                <h2 class="fw-bold text-dark m-0"><i class="fas fa-chart-bar text-primary me-2"></i>Centro de Inteligência</h2>
                <p class="text-muted m-0">Gráficos de vendas, margens e top produtos.</p>
            </div>
            <button class="btn btn-outline-secondary shadow-sm" onclick="window.print()"><i class="fas fa-print me-1"></i> Imprimir Painel</button>
        </div>

        <div class="content-body">
            
            <!-- KPIs -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-white-50 fw-bold">Faturamento de Hoje</h6>
                            <h3 class="fw-bold mb-0">R$ <?= number_format($faturamentoHoje, 2, ',', '.') ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-white-50 fw-bold">Faturamento do Mês</h6>
                            <h3 class="fw-bold mb-0">R$ <?= number_format($faturamentoMes, 2, ',', '.') ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-dark-50 fw-bold opacity-75">Patrimônio em Estoque</h6>
                            <h3 class="fw-bold mb-0 text-dark">R$ <?= number_format($patrimonioEstoque, 2, ',', '.') ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-dark text-white border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-white-50 fw-bold">Lucro Líquido Previsto</h6>
                            <h3 class="fw-bold mb-0 text-white">R$ <?= number_format($lucroEstoque, 2, ',', '.') ?></h3>
                            <small class="text-success"><i class="fas fa-arrow-up"></i> Margem Comercial</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos Row 1 -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                            <h5 class="fw-bold text-dark"><i class="fas fa-chart-area text-info me-1"></i> Faturamento dos Últimos 7 Dias</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartSemana"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                            <h5 class="fw-bold text-dark"><i class="fas fa-trophy text-warning me-1"></i> Top 5 Produtos Base</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="chartTopProdutos"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos Row 2 -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                            <h5 class="fw-bold text-dark"><i class="fas fa-chart-line text-success me-1"></i> Crescimento Mensal (Últimos 6 Meses)</h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 350px; width: 100%;">
                                <canvas id="chartMes"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Dados injetados pelo PHP
        const labelsSemana = <?= json_encode($labelsSemana) ?>;
        const dataSemana = <?= json_encode($dataSemana) ?>;

        const labelsTop = <?= json_encode($labelsTop) ?>;
        const dataTop = <?= json_encode($dataTop) ?>;

        const labelsMes = <?= json_encode($labelsMes) ?>;
        const dataMes = <?= json_encode($dataMes) ?>;

        // Tema Global
        Chart.defaults.font.family = "'Segoe UI', 'Roboto', sans-serif";
        Chart.defaults.color = "#6c757d";

        // Gráfico 7 Dias (Barra/Linha)
        new Chart(document.getElementById('chartSemana'), {
            type: 'bar',
            data: {
                labels: labelsSemana,
                datasets: [{
                    label: 'Faturamento Diário R$',
                    data: dataSemana,
                    backgroundColor: 'rgba(13, 110, 253, 0.7)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Gráfico Top Produtos (Doughnut)
        new Chart(document.getElementById('chartTopProdutos'), {
            type: 'doughnut',
            data: {
                labels: labelsTop,
                datasets: [{
                    data: dataTop,
                    backgroundColor: [
                        '#ffc107', '#198754', '#0d6efd', '#dc3545', '#0dcaf0'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } }
                },
                cutout: '65%'
            }
        });

        // Gráfico Mensal (Linha)
        new Chart(document.getElementById('chartMes'), {
            type: 'line',
            data: {
                labels: labelsMes,
                datasets: [{
                    label: 'Evolução Mensal (R$)',
                    data: dataMes,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#198754',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>