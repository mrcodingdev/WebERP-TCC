<?php
require_once 'db.php';
require_once 'auth.php';

// Busca Clientes com histórico de compras resumido
$sql = "SELECT c.*, 
               (SELECT COUNT(*) FROM vendas v WHERE v.cliente_id = c.id) as qtd_compras,
               (SELECT SUM(total) FROM vendas v WHERE v.cliente_id = c.id) as total_compras
        FROM clientes c 
        ORDER BY c.nome ASC";
$stmt = $pdo->query($sql);
$clientes = $stmt->fetchAll();
$totalClientes = count($clientes);

// Se houver edição
$editCliente = null;
if(isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmtEdit = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmtEdit->execute([$_GET['edit']]);
    $editCliente = $stmtEdit->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockControl ERP - Gestão de Clientes</title>
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
            <li><a href="clientes.php" class="active"><i class="fas fa-users"></i> Clientes</a></li>
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
<div class="content-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold text-dark m-0"><i class="fas fa-users text-success me-2"></i>Gestão de Clientes</h2>
                <p class="text-muted m-0">Cadastre clientes, visualize os gastos e inicie contatos rapidamente.</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCliente" onclick="clearForm()">
                <i class="fas fa-user-plus me-1"></i> Adicionar Cliente
            </button>
        </div>

        <div class="content-body">
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'sucesso'): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                <strong>Sucesso!</strong> Registro do cliente salvo corretamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php elseif(isset($_GET['msg']) && $_GET['msg'] == 'inativado'): ?>
            <div class="alert alert-warning alert-dismissible fade show shadow-sm border-0" role="alert">
                <strong>Atenção!</strong> O cliente não pôde ser excluído pois possui vendas atreladas. Ele foi alterado para o status <strong>Inativo</strong>.
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
                                    <th width="25%">Nome do Cliente</th>
                                    <th width="20%">Contato</th>
                                    <th width="12%" class="text-center">Ticket Tls</th>
                                    <th width="15%" class="text-center">Total Gasto</th>
                                    <th width="10%" class="text-center">Status</th>
                                    <th width="13%" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($totalClientes > 0): ?>
                                    <?php foreach($clientes as $c): 
                                        $whatsLink = '';
                                        if($c['telefone']) {
                                            $soNumeros = preg_replace('/[^0-9]/', '', $c['telefone']);
                                            if(strlen($soNumeros) >= 10) {
                                                $whatsLink = "<a href='https://wa.me/55{$soNumeros}' target='_blank' class='btn btn-sm btn-success rounded-circle ms-2 py-0 px-1' title='Chamar no WhatsApp' style='font-size:11px;'><i class='fab fa-whatsapp'></i></a>";
                                            }
                                        }
                                        
                                        $badgeStatus = ($c['status'] == 'ativo') ? '<span class="badge bg-primary">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>';
                                    ?>
                                    <tr>
                                        <td class="text-center align-middle"><?= str_pad($c['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                        <td class="align-middle"><strong><?= htmlspecialchars($c['nome']) ?></strong><br><small class="text-muted"><i class="far fa-envelope"></i> <?= htmlspecialchars($c['email'] ?: 'Sem e-mail') ?></small></td>
                                        <td class="align-middle"><i class="fas fa-phone-alt text-muted" style="font-size:12px;"></i> <?= htmlspecialchars($c['telefone'] ?: 'Não informado') ?> <?= $whatsLink ?></td>
                                        <td class="text-center align-middle"><span class="badge bg-light text-dark border"><?= $c['qtd_compras'] ?> compras</span></td>
                                        <td class="text-center fw-bold text-success align-middle">R$ <?= number_format($c['total_compras'] ?? 0, 2, ',', '.') ?></td>
                                        <td class="text-center align-middle"><?= $badgeStatus ?></td>
                                        <td class="text-center align-middle">
                                            <a href="clientes.php?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary action-btn"><i class="fas fa-edit"></i></a>
                                            <form action="clientes_action.php" method="POST" class="d-inline">
                                                <input type="hidden" name="acao" value="deletar">
                                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger action-btn" onclick="return confirm('Tem certeza que deseja excluir? Se houver compras vinculadas, ele apenas será inativado.')"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-users-slash fs-1 d-block mb-3 text-light"></i>Ainda não há clientes cadastrados.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Cliente -->
    <div class="modal fade" id="modalCliente" tabindex="-1" aria-labelledby="modalClienteLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title fw-bold" id="modalClienteLabel"><i class="fas fa-user-circle text-success me-2"></i> <?= $editCliente ? 'Editar Cliente' : 'Novo Cliente' ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="window.location='clientes.php'"></button>
                </div>
                <form action="clientes_action.php" method="POST">
                    <div class="modal-body bg-light">
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="id" id=" cli_id" value="<?= $editCliente ? $editCliente['id'] : '' ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nome Completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nome" id="cli_nome" value="<?= $editCliente ? htmlspecialchars($editCliente['nome']) : '' ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Telefone / WhatsApp</label>
                                <input type="text" class="form-control" name="telefone" id="cli_telefone" placeholder="(11) 99999-9999" value="<?= $editCliente ? htmlspecialchars($editCliente['telefone']) : '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Status da Conta</label>
                                <select class="form-select" name="status" id="cli_status">
                                    <option value="ativo" <?= ($editCliente && $editCliente['status'] == 'ativo') ? 'selected' : '' ?>>Ativo</option>
                                    <option value="inativo" <?= ($editCliente && $editCliente['status'] == 'inativo') ? 'selected' : '' ?>>Inativo</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">E-mail</label>
                            <input type="email" class="form-control" name="email" id="cli_email" placeholder="cliente@email.com" value="<?= $editCliente ? htmlspecialchars($editCliente['email']) : '' ?>">
                        </div>
                        
                    </div>
                    <div class="modal-footer border-0 bg-white">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="window.location='clientes.php'">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold"><i class="fas fa-save me-1"></i> <?= $editCliente ? 'Salvar Alterações' : 'Cadastrar Cliente' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function clearForm() {
            document.getElementById('cli_id').value = '';
            document.getElementById('cli_nome').value = '';
            document.getElementById('cli_telefone').value = '';
            document.getElementById('cli_email').value = '';
            document.getElementById('cli_status').value = 'ativo';
            document.getElementById('modalClienteLabel').innerHTML = '<i class="fas fa-user-circle text-success me-2"></i> Novo Cliente';
        }

        <?php if($editCliente): ?>
            window.onload = () => {
                var modal = new bootstrap.Modal(document.getElementById('modalCliente'));
                modal.show();
            };
        <?php endif; ?>
    </script>
</body>
</html>