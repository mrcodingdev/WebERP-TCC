<?php require_once 'db.php'; require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyStock ERP - Relatórios</title>
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
            <i class="fas fa-cubes"></i> EasyStock
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
            <li><a href="index.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="relatorios.php" class="active"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
            <li><a href="#"><i class="fas fa-users"></i> Clientes</a></li>
            <li><a href="produtos.php"><i class="fas fa-box"></i> Produtos</a></li>
            <li><a href="#"><i class="fas fa-cubes"></i> Estoque <i class="fas fa-angle-left float-end mt-1"></i></a></li>
            <li><a href="#"><i class="fas fa-users-cog"></i> Fornecedores <i class="fas fa-angle-left float-end mt-1"></i></a></li>
            <li><a href="venda.php"><i class="fas fa-shopping-cart"></i> Vendas (PDV) <i class="fas fa-angle-left float-end mt-1"></i></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-panel">
        <!-- Topbar -->
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

        <!-- Content Header -->
        <div class="content-header d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-file-pdf text-secondary me-2"></i> Emissão de Relatórios</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-secondary"><i class="fas fa-home"></i> Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Relatórios</li>
                </ol>
            </nav>
        </div>

        <!-- Main Body -->
        <div class="content-body">
            
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card panel-border shadow-sm border-0 mt-3">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-3 text-center">
                            <h4 class="card-title fw-bold text-primary"><i class="fas fa-cogs me-2"></i> Gerador de Relatórios</h4>
                            <p class="text-muted mb-0">Selecione o tipo de relatório que deseja exportar para o seu TCC</p>
                        </div>
                        <div class="card-body p-4 bg-light">
                            <!-- Forms de Geração -->
                            <div class="mb-4 bg-white p-3 rounded shadow-sm border">
                                <h5 class="text-success"><i class="fas fa-boxes me-2"></i> Inventário Completo</h5>
                                <p class="text-muted" style="font-size:14px;">Lista detalhada com todos os produtos, quantidades, preços e fornecedores.</p>
                                <div class="d-flex gap-2 mt-3">
                                    <a href="relatorio_pdf.php?tipo=completo" target="_blank" class="btn btn-outline-danger"><i class="fas fa-file-pdf me-1"></i> Gerar PDF</a>
                                    <a href="exportar_excel.php?tipo=completo" class="btn btn-outline-success"><i class="fas fa-file-excel me-1"></i>  Baixar Excel</a>
                                </div>
                            </div>
                            
                            <div class="mb-4 bg-white p-3 rounded shadow-sm border">
                                <h5 class="text-warning text-dark"><i class="fas fa-exclamation-circle me-2"></i> Alerta de Estoque Baixo</h5>
                                <p class="text-muted" style="font-size:14px;">Apenas produtos que estão com a quantidade igual ou inferior ao estoque mínimo configurado.</p>
                                <div class="d-flex gap-2 mt-3">
                                    <a href="relatorio_pdf.php?tipo=baixo" target="_blank" class="btn btn-outline-danger"><i class="fas fa-file-pdf me-1"></i> Gerar PDF</a>
                                    <a href="exportar_excel.php?tipo=baixo" class="btn btn-outline-success"><i class="fas fa-file-excel me-1"></i>  Baixar Excel</a>
                                </div>
                            </div>

                            <div class="mb-2 bg-white p-3 rounded shadow-sm border">
                                <h5 class="text-danger"><i class="fas fa-calendar-times me-2"></i> Validades e Vencimentos</h5>
                                <p class="text-muted" style="font-size:14px;">Produtos que já estão vencidos ou que irão vencer nos próximos 30 dias.</p>
                                <div class="d-flex gap-2 mt-3">
                                    <a href="relatorio_pdf.php?tipo=validade" target="_blank" class="btn btn-outline-danger"><i class="fas fa-file-pdf me-1"></i> Gerar PDF</a>
                                    <a href="exportar_excel.php?tipo=validade" class="btn btn-outline-success"><i class="fas fa-file-excel me-1"></i>  Baixar Excel</a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>