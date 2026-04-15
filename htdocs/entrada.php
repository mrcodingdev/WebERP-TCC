<?php
require_once 'db.php';
require_once 'auth.php';

// Restrição de Acesso
if ($_SESSION['user_perfil'] !== 'admin') {
    die("Acesso Negado. Apenas o administrador pode realizar entrada de estoque.");
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['produto_id'], $_POST['qtd_entrada'])) {
    $produto_id = (int)$_POST['produto_id'];
    $qtd_entrada = (int)$_POST['qtd_entrada'];
    
    if($produto_id > 0 && $qtd_entrada > 0) {
        $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade + ? WHERE id = ?");
        $stmt->execute([$qtd_entrada, $produto_id]);
        $msg = "Entrada processada com sucesso no estoque!";
    }
}

// Lista produtos ordenados alfabeticamente para o Select
$produtos = $pdo->query("SELECT id, nome, quantidade, categoria FROM produtos ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyStock ERP - Entrada de Estoque</title>
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
            <li><a href="entrada.php" class="active"><i class="fas fa-truck-loading"></i> Entrada de Estoque</a></li>
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
            <h1><i class="fas fa-truck-loading text-secondary me-2"></i> Recebimento / Entrada de Estoque</h1>
        </div>

        <div class="content-body">
            
            <?php if ($msg): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div>
            <?php endif; ?>

            <div class="card panel-border shadow-sm border-0 w-75 mx-auto mt-4">
                <div class="card-body p-4 bg-light">
                    <p class="text-muted mb-4"><i class="fas fa-info-circle"></i> Selecione o produto que chegou do fornecedor e informe a quantidade para abastecer o estoque automaticamente.</p>
                    
                    <form action="entrada.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-primary">Selecione o Produto</label>
                            <select name="produto_id" class="form-select form-select-lg" required>
                                <option value="">-- Buscar Produto --</option>
                                <?php foreach($produtos as $p): ?>
                                    <option value="<?= $p['id'] ?>">
                                        <?= htmlspecialchars($p['nome']) ?> (Estoque atual: <?= $p['quantidade'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-primary">Quantidade Recebida</label>
                            <div class="input-group input-group-lg w-50">
                                <span class="input-group-text bg-white"><i class="fas fa-plus"></i></span>
                                <input type="number" name="qtd_entrada" class="form-control" min="1" value="1" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100"><i class="fas fa-save me-2"></i> Salvar Entrada de Estoque</button>
                    </form>
                </div>
            </div>

        </div>
    </div>

</body>
</html>