<?php
require_once 'db.php';
require_once 'auth.php';

// Busca Fornecedores com contagem de produtos atrelados
$sql = "SELECT f.*, 
               (SELECT COUNT(*) FROM produtos p WHERE p.fornecedor_id = f.id) as qtd_produtos,
               (SELECT SUM(quantidade) FROM produtos p WHERE p.fornecedor_id = f.id) as total_itens
        FROM fornecedores f 
        ORDER BY f.nome ASC";
$stmt = $pdo->query($sql);
$fornecedores = $stmt->fetchAll();
$totalFornecedores = count($fornecedores);

// Se houver edição
$editFornecedor = null;
if(isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmtEdit = $pdo->prepare("SELECT * FROM fornecedores WHERE id = ?");
    $stmtEdit->execute([$_GET['edit']]);
    $editFornecedor = $stmtEdit->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockControl ERP - Gestão de Fornecedores</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .table-custom th { background-color: #2b3035; color: #fff; font-weight: 500; }
        .action-btn { width: 32px; height: 32px; padding: 0; line-height: 32px; text-align: center; border-radius: 4px; }
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
            <li><a href="fornecedores.php" class="active"><i class="fas fa-truck"></i> Fornecedores</a></li>
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
<div class="content-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold text-dark m-0"><i class="fas fa-truck text-warning me-2"></i>Gestão de Fornecedores</h2>
                <p class="text-muted m-0">Administre os fornecedores e veja rapidamente os produtos atrelados.</p>
            </div>
            <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalFornecedor" onclick="clearForm()">
                <i class="fas fa-plus-circle me-1"></i> Adicionar Fornecedor
            </button>
        </div>

        <div class="content-body">
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'sucesso'): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                <strong>Sucesso!</strong> Fornecedor salvo corretamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php elseif(isset($_GET['msg']) && $_GET['msg'] == 'inativado'): ?>
            <div class="alert alert-warning alert-dismissible fade show shadow-sm border-0" role="alert">
                <strong>Atenção!</strong> O fornecedor não pôde ser excluído pois possui Produtos atrelados no estoque. O status foi alterado para <strong>Inativo</strong>.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="card overflow-hidden shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-custom mb-0">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center">ID</th>
                                    <th width="30%">Razão Social / Nome</th>
                                    <th width="20%">Contato / CNPJ</th>
                                    <th width="12%" class="text-center">Qtd Itens</th>
                                    <th width="10%" class="text-center">Volume Fornecido</th>
                                    <th width="10%" class="text-center">Status</th>
                                    <th width="13%" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($totalFornecedores > 0): ?>
                                    <?php foreach($fornecedores as $f): 
                                        $badgeStatus = ($f['status'] == 'ativo') ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>';
                                    ?>
                                    <tr>
                                        <td class="text-center align-middle"><?= str_pad($f['id'], 3, '0', STR_PAD_LEFT) ?></td>
                                        <td class="align-middle"><strong><?= htmlspecialchars($f['nome']) ?></strong><br><small class="text-muted"><i class="far fa-envelope"></i> <?= htmlspecialchars($f['email'] ?: '--') ?></small></td>
                                        <td class="align-middle">
                                            <i class="fas fa-phone-alt text-muted" style="font-size:12px;"></i> <?= htmlspecialchars($f['telefone'] ?: '--') ?><br>
                                            <small class="text-muted fw-bold">CNPJ:</small> <small><?= htmlspecialchars($f['cnpj'] ?: '--') ?></small>
                                        </td>
                                        <td class="text-center align-middle"><span class="badge bg-light text-dark border"><?= $f['qtd_produtos'] ?> produtos</span></td>
                                        <td class="text-center align-middle"><span class="fw-bold text-primary"><?= $f['total_itens'] ?: 0 ?> unids</span></td>
                                        <td class="text-center align-middle"><?= $badgeStatus ?></td>
                                        <td class="text-center align-middle">
                                            <a href="fornecedores.php?edit=<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary action-btn"><i class="fas fa-edit"></i></a>
                                            <form action="fornecedores_action.php" method="POST" class="d-inline">
                                                <input type="hidden" name="acao" value="deletar">
                                                <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger action-btn" onclick="return confirm('Excluir este fornecedor? Ele será inativado se houver produtos no estoque.')"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-truck-loading fs-1 d-block mb-3 text-light"></i>Ainda não há fornecedores.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Fornecedor -->
    <div class="modal fade" id="modalFornecedor" tabindex="-1" aria-labelledby="modalFornecedorLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title fw-bold" id="modalFornecedorLabel"><i class="fas fa-truck text-warning me-2"></i> <?= $editFornecedor ? 'Editar Fornecedor' : 'Novo Fornecedor' ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="window.location='fornecedores.php'"></button>
                </div>
                <form action="fornecedores_action.php" method="POST">
                    <div class="modal-body bg-light">
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="id" id=" forn_id" value="<?= $editFornecedor ? $editFornecedor['id'] : '' ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Razão Social / Nome <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nome" id="forn_nome" value="<?= $editFornecedor ? htmlspecialchars($editFornecedor['nome']) : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">CNPJ (Opcional)</label>
                            <input type="text" class="form-control" name="cnpj" id="forn_cnpj" placeholder="00.000.000/0001-00" value="<?= $editFornecedor ? htmlspecialchars($editFornecedor['cnpj']) : '' ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Telefone Principal</label>
                                <input type="text" class="form-control" name="telefone" id="forn_telefone" placeholder="(00) 0000-0000" value="<?= $editFornecedor ? htmlspecialchars($editFornecedor['telefone']) : '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Status da Conta</label>
                                <select class="form-select" name="status" id="forn_status">
                                    <option value="ativo" <?= ($editFornecedor && $editFornecedor['status'] == 'ativo') ? 'selected' : '' ?>>Ativo (Fornecendo)</option>
                                    <option value="inativo" <?= ($editFornecedor && $editFornecedor['status'] == 'inativo') ? 'selected' : '' ?>>Inativo</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">E-mail de Contato</label>
                            <input type="email" class="form-control" name="email" id="forn_email" placeholder="vendas@fornecedor.com" value="<?= $editFornecedor ? htmlspecialchars($editFornecedor['email']) : '' ?>">
                        </div>
                        
                    </div>
                    <div class="modal-footer border-0 bg-white">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="window.location='fornecedores.php'">Cancelar</button>
                        <button type="submit" class="btn btn-warning fw-bold text-dark"><i class="fas fa-save me-1"></i> <?= $editFornecedor ? 'Atualizar Dados' : 'Cadastrar Fornecedor' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function clearForm() {
            document.getElementById('forn_id').value = '';
            document.getElementById('forn_nome').value = '';
            document.getElementById('forn_cnpj').value = '';
            document.getElementById('forn_telefone').value = '';
            document.getElementById('forn_email').value = '';
            document.getElementById('forn_status').value = 'ativo';
            document.getElementById('modalFornecedorLabel').innerHTML = '<i class="fas fa-truck text-warning me-2"></i> Novo Fornecedor';
        }

        <?php if($editFornecedor): ?>
            window.onload = () => {
                var modal = new bootstrap.Modal(document.getElementById('modalFornecedor'));
                modal.show();
            };
        <?php endif; ?>
    </script>
</body>
</html>