<?php
require_once 'db.php';
require_once 'auth.php';

// Busca produtos para o PDV
$stmt = $pdo->query("SELECT id, nome, preco_venda as preco, quantidade FROM produtos WHERE quantidade > 0 ORDER BY nome");
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca clientes para vincular a venda
$stmtCli = $pdo->query("SELECT id, nome FROM clientes WHERE status = 'ativo' ORDER BY nome");
$clientes = $stmtCli->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockControl ERP - PDV (Frente de Caixa)</title>
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
            <li><a href="venda.php" class="active"><i class="fas fa-shopping-cart"></i> PDV Completo</a></li>
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
<div class="content-body" style="background-color: #fff; margin: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            
            <ul class="nav nav-tabs border-bottom-0" style="background-color: #f8f9fa; padding-top: 5px; padding-left: 5px; border-radius: 8px 8px 0 0;">
                <li class="nav-item">
                    <a class="nav-link active bg-primary text-white border-0 fw-bold" href="#"><i class="fas fa-shopping-basket me-2"></i> Caixa Aberto - Nova Venda</a>
                </li>
            </ul>

            <div class="p-4">
                
                <div class="row">
                    <!-- Esquerda: Adicionar Produtos -->
                    <div class="col-md-5 border-end pe-4">
                        <h5 class="text-primary fw-bold mb-3"><i class="fas fa-barcode"></i> Informar Produto</h5>
                        
                         <div class="mb-3">
                            <label class="form-label text-secondary fw-bold">Produto em Estoque <span class="text-danger">*</span></label>
                            <select id="produto_select" class="form-select form-select-lg shadow-sm border-0 bg-light">
                                <option value="">-- Selecione ou Bipe o Código --</option>
                                <?php foreach($produtos as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-nome="<?= htmlspecialchars($p['nome']) ?>" data-preco="<?= $p['preco'] ?>" data-max="<?= $p['quantidade'] ?>">
                                        <?= htmlspecialchars($p['nome']) ?> - R$ <?= number_format($p['preco'], 2, ',', '.') ?> (Estq: <?= $p['quantidade'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-bold">Quantidade</label>
                                <input type="number" id="qtd_input" class="form-control form-control-lg shadow-sm border-0 bg-light" value="1" min="1">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="button" class="btn btn-success btn-lg w-100 shadow-sm fw-bold" onclick="adicionarAoCarrinho()"><i class="fas fa-plus me-2"></i> Adicionar</button>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="text-primary fw-bold mb-3"><i class="fas fa-money-check-alt"></i> Finalizar Venda</h5>
                        <form action="venda_action.php" method="POST" id="formVenda">
                            <input type="hidden" name="acao" value="venda_completa">
                            <input type="hidden" name="cart_data" id="cart_data" value="[]">
                            
                            <div class="mb-3">
                                <label class="form-label text-secondary fw-bold">Cliente Vinculado</label>
                                <select name="cliente_id" class="form-select border-0 bg-light shadow-sm">
                                    <option value="">Consumidor Final (Sem Vínculo)</option>
                                    <?php foreach($clientes as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-secondary fw-bold">Forma de Pagamento <span class="text-danger">*</span></label>
                                <select name="forma_pagamento" class="form-select form-select-lg border-0 bg-light shadow-sm" required>
                                    <option value="DINHEIRO">Dinheiro Espécie</option>
                                    <option value="PIX">PIX Automático</option>
                                    <option value="CARTÃO DE CRÉDITO">Cartão de Crédito</option>
                                    <option value="CARTÃO DE DÉBITO">Cartão de Débito</option>
                                </select>
                            </div>
                            
                            <div class="p-3 bg-light border rounded shadow-sm text-center mb-4">
                                <h6 class="text-muted fw-bold mb-1">TOTAL DA COMPRA</h6>
                                <h1 class="text-success mb-0 fw-bold">R$ <span id="display_total">0,00</span></h1>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-outline-danger w-100 py-3 fw-bold" onclick="limparCarrinho()"><i class="fas fa-times me-2"></i> Cancelar</button>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-primary w-100 py-3 fw-bold shadow-sm" style="background-color: #0d6efd;" onclick="finalizarVenda()"><i class="fas fa-receipt me-2"></i> Emitir NFC-e</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Direita: Carrinho -->
                    <div class="col-md-7 ps-4 pb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-primary fw-bold m-0"><i class="fas fa-list-ol"></i> Itens no Cupom</h5>
                            <span class="badge bg-secondary rounded-pill" id="total_itens_badge">0 itens</span>
                        </div>
                        <div class="table-responsive border rounded shadow-sm" style="max-height: 450px; overflow-y: auto;">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th width="40%">Produto Selecionado</th>
                                        <th width="15%" class="text-center">Qtd</th>
                                        <th width="20%">Vlr. Unit</th>
                                        <th width="20%" class="text-end pe-4">Subtotal</th>
                                        <th width="5%" class="text-center text-danger"><i class="fas fa-trash"></i></th>
                                    </tr>
                                </thead>
                                <tbody id="tabela_carrinho">
                                    <tr>
                                        <td colspan="5" class="text-center text-muted p-5"><strong>O carrinho está vazio.</strong><br>Bipe ou selecione os itens ao lado.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        let carrinho = [];

        function formatarMoeda(valor) {
            return valor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function adicionarAoCarrinho() {
            const select = document.getElementById('produto_select');
            const qtdInput = document.getElementById('qtd_input');
            
            if (select.selectedIndex === 0 || !select.value) {
                alert("Selecione um produto primeiro.");
                return;
            }

            const option = select.options[select.selectedIndex];
            const id = parseInt(select.value);
            const nome = option.getAttribute('data-nome');
            const preco = parseFloat(option.getAttribute('data-preco'));
            const qtdMax = parseInt(option.getAttribute('data-max'));
            const qtd = parseInt(qtdInput.value);

            if (qtd <= 0 || qtd > qtdMax) {
                alert("Quantidade inválida ou superior ao estoque dispónivel (" + qtdMax + ").");
                return;
            }

            const itemExistente = carrinho.find(item => item.id === id);
            if (itemExistente) {
                if (itemExistente.quantidade + qtd > qtdMax) {
                    alert("Estoque insuficiente para essa quantidade.");
                    return;
                }
                itemExistente.quantidade += qtd;
            } else {
                carrinho.push({ id, nome, preco, quantidade: qtd });
            }

            select.value = "";
            qtdInput.value = "1";
            atualizarTabela();
        }

        function removerItem(id) {
            carrinho = carrinho.filter(item => item.id !== id);
            atualizarTabela();
        }

        function limparCarrinho() {
            if(carrinho.length > 0 && confirm("Deseja cancelar todos os itens?")) {
                carrinho = [];
                atualizarTabela();
            }
        }

        function atualizarTabela() {
            const tbody = document.getElementById('tabela_carrinho');
            const displayTotal = document.getElementById('display_total');
            const inputCartData = document.getElementById('cart_data');
            const totalItensBadge = document.getElementById('total_itens_badge');

            tbody.innerHTML = '';
            let totalVenda = 0;
            let totalQtd = 0;

            if (carrinho.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted p-5"><strong>O carrinho está vazio.</strong></td></tr>';
                displayTotal.innerText = '0,00';
                inputCartData.value = '[]';
                totalItensBadge.innerText = '0 itens';
                return;
            }

            carrinho.forEach(item => {
                const subtotal = item.quantidade * item.preco;
                totalVenda += subtotal;
                totalQtd += item.quantidade;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${item.nome}</strong><br><small class="text-muted">Cód: ${item.id.toString().padStart(4, '0')}</small></td>
                    <td class="text-center"><span class="badge bg-info text-dark" style="font-size:14px;">x${item.quantidade}</span></td>
                    <td>R$ ${formatarMoeda(item.preco)}</td>
                    <td class="text-end pe-4 text-success fw-bold">R$ ${formatarMoeda(subtotal)}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-danger" onclick="removerItem(${item.id})"><i class="fas fa-trash"></i></button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            displayTotal.innerText = formatarMoeda(totalVenda);
            inputCartData.value = JSON.stringify(carrinho);
            totalItensBadge.innerText = totalQtd + ' itens';
        }

        function finalizarVenda() {
            if (carrinho.length === 0) {
                alert("Adicione itens ao carrinho antes da emissão fiscal.");
                return;
            }
            document.getElementById('formVenda').submit();
        }
    </script>
</body>
</html>