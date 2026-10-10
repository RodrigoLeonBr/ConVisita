<?php
require __DIR__ . '/inc/base.php';
exige_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    confere_csrf();
    exige_edicao();
    $id = (int)post('id');
    $seg = in_array(post('segmento'), ['usuario', 'trabalhador', 'gestor_prestador'], true) ? post('segmento') : null;
    if ($id) executa('UPDATE conselheiro SET nome=?, segmento=?, ativo=? WHERE id=?', [post('nome'), $seg, post('ativo') ? 1 : 0, $id]);
    elseif (post('nome')) executa('INSERT INTO conselheiro (nome, segmento) VALUES (?,?)', [post('nome'), $seg]);
    aviso('Conselheiro gravado.');
    redireciona('conselheiros.php');
}
$segs = ['usuario' => 'Usuários', 'trabalhador' => 'Trabalhadores', 'gestor_prestador' => 'Gestores/Prestadores'];
$lista = consulta('SELECT c.*, (SELECT COUNT(*) FROM visita_conselheiro vc WHERE vc.conselheiro_id = c.id) AS visitas FROM conselheiro c ORDER BY c.ativo DESC, c.nome');
$titulo = 'Conselheiros'; $menu = 'cadastros';
require __DIR__ . '/inc/topo.php';
?>
<h1>Conselheiros</h1>
<p class="sub">Cadastro usado para registrar quem participou de cada visita.</p>
<?php if (pode_editar()): ?>
<form method="post" class="cartao filtros"><?= csrf() ?>
  <div style="min-width:300px"><label>Nome</label><input type="text" name="nome" required></div>
  <div><label>Segmento</label><select name="segmento"><option value="">–</option><?php foreach ($segs as $k => $r): ?><option value="<?= $k ?>"><?= e($r) ?></option><?php endforeach; ?></select></div>
  <div><button class="btn">Incluir</button></div>
</form>
<?php endif; ?>
<div class="tabela"><table><tr><th>Nome</th><th>Segmento</th><th class="num">Visitas</th><th>Situação</th><th></th></tr>
<?php foreach ($lista as $c): ?>
  <tr>
    <td><form id="f<?= $c['id'] ?>" method="post"><?= csrf() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"></form><input form="f<?= $c['id'] ?>" type="text" name="nome" value="<?= e($c['nome']) ?>"></td>
    <td><select form="f<?= $c['id'] ?>" name="segmento"><option value="">–</option><?php foreach ($segs as $k => $r): ?><option value="<?= $k ?>" <?= $c['segmento'] === $k ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?></select></td>
    <td class="num"><?= (int)$c['visitas'] ?></td>
    <td><label style="color:inherit"><input form="f<?= $c['id'] ?>" type="checkbox" name="ativo" value="1" <?= $c['ativo'] ? 'checked' : '' ?>> ativo</label></td>
    <td><?php if (pode_editar()): ?><button form="f<?= $c['id'] ?>" class="btn peq sec">Gravar</button><?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if (!$lista): ?><tr><td colspan="5">Nenhum conselheiro cadastrado.</td></tr><?php endif; ?>
</table></div>
<?php require __DIR__ . '/inc/rodape.php';
