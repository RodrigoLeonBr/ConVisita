<h1 class="h3">Conselheiros</h1>
<p class="sub">Cadastro usado para registrar quem participou de cada visita.</p>
<?php if (pode_editar()): ?>
<form method="post" class="cartao filtros" action="<?= BASE ?>/conselheiros/salvar"><?= csrf() ?>
  <div style="min-width:300px"><label>Nome</label><input type="text" name="nome" required></div>
  <div><label>Segmento</label><select name="segmento"><option value="">–</option><?php foreach ($segs as $k => $r): ?><option value="<?= $k ?>"><?= e($r) ?></option><?php endforeach; ?></select></div>
  <div><button class="btn">Incluir</button></div>
</form>
<?php endif; ?>
<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th scope="col">Nome</th><th scope="col">Segmento</th><th scope="col" class="text-end">Visitas</th><th scope="col">Situação</th><th scope="col"></th></tr></thead><tbody>
<?php foreach ($lista as $c): ?>
  <tr>
    <td><form id="f<?= $c['id'] ?>" method="post" action="<?= BASE ?>/conselheiros/salvar"><?= csrf() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"></form><input form="f<?= $c['id'] ?>" type="text" name="nome" value="<?= e($c['nome']) ?>"></td>
    <td><select form="f<?= $c['id'] ?>" name="segmento"><option value="">–</option><?php foreach ($segs as $k => $r): ?><option value="<?= $k ?>" <?= $c['segmento'] === $k ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?></select></td>
    <td class="text-end"><?= (int)$c['visitas'] ?></td>
    <td><label style="color:inherit"><input form="f<?= $c['id'] ?>" type="checkbox" name="ativo" value="1" <?= $c['ativo'] ? 'checked' : '' ?>> ativo</label></td>
    <td><?php if (pode_editar()): ?><button form="f<?= $c['id'] ?>" class="btn peq sec">Gravar</button><?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if (!$lista): ?><tr><td colspan="5">Nenhum conselheiro cadastrado.</td></tr><?php endif; ?>
</tbody></table></div>
