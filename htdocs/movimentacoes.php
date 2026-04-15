<?php
require_once 'db.php';
require_once 'auth.php';

// Busca Histórico de Movimentações
$sql = "SELECT m.*, p.nome as produto_nome 
        FROM movimentacoes m 
        JOIN produtos p ON m.produto_id = p.id 
        ORDER BY m.data_movimento DESC LIMIT 300";
$stmt = $pdo->query($sql);
$movimentacoes = $stmt->fetchAll();

// Busca Produtos para o Modal
$stmtProd = $pdo->query("SELECT id, nome, quantidade FROM produtos ORDER BY nome ASC");
$produtosLista = $stmtProd->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockControl ERP - Movimentações de Estoque</title>
    <!-- Bootstrap 5 CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="assets/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    <style>
        .table-custom th {
            background-color: #2b3035;
            color: #fff;
            font-weight: 500;
            border-bottom: 0;
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
            <li><a href="movimentacoes.php" class="active"><i class="fas fa-exchange-alt"></i> Movimentações</a></li>
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
            <div>
                <h2 class="fw-bold text-dark m-0"><i class="fas fa-history text-primary me-2"></i>Histórico de Estoque</h2>
                <p class="text-muted m-0">Consulte todas as Entradas, Saídas, Perdas e Devoluções.</p>
            </div>
            <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalMovimentacao">
                <i class="fas fa-plus-circle me-1"></i> Nova Movimentação
            </button>
        </div>

        <!-- Main Body -->
        <div class="content-body">
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'sucesso'): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                <strong>Sucesso!</strong> Movimentação registrada e estoque atualizado.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th width="15%">Data e Hora</th>
                                    <th width="15%">Tipo</th>
                                    <th width="30%">Produto</th>
                                    <th width="10%" class="text-center">Qtd</th>
                                    <th width="30%">Observação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($movimentacoes) > 0): ?>
                                    <?php foreach($movimentacoes as $m): 
                                        
                                        // Definindo visual por Tipo
                                        $icone = '';
                                        $cor = '';
                                        $label = '';
                                        
                                        switch($m['tipo']) {
                                            case 'entrada_compra':
                                                $icone = '<i class="fas fa-arrow-down"></i>';
                                                $cor = 'success';
                                                $label = 'Entrada (Compra)';
                                                break;
                                            case 'saida_venda':
                                                $icone = '<i class="fas fa-arrow-up"></i>';
                                                $cor = 'primary';
                                                $label = 'Saída (Venda)';
                                                break;
                                            case 'devolucao_cliente':
                                                $icone = '<i class="fas fa-undo"></i>';
                                                $cor = 'info';
                                                $label = 'Devolução Cliente';
                                                break;
                                            case 'devolucao_fornecedor':
                                                $icone = '<i class="fas fa-reply"></i>';
                                                $cor = 'warning text-dark';
                                                $label = 'Devolvido ao Fornec.';
                                                break;
                                            case 'perda':
                                                $icone = '<i class="fas fa-times-circle"></i>';
                                                $cor = 'danger';
                                                $label = 'Perda / Avaria';
                                                break;
                                        }
                                    ?>
                                    <tr>
                                        <td><span class="text-muted"><i class="far fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($m['data_movimento'])) ?></span></td>
                                        <td>
                                            <span class="badge bg-<?= $cor ?> px-2 py-1" style="font-size: 12px; border-radius: 4px;">
                                                <?= $icone ?> <?= $label ?>
                                            </span>
                                        </td>
                                        <td><strong><?= htmlspecialchars($m['produto_nome']) ?></strong></td>
                                        <td class="text-center">
                                            <?php if(in_array($m['tipo'], ['entrada_compra', 'devolucao_cliente'])): ?>
                                                <span class="text-success fw-bold">+ <?= $m['quantidade'] ?></span>
                                            <?php else: ?>
                                                <span class="text-danger fw-bold">- <?= $m['quantidade'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="text-muted" style="font-size: 13px;"><?= htmlspecialchars($m['observacao'] ?: '--') ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-clipboard-list fs-1 d-block mb-3 text-light"></i>Sem histórico de movimentações.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Nova Movimentação -->
    <div class="modal fade" id="modalMovimentacao" tabindex="-1" aria-labelledby="modalMovimentacaoLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title fw-bold" id="modalMovimentacaoLabel"><i class="fas fa-exchange-alt me-2"></i> Registrar Movimentação</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="movimentacoes_action.php" method="POST">
                    <div class="modal-body bg-light">
                        <input type="hidden" name="acao" value="registrar">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo da Movimentação <span class="text-danger">*</span></label>
                            <select class="form-select bg-white border-secondary" name="tipo" required>
                                <option value="" disabled selected>Escolha o tipo...</option>
                                <optgroup label="Entradas (Soma no Estoque)">
                                    <option value="entrada_compra">Entrada de Compra (Fornecedor)</option>
                                    <option value="devolucao_cliente">Devolução de Cliente</option>
                                </optgroup>
                                <optgroup label="Saídas (Subtrai do Estoque)">
                                    <option value="saida_venda">Saída Avulsa (Ajuste/Venda Off)</option>
                                    <option value="devolucao_fornecedor">Devolução para Fornecedor</option>
                                    <option value="perda">Perda / Avaria</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Produto <span class="text-danger">*</span></label>
                            <select class="form-select bg-white" name="produto_id" required>
                                <option value="" disabled selected>Selecione um produto...</option>
                                <?php foreach($produtosLista as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?> (Estoque atual: <?= $p['quantidade'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Quantidade Relativa <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="quantidade" min="1" required placeholder="Ex: 5">
                            <small class="text-muted">Informe apenas números positivos. O sistema calculará + ou - de acordo com o Tipo.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Observação / Motivo / NFe</label>
                            <textarea class="form-control" name="observacao" rows="2" placeholder="Ex: Referente à Nota Fiscal Nº 12345..."></textarea>
                        </div>

                    </div>
                    <div class="modal-footer border-0 bg-white">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-check-circle me-1"></i> Confirmar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>