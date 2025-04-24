<?php
require_once '../functions/funcionarios.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $filtro = $_POST['filtro'];
    $funcionarios = listarFuncionarios($filtro);
    
    foreach ($funcionarios as $funcionario):
?>
    <tr>
        <td><?php echo $funcionario['nome']; ?></td>
        <td><?php echo $funcionario['cpf']; ?></td>
        <td><?php echo $funcionario['cargo']; ?></td>
        <td><?php echo $funcionario['telefone']; ?></td>
        <td>
            <span class="badge bg-<?php echo $funcionario['status'] == 'ativo' ? 'success' : 'danger'; ?>">
                <?php echo ucfirst($funcionario['status']); ?>
            </span>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-info" onclick="editarFuncionario(<?php echo $funcionario['id']; ?>)">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger" onclick="excluirFuncionario(<?php echo $funcionario['id']; ?>)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
<?php
    endforeach;
}
exit(); 