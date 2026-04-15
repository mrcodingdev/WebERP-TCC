<?php
require_once 'db.php';
require_once 'auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $acao = $_POST['acao'] ?? '';

    if ($acao == 'salvar') {
        $id = $_POST['id'] ?? '';
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $status = $_POST['status'] ?? 'ativo';

        if ($id) {
            $stmt = $pdo->prepare("UPDATE clientes SET nome=?, email=?, telefone=?, status=? WHERE id=?");
            $stmt->execute([$nome, $email, $telefone, $status, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO clientes (nome, email, telefone, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $telefone, $status]);
        }
        header("Location: clientes.php?msg=sucesso");
        exit;
    } elseif ($acao == 'deletar') {
        $id = $_POST['id'] ?? '';
        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM clientes WHERE id=?");
                $stmt->execute([$id]);
                header("Location: clientes.php?msg=sucesso");
            } catch (PDOException $e) {
                // Se houver FK constraint dependente (compras já feitas)
                $stmt = $pdo->prepare("UPDATE clientes SET status='inativo' WHERE id=?");
                $stmt->execute([$id]);
                header("Location: clientes.php?msg=inativado");
            }
        } else {
            header("Location: clientes.php");
        }
        exit;
    }
}
?>
