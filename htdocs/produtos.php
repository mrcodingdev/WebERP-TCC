<?php
require_once 'db.php';
require_once 'auth.php';

// Busca todos os produtos e nome do fornecedor
$stmt = $pdo->query("SELECT p.*, f.nome as fornecedor_nome FROM produtos p LEFT JOIN fornecedores f ON p.fornecedor_id = f.id ORDER BY p.id DESC");
$produtos = $stmt->fetchAll();
$totalProdutos = count($produtos);

// Busca fornecedores ativos para o Modal
$stmtForn = $pdo->query("SELECT id, nome FROM fornecedores WHERE status = 'ativo' ORDER BY nome ASC");
$fornecedoresLista = $stmtForn->fetchAll();

// Se houver um ID na URL para edição
$editProduto = null;
if(isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmtEdit = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmtEdit->execute([$_GET['edit']]);
    $editProduto = $stmtEdit->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockControl ERP - Produtos & Estoque Minímo</title>
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
        }
        .table-custom td {
            vertical-align: middle;
        }
        .action-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            line-height: 32px;
            text-align: center;
            border-radius: 4px;
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
            <li><a href="produtos.php" class="active"><i class="fas fa-box"></i> Estoque Mín. & Produtos</a></li>
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
                <h2 class="fw-bold text-dark m-0"><i class="fas fa-box text-secondary me-2"></i>Gestão de Produtos</h2>
                <p class="text-muted m-0">Cadastre itens, defina o estoque mínimo e preços de custo/venda.</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProduto" onclick="clearForm()">
                <i class="fas fa-plus-circle me-1"></i> Adicionar Produto
            </button>
        </div>

        <!-- Main Body -->
        <div class="content-body">
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'sucesso'): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                <strong>Sucesso!</strong> Registro salvo no banco de dados.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="card overflow-hidden shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-custom mb-0">
                            <thead>
                                <tr>
                                    <th width="5%">Cód.</th>
                                    <th width="25%">Nome do Produto</th>
                                    <th width="20%">Fornecedor</th>
                                    <th width="12%" class="text-center">Estoque</th>
                                    <th width="15%">Validade</th>
                                    <th width="13%">Preço Venda</th>
                                    <th width="10%" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($totalProdutos > 0): ?>
                                    <?php foreach($produtos as $p): 
                                        $badgeQtdClass = ($p['quantidade'] <= $p['estoque_minimo']) ? 'bg-danger shadow-sm' : 'bg-success shadow-sm';
                                        $badgeTextQtd = ($p['quantidade'] <= $p['estoque_minimo']) ? '<br><small class="text-danger fw-bold"><i class="fas fa-level-down-alt me-1"></i>Baixo</small>' : '<br><small class="text-success"><i class="fas fa-check-circle me-1"></i>Ideal</small>';
                                        
                                        $validadeHtml = '<span class="text-secondary"><i class="fas fa-minus"></i> N/A</span>';
                                        if($p['validade']) {
                                            $dataValidade = new DateTime($p['validade']);
                                            $hoje = new DateTime();
                                            $dias = $hoje->diff($dataValidade)->format('%R%a');
                                            
                                            if($dias < 0) {
                                                $validadeHtml = '<span class="badge bg-dark"><i class="fas fa-times-circle"></i> Vencido</span><br><small class="text-muted">'.date('d/m/Y', strtotime($p['validade'])).'</small>';
                                            } elseif ($dias <= 15) {
                                                $validadeHtml = '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Faltam '.$dias.' dias</span><br><small class="text-muted">'.date('d/m/Y', strtotime($p['validade'])).'</small>';
                                            } elseif ($dias <= 30) {
                                                $validadeHtml = '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> Faltam '.$dias.' dias</span><br><small class="text-muted">'.date('d/m/Y', strtotime($p['validade'])).'</small>';
                                            } else {
                                                $validadeHtml = '<span class="text-secondary fw-bold"><i class="fas fa-calendar-check text-success"></i> '.date('d/m/Y', strtotime($p['validade'])).'</span>';
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td class="align-middle"><?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                        <td class="align-middle"><strong><?= htmlspecialchars($p['nome']) ?></strong><br><small class="text-muted text-uppercase" style="font-size:10px;"><?= htmlspecialchars($p['categoria']) ?></small></td>
                                        <td class="align-middle"><i class="fas fa-truck text-muted me-1"></i> <?= htmlspecialchars($p['fornecedor_nome'] ?? 'Sem Vínculo') ?></td>
                                        <td class="text-center align-middle"><span class="badge <?= $badgeQtdClass ?>" style="font-size: 13px;"><?= $p['quantidade'] ?></span><?= $badgeTextQtd ?></td>
                                        <td class="align-middle"><?= $validadeHtml ?></td>
                                        <td class="fw-bold text-success align-middle">R$ <?= number_format($p['preco_venda'], 2, ',', '.') ?></td>
                                        <td class="text-center align-middle">
                                            <a href="produtos.php?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary action-btn"><i class="fas fa-edit"></i></a>
                                            <form action="produtos_action.php" method="POST" class="d-inline">
                                                <input type="hidden" name="acao" value="deletar">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger action-btn" onclick="return confirm('Tem certeza que deseja excluir? Isso excluirá o histórico atrelado!')"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-box-open fs-1 d-block mb-3 text-light"></i>Nenhum produto cadastrado no sistema.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light border-0 text-muted text-center" style="font-size: 13px;">
                    Mostrando <?= $totalProdutos ?> produtos no total.
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Produto -->
    <div class="modal fade" id="modalProduto" tabindex="-1" aria-labelledby="modalProdutoLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title fw-bold" id="modalProdutoLabel"><i class="fas fa-box-open me-2 text-warning"></i> <?= $editProduto ? 'Editar Produto' : 'Cadastrar Produto' ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="window.location='produtos.php'"></button>
                </div>
                <form action="produtos_action.php" method="POST">
                    <div class="modal-body bg-light">
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="id" id="prod_id" value="<?= $editProduto ? $editProduto['id'] : '' ?>">
                        
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-info-circle"></i> Informações Básicas</h6>
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label form-label-sm fw-bold">Nome do Produto <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control bg-light" name="nome" id="prod_nome" value="<?= $editProduto ? htmlspecialchars($editProduto['nome']) : '' ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label form-label-sm fw-bold">Categoria</label>
                                        <input type="text" class="form-control bg-light" name="categoria" id="prod_categoria" value="<?= $editProduto ? htmlspecialchars($editProduto['categoria']) : 'Geral' ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label form-label-sm fw-bold"><i class="fas fa-truck text-muted"></i> Fornecedor Responsável</label>
                                        <select class="form-select bg-light" name="fornecedor_id" id="prod_fornecedor_id">
                                            <option value="">Nenhum / Sem Vínculo</option>
                                            <?php foreach($fornecedoresLista as $forn): ?>
                                                <option value="<?= $forn['id'] ?>" <?= ($editProduto && $editProduto['fornecedor_id'] == $forn['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($forn['nome']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label form-label-sm fw-bold text-success"><i class="fas fa-calendar-alt"></i> Data de Validade</label>
                                        <input type="date" class="form-control bg-light" name="validade" id="prod_validade" value="<?= $editProduto ? $editProduto['validade'] : '' ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="text-danger fw-bold mb-3"><i class="fas fa-tags"></i> Preços e Controle de Estoque</h6>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label form-label-sm fw-bold text-muted">Custo (R$)</label>
                                        <input type="number" step="0.01" class="form-control" name="preco_compra" id="prod_preco_compra" value="<?= $editProduto ? $editProduto['preco_compra'] : '0.00' ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label form-label-sm fw-bold text-success">Venda (R$) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" class="form-control shadow-sm" name="preco_venda" id="prod_preco_venda" value="<?= $editProduto ? $editProduto['preco_venda'] : '0.00' ?>" required style="border-left: 3px solid #198754;">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label form-label-sm fw-bold">Estoque Atual</label>
                                        <input type="number" class="form-control" name="quantidade" id="prod_quantidade" value="<?= $editProduto ? $editProduto['quantidade'] : '0' ?>" <?= $editProduto ? 'title="Atenção: A via recomendada é por Movimentações."' : '' ?>>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label form-label-sm fw-bold text-warning border-bottom border-warning pb-1">Estoque Mínimo</label>
                                        <input type="number" class="form-control shadow-sm" name="estoque_minimo" id="prod_minimo" value="<?= $editProduto ? $editProduto['estoque_minimo'] : '5' ?>" required style="border-left: 3px solid #ffc107;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-white">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" onclick="window.location='produtos.php'">Cancelar</button>
                        <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm"><i class="fas fa-check-circle me-1"></i> <?= $editProduto ? 'Atualizar Produto' : 'Cadastrar Produto' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function clearForm() {
            document.getElementById('prod_id').value = '';
            document.getElementById('prod_nome').value = '';
            document.getElementById('prod_categoria').value = 'Geral';
            document.getElementById('prod_fornecedor_id').value = '';
            document.getElementById('prod_validade').value = '';
            document.getElementById('prod_preco_compra').value = '0.00';
            document.getElementById('prod_preco_venda').value = '0.00';
            document.getElementById('prod_quantidade').value = '0';
            document.getElementById('prod_minimo').value = '5';
            document.getElementById('modalProdutoLabel').innerHTML = '<i class="fas fa-box-open me-2 text-warning"></i> Cadastrar Produto';
        }

        <?php if($editProduto): ?>
            window.onload = () => {
                var modal = new bootstrap.Modal(document.getElementById('modalProduto'));
                modal.show();
            };
        <?php endif; ?>
    </script>
</body>
</html>