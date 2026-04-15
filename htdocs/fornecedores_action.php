<?php
require_once 'db.php';
require_once 'auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $acao = $_POST['acao'] ?? '';

    if ($acao == 'salvar') {
        $id = $_POST['id'] ?? '';
        $nome = trim($_POST['nome'] ?? '');
        $cnpj = trim($_POST['cnpj'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $status = $_POST['status'] ?? 'ativo';

        if ($id) {
            $stmt = $pdo->prepare("UPDATE fornecedores SET nome=?, cnpj=?, email=?, telefone=?, status=? WHERE id=?");
            $stmt->execute([$nome, $cnpj, $email, $telefone, $status, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO fornecedores (nome, cnpj, email, telefone, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $cnpj, $email, $telefone, $status]);
        }
        header("Location: fornecedores.php?msg=sucesso");
        exit;
    } elseif ($acao == 'deletar') {
        $id = $_POST['id'] ?? '';
        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM fornecedores WHERE id=?");
                $stmt->execute([$id]);
                header("Location: fornecedores.php?msg=sucesso");
            } catch (PDOException $e) {
                // Inativa se houver produtos vinculados
                $stmt = $pdo->prepare("UPDATE fornecedores SET status='inativo' WHERE id=?");
                $stmt->execute([$id]);
                header("Location: fornecedores.php?msg=inativado");
            }
        } else {
            header("Location: fornecedores.php");
        }
        exit;
    }
}
?>
