<?php
require_once 'db.php';
require_once 'auth.php';

$venda_id = $_GET['venda_id'] ?? null;

if (!$venda_id) {
    die("Cupom Fiscal não encontrado.");
}

// Busca dados da Venda
$stmtVenda = $pdo->prepare("SELECT * FROM vendas WHERE id = ?");
$stmtVenda->execute([$venda_id]);
$venda = $stmtVenda->fetch();

if (!$venda) {
    die("Venda não localizada no banco de dados.");
}

// Busca itens da Venda
$stmtItens = $pdo->prepare("
    SELECT vi.*, p.nome 
    FROM vendas_itens vi 
    JOIN produtos p ON vi.produto_id = p.id 
    WHERE vi.venda_id = ?
");
$stmtItens->execute([$venda_id]);
$itens = $stmtItens->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cupom Fiscal Eletrônico - NFC-e</title>
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        body {
            background-color: #555;
            display: flex;
            justify-content: center;
            padding: 20px;
            font-family: 'Courier New', Courier, monospace; /* Traz o estilo de bobina térmica */
        }
        .cupom {
            width: 320px;
            background-color: #fdfaf6; /* Um leve amarelado clássico de cupom térmico */
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.5);
            font-size: 13px;
            color: #000;
        }
        .header, .footer { text-align: center; }
        .header h3 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 2px 0; }
        .divider { border-top: 1px dashed #000; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 2px 0; font-size: 12px;}
        th { font-weight: normal; border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px;}
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total { font-weight: bold; font-size: 15px; }
        .qrcode {
            width: 120px;
            height: 120px;
            margin: 15px auto;
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=EasyStock_Venda_<?= $venda['id'] ?>') no-repeat center center;
            background-size: contain;
        }
        @media print {
            body { background: none; padding: 0; display: block; }
            .cupom { box-shadow: none; width: 100%; max-width: 80mm; margin: 0 auto; padding: 0; }
            .no-print { display: none; }
        }
        .btn-print {
            background: #16402c; color: white; border: none; padding: 10px 20px;
            cursor: pointer; border-radius: 5px; font-family: Arial; font-weight: bold;
            margin-bottom: 20px; display: block; width: 100%; text-align: center; font-size: 16px;
        }
        .btn-back { margin-top: 10px; background: #666;}
    </style>
</head>
<body>

    <div>
        <button class="no-print btn-print" onclick="window.print()"><i class="fas fa-print"></i> Imprimir Cupom</button>
        <button class="no-print btn-print btn-back" onclick="window.location='venda.php'"><i class="fas fa-arrow-left"></i> Voltar ao PDV</button>
        
        <div class="cupom">
            <div class="header">
                <h3>EASYSTOCK ERP</h3>
                <p>Comércio de Materiais e Papelaria</p>
                <p>CNPJ: 12.345.678/0001-90</p>
                <p>Centro - Sorocaba/SP</p>
                <div class="divider"></div>
                <p><strong>DANFE NFC-e - Documento Auxiliar</strong></p>
                <p><strong>da Nota Fiscal de Consumidor Eletrônica</strong></p>
                <p style="font-size: 10px;">Não permite aproveitamento de crédito ICMS</p>
            </div>

            <div class="divider"></div>

            <table>
                <thead>
                    <tr>
                        <th width="10%">QTD</th>
                        <th width="40%">DESCRIÇÃO</th>
                        <th width="25%" class="text-right">VL UN</th>
                        <th width="25%" class="text-right">VL TOT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $qtdTotalItems = 0;
                    foreach($itens as $i): 
                        $qtdTotalItems += $i['quantidade'];
                        $subtotal = $i['quantidade'] * $i['preco_unitario'];
                    ?>
                    <tr>
                        <td><?= str_pad($i['quantidade'], 2, '0', STR_PAD_LEFT) ?></td>
                        <td><?= substr(htmlspecialchars($i['nome']), 0, 15) ?>.</td>
                        <td class="text-right"><?= number_format($i['preco_unitario'], 2, ',', '.') ?></td>
                        <td class="text-right"><?= number_format($subtotal, 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="divider"></div>

            <table>
                <tr>
                    <td><strong>QTD. TOTAL DE ITENS</strong></td>
                    <td class="text-right"><strong><?= $qtdTotalItems ?></strong></td>
                </tr>
                <tr class="total">
                    <td>VALOR TOTAL R$</td>
                    <td class="text-right"><?= number_format($venda['total'], 2, ',', '.') ?></td>
                </tr>
                <tr>
                    <td>FORMA PAGAMENTO</td>
                    <td class="text-right">VALOR PAGO R$</td>
                </tr>
                <tr>
                    <td><?= htmlspecialchars($venda['forma_pagamento']) ?></td>
                    <td class="text-right"><?= number_format($venda['total'], 2, ',', '.') ?></td>
                </tr>
            </table>

            <div class="divider"></div>

            <div class="footer">
                <p><strong>Consulte pela Chave de Acesso em</strong></p>
                <p>http://nfce.fazenda.sp.gov.br/consulta</p>
                <p style="font-size: 11px; margin-top:5px; margin-bottom:5px;">3526 0312 3456 7800 0190 6500 1000 0120 4<?= str_pad($venda['id'], 3, '0', STR_PAD_LEFT) ?> 1234 5678</p>
                
                <p class="mt-2"><strong>CONSUMIDOR NÃO IDENTIFICADO</strong></p>
                
                <div class="qrcode"></div>
                
                <p><strong>Emissão:</strong> <?= date('d/m/Y H:i:s', strtotime($venda['data_venda'])) ?></p>
                <p><strong>Número:</strong> <?= str_pad($venda['id'], 6, '0', STR_PAD_LEFT) ?> <strong>Série:</strong> 1</p>
                
                <div class="divider"></div>
                <p><strong>Documento TCC - Sem Valor Fiscal</strong></p>
            </div>
        </div>
    </div>

</body>
</html>