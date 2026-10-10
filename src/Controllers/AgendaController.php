<?php
declare(strict_types=1);
namespace Convisita\Controllers;
use Convisita\Core\Controller;

final class AgendaController extends Controller
{
    public function index(array $p): void
    {
        $this->exigeLogin();
        $filtro = (string)get('situacao', '');
        $ordem = "FIELD(situacao_agenda,'atrasada','sem_visita','proximos_30_dias','em_dia','suspensa'), proxima_prevista";
        $lista = consulta('SELECT * FROM vw_agenda' . ($filtro ? ' WHERE situacao_agenda = ?' : '') . ' ORDER BY ' . $ordem, $filtro ? [$filtro] : []);
        $cont = [];
        foreach (consulta('SELECT situacao_agenda s, COUNT(*) n FROM vw_agenda GROUP BY situacao_agenda') as $c) $cont[$c['s']] = $c['n'];
        $this->render('agenda/index', ['titulo' => 'Agenda', 'lista' => $lista, 'cont' => $cont, 'filtro' => $filtro], 'agenda');
    }
}
